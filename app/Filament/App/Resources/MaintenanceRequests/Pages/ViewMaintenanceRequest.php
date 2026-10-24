<?php

namespace App\Filament\App\Resources\MaintenanceRequests\Pages;

use App\Filament\App\Resources\MaintenanceRequests\MaintenanceRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMaintenanceRequest extends ViewRecord
{
    protected static string $resource = MaintenanceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [...MaintenanceRequestResource::workflowActions(), EditAction::make()];
    }
}
