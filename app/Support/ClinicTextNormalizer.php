<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class ClinicTextNormalizer
{
    public const PHILIPPINE_MOBILE_NUMBER_PATTERN = '/^09[0-9]{9}$/D';

    public static function trim(?string $value): ?string
    {
        return $value === null ? null : trim($value);
    }

    public static function patientContact(?string $value): string
    {
        $contact = self::trim($value);

        if (
            $contact === null
            || preg_match(self::PHILIPPINE_MOBILE_NUMBER_PATTERN, $contact) !== 1
        ) {
            throw ValidationException::withMessages([
                'patient_contact' => 'Enter an 11-digit Philippine mobile number starting with 09.',
            ]);
        }

        return $contact;
    }

    public static function procedureName(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $plainText = trim(strip_tags($value));

        return preg_replace('/\s+/u', ' ', $plainText) ?? $plainText;
    }
}
