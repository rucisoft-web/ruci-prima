<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/** Izin standar data operasional: semua anggota melihat, staf/manajer mencatat, manajer menghapus. */
trait OperationalPermissions
{
    use InteractsWithTenant;

    public static function canCreate(): bool
    {
        return static::canOperate();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canOperate();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canManage();
    }

    public static function canDeleteAny(): bool
    {
        return static::canManage();
    }
}
