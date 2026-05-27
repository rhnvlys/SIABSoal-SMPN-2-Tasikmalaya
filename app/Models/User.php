<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
        return $this->role->nama_role === $roleName;
    }

    /**
     * Check apakah user memiliki salah satu dari role yang diberikan
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role->nama_role, $roles);
    }

    /**
     * Check apakah user adalah Admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('Admin');
    }
}
