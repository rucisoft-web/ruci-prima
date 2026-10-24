<?php

namespace App\Filament\Forms;

use App\Models\Asset;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;

/** Pilihan aset yang dicari berdasarkan kode/nama, disembunyikan bila form dibuka dari halaman aset. */
class AssetSelect
{
    public static function make(string $name = 'asset_id', bool $required = true): Select
    {
        return Select::make($name)->label('Aset')->required($required)->searchable()
            ->getSearchResultsUsing(fn (string $search) => Asset::query()
                ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                ->with('location')->orderBy('name')->limit(40)->get()
                ->mapWithKeys(fn (Asset $a) => [$a->id => static::label($a)]))
            ->getOptionLabelUsing(fn ($value) => ($a = Asset::with('location')->find($value)) ? static::label($a) : null)
            ->hidden(fn ($livewire) => $livewire instanceof RelationManager);
    }

    public static function label(Asset $asset): string
    {
        return "{$asset->code} · {$asset->name} — {$asset->location?->complete_name}";
    }
}
