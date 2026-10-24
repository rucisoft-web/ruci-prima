<?php

namespace App\Filament\App\Resources\AssetRisks\Pages;

use App\Filament\App\Resources\AssetRisks\AssetRiskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAssetRisks extends ManageRecords
{
    protected static string $resource = AssetRiskResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
