<?php

namespace App\Filament\Platform\Resources\Companies;

use App\Filament\Platform\Resources\Companies\Pages\ManageCompanies;
use App\Models\Company;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?string $modelLabel = 'Perusahaan';

    protected static ?string $pluralModelLabel = 'Perusahaan / Properti';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(255),
            TextInput::make('code')->label('Kode singkat')->maxLength(20)->helperText('Dipakai untuk alamat, mis. /app/sahid'),
            TextInput::make('slug')->label('Slug alamat')->maxLength(60)->alphaDash()->unique(ignoreRecord: true)->visibleOn('edit'),
            TextInput::make('industry')->label('Industri')->placeholder('Perhotelan, rumah sakit, manufaktur, kampus...'),
            TextInput::make('city')->label('Kota'),
            Textarea::make('address')->label('Alamat')->columnSpanFull(),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('slug')->label('Slug'),
                TextColumn::make('industry')->label('Industri')->placeholder('—'),
                TextColumn::make('memberships_count')->label('Pengguna')->counts('memberships'),
                TextColumn::make('assets_count')->label('Aset')->counts('assets'),
                IconColumn::make('is_demo')->label('Demo')->boolean(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                Action::make('open')->label('Buka')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Company $record) => url('/app/'.$record->slug))->openUrlInNewTab(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCompanies::route('/')];
    }
}
