<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Label QR Aset</title>
<style>
@page{size:A4;margin:10mm}
*{box-sizing:border-box}body{font-family:Arial,Helvetica,sans-serif;margin:0;color:#0b2f4f}
.bar{padding:12px;background:#0b3a5c;color:#fff;display:flex;justify-content:space-between;align-items:center}
.bar button{background:#c9963e;color:#fff;border:0;padding:8px 16px;border-radius:6px;font-weight:700;cursor:pointer}
.sheet{display:grid;grid-template-columns:repeat(2,1fr);gap:8mm;padding:8mm}
.label{border:2px solid #0b2f4f;border-radius:12px;padding:5mm;text-align:center;break-inside:avoid;page-break-inside:avoid}
.label .brand{font-size:12px;font-weight:700;letter-spacing:1px}
.label .sub{font-size:8px;margin:2px 0 6px;letter-spacing:.5px}
.label svg{width:42mm;height:42mm}
.label .name{font-size:14px;font-weight:700;margin-top:4px}
.label .meta{font-size:10px;margin-top:2px}
.label .foot{font-size:8px;margin-top:6px;border-top:1px solid #ddd;padding-top:4px;letter-spacing:.5px}
@media print{.bar{display:none}.sheet{padding:0}}
</style>
</head>
<body>
<div class="bar"><span>{{ $assets->count() }} label QR · pakai kertas A4 atau stiker A4, skala 100%</span><button onclick="window.print()">Cetak</button></div>
<div class="sheet">
@foreach ($assets as $asset)
    <div class="label">
        <div class="brand">{{ mb_strtoupper($asset->company->name) }}</div>
        <div class="sub">IDENTIFIKASI ASET &amp; PELAPORAN LEWAT HP</div>
        {!! \App\Support\Qr::svg($asset->qrUrl()) !!}
        <div class="name">{{ $asset->name }}</div>
        <div class="meta"><b>ID Aset:</b> {{ $asset->code }}</div>
        <div class="meta"><b>Lokasi:</b> {{ $asset->location?->complete_name }}</div>
        <div class="foot">PINDAI UNTUK MELIHAT ASET / MELAPORKAN MASALAH</div>
    </div>
@endforeach
</div>
</body>
</html>
