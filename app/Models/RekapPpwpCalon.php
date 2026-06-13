<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RekapPpwpCalon extends Model {
    protected $fillable = ['nomor_urut', 'nama_paslon'];

    // Relasi suara paslon PPWP.
    public function suaras() { return $this->hasMany(RekapPpwpSuara::class, 'calon_id'); }
}
