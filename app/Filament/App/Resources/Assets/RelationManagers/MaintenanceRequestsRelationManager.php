<?php

namespace App\Filament\App\Resources\Assets\RelationManagers;

use App\Filament\App\Resources\MaintenanceRequests\MaintenanceRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MaintenanceRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenanceRequests';

    protected static ?string $title = 'Permintaan pemeliharaan';

    public function isReadOnly(): bool
    {
        return auth()->user()?->roleIn($this->getOwnerRecord()->company_id)?->canOperate() !== true;
    }

    public function form(Schema $schema): Schema
    {
        return MaintenanceRequestResource::form($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        return MaintenanceRequestResource::infolist($schema);
    }

    public function table(Table $table): Table
    {
        return MaintenanceRequestResource::table($table)->headerActions([CreateAction::make()]);
    }
}
