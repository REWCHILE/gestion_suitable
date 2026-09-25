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
    public function index(): View
    {
        $orders = Order::with('items')
            ->orderBy('date_created', 'desc')
            ->paginate(50);

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
            $orderItemsTable = $prefix . 'woocommerce_order_items';
            $orderItemMetaTable = $prefix . 'woocommerce_order_itemmeta';

            $checkHpos = $pdo->prepare("SHOW TABLES LIKE :p1");
            $checkHpos->execute(['p1' => $hposTable]);
            $hasHpos = (bool)$checkHpos->fetch();

            $checkPosts = $pdo->prepare("SHOW TABLES LIKE :p2");
            $checkPosts->execute(['p2' => $postsTable]);
            $hasPosts = (bool)$checkPosts->fetch();

            $checkItems = $pdo->prepare("SHOW TABLES LIKE :p3");
            $checkItems->execute(['p3' => $orderItemsTable]);
            $hasOrderItems = (bool)$checkItems->fetch();

            $checkItemMeta = $pdo->prepare("SHOW TABLES LIKE :p4");
            $checkItemMeta->execute(['p4' => $orderItemMetaTable]);
            $hasOrderItemMeta = (bool)$checkItemMeta->fetch();

            $syncedOrders = 0;
            $totalRevenue = 0;

            // 1. MODO HPOS (High-Performance Order Storage WooCommerce 8+)
            // 1. INTENTO MODO HPOS (High-Performance Order Storage WooCommerce 8+)
            if ($hasHpos) {
                try {
                    $hposCols = $pdo->query("SHOW COLUMNS FROM `{$hposTable}`")->fetchAll(PDO::FETCH_COLUMN);

                    $paymentSelect = in_array('payment_method_title', $hposCols)
                        ? "COALESCE(o.payment_method_title, o.payment_method, 'Webpay Plus')"
                        : (in_array('payment_method', $hposCols) ? "COALESCE(o.payment_method, 'Webpay Plus')" : "'Webpay Plus'");

                    $dateSelect = in_array('date_created_gmt', $hposCols)
                        ? "o.date_created_gmt"
                        : (in_array('date_created', $hposCols) ? "o.date_created" : "NOW()");

                    $shippingSelect = "0";
                    $joinOp = "";
                    if (in_array($prefix . 'wc_order_operational_data', $tables)) {
                        $opCols = $pdo->query("SHOW COLUMNS FROM `{$prefix}wc_order_operational_data`")->fetchAll(PDO::FETCH_COLUMN);
                        if (in_array('shipping_total_amount', $opCols)) {
                            $shippingSelect = "COALESCE(op.shipping_total_amount, 0)";
                            $joinOp = "LEFT JOIN `{$prefix}wc_order_operational_data` op ON o.id = op.order_id";
                        }
                    }

                    $nameSelect = "o.billing_email";
                    $citySelect = "'Santiago'";
                    $joinAddr = "";
                    if (in_array($prefix . 'wc_order_addresses', $tables)) {
                        $nameSelect = "CONCAT(COALESCE(a.first_name, ''), ' ', COALESCE(a.last_name, ''))";
                        $citySelect = "COALESCE(a.city, 'Santiago')";
                        $joinAddr = "LEFT JOIN `{$prefix}wc_order_addresses` a ON o.id = a.order_id AND a.address_type = 'billing'";
                    }

                    $ordersStmt = $pdo->query("
                        SELECT 
                            o.id as wc_order_id,
                            o.status,
                            o.total_amount,
                            {$dateSelect} as date_created,
                            o.billing_email,
                            {$nameSelect} as customer_name,
                            {$citySelect} as customer_city,
                            {$paymentSelect} as payment_method,
                            {$shippingSelect} as shipping_amount
                        FROM `{$hposTable}` o
                        {$joinAddr}
                        {$joinOp}
                        WHERE o.type = 'shop_order'
                        ORDER BY o.id DESC
                        LIMIT 400
                    ");
                    $ordersData = $ordersStmt->fetchAll();

                    foreach ($ordersData as $row) {
                        $customerName = trim($row['customer_name'] ?? '');
                        if (empty($customerName)) {
                            $customerName = $row['billing_email'] ?: 'Cliente Suitable';
                        }

                        $order = Order::updateOrCreate(
                            ['wc_order_id' => $row['wc_order_id']],
                            [
                                'customer_name' => $customerName,
                                'customer_email' => $row['billing_email'] ?: 'contacto@suitable.cl',
                                'customer_city' => $row['customer_city'] ?: 'Santiago',
                                'total_amount' => (float)$row['total_amount'],
                                'shipping_amount' => (float)$row['shipping_amount'],
                                'status' => str_replace('wc-', '', $row['status']),
                                'payment_method' => $row['payment_method'] ?: 'Webpay Plus',
                                'items_count' => 1,
                                'date_created' => $row['date_created'] ?: now()
                            ]
                        );

                        if ($hasOrderItems && $hasOrderItemMeta) {
                            $this->syncOrderItems($pdo, $order->id, (int)$row['wc_order_id'], $orderItemsTable, $orderItemMetaTable);
                        }

                        $syncedOrders++;
                        $totalRevenue += (float)$row['total_amount'];
                    }
                } catch (Throwable $hposEx) {
                    // Si ocurre cualquier discrepancia en HPOS, continuará al modo clásico
                }
            }

            // 2. MODO CLÁSICO WORDPRESS (wp8q_posts + wp8q_postmeta)
            // Se ejecuta si HPOS no arrojó pedidos o si los pedidos están en posts
            if ($syncedOrders === 0 && $hasPosts) {
                $postsStmt = $pdo->query("
                    SELECT ID, post_status, post_date
                    FROM `{$postsTable}`
                    WHERE post_type IN ('shop_order', 'shop_order_placehold')
                      AND post_status NOT IN ('trash', 'auto-draft')
                    ORDER BY ID DESC
                    LIMIT 400
                ");
                $rawPosts = $postsStmt->fetchAll();

                foreach ($rawPosts as $p) {
                    $orderId = $p['ID'];

                    // Buscar postmeta del pedido
                    $metaStmt = $pdo->prepare("
                        SELECT meta_key, meta_value 
                        FROM `{$postmetaTable}` 
                        WHERE post_id = :post_id 
                          AND meta_key IN (
                            '_billing_first_name', '_billing_last_name', '_billing_email', 
                            '_billing_city', '_order_total', '_order_shipping', 
                            '_payment_method_title', '_payment_method'
                          )
                    ");
                    $metaStmt->execute(['post_id' => $orderId]);
                    $meta = $metaStmt->fetchAll(PDO::FETCH_KEY_PAIR);

                    $customerName = trim(($meta['_billing_first_name'] ?? '') . ' ' . ($meta['_billing_last_name'] ?? ''));
                    $customerEmail = $meta['_billing_email'] ?? 'ventas@suitable.cl';
                    $customerCity = $meta['_billing_city'] ?? 'Santiago';
                    $total = (float)($meta['_order_total'] ?? 0);
                    $shipping = (float)($meta['_order_shipping'] ?? 0);
                    $status = str_replace('wc-', '', $p['post_status']);
                    $paymentMethod = $meta['_payment_method_title'] ?? ($meta['_payment_method'] ?? 'Transbank Webpay');

                    $order = Order::updateOrCreate(
                        ['wc_order_id' => $orderId],
                        [
                            'customer_name' => $customerName ?: ($customerEmail ?: 'Cliente Suitable.cl'),
                            'customer_email' => $customerEmail,
                            'customer_city' => $customerCity ?: 'Santiago',
                            'total_amount' => $total,
                            'shipping_amount' => $shipping,
                            'status' => $status,
                            'payment_method' => $paymentMethod,
                            'items_count' => 1,
                            'date_created' => $p['post_date']
                        ]
                    );

                    if ($hasOrderItems && $hasOrderItemMeta) {
                        $this->syncOrderItems($pdo, $order->id, (int)$orderId, $orderItemsTable, $orderItemMetaTable);
                    }

                    $syncedOrders++;
                    $totalRevenue += $total;
                }
            }

            if ($syncedOrders === 0 && !$hasPosts && !$hasHpos) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron las tablas de pedidos de WooCommerce (`{$postsTable}` o `{$hposTable}`) en la base de datos '{$database}'."
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
                'message' => "¡Sincronización exitosa! Se importaron {$syncedOrders} pedidos reales de Suitable.cl desde la base de datos '{$database}' (prefijo {$prefix})."
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error durante la sincronización: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sincroniza los ítems y prendas de una orden específica
     */
    private function syncOrderItems(PDO $pdo, int $internalOrderId, int $wcOrderId, string $orderItemsTable, string $orderItemMetaTable): void
    {
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

                $totalQty = 0;
                foreach ($items as $it) {
                    $itemId = $it['order_item_id'];
                    $imStmt = $pdo->prepare("
                        SELECT meta_key, meta_value 
                        FROM `{$orderItemMetaTable}` 
                        WHERE order_item_id = :item_id 
                          AND meta_key IN ('_qty', '_line_total', '_line_subtotal', 'pa_color', 'color', 'pa_talla', 'talla', 'pa_size', 'size')
                    ");
                    $imStmt->execute(['item_id' => $itemId]);
                    $itemMeta = $imStmt->fetchAll(PDO::FETCH_KEY_PAIR);

                    $qty = max(1, (int)($itemMeta['_qty'] ?? 1));
                    $totalQty += $qty;
                    $lineTotal = (float)($itemMeta['_line_total'] ?? ($itemMeta['_line_subtotal'] ?? 0));
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
