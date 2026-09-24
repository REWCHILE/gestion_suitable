<?php

namespace App\Http\Controllers;

use App\Models\SearchTrend;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MlBrainController extends Controller
{
    public function index(): View
    {
        $trends = SearchTrend::orderBy('search_volume', 'desc')->get();
        $totalOrders = Order::count();
        $totalRevenue = Order::sum('total_amount');

        return view('ml_brain.index', compact('trends', 'totalOrders', 'totalRevenue'));
    }
}
