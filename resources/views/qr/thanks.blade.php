@extends('qr.layout')
@section('title', 'Laporan terkirim')
@section('content')
<div class="card" style="border-radius:16px;text-align:center;margin-top:40px">
    <div style="font-size:48px;color:#2e7d32">✓</div>
    <h2>Laporan terkirim</h2>
    <p>Permintaan pemeliharaan <b>{{ $request->number }}</b> telah dibuat untuk <b>{{ $asset->name }}</b>. Tim pemeliharaan akan menindaklanjuti.</p>
    <a class="btn navy" href="{{ route('qr.show', $token) }}">Kembali ke aset</a>
</div>
@endsection
