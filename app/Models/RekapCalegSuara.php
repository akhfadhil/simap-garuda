<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RekapCalegSuara extends Model {
    protected $fillable = ['rekap_id', 'caleg_id', 'suara'];

    // Relasi header rekap.
    public function rekap()  { return $this->belongsTo(RekapHeader::class, 'rekap_id'); }
    // Relasi caleg pemilik suara.
    public function caleg()  { return $this->belongsTo(RekapCaleg::class, 'caleg_id'); }
}
