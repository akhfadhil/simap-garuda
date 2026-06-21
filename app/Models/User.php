<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', 'username', 'role', 'email', 'password', 'phone',
        'kecamatan_id', 'desa_id', 'tps_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Menentukan casting atribut user.
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Mengambil warna badge berdasarkan role.
    public function roleColor(): string
    {
        return match($this->role) {
            'admin_partai' => '#E63946',
            'korcam'   => '#F4A261',
            'kordes'   => '#2EC4B6',
            'saksi_tps'  => '#A8DADC',
            default => '#666666',
        };
    }

    // Relasi kecamatan untuk Korcam.
    public function kecamatan() 
    { 
        return $this->belongsTo(Kecamatan::class); 
    }

    // Relasi desa untuk Kordes.
    public function desa()      { return $this->belongsTo(Desa::class); }
    // Relasi TPS untuk Saksi TPS.
    public function tps()       { return $this->belongsTo(Tps::class); }
}
