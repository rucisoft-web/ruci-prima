<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Hasil inspeksi. */
enum InspectionResult: string implements HasColor, HasLabel
{
    use HasOptions;

    case Pass = 'pass';
    case Monitor = 'monitor';
    case Fail = 'fail';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pass => 'LULUS',
            self::Monitor => 'PANTAU',
            self::Fail => 'GAGAL',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pass => 'success',
            self::Monitor => 'warning',
            self::Fail => 'danger',
        };
    }
}
