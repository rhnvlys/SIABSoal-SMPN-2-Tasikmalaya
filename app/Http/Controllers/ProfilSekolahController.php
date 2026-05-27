<?php

namespace App\Http\Controllers;

use App\Models\PengaturanSekolah;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;

class ProfilSekolahController extends Controller
{
    public function edit()
    {
        $pengaturan = PengaturanSekolah::first();
        if (!$pengaturan) {
            $pengaturan = PengaturanSekolah::create([
                'nama_sekolah' => 'SMP NEGERI 2 TASIKMALAYA',
                'nama_sistem'  => 'SIABSoal SMPN 2 Tasikmalaya',
                'nama_lengkap_sistem' => 'Sistem Informasi Analisis Butir Soal Berbasis Web SMPN 2 Tasikmalaya',
            ]);
        }
        return view('profil_sekolah.edit', compact('pengaturan'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'nama_sekolah'        => 'required|string|max:255',
            'nama_sistem'         => 'required|string|max:255',
            'nama_lengkap_sistem' => 'required|string|max:500',
            'alamat'              => 'nullable|string',
            'kepala_sekolah'      => 'nullable|string|max:255',
            'nip_kepala_sekolah'  => 'nullable|string|max:30',
            'logo'                => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        $pengaturan = PengaturanSekolah::first();
        $data = $request->only(['nama_sekolah', 'nama_sistem', 'nama_lengkap_sistem', 'alamat', 'kepala_sekolah', 'nip_kepala_sekolah']);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logo', 'public');
            $data['logo'] = $path;
        }

        $pengaturan->update($data);
        LogAktivitas::catat('Mengubah profil sekolah', 'Profil Sekolah');

        return redirect()->route('profil-sekolah.edit')->with('success', 'Profil sekolah berhasil diperbarui.');
    }
}
