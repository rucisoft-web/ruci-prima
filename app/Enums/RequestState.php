<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status permintaan pemeliharaan. */
enum RequestState: string implements HasColor, HasLabel
{
    use HasOptions;

    case New = 'new';
    case Verified = 'verified';
    case Converted = 'converted';
    case Cancelled = 'cancel';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Baru',
            self::Verified => 'Diverifikasi',
            self::Converted => 'Work Order Dibuat',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Verified => 'warning',
            self::Converted => 'success',
            self::Cancelled => 'gray',
        };
    }
}
