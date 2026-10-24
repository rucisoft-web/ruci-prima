<?php

namespace App\Filament\App\Resources\CapexProposals;

use App\Enums\CapexCategory;
use App\Enums\CapexRecommendation;
use App\Enums\CapexState;
use App\Filament\App\Resources\CapexProposals\Pages\ManageCapexProposals;
use App\Filament\Concerns\InteractsWithTenant;
use App\Filament\Forms\AssetSelect;
use App\Models\CapexProposal;
use App\Support\Money;
use App\Support\Scoring;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Usulan CAPEX: staf hanya membaca, manajer mengelola (sama dengan hak akses modul Odoo). */
class CapexProposalResource extends Resource
{
    use InteractsWithTenant;

    protected static ?string $model = CapexProposal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Risiko & Investasi';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Usulan CAPEX';

    protected static ?string $pluralModelLabel = 'Usulan CAPEX';

    public static function canCreate(): bool
    {
        return static::canManage();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canManage();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canManage();
    }

    public static function form(Schema $schema): Schema
    {
        $scale = [1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'];

        return $schema->components([
            TextInput::make('name')->label('Judul usulan')->required()->maxLength(255)->columnSpanFull(),
            AssetSelect::make(required: false)->columnSpanFull(),
            Select::make('category')->label('Kategori')->options(CapexCategory::class)->default(CapexCategory::Mandatory)->required(),
            Select::make('recommendation')->label('Rekomendasi')->options(CapexRecommendation::class)->default(CapexRecommendation::Replace),
            TextInput::make('target_year')->label('Tahun target')->numeric()->default(now()->year)->required(),
            TextInput::make('estimated_cost')->label('Estimasi biaya')->numeric()->prefix('Rp')->default(0)->required(),
            Select::make('guest_impact')->label('Dampak pelanggan/tamu (20%)')->options($scale)->default(3)->live(),
            Select::make('safety_impact')->label('Dampak keselamatan (30%)')->options($scale)->default(3)->live(),
            Select::make('revenue_impact')->label('Dampak pendapatan (20%)')->options($scale)->default(3)->live(),
            Select::make('urgency')->label('Urgensi (30%)')->options($scale)->default(3)->live(),
            TextEntry::make('priority_preview')->label('Skor prioritas')->badge()
                ->state(fn (Get $get) => Scoring::capexPriority((int) $get('guest_impact'), (int) $get('safety_impact'), (int) $get('revenue_impact'), (int) $get('urgency')).' / 100'),
            Select::make('state')->label('Status')->options(CapexState::class)->default(CapexState::Draft)->required(),
            Textarea::make('justification')->label('Justifikasi')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('priority_score', 'desc')
            ->columns([
                TextColumn::make('name')->label('Usulan')->searchable()->wrap()
                    ->description(fn (CapexProposal $record) => $record->asset?->name),
                TextColumn::make('category')->label('Kategori')->badge(),
                TextColumn::make('recommendation')->label('Rekomendasi')->badge()->toggleable(),
                TextColumn::make('target_year')->label('Tahun')->sortable(),
                TextColumn::make('estimated_cost')->label('Estimasi biaya')->sortable()->formatStateUsing(fn ($state) => Money::format($state))
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => Money::short($state))),
                TextColumn::make('priority_score')->label('Prioritas')->sortable()->weight('bold'),
                TextColumn::make('state')->label('Status')->badge(),
            ])
            ->filters([
                SelectFilter::make('state')->label('Status')->options(CapexState::class)->multiple(),
                SelectFilter::make('category')->label('Kategori')->options(CapexCategory::class),
                SelectFilter::make('target_year')->label('Tahun')->options(fn () => CapexProposal::query()->distinct()->orderBy('target_year')->pluck('target_year', 'target_year')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCapexProposals::route('/')];
    }
}
