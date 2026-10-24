<?php

namespace App\Models;

use App\Enums\AssignmentState;
use App\Enums\IdentificationSource;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Penugasan tim survei ke satu lokasi. */
class AssetSurveyAssignment extends Model
{
    protected $attributes = ['state' => 'draft'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['state' => AssignmentState::class];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(AssetSurvey::class, 'asset_survey_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AssetTemplate::class, 'asset_template_id');
    }

    public function surveyors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'asset_survey_assignment_user');
    }

    /** Hitungan aset survei ini di lokasi penugasan. */
    public function counts(): array
    {
        $rows = Asset::withoutGlobalScopes()
            ->where('asset_survey_id', $this->asset_survey_id)
            ->where('location_id', $this->location_id)
            ->get(['verification_status', 'identification_source']);

        $checked = $rows->filter(fn (Asset $a) => in_array($a->verification_status, [VerificationStatus::Verified, VerificationStatus::Missing, VerificationStatus::Additional], true))->count();

        return [
            'expected' => $rows->where('identification_source', IdentificationSource::Template)->count(),
            'verified' => $rows->where('verification_status', VerificationStatus::Verified)->count(),
            'missing' => $rows->where('verification_status', VerificationStatus::Missing)->count(),
            'additional' => $rows->where('identification_source', IdentificationSource::Additional)->count(),
            'progress' => $rows->count() ? round($checked / $rows->count() * 100, 1) : 0.0,
        ];
    }
}
