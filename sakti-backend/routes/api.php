<?php
// =============================================================================
// File   : routes/api.php
// Fungsi : API routes untuk komunikasi Python worker → Laravel
//          Autentikasi menggunakan X-API-KEY header (stateless)
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

use App\Http\Controllers\Api\AccessLogController;
use Illuminate\Support\Facades\Route;

/*
 |--------------------------------------------------------------------------
 | API Routes — SAKTI
 |--------------------------------------------------------------------------
 |
 | Semua route di sini diprefix /api secara otomatis oleh RouteServiceProvider.
 | Autentikasi dilakukan via X-API-KEY header, divalidasi di dalam controller.
 |
*/

Route::prefix('v1')->group(function () {

    // --- Access Log ---
    // POST  /api/v1/access-log       → simpan log akses dari Python worker
    Route::post('/access-log', [AccessLogController::class, 'store'])
        ->name('api.access-log.store');

    // --- Kadet lookup ---
    // GET   /api/v1/kadets/{rfid_uid} → cek data kadet berdasarkan RFID UID
    Route::get('/kadets/{rfid_uid}', [AccessLogController::class, 'findByRfid'])
        ->where('rfid_uid', '[A-Za-z0-9]+')   // hanya karakter alfanumerik
        ->name('api.kadets.by-rfid');
});
