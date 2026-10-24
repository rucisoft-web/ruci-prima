<?php

namespace App\Filament\App\Pages;

use App\Models\Asset;
use App\Models\CapexProposal;
use App\Models\LegalDocument;
use App\Models\MeetingRoom;
use App\Models\SupplyItem;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Penjelasan manfaat Ruci PRIMA untuk direksi, komisaris, dan pimpinan, memakai angka perusahaan yang sedang dibuka. */
class MengapaPrima extends Page
{
    protected static ?string $slug = 'mengapa-ruci-prima';

    protected static ?int $navigationSort = -19;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static string|UnitEnum|null $navigationGroup = 'Pimpinan';

    protected static ?string $navigationLabel = 'Mengapa Ruci PRIMA';

    protected static ?string $title = 'Mengapa Ruci PRIMA';

    protected string $view = 'filament.pages.mengapa-prima';

    public function getHeading(): string
    {
        return '';
    }

    public function getViewData(): array
    {
        $company = filament()->getTenant();
        $from = today()->subMonths(12);
        $done = WorkOrder::query()->whereIn('state', WorkOrder::COMPLETED_STATES)->where('completed_at', '>=', $from);
        $industry = strtolower((string) $company?->industry);

        return [
            'company' => $company,
            'facts' => [
                'assets' => Asset::query()->where('is_active', true)->count(),
                'asset_value' => (float) Asset::query()->where('is_active', true)->sum('acquisition_cost'),
                'documents' => LegalDocument::query()->where('is_active', true)->count(),
                'vehicles' => Vehicle::query()->count(),
                'rooms' => MeetingRoom::query()->count(),
                'supplies' => SupplyItem::query()->count(),
                'capex' => (float) CapexProposal::query()->whereIn('state', ['draft', 'proposed'])->sum('estimated_cost'),
                'reactive_cost' => (float) (clone $done)->where('work_type', '!=', 'preventive')->sum('total_cost'),
            ],
            'industry' => match (true) {
                str_contains($industry, 'hotel') || str_contains($industry, 'perhotelan') => 'hotel',
                str_contains($industry, 'transport') || str_contains($industry, 'mobilitas') => 'transport',
                str_contains($industry, 'makanan') || str_contains($industry, 'manufaktur') || str_contains($industry, 'rantai dingin') => 'food',
                default => 'general',
            },
            'dashboard' => DasborPimpinan::getUrl(),
        ];
    }
}
