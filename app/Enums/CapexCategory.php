<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Kategori usulan CAPEX. */
enum CapexCategory: string implements HasColor, HasLabel
{
    use HasOptions;

    case Mandatory = 'mandatory';
    case Safety = 'safety';
    case Guest = 'guest';
    case Revenue = 'revenue';
    case Efficiency = 'efficiency';
    case Nice = 'nice';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mandatory => 'Wajib',
            self::Safety => 'Keselamatan',
            self::Guest => 'Pengalaman Pelanggan/Tamu',
            self::Revenue => 'Peningkatan Pendapatan',
            self::Efficiency => 'Efisiensi',
            self::Nice => 'Pelengkap',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Mandatory => 'danger',
            self::Safety => 'danger',
            self::Guest => 'info',
            self::Revenue => 'success',
            self::Efficiency => 'success',
            self::Nice => 'gray',
        };
    }
}
