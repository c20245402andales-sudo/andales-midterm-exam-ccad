<?php

namespace App\Http\Requests\Concerns;

use App\Support\ClinicTextNormalizer;

trait NormalizesClinicInput
{
    protected function normalizeClinicFields(array $fields, array $procedureNameFields = []): void
    {
        $normalized = [];

        foreach ($fields as $field) {
            $value = $this->input($field);

            if (! is_string($value)) {
                continue;
            }

            $normalized[$field] = in_array($field, $procedureNameFields, true)
                ? ClinicTextNormalizer::procedureName($value)
                : ClinicTextNormalizer::trim($value);
        }

        $this->merge($normalized);
    }
}