<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetTemplateLine extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['expected_life_years' => 'float'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AssetTemplate::class, 'asset_template_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
