<?php

namespace App\Http\Controllers;

use App\Models\Mapel;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;

class MapelController extends Controller
{
    public function index(Request $request)
    {
        $mapel = Mapel::when($request->search, fn($q, $s) => $q->where('nama_mapel', 'like', "%{$s}%")->orWhere('kode_mapel', 'like', "%{$s}%"))
                      ->orderBy('nama_mapel')
                      ->paginate(15)
                      ->withQueryString();

        return view('mapel.index', compact('mapel'));
    }

    public function create()
    {
        return view('mapel.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_mapel' => 'required|string|max:20|unique:mapel,kode_mapel',
            'nama_mapel' => 'required|string|max:255',
        ]);

        Mapel::create($request->only(['kode_mapel', 'nama_mapel']));
        LogAktivitas::catat("Menambahkan mapel: {$request->nama_mapel}", 'Mapel');

        return redirect()->route('mapel.index')->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function edit(Mapel $mapel)
    {
        return view('mapel.edit', compact('mapel'));
    }

    public function update(Request $request, Mapel $mapel)
    {
        $request->validate([
            'kode_mapel' => 'required|string|max:20|unique:mapel,kode_mapel,' . $mapel->id,
            'nama_mapel' => 'required|string|max:255',
        ]);

        $mapel->update($request->only(['kode_mapel', 'nama_mapel']));
        LogAktivitas::catat("Mengubah mapel: {$mapel->nama_mapel}", 'Mapel');

        return redirect()->route('mapel.index')->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(Mapel $mapel)
    {
        if (\App\Models\Ujian::where('mapel_id', $mapel->id)->exists()) {
            return back()->with('error', 'Mata pelajaran tidak dapat dihapus karena memiliki data ujian.');
        }

        $nama = $mapel->nama_mapel;
        $mapel->delete();
        LogAktivitas::catat("Menghapus mapel: {$nama}", 'Mapel');

        return redirect()->route('mapel.index')->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
