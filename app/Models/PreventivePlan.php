<?php

namespace App\Models;

use App\Enums\PmFrequency;
use App\Enums\WorkType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PreventivePlan extends Model
{
    use Auditable, BelongsToCompany;

    protected $attributes = ['is_active' => true, 'frequency' => 'monthly'];

    protected $guarded = ['id'];

    protected array $companyParents = ['asset_id' => Asset::class];

    protected array $tracked = ['next_due_date', 'frequency', 'is_active'];

    protected function casts(): array
    {
        return ['frequency' => PmFrequency::class, 'next_due_date' => 'date', 'is_active' => 'boolean'];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereDate('next_due_date', '<', today());
    }

    public function isOverdue(): bool
    {
        return $this->is_active && $this->next_due_date->lt(today());
    }

    public function nextDateAfter(CarbonInterface $date): CarbonInterface
    {
        return match ($this->frequency) {
            PmFrequency::Weekly => $date->copy()->addWeek(),
            PmFrequency::Monthly => $date->copy()->addMonthNoOverflow(),
            PmFrequency::Quarterly => $date->copy()->addMonthsNoOverflow(3),
            PmFrequency::Semiannual => $date->copy()->addMonthsNoOverflow(6),
            PmFrequency::Annual => $date->copy()->addYearNoOverflow(),
        };
    }

    /** Terbitkan work order preventif untuk jadwal ini lalu majukan tanggal jatuh tempo. */
    public function issueWorkOrder(): WorkOrder
    {
        return DB::transaction(function () {
            $workOrder = WorkOrder::create([
                'company_id' => $this->company_id,
                'asset_id' => $this->asset_id,
                'preventive_plan_id' => $this->id,
                'work_type' => WorkType::Preventive,
                'technician_id' => $this->responsible_id,
                'planned_at' => $this->next_due_date->copy()->setTime(8, 0),
                'description' => trim($this->name."\n\n".($this->checklist ?? '')),
            ]);

            $this->update(['next_due_date' => $this->nextDateAfter($this->next_due_date)]);

            return $workOrder;
        });
    }

    public function auditLabel(): string
    {
        return (string) $this->name;
    }
}
