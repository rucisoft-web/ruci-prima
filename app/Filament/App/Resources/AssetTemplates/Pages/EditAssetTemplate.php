<?php

namespace App\Filament\App\Resources\AssetTemplates\Pages;

use App\Filament\App\Resources\AssetTemplates\AssetTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetTemplate extends EditRecord
{
    protected static string $resource = AssetTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
