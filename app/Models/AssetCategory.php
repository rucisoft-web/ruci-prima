<?php

namespace App\Models;

use App\Enums\Criticality;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    use BelongsToCompany;

    protected $attributes = ['is_active' => true, 'default_criticality' => 'medium'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'default_criticality' => Criticality::class, 'expected_life_years' => 'float'];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
