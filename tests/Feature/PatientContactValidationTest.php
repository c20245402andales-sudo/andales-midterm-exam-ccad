<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use App\Services\PatientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PatientContactValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_store_accepts_and_preserves_a_valid_philippine_mobile_number(): void
    {
        $this->postJson('/patients', [
            'patient_code' => 'PAT-VALID',
            'patient_name' => 'Valid Patient',
            'patient_contact' => '09123456789',
        ])->assertRedirect(route('patients.index'));

        $this->assertDatabaseHas('patients', [
            'patient_code' => 'PAT-VALID',
            'patient_contact' => '09123456789',
        ]);
    }

    #[DataProvider('invalidPatientContactNumbers')]
    public function test_store_rejects_invalid_contact_numbers(string $contact): void
    {
        $this->postJson('/patients', [
            'patient_code' => 'PAT-INVALID',
            'patient_name' => 'Invalid Patient',
            'patient_contact' => $contact,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('patient_contact');
    }

    public static function invalidPatientContactNumbers(): array
    {
        return [
            'letters' => ['09123ABC789'],
            'symbols' => ['0912-345-6789'],
            'too short' => ['0912345678'],
            'too long' => ['091234567890'],
            'wrong prefix' => ['08123456789'],
            'empty' => [''],
        ];
    }

    public function test_update_accepts_a_valid_contact_number(): void
    {
        $patient = $this->createPatient();

        $this->putJson("/patients/{$patient->id}", [
            'patient_code' => $patient->patient_code,
            'patient_name' => $patient->patient_name,
            'patient_contact' => '09987654321',
        ])->assertRedirect(route('patients.index'));

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'patient_contact' => '09987654321',
        ]);
    }

    public function test_update_rejects_an_invalid_contact_number_without_changing_the_record(): void
    {
        $patient = $this->createPatient();

        $this->putJson("/patients/{$patient->id}", [
            'patient_code' => $patient->patient_code,
            'patient_name' => $patient->patient_name,
            'patient_contact' => '0912-345-6789',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('patient_contact');

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'patient_contact' => '09123456789',
        ]);
    }

    public function test_patient_service_rejects_invalid_contacts_instead_of_cleaning_them(): void
    {
        try {
            app(PatientService::class)->create([
                'patient_code' => 'PAT-SERVICE',
                'patient_name' => 'Service Patient',
                'patient_contact' => '09123ABC789',
            ]);
            $this->fail('Invalid patient contact numbers must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('patient_contact', $exception->errors());
        }

        $this->assertDatabaseMissing('patients', [
            'patient_code' => 'PAT-SERVICE',
        ]);
    }

    private function createPatient(): Patient
    {
        return Patient::create([
            'patient_code' => 'PAT-UPDATE',
            'patient_name' => 'Update Patient',
            'patient_contact' => '09123456789',
        ]);
    }
}
