<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchTrend extends Model
{
    protected $table = 'search_trends';
    public $timestamps = false;

    protected $fillable = [
        'keyword',
        'search_volume',
        'growth_rate',
        'category',
        'period_type',
        'intent_level',
        'updated_at'
    ];

    protected $casts = [
        'search_volume' => 'integer',
        'growth_rate' => 'decimal:2',
        'updated_at' => 'datetime'
    ];
}
