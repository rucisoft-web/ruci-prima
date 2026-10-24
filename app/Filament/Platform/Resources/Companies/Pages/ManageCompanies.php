<?php

namespace App\Filament\Platform\Resources\Companies\Pages;

use App\Filament\Platform\Resources\Companies\CompanyResource;
use App\Support\StarterData;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCompanies extends ManageRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->after(fn ($record) => StarterData::seed($record)),
        ];
    }
}
