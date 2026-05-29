<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $siswa = Siswa::when($request->search, fn($q, $s) => $q->where(function ($query) use ($s) {
                          $query->where('nama_siswa', 'like', "%{$s}%")
                              ->orWhere('nis', 'like', "%{$s}%")
                              ->orWhere('nisn', 'like', "%{$s}%");
                      }))
                      ->when($request->status, fn($q, $s) => $q->where('status', $s))
                      ->when(auth()->user()->isGuru(), function ($q) {
                          $kelasIds = auth()->user()->guru?->kelasWali()->pluck('id') ?? collect();
                          $q->whereHas('siswaKelas', fn($sk) => $sk->whereIn('kelas_id', $kelasIds));
                      })
                      ->orderBy('nama_siswa')
                      ->paginate(20)
                      ->withQueryString();

        return view('siswa.index', compact('siswa'));
    }

    public function create()
    {
        return view('siswa.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nis'            => 'required|string|max:20|unique:siswa,nis',
            'nisn'           => 'nullable|string|max:20|unique:siswa,nisn',
            'nama_siswa'     => 'required|string|max:255',
            'jenis_kelamin'  => 'required|in:L,P',
            'alamat'         => 'nullable|string',
            'status'         => 'required|in:aktif,nonaktif,lulus',
        ]);

        Siswa::create($request->only(['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'alamat', 'status']));

        LogAktivitas::catat("Menambahkan siswa: {$request->nama_siswa}", 'Siswa');

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function edit(Siswa $siswa)
    {
        $this->authorizeSiswaAccess($siswa);
        return view('siswa.edit', compact('siswa'));
    }

    public function update(Request $request, Siswa $siswa)
    {
        $this->authorizeSiswaAccess($siswa);

        $request->validate([
            'nis'            => 'required|string|max:20|unique:siswa,nis,' . $siswa->id,
            'nisn'           => 'nullable|string|max:20|unique:siswa,nisn,' . $siswa->id,
            'nama_siswa'     => 'required|string|max:255',
            'jenis_kelamin'  => 'required|in:L,P',
            'alamat'         => 'nullable|string',
            'status'         => 'required|in:aktif,nonaktif,lulus',
        ]);

        $siswa->update($request->only(['nis', 'nisn', 'nama_siswa', 'jenis_kelamin', 'alamat', 'status']));

        LogAktivitas::catat("Mengubah siswa: {$siswa->nama_siswa}", 'Siswa');

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Siswa $siswa)
    {
        $this->authorizeSiswaAccess($siswa);

        if ($siswa->pesertaUjian()->exists()) {
            return back()->with('error', 'Siswa tidak dapat dihapus karena memiliki data ujian.');
        }

        $nama = $siswa->nama_siswa;
        $siswa->delete();

        LogAktivitas::catat("Menghapus siswa: {$nama}", 'Siswa');

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    private function authorizeSiswaAccess(Siswa $siswa): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return;
        }

        $kelasIds = $user->guru?->kelasWali()->pluck('id') ?? collect();
        $hasAccess = $siswa->siswaKelas()->whereIn('kelas_id', $kelasIds)->exists();

        if (!$hasAccess) {
            abort(403, 'Anda tidak memiliki akses ke data siswa ini.');
        }
    }
}
