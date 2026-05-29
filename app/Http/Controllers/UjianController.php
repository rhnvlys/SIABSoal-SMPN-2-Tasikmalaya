<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\Soal;
use App\Models\UjianKelas;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UjianController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Ujian::with(['guru', 'mapel', 'kelas', 'tahunAjaran'])
                      ->when($request->search, fn($q, $s) => $q->where('nama_ujian', 'like', "%{$s}%"))
                      ->when($request->status, fn($q, $s) => $q->where('status', $s))
                      ->when($request->mapel_id, fn($q, $v) => $q->where('mapel_id', $v));

        // Guru hanya lihat ujian miliknya
        if ($user->hasRole('Guru') && $user->guru) {
            $query->where('guru_id', $user->guru->id);
        }

        $ujian = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        $mapelList = Mapel::orderBy('nama_mapel')->get();

        return view('ujian.index', compact('ujian', 'mapelList'));
    }

    public function create()
    {
        $user = auth()->user();
        if ($user->isGuru() && !$user->guru) {
            abort(403, 'Akun guru belum terhubung dengan data guru.');
        }

        $guruList = $user->isAdmin()
            ? Guru::where('status', 'aktif')->orderBy('nama_guru')->get()
            : collect([$user->guru])->filter();

        $mapelList = Mapel::orderBy('nama_mapel')->get();
        $tahunAjaranList = TahunAjaran::orderByDesc('tahun_ajaran')->get();
        $kelasList = Kelas::with('tahunAjaran')->orderBy('tingkat')->orderBy('nama_kelas')->get();

        return view('ujian.create', compact('guruList', 'mapelList', 'tahunAjaranList', 'kelasList'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->isGuru()) {
            if (!auth()->user()->guru) {
                abort(403, 'Akun guru belum terhubung dengan data guru.');
            }
            $request->merge(['guru_id' => auth()->user()->guru->id]);
        }

        $request->validate([
            'guru_id'          => 'required|exists:guru,id',
            'mapel_id'         => 'required|exists:mapel,id',
            'tahun_ajaran_id'  => 'required|exists:tahun_ajaran,id',
            'nama_ujian'       => 'required|string|max:255',
            'jenis_ujian'      => 'required|in:UH,STS,SAS,ASAJ,PAS,PAT,UTS,UAS,Lainnya',
            'tanggal_ujian'    => 'required|date',
            'jumlah_soal'      => 'required|integer|min:1|max:50',
            'kkm'              => 'required|numeric|min:0|max:100',
            'metode_kelompok'  => 'required|in:persen_50,manual',
            'jumlah_kelompok_manual' => 'nullable|required_if:metode_kelompok,manual|integer|min:1',
            'kelas_ids'        => 'required|array|min:1',
            'kelas_ids.*'      => 'exists:kelas,id',
        ], [
            'kelas_ids.required' => 'Pilih minimal satu kelas.',
            'jumlah_soal.min'    => 'Jumlah soal minimal 1.',
            'jumlah_soal.max'    => 'Jumlah soal maksimal 50.',
        ]);

        DB::transaction(function () use ($request) {
            $ujian = Ujian::create($request->only([
                'guru_id', 'mapel_id', 'tahun_ajaran_id', 'nama_ujian', 'jenis_ujian',
                'tanggal_ujian', 'jumlah_soal', 'kkm', 'metode_kelompok', 'jumlah_kelompok_manual'
            ]));

            // Assign kelas
            foreach ($request->kelas_ids as $kelasId) {
                UjianKelas::create(['ujian_id' => $ujian->id, 'kelas_id' => $kelasId]);
            }

            // Auto-create soal 1 sampai jumlah_soal
            for ($i = 1; $i <= $request->jumlah_soal; $i++) {
                Soal::create([
                    'ujian_id'       => $ujian->id,
                    'nomor_soal'     => $i,
                    'kunci_jawaban'  => null,
                    'bobot'          => 1.00,
                ]);
            }

            LogAktivitas::catat("Membuat ujian: {$ujian->nama_ujian}", 'Ujian');
        });

        return redirect()->route('ujian.index')->with('success', 'Ujian berhasil dibuat. Silakan isi kunci jawaban.');
    }

    public function show(Ujian $ujian)
    {
        $ujian->load(['guru', 'mapel', 'tahunAjaran', 'kelas', 'soal', 'pesertaUjian.siswa', 'analisisButir']);
        return view('ujian.show', compact('ujian'));
    }

    public function edit(Ujian $ujian)
    {
        $user = auth()->user();
        if ($user->isGuru() && !$user->guru) {
            abort(403, 'Akun guru belum terhubung dengan data guru.');
        }

        $guruList = $user->isAdmin() ? Guru::where('status', 'aktif')->orderBy('nama_guru')->get() : collect([$user->guru])->filter();
        $mapelList = Mapel::orderBy('nama_mapel')->get();
        $tahunAjaranList = TahunAjaran::orderByDesc('tahun_ajaran')->get();
        $kelasList = Kelas::with('tahunAjaran')->orderBy('tingkat')->orderBy('nama_kelas')->get();
        $selectedKelas = $ujian->kelas->pluck('id')->toArray();

        return view('ujian.edit', compact('ujian', 'guruList', 'mapelList', 'tahunAjaranList', 'kelasList', 'selectedKelas'));
    }

    public function update(Request $request, Ujian $ujian)
    {
        if (auth()->user()->isGuru()) {
            if (!auth()->user()->guru) {
                abort(403, 'Akun guru belum terhubung dengan data guru.');
            }
            $request->merge(['guru_id' => $ujian->guru_id]);
        }

        $request->validate([
            'guru_id'          => 'required|exists:guru,id',
            'mapel_id'         => 'required|exists:mapel,id',
            'tahun_ajaran_id'  => 'required|exists:tahun_ajaran,id',
            'nama_ujian'       => 'required|string|max:255',
            'jenis_ujian'      => 'required|in:UH,STS,SAS,ASAJ,PAS,PAT,UTS,UAS,Lainnya',
            'tanggal_ujian'    => 'required|date',
            'kkm'              => 'required|numeric|min:0|max:100',
            'metode_kelompok'  => 'required|in:persen_50,manual',
            'kelas_ids'        => 'required|array|min:1',
        ]);

        DB::transaction(function () use ($request, $ujian) {
            $ujian->update($request->only([
                'guru_id', 'mapel_id', 'tahun_ajaran_id', 'nama_ujian', 'jenis_ujian',
                'tanggal_ujian', 'kkm', 'metode_kelompok', 'jumlah_kelompok_manual'
            ]));

            // Sync kelas
            $ujian->ujianKelas()->delete();
            foreach ($request->kelas_ids as $kelasId) {
                UjianKelas::create(['ujian_id' => $ujian->id, 'kelas_id' => $kelasId]);
            }

            LogAktivitas::catat("Mengubah ujian: {$ujian->nama_ujian}", 'Ujian');
        });

        return redirect()->route('ujian.show', $ujian)->with('success', 'Ujian berhasil diperbarui.');
    }

    public function destroy(Ujian $ujian)
    {
        $nama = $ujian->nama_ujian;

        DB::transaction(function () use ($ujian) {
            $ujian->analisisButir()->delete();
            foreach ($ujian->pesertaUjian as $peserta) {
                $peserta->jawabanSiswa()->delete();
            }
            $ujian->pesertaUjian()->delete();
            $ujian->soal()->delete();
            $ujian->ujianKelas()->delete();
            $ujian->delete();
        });

        LogAktivitas::catat("Menghapus ujian: {$nama}", 'Ujian');

        return redirect()->route('ujian.index')->with('success', 'Ujian berhasil dihapus beserta semua datanya.');
    }
}
