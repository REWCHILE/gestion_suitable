<?php
/**
 * Helper to ensure a WooCommerce database is available for the wizard detection
 */

try {
    $mysqli = new mysqli('127.0.0.1', 'root', '', '', 3306);
    if ($mysqli->connect_error) {
        die("No se pudo conectar a MySQL: " . $mysqli->connect_error);
    }

    $mysqli->query("CREATE DATABASE IF NOT EXISTS suitable_woocommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $mysqli->select_db('suitable_woocommerce');

    // Create wp_options
    $mysqli->query("
        CREATE TABLE IF NOT EXISTS wp_options (
            option_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            option_name VARCHAR(191) UNIQUE NOT NULL,
            option_value LONGTEXT NOT NULL,
            autoload VARCHAR(20) DEFAULT 'yes'
        );
    ");

    $options = [
        'siteurl' => 'https://tienda.suitable.cl',
        'home' => 'https://tienda.suitable.cl',
        'blogname' => 'Suitable - Fábrica de Uniformes Clínicos (Tienda Online)',
        'woocommerce_currency' => 'CLP'
    ];

    foreach ($options as $k => $v) {
        $stmt = $mysqli->prepare("INSERT INTO wp_options (option_name, option_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE option_value = ?");
        $stmt->bind_param("sss", $k, $v, $v);
        $stmt->execute();
    }

    // Create wp_posts
    $mysqli->query("
        CREATE TABLE IF NOT EXISTS wp_posts (
            ID BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_author BIGINT UNSIGNED DEFAULT 1,
            post_date DATETIME NOT NULL,
            post_date_gmt DATETIME NOT NULL,
            post_content LONGTEXT NOT NULL,
            post_title TEXT NOT NULL,
            post_status VARCHAR(20) DEFAULT 'wc-completed',
            post_type VARCHAR(20) NOT NULL DEFAULT 'shop_order'
        );
    ");

    // Create wp_postmeta
    $mysqli->query("
        CREATE TABLE IF NOT EXISTS wp_postmeta (
            meta_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT UNSIGNED NOT NULL,
            meta_key VARCHAR(255) DEFAULT NULL,
            meta_value LONGTEXT,
            INDEX(post_id),
            INDEX(meta_key(191))
        );
    ");

    // Check count of orders
    $res = $mysqli->query("SELECT COUNT(*) FROM wp_posts WHERE post_type = 'shop_order'");
    $cnt = $res->fetch_row()[0];

    if ($cnt == 0) {
        $sample_customers = [
            ['Dra. Valentina Castro', 'vcastro@clinicasantamaria.cl', 'Providencia', 49980],
            ['Dr. Martín Sepúlveda', 'msepulveda@redsalud.cl', 'Santiago', 78970],
            ['Enf. Andrea Godoy', 'agodoy@alemana.cl', 'Las Condes', 52980],
            ['Clínica Dental Maipú', 'adquisiciones@dentalmaipu.cl', 'Maipú', 345000],
            ['Dra. Nicole Bravo', 'nbravo@ucchristus.cl', 'Vitacura', 51980],
            ['Centro Médico Estético Las Condes', 'compras@esteticalascondes.cl', 'Las Condes', 520000],
            ['Dr. Sebastián Pizarro', 'spizarro@odontochile.cl', 'Ñuñoa', 104960],
            ['Enf. Rodrigo Henríquez', 'rhenriquez@hospitaldeltrabajador.cl', 'Providencia', 820000],
        ];

        for ($i = 1; $i <= 35; $i++) {
            $cust = $sample_customers[array_rand($sample_customers)];
            $amount = $cust[3];
            $date = date('Y-m-d H:i:s', strtotime("-".rand(1, 180)." days -".rand(1, 23)." hours"));

            $stmt_p = $mysqli->prepare("INSERT INTO wp_posts (post_date, post_date_gmt, post_content, post_title, post_status, post_type) VALUES (?, ?, '', ?, 'wc-completed', 'shop_order')");
            $title = "Pedido &ndash; " . date('F j, Y @ g:i A', strtotime($date));
            $stmt_p->bind_param("sss", $date, $date, $title);
            $stmt_p->execute();
            $order_id = $mysqli->insert_id;

            $meta = [
                '_order_total' => $amount,
                '_billing_first_name' => explode(' ', $cust[0])[0],
                '_billing_last_name' => explode(' ', $cust[0])[1] ?? 'Salud',
                '_billing_email' => $cust[1],
                '_billing_city' => $cust[2],
                '_payment_method_title' => 'Transbank Webpay Plus',
                '_paid_date' => $date
            ];

            $stmt_m = $mysqli->prepare("INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES (?, ?, ?)");
            foreach ($meta as $mk => $mv) {
                $stmt_m->bind_param("iss", $order_id, $mk, $mv);
                $stmt_m->execute();
            }
        }
    }

    echo "Base de datos WooCommerce 'suitable_woocommerce' verificada con éxito.\n";
} catch (Exception $e) {
    echo "Aviso: " . $e->getMessage() . "\n";
}
