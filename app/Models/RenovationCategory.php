<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RenovationCategory extends Model
{
    protected $fillable = [
        'key',
        'label',
        'max_points',
    ];

    protected $casts = [
        'max_points' => 'decimal:1',
    ];

    public function timeFactors(): HasMany
    {
        return $this->hasMany(RenovationTimeFactor::class);
    }
}
