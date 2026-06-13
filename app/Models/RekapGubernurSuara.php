<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RekapGubernurSuara extends Model
{
    protected $fillable = ['rekap_id', 'calon_id', 'suara'];

    // Relasi paslon gubernur pemilik suara.
    public function calon() { return $this->belongsTo(RekapGubernurCalon::class, 'calon_id'); }
}
