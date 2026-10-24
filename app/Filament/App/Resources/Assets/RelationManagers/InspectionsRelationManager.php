<?php

namespace App\Filament\App\Resources\Assets\RelationManagers;

use App\Filament\App\Resources\Inspections\InspectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class InspectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'inspections';

    protected static ?string $title = 'Inspeksi';

    public function isReadOnly(): bool
    {
        return auth()->user()?->roleIn($this->getOwnerRecord()->company_id)?->canOperate() !== true;
    }

    public function form(Schema $schema): Schema
    {
        return InspectionResource::form($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        return InspectionResource::infolist($schema);
    }

    public function table(Table $table): Table
    {
        return InspectionResource::table($table)->headerActions([CreateAction::make()]);
    }
}
