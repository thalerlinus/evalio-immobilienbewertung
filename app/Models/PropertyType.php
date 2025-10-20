<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyType extends Model
{
    protected $fillable = [
        'key',
        'label',
        'gnd',
        'request_only',
        'price_standard_eur',
        'notes',
    ];

    protected $casts = [
        'gnd' => 'integer',
        'request_only' => 'boolean',
        'price_standard_eur' => 'integer',
    ];

    public function calculations(): HasMany
    {
        return $this->hasMany(Calculation::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
