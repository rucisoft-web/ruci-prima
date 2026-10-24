<?php

namespace App\Filament\App\Resources\Assets\RelationManagers;

use App\Filament\App\Resources\CapexProposals\CapexProposalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CapexProposalsRelationManager extends RelationManager
{
    protected static string $relationship = 'capexProposals';

    protected static ?string $title = 'CAPEX';

    public function isReadOnly(): bool
    {
        return auth()->user()?->roleIn($this->getOwnerRecord()->company_id)?->canManage() !== true;
    }

    public function form(Schema $schema): Schema
    {
        return CapexProposalResource::form($schema);
    }


    public function table(Table $table): Table
    {
        return CapexProposalResource::table($table)->headerActions([CreateAction::make()]);
    }
}
