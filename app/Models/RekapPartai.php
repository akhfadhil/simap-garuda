<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RekapPartai extends Model {
    protected $fillable = ['jenis', 'nomor_urut', 'nama_partai', 'dapil_id'];

    const JENIS = ['dpr_ri', 'dprd_prov', 'dprd_kab'];

    // Relasi caleg dalam partai.
    public function calegs()      { return $this->hasMany(RekapCaleg::class, 'partai_id')->orderBy('nomor_urut'); }
    // Relasi suara partai.
    public function suaras()      { return $this->hasMany(RekapPartaiSuara::class, 'partai_id'); }
    // Relasi dapil untuk DPRD kabupaten.
    public function dapil()       { return $this->belongsTo(Dapil::class); }
}
