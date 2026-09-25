<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ContactGroup;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalClients = 0;
        $totalGroups = 0;
        $totalOrders = 0;
        $totalRevenue = 0.0;
        $statusCounts = [];
        $recentClients = collect();
        $recentOrders = collect();
        $groups = collect();

        try {
            $totalClients = Client::count();
        } catch (Throwable $e) {
            Log::warning("Dashboard totalClients error: " . $e->getMessage());
        }

        try {
            $totalGroups = ContactGroup::count();
        } catch (Throwable $e) {
            Log::warning("Dashboard totalGroups error: " . $e->getMessage());
        }

        try {
            $totalOrders = Order::count();
            $totalRevenue = (float) Order::sum('total_amount');
        } catch (Throwable $e) {
            Log::warning("Dashboard orders error: " . $e->getMessage());
        }

        try {
            $recentClients = Client::orderBy('updated_at', 'desc')->take(8)->get();
        } catch (Throwable $e) {
            try {
                $recentClients = Client::take(8)->get();
            } catch (Throwable $e2) {
                Log::warning("Dashboard recentClients error: " . $e2->getMessage());
            }
        }

        try {
            $recentOrders = Order::orderBy('date_created', 'desc')->take(6)->get();
        } catch (Throwable $e) {
            try {
                $recentOrders = Order::orderBy('id', 'desc')->take(6)->get();
            } catch (Throwable $e2) {
                Log::warning("Dashboard recentOrders error: " . $e2->getMessage());
            }
        }

        try {
            $groups = ContactGroup::withCount('clients')->get();
        } catch (Throwable $e) {
            try {
                $groups = ContactGroup::get();
            } catch (Throwable $e2) {
                Log::warning("Dashboard groups error: " . $e2->getMessage());
            }
        }

        return view('dashboard', compact(
            'totalClients',
            'totalGroups',
            'totalOrders',
            'totalRevenue',
            'statusCounts',
            'recentClients',
            'recentOrders',
            'groups'
        ));
    }
}

