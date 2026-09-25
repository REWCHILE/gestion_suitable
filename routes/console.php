<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('woocommerce:purge-demo', function () {
    $this->info('Eliminando toda la data demo de WooCommerce (órdenes e ítems)...');
    \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    \App\Models\OrderItem::truncate();
    \App\Models\Order::truncate();
    \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    $this->info('¡Data demo eliminada con éxito! La plataforma está lista para conectar con la base de datos real.');
})->purpose('Purga todos los pedidos y elementos de demostración de WooCommerce');

Artisan::command('woocommerce:connect-wp', function () {
    $this->info("🔍 Buscando configuración de WordPress en public_html...");

    $paths = [
        '/home/suitable/public_html/wp-config.php',
        base_path('../public_html/wp-config.php'),
        dirname(base_path()) . '/public_html/wp-config.php'
    ];

    $configPath = null;
    foreach ($paths as $p) {
        if (@file_exists($p) && @is_readable($p)) {
            $configPath = $p;
            break;
        }
    }

    if (!$configPath) {
        $this->error("❌ No se encontró wp-config.php en las rutas habituales.");
        return 1;
    }

    $this->info("✅ Archivo encontrado en: {$configPath}");
    $content = file_get_contents($configPath);

    $dbName = 'suitable_wp372';
    $dbUser = 'suitable_intranetuser';
    $dbPass = '';
    $dbHost = 'localhost';
    $prefix = 'wp8q_';

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
        $prefix = $m[1];
    }

    $this->line("📋 Parámetros de conexión de WordPress detectados:");
    $this->line("   - Base de Datos: {$dbName}");
    $this->line("   - Usuario MySQL: {$dbUser}");
    $this->line("   - Host: {$dbHost}");
    $this->line("   - Prefijo Tablas: {$prefix}");

    // Guardar en Settings
    \App\Models\Setting::set('wc_mysql_host', $dbHost);
    \App\Models\Setting::set('wc_mysql_db', $dbName);
    \App\Models\Setting::set('wc_mysql_user', $dbUser);
    if (!empty($dbPass)) {
        \App\Models\Setting::set('wc_mysql_pass', $dbPass);
    }
    \App\Models\Setting::set('wc_active_prefix', $prefix);

    $this->info("🚀 Sincronizando órdenes reales desde {$dbName}...");

    $req = new \Illuminate\Http\Request([
        'db_host' => $dbHost,
        'db_name' => $dbName,
        'db_user' => $dbUser,
        'db_pass' => $dbPass,
        'table_prefix' => $prefix
    ]);

    $ctrl = app(\App\Http\Controllers\WooCommerceController::class);
    $res = $ctrl->sync($req);
    $data = json_decode($res->getContent(), true);

    if (!empty($data['success'])) {
        $this->info("🎉 " . $data['message']);
        
        $totalOrders = \App\Models\Order::count();
        $totalRev = (float)\App\Models\Order::sum('total_amount');
        $aov = $totalOrders > 0 ? ($totalRev / $totalOrders) : 0;
        $totalItems = (int)\App\Models\OrderItem::sum('quantity');

        $this->newLine();
        $this->info("📊 RESUMEN DE VENTAS SUITABLE.CL:");
        $this->line("   - Órdenes Sincronizadas: {$totalOrders}");
        $this->line("   - Total Facturado: $" . number_format($totalRev, 0, ',', '.') . " CLP");
        $this->line("   - Ticket Promedio (AOV): $" . number_format($aov, 0, ',', '.') . " CLP");
        $this->line("   - Total Prendas / Ítems: {$totalItems}");
        
        $this->newLine();
        $this->info("📋 MUESTRA DE PEDIDOS RECIENTES:");
        $recent = \App\Models\Order::orderBy('date_created', 'desc')->take(10)->get();
        $rows = [];
        foreach ($recent as $o) {
            $rows[] = [
                '#' . $o->wc_order_id,
                $o->customer_name,
                $o->customer_email,
                $o->customer_city,
                '$' . number_format($o->total_amount, 0, ',', '.'),
                $o->items_count . ' un.',
                $o->status
            ];
        }
        $this->table(['Orden', 'Cliente', 'Email', 'Ciudad', 'Total (CLP)', 'Prendas', 'Estado'], $rows);

        // Limpiar cachés de vistas para que el frontend se actualice inmediatamente
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        $this->info("🧹 Cachés de vistas y aplicación limpiadas.");

        return 0;
    } else {
        $this->error("❌ " . ($data['message'] ?? 'Error desconocido'));
        return 1;
    }
})->purpose('Auto-detecta credenciales de WordPress y sincroniza pedidos reales de WooCommerce');

