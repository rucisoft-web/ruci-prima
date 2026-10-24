<?php

namespace App\Filament\App\Resources\Inspections\Pages;

use App\Filament\App\Resources\Inspections\InspectionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInspection extends ViewRecord
{
    protected static string $resource = InspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
