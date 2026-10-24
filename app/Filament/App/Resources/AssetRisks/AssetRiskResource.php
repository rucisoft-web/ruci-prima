<?php

namespace App\Filament\App\Resources\AssetRisks;

use App\Enums\RiskLevel;
use App\Enums\RiskState;
use App\Filament\App\Resources\AssetRisks\Pages\ManageAssetRisks;
use App\Filament\Concerns\OperationalPermissions;
use App\Filament\Forms\AssetSelect;
use App\Models\AssetRisk;
use App\Support\Scoring;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class AssetRiskResource extends Resource
{
    use OperationalPermissions;

    protected static ?string $model = AssetRisk::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static string|UnitEnum|null $navigationGroup = 'Risiko & Investasi';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Risiko aset';

    protected static ?string $pluralModelLabel = 'Register Risiko';

    public static function form(Schema $schema): Schema
    {
        $scale = [1 => '1 · Sangat rendah', 2 => '2 · Rendah', 3 => '3 · Sedang', 4 => '4 · Tinggi', 5 => '5 · Sangat tinggi'];

        return $schema->components([
            TextInput::make('name')->label('Uraian risiko')->required()->maxLength(255)->columnSpanFull(),
            AssetSelect::make()->columnSpanFull(),
            Select::make('probability')->label('Probabilitas')->options($scale)->default(2)->required()->live(),
            Select::make('consequence')->label('Konsekuensi')->options($scale)->default(2)->required()->live(),
            TextEntry::make('score_preview')->label('Skor & tingkat')->badge()
                ->state(function (Get $get) {
                    [$score, $level] = Scoring::riskMatrix((int) $get('probability'), (int) $get('consequence'));

                    return "{$score} · {$level->getLabel()}";
                }),
            Select::make('state')->label('Status')->options(RiskState::class)->default(RiskState::Open)->required(),
            Select::make('owner_id')->label('Pemilik risiko')->options(fn () => static::memberOptions())->searchable(),
            DatePicker::make('target_date')->label('Target mitigasi'),
            Textarea::make('mitigation')->label('Rencana mitigasi')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('risk_score', 'desc')
            ->columns([
                TextColumn::make('name')->label('Risiko')->searchable()->wrap(),
                TextColumn::make('asset.name')->label('Aset')->searchable()->wrap()
                    ->hidden(fn ($livewire) => $livewire instanceof \Filament\Resources\RelationManagers\RelationManager),
                TextColumn::make('probability')->label('P')->sortable(),
                TextColumn::make('consequence')->label('K')->sortable(),
                TextColumn::make('risk_score')->label('Skor')->sortable()->weight('bold'),
                TextColumn::make('level')->label('Tingkat')->badge(),
                TextColumn::make('owner.name')->label('Pemilik')->placeholder('—')->toggleable(),
                TextColumn::make('target_date')->label('Target')->date('d/m/Y')->toggleable(),
                TextColumn::make('state')->label('Status')->badge(),
            ])
            ->filters([
                SelectFilter::make('state')->label('Status')->options(RiskState::class)->multiple(),
                SelectFilter::make('level')->label('Tingkat')->options(RiskLevel::class)->multiple(),
                SelectFilter::make('probability')->label('Probabilitas')->options(array_combine(range(1, 5), range(1, 5))),
                SelectFilter::make('consequence')->label('Konsekuensi')->options(array_combine(range(1, 5), range(1, 5))),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAssetRisks::route('/')];
    }
}
