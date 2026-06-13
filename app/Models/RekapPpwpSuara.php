<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RekapPpwpSuara extends Model {
    protected $fillable = ['rekap_id', 'calon_id', 'suara'];

    // Relasi header rekap.
    public function rekap() { return $this->belongsTo(RekapHeader::class, 'rekap_id'); }
    // Relasi paslon PPWP pemilik suara.
    public function calon() { return $this->belongsTo(RekapPpwpCalon::class, 'calon_id'); }
}
