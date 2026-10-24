<?php

namespace App\Filament\App\Resources\AuditLogs;

use App\Filament\App\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Concerns\ManagerPermissions;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AuditLogResource extends Resource
{
    use ManagerPermissions;

    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Konfigurasi';

    protected static ?int $navigationSort = 90;

    protected static ?string $modelLabel = 'Jejak audit';

    protected static ?string $pluralModelLabel = 'Jejak Audit';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('user.name')->label('Pengguna')->placeholder('Sistem / QR publik'),
                TextColumn::make('event')->label('Kejadian')->badge()
                    ->color(fn (string $state) => match ($state) { 'dibuat' => 'success', 'dihapus' => 'danger', default => 'info' }),
                TextColumn::make('auditable_type')->label('Jenis')->formatStateUsing(fn (AuditLog $record) => $record->typeLabel()),
                TextColumn::make('label')->label('Data')->searchable()->wrap(),
                TextColumn::make('changes')->label('Perubahan')->wrap()
                    ->state(fn (AuditLog $record) => collect($record->changes ?? [])
                        ->map(fn ($v, $k) => is_array($v) ? "{$k}: ".json_encode($v['dari'] ?? null, JSON_UNESCAPED_UNICODE).' → '.json_encode($v['ke'] ?? null, JSON_UNESCAPED_UNICODE) : $k)
                        ->implode('; ') ?: '—'),
                TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')->label('Kejadian')->options(['dibuat' => 'Dibuat', 'diubah' => 'Diubah', 'dihapus' => 'Dihapus']),
                SelectFilter::make('auditable_type')->label('Jenis')->options(fn () => AuditLog::query()->distinct()->pluck('auditable_type')
                    ->mapWithKeys(fn ($t) => [$t => (new AuditLog(['auditable_type' => $t]))->typeLabel()])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAuditLogs::route('/')];
    }
}
