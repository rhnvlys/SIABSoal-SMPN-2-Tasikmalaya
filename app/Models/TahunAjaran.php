<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TahunAjaran extends Model
{
    use HasFactory;

    protected $table = 'tahun_ajaran';

    protected $fillable = [
        'tahun_ajaran',
        'semester',
        'status',
    ];

    public function kelas()
    {
        return $this->hasMany(Kelas::class, 'tahun_ajaran_id');
    }

    public function ujian()
    {
        return $this->hasMany(Ujian::class, 'tahun_ajaran_id');
    }

    /**
     * Ambil tahun ajaran aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Label lengkap: "2025/2026 - Ganjil"
     */
    public function getLabelAttribute(): string
    {
        return $this->tahun_ajaran . ' - ' . $this->semester;
    }
}
