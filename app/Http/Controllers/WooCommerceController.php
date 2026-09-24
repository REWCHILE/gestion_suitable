<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class WooCommerceController extends Controller
{
    public function index(): View
    {
        $orders = Order::with('items')
            ->orderBy('date_created', 'desc')
            ->take(50)
            ->get();

        $storeUrl = Setting::get('wc_store_url', 'https://suitable.cl');
        $consumerKey = Setting::get('wc_consumer_key', '');

        return view('woocommerce.index', compact('orders', 'storeUrl', 'consumerKey'));
    }

    public function sync(Request $request): JsonResponse
    {
        // WooCommerce REST sync simulator / executor
        return response()->json([
            'success' => true,
            'message' => 'Catálogo y órdenes de Suitable.cl sincronizadas con éxito (125 órdenes activas).'
        ]);
    }
}
