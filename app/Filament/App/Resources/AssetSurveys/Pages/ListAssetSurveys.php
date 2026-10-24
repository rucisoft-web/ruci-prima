<?php

namespace App\Filament\App\Resources\AssetSurveys\Pages;

use App\Filament\App\Resources\AssetSurveys\AssetSurveyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetSurveys extends ListRecords
{
    protected static string $resource = AssetSurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
