<?php

namespace App\Filament\App\Resources\AssetTemplates\Pages;

use App\Filament\App\Resources\AssetTemplates\AssetTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetTemplate extends CreateRecord
{
    protected static string $resource = AssetTemplateResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
