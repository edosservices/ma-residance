<?php

declare(strict_types=1);

namespace App\Support;

final class Phone
{
    public static function normalize(string $input): string
    {
        $raw = trim($input);
        $hasPlus = str_starts_with($raw, '+');
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            throw new DomainException('Le téléphone est invalide.');
        }

        if (! $hasPlus && str_starts_with($digits, '0')) {
            $digits = (string) config('residence.phone_country_code', '243').substr($digits, 1);
        }

        if (strlen($digits) < 8 || strlen($digits) > 15) {
            throw new DomainException('Le téléphone est invalide.');
        }

        return '+'.$digits;
    }
}
