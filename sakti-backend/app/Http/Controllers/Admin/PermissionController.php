<?php
// =============================================================================
// File   : app/Http/Controllers/Admin/PermissionController.php
// Fungsi : Manajemen hak akses kadet per lokasi oleh admin
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessPermission;
use App\Models\Kadet;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        $kadets    = Kadet::where('status', 'aktif')->orderBy('nama_lengkap')->get();
        $locations = Location::where('is_active', true)->orderBy('kode_lokasi')->get();
        $permMap   = AccessPermission::all()
            ->mapWithKeys(fn ($p) => ["{$p->kadet_id}_{$p->location_id}" => (bool) $p->is_allowed]);

        return view('admin.permissions.index', compact('kadets', 'locations', 'permMap'));
    }

    public function create(): View
    {
        $kadets    = Kadet::where('status', 'aktif')->orderBy('nama_lengkap')->get();
        $locations = Location::where('is_active', true)->orderBy('kode_lokasi')->get();

        return view('admin.permissions.create', compact('kadets', 'locations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kadet_id'    => ['required', 'exists:kadets,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'is_allowed'  => ['required', 'boolean'],
        ]);

        AccessPermission::updateOrCreate(
            [
                'kadet_id'    => $validated['kadet_id'],
                'location_id' => $validated['location_id'],
            ],
            ['is_allowed' => $validated['is_allowed']]
        );

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Hak akses berhasil disimpan.');
    }

    public function update(Request $request, AccessPermission $permission): RedirectResponse
    {
        $validated = $request->validate([
            'is_allowed' => ['required', 'boolean'],
        ]);

        $permission->update($validated);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Hak akses berhasil diperbarui.');
    }

    public function destroy(AccessPermission $permission): RedirectResponse
    {
        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Hak akses berhasil dihapus.');
    }
}
