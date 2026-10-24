<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Klasifikasi kondisi dari skor kesehatan aset. */
enum Condition: string implements HasColor, HasLabel
{
    use HasOptions;

    case Excellent = 'excellent';
    case Good = 'good';
    case Fair = 'fair';
    case Poor = 'poor';
    case Critical = 'critical';

    public function getLabel(): string
    {
        return match ($this) {
            self::Excellent => 'Sangat Baik',
            self::Good => 'Baik',
            self::Fair => 'Cukup',
            self::Poor => 'Buruk',
            self::Critical => 'Kritis',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Excellent => 'success',
            self::Good => 'success',
            self::Fair => 'warning',
            self::Poor => 'danger',
            self::Critical => 'danger',
        };
    }
}
