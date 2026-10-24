<?php

namespace App\Filament\App\Resources\Inspections\Pages;

use App\Filament\App\Resources\Inspections\InspectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInspections extends ListRecords
{
    protected static string $resource = InspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
