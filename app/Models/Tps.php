<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tps extends Model
{
    protected $fillable = ['nama', 'desa_id'];

    // Relasi desa pemilik TPS.
    public function desa()     { return $this->belongsTo(Desa::class); }
    // Relasi rekap yang diinput saksi TPS.
    public function rekapHeaders() { return $this->hasMany(RekapHeader::class); }
    // Relasi user Saksi TPS di TPS.
    public function users()    { return $this->hasMany(User::class); }
}
