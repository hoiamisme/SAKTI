<?php
// =============================================================================
// File   : app/Http/Controllers/Admin/LocationController.php
// Fungsi : CRUD manajemen pos pemeriksaan oleh admin
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(): View
    {
        $locations = Location::withCount('accessLogs')->orderBy('kode_lokasi')->get();

        return view('admin.locations.index', compact('locations'));
    }

    public function create(): View
    {
        return view('admin.locations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_lokasi' => ['required', 'string', 'max:100'],
            'kode_lokasi' => ['required', 'string', 'max:20', 'unique:locations,kode_lokasi'],
            'deskripsi'   => ['nullable', 'string'],
            'is_active'   => ['boolean'],
        ]);

        Location::create($validated);

        return redirect()->route('admin.locations.index')
            ->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function edit(Location $location): View
    {
        return view('admin.locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'nama_lokasi' => ['required', 'string', 'max:100'],
            'kode_lokasi' => ['required', 'string', 'max:20', 'unique:locations,kode_lokasi,' . $location->id],
            'deskripsi'   => ['nullable', 'string'],
            'is_active'   => ['boolean'],
        ]);

        $location->update($validated);

        return redirect()->route('admin.locations.index')
            ->with('success', 'Lokasi berhasil diperbarui.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $location->delete();

        return redirect()->route('admin.locations.index')
            ->with('success', 'Lokasi berhasil dihapus.');
    }
}
