<?php
// =============================================================================
// File   : routes/web.php
// Fungsi : Web routes — autentikasi, dashboard admin, dan dashboard penjaga
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\KadetController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PresenceController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Penjaga\DashboardController;
use App\Http\Controllers\Penjaga\KadetController as PenjagaKadet;
use App\Http\Controllers\Penjaga\ScanController;
use App\Http\Controllers\Penjaga\WarningController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// --- Root redirect ---
Route::get('/', fn () => redirect()->route('login'));

// --- Autentikasi (Laravel Breeze / custom) ---
Auth::routes(['register' => false, 'verify' => false]);

// =============================================================================
// Admin routes (hanya role: admin)
// =============================================================================
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

        // Dashboard admin
        Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');
        Route::get('/dashboard/stats', [AdminDashboard::class, 'stats'])->name('dashboard.stats');

        // Manajemen kadet
        Route::resource('kadets', KadetController::class);
        // API: ambil face_image untuk Python worker
        Route::get('/kadets/{kadet}/face-image', [KadetController::class, 'faceImage'])->name('kadets.face-image');

        // Manajemen lokasi
        Route::resource('locations', LocationController::class)
            ->except(['show']);

        // Manajemen hak akses
        Route::resource('permissions', PermissionController::class)
            ->except(['show', 'edit']);

        // Laporan log akses
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');

        // Monitoring kehadiran kadet realtime
        Route::get('/presence', [PresenceController::class, 'index'])->name('presence.index');
        Route::get('/presence/data', [PresenceController::class, 'data'])->name('presence.data');
    });

// =============================================================================
// Penjaga routes (role: admin & penjaga — admin boleh pantau semua pos)
// =============================================================================
Route::prefix('penjaga')
    ->name('penjaga.')
    ->middleware(['auth', 'role:admin,penjaga'])
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

        // Monitoring scan RFID
        Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');

        // AJAX polling — log terbaru
        Route::get('/scan/latest', [ScanController::class, 'latest'])->name('scan.latest');

        // AJAX — mulai scan
        Route::post('/scan/start', [ScanController::class, 'start'])->name('scan.start');

        // AJAX — lookup UID langsung dari input bar
        Route::post('/scan/lookup', [ScanController::class, 'lookup'])->name('scan.lookup');

        // AJAX — verifikasi wajah setelah RFID scan berhasil
        Route::post('/scan/verify-face', [ScanController::class, 'verifyFace'])->name('scan.verify-face');

        // Data kadet read-only
        Route::get('/kadets', [PenjagaKadet::class, 'index'])->name('kadets.index');

        // Peringatan akses gagal
        Route::get('/warnings', [WarningController::class, 'index'])->name('warnings.index');
        Route::patch('/warnings/{log}/read', [WarningController::class, 'markRead'])->name('warnings.read');
        Route::post('/warnings/read-all', [WarningController::class, 'markAllRead'])->name('warnings.read-all');
    });
