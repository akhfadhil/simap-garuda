<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pendukung extends Model
{
    protected $table = 'pendukungs';

    protected $fillable = [
        'nama',
        'nik',
        'no_hp',
        'alamat',
        'kecamatan_id',
        'desa_id',
        'tps_id',
        'ktp_path',
        'catatan',
        'created_by',
    ];

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    public function tps(): BelongsTo
    {
        return $this->belongsTo(Tps::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
