<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Departemen / unit kerja pemilik aset (diatur per perusahaan). */
class Department extends Model
{
    use BelongsToCompany;

    protected $attributes = ['is_active' => true];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
