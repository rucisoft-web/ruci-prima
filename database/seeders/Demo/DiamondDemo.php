<?php

namespace Database\Seeders\Demo;

use App\Enums\AssignmentState;
use App\Enums\Role;

/**
 * Diamond Food Indonesia: produsen dan distributor makanan-minuman dengan rantai dingin nasional.
 * Profil umum mengacu pada Laporan Tahunan 2025 PT Diamond Food Indonesia Tbk;
 * data aset, transaksi, nama orang, dan nomor dokumen adalah ilustrasi untuk demo.
 */
class DiamondDemo extends DemoCompany
{
    protected function profile(): array
    {
        $depots = [
            'cikarang' => 'DC Cikarang', 'medan' => 'Gudang Medan', 'surabaya' => 'Cabang Surabaya', 'denpasar' => 'Cabang Denpasar',
            'malang' => 'Cabang Malang', 'banyuwangi' => 'Titik Distribusi Banyuwangi', 'makassar' => 'Cabang Makassar',
        ];
        $locations = [
            ['hq', 'Kantor Pusat TCC Batavia Tower One Lt. 15', null],
            ['mm2100', 'Kawasan Industri MM2100, Cikarang Barat', null],
            ['dairy', 'Pabrik Dairy & UHT', 'mm2100'],
            ['icecream', 'Pabrik Es Krim', 'mm2100'],
            ['bakery', 'Pabrik Bakery (DCS Bakery Division)', 'mm2100'],
            ['utility', 'Utilitas Pabrik (boiler, amonia, IPAL)', 'mm2100'],
            ['irian3', 'Proyek Gudang Baru Irian 3', 'mm2100'],
            ['cimahi', 'Pabrik Cimahi', null],
        ];
        $depotKeys = [];
        foreach ($depots as $key => $name) {
            $locations[] = [$key, $name, null];
            $depotKeys[] = $key;
        }

        return [
            'seed' => 1974,
            'domain' => 'diamond.demo',
            'company' => [
                'name' => 'PT Diamond Food Indonesia Tbk',
                'code' => 'DMND',
                'slug' => 'diamond',
                'industry' => 'Makanan & Minuman, Distribusi Rantai Dingin',
                'city' => 'Jakarta',
                'address' => 'TCC Batavia Tower One Lt. 15, Jl. KH Mas Mansyur Kav. 126, Jakarta Pusat (data operasional ilustrasi untuk demo)',
            ],
            'users' => [
                'manajer' => ['Hendra Gunawan', 'manajer', Role::Manajer, 'Plant Engineering Manager'],
                'teknisi' => ['Fajar Nugroho', 'teknisi', Role::Staf, 'Teknisi Refrigerasi'],
                'supervisor' => ['Lestari Wulandari', 'supervisor', Role::Staf, 'Supervisor Gudang Cold Chain'],
                'umum' => ['Nadia Kusuma', 'umum', Role::Staf, 'Staf General Affairs'],
                'direktur' => ['Andreas Wijaya', 'direktur', Role::Eksekutif, 'Direktur Supply Chain'],
            ],
            'departments' => [
                'dairy' => 'Produksi Dairy & UHT', 'icecream' => 'Produksi Es Krim', 'bakery' => 'Produksi Bakery', 'engineering' => 'Engineering & Utilitas',
                'qa' => 'Quality Assurance', 'warehouse' => 'Gudang & Cold Chain', 'distribution' => 'Distribusi', 'it' => 'Teknologi Informasi',
                'finance' => 'Keuangan', 'hr' => 'HR & General Affairs',
            ],
            'categories' => [
                'line' => ['Lini Produksi', 15, 'critical'],
                'refrig' => ['Refrigerasi & Cold Room', 15, 'critical'],
                'util' => ['Boiler, Kompresor & Utilitas', 20, 'critical'],
                'elec' => ['Kelistrikan & Genset', 20, 'critical'],
                'mhe' => ['Forklift & Material Handling', 8, 'high'],
                'rack' => ['Racking & Dock', 15, 'medium'],
                'lab' => ['Peralatan Laboratorium QA', 8, 'high'],
                'env' => ['IPAL & Lingkungan', 15, 'high'],
                'fire' => ['Proteksi Kebakaran', 15, 'critical'],
                'it' => ['TI & Monitoring Suhu', 5, 'high'],
                'sec' => ['Keamanan & CCTV', 7, 'medium'],
            ],
            'unit_costs' => ['refrig' => [180, 650], 'mhe' => [220, 480], 'rack' => [60, 180], 'elec' => [150, 380], 'it' => [5, 18], 'fire' => [1, 2], 'sec' => [8, 20]],
            'cost_scale' => 1.8,
            'locations' => $locations,
            'survey' => ['name' => 'Sensus Aset Titik Distribusi 2026', 'note' => 'Verifikasi cold room, forklift, dan genset di cabang Sukanda Djaya.'],
            'templates' => [
                [
                    'name' => 'Titik Distribusi Cold Chain', 'location_type' => 'boh', 'note' => 'Standar perlengkapan cabang distribusi berpendingin.',
                    'lines' => [
                        ['Cold room beku -25°C', 'refrig', 1, 'warehouse', 'Bitzer', 'CR-25'],
                        ['Chiller room 2–8°C', 'refrig', 1, 'warehouse', 'Bitzer', 'CR-04'],
                        ['Reach truck elektrik', 'mhe', 2, 'warehouse', 'Toyota', 'BT Reflex'],
                        ['Selective racking', 'rack', 1, 'warehouse', 'Lysaght', 'SR-5L'],
                        ['Dock leveler & seal', 'rack', 2, 'distribution', 'Hörmann', 'HLS 2'],
                        ['Genset 250 kVA', 'elec', 1, 'engineering', 'Perkins', '1306C'],
                        ['Data logger suhu IoT', 'it', 4, 'qa', 'Testo', 'Saveris 2'],
                        ['APAR CO2 6 kg', 'fire', 4, 'hr', 'Yamato', 'C-6'],
                    ],
                    'locations' => $depotKeys,
                    'teams' => [
                        ['cikarang', 'Tim DC Cikarang', AssignmentState::Done], ['medan', 'Tim Sumatra', AssignmentState::Done],
                        ['surabaya', 'Tim Jawa Timur', AssignmentState::Done], ['denpasar', 'Tim Bali', AssignmentState::InProgress],
                        ['makassar', 'Tim Sulawesi', AssignmentState::Assigned],
                    ],
                ],
            ],
            'additional' => [
                ['surabaya', 'Hand pallet elektrik tambahan', 'mhe', 'warehouse'],
                ['denpasar', 'Freezer display pinjaman pelanggan', 'refrig', 'distribution'],
                ['banyuwangi', 'Genset portabel 30 kVA', 'elec', 'engineering'],
            ],
            'major' => [
                ['Lini UHT aseptik #1', 'line', 'dairy', 'dairy', 'Tetra Pak', 'A3/Flex', 2014, 38000, 'fair', [3, 5, 5, 5, 5]],
                ['Lini UHT aseptik #2', 'line', 'dairy', 'dairy', 'Tetra Pak', 'A3/Speed', 2021, 46000, 'excellent', [3, 5, 5, 5, 5]],
                ['Pasteurizer & homogenizer', 'line', 'dairy', 'dairy', 'GEA', 'Ariete 3110', 2013, 9500, 'poor', [4, 5, 5, 5, 4]],
                ['Freezer kontinu es krim #1', 'line', 'icecream', 'icecream', 'Tetra Pak', 'Hoyer Frigus', 2012, 12500, 'poor', [3, 4, 5, 5, 5]],
                ['Freezer kontinu es krim #2', 'line', 'icecream', 'icecream', 'Tetra Pak', 'Hoyer Frigus', 2019, 14000, 'good', [3, 4, 5, 5, 5]],
                ['Mesin filling cup & cone', 'line', 'icecream', 'icecream', 'Gram', 'RIA 3000', 2016, 6800, 'fair', [2, 4, 4, 5, 4]],
                ['Spiral freezer bakery', 'line', 'bakery', 'bakery', 'JBT', 'GYRoCOMPACT', 2018, 7200, 'good', [3, 3, 4, 5, 4]],
                ['Oven tunnel bakery', 'line', 'bakery', 'bakery', 'Mecatherm', 'M-TA', 2017, 8400, 'fair', [4, 3, 4, 5, 4]],
                ['Lini produksi Cimahi', 'line', 'cimahi', 'dairy', 'Krones', 'Modulfill', 2015, 11000, 'fair', [3, 4, 4, 5, 4]],
                ['Kompresor amonia #1', 'refrig', 'utility', 'engineering', 'Mycom', '250VLD', 2012, 4200, 'critical', [5, 4, 5, 5, 5]],
                ['Kompresor amonia #2', 'refrig', 'utility', 'engineering', 'Mycom', '250VLD', 2018, 4500, 'good', [5, 4, 5, 5, 5]],
                ['Cold storage hardening -30°C', 'refrig', 'icecream', 'warehouse', 'Bitzer', 'HS-30', 2012, 5600, 'poor', [3, 4, 5, 5, 4]],
                ['Boiler uap 10 ton/jam', 'util', 'utility', 'engineering', 'Loos', 'UL-S', 2013, 6200, 'fair', [5, 3, 5, 5, 5]],
                ['Kompresor udara bebas oli', 'util', 'utility', 'engineering', 'Atlas Copco', 'ZR 160', 2016, 2100, 'fair', [3, 3, 4, 5, 3]],
                ['Cooling tower pabrik', 'util', 'utility', 'engineering', 'Baltimore', 'S3E', 2013, 1400, 'poor', [3, 2, 4, 4, 3]],
                ['Trafo 3000 kVA', 'elec', 'utility', 'engineering', 'Schneider', 'Trihal', 2012, 2600, 'good', [5, 3, 5, 5, 5]],
                ['Genset pabrik 2000 kVA', 'elec', 'utility', 'engineering', 'Caterpillar', '3516', 2012, 5200, 'fair', [5, 3, 5, 5, 5]],
                ['IPAL pabrik 500 m³/hari', 'env', 'utility', 'engineering', 'Biotech', 'WWTP 500', 2013, 4800, 'poor', [3, 2, 3, 5, 4]],
                ['Lab mikrobiologi & HPLC', 'lab', 'dairy', 'qa', 'Agilent', '1260 Infinity II', 2020, 1800, 'good', [3, 4, 4, 4, 3]],
                ['Fire hydrant & sprinkler pabrik', 'fire', 'mm2100', 'hr', 'Ebara', 'FSA 1000 GPM', 2012, 1600, 'fair', [5, 3, 3, 4, 4]],
                ['Gudang otomatis ASRS DC Cikarang', 'rack', 'cikarang', 'warehouse', 'Daifuku', 'ASRS', 2022, 24000, 'excellent', [3, 3, 5, 5, 5]],
                ['Sistem WMS & monitoring suhu terpusat', 'it', 'hq', 'it', 'SAP', 'EWM', 2022, 3200, 'good', [2, 4, 5, 5, 4]],
                ['Server data center & ERP', 'it', 'hq', 'it', 'HPE', 'ProLiant DL380', 2021, 1450, 'good', [1, 3, 5, 5, 3]],
                ['NVR CCTV pabrik 128 kanal', 'sec', 'mm2100', 'hr', 'Hikvision', 'DS-96128NI', 2019, 150, 'fair', [4, 2, 2, 3, 2]],
            ],
            'pm_frequency' => ['line' => 'monthly', 'refrig' => 'monthly', 'util' => 'monthly', 'elec' => 'monthly', 'mhe' => 'monthly', 'rack' => 'semiannual', 'lab' => 'quarterly', 'env' => 'monthly', 'fire' => 'quarterly', 'it' => 'quarterly', 'sec' => 'semiannual'],
            'meter' => ['util', 'jam'],
            'wo' => ['preventive' => [14, 20], 'corrective' => [6, 12], 'emergency' => [1, 4]],
            'corrective' => ['Suhu cold room naik di atas -18°C', 'Kebocoran amonia kecil', 'Seal filling bocor', 'Motor conveyor overheat', 'Sensor suhu tidak terbaca', 'Baterai reach truck drop', 'Tekanan boiler tidak stabil', 'Panel PLC error'],
            'complaints' => ['Pintu cold room tidak menutup rapat', 'Es menumpuk di evaporator', 'Reach truck tidak bisa angkat', 'Dock leveler macet', 'Alarm suhu berbunyi', 'Lampu gudang mati', 'Genset cabang tidak otomatis menyala', 'Data logger offline'],
            'reporters' => ['Operator gudang', 'Kepala cabang', 'QA shift malam', 'Satpam', null],
            'risks' => [
                ['Kompresor amonia #1 gagal, produk beku rusak', 'Kompresor amonia #1', 4, 5, 'mitigating', 'Overhaul; siapkan kompresor cadangan sewa.'],
                ['Kebocoran amonia di ruang mesin', 'Kompresor amonia #2', 2, 5, 'mitigating', 'Detektor gas, latihan tanggap darurat, APD.'],
                ['Freezer es krim #1 berhenti saat musim puncak', 'Freezer kontinu es krim #1', 4, 4, 'open', 'Ganti dasher dan bearing; rencanakan penggantian.'],
                ['Pasteurizer gagal mencapai suhu, produk ditahan QA', 'Pasteurizer & homogenizer', 3, 5, 'open', 'Ganti plat heat exchanger.'],
                ['Cold storage hardening tidak mencapai -30°C', 'Cold storage hardening -30°C', 3, 4, 'open', 'Ganti panel insulasi dan evaporator.'],
                ['IPAL melebihi baku mutu', 'IPAL pabrik 500 m³/hari', 3, 4, 'open', 'Tambah unit DAF dan aerasi.'],
                ['Boiler trip, produksi UHT berhenti', 'Boiler uap 10 ton/jam', 2, 5, 'mitigating', 'Water treatment dan inspeksi tahunan Disnaker.'],
                ['Genset pabrik gagal start saat PLN padam', 'Genset pabrik 2000 kVA', 2, 5, 'mitigating', 'Uji beban bulanan.'],
                ['Cooling tower korosi, efisiensi turun', 'Cooling tower pabrik', 4, 3, 'open', 'Penggantian fill dan pengecatan.'],
                ['Data suhu rantai dingin hilang saat audit', 'Sistem WMS & monitoring suhu terpusat', 1, 4, 'mitigating', 'Backup cloud dan alarm SMS.'],
                ['CCTV pabrik tidak merekam', 'NVR CCTV pabrik 128 kanal', 2, 2, 'closed', 'HDD diganti.'],
            ],
            'capex' => [
                ['Pembangunan gudang dry storage Irian 3 (±3 ha)', null, 'revenue', 'replace', 165000, [3, 2, 5, 4], 'approved', 'Kapasitas gudang kering penuh; groundbreaking Agustus 2025, target selesai akhir 2026.'],
                ['Penggantian freezer kontinu es krim #1', 'Freezer kontinu es krim #1', 'revenue', 'replace', 15500, [3, 3, 5, 5], 'proposed', 'Downtime tinggi saat musim puncak; biaya perbaikan 12 bulan melampaui 15% nilai ganti.'],
                ['Overhaul kompresor amonia #1', 'Kompresor amonia #1', 'safety', 'overhaul', 1900, [3, 5, 4, 5], 'approved', 'Risiko kritis keselamatan dan kerugian produk beku.'],
                ['Revitalisasi IPAL pabrik', 'IPAL pabrik 500 m³/hari', 'mandatory', 'overhaul', 3800, [1, 3, 2, 5], 'proposed', 'Kewajiban baku mutu dan pelaporan PROPER.'],
                ['Penggantian plat heat exchanger pasteurizer', 'Pasteurizer & homogenizer', 'mandatory', 'overhaul', 1350, [4, 4, 4, 4], 'proposed', 'Keamanan pangan; hasil audit QA.'],
                ['Panel surya atap pabrik 2 MWp', null, 'efficiency', 'replace', 24000, [2, 1, 3, 2], 'draft', 'Menekan biaya energi refrigerasi siang hari.'],
                ['Penambahan 40 truk berpendingin', null, 'revenue', 'replace', 36000, [4, 2, 5, 3], 'draft', 'Mendukung titik distribusi baru (Banyuwangi) dan pertumbuhan food service.'],
                ['Retrofit LED gudang', null, 'efficiency', 'replace', 950, [2, 2, 1, 2], 'done', 'Penghematan listrik dan panas berkurang di cold room.'],
                ['Upgrade CCTV analitik', 'NVR CCTV pabrik 128 kanal', 'safety', 'replace', 600, [2, 3, 1, 2], 'rejected', 'Ditunda ke tahun berikutnya.'],
            ],
            'rooms' => [
                ['Boardroom Kantor Pusat', 20, 'Layar 98", video conference', 'hq'],
                ['Ruang Rapat Supply Chain', 12, 'Proyektor, papan tulis', 'hq'],
                ['Ruang Training Pabrik MM2100', 36, 'Proyektor, sound system', 'mm2100'],
                ['Ruang Diskusi QA', 8, 'TV 55", papan tulis', 'dairy'],
            ],
            'agendas' => [
                ['Rapat S&OP bulanan', 'distribution'], ['Review OEE lini produksi', 'dairy'], ['Audit internal HACCP', 'qa'],
                ['Rapat persiapan musim puncak es krim', 'icecream'], ['Review biaya energi pabrik', 'engineering'], ['Evaluasi kinerja cabang', 'warehouse'],
                ['Training food safety karyawan baru', 'hr'], ['Review anggaran bulanan', 'finance'], ['Rapat proyek gudang Irian 3', 'engineering'],
                ['Presentasi vendor WMS', 'it'],
            ],
            'vehicles' => [
                ['B 1974 DFI', 'Toyota Fortuner 2.8 VRZ (direksi)', 'car', 2023, 'Pertamina Dex', 7, 41_210, 'Sugeng Riyanto', 90, 1000, null, 60, 22, 45_000],
                ['B 9301 DFI', 'Isuzu Elf NMR berpendingin (cadangan cabang)', 'pickup', 2020, 'Solar', 3, 186_430, 'Kurniawan', 25, 450, 12, 140, 9, 190_000],
                ['B 9302 DFI', 'Mitsubishi Canter berpendingin', 'pickup', 2018, 'Solar', 3, 248_870, 'Budi Santoso', -3, 200, 55, 75, 30, 250_000],
                ['B 2205 DFI', 'Toyota Avanza 1.5 G (sales food service)', 'car', 2021, 'Pertalite', 7, 96_270, null, 150, 700, null, -7, 40, 100_000],
                ['B 2206 DFI', 'Toyota Hiace Commuter (antar-jemput shift)', 'minibus', 2019, 'Dexlite', 15, 158_120, 'Supriyadi', 60, 300, 20, 200, -4, 160_000],
                ['B 4407 DFI', 'Yamaha NMAX (kurir)', 'motorcycle', 2022, 'Pertalite', 2, 26_430, null, 220, 900, null, null, 45, 28_000],
            ],
            'stations' => ['SPBU 34.175.01 MM2100', 'SPBU 34.171.07 Cibitung', 'SPBU 31.102.03 Mas Mansyur', 'SPBU 34.175.12 Jababeka'],
            'workshops' => ['default' => 'Isuzu Astra Cikarang', 'car' => 'Auto2000 Cikarang', 'minibus' => 'Auto2000 Cikarang', 'motorcycle' => 'Yamaha Cikarang'],
            'trips' => [
                ['Antar direksi kunjungan pabrik', 'MM2100 Cikarang', 'finance', 3],
                ['Kirim sampel produk ke laboratorium eksternal', 'Bogor', 'qa', 1],
                ['Survei titik distribusi baru', 'Karawang', 'distribution', 3],
                ['Kunjungan pelanggan hotel & restoran', 'Kemang', 'distribution', 2],
                ['Ambil suku cadang kompresor', 'Jababeka', 'engineering', 2],
                ['Audit gudang cabang', 'DC Cikarang', 'warehouse', 3],
                ['Antar-jemput karyawan shift malam', 'Cikarang Barat', 'hr', 12],
                ['Setor dokumen pajak', 'KPP Wajib Pajak Besar', 'finance', 1],
            ],
            'supplies' => $this->officeSupplies([
                ['APD-001', 'Jaket thermal cold storage', 'other', 'pcs', 30, 10, 385_000, 6],
                ['APD-002', 'Sarung tangan thermal', 'other', 'pasang', 80, 30, 45_000, 28],
                ['APD-003', 'Hairnet & masker sekali pakai', 'other', 'box', 60, 20, 65_000, 24],
                ['KBR-005', 'Sanitizer food grade 5 L', 'cleaning', 'galon', 20, 8, 210_000, 7],
            ]),
            'storage' => ['default' => 'Gudang GA Pabrik MM2100', 'pantry' => 'Pantry kantor pusat'],
            'supply_users' => ['Produksi Dairy', 'Produksi Es Krim', 'Gudang Cold Chain', 'QA', 'Kantor Pusat', 'Distribusi'],
            'low_stock' => ['APD-002' => 12, 'APD-003' => 9, 'CTK-001' => 2, 'PTR-004' => 6],
            'supply_requests' => [
                ['warehouse', 'APD cold storage untuk shift baru', [['APD-001', 6], ['APD-002', 20]], 'issued', -10],
                ['qa', 'Hairnet & masker area produksi', [['APD-003', 12], ['KBR-005', 3]], 'issued', -6],
                ['hr', 'ATK training food safety', [['ATK-005', 30], ['ATK-003', 30], ['ATK-001', 4]], 'issued', -4],
                ['icecream', 'APD musim puncak es krim', [['APD-002', 16], ['APD-001', 4]], 'approved', -1],
                ['finance', 'ATK tutup buku', [['ATK-006', 8], ['ATK-008', 4]], 'submitted', 0],
                ['distribution', 'Formulir dan toner cabang', [['CTK-001', 2], ['ATK-002', 4]], 'submitted', 0],
                ['it', 'Kopi untuk rapat vendor', [['PTR-001', 3]], 'rejected', -3],
            ],
            'documents' => [
                ['Nomor Induk Berusaha (NIB)', 'license', '8120001995001', 'OSS RBA', -1500, null, 60],
                ['Izin Edar BPOM produk UHT', 'license', 'BPOM MD 2050xxxx', 'Badan POM', -900, 195, 120],
                ['Sertifikat Halal produk dairy & es krim', 'certificate', 'ID32160000197410224', 'BPJPH', -600, 40, 120],
                ['Sertifikat FSSC 22000 Pabrik MM2100', 'certificate', 'FSSC/2024/DFI-01', 'Badan Sertifikasi (contoh)', -500, 230, 120],
                ['Sertifikat CPPOB Pabrik Cimahi', 'certificate', 'CPPOB/2023/CMH-07', 'Badan POM', -800, -15, 90],
                ['Pengesahan Pemakaian Bejana Tekan (boiler)', 'certificate', '560/PAA/2025/1189', 'Disnakertrans Jawa Barat', -350, 15, 60],
                ['SILO Forklift & Reach Truck DC Cikarang', 'certificate', '560/SILO/2025/4410', 'Disnakertrans Jawa Barat', -300, 65, 60],
                ['Persetujuan Teknis Air Limbah Pabrik', 'license', '660/PERTEK/DLH/2024/411', 'DLH Kabupaten Bekasi', -600, 495, 90],
                ['Polis Asuransi Properti & Stok', 'insurance', 'PAR/BKS/2026/02210', 'PT Asuransi Contoh Indonesia', -320, 45, 60],
                ['Polis Asuransi Kendaraan (6 unit)', 'insurance', 'MV/BKS/2026/04112', 'PT Asuransi Contoh Indonesia', -300, 65, 45],
                ['Kontrak Pemeliharaan Refrigerasi Amonia', 'contract', 'DFI/PKS/ENG/2025/031', 'PT Refrigerasi Contoh Teknik', -330, 35, 60],
                ['Kontrak Sewa Gudang Makassar', 'contract', 'DFI/SWA/2024/007', 'PT Pergudangan Contoh Timur', -600, 130, 90],
                ['PBB Pabrik MM2100', 'tax', 'SPPT 32.16.110.004.012-0126.0', 'Bapenda Kabupaten Bekasi', -150, 70, 30],
            ],
        ];
    }
}
