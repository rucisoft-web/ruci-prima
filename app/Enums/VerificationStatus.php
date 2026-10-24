<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status verifikasi lapangan pada identifikasi awal. */
enum VerificationStatus: string implements HasColor, HasLabel
{
    use HasOptions;

    case Expected = 'expected';
    case Verified = 'verified';
    case Missing = 'missing';
    case Additional = 'additional';
    case NeedsId = 'needs_id';

    public function getLabel(): string
    {
        return match ($this) {
            self::Expected => 'Diharapkan',
            self::Verified => 'Terverifikasi',
            self::Missing => 'Tidak Ditemukan',
            self::Additional => 'Aset Tambahan',
            self::NeedsId => 'Perlu Identifikasi',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Expected => 'info',
            self::Verified => 'success',
            self::Missing => 'danger',
            self::Additional => 'warning',
            self::NeedsId => 'gray',
        };
    }
}
