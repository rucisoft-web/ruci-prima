<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\RequestState;
use App\Enums\RequestType;
use App\Enums\WorkType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use App\Support\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use LogicException;

/** Laporan kerusakan / permintaan pemeliharaan, termasuk dari pindai QR. */
class MaintenanceRequest extends Model
{
    use Auditable, BelongsToCompany;

    protected $attributes = ['state' => 'new', 'request_type' => 'corrective', 'priority' => 'medium', 'via_qr' => false];

    protected $guarded = ['id'];

    protected array $companyParents = ['asset_id' => Asset::class];

    protected array $tracked = ['state', 'priority', 'asset_id'];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'via_qr' => 'boolean',
            'request_type' => RequestType::class,
            'priority' => Priority::class,
            'state' => RequestState::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MaintenanceRequest $request) {
            $request->number ??= Sequence::next('request', $request->company_id);
            $request->requested_at ??= now();

            if (! $request->via_qr) {
                $request->reporter_id ??= auth()->id();
            }
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function reporterLabel(): string
    {
        return $this->reporter?->name ?? ($this->reporter_name ? $this->reporter_name.' (QR)' : ($this->via_qr ? 'Anonim (QR)' : '—'));
    }

    public function verify(): void
    {
        if ($this->state !== RequestState::New) {
            throw new LogicException('Hanya permintaan baru yang bisa diverifikasi.');
        }

        $this->update(['state' => RequestState::Verified]);
    }

    public function cancel(): void
    {
        if ($this->state === RequestState::Converted) {
            throw new LogicException('Permintaan yang sudah menjadi work order tidak bisa dibatalkan.');
        }

        $this->update(['state' => RequestState::Cancelled]);
    }

    public function createWorkOrder(): WorkOrder
    {
        if (! in_array($this->state, [RequestState::New, RequestState::Verified], true)) {
            throw new LogicException('Work order hanya bisa dibuat dari permintaan baru atau terverifikasi.');
        }

        return DB::transaction(function () {
            $workOrder = WorkOrder::create([
                'company_id' => $this->company_id,
                'asset_id' => $this->asset_id,
                'maintenance_request_id' => $this->id,
                'work_type' => $this->request_type === RequestType::Emergency ? WorkType::Emergency : WorkType::Corrective,
                'priority' => $this->priority,
                'description' => $this->description,
            ]);

            $this->update(['state' => RequestState::Converted, 'work_order_id' => $workOrder->id]);

            return $workOrder;
        });
    }

    public function auditLabel(): string
    {
        return (string) $this->number;
    }
}
