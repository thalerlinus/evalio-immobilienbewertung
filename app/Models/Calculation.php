<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Calculation extends Model
{
    protected $fillable = [
        'user_id',
        'property_type_id',
        'gnd',
        'baujahr',
        'anschaffungsjahr',
        'steuerjahr',
        'ermittlungsjahr',
        'alter',
        'score',
        'score_details',
        'inputs',
        'result_debug',
        'rnd_years',
        'rnd_min',
        'rnd_max',
        'afa_percent',
        'recommendation',
        'status',
        'public_ref',
    ];

    protected $appends = [
        'rnd_interval_label',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'property_type_id' => 'integer',
        'gnd' => 'integer',
        'baujahr' => 'integer',
        'anschaffungsjahr' => 'integer',
        'steuerjahr' => 'integer',
        'ermittlungsjahr' => 'integer',
        'alter' => 'integer',
        'score' => 'decimal:1',
        'score_details' => 'array',
        'inputs' => 'array',
        'result_debug' => 'array',
        'rnd_years' => 'decimal:2',
        'rnd_min' => 'integer',
        'rnd_max' => 'integer',
        'afa_percent' => 'decimal:2',
    ];

    public function getRndIntervalLabelAttribute(): ?string
    {
        $min = $this->rnd_min;
        $max = $this->rnd_max;

        if ($min === null && $max === null) {
            return null;
        }

        if ($min !== null && $max !== null) {
            if ($min === $max) {
                return sprintf('rd. %d Jahre', $min);
            }

            return sprintf('rd. %d – %d Jahre', $min, $max);
        }

        if ($min !== null) {
            return sprintf('rd. %d Jahre', $min);
        }

        if ($max !== null) {
            return sprintf('rd. %d Jahre', $max);
        }

        return null;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($calculation) {
            if (empty($calculation->public_ref)) {
                $calculation->public_ref = Str::random(32);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
