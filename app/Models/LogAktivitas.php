<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogAktivitas extends Model
{
    use HasFactory;

    protected $table = 'log_aktivitas';

    // Hanya menggunakan created_at, updated_at tetap ada dari timestamps
    protected $fillable = [
        'user_id',
        'aktivitas',
        'modul',
        'ip_address',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Helper: catat log aktivitas
     */
    public static function catat($aktivitas, $modul = null)
    {
        return static::create([
            'user_id'    => auth()->id(),
            'aktivitas'  => $aktivitas,
            'modul'      => $modul,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
