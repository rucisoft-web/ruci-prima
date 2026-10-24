<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/** Izin data konfigurasi: hanya manajer yang melihat menu dan mengubah. */
trait ManagerPermissions
{
    use InteractsWithTenant;

    public static function canViewAny(): bool
    {
        return static::canManage();
    }

    public static function canCreate(): bool
    {
        return static::canManage();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canManage();
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
