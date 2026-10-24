<?php

namespace App\Filament\App\Resources\CapexProposals\Pages;

use App\Filament\App\Resources\CapexProposals\CapexProposalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCapexProposals extends ManageRecords
{
    protected static string $resource = CapexProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
