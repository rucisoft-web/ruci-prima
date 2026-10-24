<?php

namespace App\Models;

use App\Enums\InspectionResult;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use App\Support\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inspection extends Model
{
    use Auditable, BelongsToCompany;

    protected $attributes = ['result' => 'pass', 'condition_score' => 80];

    protected $guarded = ['id'];

    protected array $companyParents = ['asset_id' => Asset::class];

    protected array $tracked = ['result', 'condition_score'];

    protected function casts(): array
    {
        return ['inspected_at' => 'datetime', 'result' => InspectionResult::class, 'meter_reading' => 'float'];
    }

    protected static function booted(): void
    {
        static::creating(function (Inspection $inspection) {
            $inspection->number ??= Sequence::next('inspection', $inspection->company_id);
            $inspection->inspected_at ??= now();
            $inspection->inspector_id ??= auth()->id();
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function auditLabel(): string
    {
        return (string) $this->number;
    }
}
