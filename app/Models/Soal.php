<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Soal extends Model
{
    use HasFactory;

    protected $table = 'soal';

    protected $fillable = [
        'ujian_id',
        'nomor_soal',
        'kunci_jawaban',
        'bobot',
    ];

    protected $casts = [
        'bobot' => 'decimal:2',
    ];

    public function ujian()
    {
        return $this->belongsTo(Ujian::class, 'ujian_id');
    }

    public function jawabanSiswa()
    {
        return $this->hasMany(JawabanSiswa::class, 'soal_id');
    }

    public function analisisButir()
    {
        return $this->hasOne(AnalisisButir::class, 'soal_id');
    }
}
