<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RekapPartai extends Model
{
    protected $fillable = ['jenis', 'nomor_urut', 'nama_partai', 'dapil_id'];

    const JENIS = ['dpr_ri', 'dprd_prov', 'dprd_kab'];

    public function scopeGaruda(Builder $query): Builder
    {
        $party = config('party');
        $numbers = collect($party['historical_numbers'] ?? [])
            ->map(fn ($number) => (int) $number)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $query->where(function (Builder $query) use ($party, $numbers) {
            $query->where('nama_partai', 'like', '%'.($party['short_name'] ?? 'Garuda').'%')
                ->orWhere('nama_partai', 'like', '%'.($party['name'] ?? 'Partai Garuda').'%');

            if ($numbers) {
                $query->orWhereIn('nomor_urut', $numbers);
            }
        });
    }

    public function isGaruda(): bool
    {
        $party = config('party');
        $numbers = collect($party['historical_numbers'] ?? [])
            ->map(fn ($number) => (int) $number)
            ->filter()
            ->unique();
        $name = mb_strtolower($this->nama_partai);

        return $numbers->contains((int) $this->nomor_urut)
            || str_contains($name, mb_strtolower($party['short_name'] ?? 'Garuda'))
            || str_contains($name, mb_strtolower($party['name'] ?? 'Partai Garuda'));
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
