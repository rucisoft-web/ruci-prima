<?php

namespace App\Filament\App\Resources\Assets\Pages;

use App\Enums\Condition;
use App\Enums\Criticality;
use App\Enums\Priority;
use App\Filament\App\Resources\Assets\AssetResource;
use App\Filament\App\Resources\Assets\IntakeActions;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make(IntakeActions::all())->label('Identifikasi awal')->icon(Heroicon::OutlinedSparkles)->button()->color('gray')
                ->visible(fn () => auth()->user()->roleIn(\Filament\Facades\Filament::getTenant())?->canOperate()),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua aset'),
            'kritis' => Tab::make('Aset kritis')->icon(Heroicon::OutlinedFire)
                ->modifyQueryUsing(fn (Builder $query) => $query->needsAttention()),
            'perhatian' => Tab::make('Perlu perhatian kesehatan')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('condition', [Condition::Fair->value, Condition::Poor->value, Condition::Critical->value])),
            'mendesak' => Tab::make('Prioritas mendesak')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('priority', Priority::Urgent->value)),
        ];
    }
}
