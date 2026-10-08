<?php

declare(strict_types=1);

namespace App\Support;

final class Money
{
    public static function format(int $minor, string $currency): string
    {
        $negative = $minor < 0;
        $minor = abs($minor);
        $decimals = (int) config("residence.currencies.$currency.decimals", 2);
        $factor = 10 ** max(0, $decimals);
        $major = intdiv($minor, $factor);
        $fraction = $minor % $factor;
        $formatted = number_format($major, 0, ',', ' ');

        if ($fraction > 0) {
            $formatted .= ','.str_pad((string) $fraction, $decimals, '0', STR_PAD_LEFT);
        }

        $symbol = (string) config("residence.currencies.$currency.symbol", $currency);

        return ($negative ? '−' : '').$formatted.' '.$symbol;
    }

    public static function toMinor(string $input): int
    {
        $normalized = str_replace([' ', "\u{00A0}", "\u{202F}"], '', trim($input));
        $normalized = str_replace(',', '.', $normalized);

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $normalized)) {
            throw new DomainException('Le montant est invalide.');
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '00');

        if ((int) $whole > 9999999999) {
            throw new DomainException('Le montant est trop élevé.');
        }

        $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);

        return ((int) $whole) * 100 + (int) $fraction;
    }

    public static function prorate(int $amount, int $numerator, int $denominator): int
    {
        if ($amount <= 0 || $numerator <= 0 || $denominator <= 0) {
            return 0;
        }

        if ($numerator >= $denominator) {
            return $amount;
        }

        $product = bcmul((string) $amount, (string) $numerator, 0);
        $half = intdiv($denominator, 2);
        $sum = bcadd($product, (string) $half, 0);

        return (int) bcdiv($sum, (string) $denominator, 0);
    }

    public static function convertMinor(int $minor, string $rate): int
    {
        $product = bcmul((string) $minor, self::normalizeRate($rate), 8);

        return (int) self::roundHalfUp($product, 0);
    }

    public static function convertMinorInverse(int $minor, string $rate): int
    {
        $normalized = self::normalizeRate($rate);

        if (bccomp($normalized, '0', 8) !== 1) {
            throw new DomainException('Le taux de change est invalide.');
        }

        $product = bcdiv((string) $minor, $normalized, 8);

        return (int) self::roundHalfUp($product, 0);
    }

    public static function normalizeRate(string $rate): string
    {
        $normalized = str_replace([' ', "\u{00A0}", "\u{202F}"], '', trim($rate));
        $normalized = str_replace(',', '.', $normalized);

        if (! preg_match('/^\d+(\.\d{1,8})?$/', $normalized) || bccomp($normalized, '0', 8) !== 1) {
            throw new DomainException('Le taux de change est invalide.');
        }

        return bcadd($normalized, '0', 8);
    }

    public static function roundHalfUp(string $number, int $scale = 0): string
    {
        $negative = str_starts_with($number, '-');
        $number = ltrim($number, '-');

        if (! str_contains($number, '.')) {
            $number .= '.0';
        }

        [$integer, $fraction] = explode('.', $number, 2);
        $fraction = str_pad($fraction, $scale + 1, '0');
        $keep = $scale > 0 ? substr($fraction, 0, $scale) : '';
        $next = (int) substr($fraction, $scale, 1);
        $base = $scale > 0 ? $integer.'.'.$keep : $integer;

        if ($next >= 5) {
            $increment = $scale > 0
                ? '0.'.str_repeat('0', $scale - 1).'1'
                : '1';
            $base = bcadd($base, $increment, $scale);
        } elseif ($scale > 0) {
            $base = $integer.'.'.str_pad($keep, $scale, '0');
        }

        return $negative ? '-'.$base : $base;
    }
}
