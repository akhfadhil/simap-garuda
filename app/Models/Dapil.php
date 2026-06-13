<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Dapil extends Model {
    protected $fillable = ['nama'];

    // Relasi kecamatan dalam dapil.
    public function kecamatans() { return $this->hasMany(Kecamatan::class); }
    // Relasi partai DPRD kabupaten dalam dapil.
    public function partais()    { return $this->hasMany(RekapPartai::class); }
}
