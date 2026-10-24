<?php

namespace App\Filament\App\Resources\Assets\RelationManagers;

use App\Filament\App\Resources\PreventivePlans\PreventivePlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PreventivePlansRelationManager extends RelationManager
{
    protected static string $relationship = 'preventivePlans';

    protected static ?string $title = 'Pemeliharaan preventif';

    public function isReadOnly(): bool
    {
        return auth()->user()?->roleIn($this->getOwnerRecord()->company_id)?->canOperate() !== true;
    }

    public function form(Schema $schema): Schema
    {
        return PreventivePlanResource::form($schema);
    }


    public function table(Table $table): Table
    {
        return PreventivePlanResource::table($table)->headerActions([CreateAction::make()]);
    }
}
