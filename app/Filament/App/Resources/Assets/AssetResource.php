<?php

namespace App\Filament\App\Resources\Assets;

use App\Enums\AssetStatus;
use App\Enums\Condition;
use App\Enums\Criticality;
use App\Enums\IdentificationSource;
use App\Enums\Priority;
use App\Enums\VerificationStatus;
use App\Filament\App\Resources\Assets\Pages\CreateAsset;
use App\Filament\App\Resources\Assets\Pages\EditAsset;
use App\Filament\App\Resources\Assets\Pages\ListAssets;
use App\Filament\App\Resources\Assets\Pages\ViewAsset;
use App\Filament\App\Resources\Assets\RelationManagers;
use App\Filament\Concerns\OperationalPermissions;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetSurvey;
use App\Models\Department;
use App\Models\Location;
use App\Support\Money;
use App\Support\Scoring;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class AssetResource extends Resource
{
    use OperationalPermissions;

    protected static ?string $model = Asset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Intelijen Aset';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Aset';

    protected static ?string $pluralModelLabel = 'Register Aset';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'code', 'serial_no'];
    }

    // --- Form ------------------------------------------------------------

    protected static function scoreInput(string $name, string $label, int $max = 100, int $default = 80): TextInput
    {
        return TextInput::make($name)->label($label)->numeric()->integer()->minValue($max === 5 ? 1 : 0)->maxValue($max)
            ->default($default)->required()->live(onBlur: true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->columnSpanFull()->schema([
                TextInput::make('name')->label('Nama aset')->required()->maxLength(255)->columnSpan(2),
                TextInput::make('code')->label('Kode aset')->placeholder('Otomatis, mis. AST/2026/00001')->maxLength(40)
                    ->disabledOn('edit')->dehydrated(fn (string $operation) => $operation === 'create'),
                Select::make('status')->label('Status')->options(AssetStatus::class)->default(AssetStatus::Operational)->required(),
                Select::make('asset_category_id')->label('Kategori')->required()->searchable()->live()
                    ->options(fn () => AssetCategory::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->afterStateUpdated(function ($state, Set $set, string $operation) {
                        if ($operation === 'create' && ($category = AssetCategory::find($state))) {
                            $set('expected_life_years', $category->expected_life_years);
                        }
                    }),
                Select::make('location_id')->label('Lokasi')->required()->searchable()
                    ->options(fn () => Location::query()->where('is_active', true)->orderBy('complete_name')->pluck('complete_name', 'id')),
                Select::make('department_id')->label('Departemen')
                    ->options(fn () => Department::query()->where('is_active', true)->orderBy('sort')->pluck('name', 'id')),
                Toggle::make('is_active')->label('Aktif')->default(true)->inline(false),
            ]),
            Tabs::make()->columnSpanFull()->tabs([
                Tab::make('Identitas & Nilai')->columns(4)->schema([
                    TextInput::make('brand')->label('Merek'),
                    TextInput::make('model')->label('Model / tipe'),
                    TextInput::make('serial_no')->label('Nomor seri'),
                    DatePicker::make('purchase_date')->label('Tanggal perolehan')->maxDate(now()),
                    TextInput::make('acquisition_cost')->label('Nilai perolehan')->numeric()->prefix('Rp')->default(0),
                    TextInput::make('replacement_cost')->label('Biaya penggantian')->numeric()->prefix('Rp')->default(0),
                    TextInput::make('expected_life_years')->label('Umur manfaat (tahun)')->numeric()->minValue(0)->default(5),
                ]),
                Tab::make('Kesehatan Aset')->columns(4)->schema([
                    static::scoreInput('physical_condition', 'Kondisi fisik (25%)'),
                    static::scoreInput('maintenance_compliance', 'Kepatuhan pemeliharaan (20%)'),
                    static::scoreInput('breakdown_reliability', 'Keandalan / bebas kerusakan (15%)'),
                    static::scoreInput('remaining_life_score', 'Sisa umur (15%)'),
                    static::scoreInput('safety_condition', 'Kondisi keselamatan (10%)'),
                    static::scoreInput('operational_reliability', 'Keandalan operasional (10%)'),
                    static::scoreInput('documentation_score', 'Kelengkapan dokumen (5%)'),
                    TextEntry::make('health_preview')->label('Skor kesehatan')->badge()
                        ->state(function (Get $get) {
                            [$score, $condition] = Scoring::health(collect(Scoring::HEALTH_WEIGHTS)->keys()->mapWithKeys(fn ($f) => [$f => (int) $get($f)])->all());

                            return number_format($score, 1, ',', '.').' · '.$condition->getLabel();
                        }),
                ]),
                Tab::make('Kekritisan & Risiko')->columns(3)->schema([
                    static::scoreInput('safety_impact', 'Dampak keselamatan (1–5)', 5, 2),
                    static::scoreInput('guest_impact', 'Dampak pelanggan/tamu (1–5)', 5, 2),
                    static::scoreInput('revenue_impact', 'Dampak pendapatan (1–5)', 5, 2),
                    static::scoreInput('operational_impact', 'Dampak operasional (1–5)', 5, 2),
                    static::scoreInput('replacement_difficulty', 'Kesulitan penggantian (1–5)', 5, 2),
                    TextEntry::make('criticality_preview')->label('Kekritisan & risiko')->badge()
                        ->state(function (Get $get) {
                            [$health] = Scoring::health(collect(Scoring::HEALTH_WEIGHTS)->keys()->mapWithKeys(fn ($f) => [$f => (int) $get($f)])->all());
                            [$crit, $level] = Scoring::criticality(collect(Scoring::CRITICALITY_FIELDS)->mapWithKeys(fn ($f) => [$f => (int) $get($f)])->all());
                            [$risk, $priority] = Scoring::assetRisk($health, $crit);

                            return "{$level->getLabel()} ({$crit}) · Risiko {$risk} · {$priority->getLabel()}";
                        }),
                ]),
                Tab::make('Verifikasi Awal')->columns(3)->schema([
                    Select::make('verification_status')->label('Status verifikasi')->options(VerificationStatus::class)->default(VerificationStatus::NeedsId),
                    Select::make('identification_source')->label('Sumber identifikasi')->options(IdentificationSource::class)->default(IdentificationSource::Manual),
                    Select::make('asset_survey_id')->label('Survei awal')->options(fn () => AssetSurvey::query()->latest('id')->pluck('name', 'id')),
                    Textarea::make('verification_note')->label('Catatan verifikasi')->columnSpanFull(),
                ]),
                Tab::make('Catatan')->schema([
                    Textarea::make('note')->hiddenLabel()->rows(5),
                ]),
            ]),
        ]);
    }

    // --- Tampilan detail -----------------------------------------------

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make('Identitas')->columnSpan(2)->columns(3)->schema([
                    TextEntry::make('code')->label('Kode aset')->weight('bold')->copyable(),
                    TextEntry::make('category.name')->label('Kategori'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('location.complete_name')->label('Lokasi')->columnSpan(2),
                    TextEntry::make('department.name')->label('Departemen')->placeholder('—'),
                    TextEntry::make('brand')->label('Merek')->placeholder('—'),
                    TextEntry::make('model')->label('Model')->placeholder('—'),
                    TextEntry::make('serial_no')->label('Nomor seri')->placeholder('—'),
                    TextEntry::make('purchase_date')->label('Tanggal perolehan')->date('d/m/Y')->placeholder('—'),
                    TextEntry::make('acquisition_cost')->label('Nilai perolehan')->formatStateUsing(fn ($state) => Money::format($state)),
                    TextEntry::make('replacement_cost')->label('Biaya penggantian')->formatStateUsing(fn ($state) => Money::format($state)),
                    TextEntry::make('age')->label('Umur / sisa umur')
                        ->state(fn (Asset $record) => number_format($record->ageYears(), 1, ',', '.').' th / '.number_format($record->remainingLifeYears(), 1, ',', '.').' th (dari '.$record->expected_life_years.' th)'),
                ]),
                View::make('filament.asset-qr')->columnSpan(1),
            ]),
            Section::make('Intelijen aset')->columns(4)->columnSpanFull()->schema([
                TextEntry::make('health_score')->label('Skor kesehatan')->size('lg')->weight('bold')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 1, ',', '.').' / 100'),
                TextEntry::make('condition')->label('Kondisi')->badge(),
                TextEntry::make('criticality')->label('Kekritisan')->badge()->suffix(fn (Asset $record) => ' ('.number_format($record->criticality_score, 2, ',', '.').')'),
                TextEntry::make('priority')->label('Prioritas')->badge()->suffix(fn (Asset $record) => ' · risiko '.number_format($record->risk_score, 1, ',', '.')),
                TextEntry::make('health_parts')->label('Komponen kesehatan')->columnSpan(2)
                    ->state(fn (Asset $record) => "Fisik {$record->physical_condition} · Pemeliharaan {$record->maintenance_compliance} · Keandalan {$record->breakdown_reliability} · Sisa umur {$record->remaining_life_score} · Keselamatan {$record->safety_condition} · Operasional {$record->operational_reliability} · Dokumen {$record->documentation_score}"),
                TextEntry::make('criticality_parts')->label('Komponen kekritisan')->columnSpan(2)
                    ->state(fn (Asset $record) => "Keselamatan {$record->safety_impact} · Pelanggan {$record->guest_impact} · Pendapatan {$record->revenue_impact} · Operasional {$record->operational_impact} · Penggantian {$record->replacement_difficulty}"),
            ]),
            Section::make('Verifikasi awal')->columns(4)->columnSpanFull()->collapsible()->schema([
                TextEntry::make('verification_status')->label('Status verifikasi')->badge(),
                TextEntry::make('identification_source')->label('Sumber')->badge(),
                TextEntry::make('survey.name')->label('Survei')->placeholder('—'),
                TextEntry::make('verifiedBy.name')->label('Diverifikasi oleh')->placeholder('—')
                    ->suffix(fn (Asset $record) => $record->verified_at ? ' · '.$record->verified_at->format('d/m/Y H:i') : ''),
                TextEntry::make('verification_note')->label('Catatan verifikasi')->columnSpanFull()->placeholder('—'),
                TextEntry::make('note')->label('Catatan aset')->columnSpanFull()->placeholder('—'),
            ]),
        ]);
    }

    // --- Tabel ---------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('risk_score', 'desc')
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable()->sortable()->toggleable(),
                TextColumn::make('name')->label('Aset')->searchable()->sortable()->wrap()
                    ->description(fn (Asset $record) => $record->location?->complete_name),
                TextColumn::make('category.name')->label('Kategori')->toggleable(),
                TextColumn::make('department.name')->label('Departemen')->toggleable(),
                TextColumn::make('verification_status')->label('Verifikasi')->badge()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('health_score')->label('Kesehatan')->numeric(1)->sortable()
                    ->color(fn (Asset $record) => $record->condition->getColor())->weight('bold')
                    ->summarize(Average::make()->label('Rata-rata')->numeric(1)),
                TextColumn::make('condition')->label('Kondisi')->badge(),
                TextColumn::make('criticality')->label('Kekritisan')->badge(),
                TextColumn::make('risk_score')->label('Risiko')->numeric(1)->sortable(),
                TextColumn::make('priority')->label('Prioritas')->badge(),
                TextColumn::make('status')->label('Status')->badge()->toggleable(),
                TextColumn::make('acquisition_cost')->label('Nilai perolehan')->sortable()->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => Money::format($state))
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => Money::short($state))),
            ])
            ->filters([
                SelectFilter::make('department_id')->label('Departemen')->relationship('department', 'name'),
                SelectFilter::make('asset_category_id')->label('Kategori')->relationship('category', 'name'),
                SelectFilter::make('location_id')->label('Lokasi (termasuk sublokasi)')->searchable()
                    ->options(fn () => Location::query()->orderBy('complete_name')->pluck('complete_name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'] ?? null) && ($location = Location::find($data['value']))) {
                            $query->whereIn('location_id', [$location->id, ...$location->descendantIds()]);
                        }
                    }),
                SelectFilter::make('condition')->label('Kondisi')->options(Condition::class)->multiple(),
                SelectFilter::make('criticality')->label('Kekritisan')->options(Criticality::class)->multiple(),
                SelectFilter::make('priority')->label('Prioritas')->options(Priority::class)->multiple(),
                SelectFilter::make('status')->label('Status')->options(AssetStatus::class)->multiple(),
                SelectFilter::make('verification_status')->label('Verifikasi')->options(VerificationStatus::class)->multiple(),
                SelectFilter::make('asset_survey_id')->label('Survei')->relationship('survey', 'name'),
                TernaryFilter::make('is_active')->label('Aktif')->default(true),
            ])
            ->groups([
                Group::make('department.name')->label('Departemen'),
                Group::make('category.name')->label('Kategori'),
                Group::make('location.complete_name')->label('Lokasi'),
                Group::make('condition')->label('Kondisi')->getTitleFromRecordUsing(fn (Asset $record) => $record->condition->getLabel()),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('print_qr')->label('Cetak label QR')->icon(Heroicon::OutlinedQrCode)
                        ->action(fn (Collection $records, $livewire) => $livewire->redirect(route('qr.labels', ['ids' => $records->pluck('id')->implode(',')]))),
                    BulkAction::make('mark_verified')->label('Tandai terverifikasi')->icon(Heroicon::OutlinedCheckBadge)
                        ->visible(fn () => static::canOperate())->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->markVerification(VerificationStatus::Verified))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InspectionsRelationManager::class,
            RelationManagers\MaintenanceRequestsRelationManager::class,
            RelationManagers\WorkOrdersRelationManager::class,
            RelationManagers\PreventivePlansRelationManager::class,
            RelationManagers\RisksRelationManager::class,
            RelationManagers\CapexProposalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssets::route('/'),
            'create' => CreateAsset::route('/create'),
            'view' => ViewAsset::route('/{record}'),
            'edit' => EditAsset::route('/{record}/edit'),
        ];
    }
}
