<?php

namespace App\Filament\App\Resources\Inspections\Pages;

use App\Filament\App\Resources\Inspections\InspectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInspection extends CreateRecord
{
    protected static string $resource = InspectionResource::class;

    protected function afterCreate(): void
    {
        if ($this->data['update_asset'] ?? false) {
            $this->record->asset->update(['physical_condition' => $this->record->condition_score]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
