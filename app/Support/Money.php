<?php

namespace App\Support;

class Money
{
    public static function format(float|int|string|null $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }

    /** Format ringkas untuk kartu dasbor: Rp 1,2 M / Rp 350,5 Jt. */
    public static function short(float|int|string|null $value): string
    {
        $n = (float) $value;

        return match (true) {
            abs($n) >= 1e12 => 'Rp '.number_format($n / 1e12, 1, ',', '.').' T',
            abs($n) >= 1e9 => 'Rp '.number_format($n / 1e9, 1, ',', '.').' M',
            abs($n) >= 1e6 => 'Rp '.number_format($n / 1e6, 1, ',', '.').' Jt',
            default => static::format($n),
        };
    }
}
