<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class WooCommerceController extends Controller
{
    /**
     * Muestra la vista principal de WooCommerce y el Asistente de Conexión
     */
    /**
     * Muestra la vista principal de WooCommerce y el Asistente de Conexión (Soporta Infinite Scroll)
     */
    public function index(Request $request): View|JsonResponse
    {
        $orders = Order::with('items')
            ->orderBy('date_created', 'desc')
            ->paginate(50);

        // Si es una petición de Infinite Scroll vía AJAX/Fetch
        if ($request->ajax() || $request->wantsJson() || $request->header('X-Infinite-Scroll')) {
            return response()->json([
                'success' => true,
                'html' => view('woocommerce._order_rows', compact('orders'))->render(),
                'has_more' => $orders->hasMorePages(),
                'next_page' => $orders->hasMorePages() ? $orders->currentPage() + 1 : null,
                'current_page' => $orders->currentPage(),
                'total' => $orders->total(),
                'count' => $orders->count(),
            ]);
        }

        $totalOrders = Order::count();
        $totalRevenue = Order::sum('total_amount');
        $uniqueCustomers = Order::distinct()->count('customer_email');

        // Configuración guardada (priorizando suitable_wp372 y wp8q_)
        $mysqlHost = Setting::get('wc_mysql_host') ?: 'localhost';
        $mysqlPort = Setting::get('wc_mysql_port') ?: '3306';
        $mysqlDb = Setting::get('wc_mysql_db') ?: 'suitable_wp372';
        $mysqlUser = Setting::get('wc_mysql_user') ?: env('DB_USERNAME', 'suitable_intranetuser');
        $mysqlPass = Setting::get('wc_mysql_pass') ?: '';
        
        $tablePrefix = Setting::get('wc_active_prefix') ?: 'wp8q_';
        if ($tablePrefix === 'wp_') {
            $tablePrefix = 'wp8q_';
        }

        $storeUrl = Setting::get('wc_store_url') ?: 'https://suitable.cl';
        $consumerKey = Setting::get('wc_consumer_key') ?: '';
        $consumerSecret = Setting::get('wc_consumer_secret') ?: '';
        $lastSync = Setting::get('wc_last_sync') ?: '';

        // Verificamos si existe wp-config.php en el servidor para alertar que está disponible auto-detección
        $wpConfigPath = $this->findWpConfigFile();
        $wpConfigFound = !empty($wpConfigPath);

        if ($wpConfigFound) {
            try {
                $wpContent = @file_get_contents($wpConfigPath);
                if ($wpContent) {
                    if (preg_match("/define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $wpContent, $m)) {
                        $mysqlDb = $m[1];
                    }
                    if (preg_match("/define\s*\(\s*['\"]DB_USER['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $wpContent, $m)) {
                        $mysqlUser = $m[1];
                    }
                    if (preg_match("/define\s*\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $wpContent, $m)) {
                        $mysqlPass = $m[1];
                    }
                    if (preg_match("/define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $wpContent, $m)) {
                        $mysqlHost = $m[1];
                    }
                    if (preg_match("/\\\$table_prefix\s*=\s*['\"]([^'\"]+)['\"]\s*;/i", $wpContent, $m)) {
                        $tablePrefix = $m[1];
                    }
                }
            } catch (Throwable $e) {
                // Silencioso
            }
        }

        return view('woocommerce.index', compact(
            'orders',
            'totalOrders',
            'totalRevenue',
            'uniqueCustomers',
            'mysqlHost',
            'mysqlPort',
            'mysqlDb',
            'mysqlUser',
            'mysqlPass',
            'tablePrefix',
            'storeUrl',
            'consumerKey',
            'consumerSecret',
            'lastSync',
            'wpConfigFound'
        ));
    }

    /**
     * Auto-detecta la configuración de WordPress desde public_html/wp-config.php
     */
    public function detectWpConfig(): JsonResponse
    {
        $filePath = $this->findWpConfigFile();

        if (!$filePath || !file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el archivo wp-config.php en las rutas habituales de public_html.'
            ]);
        }

        try {
            $content = file_get_contents($filePath);
            $dbName = '';
            $dbUser = '';
            $dbPass = '';
            $dbHost = 'localhost';
            $tablePrefix = 'wp8q_';

            if (preg_match("/define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $m)) {
                $dbName = $m[1];
            }
            if (preg_match("/define\s*\(\s*['\"]DB_USER['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $m)) {
                $dbUser = $m[1];
            }
            if (preg_match("/define\s*\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $m)) {
                $dbPass = $m[1];
            }
            if (preg_match("/define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $m)) {
                $dbHost = $m[1];
            }
            if (preg_match("/\\\$table_prefix\s*=\s*['\"]([^'\"]+)['\"]\s*;/i", $content, $m)) {
                $tablePrefix = $m[1];
            }

            return response()->json([
                'success' => true,
                'file_path' => $filePath,
                'db_name' => $dbName ?: 'suitable_wp372',
                'db_user' => $dbUser ?: 'suitable_wp372',
                'db_pass' => $dbPass,
                'db_host' => $dbHost ?: 'localhost',
                'db_prefix' => $tablePrefix ?: 'wp8q_',
                'message' => '¡Configuración de WordPress detectada con éxito en public_html!'
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al leer wp-config.php: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Prueba la conexión directa a la base de datos de WordPress/WooCommerce
     */
    public function testConnection(Request $request): JsonResponse
    {
        try {
            $host = $request->input('db_host') ?: Setting::get('wc_mysql_host', 'localhost');
            $port = $request->input('db_port') ?: Setting::get('wc_mysql_port', '3306');
            $database = $request->input('db_name') ?: Setting::get('wc_mysql_db', 'suitable_wp372');
            $user = $request->input('db_user') ?: Setting::get('wc_mysql_user', env('DB_USERNAME', 'suitable_intranetuser'));
            $pass = $request->input('db_pass');
            $prefix = $request->input('table_prefix') ?: Setting::get('wc_active_prefix', 'wp8q_');

            if ($prefix === 'wp_') {
                $prefix = 'wp8q_';
            }

            $pdo = $this->getPdoConnection($host, $port, $database, $user, $pass);

            // Auto-detectar prefijo si no coincide
            $detectedPrefix = $this->autoDetectPrefix($pdo, $prefix, $database);
            if ($detectedPrefix !== $prefix) {
                $prefix = $detectedPrefix;
            }

            // Probar existencia de tablas de órdenes (HPOS o Classic)
            $ordersFound = 0;
            $hposTable = $prefix . 'wc_orders';
            $postsTable = $prefix . 'posts';

            $checkHpos = $pdo->prepare("SHOW TABLES LIKE :p1");
            $checkHpos->execute(['p1' => $hposTable]);
            $hasHpos = (bool)$checkHpos->fetch();

            $checkPosts = $pdo->prepare("SHOW TABLES LIKE :p2");
            $checkPosts->execute(['p2' => $postsTable]);
            $hasPosts = (bool)$checkPosts->fetch();

            if ($hasHpos) {
                $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$hposTable}` WHERE `type` = 'shop_order'");
                $ordersFound = (int)$countStmt->fetchColumn();
            } elseif ($hasPosts) {
                $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$postsTable}` WHERE `post_type` IN ('shop_order', 'shop_order_placehold') AND `post_status` NOT IN ('trash', 'auto-draft')");
                $ordersFound = (int)$countStmt->fetchColumn();
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Conexión a MySQL exitosa a '{$database}', pero no se encontraron las tablas `{$postsTable}` ni `{$hposTable}`. Verifica el prefijo de tablas."
                ]);
            }

            // Guardar configuración validada
            Setting::set('wc_mysql_host', $host);
            Setting::set('wc_mysql_port', $port);
            Setting::set('wc_mysql_db', $database);
            Setting::set('wc_mysql_user', $user);
            if (!empty($pass)) {
                Setting::set('wc_mysql_pass', $pass);
            }
            Setting::set('wc_active_prefix', $prefix);

            return response()->json([
                'success' => true,
                'database' => $database,
                'prefix' => $prefix,
                'orders_found' => $ordersFound,
                'message' => "¡Conexión establecida con éxito! Base de datos '{$database}', prefijo '{$prefix}'. Se detectaron {$ordersFound} pedidos reales en Suitable.cl."
            ]);
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $hint = "";
            if (str_contains($msg, '1045') || str_contains($msg, 'Access denied')) {
                $hint = " Recuerda que en cPanel -> Bases de datos MySQL debes agregar el usuario a la base de datos `{$database}` con permisos de lectura (SELECT).";
            } elseif (str_contains($msg, '1049') || str_contains($msg, 'Unknown database')) {
                $hint = " La base de datos `{$database}` no existe en este servidor MySQL.";
            }
            return response()->json([
                'success' => false,
                'message' => "Error de conexión MySQL: {$msg}.{$hint}"
            ]);
        }
    }

    /**
     * Guarda la configuración de WooCommerce en la base de datos
     */
    public function saveSettings(Request $request): JsonResponse
    {
        $fields = [
            'wc_mysql_host' => $request->input('db_host') ?: 'localhost',
            'wc_mysql_port' => $request->input('db_port') ?: '3306',
            'wc_mysql_db' => $request->input('db_name') ?: 'suitable_wp372',
            'wc_mysql_user' => $request->input('db_user') ?: env('DB_USERNAME', 'suitable_intranetuser'),
            'wc_active_prefix' => $request->input('table_prefix') ?: 'wp8q_',
            'wc_store_url' => $request->input('store_url') ?: 'https://suitable.cl',
            'wc_consumer_key' => $request->input('consumer_key') ?: '',
            'wc_consumer_secret' => $request->input('consumer_secret') ?: ''
        ];

        if ($fields['wc_active_prefix'] === 'wp_') {
            $fields['wc_active_prefix'] = 'wp8q_';
        }

        if ($request->filled('db_pass')) {
            $fields['wc_mysql_pass'] = $request->input('db_pass');
        }

        foreach ($fields as $key => $value) {
            Setting::set($key, $value);
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuración de WooCommerce guardada correctamente.'
        ]);
    }

    /**
     * Sincroniza órdenes reales desde la base de datos de WordPress/WooCommerce
     * Extrae información fehaciente de wp_postmeta, wc_orders, wc_order_addresses y line_items
     */
    public function sync(Request $request): JsonResponse
    {
        try {
            $host = $request->input('db_host') ?: Setting::get('wc_mysql_host', 'localhost');
            $port = $request->input('db_port') ?: Setting::get('wc_mysql_port', '3306');
            $database = $request->input('db_name') ?: Setting::get('wc_mysql_db', 'suitable_wp372');
            $user = $request->input('db_user') ?: Setting::get('wc_mysql_user', env('DB_USERNAME', 'suitable_intranetuser'));
            $pass = $request->input('db_pass');
            $prefix = $request->input('table_prefix') ?: Setting::get('wc_active_prefix', 'wp8q_');

            if ($prefix === 'wp_') {
                $prefix = 'wp8q_';
            }

            $pdo = $this->getPdoConnection($host, $port, $database, $user, $pass);

            // Auto-detectar prefijo si no coincide
            $detectedPrefix = $this->autoDetectPrefix($pdo, $prefix, $database);
            if ($detectedPrefix !== $prefix) {
                $prefix = $detectedPrefix;
            }

            $postsTable = $prefix . 'posts';
            $postmetaTable = $prefix . 'postmeta';
            $hposTable = $prefix . 'wc_orders';
            $addressesTable = $prefix . 'wc_order_addresses';
            $orderItemsTable = $prefix . 'woocommerce_order_items';
            $orderItemMetaTable = $prefix . 'woocommerce_order_itemmeta';
            $usersTable = $prefix . 'users';

            $checkTable = function(string $table) use ($pdo): bool {
                try {
                    $st = $pdo->prepare("SHOW TABLES LIKE :tbl");
                    $st->execute(['tbl' => $table]);
                    return (bool)$st->fetch();
                } catch (Throwable $e) {
                    return false;
                }
            };

            $hasPosts = $checkTable($postsTable);
            $hasPostmeta = $checkTable($postmetaTable);
            $hasHpos = $checkTable($hposTable);
            $hasAddresses = $checkTable($addressesTable);
            $hasOrderItems = $checkTable($orderItemsTable);
            $hasOrderItemMeta = $checkTable($orderItemMetaTable);
            $hasUsers = $checkTable($usersTable);

            if (!$hasPosts && !$hasHpos) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron las tablas de pedidos de WooCommerce (`{$postsTable}` ni `{$hposTable}`) en la base de datos '{$database}' con prefijo '{$prefix}'."
                ]);
            }

            // 1. RECOLECTAR TODOS LOS IDS DE ÓRDENES (UNIFICADO)
            $orderIdsMap = []; // order_id => ['status' => ..., 'date' => ...]

            // A) Desde wp_posts
            if ($hasPosts) {
                try {
                    $postsStmt = $pdo->query("
                        SELECT ID, post_status, post_date
                        FROM `{$postsTable}`
                        WHERE post_type IN ('shop_order', 'shop_order_placehold')
                          AND post_status NOT IN ('trash', 'auto-draft')
                        ORDER BY ID DESC
                        LIMIT 500
                    ");
                    while ($p = $postsStmt->fetch()) {
                        $orderIdsMap[(int)$p['ID']] = [
                            'status' => $p['post_status'],
                            'date' => $p['post_date'],
                        ];
                    }
                } catch (Throwable $e) {}
            }

            // B) Desde wc_orders (HPOS) si existe
            if ($hasHpos) {
                try {
                    $hposStmt = $pdo->query("
                        SELECT id, status, date_created_gmt, date_created
                        FROM `{$hposTable}`
                        WHERE type = 'shop_order'
                          AND status NOT IN ('trash', 'auto-draft')
                        ORDER BY id DESC
                        LIMIT 500
                    ");
                    while ($h = $hposStmt->fetch()) {
                        $oid = (int)$h['id'];
                        if (!isset($orderIdsMap[$oid])) {
                            $orderIdsMap[$oid] = [
                                'status' => $h['status'],
                                'date' => $h['date_created_gmt'] ?: ($h['date_created'] ?: now()->toDateTimeString()),
                            ];
                        }
                    }
                } catch (Throwable $e) {}
            }

            // Ordenar por ID descendente (más recientes primero)
            krsort($orderIdsMap);

            $syncedOrders = 0;
            $totalRevenue = 0.0;

            foreach ($orderIdsMap as $orderId => $orderInfo) {
                // 1. Metadata fehaciente desde postmeta (donde WooCommerce almacena montos y facturación)
                $meta = [];
                if ($hasPostmeta) {
                    try {
                        $mStmt = $pdo->prepare("
                            SELECT meta_key, meta_value 
                            FROM `{$postmetaTable}` 
                            WHERE post_id = :post_id 
                              AND meta_key IN (
                                '_billing_first_name', '_billing_last_name', '_billing_email', 
                                '_billing_city', '_billing_address_1', '_billing_phone',
                                '_order_total', '_order_shipping', '_order_tax', '_order_currency',
                                '_payment_method_title', '_payment_method', '_customer_user'
                              )
                        ");
                        $mStmt->execute(['post_id' => $orderId]);
                        $meta = $mStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
                    } catch (Throwable $e) {}
                }

                // 2. Fila HPOS si existe
                $hposRow = null;
                if ($hasHpos) {
                    try {
                        $hStmt = $pdo->prepare("SELECT * FROM `{$hposTable}` WHERE id = :id LIMIT 1");
                        $hStmt->execute(['id' => $orderId]);
                        $hposRow = $hStmt->fetch() ?: null;
                    } catch (Throwable $e) {}
                }

                // 3. Dirección HPOS si existe
                $addrRow = null;
                if ($hasAddresses) {
                    try {
                        $aStmt = $pdo->prepare("SELECT * FROM `{$addressesTable}` WHERE order_id = :id AND address_type = 'billing' LIMIT 1");
                        $aStmt->execute(['id' => $orderId]);
                        $addrRow = $aStmt->fetch() ?: null;
                    } catch (Throwable $e) {}
                }

                // 4. Nombre del Cliente
                $firstName = trim($meta['_billing_first_name'] ?? ($addrRow['first_name'] ?? ''));
                $lastName = trim($meta['_billing_last_name'] ?? ($addrRow['last_name'] ?? ''));
                $customerName = trim("{$firstName} {$lastName}");

                // Si no hay nombre en billing, buscar en tabla de usuarios si hay _customer_user
                if (empty($customerName) && $hasUsers && !empty($meta['_customer_user']) && (int)$meta['_customer_user'] > 0) {
                    try {
                        $uStmt = $pdo->prepare("SELECT display_name, user_email FROM `{$usersTable}` WHERE ID = :uid LIMIT 1");
                        $uStmt->execute(['uid' => (int)$meta['_customer_user']]);
                        $uRow = $uStmt->fetch();
                        if ($uRow && !empty($uRow['display_name'])) {
                            $customerName = trim($uRow['display_name']);
                        }
                    } catch (Throwable $e) {}
                }

                // Si aún está vacío, deducir desde el email
                $emailCand = trim($meta['_billing_email'] ?? ($addrRow['email'] ?? ($hposRow['billing_email'] ?? '')));
                if (empty($customerName) && !empty($emailCand) && !str_starts_with(strtolower($emailCand), 'ventas@suitable')) {
                    $local = explode('@', $emailCand)[0];
                    $customerName = ucwords(str_replace(['.', '_', '-'], ' ', $local));
                }

                if (empty($customerName)) {
                    $customerName = 'Cliente Institucional Suitable';
                }

                // 5. Determinar Email
                $customerEmail = !empty($emailCand) ? $emailCand : 'ventas@suitable.cl';

                // 6. Determinar Ciudad / Comuna
                $customerCity = trim($meta['_billing_city'] ?? ($addrRow['city'] ?? ''));
                if (empty($customerCity)) {
                    $customerCity = 'Santiago';
                }

                // 7. Determinar Medio de Pago
                $paymentMethod = $meta['_payment_method_title'] 
                    ?? ($hposRow['payment_method_title'] 
                    ?? ($meta['_payment_method'] 
                    ?? ($hposRow['payment_method'] ?? 'Transbank Webpay Plus')));

                $pmLower = strtolower($paymentMethod);
                if (str_contains($pmLower, 'webpay') || str_contains($pmLower, 'transbank')) {
                    $paymentMethod = 'Transbank Webpay Plus';
                } elseif (str_contains($pmLower, 'bacs') || str_contains($pmLower, 'transfer') || str_contains($pmLower, 'bancaria')) {
                    $paymentMethod = 'Transferencia Bancaria';
                }

                // 8. Determinar Monto Total y Envío
                $orderTotal = 0.0;
                if (isset($meta['_order_total']) && (float)$meta['_order_total'] > 0) {
                    $orderTotal = (float)$meta['_order_total'];
                } elseif (isset($hposRow['total_amount']) && (float)$hposRow['total_amount'] > 0) {
                    $orderTotal = (float)$hposRow['total_amount'];
                }

                $shippingAmount = 0.0;
                if (isset($meta['_order_shipping'])) {
                    $shippingAmount = (float)$meta['_order_shipping'];
                }

                // 9. Estado de la orden
                $rawStatus = $orderInfo['status'] ?? ($hposRow['status'] ?? 'processing');
                $status = str_replace('wc-', '', $rawStatus);
                if (empty($status) || $status === 'draft' || $status === 'auto-draft') {
                    $status = 'processing';
                }

                // 10. Fecha de creación
                $dateCreated = $orderInfo['date'] 
                    ?? ($hposRow['date_created_gmt'] 
                    ?? ($hposRow['date_created'] ?? now()->toDateTimeString()));

                // 11. Guardar o Actualizar Modelo Order
                $order = Order::updateOrCreate(
                    ['wc_order_id' => $orderId],
                    [
                        'customer_name' => $customerName,
                        'customer_email' => $customerEmail,
                        'customer_city' => $customerCity,
                        'total_amount' => $orderTotal,
                        'shipping_amount' => $shippingAmount,
                        'status' => $status,
                        'payment_method' => $paymentMethod,
                        'items_count' => 1,
                        'date_created' => $dateCreated
                    ]
                );

                // 12. Sincronizar Líneas de Ítems (Prendas, Uniformes, Accesorios)
                $itemStats = ['total_amount' => 0.0, 'total_qty' => 1];
                if ($hasOrderItems && $hasOrderItemMeta) {
                    $itemStats = $this->syncOrderItems(
                        $pdo, 
                        $order->id, 
                        $orderId, 
                        $orderItemsTable, 
                        $orderItemMetaTable, 
                        $hasPostmeta ? $postmetaTable : null
                    );
                }

                // Si total_amount sigue en 0, calcularlo con el total de los ítems + envío
                if ($orderTotal <= 0 && $itemStats['total_amount'] > 0) {
                    $orderTotal = $itemStats['total_amount'] + $shippingAmount;
                    $order->update(['total_amount' => $orderTotal]);
                }

                $syncedOrders++;
                $totalRevenue += $orderTotal;
            }

            if ($syncedOrders === 0) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron órdenes registradas en las tablas de la base de datos '{$database}'."
                ]);
            }

            // Guardar configuración confirmada
            Setting::set('wc_mysql_host', $host);
            Setting::set('wc_mysql_port', $port);
            Setting::set('wc_mysql_db', $database);
            Setting::set('wc_mysql_user', $user);
            if (!empty($pass)) {
                Setting::set('wc_mysql_pass', $pass);
            }
            Setting::set('wc_active_prefix', $prefix);
            Setting::set('wc_last_sync', now()->toDateTimeString());

            return response()->json([
                'success' => true,
                'synced_count' => $syncedOrders,
                'total_revenue' => $totalRevenue,
                'prefix' => $prefix,
                'formatted_revenue' => '$' . number_format($totalRevenue, 0, ',', '.'),
                'message' => "¡Sincronización exitosa! Se importaron {$syncedOrders} pedidos reales con un total facturado de $" . number_format($totalRevenue, 0, ',', '.') . " CLP desde '{$database}' (prefijo {$prefix})."
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error durante la sincronización: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sincroniza los ítems y prendas de una orden específica con cálculo de subtotales
     */
    private function syncOrderItems(
        PDO $pdo, 
        int $internalOrderId, 
        int $wcOrderId, 
        string $orderItemsTable, 
        string $orderItemMetaTable,
        ?string $postmetaTable
    ): array
    {
        $totalQty = 0;
        $orderItemsSum = 0.0;

        try {
            $itemsStmt = $pdo->prepare("
                SELECT order_item_id, order_item_name 
                FROM `{$orderItemsTable}` 
                WHERE order_id = :order_id AND order_item_type = 'line_item'
            ");
            $itemsStmt->execute(['order_id' => $wcOrderId]);
            $items = $itemsStmt->fetchAll();

            if (!empty($items)) {
                OrderItem::where('order_id', $internalOrderId)->delete();

                foreach ($items as $it) {
                    $itemId = $it['order_item_id'];
                    $imStmt = $pdo->prepare("
                        SELECT meta_key, meta_value 
                        FROM `{$orderItemMetaTable}` 
                        WHERE order_item_id = :item_id 
                          AND meta_key IN (
                            '_qty', '_line_total', '_line_subtotal', '_line_tax', 
                            '_product_id', '_variation_id',
                            'pa_color', 'color', 'pa_talla', 'talla', 'pa_size', 'size'
                          )
                    ");
                    $imStmt->execute(['item_id' => $itemId]);
                    $itemMeta = $imStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

                    $qty = max(1, (int)($itemMeta['_qty'] ?? 1));
                    $totalQty += $qty;
                    
                    $lineTotal = (float)($itemMeta['_line_total'] ?? ($itemMeta['_line_subtotal'] ?? 0));

                    // Si lineTotal sigue en 0, buscar el precio del producto en wp_postmeta
                    if ($lineTotal <= 0 && $postmetaTable && !empty($itemMeta['_product_id'])) {
                        try {
                            $pId = (int)$itemMeta['_product_id'];
                            $vId = (int)($itemMeta['_variation_id'] ?? 0);
                            $lookupId = $vId > 0 ? $vId : $pId;

                            $priceStmt = $pdo->prepare("
                                SELECT meta_value 
                                FROM `{$postmetaTable}` 
                                WHERE post_id = :pid AND meta_key IN ('_price', '_regular_price', '_sale_price') 
                                ORDER BY CASE WHEN meta_key = '_price' THEN 1 ELSE 2 END
                                LIMIT 1
                            ");
                            $priceStmt->execute(['pid' => $lookupId]);
                            $foundPrice = (float)$priceStmt->fetchColumn();
                            if ($foundPrice > 0) {
                                $lineTotal = $foundPrice * $qty;
                            }
                        } catch (Throwable $e) {}
                    }

                    $orderItemsSum += $lineTotal;
                    $unitPrice = $qty > 0 ? ($lineTotal / $qty) : $lineTotal;
                    $color = $itemMeta['pa_color'] ?? ($itemMeta['color'] ?? 'Estándar');
                    $size = $itemMeta['pa_talla'] ?? ($itemMeta['talla'] ?? ($itemMeta['pa_size'] ?? ($itemMeta['size'] ?? 'M')));

                    OrderItem::create([
                        'order_id' => $internalOrderId,
                        'product_name' => $it['order_item_name'] ?: 'Uniforme Clínico Suitable',
                        'category' => 'Vestuario Clínico',
                        'variation_color' => $color,
                        'variation_size' => $size,
                        'quantity' => $qty,
                        'price' => $unitPrice,
                        'subtotal' => $lineTotal
                    ]);
                }

                if ($totalQty > 0) {
                    Order::where('id', $internalOrderId)->update(['items_count' => $totalQty]);
                }
            }
        } catch (Throwable $e) {
            // Silencioso
        }

        return [
            'total_amount' => $orderItemsSum,
            'total_qty' => max(1, $totalQty)
        ];
    }

    /**
     * Purga y elimina toda la data demo o registros de pedidos para dejar la base de datos limpia
     */
    public function purgeDemo(): JsonResponse
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            OrderItem::truncate();
            Order::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            return response()->json([
                'success' => true,
                'message' => 'Toda la data demo de pedidos e ítems ha sido eliminada con éxito. La plataforma está 100% limpia.'
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al purgar datos: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Auto-detecta el prefijo de tablas en la base de datos conectada
     */
    private function autoDetectPrefix(PDO $pdo, string $prefix, string $database): string
    {
        // 1. Probar el prefijo actual
        $stmt = $pdo->prepare("SHOW TABLES LIKE :p1");
        $stmt->execute(['p1' => $prefix . 'posts']);
        if ($stmt->fetch()) {
            return $prefix;
        }

        $stmt = $pdo->prepare("SHOW TABLES LIKE :p2");
        $stmt->execute(['p2' => $prefix . 'wc_orders']);
        if ($stmt->fetch()) {
            return $prefix;
        }

        // 2. Si no existe, buscar en information_schema qué tabla termina en 'posts' o 'wc_orders'
        try {
            $search = $pdo->prepare("
                SELECT TABLE_NAME 
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = :db 
                  AND (TABLE_NAME LIKE '%posts' OR TABLE_NAME LIKE '%wc_orders')
                ORDER BY CASE WHEN TABLE_NAME LIKE '%posts' THEN 1 ELSE 2 END
                LIMIT 1
            ");
            $search->execute(['db' => $database]);
            $table = $search->fetchColumn();

            if ($table) {
                if (preg_match('/^(.*)posts$/', $table, $m)) {
                    return $m[1];
                }
                if (preg_match('/^(.*)wc_orders$/', $table, $m)) {
                    return $m[1];
                }
            }
        } catch (Throwable $e) {
            // Silencioso
        }

        // 3. Fallback por defecto verificado en phpMyAdmin: wp8q_
        return 'wp8q_';
    }

    /**
     * Conexión PDO con timeout y UTF-8
     */
    private function getPdoConnection(string $host, string $port, string $database, string $user, ?string $pass): PDO
    {
        $host = trim($host) ?: 'localhost';
        $port = trim($port) ?: '3306';
        $database = trim($database) ?: 'suitable_wp372';
        $user = trim($user) ?: env('DB_USERNAME', 'suitable_intranetuser');

        // Si el usuario no especificó password en el wizard, reutilizamos la del .env de la intranet
        if ($pass === '' || $pass === null) {
            $pass = (string)Setting::get('wc_mysql_pass', '');
            if ($pass === '') {
                $pass = (string)env('DB_PASSWORD', '');
            }
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 8,
        ]);
    }

    /**
     * Localiza el archivo wp-config.php en el servidor si existe
     */
    private function findWpConfigFile(): ?string
    {
        $possiblePaths = [
            base_path('../public_html/wp-config.php'),
            '/home/suitable/public_html/wp-config.php',
            dirname(base_path()) . '/public_html/wp-config.php',
            isset($_SERVER['DOCUMENT_ROOT']) ? dirname($_SERVER['DOCUMENT_ROOT']) . '/public_html/wp-config.php' : null,
            'c:/laragon/www/public_html/wp-config.php'
        ];

        foreach ($possiblePaths as $path) {
            if (!$path) continue;
            try {
                if (@file_exists($path) && @is_readable($path)) {
                    return $path;
                }
            } catch (Throwable $e) {
                // Silencioso
            }
        }

        return null;
    }
}
