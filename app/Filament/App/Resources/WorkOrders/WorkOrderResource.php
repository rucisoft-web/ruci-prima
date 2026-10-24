<?php

namespace App\Filament\App\Resources\WorkOrders;

use App\Enums\Priority;
use App\Enums\WorkOrderState;
use App\Enums\WorkType;
use App\Filament\App\Resources\Assets\AssetResource;
use App\Filament\App\Resources\MaintenanceRequests\MaintenanceRequestResource;
use App\Filament\App\Resources\WorkOrders\Pages\CreateWorkOrder;
use App\Filament\App\Resources\WorkOrders\Pages\EditWorkOrder;
use App\Filament\App\Resources\WorkOrders\Pages\ListWorkOrders;
use App\Filament\App\Resources\WorkOrders\Pages\ViewWorkOrder;
use App\Filament\Concerns\OperationalPermissions;
use App\Filament\Forms\AssetSelect;
use App\Models\AssetCategory;
use App\Models\WorkOrder;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class WorkOrderResource extends Resource
{
    use OperationalPermissions;

    protected static ?string $model = WorkOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Inspeksi & Pemeliharaan';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Work order';

    protected static ?string $pluralModelLabel = 'Work Order';

    protected static ?string $recordTitleAttribute = 'number';

    public static function canEdit(Model $record): bool
    {
        return static::canOperate() && ! in_array($record->state, [WorkOrderState::Closed, WorkOrderState::Cancelled], true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pekerjaan')->columns(4)->columnSpanFull()->schema([
                AssetSelect::make()->columnSpan(2),
                Select::make('work_type')->label('Jenis pekerjaan')->options(WorkType::class)->default(WorkType::Corrective)->required(),
                Select::make('priority')->label('Prioritas')->options(Priority::class)->default(Priority::Medium)->required(),
                Select::make('technician_id')->label('Teknisi')->options(fn () => static::memberOptions())->searchable(),
                DateTimePicker::make('planned_at')->label('Rencana')->default(now())->seconds(false),
                DateTimePicker::make('started_at')->label('Mulai')->seconds(false),
                DateTimePicker::make('completed_at')->label('Selesai')->seconds(false),
                Textarea::make('description')->label('Uraian pekerjaan')->columnSpanFull(),
            ]),
            Section::make('Hasil & biaya')->columns(4)->columnSpanFull()->schema([
                Textarea::make('root_cause')->label('Akar masalah')->columnSpan(2),
                Textarea::make('action_taken')->label('Tindakan yang dilakukan')->columnSpan(2),
                TextInput::make('labor_hours')->label('Jam kerja')->numeric()->default(0),
                TextInput::make('downtime_hours')->label('Jam henti (downtime)')->numeric()->default(0),
                TextInput::make('sparepart_cost')->label('Biaya suku cadang')->numeric()->prefix('Rp')->default(0),
                TextInput::make('vendor_cost')->label('Biaya vendor')->numeric()->prefix('Rp')->default(0),
                TextInput::make('other_cost')->label('Biaya lain')->numeric()->prefix('Rp')->default(0),
                FileUpload::make('photo_after')->label('Foto sesudah')->image()->disk('local')->directory('work-order')->visibility('private')
                    ->maxSize(config('asset.uploads.max_kb'))->columnSpan(3),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->columnSpanFull()->schema([
                TextEntry::make('number')->label('Nomor')->weight('bold'),
                TextEntry::make('asset.name')->label('Aset')->columnSpan(2)
                    ->url(fn (WorkOrder $record) => AssetResource::getUrl('view', ['record' => $record->asset_id])),
                TextEntry::make('state')->label('Status')->badge(),
                TextEntry::make('work_type')->label('Jenis')->badge(),
                TextEntry::make('priority')->label('Prioritas')->badge(),
                TextEntry::make('technician.name')->label('Teknisi')->placeholder('—'),
                TextEntry::make('request.number')->label('Dari permintaan')->placeholder('—')
                    ->url(fn (WorkOrder $record) => $record->maintenance_request_id ? MaintenanceRequestResource::getUrl('view', ['record' => $record->maintenance_request_id]) : null),
                TextEntry::make('planned_at')->label('Rencana')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextEntry::make('started_at')->label('Mulai')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextEntry::make('completed_at')->label('Selesai')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextEntry::make('preventivePlan.name')->label('Rencana PM')->placeholder('—'),
                TextEntry::make('description')->label('Uraian')->columnSpanFull()->placeholder('—'),
                TextEntry::make('root_cause')->label('Akar masalah')->columnSpan(2)->placeholder('—'),
                TextEntry::make('action_taken')->label('Tindakan')->columnSpan(2)->placeholder('—'),
                TextEntry::make('labor_hours')->label('Jam kerja'),
                TextEntry::make('downtime_hours')->label('Downtime (jam)'),
                TextEntry::make('costs')->label('Suku cadang / vendor / lain')->columnSpan(1)
                    ->state(fn (WorkOrder $record) => Money::format($record->sparepart_cost).' / '.Money::format($record->vendor_cost).' / '.Money::format($record->other_cost)),
                TextEntry::make('total_cost')->label('Total biaya')->weight('bold')->formatStateUsing(fn ($state) => Money::format($state)),
                ImageEntry::make('photo_after')->label('Foto sesudah')->disk('local')->visibility('private')->columnSpanFull()->imageHeight(220),
            ]),
        ]);
    }

    /** Tombol transisi status work order. */
    public static function workflowActions(): array
    {
        $make = fn (string $action, string $label, $icon, string $color) => Action::make('wo_'.$action)->label($label)->icon($icon)->color($color)
            ->visible(fn (WorkOrder $record) => static::canOperate() && $record->can($action))
            ->action(fn (WorkOrder $record) => $record->transition($action));

        return [
            $make('assign', 'Tugaskan', Heroicon::OutlinedUserPlus, 'info'),
            $make('start', 'Mulai', Heroicon::OutlinedPlay, 'warning'),
            $make('wait', 'Tunggu suku cadang/vendor', Heroicon::OutlinedPause, 'gray'),
            $make('done', 'Selesai', Heroicon::OutlinedCheck, 'success'),
            $make('verify', 'Verifikasi', Heroicon::OutlinedCheckBadge, 'success'),
            $make('close', 'Tutup', Heroicon::OutlinedLockClosed, 'gray'),
            $make('cancel', 'Batalkan', Heroicon::OutlinedXMark, 'danger')->requiresConfirmation(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('planned_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('Nomor')->searchable(),
                TextColumn::make('asset.name')->label('Aset')->searchable()->wrap()
                    ->hidden(fn ($livewire) => $livewire instanceof \Filament\Resources\RelationManagers\RelationManager)
                    ->description(fn (WorkOrder $record) => $record->asset?->location?->complete_name),
                TextColumn::make('work_type')->label('Jenis')->badge(),
                TextColumn::make('priority')->label('Prioritas')->badge(),
                TextColumn::make('technician.name')->label('Teknisi')->placeholder('—')->toggleable(),
                TextColumn::make('planned_at')->label('Rencana')->dateTime('d/m/Y')->sortable(),
                TextColumn::make('completed_at')->label('Selesai')->dateTime('d/m/Y')->sortable()->toggleable(),
                TextColumn::make('total_cost')->label('Biaya')->sortable()->formatStateUsing(fn ($state) => Money::format($state))
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => Money::short($state))),
                TextColumn::make('state')->label('Status')->badge(),
            ])
            ->filters([
                SelectFilter::make('state')->label('Status')->options(WorkOrderState::class)->multiple(),
                SelectFilter::make('work_type')->label('Jenis')->options(WorkType::class)->multiple(),
                SelectFilter::make('priority')->label('Prioritas')->options(Priority::class)->multiple(),
                SelectFilter::make('category')->label('Kategori aset')
                    ->options(fn () => AssetCategory::query()->orderBy('name')->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('asset', fn ($q) => $q->where('asset_category_id', $data['value'])) : $query),
                Filter::make('planned')->label('Periode rencana')
                    ->schema([
                        DatePicker::make('from')->label('Rencana dari'),
                        DatePicker::make('until')->label('Rencana sebelum'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->where('planned_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->where('planned_at', '<', $d))),
                Filter::make('completed')->label('Periode selesai')
                    ->schema([
                        DatePicker::make('from')->label('Selesai dari'),
                        DatePicker::make('until')->label('Selesai sebelum'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->where('completed_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->where('completed_at', '<', $d))),
            ])
            ->recordActions([ViewAction::make(), ActionGroup::make(static::workflowActions())]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkOrders::route('/'),
            'create' => CreateWorkOrder::route('/create'),
            'view' => ViewWorkOrder::route('/{record}'),
            'edit' => EditWorkOrder::route('/{record}/edit'),
        ];
    }
}
