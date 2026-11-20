<?php
namespace App\Support;
final class Money
{
    public static function cents(string $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }
    public static function decimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string)($cents % 100), 2, '0', STR_PAD_LEFT);
    }
    public static function format(string|int|float|null $value): string
    {
        return '₱'.number_format((float)($value ?? 0), 2);
    }
}
