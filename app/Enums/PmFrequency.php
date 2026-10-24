<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Frekuensi pemeliharaan preventif. */
enum PmFrequency: string implements HasColor, HasLabel
{
    use HasOptions;

    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Semiannual = 'semiannual';
    case Annual = 'annual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Weekly => 'Mingguan',
            self::Monthly => 'Bulanan',
            self::Quarterly => 'Triwulanan',
            self::Semiannual => 'Semesteran',
            self::Annual => 'Tahunan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Weekly => 'gray',
            self::Monthly => 'gray',
            self::Quarterly => 'gray',
            self::Semiannual => 'gray',
            self::Annual => 'gray',
        };
    }
}
