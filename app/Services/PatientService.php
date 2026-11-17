<?php

namespace App\Services;

use App\Models\Patient;
use App\Support\ClinicTextNormalizer;
use App\Support\UniqueConstraintValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientService
{
    public function create(array $data): Patient
    {
        return UniqueConstraintValidation::run(
            fn () => Patient::create($this->sanitize($data)),
            'patient_code',
            'This patient code is already registered.'
        );
    }

    public function update(Patient $patient, array $data): Patient
    {
        return UniqueConstraintValidation::run(
            function () use ($patient, $data): Patient {
                $patient->update($this->sanitize($data));

                return $patient;
            },
            'patient_code',
            'This patient code is already registered.'
        );
    }

    public function delete(Patient $patient): void
    {
        DB::transaction(function () use ($patient) {
            if ($patient->appointments()->exists()) {
                throw ValidationException::withMessages([
                    'patient' => 'This patient has existing appointments and cannot be deleted.',
                ]);
            }

            $patient->delete();
        });
    }

    private function sanitize(array $data): array
    {
        return [
            'patient_code' => ClinicTextNormalizer::trim($data['patient_code']),
            'patient_name' => ClinicTextNormalizer::trim($data['patient_name']),
            'patient_contact' => ClinicTextNormalizer::patientContact(
                $data['patient_contact'] ?? null
            ),
        ];
    }
}
