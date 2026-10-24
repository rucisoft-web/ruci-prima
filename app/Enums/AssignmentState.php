<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status penugasan tim survei. */
enum AssignmentState: string implements HasColor, HasLabel
{
    use HasOptions;

    case Draft = 'draft';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Assigned => 'Ditugaskan',
            self::InProgress => 'Berjalan',
            self::Done => 'Selesai',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Assigned => 'info',
            self::InProgress => 'warning',
            self::Done => 'success',
        };
    }
}
