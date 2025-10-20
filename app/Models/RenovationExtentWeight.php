<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RenovationExtentWeight extends Model
{
    protected $fillable = [
        'extent_percent',
        'weight',
    ];

    protected $casts = [
        'extent_percent' => 'integer',
        'weight' => 'decimal:2',
    ];
}
