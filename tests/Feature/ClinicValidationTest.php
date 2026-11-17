<?php

namespace Tests\Feature;

use App\Models\DentalAppointment;
use App\Models\Dentist;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\User;
use App\Services\ProcedureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClinicValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_appointment_can_be_updated_with_its_unchanged_past_datetime(): void
    {
        [$appointment, $patient, $dentist, $procedure] = $this->createAppointment(
            now()->subDay()->startOfMinute()
        );

        $response = $this->putJson(
            "/dental-appointments/{$appointment->id}",
            $this->appointmentPayload($appointment->scheduled_datetime->toDateTimeString(), $patient, $dentist, $procedure)
        );

        $response->assertRedirect(route('dental-appointments.index'));
        $this->assertDatabaseHas('dental_appointments', [
            'id' => $appointment->id,
            'payment_status' => 'paid',
        ]);
    }

    public function test_appointment_update_preserves_existing_procedure_cost_snapshots(): void
    {
        [$appointment, $patient, $dentist, $procedure] = $this->createAppointment(
            now()->addDays(2)->startOfMinute()
        );

        $procedure->update(['procedure_cost' => '75.00']);
        $payload = $this->appointmentPayload(
            $appointment->scheduled_datetime->toDateTimeString(),
            $patient,
            $dentist,
            $procedure
        );
        $payload['procedures'][0]['unit_cost'] = 0;
        $payload['procedures'][] = [
            'procedure_id' => $procedure->id,
            'tooth_number' => 2,
            'unit_cost' => 0,
        ];

        $this->putJson("/dental-appointments/{$appointment->id}", $payload)
            ->assertRedirect(route('dental-appointments.index'));

        $this->assertDatabaseHas('dental_appointment_procedure', [
            'dental_appointment_id' => $appointment->id,
            'procedure_id' => $procedure->id,
            'tooth_number' => 1,
            'unit_cost' => '40.00',
        ]);
        $this->assertDatabaseHas('dental_appointment_procedure', [
            'dental_appointment_id' => $appointment->id,
            'procedure_id' => $procedure->id,
            'tooth_number' => 2,
            'unit_cost' => '75.00',
        ]);
    }

    public function test_dentist_cannot_be_scheduled_for_the_same_datetime_twice(): void
    {
        [, $patient, $dentist, $procedure] = $this->createAppointment(
            now()->addDays(2)->startOfMinute()
        );

        $this->postJson('/dental-appointments', $this->appointmentPayload(
            now()->addDays(2)->startOfMinute()->toDateTimeString(),
            $patient,
            $dentist,
            $procedure
        ))->assertUnprocessable()
            ->assertJsonValidationErrors('scheduled_datetime');
    }

    public function test_appointment_index_calculates_total_from_saved_procedure_cost_snapshots(): void
    {
        [$appointment] = $this->createAppointment(
            now()->addDays(2)->startOfMinute()
        );

        $this->get('/dental-appointments')
            ->assertInertia(fn ($page) => $page
                ->where('appointments.data.0.id', $appointment->id)
                ->where('appointments.data.0.total_cost', 40));
    }

    public function test_appointment_update_rejects_a_changed_past_datetime(): void
    {
        [$appointment, $patient, $dentist, $procedure] = $this->createAppointment(
            now()->subDay()->startOfMinute()
        );

        $response = $this->putJson(
            "/dental-appointments/{$appointment->id}",
            $this->appointmentPayload(now()->subDays(2)->startOfMinute()->toDateTimeString(), $patient, $dentist, $procedure)
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('scheduled_datetime');
    }

    public function test_appointment_rejects_duplicate_procedure_and_tooth_combinations(): void
    {
        [, $patient, $dentist, $procedure] = $this->createAppointment(
            now()->addDays(2)->startOfMinute()
        );

        $payload = $this->appointmentPayload(now()->addDays(3)->startOfMinute()->toDateTimeString(), $patient, $dentist, $procedure);
        $payload['procedures'][] = $payload['procedures'][0];

        $this->postJson('/dental-appointments', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('procedures');
    }

    public function test_patient_store_and_update_reject_whitespace_only_fields(): void
    {
        $this->postJson('/patients', [
            'patient_code' => '   ',
            'patient_name' => "\t  ",
            'patient_contact' => '5551234',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['patient_code', 'patient_name']);

        $patient = Patient::create([
            'patient_code' => 'PAT-1',
            'patient_name' => 'Patient One',
            'patient_contact' => '5551234',
        ]);

        $this->putJson("/patients/{$patient->id}", [
            'patient_code' => 'PAT-1',
            'patient_name' => '   ',
            'patient_contact' => '   ',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['patient_name', 'patient_contact']);
    }

    public function test_dentist_store_and_update_reject_whitespace_only_fields(): void
    {
        $this->postJson('/dentists', [
            'dentist_name' => '   ',
            'dentist_license' => "\t",
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['dentist_name', 'dentist_license']);

        $dentist = Dentist::create([
            'dentist_name' => 'Dr. One',
            'dentist_license' => 'LIC-1',
        ]);

        $this->putJson("/dentists/{$dentist->id}", [
            'dentist_name' => '   ',
            'dentist_license' => 'LIC-1',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('dentist_name');
    }

    public function test_procedure_store_and_update_reject_whitespace_only_names(): void
    {
        $this->postJson('/procedures', [
            'procedure_name' => " \t ",
            'procedure_cost' => '20.00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('procedure_name');

        $procedure = Procedure::create([
            'procedure_name' => 'Cleaning',
            'procedure_cost' => '20.00',
        ]);

        $this->putJson("/procedures/{$procedure->id}", [
            'procedure_name' => '   ',
            'procedure_cost' => '20.00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('procedure_name');
    }

    public function test_procedure_cost_cannot_exceed_the_database_decimal_limit(): void
    {
        $this->postJson('/procedures', [
            'procedure_name' => 'Expensive procedure',
            'procedure_cost' => '100000000.00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('procedure_cost');
    }

    public function test_guests_cannot_access_clinic_records(): void
    {
        auth()->logout();

        $this->get('/patients')->assertRedirect('/login');
        $this->post('/dental-appointments')->assertRedirect('/login');
    }

    public function test_patient_and_dentist_search_filters_records_server_side(): void
    {
        Patient::create([
            'patient_code' => 'FIND-1',
            'patient_name' => 'Searchable Patient',
            'patient_contact' => '5551001',
        ]);
        Patient::create([
            'patient_code' => 'OTHER-1',
            'patient_name' => 'Other Patient',
            'patient_contact' => '5551002',
        ]);
        Dentist::create([
            'dentist_name' => 'Dr. Searchable',
            'dentist_license' => 'FIND-LIC',
        ]);
        Dentist::create([
            'dentist_name' => 'Dr. Other',
            'dentist_license' => 'OTHER-LIC',
        ]);

        $this->get('/patients?search=FIND')
            ->assertInertia(fn ($page) => $page
                ->where('search', 'FIND')
                ->where('patients.total', 1)
                ->where('patients.data.0.patient_code', 'FIND-1'));

        $this->get('/dentists?search=FIND')
            ->assertInertia(fn ($page) => $page
                ->where('search', 'FIND')
                ->where('dentists.total', 1)
                ->where('dentists.data.0.dentist_license', 'FIND-LIC'));
    }

    public function test_procedure_uniqueness_uses_the_normalized_name_on_store_and_update(): void
    {
        Procedure::create([
            'procedure_name' => 'Routine cleaning',
            'procedure_cost' => '40.00',
        ]);
        $procedure = Procedure::create([
            'procedure_name' => 'Filling',
            'procedure_cost' => '60.00',
        ]);

        $this->postJson('/procedures', [
            'procedure_name' => '  <strong>Routine   cleaning</strong>  ',
            'procedure_cost' => '45.00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('procedure_name');

        $this->putJson("/procedures/{$procedure->id}", [
            'procedure_name' => '<span>Routine cleaning</span>',
            'procedure_cost' => '65.00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('procedure_name');
    }

    public function test_procedure_service_translates_database_unique_conflicts(): void
    {
        Procedure::create([
            'procedure_name' => 'Polishing',
            'procedure_cost' => '30.00',
        ]);

        try {
            app(ProcedureService::class)->create([
                'procedure_name' => '<b>Polishing</b>',
                'procedure_cost' => '30.00',
            ]);
            $this->fail('A duplicate normalized procedure should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('procedure_name', $exception->errors());
        }
    }

    private function createAppointment(Carbon $scheduledDatetime): array
    {
        $patient = Patient::create([
            'patient_code' => 'PAT-1',
            'patient_name' => 'Patient One',
            'patient_contact' => '5551234',
        ]);
        $dentist = Dentist::create([
            'dentist_name' => 'Dr. One',
            'dentist_license' => 'LIC-1',
        ]);
        $procedure = Procedure::create([
            'procedure_name' => 'Cleaning',
            'procedure_cost' => '40.00',
        ]);
        $appointment = DentalAppointment::create([
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'scheduled_datetime' => $scheduledDatetime,
            'payment_status' => 'unpaid',
        ]);
        $appointment->procedureEntries()->create([
            'procedure_id' => $procedure->id,
            'tooth_number' => 1,
            'unit_cost' => $procedure->procedure_cost,
        ]);

        return [$appointment, $patient, $dentist, $procedure];
    }

    private function appointmentPayload(
        string $scheduledDatetime,
        Patient $patient,
        Dentist $dentist,
        Procedure $procedure
    ): array {
        return [
            'patient_id' => $patient->id,
            'dentist_id' => $dentist->id,
            'scheduled_datetime' => $scheduledDatetime,
            'payment_status' => 'paid',
            'procedures' => [[
                'procedure_id' => $procedure->id,
                'tooth_number' => 1,
            ]],
        ];
    }
}
