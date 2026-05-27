<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswa';

    protected $fillable = [
        'nis',
        'nisn',
        'nama_siswa',
        'jenis_kelamin',
        'alamat',
        'status',
    ];

    public function siswaKelas()
    {
        return $this->hasMany(SiswaKelas::class, 'siswa_id');
    }

    public function kelas()
    {
        return $this->belongsToMany(Kelas::class, 'siswa_kelas', 'siswa_id', 'kelas_id')
                    ->withPivot('tahun_ajaran_id', 'status')
                    ->withTimestamps();
    }

    public function pesertaUjian()
    {
        return $this->hasMany(PesertaUjian::class, 'siswa_id');
    }
}
