<?php

namespace App\Support;

class FilamentNumberFormatter
{
    public static function format(mixed $state, int $decimals = 0): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (! is_numeric($state)) {
            return (string) $state;
        }

        return number_format((float) $state, $decimals, '.', ',');
    }
}
