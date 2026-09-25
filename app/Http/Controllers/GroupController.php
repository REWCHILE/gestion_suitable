<?php

namespace App\Http\Controllers;

use App\Models\ContactGroup;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    /**
     * Listado de grupos con conteo de clientes y lista de clientes de WooCommerce
     */
    public function index(): View
    {
        $groups = ContactGroup::withCount('clients')
            ->orderBy('id', 'desc')
            ->get();

        // Obtener clientes únicos registrados en órdenes de WooCommerce
        $wooCustomers = Order::select(
                'customer_email',
                DB::raw('MAX(customer_name) as customer_name'),
                DB::raw('MAX(customer_city) as customer_city'),
                DB::raw('SUM(total_amount) as total_spent'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('MAX(date_created) as last_order_date')
            )
            ->whereNotNull('customer_email')
            ->where('customer_email', '!=', '')
            ->groupBy('customer_email')
            ->orderBy('total_spent', 'desc')
            ->get();

        // Obtener clientes existentes del CRM
        $crmClients = Client::select('id', 'empresa', 'contacto_nombre', 'email', 'region_comuna')
            ->orderBy('empresa', 'asc')
            ->get();

        return view('groups.index', compact('groups', 'wooCustomers', 'crmClients'));
    }

    /**
     * Guarda un nuevo grupo e incorpora los clientes de WooCommerce y CRM seleccionados
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
            'include_all_wc' => 'nullable|boolean',
            'wc_emails' => 'nullable|array',
            'client_ids' => 'nullable|array'
        ]);

        $group = ContactGroup::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'color' => $validated['color'] ?: '#1E8888'
        ]);

        $clientIds = [];

        // 1. Clientes seleccionados de WooCommerce
        $wcEmails = $request->input('wc_emails', []);
        if ($request->boolean('include_all_wc')) {
            $wcEmails = Order::whereNotNull('customer_email')
                ->where('customer_email', '!=', '')
                ->distinct()
                ->pluck('customer_email')
                ->toArray();
        }

        foreach ($wcEmails as $email) {
            $client = $this->syncWooCustomerToClient($email);
            if ($client) {
                $clientIds[] = $client->id;
            }
        }

        // 2. Clientes seleccionados del CRM
        $crmIds = $request->input('client_ids', []);
        if (!empty($crmIds) && is_array($crmIds)) {
            $clientIds = array_merge($clientIds, $crmIds);
        }

        $uniqueIds = array_unique(array_filter($clientIds));
        if (!empty($uniqueIds)) {
            $group->clients()->sync($uniqueIds);
        }

        $totalMembers = count($uniqueIds);

        return response()->json([
            'success' => true,
            'message' => "¡Grupo '{$group->name}' creado exitosamente con {$totalMembers} contactos!",
            'group' => $group,
            'members_count' => $totalMembers
        ]);
    }

    /**
     * Agrega clientes de WooCommerce a un grupo ya existente
     */
    public function addWcClients(Request $request, ContactGroup $group): JsonResponse
    {
        $wcEmails = $request->input('wc_emails', []);
        if ($request->boolean('include_all_wc')) {
            $wcEmails = Order::whereNotNull('customer_email')
                ->where('customer_email', '!=', '')
                ->distinct()
                ->pluck('customer_email')
                ->toArray();
        }

        $clientIds = [];
        foreach ($wcEmails as $email) {
            $client = $this->syncWooCustomerToClient($email);
            if ($client) {
                $clientIds[] = $client->id;
            }
        }

        $uniqueIds = array_unique(array_filter($clientIds));
        if (!empty($uniqueIds)) {
            $group->clients()->syncWithoutDetaching($uniqueIds);
        }

        $currentTotal = $group->clients()->count();

        return response()->json([
            'success' => true,
            'message' => "Se agregaron " . count($uniqueIds) . " clientes de WooCommerce al grupo '{$group->name}'. Total actual: {$currentTotal} miembros.",
            'total_members' => $currentTotal
        ]);
    }

    /**
     * Elimina un grupo
     */
    public function destroy(ContactGroup $group): JsonResponse
    {
        $group->delete();
        return response()->json([
            'success' => true,
            'message' => 'Grupo eliminado correctamente'
        ]);
    }

    /**
     * Sincroniza o crea un contacto Client en la base de datos a partir de un cliente de WooCommerce
     */
    protected function syncWooCustomerToClient(string $email): ?Client
    {
        $email = trim(strtolower($email));
        if (empty($email)) {
            return null;
        }

        $client = Client::where('email', $email)->first();

        $wooStats = Order::where('customer_email', $email)
            ->selectRaw('MAX(customer_name) as name, MAX(customer_city) as city, SUM(total_amount) as total_spent, COUNT(*) as orders_count')
            ->first();

        $name = $wooStats && !empty($wooStats->name) ? trim($wooStats->name) : '';
        if (empty($name) || str_starts_with(strtolower($name), 'ventas@suitable')) {
            $parts = explode('@', $email);
            $name = ucwords(str_replace(['.', '_', '-'], ' ', $parts[0]));
        }
        $city = $wooStats && !empty($wooStats->city) ? trim($wooStats->city) : 'Santiago';
        $totalSpent = $wooStats ? (float)$wooStats->total_spent : 0;
        $ordersCount = $wooStats ? (int)$wooStats->orders_count : 1;

        if (!$client) {
            $client = Client::create([
                'empresa' => $name,
                'contacto_nombre' => $name,
                'email' => $email,
                'region_comuna' => $city,
                'estado' => 'cliente_activo',
                'monto_cotizacion' => $totalSpent,
                'notas' => "Cliente importado desde tienda online WooCommerce ({$ordersCount} compras registradas)"
            ]);
        } else {
            if ($client->monto_cotizacion <= 0 && $totalSpent > 0) {
                $client->update([
                    'monto_cotizacion' => $totalSpent,
                    'region_comuna' => $client->region_comuna ?: $city
                ]);
            }
        }

        return $client;
    }
}

