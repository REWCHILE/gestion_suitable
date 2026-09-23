<?php
require_once __DIR__ . '/config.php';
require_auth();

$user = current_user();
$db = get_db();

// Filter: period
$period = $_GET['period'] ?? 'month'; // 'day', 'week', 'month', 'year'

// Fetch aggregate metrics from orders and traffic
$total_revenue = $db->query("SELECT SUM(total_amount) FROM orders")->fetchColumn() ?: 0;
$total_orders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0;
$aov = $total_orders > 0 ? round($total_revenue / $total_orders) : 0;

// Marketing metrics averages
$marketing = $db->query("
    SELECT AVG(ctr) as avg_ctr, AVG(cpa) as avg_cpa, AVG(cvr) as avg_cvr, SUM(sessions) as total_sessions, SUM(visitors) as total_visitors
    FROM traffic_metrics
")->fetch();

$avg_ctr = round($marketing['avg_ctr'] ?? 3.8, 2);
$avg_cpa = round($marketing['avg_cpa'] ?? 14500);
$avg_cvr = round($marketing['avg_cvr'] ?? 2.9, 2);
$total_sessions = intval($marketing['total_sessions'] ?? 38400);

// Fetch time series data for the selected period
if ($period === 'day') {
    $time_series = $db->query("
        SELECT strftime('%d/%m', date_created) as label, COUNT(*) as orders_count, SUM(total_amount) as revenue 
        FROM orders 
        WHERE date_created >= date('now', '-7 days')
        GROUP BY strftime('%d/%m', date_created)
        ORDER BY MIN(date_created) ASC
    ")->fetchAll();
} elseif ($period === 'week') {
    $time_series = $db->query("
        SELECT 'Sem ' || strftime('%W', date_created) as label, COUNT(*) as orders_count, SUM(total_amount) as revenue 
        FROM orders 
        WHERE date_created >= date('now', '-60 days')
        GROUP BY strftime('%W', date_created)
        ORDER BY MIN(date_created) ASC
    ")->fetchAll();
} elseif ($period === 'year') {
    $time_series = $db->query("
        SELECT strftime('%Y', date_created) as label, COUNT(*) as orders_count, SUM(total_amount) as revenue 
        FROM orders 
        GROUP BY strftime('%Y', date_created)
        ORDER BY MIN(date_created) ASC
    ")->fetchAll();
} else { // default 'month'
    $time_series = $db->query("
        SELECT strftime('%m/%Y', date_created) as label, COUNT(*) as orders_count, SUM(total_amount) as revenue 
        FROM orders 
        WHERE date_created >= date('now', '-12 months')
        GROUP BY strftime('%Y-%m', date_created)
        ORDER BY MIN(date_created) ASC
    ")->fetchAll();
}

// Top Selling Categories
$top_categories = $db->query("
    SELECT category, COUNT(*) as items_sold, SUM(subtotal) as total_revenue
    FROM order_items
    GROUP BY category
    ORDER BY total_revenue DESC
    LIMIT 5
")->fetchAll();

// Top Colors Sold
$top_colors = $db->query("
    SELECT variation_color, COUNT(*) as count_sold
    FROM order_items
    WHERE variation_color IS NOT NULL AND variation_color != ''
    GROUP BY variation_color
    ORDER BY count_sold DESC
    LIMIT 6
")->fetchAll();

// Max revenue in series for SVG chart scaling
$max_rev = 1;
foreach ($time_series as $ts) {
    if ($ts['revenue'] > $max_rev) $max_rev = $ts['revenue'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analítica &amp; Tráfico E-commerce | Suitable</title>
  <link rel="stylesheet" href="assets/css/app.css">
  <style>
    .time-filter-bar {
      display: flex;
      background: white;
      border: 1px solid var(--border-light);
      border-radius: var(--radius-sm);
      padding: 4px;
      gap: 4px;
    }
    .time-filter-btn {
      padding: 6px 14px;
      font-size: 13px;
      font-weight: 700;
      color: var(--text-muted);
      border-radius: 4px;
      text-decoration: none;
      transition: var(--transition);
    }
    .time-filter-btn.active {
      background-color: var(--primary);
      color: white;
    }
    .chart-container {
      background-color: white;
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      padding: 24px;
      margin-bottom: 24px;
      box-shadow: var(--shadow-sm);
    }
    .chart-bars {
      display: flex;
      align-items: flex-end;
      gap: 16px;
      height: 220px;
      padding-top: 20px;
      border-bottom: 1px solid var(--border-light);
    }
    .chart-bar-col {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      height: 100%;
      justify-content: flex-end;
    }
    .chart-bar {
      width: 100%;
      max-width: 44px;
      background: var(--primary-gradient);
      border-radius: 4px 4px 0 0;
      transition: height 0.5s ease;
      position: relative;
    }
    .chart-bar:hover {
      background: linear-gradient(135deg, #25a8a8 0%, #156B6B 100%);
    }
    .chart-label {
      font-size: 11px;
      color: var(--text-muted);
      margin-top: 8px;
      white-space: nowrap;
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
          <li><a href="analytics.php" class="nav-link active">📈 Analítica &amp; Tráfico</a></li>
          <li><a href="ml_brain.php" class="nav-link">🧠 Cerebro ML</a></li>
          <li><a href="wc_wizard.php" class="nav-link">🧙 Wizard WooCommerce</a></li>
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
        <h1>Rendimiento E-commerce &amp; Métricas de Tráfico</h1>
        <p class="page-subtitle">Historial de pedidos WooCommerce sincronizados, conversión, CTR y costo por adquisición</p>
      </div>

      <div class="header-actions">
        <!-- TIME FILTER SWITCHER -->
        <div class="time-filter-bar">
          <a href="analytics.php?period=day" class="time-filter-btn <?= $period === 'day' ? 'active' : '' ?>">Día</a>
          <a href="analytics.php?period=week" class="time-filter-btn <?= $period === 'week' ? 'active' : '' ?>">Semana</a>
          <a href="analytics.php?period=month" class="time-filter-btn <?= $period === 'month' ? 'active' : '' ?>">Mes</a>
          <a href="analytics.php?period=year" class="time-filter-btn <?= $period === 'year' ? 'active' : '' ?>">Año</a>
        </div>

        <a href="wc_wizard.php" class="btn btn-primary btn-sm">
          🧙 Wizard de Detección WooCommerce
        </a>
      </div>
    </div>

    <!-- CARDS DE MÉTRICAS CLAVE (CTR, CPA, AOV, CVR, INGRESOS) -->
    <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));">
      
      <!-- INGRESOS TOTALES -->
      <div class="kpi-card">
        <div class="kpi-header">
          <span class="kpi-title">Ingresos Históricos</span>
          <div class="kpi-icon" style="background-color: #DEF4EC; color: #03543F;">💰</div>
        </div>
        <div class="kpi-val" style="color: #059669;">
          $<?= number_format($total_revenue, 0, ',', '.') ?>
        </div>
        <div class="kpi-trend">CLP ventas totales WooCommerce</div>
      </div>

      <!-- PEDIDOS TOTALES -->
      <div class="kpi-card">
        <div class="kpi-header">
          <span class="kpi-title">Pedidos Sincronizados</span>
          <div class="kpi-icon" style="background-color: #E0F2FE; color: #0284C7;">📦</div>
        </div>
        <div class="kpi-val"><?= number_format($total_orders, 0, ',', '.') ?></div>
        <div class="kpi-trend">Órdenes cerradas en tienda</div>
      </div>

      <!-- AOV TICKET PROMEDIO -->
      <div class="kpi-card">
        <div class="kpi-header">
          <span class="kpi-title">Ticket Promedio (AOV)</span>
          <div class="kpi-icon" style="background-color: #F3E8FF; color: #7E22CE;">🛒</div>
        </div>
        <div class="kpi-val">$<?= number_format($aov, 0, ',', '.') ?></div>
        <div class="kpi-trend">Valor medio por pedido</div>
      </div>

      <!-- CTR DE MARKETING -->
      <div class="kpi-card">
        <div class="kpi-header">
          <span class="kpi-title">CTR (Click Through)</span>
          <div class="kpi-icon" style="background-color: #CCFBF1; color: #0F766E;">🎯</div>
        </div>
        <div class="kpi-val" style="color: #1E8888;"><?= $avg_ctr ?>%</div>
        <div class="kpi-trend">Tasa clics en campañas email</div>
      </div>

      <!-- CPA COSTO POR ADQUISICIÓN -->
      <div class="kpi-card">
        <div class="kpi-header">
          <span class="kpi-title">CPA Adquisición</span>
          <div class="kpi-icon" style="background-color: #FEF3C7; color: #D97706;">🏷️</div>
        </div>
        <div class="kpi-val">$<?= number_format($avg_cpa, 0, ',', '.') ?></div>
        <div class="kpi-trend">Costo por cliente clínico nuevo</div>
      </div>

      <!-- CVR TASA DE CONVERSIÓN -->
      <div class="kpi-card">
        <div class="kpi-header">
          <span class="kpi-title">Conversión (CVR)</span>
          <div class="kpi-icon" style="background-color: #FFE4E6; color: #E11D48;">⚡</div>
        </div>
        <div class="kpi-val"><?= $avg_cvr ?>%</div>
        <div class="kpi-trend">De sesión a compra médica</div>
      </div>

    </div>

    <!-- GRÁFICO DE EVOLUCIÓN TEMPORAL -->
    <div class="chart-container">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
        <div>
          <h2 style="font-size: 16px; font-weight: 800; color: var(--text-main);">
            Evolución de Ventas e Ingresos (Corte: <?= ucfirst($period) ?>)
          </h2>
          <p style="font-size: 12px; color: var(--text-muted);">
            Comportamiento de compras de uniformes clínicos y dotaciones
          </p>
        </div>
        <span class="badge badge-teal">WooCommerce Data Sync</span>
      </div>

      <div class="chart-bars">
        <?php foreach ($time_series as $ts): 
          $pct = max(8, round(($ts['revenue'] / $max_rev) * 100));
        ?>
          <div class="chart-bar-col">
            <div class="chart-bar" style="height: <?= $pct ?>%;" title="<?= htmlspecialchars($ts['label']) ?>: $<?= number_format($ts['revenue'], 0, ',', '.') ?> CLP (<?= $ts['orders_count'] ?> pedidos)"></div>
            <div class="chart-label"><?= htmlspecialchars($ts['label']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- TWO COLUMNS: TOP CATEGORÍAS & COLORES MÁS DEMANDADOS -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
      
      <!-- TOP CATEGORÍAS -->
      <div class="table-card">
        <div class="table-header-bar">
          <strong style="font-size: 14px; color: var(--text-main);">Categorías Más Vendidas</strong>
          <span class="badge badge-blue">Ranking</span>
        </div>
        <table class="crm-table">
          <thead>
            <tr>
              <th>Categoría</th>
              <th>Unidades</th>
              <th style="text-align: right;">Ingresos</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($top_categories as $tc): ?>
              <tr>
                <td><strong><?= htmlspecialchars($tc['category']) ?></strong></td>
                <td><?= $tc['items_sold'] ?> prendas</td>
                <td style="text-align: right; font-weight: 700; color: #059669;">
                  $<?= number_format($tc['total_revenue'], 0, ',', '.') ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- TOP COLORES CLÍNICOS -->
      <div class="table-card">
        <div class="table-header-bar">
          <strong style="font-size: 14px; color: var(--text-main);">Preferencia de Colores Clínicos (Demanda)</strong>
          <span class="badge badge-teal">Tela Flex</span>
        </div>
        <table class="crm-table">
          <thead>
            <tr>
              <th>Color</th>
              <th>Prendas Vendidas</th>
              <th>Participación</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $tot_col = array_sum(array_column($top_colors, 'count_sold')) ?: 1;
            foreach ($top_colors as $color): 
              $share = round(($color['count_sold'] / $tot_col) * 100);
            ?>
              <tr>
                <td>
                  <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: var(--primary); margin-right: 6px;"></span>
                  <strong><?= htmlspecialchars($color['variation_color']) ?></strong>
                </td>
                <td><?= $color['count_sold'] ?> unidades</td>
                <td>
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="flex-grow: 1; height: 6px; background-color: #E2E8F0; border-radius: 3px; overflow: hidden;">
                      <div style="width: <?= $share ?>%; height: 100%; background-color: var(--primary);"></div>
                    </div>
                    <span style="font-size: 11px; font-weight: 700; color: var(--text-muted);"><?= $share ?>%</span>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>

  </main>

  <script src="assets/js/app.js"></script>
</body>
</html>
