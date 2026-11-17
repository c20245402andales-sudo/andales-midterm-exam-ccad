<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueProcedureToothCombinations implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        $combinations = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $procedureId = filter_var($item['procedure_id'] ?? null, FILTER_VALIDATE_INT);
            $toothNumber = filter_var($item['tooth_number'] ?? null, FILTER_VALIDATE_INT);

            if ($procedureId === false || $toothNumber === false) {
                continue;
            }

            $combination = $procedureId.'-'.$toothNumber;

            if (isset($combinations[$combination])) {
                $fail('The same procedure cannot be assigned to the same tooth more than once.');

                return;
            }

            $combinations[$combination] = true;
        }
    }
}