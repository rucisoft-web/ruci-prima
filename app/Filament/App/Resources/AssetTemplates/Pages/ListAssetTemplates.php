<?php

namespace App\Filament\App\Resources\AssetTemplates\Pages;

use App\Filament\App\Resources\AssetTemplates\AssetTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetTemplates extends ListRecords
{
    protected static string $resource = AssetTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
