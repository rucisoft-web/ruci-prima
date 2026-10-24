<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Resources\AssetRisks\AssetRiskResource;
use App\Filament\App\Resources\Assets\AssetResource;
use App\Filament\App\Resources\CapexProposals\CapexProposalResource;
use App\Filament\App\Resources\LegalDocuments\LegalDocumentResource;
use App\Filament\App\Resources\Vehicles\VehicleResource;
use App\Filament\App\Resources\WorkOrders\WorkOrderResource;
use App\Services\LeadershipDashboard;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Satu layar untuk direksi dan komisaris: indeks kesiapan, keputusan yang menunggu, grafik tren, dan catatan otomatis. */
class DasborPimpinan extends Page
{
    protected static ?string $slug = 'dasbor-pimpinan';

    protected static ?int $navigationSort = -20;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static string|UnitEnum|null $navigationGroup = 'Pimpinan';

    protected static ?string $navigationLabel = 'Dasbor Pimpinan';

    protected static ?string $title = 'Dasbor Pimpinan';

    protected string $view = 'filament.pages.dasbor-pimpinan';

    public function getHeading(): string
    {
        return '';
    }

    public function getViewData(): array
    {
        return [
            'd' => app(LeadershipDashboard::class)->data(),
            'company' => filament()->getTenant(),
            'links' => [
                'assets' => AssetResource::getUrl('index'),
                'capex' => CapexProposalResource::getUrl('index', ['filters' => ['state' => ['values' => ['draft', 'proposed']]]]),
                'risks' => AssetRiskResource::getUrl('index', ['filters' => ['state' => ['values' => ['open', 'mitigating']]]]),
                'risk' => fn (int $p, int $c) => AssetRiskResource::getUrl('index', ['filters' => [
                    'probability' => ['value' => $p], 'consequence' => ['value' => $c], 'state' => ['values' => ['open', 'mitigating']],
                ]]),
                'documents' => LegalDocumentResource::getUrl('index', ['activeTab' => 'tindakan']),
                'work_orders' => WorkOrderResource::getUrl('index'),
                'vehicles' => VehicleResource::getUrl('index'),
                'rooms' => JadwalRuangRapat::getUrl(),
                'asset_dashboard' => DasborEksekutif::getUrl(),
                'why' => MengapaPrima::getUrl(),
            ],
        ];
    }
}
