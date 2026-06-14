<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalisisButir extends Model
{
    use HasFactory;

    protected $table = 'analisis_butir';

    protected $fillable = [
        'ujian_id',
        'soal_id',
        'nomor_soal',
        'ba',
        'bb',
        'ja',
        'jb',
        'n_analisis',
        'jumlah_benar',
        'jumlah_peserta_valid',
        'dp',
        'kategori_dp',
        'tk',
        'kategori_tk',
        'distractor_status',
        'validitas',
        'kategori_validitas',
        'reliabilitas',
        'kategori_reliabilitas',
        'metode_reliabilitas',
        'keputusan',
        'alasan_rekomendasi',
    ];

    protected $casts = [
        'dp' => 'decimal:3',
        'tk' => 'decimal:3',
        'distractor_status' => 'array',
        'validitas' => 'decimal:3',
        'reliabilitas' => 'decimal:3',
    ];

    public function ujian()
    {
        return $this->belongsTo(Ujian::class, 'ujian_id');
    }

    public function soal()
    {
        return $this->belongsTo(Soal::class, 'soal_id');
    }

    public function hasAnalysisResult(): bool
    {
        foreach (['ba', 'bb', 'ja', 'jb', 'dp', 'tk', 'kategori_dp', 'kategori_tk', 'keputusan'] as $field) {
            $value = $this->getAttribute($field);

            if ($value === null || $value === '') {
                return false;
            }
        }

        return true;
    }
}
