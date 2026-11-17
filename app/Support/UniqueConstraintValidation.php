<?php

namespace App\Support;

use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final class UniqueConstraintValidation
{
    public static function run(Closure $operation, string $field, string $message): mixed
    {
        try {
            return $operation();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                $field => $message,
            ]);
        }
    }
}