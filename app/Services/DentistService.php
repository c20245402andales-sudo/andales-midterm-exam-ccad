<?php

namespace App\Services;

use App\Models\Dentist;
use App\Support\ClinicTextNormalizer;
use App\Support\UniqueConstraintValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DentistService
{
    public function create(array $data): Dentist
    {
        return UniqueConstraintValidation::run(
            fn () => Dentist::create($this->sanitize($data)),
            'dentist_license',
            'This dentist license number is already registered.'
        );
    }

    public function update(Dentist $dentist, array $data): Dentist
    {
        return UniqueConstraintValidation::run(
            function () use ($dentist, $data): Dentist {
                $dentist->update($this->sanitize($data));

                return $dentist;
            },
            'dentist_license',
            'This dentist license number is already registered.'
        );
    }

    public function delete(Dentist $dentist): void
    {
        DB::transaction(function () use ($dentist) {
            if ($dentist->appointments()->exists()) {
                throw ValidationException::withMessages([
                    'dentist' =>
                        'This dentist has existing appointments and cannot be deleted.',
                ]);
            }

            $dentist->delete();
        });
    }

    private function sanitize(array $data): array
    {
        return [
            'dentist_name' => ClinicTextNormalizer::trim($data['dentist_name']),
            'dentist_license' => ClinicTextNormalizer::trim($data['dentist_license']),
        ];
    }
}
