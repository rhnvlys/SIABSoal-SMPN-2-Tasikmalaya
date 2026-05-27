<?php

namespace App\Http\Controllers;

use App\Models\TahunAjaran;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;

class TahunAjaranController extends Controller
{
    public function index()
    {
        $tahunAjaran = TahunAjaran::orderByDesc('tahun_ajaran')->orderBy('semester')->paginate(15);
        return view('tahun_ajaran.index', compact('tahunAjaran'));
    }

    public function create()
    {
        return view('tahun_ajaran.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'tahun_ajaran' => 'required|string|max:20',
            'semester'     => 'required|in:Ganjil,Genap',
            'status'       => 'required|in:aktif,nonaktif',
        ]);

        // Jika status aktif, nonaktifkan yang lain
        if ($request->status === 'aktif') {
            TahunAjaran::where('status', 'aktif')->update(['status' => 'nonaktif']);
        }

        TahunAjaran::create($request->only(['tahun_ajaran', 'semester', 'status']));
        LogAktivitas::catat("Menambahkan tahun ajaran: {$request->tahun_ajaran} {$request->semester}", 'Tahun Ajaran');

        return redirect()->route('tahun-ajaran.index')->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function edit(TahunAjaran $tahun_ajaran)
    {
        return view('tahun_ajaran.edit', compact('tahun_ajaran'));
    }

    public function update(Request $request, TahunAjaran $tahun_ajaran)
    {
        $request->validate([
            'tahun_ajaran' => 'required|string|max:20',
            'semester'     => 'required|in:Ganjil,Genap',
            'status'       => 'required|in:aktif,nonaktif',
        ]);

        if ($request->status === 'aktif') {
            TahunAjaran::where('status', 'aktif')->where('id', '!=', $tahun_ajaran->id)->update(['status' => 'nonaktif']);
        }

        $tahun_ajaran->update($request->only(['tahun_ajaran', 'semester', 'status']));
        LogAktivitas::catat("Mengubah tahun ajaran: {$tahun_ajaran->label}", 'Tahun Ajaran');

        return redirect()->route('tahun-ajaran.index')->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    public function destroy(TahunAjaran $tahun_ajaran)
    {
        if ($tahun_ajaran->ujian()->exists()) {
            return back()->with('error', 'Tahun ajaran tidak dapat dihapus karena memiliki data ujian.');
        }

        $label = $tahun_ajaran->label;
        $tahun_ajaran->delete();
        LogAktivitas::catat("Menghapus tahun ajaran: {$label}", 'Tahun Ajaran');

        return redirect()->route('tahun-ajaran.index')->with('success', 'Tahun ajaran berhasil dihapus.');
    }
}
