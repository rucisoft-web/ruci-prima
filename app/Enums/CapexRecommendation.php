<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Rekomendasi tindakan atas aset. */
enum CapexRecommendation: string implements HasColor, HasLabel
{
    use HasOptions;

    case Repair = 'repair';
    case Replace = 'replace';
    case Overhaul = 'overhaul';
    case Monitor = 'monitor';
    case Retire = 'retire';
    case Investigate = 'investigate';

    public function getLabel(): string
    {
        return match ($this) {
            self::Repair => 'Perbaiki',
            self::Replace => 'Ganti',
            self::Overhaul => 'Overhaul',
            self::Monitor => 'Pantau',
            self::Retire => 'Hapus',
            self::Investigate => 'Investigasi',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Repair => 'info',
            self::Replace => 'warning',
            self::Overhaul => 'warning',
            self::Monitor => 'gray',
            self::Retire => 'gray',
            self::Investigate => 'info',
        };
    }
}
