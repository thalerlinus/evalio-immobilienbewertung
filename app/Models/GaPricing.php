<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GaPricing extends Model
{
    protected $fillable = [
        'key',
        'label',
        'category',
        'sort_order',
        'price_eur',
    ];

    protected $casts = [
        'price_eur' => 'integer',
        'sort_order' => 'integer',
    ];
}
