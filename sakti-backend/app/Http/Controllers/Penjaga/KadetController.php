<?php
// =============================================================================
// File   : app/Http/Controllers/Penjaga/KadetController.php
// Fungsi : Tampilan daftar kadet read-only untuk penjaga pos
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace App\Http\Controllers\Penjaga;

use App\Http\Controllers\Controller;
use App\Models\Kadet;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KadetController extends Controller
{
    public function index(Request $request): View
    {
        $kadets = Kadet::when(
                $request->filled('q'),
                fn ($q) => $q->where(function ($sub) use ($request) {
                    $sub->where('nama_lengkap', 'like', '%' . $request->q . '%')
                        ->orWhere('nim', 'like', '%' . $request->q . '%');
                })
            )
            ->orderBy('nama_lengkap')
            ->paginate(20)
            ->withQueryString();

        return view('penjaga.kadets.index', compact('kadets'));
    }
}
