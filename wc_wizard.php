<?php
require_once __DIR__ . '/config.php';
require_auth();

$user = current_user();
$db = get_db();

$db_host = $_POST['db_host'] ?? get_setting('wc_mysql_host', '127.0.0.1');
$db_user = $_POST['db_user'] ?? get_setting('wc_mysql_user', 'root');
$db_pass = $_POST['db_pass'] ?? get_setting('wc_mysql_pass', '');
$db_port = intval($_POST['db_port'] ?? get_setting('wc_mysql_port', 3306));

$detected_installs = [];
$scan_error = '';

// Scanner function
function scanMysqlForWoocommerce(string $host, string $user, string $pass, int $port): array {
    $results = [];
    
    // Connect without selecting DB to query schemas
    $mysqli = @new mysqli($host, $user, $pass, '', $port);
    if ($mysqli->connect_error) {
        throw new Exception("Error al conectar al servidor MySQL: " . $mysqli->connect_error);
    }

    $db_res = $mysqli->query("SHOW DATABASES;");
    if (!$db_res) {
        throw new Exception("No se pudieron listar las bases de datos.");
    }

    $system_dbs = ['information_schema', 'mysql', 'performance_schema', 'sys'];

    while ($row = $db_res->fetch_row()) {
        $db_name = $row[0];
        if (in_array($db_name, $system_dbs)) continue;

        // Check tables in this database
        $tables_res = $mysqli->query("SELECT TABLE_NAME FROM information_schema.tables WHERE TABLE_SCHEMA = '$db_name'");
        if (!$tables_res) continue;

        $tables = [];
        while ($t_row = $tables_res->fetch_row()) {
            $tables[] = $t_row[0];
        }

        // Find potential prefixes by looking for options or posts tables
        $found_wc = false;
        $prefix = '';

        foreach ($tables as $tbl) {
            if (preg_match('/^(.*)options$/i', $tbl, $m)) {
                $candidate_prefix = $m[1];
                $posts_table = $candidate_prefix . 'posts';
                $postmeta_table = $candidate_prefix . 'postmeta';
                $wc_orders_table = $candidate_prefix . 'wc_orders';

                $has_wc_classic = in_array($posts_table, $tables);
                $has_wc_hpos = in_array($wc_orders_table, $tables);

                if ($has_wc_classic || $has_wc_hpos) {
                    $prefix = $candidate_prefix;
                    $found_wc = true;
                    break;
                }
            }
        }

        if ($found_wc) {
            // Read details from this WooCommerce installation
            $mysqli->select_db($db_name);
            
            // Site URL & Name
            $site_url = 'Desconocido';
            $site_name = $db_name;
            $options_table = $prefix . 'options';

            $opt_res = $mysqli->query("SELECT option_name, option_value FROM `$options_table` WHERE option_name IN ('siteurl', 'home', 'blogname')");
            if ($opt_res) {
                while ($opt = $opt_res->fetch_assoc()) {
                    if ($opt['option_name'] === 'siteurl' || $opt['option_name'] === 'home') {
                        $site_url = $opt['option_value'];
                    }
                    if ($opt['option_name'] === 'blogname') {
                        $site_name = $opt['option_value'];
                    }
                }
            }

            // Extract subdomain
            $parsed_url = parse_url($site_url);
            $subdomain = $parsed_url['host'] ?? $site_url;

            // Orders count & Total Revenue
            $total_orders = 0;
            $total_sales = 0;
            $first_order_date = null;
            $last_order_date = null;

            $posts_table = $prefix . 'posts';
            $postmeta_table = $prefix . 'postmeta';

            if (in_array($posts_table, $tables)) {
                $orders_res = $mysqli->query("
                    SELECT COUNT(*) as total_orders, 
                           MIN(post_date) as first_date, 
                           MAX(post_date) as last_date 
                    FROM `$posts_table` 
                    WHERE post_type = 'shop_order'
                ");
                if ($orders_res && $ord_row = $orders_res->fetch_assoc()) {
                    $total_orders = intval($ord_row['total_orders'] ?? 0);
                    $first_order_date = $ord_row['first_date'];
                    $last_order_date = $ord_row['last_date'];
                }

                // Sum of order total
                if ($total_orders > 0 && in_array($postmeta_table, $tables)) {
                    $sales_res = $mysqli->query("
                        SELECT SUM(CAST(pm.meta_value AS DECIMAL(15,2))) as total_revenue
                        FROM `$postmeta_table` pm
                        INNER JOIN `$posts_table` p ON pm.post_id = p.ID
                        WHERE pm.meta_key = '_order_total' AND p.post_type = 'shop_order'
                    ");
                    if ($sales_res && $s_row = $sales_res->fetch_assoc()) {
                        $total_sales = floatval($s_row['total_revenue'] ?? 0);
                    }
                }
            }

            $results[] = [
                'db_name' => $db_name,
                'prefix' => $prefix,
                'site_name' => $site_name,
                'site_url' => $site_url,
                'subdomain' => $subdomain,
                'total_orders' => $total_orders,
                'total_sales' => $total_sales,
                'first_order_date' => $first_order_date,
                'last_order_date' => $last_order_date,
            ];
        }
    }

    $mysqli->close();
    return $results;
}

// Auto-run scan on load
try {
    $detected_installs = scanMysqlForWoocommerce($db_host, $db_user, $db_pass, $db_port);
} catch (Exception $e) {
    $scan_error = $e->getMessage();
}

// Import Action
$imported_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_db'])) {
    $target_db = $_POST['target_db'];
    $target_prefix = $_POST['target_prefix'];

    try {
        $mysqli = new mysqli($db_host, $db_user, $db_pass, $target_db, $db_port);
        if ($mysqli->connect_error) throw new Exception($mysqli->connect_error);

        $posts_table = $target_prefix . 'posts';
        $postmeta_table = $target_prefix . 'postmeta';

        // Query all orders
        $query = "
            SELECT p.ID, p.post_date, p.post_status,
                   MAX(CASE WHEN pm.meta_key = '_order_total' THEN pm.meta_value END) as total_amount,
                   MAX(CASE WHEN pm.meta_key = '_billing_first_name' THEN pm.meta_value END) as first_name,
                   MAX(CASE WHEN pm.meta_key = '_billing_last_name' THEN pm.meta_value END) as last_name,
                   MAX(CASE WHEN pm.meta_key = '_billing_email' THEN pm.meta_value END) as email,
                   MAX(CASE WHEN pm.meta_key = '_billing_city' THEN pm.meta_value END) as city,
                   MAX(CASE WHEN pm.meta_key = '_payment_method_title' THEN pm.meta_value END) as payment_method
            FROM `$posts_table` p
            LEFT JOIN `$postmeta_table` pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            GROUP BY p.ID
            ORDER BY p.post_date DESC
        ";

        $res = $mysqli->query($query);
        $imported_count = 0;
        $total_imported_val = 0;

        $stmt_ins = $db->prepare("
            INSERT INTO orders (wc_order_id, customer_name, customer_email, customer_city, total_amount, status, payment_method, date_created)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT(wc_order_id) DO UPDATE SET
                total_amount = excluded.total_amount,
                status = excluded.status
        ");

        while ($o = $res->fetch_assoc()) {
            $name = trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? '')) ?: 'Cliente Tienda';
            $email = $o['email'] ?: 'cliente@suitable.cl';
            $city = $o['city'] ?: 'Santiago';
            $total = floatval($o['total_amount'] ?? 0);
            $status = str_replace('wc-', '', $o['post_status'] ?: 'completed');
            $pm = $o['payment_method'] ?: 'Transbank Webpay Plus';
            $date = $o['post_date'] ?: date('Y-m-d H:i:s');

            $stmt_ins->execute([$o['ID'], $name, $email, $city, $total, $status, $pm, $date]);
            $imported_count++;
            $total_imported_val += $total;
        }

        // Save active MySQL connection settings
        set_setting('wc_mysql_host', $db_host);
        set_setting('wc_mysql_user', $db_user);
        set_setting('wc_mysql_pass', $db_pass);
        set_setting('wc_mysql_port', (string)$db_port);
        set_setting('wc_active_db', $target_db);
        set_setting('wc_active_prefix', $target_prefix);

        $imported_msg = "¡Se importaron exitosamente $imported_count pedidos por un total de $" . number_format($total_imported_val, 0, ',', '.') . " CLP desde la base de datos '$target_db'!";
        
        // Refresh local scan
        $detected_installs = scanMysqlForWoocommerce($db_host, $db_user, $db_pass, $db_port);

    } catch (Exception $ex) {
        $scan_error = "Error al importar: " . $ex->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Wizard Detector de WooCommerce | Suitable</title>
  <link rel="stylesheet" href="assets/css/app.css">
  <style>
    .wizard-card {
      background-color: var(--bg-surface);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-sm);
      padding: 24px;
      margin-bottom: 24px;
    }
    .install-preview-card {
      border: 2px solid var(--border-light);
      border-radius: var(--radius-md);
      padding: 20px;
      background: white;
      margin-bottom: 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: var(--transition);
      flex-wrap: wrap;
      gap: 16px;
    }
    .install-preview-card:hover {
      border-color: var(--primary);
      box-shadow: var(--shadow-md);
    }
    .badge-subdomain {
      background-color: #E0F2FE;
      color: #0369A1;
      font-size: 12px;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 4px;
      display: inline-block;
      margin-bottom: 6px;
    }
    .stat-metric {
      font-size: 20px;
      font-weight: 800;
      color: var(--text-main);
    }
    .stat-label {
      font-size: 11px;
      text-transform: uppercase;
      color: var(--text-muted);
      font-weight: 700;
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <header class="app-navbar">
    <div class="navbar-container">
      <div class="brand-section">
        <a href="index.php">
          <img src="https://suitable.cl/wp-content/uploads/2025/04/logo_verde-350x128.png" alt="Suitable" class="brand-logo-img">
        </a>
        <span class="brand-division-tag">B2B Outreach</span>
      </div>

      <nav>
        <ul class="nav-menu">
          <li><a href="index.php" class="nav-link">📊 Dashboard</a></li>
          <li><a href="clients.php" class="nav-link">👥 Pipeline</a></li>
          <li><a href="campaigns.php" class="nav-link">🚀 Campañas IA</a></li>
          <li><a href="analytics.php" class="nav-link">📈 Analítica &amp; Tráfico</a></li>
          <li><a href="ml_brain.php" class="nav-link">🧠 Cerebro ML</a></li>
          <li><a href="wc_wizard.php" class="nav-link active">🧙 Wizard WooCommerce</a></li>
          <li><a href="templates_view.php" class="nav-link">📑 Plantillas</a></li>
          <li><a href="settings.php" class="nav-link">⚙️ Ajustes</a></li>
        </ul>
      </nav>

      <div class="user-controls">
        <div class="user-badge">
          <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
          <div>
            <div style="font-weight: 700; line-height: 1.1;"><?= htmlspecialchars($user['name']) ?></div>
            <span class="role-tag <?= $user['role'] === 'admin' ? 'role-admin' : 'role-enviador' ?>">
              <?= strtoupper($user['role']) ?>
            </span>
          </div>
        </div>
        <a href="logout.php" class="btn btn-secondary btn-sm">Salir 🚪</a>
      </div>
    </div>
  </header>

  <main class="main-container">
    
    <div class="page-header">
      <div class="page-title-group">
        <h1>Wizard Detector de Instalaciones WooCommerce</h1>
        <p class="page-subtitle">Escanee automáticamente su panel MySQL (Laragon / cPanel / subdominios) y detecte pedidos, ventas y tiendas activas</p>
      </div>

      <div class="header-actions">
        <a href="analytics.php" class="btn btn-secondary btn-sm">📈 Ver Dashboard Analítico</a>
        <a href="ml_brain.php" class="btn btn-secondary btn-sm">🧠 Ver Cerebro ML</a>
      </div>
    </div>

    <?php if ($imported_msg): ?>
      <div style="background-color: #ECFDF5; border: 1px solid #6EE7B7; border-radius: var(--radius-md); padding: 18px; margin-bottom: 20px;">
        <h3 style="color: #065F46; font-size: 15px; font-weight: 700; margin-bottom: 4px;">
          ✓ ¡Sincronización Completada!
        </h3>
        <p style="color: #047857; font-size: 13px; margin: 0;">
          <?= htmlspecialchars($imported_msg) ?>
        </p>
        <div style="margin-top: 12px; display: flex; gap: 10px;">
          <a href="analytics.php" class="btn btn-primary btn-sm">Ver Métricas de Tráfico &amp; Ventas →</a>
          <a href="ml_brain.php" class="btn btn-secondary btn-sm">Analizar Tendencias en Cerebro ML →</a>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($scan_error): ?>
      <div style="background-color: #FEE2E2; border: 1px solid #FCA5A5; border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px; color: #991B1B; font-size: 13px;">
        <strong>⚠ Aviso de Conexión:</strong> <?= htmlspecialchars($scan_error) ?>
      </div>
    <?php endif; ?>

    <!-- PARÁMETROS DE CONEXIÓN MYSQL -->
    <div class="wizard-card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <div>
          <h2 style="font-size: 16px; font-weight: 800; color: var(--text-main);">
            🔌 Servidor de Base de Datos MySQL (Panel / cPanel / Localhost)
          </h2>
          <p style="font-size: 12px; color: var(--text-muted);">
            Configuración de acceso para escanear esquemas de bases de datos
          </p>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('form-scan-params').style.display = document.getElementById('form-scan-params').style.display === 'none' ? 'block' : 'none'">
          ⚙️ Modificar Credenciales MySQL
        </button>
      </div>

      <form id="form-scan-params" method="POST" action="wc_wizard.php" style="display: <?= $scan_error ? 'block' : 'none' ?>; border-top: 1px solid var(--border-light); padding-top: 16px;">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Host de Base de Datos (ej. localhost o IP cPanel)</label>
            <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($db_host) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Puerto</label>
            <input type="number" name="db_port" class="form-control" value="<?= htmlspecialchars((string)$db_port) ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Usuario MySQL (cPanel / Laragon)</label>
            <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($db_user) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Contraseña MySQL</label>
            <input type="password" name="db_pass" class="form-control" value="<?= htmlspecialchars($db_pass) ?>" placeholder="Dejar vacío si no tiene contraseña">
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-sm">
          🔍 Re-escanear Servidor MySQL
        </button>
      </form>
    </div>

    <!-- RESULTADOS DEL ESCANEO (INSTALACIONES DETECTADAS) -->
    <div style="margin-bottom: 30px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-size: 18px; font-weight: 800; color: var(--text-main);">
          🏪 Tiendas &amp; Subdominios WooCommerce Detectados (<?= count($detected_installs) ?>)
        </h2>
        <span class="badge badge-teal">Escaneo Activo</span>
      </div>

      <?php if (empty($detected_installs)): ?>
        <div class="wizard-card" style="text-align: center; padding: 40px;">
          <div style="font-size: 40px; margin-bottom: 12px;">🔍</div>
          <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">
            No se detectaron tablas de WooCommerce en las bases de datos escaneadas
          </h3>
          <p style="font-size: 13px; color: var(--text-muted); max-width: 500px; margin: 0 auto 18px auto;">
            Verifique que el usuario de MySQL tenga permisos para listar bases de datos, o importe la base de datos de WooCommerce de Suitable en su entorno.
          </p>
          <a href="setup_wc_demo_db.php" class="btn btn-primary btn-sm">
            ⚡ Crear Base de Datos de Prueba 'suitable_woocommerce'
          </a>
        </div>
      <?php else: ?>
        <?php foreach ($detected_installs as $inst): ?>
          <div class="install-preview-card">
            
            <div style="max-width: 380px;">
              <span class="badge-subdomain">🌐 <?= htmlspecialchars($inst['subdomain']) ?></span>
              <h3 style="font-size: 16px; font-weight: 800; color: var(--text-main); margin: 4px 0;">
                <?= htmlspecialchars($inst['site_name']) ?>
              </h3>
              <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                🗄️ <strong>Base de Datos:</strong> <code><?= htmlspecialchars($inst['db_name']) ?></code> &nbsp;|&nbsp; 
                Prefijo: <code><?= htmlspecialchars($inst['prefix']) ?></code>
              </div>
              <div style="font-size: 11px; color: #0F766E; margin-top: 4px;">
                🔗 URL: <a href="<?= htmlspecialchars($inst['site_url']) ?>" target="_blank"><?= htmlspecialchars($inst['site_url']) ?></a>
              </div>
            </div>

            <!-- PREVIEWS STATS: PEDIDOS Y VENTAS -->
            <div style="display: flex; gap: 24px; align-items: center; border-left: 2px solid var(--border-light); padding-left: 20px;">
              <div>
                <div class="stat-label">Pedidos Detectados</div>
                <div class="stat-metric" style="color: var(--primary);">
                  📦 <?= number_format($inst['total_orders'], 0, ',', '.') ?>
                </div>
                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                  <?= $inst['last_order_date'] ? 'Último: ' . date('d/m/Y', strtotime($inst['last_order_date'])) : 'Sin órdenes' ?>
                </div>
              </div>

              <div>
                <div class="stat-label">Ventas Históricas</div>
                <div class="stat-metric" style="color: #059669;">
                  💰 $<?= number_format($inst['total_sales'], 0, ',', '.') ?> <span style="font-size: 12px; font-weight: 600;">CLP</span>
                </div>
                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                  Ticket Promedio: $<?= $inst['total_orders'] > 0 ? number_format(round($inst['total_sales'] / $inst['total_orders']), 0, ',', '.') : 0 ?>
                </div>
              </div>
            </div>

            <!-- BOTÓN DE CONEXIÓN E IMPORTACIÓN -->
            <div>
              <form method="POST" action="wc_wizard.php">
                <input type="hidden" name="db_host" value="<?= htmlspecialchars($db_host) ?>">
                <input type="hidden" name="db_user" value="<?= htmlspecialchars($db_user) ?>">
                <input type="hidden" name="db_pass" value="<?= htmlspecialchars($db_pass) ?>">
                <input type="hidden" name="db_port" value="<?= htmlspecialchars((string)$db_port) ?>">
                <input type="hidden" name="target_db" value="<?= htmlspecialchars($inst['db_name']) ?>">
                <input type="hidden" name="target_prefix" value="<?= htmlspecialchars($inst['prefix']) ?>">
                <input type="hidden" name="import_db" value="1">
                
                <button type="submit" class="btn btn-primary" style="padding: 12px 20px; font-weight: 700;">
                  ⚡ Conectar e Importar Tienda →
                </button>
              </form>
            </div>

          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </main>

  <script src="assets/js/app.js"></script>
</body>
</html>
