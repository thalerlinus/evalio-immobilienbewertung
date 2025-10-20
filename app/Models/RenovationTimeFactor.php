<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RenovationTimeFactor extends Model
{
    protected $fillable = [
        'renovation_category_id',
        'time_window_key',
        'factor',
    ];

    protected $casts = [
        'renovation_category_id' => 'integer',
        'factor' => 'decimal:2',
    ];

    public function renovationCategory(): BelongsTo
    {
        return $this->belongsTo(RenovationCategory::class);
    }
}
