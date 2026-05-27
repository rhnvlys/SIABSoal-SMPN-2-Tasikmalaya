<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ujian extends Model
{
    use HasFactory;

    protected $table = 'ujian';

    protected $fillable = [
        'guru_id',
        'mapel_id',
        'tahun_ajaran_id',
        'nama_ujian',
        'jenis_ujian',
        'tanggal_ujian',
        'jumlah_soal',
        'kkm',
        'metode_kelompok',
        'jumlah_kelompok_manual',
        'status',
    ];

    protected $casts = [
        'tanggal_ujian' => 'date',
        'kkm' => 'decimal:2',
    ];

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'guru_id');
    }

    public function mapel()
    {
        return $this->belongsTo(Mapel::class, 'mapel_id');
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function ujianKelas()
    {
        return $this->hasMany(UjianKelas::class, 'ujian_id');
    }

    public function kelas()
    {
        return $this->belongsToMany(Kelas::class, 'ujian_kelas', 'ujian_id', 'kelas_id')
                    ->withTimestamps();
    }

    public function soal()
    {
        return $this->hasMany(Soal::class, 'ujian_id')->orderBy('nomor_soal');
    }

    public function pesertaUjian()
    {
        return $this->hasMany(PesertaUjian::class, 'ujian_id');
    }

    public function analisisButir()
    {
        return $this->hasMany(AnalisisButir::class, 'ujian_id');
    }

    /**
     * Cek apakah kunci jawaban sudah lengkap
     */
    public function isKunciLengkap(): bool
    {
        return $this->soal()->whereNotNull('kunci_jawaban')->count() === $this->jumlah_soal;
    }

    /**
     * Status label map
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'kunci_lengkap' => 'Kunci Lengkap',
            'data_mentah' => 'Data Mentah T1',
            'olah_data' => 'Olah Data T2',
            'dianalisis' => 'Dianalisis T3',
            'selesai' => 'Selesai',
            default => $this->status,
        };
    }
}
