<?php

namespace App\Support;

use App\Models\AssetCategory;
use App\Models\Company;
use App\Models\Department;

/** Data awal umum untuk perusahaan baru: departemen dan kategori aset yang lazim. */
class StarterData
{
    public const DEPARTMENTS = [
        ['Operasional', 'OPS'],
        ['Teknik & Pemeliharaan', 'ENG'],
        ['Umum & Administrasi', 'ADM'],
        ['Teknologi Informasi', 'IT'],
        ['Keamanan & K3', 'SEC'],
    ];

    /** [nama, kode, umur manfaat (tahun), kekritisan bawaan] */
    public const CATEGORIES = [
        ['HVAC & Tata Udara', 'HVAC', 15, 'high'],
        ['Kelistrikan & Genset', 'ELEC', 20, 'critical'],
        ['Lift & Eskalator', 'LIFT', 25, 'critical'],
        ['Plumbing & Pompa', 'PLMB', 15, 'high'],
        ['Proteksi Kebakaran', 'FIRE', 15, 'critical'],
        ['Furnitur & Interior', 'FURN', 8, 'low'],
        ['TI & Jaringan', 'IT', 5, 'medium'],
        ['Keamanan & CCTV', 'SEC', 7, 'medium'],
        ['Kendaraan', 'VEH', 8, 'medium'],
        ['Bangunan & Sipil', 'CIV', 30, 'high'],
    ];

    public static function seed(Company $company): void
    {
        app(CurrentCompany::class)->runAs($company, function () {
            foreach (self::DEPARTMENTS as $i => [$name, $code]) {
                Department::firstOrCreate(['code' => $code], ['name' => $name, 'sort' => ($i + 1) * 10]);
            }

            foreach (self::CATEGORIES as [$name, $code, $life, $criticality]) {
                AssetCategory::firstOrCreate(['code' => $code], ['name' => $name, 'expected_life_years' => $life, 'default_criticality' => $criticality]);
            }
        });
    }
}
