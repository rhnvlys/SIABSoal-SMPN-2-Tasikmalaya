<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        return static::first();
    }
}
