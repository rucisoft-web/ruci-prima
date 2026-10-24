<?php

namespace App\Filament\App\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Mengapa perusahaan memerlukan Ruci PRIMA: masalah yang umum terjadi dan solusi yang diberikan. */
class KenapaPerluPrima extends Page
{
    protected static ?string $slug = 'kenapa-perlu-ruci-prima';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Panduan & Informasi';

    protected static ?string $navigationLabel = 'Kenapa Perlu Ruci PRIMA';

    protected static ?string $title = 'Kenapa Perusahaan Perlu Ruci PRIMA';

    protected string $view = 'filament.pages.kenapa-perlu-prima';

    public function getHeading(): string
    {
        return '';
    }

    public function getViewData(): array
    {
        return ['guideUrl' => PanduanPenggunaan::getUrl(), 'featuresUrl' => FiturUtama::getUrl()];
    }
}
