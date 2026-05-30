<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PengaturanSekolah extends Model
{
    use HasFactory;

    protected $table = 'pengaturan_sekolah';

    protected $fillable = [
        'nama_sekolah',
        'nama_sistem',
        'nama_lengkap_sistem',
        'alamat',
        'kepala_sekolah',
        'nip_kepala_sekolah',
        'logo',
    ];

    /**
     * Ambil pengaturan sekolah (singleton pattern — hanya 1 row)
     */
    public static function getSettings()
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'nama_sekolah' => 'SMP NEGERI 2 TASIKMALAYA',
                'nama_sistem' => 'SIABSoal SMPN 2 Tasikmalaya',
                'nama_lengkap_sistem' => 'Sistem Informasi Analisis Butir Soal Berbasis Web SMPN 2 Tasikmalaya',
            ]
        );
    }

    public function logoUrl(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        return route('logo-sekolah.show', ['path' => $this->logo]);
    }

    public function logoPath(): ?string
    {
        if (!$this->logo || !Storage::disk('public')->exists($this->logo)) {
            return null;
        }

        return Storage::disk('public')->path($this->logo);
    }
}
