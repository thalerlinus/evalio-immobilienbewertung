<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecommendationRule extends Model
{
    protected $fillable = [
        'threshold_logic',
        'text',
        'sort',
    ];

    protected $casts = [
        'sort' => 'integer',
    ];
}
