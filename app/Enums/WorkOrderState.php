<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status work order. */
enum WorkOrderState: string implements HasColor, HasLabel
{
    use HasOptions;

    case Draft = 'draft';
    case Assigned = 'assigned';
    case Progress = 'progress';
    case Waiting = 'waiting';
    case Done = 'done';
    case Verified = 'verified';
    case Closed = 'closed';
    case Cancelled = 'cancel';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Assigned => 'Ditugaskan',
            self::Progress => 'Dikerjakan',
            self::Waiting => 'Menunggu Suku Cadang/Vendor',
            self::Done => 'Selesai',
            self::Verified => 'Diverifikasi',
            self::Closed => 'Ditutup',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Assigned => 'info',
            self::Progress => 'warning',
            self::Waiting => 'warning',
            self::Done => 'success',
            self::Verified => 'success',
            self::Closed => 'gray',
            self::Cancelled => 'gray',
        };
    }
}
