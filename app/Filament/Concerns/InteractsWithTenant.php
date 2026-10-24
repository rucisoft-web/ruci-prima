<?php

namespace App\Filament\Concerns;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Membership;
use App\Models\User;
use Filament\Facades\Filament;

/** Pintasan untuk resource dan halaman: pengguna, perusahaan aktif, peran. */
trait InteractsWithTenant
{
    protected static function tenantUser(): ?User
    {
        /** @var User|null */
        return auth()->user();
    }

    protected static function tenantCompany(): ?Company
    {
        /** @var Company|null */
        return Filament::getTenant();
    }

    protected static function tenantRole(): ?Role
    {
        return static::tenantUser()?->roleIn(static::tenantCompany());
    }

    /** Staf dan manajer boleh mencatat data operasional; eksekutif hanya membaca. */
    protected static function canOperate(): bool
    {
        return (bool) static::tenantRole()?->canOperate();
    }

    protected static function canManage(): bool
    {
        return (bool) static::tenantRole()?->canManage();
    }

    /** @return array<int, string> anggota aktif perusahaan ini. */
    protected static function memberOptions(): array
    {
        return Membership::query()->with('user')->where('is_active', true)->get()
            ->sortBy('user.name')
            ->mapWithKeys(fn (Membership $m) => [$m->user_id => $m->user->name])
            ->all();
    }
}
