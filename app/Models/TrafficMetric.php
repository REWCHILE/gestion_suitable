<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficMetric extends Model
{
    protected $table = 'traffic_metrics';
    public $timestamps = false;

    protected $fillable = [
        'period_type',
        'period_date',
        'sessions',
        'visitors',
        'orders_count',
        'revenue',
        'ad_spend',
        'ctr',
        'cpa',
        'cvr',
        'aov'
    ];

    protected $casts = [
        'period_date' => 'date',
        'sessions' => 'integer',
        'visitors' => 'integer',
        'orders_count' => 'integer',
        'revenue' => 'decimal:2',
        'ad_spend' => 'decimal:2',
        'ctr' => 'decimal:2',
        'cpa' => 'decimal:2',
        'cvr' => 'decimal:2',
        'aov' => 'decimal:2'
    ];
}
