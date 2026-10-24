<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status survei aset awal. */
enum SurveyState: string implements HasColor, HasLabel
{
    use HasOptions;

    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Cancelled = 'cancel';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::InProgress => 'Berjalan',
            self::Done => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::InProgress => 'warning',
            self::Done => 'success',
            self::Cancelled => 'gray',
        };
    }
}
