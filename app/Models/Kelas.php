<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';

    protected $fillable = [
        'tahun_ajaran_id',
        'wali_kelas_id',
        'nama_kelas',
        'tingkat',
    ];

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function waliKelas()
    {
        return $this->belongsTo(Guru::class, 'wali_kelas_id');
    }

    public function siswaKelas()
    {
        return $this->hasMany(SiswaKelas::class, 'kelas_id');
    }

    public function siswa()
    {
        return $this->belongsToMany(Siswa::class, 'siswa_kelas', 'kelas_id', 'siswa_id')
                    ->withPivot('tahun_ajaran_id', 'status')
                    ->withTimestamps();
    }

    public function ujianKelas()
    {
        return $this->hasMany(UjianKelas::class, 'kelas_id');
    }

    /**
     * Label: "IX A (2025/2026 Ganjil)"
     */
    public function getLabelAttribute(): string
    {
        return $this->nama_kelas;
    }
}
