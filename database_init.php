<?php
/**
 * Database Initializer & Seeder for Suitable B2B Outreach, WooCommerce Sync, and ML Brain
 */

require_once __DIR__ . '/config.php';

$db = get_db();

// 1. Users Table
$db->exec("
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'enviador',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
");

// 2. Clients Table
$db->exec("
CREATE TABLE IF NOT EXISTS clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    empresa TEXT NOT NULL,
    contacto_nombre TEXT NOT NULL,
    email TEXT NOT NULL,
    telefono TEXT DEFAULT '',
    cargo TEXT DEFAULT '',
    region_comuna TEXT DEFAULT 'Santiago, RM',
    tamano_equipo INTEGER DEFAULT 15,
    estado TEXT NOT NULL DEFAULT 'nuevo',
    notas TEXT DEFAULT '',
    fecha_tallaje DATE DEFAULT NULL,
    monto_cotizacion REAL DEFAULT NULL,
    ultimo_envio_tipo TEXT DEFAULT NULL,
    ultimo_envio_fecha DATETIME DEFAULT NULL,
    asignado_a INTEGER DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(asignado_a) REFERENCES users(id) ON DELETE SET NULL
);
");

// 3. Contact Groups Table
$db->exec("
CREATE TABLE IF NOT EXISTS contact_groups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT DEFAULT '',
    color TEXT DEFAULT '#1E8888',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
");

// 4. Group Members
$db->exec("
CREATE TABLE IF NOT EXISTS group_members (
    group_id INTEGER NOT NULL,
    client_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(group_id, client_id),
    FOREIGN KEY(group_id) REFERENCES contact_groups(id) ON DELETE CASCADE,
    FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE
);
");

// 5. Campaigns Table
$db->exec("
CREATE TABLE IF NOT EXISTS campaigns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    group_id INTEGER,
    template_id INTEGER NOT NULL DEFAULT 2,
    subject TEXT NOT NULL,
    preheader TEXT DEFAULT '',
    ai_provider TEXT DEFAULT 'groq',
    ai_prompt TEXT DEFAULT '',
    status TEXT NOT NULL DEFAULT 'borrador',
    sent_count INTEGER DEFAULT 0,
    total_count INTEGER DEFAULT 0,
    user_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(group_id) REFERENCES contact_groups(id) ON DELETE SET NULL,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
);
");

// 6. Email Logs Table
$db->exec("
CREATE TABLE IF NOT EXISTS email_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    campaign_id INTEGER DEFAULT NULL,
    user_id INTEGER,
    template_id INTEGER NOT NULL,
    recipient_email TEXT NOT NULL,
    subject TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'simulado',
    details TEXT DEFAULT '',
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY(campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
);
");

// 7. WooCommerce Orders Table
$db->exec("
CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    wc_order_id INTEGER UNIQUE,
    customer_name TEXT NOT NULL,
    customer_email TEXT NOT NULL,
    customer_city TEXT DEFAULT 'Santiago',
    total_amount REAL NOT NULL DEFAULT 0,
    shipping_amount REAL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'completed', -- 'completed', 'processing', 'pending', 'refunded'
    payment_method TEXT DEFAULT 'Transbank Webpay Plus',
    items_count INTEGER DEFAULT 1,
    date_created DATETIME NOT NULL
);
");

// 8. WooCommerce Order Items Table
$db->exec("
CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    product_name TEXT NOT NULL,
    category TEXT DEFAULT 'Top Clínico',
    variation_color TEXT DEFAULT 'Azul Marino',
    variation_size TEXT DEFAULT 'M',
    quantity INTEGER DEFAULT 1,
    price REAL NOT NULL,
    subtotal REAL NOT NULL,
    FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE
);
");

// 9. Traffic & Marketing Metrics Table
$db->exec("
CREATE TABLE IF NOT EXISTS traffic_metrics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    period_type TEXT NOT NULL, -- 'day', 'week', 'month', 'year'
    period_date DATE NOT NULL,
    sessions INTEGER DEFAULT 0,
    visitors INTEGER DEFAULT 0,
    orders_count INTEGER DEFAULT 0,
    revenue REAL DEFAULT 0,
    ad_spend REAL DEFAULT 0,
    ctr REAL DEFAULT 0, -- Click Through Rate %
    cpa REAL DEFAULT 0, -- Cost per Acquisition $ CLP
    cvr REAL DEFAULT 0, -- Conversion Rate %
    aov REAL DEFAULT 0  -- Average Order Value $ CLP
);
");

// 10. Search Trends Table (ML Brain)
$db->exec("
CREATE TABLE IF NOT EXISTS search_trends (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    keyword TEXT NOT NULL,
    search_volume INTEGER NOT NULL,
    growth_rate REAL NOT NULL, -- percentage growth
    category TEXT DEFAULT 'General',
    period_type TEXT NOT NULL, -- 'daily', 'weekly', 'monthly'
    intent_level TEXT DEFAULT 'Alta Intención de Compra',
    updated_at DATE DEFAULT CURRENT_DATE
);
");

// 11. Settings Table
$db->exec("
CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT
);
");

// Default Settings
$settings_defaults = [
    'brevo_api_key' => '',
    'sender_email' => 'ventas@suitable.cl',
    'sender_name' => 'Suitable Uniformes Clínicos',
    'brand_color' => '#1E8888',
    'garantia_meses' => '6',
    // AI
    'active_ai_provider' => 'groq',
    'groq_api_key' => '',
    'groq_model' => 'llama-3.3-70b-versatile',
    'openai_api_key' => '',
    'openai_model' => 'gpt-4o-mini',
    'claude_api_key' => '',
    'claude_model' => 'claude-3-5-sonnet-20241022',
    'gemini_api_key' => '',
    'gemini_model' => 'gemini-1.5-flash',
    // WooCommerce REST API
    'wc_store_url' => 'https://suitable.cl',
    'wc_consumer_key' => '',
    'wc_consumer_secret' => '',
];

foreach ($settings_defaults as $k => $v) {
    $stmt = $db->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)");
    $stmt->execute([$k, $v]);
}

// Seed Users
$user_count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($user_count == 0) {
    $users = [
        ['name' => 'Administrador Suitable', 'email' => 'admin@suitable.cl', 'password' => 'admin123', 'role' => 'admin'],
        ['name' => 'Ejecutiva Ventas B2B', 'email' => 'ventas@suitable.cl', 'password' => 'ventas123', 'role' => 'enviador'],
        ['name' => 'Coordinador Clínicas & Convenios', 'email' => 'convenios@suitable.cl', 'password' => 'convenios123', 'role' => 'enviador']
    ];
    $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
    foreach ($users as $u) {
        $hash = password_hash($u['password'], PASSWORD_DEFAULT);
        $stmt->execute([$u['name'], $u['email'], $hash, $u['role']]);
    }
}

// Seed Groups
$group_count = $db->query("SELECT COUNT(*) FROM contact_groups")->fetchColumn();
if ($group_count == 0) {
    $groups = [
        ['name' => 'Clínicas & Hospitales RM', 'description' => 'Instituciones de alta complejidad en Santiago con equipos médicos grandes.', 'color' => '#1E8888'],
        ['name' => 'Centros Odontológicos', 'description' => 'Clínicas dentales y cadenas que solicitan bordado y tallaje presencial.', 'color' => '#0284C7'],
        ['name' => 'Centros Estéticos & Dermatología', 'description' => 'Centros de medicina estética que priorizan calce moderno y tela Flex.', 'color' => '#8B5CF6'],
        ['name' => 'Prospectos Tallaje Urgente', 'description' => 'Leads con cotización pendiente para agendamiento de visita con muestrario.', 'color' => '#D97706'],
    ];
    $stmt = $db->prepare("INSERT INTO contact_groups (name, description, color) VALUES (?, ?, ?)");
    foreach ($groups as $g) {
        $stmt->execute([$g['name'], $g['description'], $g['color']]);
    }
}

// Seed WooCommerce Historical Orders if empty
$orders_count = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
if ($orders_count == 0) {
    // Generate realistic WooCommerce orders history for Suitable.cl
    $products_catalog = [
        ['name' => 'Polera Clínica Elástica Mujer Flex', 'cat' => 'Top Clínico Mujer', 'price' => 24990, 'colors' => ['Azul Marino', 'Celeste', 'Verde Nilo', 'Lila', 'Caribbean', 'Blanco']],
        ['name' => 'Pantalón Clínico Elasticado Mujer Flex', 'cat' => 'Pantalón Mujer', 'price' => 26990, 'colors' => ['Azul Marino', 'Negro', 'Celeste', 'Verde Nilo', 'Gris Oscuro']],
        ['name' => 'Top Clínico Hombre Flex', 'cat' => 'Top Clínico Hombre', 'price' => 25990, 'colors' => ['Verde Caribbean', 'Azul Marino', 'Azul Rey', 'Púrpura', 'Negro']],
        ['name' => 'Pantalón Clínico Hombre Flex', 'cat' => 'Pantalón Hombre', 'price' => 27990, 'colors' => ['Azul Marino', 'Negro', 'Gris Claro']],
        ['name' => 'Gorro Quirúrgico Antifluidos', 'cat' => 'Accesorios', 'price' => 8990, 'colors' => ['Estampado Clínico', 'Azul Marino', 'Teal']],
        ['name' => 'Kit Cirugía en Casa / Sutura', 'cat' => 'Kits Médicos', 'price' => 34990, 'colors' => ['Estándar']],
        ['name' => 'Dotación Set Clínico Corporativo x10', 'cat' => 'Corporativo B2B', 'price' => 450000, 'colors' => ['Personalizado con Bordado']],
    ];

    $sample_buyers = [
        ['Dr. Patricio Alarcón', 'palarcon@clinicasantamaria.cl', 'Providencia'],
        ['Dra. Camila Vergara', 'cvergara@redsalud.cl', 'Santiago'],
        ['Enf. Marcela Fuentes', 'mfuentes@ucchristus.cl', 'Las Condes'],
        ['Dr. Javier Soto', 'jsoto@odontosalud.cl', 'Viña del Mar'],
        ['Dra. Francisca Reyes', 'freyes@esteticavitacura.cl', 'Vitacura'],
        ['Clínica Dental Cordillera', 'compras@dentalcordillera.cl', 'La Florida'],
        ['Enf. Rodrigo Silva', 'rsilva@hospitaldetrabajador.cl', 'Providencia'],
        ['Dra. Andrea Morales', 'amorales@alemana.cl', 'Vitacura'],
        ['Centro Médico San Cristóbal', 'adquisiciones@sancristobal.cl', 'Santiago'],
        ['Dr. Gonzalo Henríquez', 'ghenriquez@veterinariachile.cl', 'Ñuñoa'],
    ];

    $sizes = ['XS', 'S', 'M', 'L', 'XL'];
    $stmt_ord = $db->prepare("
        INSERT INTO orders (wc_order_id, customer_name, customer_email, customer_city, total_amount, shipping_amount, status, payment_method, items_count, date_created)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt_item = $db->prepare("
        INSERT INTO order_items (order_id, product_name, category, variation_color, variation_size, quantity, price, subtotal)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $wc_id_start = 24100;
    // Generate orders spanning the last 12 months with natural seasonality
    for ($i = 0; $i < 90; $i++) {
        $days_ago = rand(0, 365);
        $order_date = date('Y-m-d H:i:s', strtotime("-$days_ago days -".rand(0, 23)." hours"));
        $buyer = $sample_buyers[array_rand($sample_buyers)];
        $num_items = rand(1, 4);
        
        $order_total = 0;
        $order_items = [];

        for ($j = 0; $j < $num_items; $j++) {
            $p = $products_catalog[array_rand($products_catalog)];
            $color = $p['colors'][array_rand($p['colors'])];
            $size = $sizes[array_rand($sizes)];
            $qty = ($p['cat'] === 'Corporativo B2B') ? 1 : rand(1, 3);
            $sub = $p['price'] * $qty;
            $order_total += $sub;

            $order_items[] = [
                'name' => $p['name'],
                'cat' => $p['cat'],
                'color' => $color,
                'size' => $size,
                'qty' => $qty,
                'price' => $p['price'],
                'subtotal' => $sub
            ];
        }

        $shipping = $order_total > 50000 ? 0 : 3990;
        $order_total += $shipping;
        $wc_id = $wc_id_start + $i;

        $stmt_ord->execute([
            $wc_id, $buyer[0], $buyer[1], $buyer[2], $order_total, $shipping, 'completed', 'Transbank Webpay', count($order_items), $order_date
        ]);
        $order_db_id = $db->lastInsertId();

        foreach ($order_items as $oi) {
            $stmt_item->execute([
                $order_db_id, $oi['name'], $oi['cat'], $oi['color'], $oi['size'], $oi['qty'], $oi['price'], $oi['subtotal']
            ]);
        }
    }
}

// Seed Search Trends for ML Brain
$trends_count = $db->query("SELECT COUNT(*) FROM search_trends")->fetchColumn();
if ($trends_count == 0) {
    $search_data = [
        ['keyword' => 'uniformes clinicos antifluidos', 'search_volume' => 14200, 'growth_rate' => 38.4, 'category' => 'Antifluidos', 'period_type' => 'monthly', 'intent_level' => 'Muy Alta Intención de Compra'],
        ['keyword' => 'top clinico hombre flex', 'search_volume' => 8900, 'growth_rate' => 45.2, 'category' => 'Línea Hombre', 'period_type' => 'monthly', 'intent_level' => 'Alta Intención de Compra'],
        ['keyword' => 'pantalon clinico elasticado mujer', 'search_volume' => 12400, 'growth_rate' => 29.8, 'category' => 'Línea Mujer', 'period_type' => 'monthly', 'intent_level' => 'Muy Alta Intención de Compra'],
        ['keyword' => 'servicio de tallaje para clinicas', 'search_volume' => 3100, 'growth_rate' => 62.5, 'category' => 'B2B & Convenios', 'period_type' => 'monthly', 'intent_level' => 'Corporativo B2B'],
        ['keyword' => 'gorros quirurgicos bordados chile', 'search_volume' => 5400, 'growth_rate' => 21.0, 'category' => 'Accesorios', 'period_type' => 'monthly', 'intent_level' => 'Media Intención de Compra'],
        ['keyword' => 'uniformes clinicos venta mayorista santiago', 'search_volume' => 4800, 'growth_rate' => 51.7, 'category' => 'B2B & Convenios', 'period_type' => 'monthly', 'intent_level' => 'Corporativo B2B'],
        ['keyword' => 'scrubs medicos tela flex 4-way', 'search_volume' => 7600, 'growth_rate' => 41.3, 'category' => 'Tecnología Flex', 'period_type' => 'monthly', 'intent_level' => 'Alta Intención de Compra'],
        ['keyword' => 'uniformes clinicos garantia 6 meses', 'search_volume' => 2200, 'growth_rate' => 84.0, 'category' => 'Garantía de Marca', 'period_type' => 'monthly', 'intent_level' => 'Muy Alta Intención de Compra'],
    ];

    $stmt_st = $db->prepare("
        INSERT INTO search_trends (keyword, search_volume, growth_rate, category, period_type, intent_level)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($search_data as $sd) {
        $stmt_st->execute([$sd['keyword'], $sd['search_volume'], $sd['growth_rate'], $sd['category'], $sd['period_type'], $sd['intent_level']]);
    }
}

// Seed Traffic & Marketing Performance Metrics (Daily, Weekly, Monthly, Yearly)
$traffic_count = $db->query("SELECT COUNT(*) FROM traffic_metrics")->fetchColumn();
if ($traffic_count == 0) {
    // Generate 30 daily metrics records
    $stmt_tr = $db->prepare("
        INSERT INTO traffic_metrics (period_type, period_date, sessions, visitors, orders_count, revenue, ad_spend, ctr, cpa, cvr, aov)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    for ($d = 30; $d >= 0; $d--) {
        $pdate = date('Y-m-d', strtotime("-$d days"));
        $sessions = rand(650, 1400);
        $visitors = round($sessions * 0.82);
        $orders = rand(12, 38);
        $aov = rand(42000, 68000);
        $rev = $orders * $aov;
        $ad_spend = rand(25000, 65000);
        $ctr = round(rand(28, 49) / 10, 2); // 2.8% to 4.9% CTR
        $cpa = round($ad_spend / max(1, $orders));
        $cvr = round(($orders / $sessions) * 100, 2);

        $stmt_tr->execute(['day', $pdate, $sessions, $visitors, $orders, $rev, $ad_spend, $ctr, $cpa, $cvr, $aov]);
    }
}

if (php_sapi_name() === 'cli') {
    echo "Base de datos migrada con éxito: Tablas de WooCommerce, Métricas de Tráfico (CTR, CPA, AOV) y Cerebro de ML inicializadas.\n";
}
