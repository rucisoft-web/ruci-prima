<?php

namespace App\Filament\App\Resources\Inspections;

use App\Enums\InspectionResult;
use App\Filament\App\Resources\Inspections\Pages\CreateInspection;
use App\Filament\App\Resources\Inspections\Pages\EditInspection;
use App\Filament\App\Resources\Inspections\Pages\ListInspections;
use App\Filament\App\Resources\Inspections\Pages\ViewInspection;
use App\Filament\Concerns\OperationalPermissions;
use App\Filament\Forms\AssetSelect;
use App\Models\Inspection;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class InspectionResource extends Resource
{
    use OperationalPermissions;

    protected static ?string $model = Inspection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Inspeksi & Pemeliharaan';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Inspeksi';

    protected static ?string $pluralModelLabel = 'Inspeksi';

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->columnSpanFull()->schema([
                AssetSelect::make()->columnSpan(2),
                DateTimePicker::make('inspected_at')->label('Waktu inspeksi')->default(now())->required()->seconds(false),
                Select::make('inspector_id')->label('Inspektor')->options(fn () => static::memberOptions())->default(fn () => auth()->id())->required(),
                Select::make('result')->label('Hasil')->options(InspectionResult::class)->default(InspectionResult::Pass)->required(),
                TextInput::make('condition_score')->label('Skor kondisi (0–100)')->numeric()->integer()->minValue(0)->maxValue(100)->default(80)->required(),
                TextInput::make('meter_reading')->label('Pembacaan meter')->numeric(),
                TextInput::make('meter_unit')->label('Satuan meter')->placeholder('jam, kWh, km'),
                Toggle::make('update_asset')->label('Perbarui kondisi fisik aset dengan skor ini')->default(true)->dehydrated(false)->inline(false)
                    ->visible(fn (string $operation, $livewire) => $operation === 'create' && ! $livewire instanceof \Filament\Resources\RelationManagers\RelationManager),
                Textarea::make('finding')->label('Temuan')->columnSpanFull(),
                Textarea::make('recommendation')->label('Rekomendasi')->columnSpanFull(),
                FileUpload::make('photo')->label('Foto')->image()->disk('local')->directory('inspeksi')->visibility('private')
                    ->maxSize(config('asset.uploads.max_kb'))->columnSpanFull(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->columnSpanFull()->schema([
                TextEntry::make('number')->label('Nomor')->weight('bold'),
                TextEntry::make('asset.name')->label('Aset')->columnSpan(2)
                    ->url(fn (Inspection $record) => \App\Filament\App\Resources\Assets\AssetResource::getUrl('view', ['record' => $record->asset_id])),
                TextEntry::make('inspected_at')->label('Waktu')->dateTime('d/m/Y H:i'),
                TextEntry::make('inspector.name')->label('Inspektor'),
                TextEntry::make('result')->label('Hasil')->badge(),
                TextEntry::make('condition_score')->label('Skor kondisi'),
                TextEntry::make('meter_reading')->label('Meter')->suffix(fn (Inspection $record) => ' '.$record->meter_unit)->placeholder('—'),
                TextEntry::make('finding')->label('Temuan')->columnSpan(2)->placeholder('—'),
                TextEntry::make('recommendation')->label('Rekomendasi')->columnSpan(2)->placeholder('—'),
                ImageEntry::make('photo')->label('Foto')->disk('local')->visibility('private')->columnSpanFull()->imageHeight(220),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('inspected_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('Nomor')->searchable(),
                TextColumn::make('asset.name')->label('Aset')->searchable()->wrap()
                    ->hidden(fn ($livewire) => $livewire instanceof \Filament\Resources\RelationManagers\RelationManager)
                    ->description(fn (Inspection $record) => $record->asset?->code),
                TextColumn::make('inspected_at')->label('Waktu')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('inspector.name')->label('Inspektor'),
                TextColumn::make('result')->label('Hasil')->badge(),
                TextColumn::make('condition_score')->label('Skor')->sortable(),
            ])
            ->filters([
                SelectFilter::make('result')->label('Hasil')->options(InspectionResult::class),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInspections::route('/'),
            'create' => CreateInspection::route('/create'),
            'view' => ViewInspection::route('/{record}'),
            'edit' => EditInspection::route('/{record}/edit'),
        ];
    }
}
