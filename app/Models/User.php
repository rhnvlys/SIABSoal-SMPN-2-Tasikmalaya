<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'Admin';
    public const ROLE_GURU = 'Guru';
    public const ROLE_KEPALA_SEKOLAH = 'Kepala Sekolah';
    public const FINAL_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_GURU,
        self::ROLE_KEPALA_SEKOLAH,
    ];

    protected $fillable = [
        'role_id',
        'name',
        'username',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function guru()
    {
        return $this->hasOne(Guru::class, 'user_id');
    }

    public function logAktivitas()
    {
        return $this->hasMany(LogAktivitas::class, 'user_id');
    }

    /**
     * Check apakah user memiliki role tertentu
     */
    public function hasRole($roleName): bool
    {
        return $this->role?->nama_role === $roleName;
    }

    /**
     * Check apakah user memiliki salah satu dari role yang diberikan
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role?->nama_role, $roles, true);
    }

    /**
     * Check apakah user adalah Admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isGuru(): bool
    {
        return $this->hasRole(self::ROLE_GURU);
    }

    public function isKepalaSekolah(): bool
    {
        return $this->hasRole(self::ROLE_KEPALA_SEKOLAH);
    }

    public function hasFinalRole(): bool
    {
        return $this->hasAnyRole(self::FINAL_ROLES);
    }

    public function dashboardRouteName(): ?string
    {
        return match ($this->role?->nama_role) {
            self::ROLE_ADMIN => 'dashboard.admin',
            self::ROLE_GURU => 'dashboard.guru',
            self::ROLE_KEPALA_SEKOLAH => 'dashboard.kepala-sekolah',
            default => null,
        };
    }
}
