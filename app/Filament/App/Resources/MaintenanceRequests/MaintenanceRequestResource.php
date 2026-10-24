<?php

namespace App\Filament\App\Resources\MaintenanceRequests;

use App\Enums\Priority;
use App\Enums\RequestState;
use App\Enums\RequestType;
use App\Filament\App\Resources\Assets\AssetResource;
use App\Filament\App\Resources\MaintenanceRequests\Pages\CreateMaintenanceRequest;
use App\Filament\App\Resources\MaintenanceRequests\Pages\EditMaintenanceRequest;
use App\Filament\App\Resources\MaintenanceRequests\Pages\ListMaintenanceRequests;
use App\Filament\App\Resources\MaintenanceRequests\Pages\ViewMaintenanceRequest;
use App\Filament\App\Resources\WorkOrders\WorkOrderResource;
use App\Filament\Concerns\OperationalPermissions;
use App\Filament\Forms\AssetSelect;
use App\Models\MaintenanceRequest;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MaintenanceRequestResource extends Resource
{
    use OperationalPermissions;

    protected static ?string $model = MaintenanceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Inspeksi & Pemeliharaan';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Permintaan pemeliharaan';

    protected static ?string $pluralModelLabel = 'Permintaan Pemeliharaan';

    protected static ?string $recordTitleAttribute = 'number';

    public static function getNavigationBadge(): ?string
    {
        $count = MaintenanceRequest::query()->where('state', RequestState::New->value)->count();

        return $count ? (string) $count : null;
    }

    public static function canEdit(Model $record): bool
    {
        return static::canOperate() && in_array($record->state, [RequestState::New, RequestState::Verified], true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->columnSpanFull()->schema([
                AssetSelect::make()->columnSpan(3),
                Select::make('request_type')->label('Jenis')->options(RequestType::class)->default(RequestType::Corrective)->required(),
                Select::make('priority')->label('Prioritas')->options(Priority::class)->default(Priority::Medium)->required(),
                Textarea::make('description')->label('Deskripsi masalah')->required()->rows(4)->columnSpanFull(),
                FileUpload::make('photo_before')->label('Foto sebelum')->image()->disk('local')->directory('permintaan')->visibility('private')
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
                    ->url(fn (MaintenanceRequest $record) => AssetResource::getUrl('view', ['record' => $record->asset_id])),
                TextEntry::make('asset.location.complete_name')->label('Lokasi'),
                TextEntry::make('requested_at')->label('Waktu lapor')->dateTime('d/m/Y H:i'),
                TextEntry::make('reporter')->label('Pelapor')->state(fn (MaintenanceRequest $record) => $record->reporterLabel()),
                TextEntry::make('reporter_contact')->label('Kontak pelapor')->placeholder('—'),
                TextEntry::make('state')->label('Status')->badge(),
                TextEntry::make('request_type')->label('Jenis')->badge(),
                TextEntry::make('priority')->label('Prioritas')->badge(),
                TextEntry::make('workOrder.number')->label('Work order')->placeholder('—')
                    ->url(fn (MaintenanceRequest $record) => $record->work_order_id ? WorkOrderResource::getUrl('view', ['record' => $record->work_order_id]) : null),
                TextEntry::make('description')->label('Deskripsi')->columnSpanFull(),
                ImageEntry::make('photo_before')->label('Foto sebelum')->disk('local')->visibility('private')->columnSpanFull()->imageHeight(220),
            ]),
        ]);
    }

    /** Aksi alur kerja: verifikasi → buat work order, atau batalkan. */
    public static function workflowActions(): array
    {
        return [
            Action::make('verify')->label('Verifikasi')->icon(Heroicon::OutlinedCheck)->color('info')
                ->visible(fn (MaintenanceRequest $record) => static::canOperate() && $record->state === RequestState::New)
                ->action(fn (MaintenanceRequest $record) => $record->verify()),
            Action::make('create_work_order')->label('Buat work order')->icon(Heroicon::OutlinedWrenchScrewdriver)->color('success')
                ->visible(fn (MaintenanceRequest $record) => static::canOperate() && in_array($record->state, [RequestState::New, RequestState::Verified], true))
                ->requiresConfirmation()
                ->action(function (MaintenanceRequest $record, $livewire) {
                    $workOrder = $record->createWorkOrder();
                    Notification::make()->title("Work order {$workOrder->number} dibuat.")->success()->send();
                    $livewire->redirect(WorkOrderResource::getUrl('view', ['record' => $workOrder]));
                }),
            Action::make('cancel')->label('Batalkan')->icon(Heroicon::OutlinedXMark)->color('gray')
                ->visible(fn (MaintenanceRequest $record) => static::canOperate() && in_array($record->state, [RequestState::New, RequestState::Verified], true))
                ->requiresConfirmation()
                ->action(fn (MaintenanceRequest $record) => $record->cancel()),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('requested_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('Nomor')->searchable(),
                TextColumn::make('asset.name')->label('Aset')->searchable()->wrap()
                    ->hidden(fn ($livewire) => $livewire instanceof \Filament\Resources\RelationManagers\RelationManager)
                    ->description(fn (MaintenanceRequest $record) => $record->asset?->location?->complete_name),
                TextColumn::make('requested_at')->label('Waktu')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('request_type')->label('Jenis')->badge(),
                TextColumn::make('priority')->label('Prioritas')->badge(),
                TextColumn::make('description')->label('Deskripsi')->limit(60)->wrap()->toggleable(),
                IconColumn::make('via_qr')->label('QR')->boolean()->toggleable(),
                TextColumn::make('state')->label('Status')->badge(),
            ])
            ->filters([
                SelectFilter::make('state')->label('Status')->options(RequestState::class)->multiple(),
                SelectFilter::make('priority')->label('Prioritas')->options(Priority::class)->multiple(),
                SelectFilter::make('request_type')->label('Jenis')->options(RequestType::class),
            ])
            ->recordActions([ViewAction::make(), ...static::workflowActions()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenanceRequests::route('/'),
            'create' => CreateMaintenanceRequest::route('/create'),
            'view' => ViewMaintenanceRequest::route('/{record}'),
            'edit' => EditMaintenanceRequest::route('/{record}/edit'),
        ];
    }
}
