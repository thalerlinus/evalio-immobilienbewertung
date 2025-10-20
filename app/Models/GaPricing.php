<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GaPricing extends Model
{
    protected $fillable = [
        'key',
        'label',
        'price_eur',
    ];

    protected $casts = [
        'price_eur' => 'integer',
    ];
}
