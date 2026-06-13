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
        'name', 'username', 'role', 'email', 'password',
        'kecamatan_id', 'desa_id', 'tps_id', 'partai_id',
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
            'admin' => '#E63946',
            'komisioner' => '#2563EB',
            'partai' => '#7C3AED',
            'ppk'   => '#F4A261',
            'pps'   => '#2EC4B6',
            'kpps'  => '#A8DADC',
            default => '#666666',
        };
    }

    // Relasi kecamatan untuk user PPK.
    public function kecamatan() 
    { 
        return $this->belongsTo(Kecamatan::class); 
    }

    // Relasi desa untuk user PPS.
    public function desa()      { return $this->belongsTo(Desa::class); }
    // Relasi TPS untuk user KPPS.
    public function tps()       { return $this->belongsTo(Tps::class); }
    // Relasi partai untuk akun partai.
    public function partai()    { return $this->belongsTo(RekapPartai::class, 'partai_id'); }
}
