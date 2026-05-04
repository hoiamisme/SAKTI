{{--
    File   : resources/views/layouts/penjaga.blade.php
    Fungsi : Layout utama halaman penjaga pos — sidebar monitoring
    Author : SAKTI Dev Team
    Date   : 2026-05-01
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — SAKTI Penjaga</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --sb-bg: #1a1a2e;
            --sb-hover: #16213e;
            --sb-active: #0f3460;
            --accent: #e94560;
            --sb-text: #a8b0c8;
            --sb-width: 250px;
        }
        html, body { height: 100%; }
        body { background: #f0f2f5; font-size: 0.9rem; }

        .sakti-sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sb-width);
            background: var(--sb-bg);
            display: flex; flex-direction: column;
            z-index: 1040; overflow-y: auto;
        }
        .sb-brand {
            padding: 1.25rem 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
            flex-shrink: 0;
        }
        .sb-brand .brand-title {
            color: var(--accent); font-weight: 700;
            font-size: 1.25rem; letter-spacing: 3px; margin: 0;
        }
        .sb-brand .brand-sub { color: var(--sb-text); font-size: 0.68rem; }

        .sb-nav { flex: 1; padding: 0.75rem 0; }
        .sb-section {
            color: rgba(255,255,255,.3);
            font-size: .65rem; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase;
            padding: .9rem 1.25rem .3rem;
        }
        .sb-link {
            display: flex; align-items: center; gap: .7rem;
            padding: .65rem 1.25rem;
            color: var(--sb-text); text-decoration: none;
            font-size: .875rem;
            border-left: 3px solid transparent;
            transition: background .15s, color .15s;
        }
        .sb-link:hover { background: var(--sb-hover); color: #fff; }
        .sb-link.active { background: var(--sb-active); color: #fff; border-left-color: var(--accent); }
        .sb-link i { width: 20px; text-align: center; font-size: 1rem; }

        .sb-user {
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(255,255,255,.08);
            flex-shrink: 0;
        }
        .sb-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--accent);
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }

        .main-wrapper {
            margin-left: var(--sb-width);
            min-height: 100vh;
            display: flex; flex-direction: column;
        }
        .topbar {
            background: #fff; padding: .7rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 999;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .topbar-title { font-weight: 600; color: #1a1a2e; font-size: .95rem; }
        .page-body { padding: 1.5rem; flex: 1; }

        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,.06); }
        .card-header {
            background: #fff; border-bottom: 1px solid #f0f0f0;
            border-radius: 12px 12px 0 0 !important;
            padding: .9rem 1.25rem; font-weight: 600; font-size: .9rem;
        }
        .stat-card {
            border-radius: 12px; padding: 1.25rem 1.5rem;
            color: #fff; position: relative; overflow: hidden;
        }
        .stat-card .stat-icon {
            position: absolute; right: 1rem; top: 50%;
            transform: translateY(-50%);
            font-size: 3rem; opacity: .15;
        }
        .stat-card .stat-num { font-size: 2rem; font-weight: 700; line-height: 1; }
        .stat-card .stat-label { font-size: .75rem; opacity: .85; margin-top: .25rem; }

        .badge-granted      { background: #198754; }
        .badge-denied       { background: #dc3545; }
        .badge-face_mismatch{ background: #fd7e14; }
        .badge-rfid_unknown { background: #6c757d; }
        .table thead th { font-size: .78rem; font-weight: 700; text-transform: uppercase; color: #6c757d; }

        @media (max-width: 991px) {
            .sakti-sidebar { transform: translateX(-100%); transition: transform .25s; }
            .sakti-sidebar.show { transform: none; }
            .main-wrapper { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>

@php
    /* Hitung peringatan BELUM DIBACA hari ini untuk badge notifikasi */
    $failedToday = \App\Models\AccessLog::whereDate('waktu_akses', today())
        ->whereIn('status', ['denied', 'face_mismatch', 'rfid_unknown'])
        ->where('is_read', false)
        ->when(Auth::user()->location_id, fn ($q) => $q->where('location_id', Auth::user()->location_id))
        ->count();
@endphp

{{-- ======= SIDEBAR ======= --}}
<aside class="sakti-sidebar">
    <div class="sb-brand">
        <p class="brand-title"><i class="bi bi-shield-lock-fill me-1"></i>SAKTI</p>
        <span class="brand-sub">Panel Penjaga Pos<br>KSATRIAN UNHAN RI</span>
    </div>

    <nav class="sb-nav">
        <span class="sb-section">Menu Penjaga</span>

        <a href="{{ route('penjaga.dashboard') }}"
           class="sb-link {{ request()->routeIs('penjaga.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <a href="{{ route('penjaga.scan.index') }}"
           class="sb-link {{ request()->routeIs('penjaga.scan.*') ? 'active' : '' }}">
            <i class="bi bi-upc-scan"></i> SCAN RFID
        </a>

        <a href="{{ route('penjaga.kadets.index') }}"
           class="sb-link {{ request()->routeIs('penjaga.kadets.*') ? 'active' : '' }}">
            <i class="bi bi-person-badge"></i> Data Kadet
        </a>

        <a href="{{ route('penjaga.warnings.index') }}"
           class="sb-link {{ request()->routeIs('penjaga.warnings.*') ? 'active' : '' }}">
            <i class="bi bi-exclamation-triangle"></i>
            Peringatan
            @if($failedToday > 0)
                <span class="badge rounded-pill ms-auto" style="background:var(--accent);font-size:.7rem;">
                    {{ $failedToday > 99 ? '99+' : $failedToday }}
                </span>
            @endif
        </a>
    </nav>

    <div class="sb-user">
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="sb-avatar">
                <i class="bi bi-person-fill text-white" style="font-size:.9rem;"></i>
            </div>
            <div class="overflow-hidden">
                <div class="text-white fw-semibold text-truncate" style="font-size:.82rem;">
                    {{ Auth::user()->name }}
                </div>
                <div class="text-muted" style="font-size:.7rem;">Penjaga Pos</div>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-sm w-100"
                    style="background:rgba(233,69,96,.15);color:var(--accent);border:1px solid rgba(233,69,96,.3);">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </button>
        </form>
    </div>
</aside>

{{-- ======= MAIN WRAPPER ======= --}}
<div class="main-wrapper">
    <header class="topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm d-lg-none border-0 p-1" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
            <span class="topbar-title"><i class="bi bi-chevron-right text-muted me-1" style="font-size:.7rem;"></i>@yield('page-title', 'Dashboard')</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary">PENJAGA</span>
            @if(Auth::user()->location)
                <span class="badge bg-secondary">{{ Auth::user()->location->nama_lokasi }}</span>
            @endif
            <span class="text-muted d-none d-md-inline" style="font-size:.78rem;">
                {{ now()->translatedFormat('d F Y H:i') }}
            </span>
        </div>
    </header>

    <main class="page-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar   = document.querySelector('.sakti-sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => sidebar.classList.toggle('show'));
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && e.target !== toggleBtn) sidebar.classList.remove('show');
        });
    }
</script>
@stack('scripts')
</body>
</html>
