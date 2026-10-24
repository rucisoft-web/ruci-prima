<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status risiko. */
enum RiskState: string implements HasColor, HasLabel
{
    use HasOptions;

    case Open = 'open';
    case Mitigating = 'mitigating';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Terbuka',
            self::Mitigating => 'Dalam Mitigasi',
            self::Closed => 'Ditutup',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'danger',
            self::Mitigating => 'warning',
            self::Closed => 'gray',
        };
    }
}
