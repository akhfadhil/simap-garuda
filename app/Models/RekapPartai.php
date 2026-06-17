<?php

namespace App\Models;

use App\Support\PartyConfig;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RekapPartai extends Model
{
    protected $fillable = ['jenis', 'nomor_urut', 'nama_partai', 'dapil_id'];

    const JENIS = ['dpr_ri', 'dprd_prov', 'dprd_kab'];

    public function scopeConfiguredParty(Builder $query): Builder
    {
        return PartyConfig::applyPartyQuery($query);
    }

    public function scopeGaruda(Builder $query): Builder
    {
        return $this->scopeConfiguredParty($query);
    }

    public function isConfiguredParty(): bool
    {
        return PartyConfig::matchesParty($this->nomor_urut, $this->nama_partai);
    }

    public function isGaruda(): bool
    {
        return $this->isConfiguredParty();
    }

    // Relasi caleg dalam partai.
    public function calegs()
    {
        return $this->hasMany(RekapCaleg::class, 'partai_id')->orderBy('nomor_urut');
    }

    // Relasi suara partai.
    public function suaras()
    {
        return $this->hasMany(RekapPartaiSuara::class, 'partai_id');
    }

    // Relasi dapil untuk DPRD kabupaten.
    public function dapil()
    {
        return $this->belongsTo(Dapil::class);
    }
}
