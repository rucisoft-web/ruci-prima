<?php

namespace App\Filament\App\Resources\AssetSurveys\Pages;

use App\Filament\App\Resources\AssetSurveys\AssetSurveyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAssetSurvey extends ViewRecord
{
    protected static string $resource = AssetSurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [...AssetSurveyResource::stateActions(), EditAction::make()];
    }
}
