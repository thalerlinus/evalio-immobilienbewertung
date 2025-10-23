<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Offer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'number',
        'calculation_id',
        'customer_id',
        'property_type_id',
        'calculation_snapshot',
        'input_snapshot',
        'base_price_eur',
        'inspection_price_eur',
    'discount_code',
    'discount_percent',
    'discount_applied_at',
        'discount_eur',
        'net_total_eur',
        'vat_percent',
        'vat_amount_eur',
        'gross_total_eur',
    'ga_package_key',
    'ga_package_label',
    'ga_package_price_eur',
        'status',
        'sent_at',
        'accepted_at',
        'rejected_at',
        'expires_at',
        'view_token',
        'line_items',
        'notes',
    ];

    protected $casts = [
        'calculation_id' => 'integer',
        'customer_id' => 'integer',
        'property_type_id' => 'integer',
        'calculation_snapshot' => 'array',
        'input_snapshot' => 'array',
        'base_price_eur' => 'integer',
        'inspection_price_eur' => 'integer',
        'discount_percent' => 'integer',
        'discount_eur' => 'integer',
        'net_total_eur' => 'integer',
        'vat_percent' => 'integer',
        'vat_amount_eur' => 'integer',
        'gross_total_eur' => 'integer',
    'ga_package_price_eur' => 'integer',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expires_at' => 'datetime',
        'discount_applied_at' => 'datetime',
        'line_items' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($offer) {
            if (empty($offer->view_token)) {
                $offer->view_token = Str::random(64);
            }
            if (empty($offer->number)) {
                $offer->number = self::generateOfferNumber();
            }
        });
    }

    protected static function generateOfferNumber(): string
    {
        $year = date('Y');
        $lastOffer = self::whereYear('created_at', $year)
            ->orderBy('number', 'desc')
            ->first();

        if ($lastOffer && preg_match('/^AN-(\d{4})-(\d+)$/', $lastOffer->number, $matches)) {
            $nextNumber = (int)$matches[2] + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('AN-%s-%04d', $year, $nextNumber);
    }

    public function calculation(): BelongsTo
    {
        return $this->belongsTo(Calculation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(OfferAttachment::class);
    }
}
