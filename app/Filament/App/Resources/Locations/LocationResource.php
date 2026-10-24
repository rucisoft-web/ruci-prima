<?php

namespace App\Filament\App\Resources\Locations;

use App\Filament\App\Resources\Locations\Pages\ManageLocations;
use App\Filament\Concerns\ManagerPermissions;
use App\Models\Location;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class LocationResource extends Resource
{
    use ManagerPermissions;

    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Konfigurasi';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Lokasi';

    protected static ?string $pluralModelLabel = 'Lokasi';

    protected static ?string $recordTitleAttribute = 'complete_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama lokasi')->required()->maxLength(255),
            Select::make('parent_id')->label('Lokasi induk')
                ->options(fn (?Location $record) => Location::query()
                    ->when($record, fn ($q) => $q->whereNotIn('id', [$record->id, ...$record->descendantIds()]))
                    ->orderBy('complete_name')->pluck('complete_name', 'id'))
                ->searchable(),
            Toggle::make('is_active')->label('Aktif')->default(true),
            Textarea::make('note')->label('Catatan')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('complete_name')
            ->columns([
                TextColumn::make('complete_name')->label('Lokasi')->searchable()->sortable(),
                TextColumn::make('assets_count')->label('Jumlah aset')->counts('assets')->sortable(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('parent_id')->label('Induk')
                    ->options(fn () => Location::query()->has('children')->orderBy('complete_name')->pluck('complete_name', 'id')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (Location $record) => $record->assets()->exists() || $record->children()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageLocations::route('/')];
    }
}
