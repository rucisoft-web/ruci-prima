<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Resources\AssetRisks\AssetRiskResource;
use App\Filament\App\Resources\Assets\AssetResource;
use App\Filament\App\Resources\CapexProposals\CapexProposalResource;
use App\Filament\App\Resources\PreventivePlans\PreventivePlanResource;
use App\Filament\App\Resources\WorkOrders\WorkOrderResource;
use App\Models\WorkOrder;
use App\Services\ExecutiveDashboard;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Dasbor eksekutif dengan drill-down: setiap kartu, batang, dan sel membuka data di baliknya. */
class DasborEksekutif extends Page
{
    protected static ?string $slug = 'dasbor-aset';

    protected static ?int $navigationSort = -10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Intelijen Aset';

    protected static ?string $navigationLabel = 'Dasbor Aset';

    protected static ?string $title = 'Dasbor Aset';

    protected string $view = 'filament.pages.dasbor-eksekutif';

    public function getHeading(): string
    {
        return '';
    }

    public function getViewData(): array
    {
        $data = app(ExecutiveDashboard::class)->data();
        $ytd = $data['ytd_start']->toDateString();
        $active = ['is_active' => ['value' => 1]];

        $assets = fn (array $filters = [], ?string $tab = null) => AssetResource::getUrl('index', array_filter([
            'tab' => $tab, 'filters' => $filters + $active,
        ]));
        $workOrders = fn (array $filters) => WorkOrderResource::getUrl('index', ['filters' => $filters]);
        $completed = ['state' => ['values' => WorkOrder::COMPLETED_STATES], 'completed' => ['from' => $ytd]];

        return [
            'd' => $data,
            'company' => filament()->getTenant(),
            'links' => [
                'assets' => $assets(),
                'health' => $assets([], 'perhatian'),
                'critical' => $assets([], 'kritis'),
                'department' => fn ($id) => $id ? $assets(['department_id' => ['value' => $id]]) : null,
                'condition' => fn ($key) => $assets(['condition' => ['values' => [$key]]]),
                'open_wo' => $workOrders(['state' => ['values' => ['draft', 'assigned', 'progress', 'waiting', 'done', 'verified']]]),
                'ytd_cost' => $workOrders($completed),
                'cost_category' => fn ($id) => $workOrders($completed + ['category' => ['value' => $id]]),
                'trend' => fn (array $m, ?string $type = null) => $workOrders(array_filter([
                    'planned' => ['from' => $m['start'], 'until' => $m['end']],
                    'work_type' => $type ? ['values' => [$type]] : null,
                ])),
                'overdue_pm' => PreventivePlanResource::getUrl('index', ['filters' => ['overdue' => ['isActive' => true], 'is_active' => ['value' => 1]]]),
                'pm' => PreventivePlanResource::getUrl('index'),
                'capex' => CapexProposalResource::getUrl('index', ['filters' => ['state' => ['values' => ['draft', 'proposed', 'approved']]]]),
                'risk' => fn (int $p, int $c) => AssetRiskResource::getUrl('index', ['filters' => [
                    'probability' => ['value' => $p], 'consequence' => ['value' => $c], 'state' => ['values' => ['open', 'mitigating']],
                ]]),
                'asset' => fn ($asset) => AssetResource::getUrl('view', ['record' => $asset]),
            ],
        ];
    }
}
