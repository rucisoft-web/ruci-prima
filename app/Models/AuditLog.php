<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use BelongsToCompany;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    /** Dimatikan saat memuat data contoh agar jejak audit tidak dipenuhi baris seeder. */
    public static bool $enabled = true;

    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'datetime'];
    }

    public static function record(string $event, Model $model, ?array $changes = null): void
    {
        if (! static::$enabled || ! $model->company_id) {
            return;
        }

        static::withoutGlobalScopes()->create([
            'company_id' => $model->company_id,
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'label' => method_exists($model, 'auditLabel') ? mb_substr($model->auditLabel(), 0, 250) : null,
            'changes' => $changes,
            'ip_address' => request()?->ip(),
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function typeLabel(): string
    {
        return match (class_basename($this->auditable_type)) {
            'Asset' => 'Aset',
            'Inspection' => 'Inspeksi',
            'MaintenanceRequest' => 'Permintaan Pemeliharaan',
            'WorkOrder' => 'Work Order',
            'PreventivePlan' => 'Rencana PM',
            'AssetRisk' => 'Risiko',
            'CapexProposal' => 'Usulan CAPEX',
            'AssetSurvey' => 'Survei Aset',
            default => class_basename($this->auditable_type),
        };
    }
}
