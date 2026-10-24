<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\WorkOrderState;
use App\Enums\WorkType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use App\Support\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class WorkOrder extends Model
{
    use Auditable, BelongsToCompany;

    protected $attributes = ['state' => 'draft', 'work_type' => 'corrective', 'priority' => 'medium', 'sparepart_cost' => 0, 'vendor_cost' => 0, 'other_cost' => 0];

    protected $guarded = ['id'];

    protected array $companyParents = ['asset_id' => Asset::class];

    protected array $tracked = ['state', 'priority', 'technician_id', 'total_cost'];

    /** Transisi status yang sah: aksi => [status asal yang diizinkan, status tujuan]. */
    public const TRANSITIONS = [
        'assign' => [['draft'], 'assigned'],
        'start' => [['draft', 'assigned', 'waiting'], 'progress'],
        'wait' => [['progress'], 'waiting'],
        'done' => [['progress', 'waiting'], 'done'],
        'verify' => [['done'], 'verified'],
        'close' => [['done', 'verified'], 'closed'],
        'cancel' => [['draft', 'assigned', 'progress', 'waiting'], 'cancel'],
    ];

    public const OPEN_STATES = ['draft', 'assigned', 'progress', 'waiting', 'done', 'verified'];

    public const COMPLETED_STATES = ['done', 'verified', 'closed'];

    protected function casts(): array
    {
        return [
            'work_type' => WorkType::class,
            'priority' => Priority::class,
            'state' => WorkOrderState::class,
            'planned_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'labor_hours' => 'float',
            'downtime_hours' => 'float',
            'sparepart_cost' => 'decimal:2',
            'vendor_cost' => 'decimal:2',
            'other_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WorkOrder $workOrder) {
            $workOrder->number ??= Sequence::next('work_order', $workOrder->company_id);
            $workOrder->planned_at ??= now();
        });

        static::saving(function (WorkOrder $workOrder) {
            $workOrder->total_cost = (float) $workOrder->sparepart_cost + (float) $workOrder->vendor_cost + (float) $workOrder->other_cost;
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class, 'maintenance_request_id');
    }

    public function preventivePlan(): BelongsTo
    {
        return $this->belongsTo(PreventivePlan::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function can(string $action): bool
    {
        return in_array($this->state->value, self::TRANSITIONS[$action][0] ?? [], true);
    }

    public function transition(string $action): void
    {
        if (! $this->can($action)) {
            throw new LogicException("Aksi {$action} tidak berlaku untuk status {$this->state->getLabel()}.");
        }

        $data = ['state' => self::TRANSITIONS[$action][1]];

        if ($action === 'start' && ! $this->started_at) {
            $data['started_at'] = now();
        }

        if ($action === 'done') {
            $data['completed_at'] = now();
        }

        $this->update($data);
    }

    public function auditLabel(): string
    {
        return (string) $this->number;
    }
}
