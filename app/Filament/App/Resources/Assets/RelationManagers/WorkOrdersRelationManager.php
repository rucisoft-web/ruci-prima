<?php

namespace App\Filament\App\Resources\Assets\RelationManagers;

use App\Filament\App\Resources\WorkOrders\WorkOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class WorkOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'workOrders';

    protected static ?string $title = 'Work order';

    public function isReadOnly(): bool
    {
        return auth()->user()?->roleIn($this->getOwnerRecord()->company_id)?->canOperate() !== true;
    }

    public function form(Schema $schema): Schema
    {
        return WorkOrderResource::form($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        return WorkOrderResource::infolist($schema);
    }

    public function table(Table $table): Table
    {
        return WorkOrderResource::table($table)->headerActions([CreateAction::make()]);
    }
}
