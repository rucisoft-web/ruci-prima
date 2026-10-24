<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Jenis permintaan pemeliharaan. */
enum RequestType: string implements HasColor, HasLabel
{
    use HasOptions;

    case Corrective = 'corrective';
    case Emergency = 'emergency';
    case Inspection = 'inspection';

    public function getLabel(): string
    {
        return match ($this) {
            self::Corrective => 'Korektif / Kerusakan',
            self::Emergency => 'Darurat',
            self::Inspection => 'Temuan Inspeksi',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Corrective => 'info',
            self::Emergency => 'danger',
            self::Inspection => 'warning',
        };
    }
}
