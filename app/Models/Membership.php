<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Keanggotaan pengguna di satu perusahaan beserta perannya. */
class Membership extends Model
{
    use BelongsToCompany;

    protected $attributes = ['is_active' => true];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['role' => Role::class, 'is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
