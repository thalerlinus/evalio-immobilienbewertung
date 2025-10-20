<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntervalRule extends Model
{
    protected $fillable = [
        'method',
        'param1',
        'param2',
        'note',
    ];

    protected $casts = [
        'param1' => 'decimal:3',
        'param2' => 'decimal:3',
    ];
}
