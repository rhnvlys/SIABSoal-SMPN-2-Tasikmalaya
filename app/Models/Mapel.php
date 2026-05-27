<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    use HasFactory;

    protected $table = 'mapel';

    protected $fillable = [
        'kode_mapel',
        'nama_mapel',
    ];

    public function ujian()
    {
        return $this->hasMany(Ujian::class, 'mapel_id');
    }
}
