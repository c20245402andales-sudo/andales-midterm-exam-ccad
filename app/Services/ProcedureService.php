<?php

namespace App\Services;

use App\Models\Procedure;
use App\Support\ClinicTextNormalizer;
use App\Support\UniqueConstraintValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcedureService
{
    public function create(array $data): Procedure
    {
        return UniqueConstraintValidation::run(
            fn () => Procedure::create($this->sanitize($data)),
            'procedure_name',
            'This procedure already exists.'
        );
    }

    public function update(Procedure $procedure, array $data): Procedure
    {
        return UniqueConstraintValidation::run(
            function () use ($procedure, $data): Procedure {
                $procedure->update($this->sanitize($data));

                return $procedure;
            },
            'procedure_name',
            'This procedure already exists.'
        );
    }

    public function delete(Procedure $procedure): void
    {
        DB::transaction(function () use ($procedure) {
            if ($procedure->appointmentEntries()->exists()) {
                throw ValidationException::withMessages([
                    'procedure' => 'This procedure is being used by an appointment and cannot be deleted.',
                ]);
            }

            $procedure->delete();
        });
    }

    private function sanitize(array $data): array
    {
        $name = ClinicTextNormalizer::procedureName($data['procedure_name']);

        $cost = $data['procedure_cost'];

        if (
            ! is_numeric($cost)
            || ! preg_match('/^\d+(?:\.\d{1,2})?$/D', (string) $cost)
            || (float) $cost > 99999999.99
        ) {
            throw ValidationException::withMessages([
                'procedure_cost' => 'The procedure cost must be a non-negative amount with no more than two decimal places and a maximum of 99,999,999.99.',
            ]);
        }

        return [
            'procedure_name' => $name,
            'procedure_cost' => $cost,
        ];
    }
}
