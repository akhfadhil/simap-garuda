<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Desa extends Model
{
    protected $fillable = ['nama', 'kecamatan_id'];

    // Relasi kecamatan pemilik desa.
    public function kecamatan() { return $this->belongsTo(Kecamatan::class); }
    // Relasi TPS dalam desa.
    public function tps()       { return $this->hasMany(Tps::class); }
    // Relasi user Kordes di desa.
    public function users()     { return $this->hasMany(User::class); }
    // Relasi pendukung.
    public function pendukungs() { return $this->hasMany(Pendukung::class); }
}
