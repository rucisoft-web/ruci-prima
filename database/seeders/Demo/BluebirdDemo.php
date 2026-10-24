<?php

namespace Database\Seeders\Demo;

use App\Enums\AssignmentState;
use App\Enums\Role;

/**
 * Blue Bird: transportasi darat (taksi, sewa kendaraan, shuttle Cititrans, bus Bigbird) dengan jaringan pool.
 * Profil umum mengacu pada Laporan Tahunan & Keberlanjutan 2025 PT Blue Bird Tbk;
 * data aset, transaksi, nama orang, dan nomor dokumen adalah ilustrasi untuk demo.
 */
class BluebirdDemo extends DemoCompany
{
    protected function profile(): array
    {
        $pools = [
            'halim' => 'Pool Halim', 'ciputat' => 'Pool Ciputat', 'perigi' => 'Pool Perigi', 'buncit' => 'Pool Warung Buncit',
            'jimbaran' => 'Pool Jimbaran Bali', 'semarang' => 'Pool Semarang',
        ];
        $locations = [
            ['hq', 'Gedung Bluebird Mampang', null],
            ['lobby', 'Lantai 1 · Lobby & Layanan Pelanggan', 'hq'],
            ['callcenter', 'Lantai 3 · Command Center & Call Center', 'hq'],
            ['office', 'Lantai 5 · Kantor Direksi', 'hq'],
            ['datacenter', 'Data Center', 'hq'],
            ['hqplant', 'Plant & Utilitas Gedung', 'hq'],
        ];
        $poolKeys = [];
        foreach ($pools as $key => $name) {
            $locations[] = [$key, $name, null];
            $locations[] = ["{$key}_ws", "Bengkel {$name}", $key];
            $locations[] = ["{$key}_ev", "Area SPKLU {$name}", $key];
            $locations[] = ["{$key}_wash", "Area Cuci {$name}", $key];
            $poolKeys[] = $key;
        }
        $locations[] = ['cititrans', 'Terminal Shuttle Cititrans Bandara', null];
        $locations[] = ['bigbird', 'Depo Bus Bigbird Cakung', null];

        return [
            'seed' => 2001,
            'domain' => 'bluebird.demo',
            'company' => [
                'name' => 'PT Blue Bird Tbk',
                'code' => 'BIRD',
                'slug' => 'bluebird',
                'industry' => 'Transportasi & Mobilitas',
                'city' => 'Jakarta',
                'address' => 'Gedung Bluebird, Jl. Mampang Prapatan Raya No. 60, Jakarta Selatan 12790 (data operasional ilustrasi untuk demo)',
            ],
            'users' => [
                'manajer' => ['Yudi Hartono', 'manajer', Role::Manajer, 'Kepala Teknik & Fasilitas'],
                'teknisi' => ['Eko Saputra', 'teknisi', Role::Staf, 'Mekanik Senior Pool Halim'],
                'supervisor' => ['Maya Anggraini', 'supervisor', Role::Staf, 'Supervisor Operasional Pool'],
                'umum' => ['Rizky Ramadhan', 'umum', Role::Staf, 'Staf General Affairs'],
                'direktur' => ['Taufik Hidayat', 'direktur', Role::Eksekutif, 'Direktur Operasional'],
            ],
            'departments' => [
                'ops' => 'Operasional Pool', 'workshop' => 'Teknik & Bengkel', 'ev' => 'Armada Listrik (EV)', 'cs' => 'Layanan Pelanggan',
                'hse' => 'Keamanan & K3', 'it' => 'Teknologi & Digital', 'shuttle' => 'Cititrans & Bigbird', 'finance' => 'Keuangan',
                'hr' => 'SDM & General Affairs',
            ],
            'categories' => [
                'fleet' => ['Armada Operasional (batch)', 7, 'critical'],
                'ev' => ['Infrastruktur Pengisian EV', 10, 'critical'],
                'ws' => ['Peralatan Bengkel', 10, 'high'],
                'wash' => ['Fasilitas Cuci Kendaraan', 8, 'medium'],
                'elec' => ['Kelistrikan & Genset', 20, 'critical'],
                'hvac' => ['Tata Udara Gedung', 15, 'high'],
                'lift' => ['Lift Gedung', 25, 'critical'],
                'fire' => ['Proteksi Kebakaran', 15, 'critical'],
                'it' => ['TI, Dispatch & Call Center', 5, 'high'],
                'sec' => ['Keamanan & CCTV', 7, 'medium'],
                'env' => ['Lingkungan & Pengolahan Limbah', 12, 'high'],
            ],
            'unit_costs' => ['ev' => [180, 420], 'ws' => [25, 160], 'wash' => [15, 60], 'fire' => [1, 2], 'sec' => [8, 25], 'elec' => [90, 260]],
            'cost_scale' => 1.6,
            'locations' => $locations,
            'survey' => ['name' => 'Sensus Aset Pool 2026', 'note' => 'Verifikasi fasilitas bengkel, SPKLU, dan area cuci di seluruh pool.'],
            'templates' => [
                [
                    'name' => 'Fasilitas Pool Standar', 'location_type' => 'boh', 'note' => 'Perlengkapan minimum setiap pool taksi.',
                    'lines' => [
                        ['SPKLU DC 60 kW', 'ev', 2, 'ev', 'Delta', 'DC Wallbox 60'],
                        ['Car lift 2 tiang 4 ton', 'ws', 2, 'workshop', 'Launch', 'TLT240'],
                        ['Kompresor angin 10 HP', 'ws', 1, 'workshop', 'Hitachi', 'Bebicon 7.5'],
                        ['Spooring & balancing', 'ws', 1, 'workshop', 'Hunter', 'Hawkeye'],
                        ['Mesin cuci mobil tekanan tinggi', 'wash', 1, 'ops', 'Kärcher', 'HD 10/25'],
                        ['Genset pool 100 kVA', 'elec', 1, 'workshop', 'Perkins', '1104C'],
                        ['NVR CCTV pool', 'sec', 1, 'hse', 'Hikvision', 'DS-7732NI'],
                        ['APAR CO2 6 kg', 'fire', 4, 'hse', 'Yamato', 'C-6'],
                    ],
                    'locations' => $poolKeys,
                    'teams' => [
                        ['halim', 'Tim Pool Halim', AssignmentState::Done], ['ciputat', 'Tim Pool Ciputat', AssignmentState::Done],
                        ['perigi', 'Tim Pool Perigi', AssignmentState::Done], ['buncit', 'Tim Pool Warung Buncit', AssignmentState::InProgress],
                        ['jimbaran', 'Tim Bali', AssignmentState::InProgress], ['semarang', 'Tim Jawa Tengah', AssignmentState::Assigned],
                    ],
                ],
            ],
            'additional' => [
                ['halim_ws', 'Scanner diagnosa EV portabel', 'ws', 'ev'],
                ['ciputat_ws', 'Dongkrak buaya 3 ton tambahan', 'ws', 'workshop'],
                ['jimbaran', 'Mesin pencacah sampah plastik', 'env', 'hse'],
            ],
            'major' => [
                ['Batch e-Bluebird 2025 (100 unit)', 'fleet', 'halim', 'ev', 'BYD', 'e6', 2025, 62000, 'excellent', [3, 5, 5, 5, 4]],
                ['Batch e-Silverbird 2024 (40 unit)', 'fleet', 'buncit', 'ev', 'Hyundai', 'Ioniq 5', 2024, 32000, 'good', [3, 5, 5, 4, 4]],
                ['Batch taksi reguler 2019 (150 unit)', 'fleet', 'perigi', 'ops', 'Toyota', 'Transmover', 2019, 33000, 'poor', [4, 4, 5, 5, 3]],
                ['Batch bus Bigbird 2018 (20 unit)', 'fleet', 'bigbird', 'shuttle', 'Mercedes-Benz', 'OH 1526', 2018, 30000, 'fair', [4, 4, 4, 4, 4]],
                ['Batch shuttle Cititrans 2022 (30 unit)', 'fleet', 'cititrans', 'shuttle', 'Toyota', 'Hiace Premio', 2022, 18000, 'good', [3, 5, 5, 4, 3]],
                ['SPKLU Ultra-fast 180 kW Pool Halim', 'ev', 'halim_ev', 'ev', 'ABB', 'Terra 184', 2024, 1650, 'good', [3, 3, 5, 5, 4]],
                ['Trafo khusus SPKLU Pool Halim 1250 kVA', 'elec', 'halim_ev', 'ev', 'Schneider', 'Trihal', 2024, 980, 'excellent', [5, 3, 5, 5, 4]],
                ['Panel distribusi SPKLU Pool Ciputat', 'elec', 'ciputat_ev', 'ev', 'Schneider', 'Prisma G', 2023, 420, 'fair', [5, 2, 4, 5, 3]],
                ['Car wash otomatis tunnel Pool Halim', 'wash', 'halim_wash', 'ops', 'Istobal', 'M\'NEX 22', 2017, 1350, 'poor', [2, 4, 3, 4, 3]],
                ['Instalasi daur ulang air cucian', 'env', 'halim_wash', 'hse', 'Biotech', 'WRS 30', 2017, 650, 'poor', [3, 2, 2, 4, 3]],
                ['Waste Station Pool Jimbaran', 'env', 'jimbaran', 'hse', 'Rekosistem', 'Waste Station', 2025, 310, 'excellent', [2, 3, 1, 3, 2]],
                ['Paint booth bengkel body', 'ws', 'perigi_ws', 'workshop', 'Blowtherm', 'Junior', 2015, 780, 'fair', [4, 2, 3, 4, 3]],
                ['Mesin bubut & press bengkel pusat', 'ws', 'perigi_ws', 'workshop', 'Krisbow', 'KW15', 2013, 260, 'poor', [3, 1, 2, 3, 2]],
                ['Lift brake tester bus', 'ws', 'bigbird', 'shuttle', 'MAHA', 'IW 7', 2016, 540, 'fair', [5, 3, 3, 4, 3]],
                ['Genset gedung 1000 kVA', 'elec', 'hqplant', 'hr', 'Cummins', 'C1000D5', 2012, 2800, 'fair', [5, 4, 5, 5, 5]],
                ['UPS Command Center 60 kVA', 'elec', 'datacenter', 'it', 'APC', 'Galaxy VX', 2021, 520, 'good', [3, 4, 5, 5, 3]],
                ['Chiller gedung 200 TR', 'hvac', 'hqplant', 'hr', 'Daikin', 'EWAD-200', 2013, 2100, 'poor', [3, 4, 3, 4, 4]],
                ['VRV Command Center', 'hvac', 'callcenter', 'it', 'Daikin', 'VRV X', 2020, 640, 'good', [2, 3, 5, 5, 3]],
                ['Lift Penumpang Gedung #1', 'lift', 'hq', 'hr', 'Hyundai', 'LUXEN', 2012, 1100, 'fair', [5, 3, 2, 4, 4]],
                ['Lift Penumpang Gedung #2', 'lift', 'hq', 'hr', 'Hyundai', 'LUXEN', 2012, 1100, 'critical', [5, 3, 2, 4, 4]],
                ['Pompa hidran & sprinkler gedung', 'fire', 'hqplant', 'hse', 'Ebara', 'FSA 500 GPM', 2012, 620, 'fair', [5, 3, 2, 4, 4]],
                ['Server dispatch & aplikasi MyBluebird', 'it', 'datacenter', 'it', 'Dell', 'R760', 2023, 1450, 'excellent', [2, 5, 5, 5, 4]],
                ['Sistem telepon call center 120 seat', 'it', 'callcenter', 'cs', 'Avaya', 'Aura', 2016, 980, 'poor', [1, 5, 4, 5, 3]],
                ['Video wall command center', 'it', 'callcenter', 'ops', 'Samsung', 'VM55B', 2020, 760, 'good', [2, 3, 3, 4, 3]],
                ['Core switch & firewall', 'it', 'datacenter', 'it', 'Fortinet', 'FG-600F', 2022, 690, 'good', [2, 4, 5, 5, 3]],
                ['NVR CCTV gedung 128 kanal', 'sec', 'datacenter', 'hse', 'Hikvision', 'DS-96128NI', 2019, 140, 'fair', [4, 2, 2, 3, 2]],
            ],
            'pm_frequency' => ['fleet' => 'monthly', 'ev' => 'monthly', 'ws' => 'quarterly', 'wash' => 'monthly', 'elec' => 'monthly', 'hvac' => 'monthly', 'lift' => 'monthly', 'fire' => 'quarterly', 'it' => 'quarterly', 'sec' => 'semiannual', 'env' => 'monthly'],
            'meter' => ['ev', 'kWh'],
            'wo' => ['preventive' => [12, 18], 'corrective' => [5, 11], 'emergency' => [0, 4]],
            'corrective' => ['SPKLU gagal komunikasi dengan server', 'Konektor CCS aus', 'Car lift tidak bisa turun', 'Kompresor angin bocor', 'Pompa cuci tidak bertekanan', 'Genset gagal start', 'Modul baterai suhu tinggi', 'Panel trip saat pengisian serentak'],
            'complaints' => ['SPKLU tidak bisa memulai pengisian', 'Lampu bengkel mati', 'Air cucian tidak keluar', 'Kompresor berisik', 'CCTV pool tidak tampil', 'Kabel charger terkelupas', 'Car lift bunyi keras', 'AC ruang tunggu pengemudi tidak dingin'],
            'reporters' => ['Pengemudi pool', 'Kepala regu pool', 'Petugas SPKLU', 'Satpam pool', null],
            'risks' => [
                ['Antrean pengisian EV saat pergantian shift', 'SPKLU Ultra-fast 180 kW Pool Halim', 4, 4, 'mitigating', 'Penjadwalan pengisian berbasis aplikasi dan tambah 4 titik SPKLU.'],
                ['Taksi reguler 2019 melewati umur ekonomis', 'Batch taksi reguler 2019 (150 unit)', 4, 4, 'open', 'Peremajaan bertahap ke armada listrik.'],
                ['Lift gedung #2 macet', 'Lift Penumpang Gedung #2', 4, 5, 'open', 'Modernisasi kontrol; batasi pemakaian sementara.'],
                ['Sistem telepon call center gagal saat jam sibuk', 'Sistem telepon call center 120 seat', 3, 5, 'mitigating', 'Migrasi ke contact center berbasis cloud.'],
                ['Genset gedung gagal saat PLN padam', 'Genset gedung 1000 kVA', 2, 5, 'mitigating', 'Uji beban bulanan, kontrak servis 24 jam.'],
                ['Air limbah cucian melebihi baku mutu', 'Instalasi daur ulang air cucian', 3, 4, 'open', 'Revitalisasi filter dan media; uji lab bulanan.'],
                ['Trafo SPKLU kelebihan beban', 'Panel distribusi SPKLU Pool Ciputat', 3, 4, 'open', 'Tambah daya PLN dan manajemen beban dinamis.'],
                ['Kebakaran di paint booth', 'Paint booth bengkel body', 2, 5, 'mitigating', 'Pembersihan filter dan detektor api otomatis.'],
                ['Rem bus tidak terdeteksi aus', 'Lift brake tester bus', 2, 5, 'open', 'Kalibrasi tahunan alat uji rem.'],
                ['Data dispatch hilang saat gangguan server', 'Server dispatch & aplikasi MyBluebird', 1, 5, 'mitigating', 'Replikasi aktif-aktif ke cloud.'],
                ['Chiller gedung bocor refrigeran', 'Chiller gedung 200 TR', 3, 3, 'open', 'Ganti ke VRF modular saat renovasi.'],
                ['CCTV gedung tidak merekam', 'NVR CCTV gedung 128 kanal', 2, 2, 'closed', 'HDD diganti.'],
            ],
            'capex' => [
                ['Penambahan 300 unit taksi listrik (e-Bluebird)', 'Batch taksi reguler 2019 (150 unit)', 'revenue', 'replace', 186000, [5, 3, 5, 4], 'proposed', 'Target peningkatan rasio kendaraan listrik; biaya energi per km lebih rendah dari BBM.'],
                ['Penambahan 8 titik SPKLU di 4 pool', 'SPKLU Ultra-fast 180 kW Pool Halim', 'revenue', 'replace', 9600, [4, 2, 5, 5], 'approved', 'Antrean pengisian mengurangi jam operasi armada listrik.'],
                ['Peremajaan bus Bigbird (10 unit)', 'Batch bus Bigbird 2018 (20 unit)', 'safety', 'replace', 28000, [4, 4, 4, 3], 'proposed', 'Biaya perbaikan naik dan permintaan sewa bus pariwisata tumbuh.'],
                ['Migrasi contact center ke cloud', 'Sistem telepon call center 120 seat', 'guest', 'replace', 2400, [5, 1, 4, 4], 'proposed', 'Perangkat lama tanpa dukungan vendor; pelanggan mengeluh antrean telepon.'],
                ['Modernisasi Lift Gedung #2', 'Lift Penumpang Gedung #2', 'safety', 'overhaul', 1250, [3, 5, 1, 5], 'approved', 'Risiko kritis keselamatan.'],
                ['Revitalisasi daur ulang air cucian', 'Instalasi daur ulang air cucian', 'mandatory', 'overhaul', 850, [1, 3, 1, 4], 'proposed', 'Kewajiban baku mutu air limbah dan target keberlanjutan.'],
                ['Panel surya atap pool (500 kWp)', null, 'efficiency', 'replace', 7200, [2, 1, 3, 2], 'draft', 'Menekan biaya listrik pengisian EV di siang hari.'],
                ['Penggantian car wash tunnel Pool Halim', 'Car wash otomatis tunnel Pool Halim', 'efficiency', 'replace', 1600, [3, 2, 3, 3], 'draft', 'Kerusakan berulang; waktu cuci menghambat keluar pool.'],
                ['Retrofit LED seluruh pool', null, 'efficiency', 'replace', 640, [2, 2, 1, 2], 'done', 'Penghematan listrik ±22%.'],
            ],
            'rooms' => [
                ['Ruang Rapat Direksi', 16, 'Layar 86", video conference', 'office'],
                ['Ruang Rapat Command Center', 10, 'Video wall, papan tulis', 'callcenter'],
                ['Ruang Training Pengemudi', 40, 'Proyektor, sound system, simulator', 'lobby'],
                ['Ruang Diskusi Teknik', 6, 'TV 55", papan tulis', 'hq'],
            ],
            'agendas' => [
                ['Rapat produktivitas armada harian', 'ops'], ['Evaluasi utilisasi SPKLU', 'ev'], ['Review keluhan pelanggan', 'cs'],
                ['Training pengemudi baru', 'hr'], ['Koordinasi K3 pool', 'hse'], ['Rapat rute baru Cititrans', 'shuttle'],
                ['Review anggaran bulanan', 'finance'], ['Sprint review aplikasi MyBluebird', 'it'], ['Evaluasi vendor suku cadang', 'workshop'],
                ['Rapat persiapan mudik gratis', 'hr'],
            ],
            'vehicles' => [
                ['B 1001 BBD', 'Toyota Innova Zenix Hybrid (direksi)', 'car', 2024, 'Pertamax', 7, 28_430, 'Sutrisno', 120, 1100, null, 80, 25, 30_000],
                ['B 7201 BBD', 'Isuzu Elf NMR (mobil derek)', 'pickup', 2019, 'Solar', 3, 154_870, 'Darmawan', 30, 300, 14, 160, 6, 155_000],
                ['B 7202 BBD', 'Mitsubishi L300 (mobil servis keliling)', 'pickup', 2021, 'Solar', 3, 98_120, 'Wahyudi', -6, 600, 45, 200, 15, 100_000],
                ['B 2203 BBD', 'Hyundai Ioniq 5 (patroli lapangan)', 'car', 2024, 'Listrik', 5, 36_900, 'Rudi Setiawan', 210, 1200, null, 110, 50, 40_000],
                ['B 2204 BBD', 'Toyota Avanza 1.5 G (operasional pool)', 'car', 2020, 'Pertalite', 7, 112_880, null, 65, 180, null, -3, 70, 115_000, 'maintenance'],
                ['B 4405 BBD', 'Honda PCX 160 (kurir dokumen)', 'motorcycle', 2023, 'Pertalite', 2, 21_430, null, 220, 900, null, null, 45, 24_000],
            ],
            'stations' => ['SPBU 31.127.01 Mampang', 'SPBU 34.129.03 Pancoran', 'SPBU 34.131.02 Halim', 'SPBU 34.154.01 Ciputat'],
            'chargers' => ['SPKLU Pool Halim', 'SPKLU Pool Warung Buncit', 'SPKLU Pool Ciputat'],
            'workshops' => ['default' => 'Bengkel Pusat Pool Perigi', 'motorcycle' => 'AHASS Mampang'],
            'trips' => [
                ['Inspeksi kesiapan pool', 'Pool Ciputat', 'ops', 3],
                ['Antar direksi rapat dengan regulator', 'Kementerian Perhubungan', 'finance', 2],
                ['Ambil suku cadang EV di distributor', 'Sunter', 'workshop', 2],
                ['Audit K3 bengkel', 'Pool Perigi', 'hse', 3],
                ['Survei lokasi SPKLU baru', 'Cibubur', 'ev', 3],
                ['Kunjungan pelanggan korporat', 'Sudirman', 'cs', 2],
                ['Setor dokumen pajak', 'KPP Wajib Pajak Besar', 'finance', 1],
                ['Koordinasi rute Cititrans', 'Bandara Soekarno-Hatta', 'shuttle', 3],
            ],
            'supplies' => $this->officeSupplies([
                ['OPR-001', 'Formulir serah terima kendaraan', 'printing', 'buku', 60, 20, 18_000, 25],
                ['OPR-002', 'Stiker armada & nomor lambung', 'other', 'lembar', 200, 60, 12_000, 70],
                ['KBR-005', 'Hand sanitizer 500 ml', 'cleaning', 'botol', 40, 15, 38_000, 14],
                ['PTR-005', 'Air mineral botol 600 ml (ruang pengemudi)', 'pantry', 'box', 30, 12, 52_000, 18],
            ]),
            'storage' => ['default' => 'Gudang GA Gedung Mampang', 'pantry' => 'Pantry lantai 1'],
            'supply_users' => ['Pool Halim', 'Pool Ciputat', 'Command Center', 'Keuangan', 'SDM', 'Layanan Pelanggan'],
            'low_stock' => ['OPR-001' => 8, 'CTK-001' => 1, 'PTR-005' => 5, 'KBR-002' => 30],
            'supply_requests' => [
                ['ops', 'Formulir dan stiker untuk pool Ciputat', [['OPR-001', 10], ['OPR-002', 40]], 'issued', -11],
                ['cs', 'Kebutuhan bulanan layanan pelanggan', [['ATK-001', 6], ['ATK-003', 20]], 'issued', -7],
                ['hr', 'Perlengkapan training pengemudi baru', [['ATK-005', 30], ['ATK-004', 6], ['PTR-005', 4]], 'issued', -4],
                ['finance', 'ATK tutup buku', [['ATK-006', 8], ['ATK-008', 4]], 'approved', -1],
                ['hse', 'Kebersihan ruang tunggu pengemudi', [['KBR-001', 2], ['KBR-005', 6], ['KBR-002', 24]], 'submitted', 0],
                ['it', 'Toner printer command center', [['CTK-001', 2]], 'submitted', 0],
                ['shuttle', 'Air mineral penumpang shuttle', [['PTR-005', 20]], 'rejected', -3],
            ],
            'documents' => [
                ['Nomor Induk Berusaha (NIB)', 'license', '8120002001001', 'OSS RBA', -1500, null, 60],
                ['Izin Penyelenggaraan Angkutan Taksi', 'license', 'KP.230/AJ.206/2024/101', 'Kementerian Perhubungan', -700, 395, 120],
                ['Izin Angkutan Antarkota Cititrans', 'license', 'KP.230/AJ.206/2024/215', 'Kementerian Perhubungan', -650, 40, 120],
                ['Sertifikat Laik Fungsi (SLF) Gedung Mampang', 'certificate', '644/SLF/DPMPTSP/2021/552', 'DPMPTSP Provinsi DKI Jakarta', -1800, 25, 90],
                ['SILO Lift Gedung No. 1–2', 'certificate', '560/SILO/2025/3311', 'Disnakertransgi DKI Jakarta', -360, -9, 60],
                ['Sertifikat Laik Operasi SPKLU Pool Halim', 'certificate', 'SLO/SPKLU/2024/0912', 'Lembaga Inspeksi Teknik (contoh)', -330, 35, 60],
                ['Persetujuan Teknis Air Limbah Pool Halim', 'license', '660/PERTEK/DLH/2024/298', 'Dinas Lingkungan Hidup DKI Jakarta', -600, 495, 90],
                ['Sertifikat ISO 45001 SMK3', 'certificate', 'ISO45001/2024/BB-01', 'Badan Sertifikasi (contoh)', -500, 600, 120],
                ['Polis Asuransi Gedung & Pool', 'insurance', 'PAR/JKT/2026/01120', 'PT Asuransi Contoh Indonesia', -320, 45, 60],
                ['Polis Asuransi Kendaraan Operasional (6 unit)', 'insurance', 'MV/JKT/2026/03381', 'PT Asuransi Contoh Indonesia', -300, 65, 45],
                ['Kontrak Pemeliharaan SPKLU', 'contract', 'BB/PKS/EV/2025/021', 'PT Energi Contoh Teknik', -340, 25, 60],
                ['Kontrak Jasa Keamanan Pool', 'contract', 'BB/PKS/SEC/2025/014', 'PT Satpam Contoh Sejahtera', -330, 150, 60],
                ['PBB Gedung Mampang', 'tax', 'SPPT 31.74.060.002.015-0060.0', 'Bapenda DKI Jakarta', -150, 70, 30],
                ['STNK Isuzu Elf NMR (mobil derek)', 'tax', 'STNK-7201', 'Samsat Jakarta Timur', -1500, 300, 30, 'B 7201 BBD'],
            ],
        ];
    }
}
