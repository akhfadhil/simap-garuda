<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RekapHeader extends Model {
    protected $fillable = [
        'tps_id', 'jenis',
        'dpt_lk', 'dpt_pr',
        'pengguna_dpt_lk', 'pengguna_dpt_pr',
        'pengguna_dptb_lk', 'pengguna_dptb_pr',
        'pengguna_dpk_lk', 'pengguna_dpk_pr',
        'ss_diterima', 'ss_digunakan', 'ss_rusak', 'ss_sisa',
        'disabilitas_lk', 'disabilitas_pr',
        'suara_tidak_sah', 'status', 'diinput_oleh', 'difinalisasi_at',
    ];

    protected $casts = ['difinalisasi_at' => 'datetime'];

    const JENIS_LABELS = [
    'ppwp'      => 'Presiden & Wakil Presiden',
    'gubernur'  => 'Gubernur & Wakil Gubernur',
    'bupati'    => 'Bupati & Wakil Bupati',
    'dpd'       => 'DPD',
    'dpr_ri'    => 'DPR RI',
    'dprd_prov' => 'DPRD Provinsi',
    'dprd_kab'  => 'DPRD Kabupaten',
    ];

    // Relasi TPS pemilik rekap.
    public function tps()         { return $this->belongsTo(Tps::class); }
    // Relasi user yang menginput rekap.
    public function inputBy()     { return $this->belongsTo(User::class, 'diinput_oleh'); }
    // Relasi suara PPWP.
    public function ppwpSuaras()  { return $this->hasMany(RekapPpwpSuara::class, 'rekap_id'); }
    // Relasi suara DPD.
    public function dpdSuaras()   { return $this->hasMany(RekapDpdSuara::class, 'rekap_id'); }
    // Relasi suara partai.
    public function partaiSuaras(){ return $this->hasMany(RekapPartaiSuara::class, 'rekap_id'); }
    // Relasi suara caleg.
    public function calegSuaras() { return $this->hasMany(RekapCalegSuara::class, 'rekap_id'); }
    // Relasi suara gubernur.
    public function gubernurSuaras() { return $this->hasMany(RekapGubernurSuara::class, 'rekap_id'); }
    // Relasi suara bupati.
    public function bupatiSuaras()   { return $this->hasMany(RekapBupatiSuara::class,   'rekap_id'); }

    // Menghitung total suara sah.
    public function getSuaraSahAttribute(): int {
        return match($this->jenis) {
            'ppwp'      => $this->ppwpSuaras->sum('suara'),
            'gubernur'  => $this->gubernurSuaras->sum('suara'),
            'bupati'    => $this->bupatiSuaras->sum('suara'),
            'dpd'       => $this->dpdSuaras->sum('suara'),
            default     => $this->partaiSuaras->sum('suara') + $this->calegSuaras->sum('suara'),
        };
    }

    // Menghitung total pengguna hak pilih laki-laki.
    public function getTotalPenggunaLkAttribute(): int {
        return $this->pengguna_dpt_lk + $this->pengguna_dptb_lk + $this->pengguna_dpk_lk;
    }
    // Menghitung total pengguna hak pilih perempuan.
    public function getTotalPenggunaPrAttribute(): int {
        return $this->pengguna_dpt_pr + $this->pengguna_dptb_pr + $this->pengguna_dpk_pr;
    }
}
