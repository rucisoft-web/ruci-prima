<?php

namespace App\Models;

use App\Enums\CapexCategory;
use App\Enums\CapexRecommendation;
use App\Enums\CapexState;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use App\Support\Scoring;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapexProposal extends Model
{
    use Auditable, BelongsToCompany;

    protected $attributes = ['state' => 'draft', 'category' => 'mandatory', 'recommendation' => 'replace'];

    protected $guarded = ['id'];

    protected array $companyParents = ['asset_id' => Asset::class];

    protected array $tracked = ['name', 'state', 'estimated_cost'];

    public const OPEN_STATES = ['draft', 'proposed', 'approved'];

    protected function casts(): array
    {
        return [
            'category' => CapexCategory::class,
            'recommendation' => CapexRecommendation::class,
            'state' => CapexState::class,
            'estimated_cost' => 'decimal:2',
            'priority_score' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CapexProposal $capex) {
            $capex->target_year ??= (int) now()->format('Y');
            $capex->priority_score = Scoring::capexPriority(
                (int) ($capex->guest_impact ?? 3), (int) ($capex->safety_impact ?? 3),
                (int) ($capex->revenue_impact ?? 3), (int) ($capex->urgency ?? 3),
            );
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
