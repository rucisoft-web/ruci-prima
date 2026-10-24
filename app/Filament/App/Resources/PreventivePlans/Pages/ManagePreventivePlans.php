<?php

namespace App\Filament\App\Resources\PreventivePlans\Pages;

use App\Filament\App\Resources\PreventivePlans\PreventivePlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePreventivePlans extends ManageRecords
{
    protected static string $resource = PreventivePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
