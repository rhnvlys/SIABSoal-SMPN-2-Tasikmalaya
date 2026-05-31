<?php

namespace App\Http\Controllers;

use App\Models\PengaturanSekolah;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfilSekolahController extends Controller
{
    public function edit()
    {
        $pengaturan = $this->settings();
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
            'logo'                => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'logo_url'            => 'nullable|url|max:2048',
        ]);

        $pengaturan = $this->settings();
        $data = $request->only(['nama_sekolah', 'nama_sistem', 'nama_lengkap_sistem', 'alamat', 'kepala_sekolah', 'nip_kepala_sekolah']);
        $logoChanged = false;
        $logoAction = null;

        if ($request->hasFile('logo')) {
            $oldLogo = $pengaturan->logo;
            $path = $request->file('logo')->store('logo-sekolah', 'public');
            $data['logo'] = $path;
            $data['logo_url'] = null;
            $logoChanged = true;
            $logoAction = 'Upload / ganti logo sekolah';

            $this->deleteLocalLogo($oldLogo);
        } elseif ($request->filled('logo_url')) {
            $data['logo'] = null;
            $data['logo_url'] = $request->string('logo_url')->trim()->toString();
            $logoChanged = $data['logo_url'] !== $pengaturan->logo_url || (bool) $pengaturan->logo;
            $logoAction = 'Menggunakan URL logo sekolah';

            $this->deleteLocalLogo($pengaturan->logo);
        } elseif ($request->has('logo_url') && $pengaturan->logo_url) {
            $data['logo_url'] = null;
            $logoChanged = true;
            $logoAction = 'Menghapus URL logo sekolah';
        }

        $pengaturan->update($data);
        LogAktivitas::catat(
            $logoChanged ? 'Upload logo sekolah' : 'Update profil sekolah',
            'Profil Sekolah',
            $logoChanged ? $logoAction : 'Mengubah profil sekolah'
        );

        return redirect()->route('profil-sekolah.edit')->with('success', 'Profil sekolah berhasil diperbarui.');
    }

    public function logo(string $path)
    {
        abort_if(str_contains($path, '..'), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function settings(): PengaturanSekolah
    {
        return PengaturanSekolah::firstOrCreate(
            ['id' => 1],
            [
                'nama_sekolah' => 'SMP NEGERI 2 TASIKMALAYA',
                'nama_sistem'  => 'SIABSoal SMPN 2 Tasikmalaya',
                'nama_lengkap_sistem' => 'Sistem Informasi Analisis Butir Soal Berbasis Web SMPN 2 Tasikmalaya',
            ]
        );
    }

    private function deleteLocalLogo(?string $path): void
    {
        if (! $path || filter_var($path, FILTER_VALIDATE_URL)) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
