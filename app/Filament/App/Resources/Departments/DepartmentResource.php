<?php

namespace App\Filament\App\Resources\Departments;

use App\Filament\App\Resources\Departments\Pages\ManageDepartments;
use App\Filament\Concerns\ManagerPermissions;
use App\Models\Department;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Departemen pemilik aset. Pada modul Odoo ini daftar tetap khusus hotel; di sini diatur per perusahaan. */
class DepartmentResource extends Resource
{
    use ManagerPermissions;

    protected static ?string $model = Department::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Konfigurasi';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'Departemen';

    protected static ?string $pluralModelLabel = 'Departemen';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(255),
            TextInput::make('code')->label('Kode')->maxLength(20),
            TextInput::make('sort')->label('Urutan')->numeric()->default(10),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('sort')->label('#')->sortable(),
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('code')->label('Kode'),
                TextColumn::make('assets_count')->label('Jumlah aset')->counts('assets'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (Department $record) => $record->assets()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageDepartments::route('/')];
    }
}
