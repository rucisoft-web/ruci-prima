<?php

namespace App\Filament\App\Pages;

use App\Enums\VerificationStatus;
use App\Filament\App\Resources\Assets\AssetResource;
use App\Filament\App\Resources\Assets\IntakeActions;
use App\Filament\Concerns\InteractsWithTenant;
use App\Models\Asset;
use App\Models\Location;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Url;
use UnitEnum;

/** Verifikasi lapangan: tim survei menandai aset ada, tidak ditemukan, atau tambahan. */
class VerifikasiAset extends Page implements HasTable
{
    use InteractsWithTable, InteractsWithTenant;

    #[Url(as: 'filters')]
    public ?array $tableFilters = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|UnitEnum|null $navigationGroup = 'Identifikasi Awal';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Verifikasi Aset';

    protected static ?string $title = 'Verifikasi Aset';

    protected static ?string $slug = 'verifikasi-aset';

    protected string $view = 'filament.pages.verifikasi-aset';

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make(IntakeActions::all())->label('Identifikasi awal')->icon(Heroicon::OutlinedSparkles)->button()
                ->visible(fn () => static::canOperate()),
        ];
    }

    protected function markAction(string $name, string $label, VerificationStatus $status, $icon, string $color): Action
    {
        return Action::make($name)->label($label)->icon($icon)->color($color)->iconButton()->tooltip($label)
            ->visible(fn () => static::canOperate())
            ->action(fn (Asset $record) => $record->markVerification($status));
    }

    protected function markBulk(string $name, string $label, VerificationStatus $status, $icon): BulkAction
    {
        return BulkAction::make($name)->label($label)->icon($icon)
            ->visible(fn () => static::canOperate())
            ->action(function (Collection $records) use ($status) {
                $records->each->markVerification($status);
                Notification::make()->title($records->count().' aset: '.$status->getLabel())->success()->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Asset::query()->with(['location', 'category', 'verifiedBy'])->where('is_active', true))
            ->defaultSort('location_id')
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable(),
                TextColumn::make('name')->label('Aset')->searchable()->wrap()
                    ->url(fn (Asset $record) => AssetResource::getUrl('view', ['record' => $record])),
                TextColumn::make('location.complete_name')->label('Lokasi')->wrap(),
                TextColumn::make('category.name')->label('Kategori')->toggleable(),
                TextColumn::make('identification_source')->label('Sumber')->badge(),
                TextColumn::make('verification_status')->label('Verifikasi')->badge(),
                TextColumn::make('verifiedBy.name')->label('Oleh')->placeholder('—')
                    ->description(fn (Asset $record) => $record->verified_at?->format('d/m/Y H:i')),
            ])
            ->filters([
                SelectFilter::make('verification_status')->label('Status verifikasi')->options(VerificationStatus::class)->multiple(),
                SelectFilter::make('asset_survey_id')->label('Survei')->relationship('survey', 'name'),
                SelectFilter::make('location_id')->label('Lokasi (termasuk sublokasi)')->searchable()
                    ->options(fn () => Location::query()->orderBy('complete_name')->pluck('complete_name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'] ?? null) && ($location = Location::find($data['value']))) {
                            $query->whereIn('location_id', [$location->id, ...$location->descendantIds()]);
                        }
                    }),
            ])
            ->recordActions([
                $this->markAction('verified', 'Ada / terverifikasi', VerificationStatus::Verified, Heroicon::OutlinedCheckCircle, 'success'),
                $this->markAction('missing', 'Tidak ditemukan', VerificationStatus::Missing, Heroicon::OutlinedXCircle, 'danger'),
                $this->markAction('additional', 'Aset tambahan', VerificationStatus::Additional, Heroicon::OutlinedPlusCircle, 'warning'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    $this->markBulk('bulk_verified', 'Tandai terverifikasi', VerificationStatus::Verified, Heroicon::OutlinedCheckCircle),
                    $this->markBulk('bulk_missing', 'Tandai tidak ditemukan', VerificationStatus::Missing, Heroicon::OutlinedXCircle),
                ]),
            ]);
    }
}
