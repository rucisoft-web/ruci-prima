<?php

namespace App\Services;

use App\Enums\Condition;
use App\Enums\Criticality;
use App\Enums\RiskLevel;
use App\Enums\RiskState;
use App\Enums\WorkType;
use App\Models\Asset;
use App\Models\AssetRisk;
use App\Models\CapexProposal;
use App\Models\PreventivePlan;
use App\Models\WorkOrder;
use Carbon\CarbonImmutable;

/**
 * Data Dasbor Eksekutif (setara gpch.asset.dashboard di Odoo), dihitung dengan agregasi SQL
 * untuk perusahaan aktif.
 */
class ExecutiveDashboard
{
    public function data(?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();
        $ytdStart = $today->startOfYear();

        $assets = Asset::query()->where('is_active', true);
        $totalAssets = (clone $assets)->count();
        $avgHealth = $totalAssets ? round((float) (clone $assets)->avg('health_score'), 1) : 0.0;

        $openWorkOrders = WorkOrder::query()->whereNotIn('state', ['closed', 'cancel'])->count();
        $pmTotal = PreventivePlan::query()->where('is_active', true)->count();
        $overduePm = PreventivePlan::query()->overdue()->count();
        $pmCompliance = $pmTotal ? round(($pmTotal - $overduePm) / $pmTotal * 100, 1) : 100.0;

        $ytdDone = WorkOrder::query()->whereIn('state', WorkOrder::COMPLETED_STATES)->where('completed_at', '>=', $ytdStart);
        $maintenanceCost = (float) (clone $ytdDone)->sum('total_cost');
        $capexRequired = (float) CapexProposal::query()->whereIn('state', CapexProposal::OPEN_STATES)->sum('estimated_cost');

        $openRisks = AssetRisk::query()->where('state', '!=', RiskState::Closed->value);
        $criticalRiskCount = (clone $openRisks)->where('level', RiskLevel::Critical->value)->count();
        $emergencyCount = WorkOrder::query()->where('work_type', WorkType::Emergency->value)->where('planned_at', '>=', $ytdStart)->count();

        // Indeks gabungan 0–100; bobot sama dengan modul Odoo (komponen 80×10% dan CAPEX 100×10% adalah konstanta di sana).
        $index = round(
            $avgHealth * 0.30
            + $pmCompliance * 0.20
            + max(0, 100 - $criticalRiskCount * 10) * 0.20
            + max(0, 100 - $emergencyCount * 5) * 0.10
            + 80 * 0.10
            + 100 * 0.10,
            1,
        );

        return [
            'today' => $today,
            'ytd_start' => $ytdStart,
            'kpi' => [
                'total_assets' => $totalAssets,
                'asset_value' => (float) (clone $assets)->sum('acquisition_cost'),
                'asset_health' => $avgHealth,
                'critical_assets' => (clone $assets)->needsAttention()->count(),
                'open_workorders' => $openWorkOrders,
                'overdue_pm' => $overduePm,
                'maintenance_cost' => $maintenanceCost,
                'capex_required' => $capexRequired,
                'asset_care_index' => max(0, min(100, $index)),
                'pm_compliance' => $pmCompliance,
            ],
            'health_by_department' => $this->healthByDepartment(),
            'condition_distribution' => $this->conditionDistribution(),
            'cost_by_category' => $this->costByCategory($ytdStart),
            'risk_matrix' => $this->riskMatrix(),
            'maintenance_trend' => $this->trend($today),
            'attention' => Asset::query()->where('is_active', true)->with('location')
                ->orderByDesc('risk_score')->limit(6)->get(),
        ];
    }

    protected function healthByDepartment(): array
    {
        return Asset::query()->where('assets.is_active', true)
            ->leftJoin('departments', 'departments.id', '=', 'assets.department_id')
            ->selectRaw('assets.department_id as id, COALESCE(departments.name, ?) as label, AVG(assets.health_score) as value, COUNT(*) as total', ['Tanpa departemen'])
            ->groupBy('assets.department_id', 'departments.name')
            ->orderBy('value')
            ->get()
            ->map(fn ($row) => ['id' => $row->id, 'label' => $row->label, 'value' => round((float) $row->value, 1), 'total' => (int) $row->total])
            ->all();
    }

    protected function conditionDistribution(): array
    {
        $counts = Asset::query()->where('is_active', true)->selectRaw('`condition` as c, COUNT(*) as n')->groupBy('condition')->pluck('n', 'c');
        $total = max(1, $counts->sum());

        return collect(Condition::cases())
            ->filter(fn (Condition $c) => isset($counts[$c->value]))
            ->map(fn (Condition $c) => [
                'key' => $c->value,
                'label' => $c->getLabel(),
                'value' => (int) $counts[$c->value],
                'percent' => round($counts[$c->value] / $total * 100),
            ])->values()->all();
    }

    protected function costByCategory(CarbonImmutable $ytdStart): array
    {
        $rows = WorkOrder::query()
            ->join('assets', 'assets.id', '=', 'work_orders.asset_id')
            ->join('asset_categories', 'asset_categories.id', '=', 'assets.asset_category_id')
            ->whereIn('work_orders.state', WorkOrder::COMPLETED_STATES)
            ->where('work_orders.completed_at', '>=', $ytdStart)
            ->selectRaw('asset_categories.id as id, asset_categories.name as label, SUM(work_orders.total_cost) as value')
            ->groupBy('asset_categories.id', 'asset_categories.name')
            ->orderByDesc('value')
            ->limit(8)
            ->get();

        $max = max(1, (float) $rows->max('value'));

        return $rows->map(fn ($row) => [
            'id' => $row->id, 'label' => $row->label, 'value' => (float) $row->value, 'percent' => round($row->value / $max * 100, 1),
        ])->all();
    }

    protected function riskMatrix(): array
    {
        $counts = AssetRisk::query()->where('state', '!=', RiskState::Closed->value)
            ->selectRaw('probability as p, consequence as c, COUNT(*) as n')
            ->groupBy('probability', 'consequence')
            ->get()
            ->mapWithKeys(fn ($r) => ["{$r->p}-{$r->c}" => (int) $r->n]);

        $cells = [];

        // Baris dari probabilitas tertinggi agar matriks terbaca seperti peta panas standar.
        for ($p = 5; $p >= 1; $p--) {
            for ($c = 1; $c <= 5; $c++) {
                $score = $p * $c;
                $cells[] = [
                    'p' => $p, 'c' => $c, 'count' => $counts["{$p}-{$c}"] ?? 0,
                    'level' => $score >= 20 ? 'critical' : ($score >= 12 ? 'high' : ($score >= 6 ? 'medium' : 'low')),
                ];
            }
        }

        return $cells;
    }

    protected function trend(CarbonImmutable $today): array
    {
        $start = $today->startOfMonth()->subMonths(11);
        $rows = WorkOrder::query()->where('planned_at', '>=', $start)->where('planned_at', '<', $today->startOfMonth()->addMonth())
            ->get(['planned_at', 'work_type'])
            ->groupBy(fn (WorkOrder $w) => $w->planned_at->format('Y-m'));

        $months = [];
        $max = 1;

        for ($i = 0; $i < 12; $i++) {
            $month = $start->addMonths($i);
            $items = $rows->get($month->format('Y-m'), collect());
            $entry = [
                'label' => $month->translatedFormat('M'),
                'full_label' => $month->translatedFormat('F Y'),
                'start' => $month->toDateString(),
                'end' => $month->addMonth()->toDateString(),
                'preventive' => $items->where('work_type', WorkType::Preventive)->count(),
                'corrective' => $items->where('work_type', WorkType::Corrective)->count(),
                'emergency' => $items->where('work_type', WorkType::Emergency)->count(),
            ];
            $max = max($max, $entry['preventive'], $entry['corrective'], $entry['emergency']);
            $months[] = $entry;
        }

        return ['months' => $months, 'max' => $max];
    }
}
