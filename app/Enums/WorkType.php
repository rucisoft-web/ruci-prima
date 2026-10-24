<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Jenis pekerjaan pemeliharaan. */
enum WorkType: string implements HasColor, HasLabel
{
    use HasOptions;

    case Preventive = 'preventive';
    case Corrective = 'corrective';
    case Emergency = 'emergency';
    case Overhaul = 'overhaul';

    public function getLabel(): string
    {
        return match ($this) {
            self::Preventive => 'Preventif',
            self::Corrective => 'Korektif',
            self::Emergency => 'Darurat',
            self::Overhaul => 'Overhaul',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Preventive => 'success',
            self::Corrective => 'info',
            self::Emergency => 'danger',
            self::Overhaul => 'warning',
        };
    }
}
