<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Jenis lokasi pada templat aset. */
enum LocationType: string implements HasColor, HasLabel
{
    use HasOptions;

    case Room = 'room';
    case Meeting = 'meeting';
    case PublicArea = 'public';
    case BackOffice = 'boh';
    case Plant = 'plant';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Room => 'Kamar / Ruang Unit',
            self::Meeting => 'Ruang Rapat / Acara',
            self::PublicArea => 'Area Publik',
            self::BackOffice => 'Area Belakang (BOH)',
            self::Plant => 'Plant / Utilitas',
            self::Other => 'Lainnya',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Room => 'gray',
            self::Meeting => 'gray',
            self::PublicArea => 'gray',
            self::BackOffice => 'gray',
            self::Plant => 'gray',
            self::Other => 'gray',
        };
    }
}
