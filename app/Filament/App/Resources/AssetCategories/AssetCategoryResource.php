<?php

namespace App\Filament\App\Resources\AssetCategories;

use App\Enums\Criticality;
use App\Filament\App\Resources\AssetCategories\Pages\ManageAssetCategories;
use App\Filament\Concerns\ManagerPermissions;
use App\Models\AssetCategory;
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
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class AssetCategoryResource extends Resource
{
    use ManagerPermissions;

    protected static ?string $model = AssetCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Konfigurasi';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Kategori aset';

    protected static ?string $pluralModelLabel = 'Kategori Aset';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(255),
            TextInput::make('code')->label('Kode')->required()->maxLength(20)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->where('company_id', static::tenantCompany()?->id)),
            TextInput::make('expected_life_years')->label('Umur manfaat (tahun)')->numeric()->minValue(0)->default(5)->required(),
            Select::make('default_criticality')->label('Kekritisan bawaan')->options(Criticality::class)->default(Criticality::Medium)->required(),
            Toggle::make('is_active')->label('Aktif')->default(true),
            Textarea::make('note')->label('Catatan')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable(),
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('expected_life_years')->label('Umur manfaat')->suffix(' th'),
                TextColumn::make('default_criticality')->label('Kekritisan bawaan')->badge(),
                TextColumn::make('assets_count')->label('Jumlah aset')->counts('assets')->sortable(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (AssetCategory $record) => $record->assets()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAssetCategories::route('/')];
    }
}
