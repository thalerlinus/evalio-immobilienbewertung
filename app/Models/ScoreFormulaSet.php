<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoreFormulaSet extends Model
{
    protected $fillable = [
        'score',
        'a',
        'b',
        'c',
        'alter_schwelle',
        'rel_alter_min',
    ];

    protected $casts = [
        'score' => 'decimal:1',
        'a' => 'decimal:4',
        'b' => 'decimal:4',
        'c' => 'decimal:4',
        'alter_schwelle' => 'integer',
        'rel_alter_min' => 'decimal:2',
    ];
}
