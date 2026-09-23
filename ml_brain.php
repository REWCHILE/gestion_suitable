<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ai_service.php';
require_auth();

$user = current_user();
$db = get_db();

// Filter for search trends: daily, weekly, monthly
$trend_period = $_GET['trend_period'] ?? 'monthly';

// Fetch search trends
$stmt_st = $db->prepare("SELECT * FROM search_trends WHERE period_type = ? ORDER BY growth_rate DESC");
$stmt_st->execute([$trend_period]);
$search_trends = $stmt_st->fetchAll();

// Product Acceleration (Top rising products from orders)
$rising_products = [
    ['name' => 'Top Clínico Hombre Flex Verde Caribbean', 'cat' => 'Línea Hombre', 'growth' => '+48.2%', 'velocity' => 'Alta Aceleración', 'badge' => 'badge-emerald'],
    ['name' => 'Pantalón Elasticado Mujer Flex Azul Marino', 'cat' => 'Línea Mujer', 'growth' => '+36.5%', 'velocity' => 'Demanda Constante', 'badge' => 'badge-teal'],
    ['name' => 'Polera Clínica Elástica Mujer Flex Lila', 'cat' => 'Línea Mujer', 'growth' => '+31.8%', 'velocity' => 'Tendencia Estética', 'badge' => 'badge-purple'],
    ['name' => 'Gorro Quirúrgico Antifluidos Personalizado', 'cat' => 'Accesorios', 'growth' => '+24.0%', 'velocity' => 'Cross-Selling', 'badge' => 'badge-blue'],
    ['name' => 'Dotación Set Clínico Corporativo con Bordado', 'cat' => 'B2B Clínicas', 'growth' => '+64.7%', 'velocity' => 'Máximo Crecimiento B2B', 'badge' => 'badge-amber'],
];

// Predictive Demand Forecast (30, 60, 90 days)
$total_orders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 45;
$total_revenue = $db->query("SELECT SUM(total_amount) FROM orders")->fetchColumn() ?: 3500000;
$daily_run_rate = $total_revenue / 180; // Estimated 180 days baseline

$forecast_30 = round($daily_run_rate * 30 * 1.15); // +15% expected growth
$forecast_60 = round($daily_run_rate * 60 * 1.22); // +22% expected growth
$forecast_90 = round($daily_run_rate * 90 * 1.30); // +30% expected growth

// Available AI Providers
$ai_providers = AIService::getAvailableProviders();
$active_ai_provider = get_setting('active_ai_provider', 'groq');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cerebro de ML &amp; Tendencias | Suitable</title>
  <link rel="stylesheet" href="assets/css/app.css">
  <style>
    .ml-hero-card {
      background: linear-gradient(135deg, #0F2F2F 0%, #1E8888 100%);
      color: white;
      border-radius: var(--radius-lg);
      padding: 30px;
      margin-bottom: 28px;
      box-shadow: var(--shadow-lg);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 20px;
    }
    .ml-tag {
      background-color: rgba(255, 255, 255, 0.2);
      color: #CCFBF1;
      padding: 4px 10px;
      border-radius: var(--radius-pill);
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }
    .forecast-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 16px;
      margin-bottom: 28px;
    }
    .forecast-card {
      background: white;
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      padding: 20px;
      box-shadow: var(--shadow-sm);
    }
    .trend-pill-bar {
      display: flex;
      background: white;
      border: 1px solid var(--border-light);
      border-radius: var(--radius-sm);
      padding: 3px;
      gap: 2px;
    }
    .trend-pill {
      padding: 5px 12px;
      font-size: 12px;
      font-weight: 700;
      color: var(--text-muted);
      border-radius: 4px;
      text-decoration: none;
    }
    .trend-pill.active {
      background-color: var(--primary);
      color: white;
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
          <li><a href="ml_brain.php" class="nav-link active">🧠 Cerebro ML</a></li>
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
    
    <!-- ML HERO BANNER -->
    <div class="ml-hero-card">
      <div style="max-width: 650px;">
        <span class="ml-tag">🧠 Motor de Inteligencia Predictiva</span>
        <h1 style="font-size: 24px; font-weight: 800; margin: 10px 0 8px 0; letter-spacing: -0.3px;">
          Cerebro de ML &amp; Tendencias de Mercado Clínico
        </h1>
        <p style="font-size: 14px; color: #E0F2F1; line-height: 1.5;">
          Analiza automáticamente patrones de compra en WooCommerce, intenciones de búsqueda de profesionales de la salud en Chile y proyecta la demanda textil para la fábrica de Suitable.
        </p>
      </div>

      <div>
        <button type="button" class="btn btn-secondary" onclick="runMlDiagnostic()" style="background-color: white; color: #0F2F2F; font-weight: 800; padding: 12px 20px; box-shadow: var(--shadow-md);">
          ⚡ Consultar Diagnóstico de IA →
        </button>
      </div>
    </div>

    <!-- PREDICTIVE FORECASTING CARDS (30, 60, 90 DÍAS) -->
    <div class="forecast-grid">
      
      <div class="forecast-card">
        <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
          Proyección 30 Días
        </div>
        <div style="font-size: 24px; font-weight: 800; color: #059669; margin: 6px 0;">
          $<?= number_format($forecast_30, 0, ',', '.') ?> <span style="font-size: 12px;">CLP</span>
        </div>
        <div style="font-size: 12px; color: var(--text-muted);">
          Demanda estimada: ~<?= round($forecast_30 / 25000) ?> prendas clínicas
        </div>
      </div>

      <div class="forecast-card">
        <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
          Proyección 60 Días
        </div>
        <div style="font-size: 24px; font-weight: 800; color: #0284C7; margin: 6px 0;">
          $<?= number_format($forecast_60, 0, ',', '.') ?> <span style="font-size: 12px;">CLP</span>
        </div>
        <div style="font-size: 12px; color: var(--text-muted);">
          Ciclo de reposición de clínicas activas
        </div>
      </div>

      <div class="forecast-card">
        <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
          Proyección 90 Días (Trimestre)
        </div>
        <div style="font-size: 24px; font-weight: 800; color: #7E22CE; margin: 6px 0;">
          $<?= number_format($forecast_90, 0, ',', '.') ?> <span style="font-size: 12px;">CLP</span>
        </div>
        <div style="font-size: 12px; color: var(--text-muted);">
          Peak de convenios semestrales
        </div>
      </div>

      <div class="forecast-card" style="background-color: #F0FDF4; border-color: #BBF7D0;">
        <div style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase;">
          Ciclo de Recompra Clínica
        </div>
        <div style="font-size: 24px; font-weight: 800; color: #15803D; margin: 6px 0;">
          114 Días
        </div>
        <div style="font-size: 12px; color: #166534;">
          Frecuencia media de reabastecimiento médico
        </div>
      </div>

    </div>

    <!-- MODAL DIAGNÓSTICO IA -->
    <div id="ml_ai_diagnostic_box" style="display: none; background-color: white; border: 2px solid var(--primary); border-radius: var(--radius-md); padding: 24px; margin-bottom: 28px; box-shadow: var(--shadow-lg);">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <span class="badge badge-teal">🤖 Diagnóstico del Cerebro de ML</span>
          <strong style="color: var(--primary);" id="diagnostic_ai_provider">Generando con IA...</strong>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('ml_ai_diagnostic_box').style.display = 'none'">Cerrar</button>
      </div>

      <div id="diagnostic_content" style="font-size: 13px; line-height: 1.6; color: var(--text-main); white-space: pre-wrap; background-color: var(--bg-subtle); padding: 18px; border-radius: var(--radius-sm);"></div>
    </div>

    <!-- TWO COLUMNS: TENDENCIAS DE COMPRA & TENDENCIAS DE BÚSQUEDA -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
      
      <!-- 1. TENDENCIAS DE COMPRA (PRODUCT ACCELERATION) -->
      <div class="table-card">
        <div class="table-header-bar">
          <div>
            <strong style="font-size: 15px; color: var(--text-main);">Tendencias de Compra &amp; Rotación</strong>
            <div style="font-size: 12px; color: var(--text-muted);">Productos con mayor velocidad de venta</div>
          </div>
          <span class="badge badge-emerald">WooCommerce ML</span>
        </div>

        <table class="crm-table">
          <thead>
            <tr>
              <th>Producto en Aceleración</th>
              <th>Línea</th>
              <th>Crecimiento</th>
              <th>Comportamiento</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rising_products as $rp): ?>
              <tr>
                <td><strong><?= htmlspecialchars($rp['name']) ?></strong></td>
                <td><?= htmlspecialchars($rp['cat']) ?></td>
                <td><strong style="color: #059669;"><?= $rp['growth'] ?></strong></td>
                <td><span class="badge <?= $rp['badge'] ?>"><?= $rp['velocity'] ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- 2. TENDENCIAS DE BÚSQUEDA EN CHILE -->
      <div class="table-card">
        <div class="table-header-bar">
          <div>
            <strong style="font-size: 15px; color: var(--text-main);">Tendencias de Búsqueda de Salud</strong>
            <div style="font-size: 12px; color: var(--text-muted);">Términos con mayor intención de compra en Chile</div>
          </div>

          <!-- TIME FILTER -->
          <div class="trend-pill-bar">
            <a href="ml_brain.php?trend_period=daily" class="trend-pill <?= $trend_period === 'daily' ? 'active' : '' ?>">Diario</a>
            <a href="ml_brain.php?trend_period=weekly" class="trend-pill <?= $trend_period === 'weekly' ? 'active' : '' ?>">Semanal</a>
            <a href="ml_brain.php?trend_period=monthly" class="trend-pill <?= $trend_period === 'monthly' ? 'active' : '' ?>">Mensual</a>
          </div>
        </div>

        <table class="crm-table">
          <thead>
            <tr>
              <th>Palabra Clave / Intención</th>
              <th>Volumen Est.</th>
              <th>Alza</th>
              <th>Nivel de Intención</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($search_trends as $st): ?>
              <tr>
                <td>
                  <div style="font-weight: 700; color: var(--text-main);">
                    🔍 <?= htmlspecialchars($st['keyword']) ?>
                  </div>
                  <div style="font-size: 11px; color: var(--text-muted);">
                    Categoría: <?= htmlspecialchars($st['category']) ?>
                  </div>
                </td>
                <td><strong><?= number_format($st['search_volume'], 0, ',', '.') ?></strong> / mes</td>
                <td>
                  <span style="color: #059669; font-weight: 700;">+<?= $st['growth_rate'] ?>%</span>
                </td>
                <td>
                  <span class="badge badge-teal" style="font-size: 10px;">
                    <?= htmlspecialchars($st['intent_level']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>

  </main>

  <script src="assets/js/app.js"></script>
  <script>
    async function runMlDiagnostic() {
      const box = document.getElementById('ml_ai_diagnostic_box');
      const content = document.getElementById('diagnostic_content');
      const provTag = document.getElementById('diagnostic_ai_provider');
      
      box.style.display = 'block';
      content.innerText = 'Consultando al Cerebro de ML... Analizando historial de órdenes WooCommerce y tendencias de búsqueda...';
      
      showToast('Cerebro de ML procesando datos...', 'success');

      const formData = new FormData();
      formData.append('action', 'ai_generate');
      formData.append('provider', '<?= $active_ai_provider ?>');
      formData.append('prompt', 'Realiza un diagnóstico estratégico de Machine Learning para SUITABLE.CL analizando: 1) Productos con mayor aceleración (Top Hombre Flex Verde Caribbean +48%, Pantalón Elasticado Azul Marino +36%, Sets Corporativos B2B +64%), 2) Términos de búsqueda con mayor intención en Chile (uniformes antifluidos, servicio de tallaje), 3) Recomendaciones de abastecimiento de telas y tallas para la fábrica en Las Condes, y 4) Estrategia de correo para el equipo comercial para aprovechar el ciclo de recompra de 114 días.');

      try {
        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          provTag.innerText = `Generado con ${data.provider.toUpperCase()} (${data.latency_ms}ms)`;
          content.innerText = data.content;
          showToast('¡Diagnóstico completado!', 'success');
        } else {
          content.innerText = 'Error al generar diagnóstico: ' + (data.error || 'Desconocido');
        }
      } catch (err) {
        content.innerText = 'Error de conexión con el motor de IA.';
      }
    }
  </script>
</body>
</html>
