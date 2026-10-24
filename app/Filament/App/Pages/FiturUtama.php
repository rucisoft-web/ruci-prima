<?php

namespace App\Filament\App\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Penjelasan fitur utama Ruci PRIMA (isi sama dengan halaman publik /fitur). */
class FiturUtama extends Page
{
    protected static ?string $slug = 'fitur-utama';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Panduan & Informasi';

    protected static ?string $navigationLabel = 'Fitur Utama';

    protected static ?string $title = 'Fitur Utama Ruci PRIMA';

    protected string $view = 'filament.pages.fitur-utama';

    public function getHeading(): string
    {
        return '';
    }

    public function getViewData(): array
    {
        return ['guestUrl' => null, 'dashboardUrl' => DasborPimpinan::getUrl()];
    }
}
