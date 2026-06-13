<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RekapDpdCalon extends Model {
    protected $fillable = ['nomor_urut', 'nama_calon'];

    // Relasi suara calon DPD.
    public function suaras() { return $this->hasMany(RekapDpdSuara::class, 'calon_id'); }
}
