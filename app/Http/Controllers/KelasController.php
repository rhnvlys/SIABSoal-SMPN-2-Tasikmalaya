<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\SiswaKelas;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        $kelas = Kelas::with(['tahunAjaran', 'waliKelas'])
                      ->withCount('siswaKelas')
                      ->when($request->tahun_ajaran_id, fn($q, $v) => $q->where('tahun_ajaran_id', $v))
                      ->when(auth()->user()->isGuru(), fn($q) => $q->where('wali_kelas_id', auth()->user()->guru?->id ?? 0))
                      ->orderBy('tingkat')
                      ->orderBy('nama_kelas')
                      ->paginate(15)
                      ->withQueryString();

        $tahunAjaranList = TahunAjaran::orderByDesc('tahun_ajaran')->get();

        return view('kelas.index', compact('kelas', 'tahunAjaranList'));
    }

    public function create()
    {
        $tahunAjaranList = TahunAjaran::orderByDesc('tahun_ajaran')->get();
        $guruList = Guru::where('status', 'aktif')->orderBy('nama_guru')->get();
        return view('kelas.create', compact('tahunAjaranList', 'guruList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kelas'      => 'required|string|max:50',
            'tingkat'         => 'required|in:VII,VIII,IX',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'wali_kelas_id'   => 'nullable|exists:guru,id',
        ]);

        Kelas::create($request->only(['nama_kelas', 'tingkat', 'tahun_ajaran_id', 'wali_kelas_id']));

        LogAktivitas::catat("Menambahkan kelas: {$request->nama_kelas}", 'Kelas');

        return redirect()->route('kelas.index')->with('success', 'Data kelas berhasil ditambahkan.');
    }

    public function show(Kelas $kela)
    {
        $kelas = $kela;
        $this->authorizeClassAccess($kelas);

        $kelas->load(['tahunAjaran', 'waliKelas', 'siswaKelas.siswa']);
        $siswaList = Siswa::where('status', 'aktif')
                          ->whereNotIn('id', $kelas->siswaKelas->pluck('siswa_id'))
                          ->orderBy('nama_siswa')
                          ->get();
        $canManageStudents = auth()->user()->isAdmin() || $this->isWaliKelas($kelas);

        return view('kelas.show', compact('kelas', 'siswaList', 'canManageStudents'));
    }

    public function edit(Kelas $kela)
    {
        $kelas = $kela;
        $tahunAjaranList = TahunAjaran::orderByDesc('tahun_ajaran')->get();
        $guruList = Guru::where('status', 'aktif')->orderBy('nama_guru')->get();
        return view('kelas.edit', compact('kelas', 'tahunAjaranList', 'guruList'));
    }

    public function update(Request $request, Kelas $kela)
    {
        $kelas = $kela;
        $request->validate([
            'nama_kelas'      => 'required|string|max:50',
            'tingkat'         => 'required|in:VII,VIII,IX',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'wali_kelas_id'   => 'nullable|exists:guru,id',
        ]);

        $kelas->update($request->only(['nama_kelas', 'tingkat', 'tahun_ajaran_id', 'wali_kelas_id']));

        // Handle siswa assignment jika ada
        if ($request->has('siswa_ids')) {
            foreach ($request->siswa_ids as $siswaId) {
                SiswaKelas::firstOrCreate([
                    'siswa_id'        => $siswaId,
                    'kelas_id'        => $kelas->id,
                    'tahun_ajaran_id' => $kelas->tahun_ajaran_id,
                ], ['status' => 'aktif']);
            }
        }

        LogAktivitas::catat("Mengubah kelas: {$kelas->nama_kelas}", 'Kelas');

        return redirect()->route('kelas.index')->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kela)
    {
        $kelas = $kela;
        if ($kelas->ujianKelas()->exists()) {
            return back()->with('error', 'Kelas tidak dapat dihapus karena memiliki data ujian.');
        }

        $nama = $kelas->nama_kelas;
        $kelas->siswaKelas()->delete();
        $kelas->delete();

        LogAktivitas::catat("Menghapus kelas: {$nama}", 'Kelas');

        return redirect()->route('kelas.index')->with('success', 'Data kelas berhasil dihapus.');
    }

    public function attachSiswa(Request $request, Kelas $kela)
    {
        $kelas = $kela;
        $this->authorizeClassManageStudents($kelas);

        $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
        ], [
            'siswa_id.required' => 'Pilih siswa yang akan ditambahkan.',
        ]);

        SiswaKelas::firstOrCreate([
            'siswa_id' => $request->siswa_id,
            'kelas_id' => $kelas->id,
            'tahun_ajaran_id' => $kelas->tahun_ajaran_id,
        ], ['status' => 'aktif']);

        LogAktivitas::catat("Menambahkan siswa ke kelas: {$kelas->nama_kelas}", 'Kelas');

        return redirect()->route('kelas.show', $kelas)->with('success', 'Siswa berhasil ditambahkan ke kelas.');
    }

    public function detachSiswa(Kelas $kela, Siswa $siswa)
    {
        $kelas = $kela;
        $this->authorizeClassManageStudents($kelas);

        SiswaKelas::where('kelas_id', $kelas->id)
            ->where('siswa_id', $siswa->id)
            ->delete();

        LogAktivitas::catat("Menghapus siswa dari kelas: {$kelas->nama_kelas}", 'Kelas');

        return redirect()->route('kelas.show', $kelas)->with('success', 'Siswa berhasil dihapus dari kelas.');
    }

    private function authorizeClassAccess(Kelas $kelas): void
    {
        if (auth()->user()->isGuru() && !$this->isWaliKelas($kelas)) {
            abort(403, 'Anda tidak memiliki akses ke kelas ini.');
        }
    }

    private function authorizeClassManageStudents(Kelas $kelas): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }

        if (!$this->isWaliKelas($kelas)) {
            abort(403, 'Anda tidak memiliki akses mengelola siswa kelas ini.');
        }
    }

    private function isWaliKelas(Kelas $kelas): bool
    {
        return auth()->user()->isGuru()
            && auth()->user()->guru
            && (int) $kelas->wali_kelas_id === (int) auth()->user()->guru->id;
    }
}
