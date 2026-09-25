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
use Exception;

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
        $uniqueCustomers = Order::distinct('customer_email')->count('customer_email');

        // Configuración guardada (con valores por defecto basados en Suitable.cl y suitable_wp372)
        $mysqlHost = Setting::get('wc_mysql_host', 'localhost');
        $mysqlPort = Setting::get('wc_mysql_port', '3306');
        $mysqlDb = Setting::get('wc_mysql_db', 'suitable_wp372');
        $mysqlUser = Setting::get('wc_mysql_user', 'suitable_intranetuser');
        $mysqlPass = Setting::get('wc_mysql_pass', '');
        $tablePrefix = Setting::get('wc_active_prefix', 'wp8q_');

        $storeUrl = Setting::get('wc_store_url', 'https://suitable.cl');
        $consumerKey = Setting::get('wc_consumer_key', '');
        $consumerSecret = Setting::get('wc_consumer_secret', '');
        $lastSync = Setting::get('wc_last_sync', '');

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
        } catch (Exception $e) {
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
        $host = $request->input('db_host') ?: Setting::get('wc_mysql_host', 'localhost');
        $port = $request->input('db_port') ?: Setting::get('wc_mysql_port', '3306');
        $database = $request->input('db_name') ?: Setting::get('wc_mysql_db', 'suitable_wp372');
        $user = $request->input('db_user') ?: Setting::get('wc_mysql_user', 'suitable_intranetuser');
        $pass = $request->has('db_pass') ? $request->input('db_pass') : Setting::get('wc_mysql_pass', '');
        $prefix = $request->input('table_prefix') ?: Setting::get('wc_active_prefix', 'wp8q_');

        try {
            $pdo = $this->getPdoConnection($host, $port, $database, $user, $pass);

            // Verificar si existen tablas con el prefijo indicado
            $stmt = $pdo->prepare("SHOW TABLES LIKE :pattern");
            $stmt->execute(['pattern' => $prefix . '%']);
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($tables)) {
                return response()->json([
                    'success' => false,
                    'message' => "Conexión a MySQL exitosa, pero no se encontraron tablas con el prefijo '{$prefix}' en la base de datos '{$database}'. Revisa el prefijo (por ejemplo 'wp8q_')."
                ]);
            }

            // Probar existencia de tablas de órdenes (HPOS o Classic)
            $ordersFound = 0;
            $hposTable = $prefix . 'wc_orders';
            $postsTable = $prefix . 'posts';

            if (in_array($hposTable, $tables)) {
                $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$hposTable}` WHERE `type` = 'shop_order'");
                $ordersFound = (int)$countStmt->fetchColumn();
            } elseif (in_array($postsTable, $tables)) {
                $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$postsTable}` WHERE `post_type` IN ('shop_order', 'shop_order_placehold') AND `post_status` NOT IN ('trash', 'auto-draft')");
                $ordersFound = (int)$countStmt->fetchColumn();
            }

            return response()->json([
                'success' => true,
                'database' => $database,
                'prefix' => $prefix,
                'tables_count' => count($tables),
                'orders_found' => $ordersFound,
                'message' => "¡Conexión establecida con éxito! Base de datos '{$database}', prefijo '{$prefix}'. Se detectaron {$ordersFound} pedidos en tu tienda WooCommerce."
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión MySQL: ' . $e->getMessage() . '. Si el usuario no tiene permisos sobre ' . $database . ', asígnalos en cPanel -> Bases de Datos MySQL.'
            ]);
        }
    }

    /**
     * Guarda la configuración de WooCommerce en la base de datos
     */
    public function saveSettings(Request $request): JsonResponse
    {
        $fields = [
            'wc_mysql_host' => $request->input('db_host', 'localhost'),
            'wc_mysql_port' => $request->input('db_port', '3306'),
            'wc_mysql_db' => $request->input('db_name', 'suitable_wp372'),
            'wc_mysql_user' => $request->input('db_user', 'suitable_intranetuser'),
            'wc_active_prefix' => $request->input('table_prefix', 'wp8q_'),
            'wc_store_url' => $request->input('store_url', 'https://suitable.cl'),
            'wc_consumer_key' => $request->input('consumer_key', ''),
            'wc_consumer_secret' => $request->input('consumer_secret', '')
        ];

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
        // Guardar parámetros si vienen en la petición
        if ($request->filled('db_name')) {
            $this->saveSettings($request);
        }

        $host = Setting::get('wc_mysql_host', 'localhost');
        $port = Setting::get('wc_mysql_port', '3306');
        $database = Setting::get('wc_mysql_db', 'suitable_wp372');
        $user = Setting::get('wc_mysql_user', 'suitable_intranetuser');
        $pass = Setting::get('wc_mysql_pass', '');
        $prefix = Setting::get('wc_active_prefix', 'wp8q_');

        try {
            $pdo = $this->getPdoConnection($host, $port, $database, $user, $pass);

            // Verificar tablas
            $stmt = $pdo->prepare("SHOW TABLES LIKE :pattern");
            $stmt->execute(['pattern' => $prefix . '%']);
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $postsTable = $prefix . 'posts';
            $postmetaTable = $prefix . 'postmeta';
            $hposTable = $prefix . 'wc_orders';
            $orderItemsTable = $prefix . 'woocommerce_order_items';
            $orderItemMetaTable = $prefix . 'woocommerce_order_itemmeta';

            $syncedOrders = 0;
            $totalRevenue = 0;

            // 1. MODO HPOS (High-Performance Order Storage WooCommerce 8+)
            if (in_array($hposTable, $tables)) {
                $ordersStmt = $pdo->query("
                    SELECT 
                        o.id as wc_order_id,
                        o.status,
                        o.total_amount,
                        o.date_created_gmt as date_created,
                        o.billing_email,
                        CONCAT(COALESCE(a.first_name, ''), ' ', COALESCE(a.last_name, '')) as customer_name,
                        COALESCE(a.city, 'Santiago') as customer_city,
                        COALESCE(op.payment_method_title, 'Webpay Plus') as payment_method,
                        COALESCE(op.shipping_total_amount, 0) as shipping_amount
                    FROM `{$hposTable}` o
                    LEFT JOIN `{$prefix}wc_order_addresses` a ON o.id = a.order_id AND a.address_type = 'billing'
                    LEFT JOIN `{$prefix}wc_order_operational_data` op ON o.id = op.order_id
                    WHERE o.type = 'shop_order'
                    ORDER BY o.id DESC
                    LIMIT 300
                ");
                $ordersData = $ordersStmt->fetchAll();

                foreach ($ordersData as $row) {
                    $order = Order::updateOrCreate(
                        ['wc_order_id' => $row['wc_order_id']],
                        [
                            'customer_name' => trim($row['customer_name']) ?: ($row['billing_email'] ?: 'Cliente Suitable'),
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

                    $syncedOrders++;
                    $totalRevenue += (float)$row['total_amount'];
                }
            } 
            // 2. MODO CLÁSICO WORDPRESS (wp8q_posts + wp8q_postmeta)
            elseif (in_array($postsTable, $tables) && in_array($postmetaTable, $tables)) {
                $postsStmt = $pdo->query("
                    SELECT ID, post_status, post_date
                    FROM `{$postsTable}`
                    WHERE post_type IN ('shop_order', 'shop_order_placehold')
                      AND post_status NOT IN ('trash', 'auto-draft')
                    ORDER BY ID DESC
                    LIMIT 300
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

                    // Contar ítems reales si existe la tabla
                    $itemsCount = 1;
                    if (in_array($orderItemsTable, $tables)) {
                        $itemsCountStmt = $pdo->prepare("SELECT COUNT(*) FROM `{$orderItemsTable}` WHERE order_id = :order_id AND order_item_type = 'line_item'");
                        $itemsCountStmt->execute(['order_id' => $orderId]);
                        $itemsCount = max(1, (int)$itemsCountStmt->fetchColumn());
                    }

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
                            'items_count' => $itemsCount,
                            'date_created' => $p['post_date']
                        ]
                    );

                    // Sincronizar ítems de la orden si existen
                    if (in_array($orderItemsTable, $tables) && in_array($orderItemMetaTable, $tables)) {
                        $itemsStmt = $pdo->prepare("
                            SELECT order_item_id, order_item_name 
                            FROM `{$orderItemsTable}` 
                            WHERE order_id = :order_id AND order_item_type = 'line_item'
                        ");
                        $itemsStmt->execute(['order_id' => $orderId]);
                        $items = $itemsStmt->fetchAll();

                        if (!empty($items)) {
                            // Limpiar ítems anteriores de esta orden para evitar duplicidad
                            OrderItem::where('order_id', $order->id)->delete();

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
                                $lineTotal = (float)($itemMeta['_line_total'] ?? ($itemMeta['_line_subtotal'] ?? 0));
                                $unitPrice = $qty > 0 ? ($lineTotal / $qty) : $lineTotal;
                                $color = $itemMeta['pa_color'] ?? ($itemMeta['color'] ?? 'Estándar');
                                $size = $itemMeta['pa_talla'] ?? ($itemMeta['talla'] ?? ($itemMeta['pa_size'] ?? ($itemMeta['size'] ?? 'M')));

                                OrderItem::create([
                                    'order_id' => $order->id,
                                    'product_name' => $it['order_item_name'] ?: 'Uniforme Clínico Suitable',
                                    'category' => 'Vestuario Clínico',
                                    'variation_color' => $color,
                                    'variation_size' => $size,
                                    'quantity' => $qty,
                                    'price' => $unitPrice,
                                    'subtotal' => $lineTotal
                                ]);
                            }
                        }
                    }

                    $syncedOrders++;
                    $totalRevenue += $total;
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron las tablas de pedidos de WooCommerce (`{$prefix}posts` o `{$prefix}wc_orders`) en la base de datos '{$database}'."
                ]);
            }

            Setting::set('wc_last_sync', now()->toDateTimeString());

            return response()->json([
                'success' => true,
                'synced_count' => $syncedOrders,
                'total_revenue' => $totalRevenue,
                'formatted_revenue' => '$' . number_format($totalRevenue, 0, ',', '.'),
                'message' => "¡Sincronización exitosa! Se importaron {$syncedOrders} pedidos reales de Suitable.cl desde la base de datos '{$database}'."
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error durante la sincronización: ' . $e->getMessage()
            ]);
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
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al purgar datos: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Conexión PDO con timeout y UTF-8
     */
    private function getPdoConnection(string $host, string $port, string $database, string $user, string $pass): PDO
    {
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 6,
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
            } catch (\Throwable $e) {
                // Silently skip any restricted paths
            }
        }

        return null;
    }
}
