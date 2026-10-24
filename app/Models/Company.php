<?php

namespace App\Models;

use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** Perusahaan / properti (tenant). Seluruh data aset berinduk ke sini. */
class Company extends Model implements HasName
{
    protected $attributes = ['is_active' => true, 'is_demo' => false];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_demo' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Company $company) {
            $base = Str::slug($company->code ?: $company->name) ?: 'perusahaan';
            $slug = $base;

            for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }

            $company->slug ??= $slug;
        });
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    // Relasi yang dibutuhkan Filament untuk resource bertenant.
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function assetCategories(): HasMany
    {
        return $this->hasMany(AssetCategory::class);
    }

    public function assetTemplates(): HasMany
    {
        return $this->hasMany(AssetTemplate::class);
    }

    public function assetSurveys(): HasMany
    {
        return $this->hasMany(AssetSurvey::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function preventivePlans(): HasMany
    {
        return $this->hasMany(PreventivePlan::class);
    }

    public function assetRisks(): HasMany
    {
        return $this->hasMany(AssetRisk::class);
    }

    public function capexProposals(): HasMany
    {
        return $this->hasMany(CapexProposal::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
