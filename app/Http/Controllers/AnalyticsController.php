<?php

namespace App\Http\Controllers;

use App\Models\TrafficMetric;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $metrics = TrafficMetric::orderBy('period_date', 'desc')->take(30)->get();

        $totalSessions = $metrics->sum('sessions');
        $totalOrders = $metrics->sum('orders_count');
        $totalRevenue = $metrics->sum('revenue');
        $totalAdSpend = $metrics->sum('ad_spend');
        $avgCtr = $metrics->avg('ctr');
        $avgCpa = $totalOrders > 0 ? round($totalAdSpend / $totalOrders) : 0;
        $avgAov = $totalOrders > 0 ? round($totalRevenue / $totalOrders) : 0;

        return view('analytics.index', compact(
            'metrics',
            'totalSessions',
            'totalOrders',
            'totalRevenue',
            'totalAdSpend',
            'avgCtr',
            'avgCpa',
            'avgAov'
        ));
    }
}
