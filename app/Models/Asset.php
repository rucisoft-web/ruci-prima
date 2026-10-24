<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Enums\Condition;
use App\Enums\Criticality;
use App\Enums\IdentificationSource;
use App\Enums\Priority;
use App\Enums\VerificationStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use App\Support\Scoring;
use App\Support\Sequence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Register aset. Skor kesehatan, kekritisan, risiko, dan prioritas dihitung
 * ulang setiap kali disimpan (setara field compute store=True di Odoo).
 */
class Asset extends Model
{
    use Auditable, BelongsToCompany;

    protected $attributes = ['is_active' => true, 'status' => 'operational', 'verification_status' => 'needs_id', 'identification_source' => 'manual'];

    protected $guarded = ['id', 'qr_token'];

    protected array $companyParents = [
        'asset_category_id' => AssetCategory::class,
        'location_id' => Location::class,
        'department_id' => Department::class,
        'asset_survey_id' => AssetSurvey::class,
    ];

    protected array $tracked = ['name', 'code', 'asset_category_id', 'location_id', 'department_id', 'status', 'verification_status'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'purchase_date' => 'date',
            'verified_at' => 'datetime',
            'acquisition_cost' => 'decimal:2',
            'replacement_cost' => 'decimal:2',
            'expected_life_years' => 'float',
            'health_score' => 'float',
            'criticality_score' => 'float',
            'risk_score' => 'float',
            'condition' => Condition::class,
            'criticality' => Criticality::class,
            'priority' => Priority::class,
            'status' => AssetStatus::class,
            'verification_status' => VerificationStatus::class,
            'identification_source' => IdentificationSource::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Asset $asset) {
            $asset->qr_token = Str::random(40);

            if (blank($asset->code)) {
                $asset->code = Sequence::next('asset', $asset->company_id);
            }

            if (! $asset->isDirty('expected_life_years') && $asset->asset_category_id) {
                $life = AssetCategory::withoutGlobalScopes()->whereKey($asset->asset_category_id)->value('expected_life_years');
                $asset->expected_life_years = $life ?? 5;
            }
        });

        static::saving(fn (Asset $asset) => $asset->recalculate());
    }

    public function recalculate(): void
    {
        $raw = $this->getAttributes();
        $pick = fn (array $fields, int $default) => array_map(fn ($f) => $raw[$f] ?? $default, array_combine($fields, $fields));

        [$health, $condition] = Scoring::health($pick(array_keys(Scoring::HEALTH_WEIGHTS), 80));
        [$critScore, $criticality] = Scoring::criticality($pick(Scoring::CRITICALITY_FIELDS, 2));
        [$risk, $priority] = Scoring::assetRisk($health, $critScore);

        $this->health_score = $health;
        $this->condition = $condition;
        $this->criticality_score = $critScore;
        $this->criticality = $criticality;
        $this->risk_score = $risk;
        $this->priority = $priority;
    }

    // --- Relasi ---------------------------------------------------------

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(AssetSurvey::class, 'asset_survey_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class)->latest('inspected_at');
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

    public function risks(): HasMany
    {
        return $this->hasMany(AssetRisk::class);
    }

    public function capexProposals(): HasMany
    {
        return $this->hasMany(CapexProposal::class);
    }

    // --- Umur dan siklus hidup (dihitung saat dibaca agar selalu terkini) --

    public function ageYears(): float
    {
        return $this->purchase_date ? round(max($this->purchase_date->diffInDays(now()) / 365.25, 0), 2) : 0.0;
    }

    public function remainingLifeYears(): float
    {
        return round(max($this->expected_life_years - $this->ageYears(), 0), 2);
    }

    // --- Verifikasi lapangan ------------------------------------------

    public function markVerification(VerificationStatus $status): void
    {
        $data = ['verification_status' => $status, 'verified_by_id' => auth()->id(), 'verified_at' => now()];

        if ($status === VerificationStatus::Additional) {
            $data['identification_source'] = IdentificationSource::Additional;
        }

        $this->update($data);
    }

    // --- QR -----------------------------------------------------------

    public function qrUrl(): string
    {
        return route('qr.show', $this->qr_token);
    }

    public function regenerateQr(): void
    {
        $this->forceFill(['qr_token' => Str::random(40)])->save();
    }

    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->where('criticality', Criticality::Critical->value)->orWhere('condition', Condition::Critical->value));
    }
}
