<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RekapCaleg extends Model {
    protected $fillable = ['partai_id', 'nomor_urut', 'nama_caleg'];

    // Relasi partai pengusung caleg.
    public function partai() { return $this->belongsTo(RekapPartai::class, 'partai_id'); }
    // Relasi suara caleg.
    public function suaras() { return $this->hasMany(RekapCalegSuara::class, 'caleg_id'); }
}
