<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Asal pencatatan aset. */
enum IdentificationSource: string implements HasColor, HasLabel
{
    use HasOptions;

    case Manual = 'manual';
    case Template = 'template';
    case Copied = 'copied';
    case Duplicated = 'duplicated';
    case Additional = 'additional';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Template => 'Dari Templat',
            self::Copied => 'Disalin dari Lokasi',
            self::Duplicated => 'Duplikasi',
            self::Additional => 'Ditemukan di Lapangan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Manual => 'gray',
            self::Template => 'info',
            self::Copied => 'info',
            self::Duplicated => 'info',
            self::Additional => 'warning',
        };
    }
}
