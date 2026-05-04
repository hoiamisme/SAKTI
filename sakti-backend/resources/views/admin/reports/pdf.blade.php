<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a2e; }

    .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #1a1a2e; padding-bottom: 10px; }
    .header h1 { font-size: 15px; font-weight: bold; letter-spacing: 1px; }
    .header h2 { font-size: 11px; font-weight: normal; margin-top: 2px; color: #444; }
    .header .meta { font-size: 9px; color: #666; margin-top: 6px; }

    .filter-info { background: #f0f4ff; border: 1px solid #c0cce0; border-radius: 4px;
                   padding: 6px 10px; margin-bottom: 12px; font-size: 9px; color: #333; }
    .filter-info span { margin-right: 16px; }

    .stats { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
    .stats td { border: 1px solid #ddd; padding: 5px 8px; text-align: center; font-size: 9px; }
    .stats .lbl { font-weight: bold; background: #f5f5f5; }
    .stat-total   { background: #e8e8e8; }
    .stat-granted { background: #d4edda; color: #155724; }
    .stat-denied  { background: #f8d7da; color: #721c24; }
    .stat-face    { background: #fff3cd; color: #856404; }
    .stat-rfid    { background: #e2e3e5; color: #383d41; }

    table.logs { width: 100%; border-collapse: collapse; }
    table.logs thead tr { background: #1a1a2e; color: #fff; }
    table.logs thead th { padding: 5px 6px; text-align: left; font-size: 9px; font-weight: bold; }
    table.logs tbody tr:nth-child(even) { background: #f9f9f9; }
    table.logs tbody tr.row-denied  { background: #ffe8e8; }
    table.logs tbody tr.row-mismatch { background: #fffbe6; }
    table.logs tbody td { padding: 4px 6px; font-size: 9px; border-bottom: 1px solid #e8e8e8; vertical-align: middle; }
    .snap-img { width: 60px; height: 45px; object-fit: cover; border-radius: 2px; border: 1px solid #ccc; }
    .snap-none { color: #bbb; font-size: 8px; }

    .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 8px; font-weight: bold; }
    .b-granted  { background: #198754; color: #fff; }
    .b-denied   { background: #dc3545; color: #fff; }
    .b-mismatch { background: #fd7e14; color: #fff; }
    .b-rfid     { background: #6c757d; color: #fff; }
    .b-masuk    { background: #0d6efd; color: #fff; }
    .b-keluar   { background: #ffc107; color: #000; }

    .footer { text-align: center; font-size: 8px; color: #888; margin-top: 16px;
              border-top: 1px solid #ddd; padding-top: 8px; }
</style>
</head>
<body>

<div class="header">
    <h1>SISTEM AKSES KSATRIAN TERPADU (SAKTI)</h1>
    <h2>UNHAN RI — Laporan Log Akses</h2>
    <div class="meta">
        Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB &nbsp;|&nbsp;
        Total data: {{ $stats['total'] }} entri
    </div>
</div>

{{-- Filter aktif --}}
<div class="filter-info">
    <span><strong>Periode:</strong>
        {{ $dari  ? \Carbon\Carbon::parse($dari)->format('d/m/Y')  : 'Semua' }}
        – {{ $sampai ? \Carbon\Carbon::parse($sampai)->format('d/m/Y') : 'Semua' }}
    </span>
    <span><strong>Lokasi:</strong> {{ $lokasiNama }}</span>
    <span><strong>Status:</strong> {{ $statusLabel }}</span>
</div>

{{-- Ringkasan statistik --}}
<table class="stats">
    <tr>
        <td class="lbl">Total</td>
        <td class="lbl">Diberikan</td>
        <td class="lbl">Ditolak</td>
        <td class="lbl">Wajah ≠</td>
        <td class="lbl">RFID Tak Dikenal</td>
    </tr>
    <tr>
        <td class="stat-total"><strong>{{ $stats['total'] }}</strong></td>
        <td class="stat-granted">{{ $stats['granted'] }}</td>
        <td class="stat-denied">{{ $stats['denied'] }}</td>
        <td class="stat-face">{{ $stats['face_mismatch'] }}</td>
        <td class="stat-rfid">{{ $stats['rfid_unknown'] }}</td>
    </tr>
</table>

{{-- Tabel data --}}
<table class="logs">
    <thead>
        <tr>
            <th style="width:4%">No</th>
            <th style="width:11%">Waktu</th>
            <th style="width:16%">Nama Kadet</th>
            <th style="width:9%">NIM</th>
            <th style="width:14%">Lokasi</th>
            <th style="width:8%">Status</th>
            <th style="width:7%">Arah</th>
            <th style="width:8%">Foto</th>
            <th style="width:23%">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($logs as $i => $log)
            @php
                $rowClass = match($log->status) {
                    'denied'        => 'row-denied',
                    'face_mismatch' => 'row-mismatch',
                    default         => '',
                };
                [$badgeClass, $badgeLabel] = match($log->status) {
                    'granted'       => ['b-granted',  'DIBERIKAN'],
                    'denied'        => ['b-denied',   'DITOLAK'],
                    'face_mismatch' => ['b-mismatch', 'WAJAH ≠'],
                    'rfid_unknown'  => ['b-rfid',     'RFID ≠'],
                    default         => ['b-rfid',      strtoupper($log->status)],
                };
                $imgSrc = $snapBase64[$log->id] ?? null;
            @endphp
            <tr class="{{ $rowClass }}">
                <td>{{ $i + 1 }}</td>
                <td>{{ $log->waktu_akses?->format('d/m/Y H:i:s') ?? '-' }}</td>
                <td>{{ $log->kadet?->nama_lengkap ?? 'Tidak Dikenal' }}</td>
                <td>{{ $log->kadet?->nim ?? '-' }}</td>
                <td>{{ $log->location?->nama_lokasi ?? '-' }}</td>
                <td><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>
                <td>
                    @if($log->direction === 'masuk')
                        <span class="badge b-masuk">MASUK</span>
                    @elseif($log->direction === 'keluar')
                        <span class="badge b-keluar">KELUAR</span>
                    @else
                        <span style="color:#bbb;">—</span>
                    @endif
                </td>
                <td>
                    @if($imgSrc)
                        <img src="{{ $imgSrc }}" class="snap-img">
                    @else
                        <span class="snap-none">— tidak ada —</span>
                    @endif
                </td>
                <td>{{ \Illuminate\Support\Str::limit($log->keterangan, 60) ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" style="text-align:center; padding:12px; color:#888;">
                    Tidak ada data untuk filter yang dipilih.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    Laporan ini dibuat secara otomatis oleh Sistem SAKTI — KSATRIAN UNHAN RI
</div>

</body>
</html>
