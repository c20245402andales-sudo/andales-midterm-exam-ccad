<?php

namespace App\Services;

use App\Models\DentalAppointment;
use App\Models\Dentist;
use App\Models\Procedure;
use App\Support\UniqueConstraintValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DentalAppointmentService
{
    /**
     * Create a new dental appointment.
     */
    public function create(array $data): DentalAppointment
    {
        return UniqueConstraintValidation::run(
            fn () => DB::transaction(function () use ($data) {
                $this->checkDentistSchedule(
                    $data['dentist_id'],
                    $data['scheduled_datetime']
                );

                $appointment = DentalAppointment::create([
                    'patient_id' => $data['patient_id'],
                    'dentist_id' => $data['dentist_id'],
                    'scheduled_datetime' => $data['scheduled_datetime'],
                    'payment_status' => $data['payment_status'] ?? 'unpaid',
                ]);

                $this->syncProcedures(
                    $appointment,
                    $data['procedures']
                );

                return $appointment->load([
                    'patient',
                    'dentist',
                    'procedureEntries.procedure',
                ]);
            }),
            'procedures',
            'A procedure and tooth combination is already assigned to this appointment.'
        );
    }

    /**
     * Update an existing dental appointment.
     */
    public function update(
        DentalAppointment $appointment,
        array $data
    ): DentalAppointment {
        return UniqueConstraintValidation::run(
            fn () => DB::transaction(function () use ($appointment, $data) {
                $this->checkDentistSchedule(
                    $data['dentist_id'],
                    $data['scheduled_datetime'],
                    $appointment->id
                );

                $appointment->update([
                    'patient_id' => $data['patient_id'],
                    'dentist_id' => $data['dentist_id'],
                    'scheduled_datetime' => $data['scheduled_datetime'],
                    'payment_status' => $data['payment_status'],
                ]);

                $this->syncProcedures(
                    $appointment,
                    $data['procedures']
                );

                return $appointment->load([
                    'patient',
                    'dentist',
                    'procedureEntries.procedure',
                ]);
            }),
            'procedures',
            'A procedure and tooth combination is already assigned to this appointment.'
        );
    }

    /**
     * Delete an existing dental appointment.
     */
    public function delete(DentalAppointment $appointment): void
    {
        DB::transaction(function () use ($appointment) {

            $appointment->procedureEntries()->delete();

            $appointment->delete();
        });
    }

    /**
     * Check whether the dentist already has an appointment
     * at the requested date and time.
     */
    private function checkDentistSchedule(
        int $dentistId,
        string $scheduledDatetime,
        ?int $ignoreId = null
    ): void {
        Dentist::query()
            ->whereKey($dentistId)
            ->lockForUpdate()
            ->firstOrFail();

        $query = DentalAppointment::where(
            'dentist_id',
            $dentistId
        )->where(
            'scheduled_datetime',
            $scheduledDatetime
        );

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'scheduled_datetime' => 'This dentist already has an appointment at that time.',
            ]);
        }
    }

    /**
     * Synchronize the procedures associated with an appointment.
     */
    private function syncProcedures(
        DentalAppointment $appointment,
        array $procedures
    ): void {
        $existingCosts = $appointment->procedureEntries()
            ->get()
            ->keyBy(fn ($entry) => $entry->procedure_id.':'.$entry->tooth_number);

        $appointment->procedureEntries()->delete();

        $procedureIds = array_values(array_unique(
            array_column($procedures, 'procedure_id')
        ));

        $procedureRecords = Procedure::query()
            ->whereKey($procedureIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($procedures as $item) {
            $procedure = $procedureRecords->get($item['procedure_id'])
                ?? Procedure::findOrFail($item['procedure_id']);
            $combination = $procedure->id.':'.$item['tooth_number'];

            $appointment->procedureEntries()->create([
                'procedure_id' => $procedure->id,
                'tooth_number' => $item['tooth_number'],
                'unit_cost' => $existingCosts->get($combination)?->unit_cost
                    ?? $procedure->procedure_cost,
            ]);
        }
    }
}
