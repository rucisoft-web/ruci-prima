@php($record = $getRecord())
<div class="rac-qr-card">
    <div class="rac-qr-title">QR Asset ID</div>
    <div class="rac-qr-img">{!! \App\Support\Qr::svg($record->qrUrl()) !!}</div>
    <div class="rac-qr-actions">
        <a href="{{ $record->qrUrl() }}" target="_blank" rel="noopener">Buka halaman QR</a>
        <a href="{{ route('qr.labels', ['ids' => $record->id]) }}" target="_blank" rel="noopener">Cetak label</a>
    </div>
    <p class="rac-qr-note">Pindai dari HP untuk melihat aset dan melaporkan kerusakan tanpa login.</p>
</div>
