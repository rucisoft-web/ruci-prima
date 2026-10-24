@extends('qr.layout')
@section('title', $asset->code)
@section('content')
<div class="head">
    <div class="brand">{{ $asset->company->name }} · Ruci PRIMA</div>
    <h1>{{ $asset->name }}</h1>
    <div>{{ $asset->code }} • {{ $asset->location?->complete_name }}</div>
</div>
<div class="card">
    <div class="grid">
        <div><small>Kesehatan aset</small><div class="score">{{ number_format($asset->health_score, 0) }}</div></div>
        <div><small>Status</small>
            <p>
                <span class="badge {{ $asset->status->getColor() }}">{{ $asset->status->getLabel() }}</span>
                <span class="badge {{ $asset->condition->getColor() }}">Kondisi: {{ $asset->condition->getLabel() }}</span>
                <span class="badge {{ $asset->criticality->getColor() }}">Kekritisan: {{ $asset->criticality->getLabel() }}</span>
            </p>
        </div>
    </div>
    <small>{{ $asset->category?->name }}@if($asset->brand) · {{ $asset->brand }} {{ $asset->model }}@endif</small>
    <hr>
    <h3 style="margin:0">Laporkan masalah</h3>
    <p><small>Aset dan lokasi sudah teridentifikasi otomatis dari kode QR.</small></p>
    @if ($errors->any())
        <div class="err">{{ $errors->first() }}</div>
    @endif
    <form action="{{ route('qr.report', $token) }}" method="post" enctype="multipart/form-data">
        @csrf
        <label for="request_type">Jenis masalah</label>
        <select id="request_type" name="request_type">
            <option value="corrective">Kerusakan / perlu perbaikan</option>
            <option value="emergency">Darurat / mendesak</option>
            <option value="inspection">Temuan pemeriksaan</option>
        </select>
        <label for="priority">Prioritas</label>
        <select id="priority" name="priority">
            <option value="medium">Sedang</option><option value="low">Rendah</option><option value="high">Tinggi</option><option value="urgent">Mendesak</option>
        </select>
        <label for="description">Deskripsi masalah</label>
        <textarea id="description" name="description" rows="5" required minlength="5" maxlength="2000" placeholder="Contoh: AC tidak dingin, terdengar suara tidak normal...">{{ old('description') }}</textarea>
        <div class="grid">
            <div><label for="reporter_name">Nama pelapor (opsional)</label><input id="reporter_name" name="reporter_name" maxlength="100" value="{{ old('reporter_name') }}"></div>
            <div><label for="reporter_contact">No. HP / kamar (opsional)</label><input id="reporter_contact" name="reporter_contact" maxlength="100" value="{{ old('reporter_contact') }}"></div>
        </div>
        <label for="photo">Foto (opsional)</label>
        <input id="photo" type="file" name="photo" accept="image/*" capture="environment">
        <input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
        <button type="submit">Kirim laporan</button>
    </form>
</div>
@endsection
