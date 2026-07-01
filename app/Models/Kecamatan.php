<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kecamatan extends Model
{
    protected $fillable = ['nama', 'dapil_id'];

    // Relasi desa dalam kecamatan.
    public function desas() { return $this->hasMany(Desa::class); }
    // Relasi user Korcam di kecamatan.
    public function users() { return $this->hasMany(User::class); }
    // Relasi dapil kecamatan.
    public function dapil() { return $this->belongsTo(Dapil::class); }
    // Relasi pendukung.
    public function pendukungs() { return $this->hasMany(Pendukung::class); }
}
