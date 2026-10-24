<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status operasional aset. */
enum AssetStatus: string implements HasColor, HasLabel
{
    use HasOptions;

    case Operational = 'operational';
    case Monitor = 'monitor';
    case Repair = 'repair';
    case OutOfService = 'out_of_service';
    case Retired = 'retired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Operational => 'Beroperasi',
            self::Monitor => 'Dipantau',
            self::Repair => 'Dalam Perbaikan',
            self::OutOfService => 'Tidak Beroperasi',
            self::Retired => 'Dihapusbukukan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Operational => 'success',
            self::Monitor => 'warning',
            self::Repair => 'warning',
            self::OutOfService => 'danger',
            self::Retired => 'gray',
        };
    }
}
