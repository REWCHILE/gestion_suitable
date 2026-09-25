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

