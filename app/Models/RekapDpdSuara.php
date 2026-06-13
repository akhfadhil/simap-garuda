<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RekapDpdSuara extends Model {
    protected $fillable = ['rekap_id', 'calon_id', 'suara'];

    // Relasi header rekap.
    public function rekap() { return $this->belongsTo(RekapHeader::class, 'rekap_id'); }
    // Relasi calon DPD pemilik suara.
    public function calon() { return $this->belongsTo(RekapDpdCalon::class, 'calon_id'); }
}
