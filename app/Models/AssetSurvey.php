<?php

namespace App\Models;

use App\Enums\AssignmentState;
use App\Enums\IdentificationSource;
use App\Enums\SurveyState;
use App\Enums\VerificationStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Survei identifikasi aset awal (inventarisasi lapangan). */
class AssetSurvey extends Model
{
    use Auditable, BelongsToCompany;

    protected $attributes = ['state' => 'draft'];

    protected $guarded = ['id'];

    protected array $tracked = ['state', 'name'];

    protected function casts(): array
    {
        return ['state' => SurveyState::class, 'date_start' => 'date', 'date_end' => 'date'];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetSurveyAssignment::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function start(): void
    {
        $this->update(['state' => SurveyState::InProgress]);
        $this->assignments()->where('state', AssignmentState::Draft->value)->update(['state' => AssignmentState::Assigned->value]);
    }

    public function complete(): void
    {
        $this->update(['state' => SurveyState::Done, 'date_end' => $this->date_end ?? now()]);
    }

    /** Ringkasan kemajuan survei. */
    public function stats(): array
    {
        $assets = $this->assets()->toBase();
        $total = $this->assignments()->count();
        $completed = $this->assignments()->where('state', AssignmentState::Done->value)->count();

        return [
            'total_assignments' => $total,
            'completed_assignments' => $completed,
            'total_expected' => (clone $assets)->where('identification_source', IdentificationSource::Template->value)->count(),
            'verified' => (clone $assets)->where('verification_status', VerificationStatus::Verified->value)->count(),
            'missing' => (clone $assets)->where('verification_status', VerificationStatus::Missing->value)->count(),
            'additional' => (clone $assets)->where('identification_source', IdentificationSource::Additional->value)->count(),
            'progress' => $total ? round($completed / $total * 100, 1) : 0.0,
        ];
    }
}
