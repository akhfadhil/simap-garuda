<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekapCellFlag extends Model
{
    protected $fillable = [
        'jenis',
        'level',
        'entity_id',
        'row_key',
        'flagged_by',
    ];
}
