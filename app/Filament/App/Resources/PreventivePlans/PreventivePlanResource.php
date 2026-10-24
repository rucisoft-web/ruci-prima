<?php

namespace App\Filament\App\Resources\PreventivePlans;

use App\Enums\PmFrequency;
use App\Filament\App\Resources\PreventivePlans\Pages\ManagePreventivePlans;
use App\Filament\App\Resources\WorkOrders\WorkOrderResource;
use App\Filament\Concerns\OperationalPermissions;
use App\Filament\Forms\AssetSelect;
use App\Models\PreventivePlan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class PreventivePlanResource extends Resource
{
    use OperationalPermissions;

    protected static ?string $model = PreventivePlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Inspeksi & Pemeliharaan';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'Rencana pemeliharaan preventif';

    protected static ?string $pluralModelLabel = 'Pemeliharaan Preventif';

    protected static ?string $navigationLabel = 'Pemeliharaan Preventif';

    public static function getNavigationBadge(): ?string
    {
        $count = PreventivePlan::query()->overdue()->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Rencana PM lewat jatuh tempo';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama rencana')->required()->maxLength(255)->columnSpanFull(),
            AssetSelect::make()->columnSpanFull(),
            Select::make('frequency')->label('Frekuensi')->options(PmFrequency::class)->default(PmFrequency::Monthly)->required(),
            DatePicker::make('next_due_date')->label('Jatuh tempo berikutnya')->default(now())->required(),
            Select::make('responsible_id')->label('Penanggung jawab')->options(fn () => static::memberOptions())->searchable(),
            Toggle::make('is_active')->label('Aktif')->default(true)->inline(false),
            Textarea::make('checklist')->label('Daftar periksa')->rows(5)->columnSpanFull(),
        ]);
    }

    public static function issueAction(): Action
    {
        return Action::make('issue_wo')->label('Terbitkan WO')->icon(Heroicon::OutlinedWrenchScrewdriver)->color('success')
            ->visible(fn (PreventivePlan $record) => static::canOperate() && $record->is_active)
            ->requiresConfirmation()
            ->modalDescription('Work order preventif dibuat untuk tanggal jatuh tempo ini, lalu jatuh tempo dimajukan sesuai frekuensi.')
            ->action(function (PreventivePlan $record) {
                $workOrder = $record->issueWorkOrder();
                Notification::make()->title("Work order {$workOrder->number} diterbitkan.")
                    ->body('Jatuh tempo berikutnya: '.$record->next_due_date->format('d/m/Y'))
                    ->actions([Action::make('open')->label('Buka')->url(WorkOrderResource::getUrl('view', ['record' => $workOrder]))])
                    ->success()->send();
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('next_due_date')
            ->columns([
                TextColumn::make('name')->label('Rencana')->searchable()->wrap(),
                TextColumn::make('asset.name')->label('Aset')->searchable()->wrap()
                    ->hidden(fn ($livewire) => $livewire instanceof \Filament\Resources\RelationManagers\RelationManager)
                    ->description(fn (PreventivePlan $record) => $record->asset?->location?->complete_name),
                TextColumn::make('frequency')->label('Frekuensi')->badge(),
                TextColumn::make('next_due_date')->label('Jatuh tempo')->date('d/m/Y')->sortable()
                    ->color(fn (PreventivePlan $record) => $record->isOverdue() ? 'danger' : null)
                    ->description(fn (PreventivePlan $record) => $record->isOverdue() ? 'Terlambat '.(int) $record->next_due_date->diffInDays(today()).' hari' : null),
                TextColumn::make('responsible.name')->label('Penanggung jawab')->placeholder('—'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([
                Filter::make('overdue')->label('Lewat jatuh tempo')->query(fn (Builder $query) => $query->overdue()),
                SelectFilter::make('frequency')->label('Frekuensi')->options(PmFrequency::class),
                TernaryFilter::make('is_active')->label('Aktif')->default(true),
            ])
            ->recordActions([static::issueAction(), EditAction::make(), DeleteAction::make()])
            ->toolbarActions([
                BulkAction::make('issue_bulk')->label('Terbitkan WO terpilih')->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->visible(fn () => static::canOperate())->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $count = $records->filter->is_active->each->issueWorkOrder()->count();
                        Notification::make()->title("{$count} work order preventif diterbitkan.")->success()->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePreventivePlans::route('/')];
    }
}
