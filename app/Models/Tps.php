<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tps extends Model
{
    protected $fillable = ['nama', 'desa_id'];

    // Relasi desa pemilik TPS.
    public function desa()     { return $this->belongsTo(Desa::class); }
    // Relasi dokumen yang diupload untuk TPS.
    public function dokumens() { return $this->hasMany(Dokumen::class); }
    // Relasi user KPPS di TPS.
    public function users()    { return $this->hasMany(User::class); }
}
