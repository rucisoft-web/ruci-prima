<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status usulan CAPEX. */
enum CapexState: string implements HasColor, HasLabel
{
    use HasOptions;

    case Draft = 'draft';
    case Proposed = 'proposed';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Done = 'done';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Proposed => 'Diusulkan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Done => 'Terealisasi',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Proposed => 'info',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Done => 'success',
        };
    }
}
