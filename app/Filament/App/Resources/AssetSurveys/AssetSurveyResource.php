<?php

namespace App\Filament\App\Resources\AssetSurveys;

use App\Enums\SurveyState;
use App\Filament\App\Resources\AssetSurveys\Pages\CreateAssetSurvey;
use App\Filament\App\Resources\AssetSurveys\Pages\EditAssetSurvey;
use App\Filament\App\Resources\AssetSurveys\Pages\ListAssetSurveys;
use App\Filament\App\Resources\AssetSurveys\Pages\ViewAssetSurvey;
use App\Filament\App\Resources\AssetSurveys\RelationManagers\AssignmentsRelationManager;
use App\Filament\Concerns\OperationalPermissions;
use App\Models\AssetSurvey;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AssetSurveyResource extends Resource
{
    use OperationalPermissions;

    protected static ?string $model = AssetSurvey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Identifikasi Awal';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Survei aset awal';

    protected static ?string $pluralModelLabel = 'Survei Aset Awal';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->columnSpanFull()->schema([
                TextInput::make('name')->label('Nama survei')->default('Survei Aset Awal')->required()->columnSpan(2),
                Select::make('manager_id')->label('Koordinator')->options(fn () => static::memberOptions())->default(fn () => auth()->id()),
                Select::make('state')->label('Status')->options(SurveyState::class)->default(SurveyState::Draft)->disabledOn('create'),
                DatePicker::make('date_start')->label('Mulai')->default(now()),
                DatePicker::make('date_end')->label('Selesai'),
                Textarea::make('note')->label('Catatan')->columnSpanFull(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        $stat = fn (string $key) => fn (AssetSurvey $record) => $record->stats()[$key];

        return $schema->components([
            Section::make()->columns(4)->columnSpanFull()->schema([
                TextEntry::make('name')->label('Survei')->weight('bold')->columnSpan(2),
                TextEntry::make('state')->label('Status')->badge(),
                TextEntry::make('manager.name')->label('Koordinator')->placeholder('—'),
                TextEntry::make('date_start')->label('Mulai')->date('d/m/Y')->placeholder('—'),
                TextEntry::make('date_end')->label('Selesai')->date('d/m/Y')->placeholder('—'),
                TextEntry::make('assignments_progress')->label('Penugasan selesai')
                    ->state(fn (AssetSurvey $record) => ($s = $record->stats())['completed_assignments'].' / '.$s['total_assignments'].' ('.$s['progress'].'%)'),
                TextEntry::make('expected')->label('Aset dari templat')->state($stat('total_expected')),
                TextEntry::make('verified')->label('Terverifikasi')->state($stat('verified'))->color('success'),
                TextEntry::make('missing')->label('Tidak ditemukan')->state($stat('missing'))->color('danger'),
                TextEntry::make('additional')->label('Aset tambahan')->state($stat('additional'))->color('warning'),
                TextEntry::make('note')->label('Catatan')->columnSpanFull()->placeholder('—'),
            ]),
        ]);
    }

    public static function stateActions(): array
    {
        return [
            Action::make('start')->label('Mulai survei')->icon(Heroicon::OutlinedPlay)->color('primary')
                ->visible(fn (AssetSurvey $record) => static::canOperate() && $record->state === SurveyState::Draft)
                ->action(fn (AssetSurvey $record) => $record->start()),
            Action::make('complete')->label('Selesaikan survei')->icon(Heroicon::OutlinedCheck)->color('success')
                ->visible(fn (AssetSurvey $record) => static::canOperate() && $record->state === SurveyState::InProgress)
                ->requiresConfirmation()
                ->action(fn (AssetSurvey $record) => $record->complete()),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')->label('Survei')->searchable(),
                TextColumn::make('date_start')->label('Mulai')->date('d/m/Y'),
                TextColumn::make('manager.name')->label('Koordinator')->placeholder('—'),
                TextColumn::make('assignments_count')->label('Penugasan')->counts('assignments'),
                TextColumn::make('assets_count')->label('Aset')->counts('assets'),
                TextColumn::make('progress')->label('Kemajuan')->state(fn (AssetSurvey $record) => $record->stats()['progress'].'%'),
                TextColumn::make('state')->label('Status')->badge(),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [AssignmentsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssetSurveys::route('/'),
            'create' => CreateAssetSurvey::route('/create'),
            'view' => ViewAssetSurvey::route('/{record}'),
            'edit' => EditAssetSurvey::route('/{record}/edit'),
        ];
    }
}
