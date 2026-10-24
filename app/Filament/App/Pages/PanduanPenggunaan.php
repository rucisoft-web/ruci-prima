<?php

namespace App\Filament\App\Pages;

use App\Support\PanduanPeran;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;
use UnitEnum;

/** Panduan penggunaan langkah demi langkah untuk setiap peran, dengan gambar layar. */
class PanduanPenggunaan extends Page
{
    protected static ?string $slug = 'panduan-penggunaan';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Panduan & Informasi';

    protected static ?string $navigationLabel = 'Panduan Penggunaan';

    protected static ?string $title = 'Panduan Penggunaan';

    protected string $view = 'filament.pages.panduan-penggunaan';

    #[Url(as: 'peran')]
    public ?string $role = null;

    public function getHeading(): string
    {
        return '';
    }

    public function getViewData(): array
    {
        $guides = PanduanPeran::all();
        $mine = PanduanPeran::forUser(auth()->user(), filament()->getTenant());
        $active = array_key_exists((string) $this->role, $guides) ? $this->role : $mine;

        return ['guides' => $guides, 'active' => $active, 'mine' => $mine, 'guide' => $guides[$active]];
    }
}
