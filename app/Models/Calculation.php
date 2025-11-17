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
        'afa_percent_from',
        'afa_percent_to',
        'afa_percent_label',
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
        $displayMinThreshold = 15;
        $displayMaxThreshold = 25;

        if ($min === null && $max === null) {
            return null;
        }

        $shouldClampToMinimumDisplay = ($max !== null && $max < $displayMaxThreshold)
            || ($min !== null && $min < $displayMinThreshold);

        if ($shouldClampToMinimumDisplay) {
            $min = $displayMinThreshold;
            $max = $displayMaxThreshold;
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

    public function getAfaPercentFromAttribute(): ?float
    {
        $denominator = $this->rnd_max ?? $this->rnd_years;

        if (! $denominator || $denominator <= 0) {
            return null;
        }

        return round(100 / $denominator, 2);
    }

    public function getAfaPercentToAttribute(): ?float
    {
        $denominator = $this->rnd_min ?? $this->rnd_years;

        if (! $denominator || $denominator <= 0) {
            return null;
        }

        return round(100 / $denominator, 2);
    }

    public function getAfaPercentLabelAttribute(): ?string
    {
        $from = $this->afa_percent_from;
        $to = $this->afa_percent_to;

        if ($from === null && $to === null) {
            return null;
        }

        if ($from !== null && $to !== null) {
            if (abs($from - $to) < 0.01) {
                return sprintf('rd. %s %%', number_format($from, 2, ',', '.'));
            }

            return sprintf('rd. %s – %s %%', number_format($from, 2, ',', '.'), number_format($to, 2, ',', '.'));
        }

        $value = $from ?? $to;

        return $value !== null ? sprintf('rd. %s %%', number_format($value, 2, ',', '.')) : null;
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
