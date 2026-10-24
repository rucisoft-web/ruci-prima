<?php

namespace App\Filament\App\Resources\AssetSurveys\Pages;

use App\Filament\App\Resources\AssetSurveys\AssetSurveyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetSurvey extends EditRecord
{
    protected static string $resource = AssetSurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
