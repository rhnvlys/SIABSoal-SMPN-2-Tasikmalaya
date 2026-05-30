<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class LogAktivitas extends Model
{
    use HasFactory;

    protected $table = 'log_aktivitas';

    // Hanya menggunakan created_at, updated_at tetap ada dari timestamps
    protected $fillable = [
        'user_id',
        'nama_user',
        'role',
        'aksi',
        'aktivitas',
        'modul',
        'deskripsi',
        'related_type',
        'related_id',
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
    public static function catat($aksi, $modul = null, $deskripsi = null, $relatedType = null, $relatedId = null)
    {
        $user = auth()->user();
        $deskripsi = $deskripsi ?: $aksi;

        $payload = [
            'user_id'    => $user?->id,
            'aktivitas'  => $deskripsi,
            'modul'      => $modul,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        static $availableColumns = null;
        $availableColumns ??= Schema::getColumnListing('log_aktivitas');

        foreach ([
            'nama_user' => $user?->name,
            'role' => $user?->role?->nama_role,
            'aksi' => $aksi,
            'deskripsi' => $deskripsi,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
        ] as $column => $value) {
            if (in_array($column, $availableColumns, true)) {
                $payload[$column] = $value;
            }
        }

        return static::create($payload);
    }
}
