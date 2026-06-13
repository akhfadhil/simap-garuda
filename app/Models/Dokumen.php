<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dokumen extends Model
{
    protected $fillable = [
        'tps_id', 'kecamatan_id', 'uploaded_by', 'jenis', 'level', 'status',
        'verified_by', 'verified_at', 'file_path', 'file_name', 'file_size',
        'komentar', 'is_archived', 'archived_at',
    ];

    protected $casts = [
        'verified_at'  => 'datetime',
        'archived_at'  => 'datetime',
        'is_archived'  => 'boolean',
    ];

    const JENIS = [
        'PPWP'      => 'Presiden & Wakil Presiden',
        'GUBERNUR'  => 'Gubernur & Wakil Gubernur',
        'BUPATI'    => 'Bupati & Wakil Bupati',
        'DPD'       => 'DPD',
        'DPR_RI'    => 'DPR RI',
        'DPRD_PROV' => 'DPRD Provinsi',
        'DPRD_KAB'  => 'DPRD Kabupaten',
    ];

    const STATUS_COLORS = [
        'menunggu_verifikasi' => '#F4A261',
        'terverifikasi'       => '#2EC4B6',
        'ditolak'             => '#EF4444',
    ];

    const STATUS_LABELS = [
    'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'terverifikasi'       => 'Terverifikasi',
        'ditolak'             => 'Ditolak',
    ];


    // Relasi TPS pemilik dokumen.
    public function tps()       { return $this->belongsTo(Tps::class); }
    // Relasi kecamatan untuk dokumen level PPK.
    public function kecamatan() { return $this->belongsTo(Kecamatan::class); }
    // Relasi user yang mengupload dokumen.
    public function uploader()  { return $this->belongsTo(User::class, 'uploaded_by'); }
    // Relasi user yang memverifikasi dokumen.
    public function verifier()  { return $this->belongsTo(User::class, 'verified_by'); }
}
