<?php

namespace App\Models;

use App\Enums\RiskLevel;
use App\Enums\RiskState;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use App\Support\Scoring;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetRisk extends Model
{
    use Auditable, BelongsToCompany;

    protected $attributes = ['state' => 'open', 'probability' => 2, 'consequence' => 2];

    protected $guarded = ['id'];

    protected array $companyParents = ['asset_id' => Asset::class];

    protected array $tracked = ['probability', 'consequence', 'state'];

    protected function casts(): array
    {
        return ['level' => RiskLevel::class, 'state' => RiskState::class, 'target_date' => 'date'];
    }

    protected static function booted(): void
    {
        static::saving(function (AssetRisk $risk) {
            [$risk->risk_score, $risk->level] = Scoring::riskMatrix((int) $risk->probability ?: 2, (int) $risk->consequence ?: 2);
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
