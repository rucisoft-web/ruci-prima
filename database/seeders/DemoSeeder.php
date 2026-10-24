<?php

namespace Database\Seeders;

use Database\Seeders\Demo\BluebirdDemo;
use Database\Seeders\Demo\DemoCompany;
use Database\Seeders\Demo\DiamondDemo;
use Database\Seeders\Demo\SahidDemo;
use Illuminate\Database\Seeder;

/**
 * Data contoh tiga perusahaan: Hotel Sahid (perhotelan), Blue Bird (transportasi), dan Diamond Food (makanan & rantai dingin).
 * Profil umum mengacu pada laporan tahunan 2025 masing-masing; angka operasional adalah ilustrasi.
 */
class DemoSeeder extends Seeder
{
    /** @var array<class-string<DemoCompany>> */
    public const COMPANIES = [SahidDemo::class, BluebirdDemo::class, DiamondDemo::class];

    public const SLUGS = ['sahid', 'bluebird', 'diamond'];

    public function run(): void
    {
        $this->call(self::COMPANIES);
    }
}
