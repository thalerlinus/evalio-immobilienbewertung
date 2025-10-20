<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferAttachment extends Model
{
    protected $fillable = [
        'offer_id',
        'disk',
        'path',
        'original_name',
        'size_bytes',
    ];

    protected $casts = [
        'offer_id' => 'integer',
        'size_bytes' => 'integer',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
