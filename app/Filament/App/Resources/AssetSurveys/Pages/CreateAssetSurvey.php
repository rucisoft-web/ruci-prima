<?php

namespace App\Filament\App\Resources\AssetSurveys\Pages;

use App\Filament\App\Resources\AssetSurveys\AssetSurveyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetSurvey extends CreateRecord
{
    protected static string $resource = AssetSurveyResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
