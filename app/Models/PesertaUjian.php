<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesertaUjian extends Model
{
    use HasFactory;

    protected $table = 'peserta_ujian';

    protected $fillable = [
        'ujian_id',
        'siswa_id',
        'kelas_id',
        'status_kehadiran',
        'jumlah_benar',
        'jumlah_salah',
        'total_skor',
        'nilai',
        'ranking',
        'kelompok',
        'keterangan',
    ];

    protected $casts = [
        'total_skor' => 'decimal:2',
        'nilai' => 'decimal:2',
    ];

    public function ujian()
    {
        return $this->belongsTo(Ujian::class, 'ujian_id');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function jawabanSiswa()
    {
        return $this->hasMany(JawabanSiswa::class, 'peserta_ujian_id');
    }

    /**
     * Scope hanya peserta yang hadir
     */
    public function scopeHadir($query)
    {
        return $query->where('status_kehadiran', 'hadir');
    }

    /**
     * Scope kelompok atas
     */
    public function scopeKelompokAtas($query)
    {
        return $query->where('kelompok', 'atas');
    }

    /**
     * Scope kelompok bawah
     */
    public function scopeKelompokBawah($query)
    {
        return $query->where('kelompok', 'bawah');
    }
}
