<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ContactGroup;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalClients = Client::count();
        $totalGroups = ContactGroup::count();
        $totalOrders = Order::count();
        $totalRevenue = Order::sum('total_amount');

        $statusCounts = Client::selectRaw('estado, count(*) as count, sum(monto_cotizacion) as total_monto')
            ->groupBy('estado')
            ->pluck('count', 'estado')
            ->toArray();

        $recentClients = Client::orderBy('updated_at', 'desc')->take(8)->get();
        $recentOrders = Order::orderBy('date_created', 'desc')->take(6)->get();

        $groups = ContactGroup::withCount('clients')->get();

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
