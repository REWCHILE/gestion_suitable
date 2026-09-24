<?php
require_once __DIR__ . '/config.php';
require_auth();

$user = current_user();
$db = get_db();

// Filter parameters: DEFAULT VIEW IS 'table' AS REQUESTED
$status_filter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$view_mode = $_GET['view'] ?? 'table'; // 'table' by default

// Base query
$query = "SELECT * FROM clients WHERE 1=1";
$params = [];

if ($status_filter) {
    $query .= " AND estado = ?";
    $params[] = $status_filter;
}

if ($search) {
    $query .= " AND (empresa LIKE ? OR contacto_nombre LIKE ? OR email LIKE ? OR region_comuna LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY updated_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$clients = $stmt->fetchAll();

// Group clients by status for Kanban & stage definitions
$columns = [
    'nuevo' => [
        'title' => 'Nuevos',
        'icon' => '📥',
        'color' => '#3B82F6',
        'step_num' => 1,
        'step_name' => '1. Prospección',
        'step_desc' => 'Entrada y calificación de clínicas o mutuales.',
        'step_action' => 'Validar encargado de compras',
        'badge' => 'badge-blue',
        'clients' => [],
        'total_monto' => 0
    ],
    'correo_1_enviado' => [
        'title' => 'Correo 1 (Flex)',
        'icon' => '✉️',
        'color' => '#6366F1',
        'step_num' => 2,
        'step_name' => '2. Presentación Flex',
        'step_desc' => 'Envío Plantilla 1: Antifluidos y catálogo clínico.',
        'step_action' => 'Presentar telas y tecnología',
        'badge' => 'badge-indigo',
        'clients' => [],
        'total_monto' => 0
    ],
    'correo_2_enviado' => [
        'title' => 'Correo 2 (B2B)',
        'icon' => '🚀',
        'color' => '#8B5CF6',
        'step_num' => 3,
        'step_name' => '3. Propuesta B2B',
        'step_desc' => 'Envío Plantilla 2: Fábrica chilena y 6M garantía.',
        'step_action' => 'Ofrecer servicio de tallaje',
        'badge' => 'badge-purple',
        'clients' => [],
        'total_monto' => 0
    ],
    'tallaje_agendado' => [
        'title' => 'Tallaje en Terreno',
        'icon' => '📏',
        'color' => '#F59E0B',
        'step_num' => 4,
        'step_name' => '4. Tallaje en Terreno',
        'step_desc' => '¡Diferenciador Clave! Muestras y percheros in situ.',
        'step_action' => 'Prueba en vivo médicos (XS-3XL)',
        'badge' => 'badge-amber',
        'clients' => [],
        'total_monto' => 0
    ],
    'cotizacion_enviada' => [
        'title' => 'Cotización Enviada',
        'icon' => '💼',
        'color' => '#10B981',
        'step_num' => 5,
        'step_name' => '5. Cotización Formal',
        'step_desc' => 'Propuesta económica por volumen y bordados.',
        'step_action' => 'Seguimiento orden de compra',
        'badge' => 'badge-teal',
        'clients' => [],
        'total_monto' => 0
    ],
    'ganado' => [
        'title' => 'Venta Ganada',
        'icon' => '🏆',
        'color' => '#059669',
        'step_num' => 6,
        'step_name' => '6. Convenio Cerrado',
        'step_desc' => 'Contrato firmado. Confección en taller y entrega.',
        'step_action' => 'Recompra a 114 días',
        'badge' => 'badge-emerald',
        'clients' => [],
        'total_monto' => 0
    ],
];

$total_pipeline_monto = 0;
$total_personal = 0;
$total_tallajes = 0;

foreach ($clients as $c) {
    $st = $c['estado'];
    if (isset($columns[$st])) {
        $columns[$st]['clients'][] = $c;
        if (!empty($c['monto_cotizacion'])) {
            $columns[$st]['total_monto'] += floatval($c['monto_cotizacion']);
            $total_pipeline_monto += floatval($c['monto_cotizacion']);
        }
    }
    if (!empty($c['tamano_equipo'])) {
        $total_personal += intval($c['tamano_equipo']);
    }
    if (!empty($c['fecha_tallaje']) || $c['estado'] === 'tallaje_agendado') {
        $total_tallajes++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pipeline de Clínicas &amp; Embudo B2B | Suitable</title>
  <link rel="stylesheet" href="assets/css/app.css?v=<?= time() ?>">
  <style>
    /* CUSTOM BRAND SCROLLBARS (SUITABLE TEAL #1E8888) */
    ::-webkit-scrollbar {
      width: 9px;
      height: 9px;
    }
    ::-webkit-scrollbar-track {
      background: #E6F4F4;
      border-radius: 6px;
    }
    ::-webkit-scrollbar-thumb {
      background: #1E8888;
      border-radius: 6px;
      border: 2px solid #E6F4F4;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: #156B6B;
    }
    * {
      scrollbar-color: #1E8888 #E6F4F4;
      scrollbar-width: thin;
    }

    /* View switcher */
    .view-switcher {
      display: flex;
      background-color: #F1F5F9;
      border: 1px solid var(--border-light);
      border-radius: var(--radius-sm);
      padding: 3px;
      gap: 3px;
    }
    .view-btn {
      padding: 6px 14px;
      font-size: 13px;
      font-weight: 600;
      border: none;
      background: none;
      border-radius: 4px;
      cursor: pointer;
      color: var(--text-muted);
      text-decoration: none;
      transition: all 0.15s ease;
    }
    .view-btn.active {
      background-color: white;
      color: var(--primary);
      box-shadow: var(--shadow-sm);
    }

    /* Process Flow Hero Card (PLACED BELOW THE TABLE) */
    .pipeline-guide-card {
      background: #FFFFFF;
      border: 1px solid #E2E8F0;
      border-top: 4px solid var(--primary);
      border-radius: var(--radius-lg);
      padding: 22px 24px;
      margin-top: 28px;
      margin-bottom: 30px;
      box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
      position: relative;
    }

    .pipeline-guide-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
      margin-bottom: 18px;
    }

    .guide-tag {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: var(--primary);
      background: #E6F4F4;
      padding: 4px 10px;
      border-radius: 4px;
      margin-bottom: 6px;
    }

    .guide-title {
      font-size: 18px;
      font-weight: 800;
      color: #0F172A;
      margin-bottom: 3px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .guide-subtitle {
      font-size: 13px;
      color: #64748B;
    }

    .guide-stats-row {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .guide-stat-pill {
      background: #F8FAFC;
      border: 1px solid #E2E8F0;
      border-radius: 8px;
      padding: 8px 14px;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      min-width: 140px;
    }

    .guide-stat-val {
      font-size: 15px;
      font-weight: 800;
      color: #0F172A;
      line-height: 1.2;
    }

    .guide-stat-lbl {
      font-size: 10.5px;
      color: #64748B;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }

    /* Graphic Step Flow Grid */
    .pipeline-steps-grid {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 10px;
      position: relative;
    }

    .pipeline-step-box {
      background: #F8FAFC;
      border: 1px solid #E2E8F0;
      border-radius: 10px;
      padding: 12px 10px;
      display: flex;
      flex-direction: column;
      height: 100%;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      cursor: pointer;
    }

    .pipeline-step-box:hover {
      background: #FFFFFF;
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
      border-color: var(--step-accent, var(--primary));
    }

    .step-number-tag {
      font-size: 9.5px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 2px 6px;
      border-radius: 4px;
      display: inline-block;
      margin-bottom: 6px;
      align-self: flex-start;
    }

    .step-box-title {
      font-size: 12.5px;
      font-weight: 800;
      color: #0F172A;
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .step-box-desc {
      font-size: 11px;
      color: #475569;
      line-height: 1.35;
      margin-bottom: 8px;
      flex-grow: 1;
    }

    .step-box-action {
      font-size: 10px;
      font-weight: 700;
      color: #0F766E;
      background: #F0FDFA;
      border: 1px solid #CCFBF1;
      padding: 3px 6px;
      border-radius: 4px;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .step-key-highlight {
      border: 2px solid #F59E0B !important;
      background: #FFFBEB !important;
      box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15) !important;
    }

    .step-badge-key {
      position: absolute;
      top: -9px;
      right: 8px;
      background: #F59E0B;
      color: #FFFFFF;
      font-size: 8.5px;
      font-weight: 800;
      padding: 2px 6px;
      border-radius: 10px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* KANBAN MASTER WRAPPER & COLUMNS */
    .kanban-board-wrapper {
      display: flex !important;
      gap: 16px !important;
      overflow-x: auto !important;
      overflow-y: hidden !important;
      padding-bottom: 24px !important;
      align-items: flex-start !important;
      scrollbar-color: #1E8888 #E6F4F4 !important;
      scrollbar-width: thin !important;
    }

    .kanban-board-wrapper::-webkit-scrollbar {
      height: 9px;
    }
    .kanban-board-wrapper::-webkit-scrollbar-track {
      background: #E6F4F4;
      border-radius: 6px;
    }
    .kanban-board-wrapper::-webkit-scrollbar-thumb {
      background: #1E8888;
      border-radius: 6px;
      border: 2px solid #E6F4F4;
    }
    .kanban-board-wrapper::-webkit-scrollbar-thumb:hover {
      background: #156B6B;
    }

    /* KANBAN COLUMN - ABSOLUTELY NO INTERNAL HORIZONTAL SCROLLBAR */
    .kanban-col {
      flex: 0 0 295px !important;
      width: 295px !important;
      min-width: 295px !important;
      max-width: 295px !important;
      background-color: #F8FAFC !important;
      border: 1px solid #E2E8F0 !important;
      border-radius: 12px !important;
      padding: 12px !important;
      display: flex !important;
      flex-direction: column !important;
      gap: 12px !important;
      box-shadow: var(--shadow-sm) !important;
      overflow: hidden !important;
      overflow-x: hidden !important;
    }

    .kanban-col-header {
      padding: 10px 12px;
      background: #FFFFFF;
      border-radius: 8px;
      border: 1px solid #E2E8F0;
      border-left: 4px solid var(--col-color, var(--primary));
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .kanban-col-header-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .kanban-col-title {
      font-size: 13px;
      font-weight: 800;
      color: #0F172A;
      display: flex;
      align-items: center;
      gap: 6px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .kanban-count {
      background-color: #F1F5F9;
      color: #475569;
      padding: 2px 8px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 800;
      flex-shrink: 0;
    }

    .kanban-col-amount {
      font-size: 11px;
      font-weight: 700;
      color: #059669;
    }

    .kanban-cards {
      display: flex !important;
      flex-direction: column !important;
      gap: 12px !important;
      overflow: hidden !important;
      overflow-x: hidden !important;
      overflow-y: visible !important;
    }

    /* KANBAN CARDS - PERFECTLY CONSTRAINED */
    .kanban-card {
      background-color: #FFFFFF !important;
      border-radius: 10px !important;
      padding: 14px !important;
      box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04) !important;
      border: 1px solid #E2E8F0 !important;
      cursor: pointer !important;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
      position: relative !important;
      width: 100% !important;
      box-sizing: border-box !important;
      overflow: hidden !important;
      overflow-x: hidden !important;
    }

    .kanban-card:hover {
      box-shadow: 0 10px 24px rgba(15, 23, 42, 0.09) !important;
      border-color: var(--primary) !important;
      transform: translateY(-2px) !important;
    }

    .card-clinic-title {
      font-size: 13.5px;
      font-weight: 800;
      color: #0F172A;
      margin-bottom: 6px;
      display: flex;
      align-items: flex-start;
      gap: 6px;
      line-height: 1.3;
    }

    .card-contact-row {
      font-size: 12px;
      color: #475569;
      margin-bottom: 8px;
      display: flex;
      align-items: flex-start;
      gap: 6px;
    }

    .card-tags-row {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      margin-bottom: 10px;
    }

    .card-pill-tag {
      font-size: 10.5px;
      font-weight: 600;
      background: #F1F5F9;
      color: #475569;
      padding: 2px 7px;
      border-radius: 4px;
    }

    .card-tallaje-box {
      background-color: #FAF5FF;
      border: 1px solid #E9D5FF;
      color: #7E22CE;
      font-size: 11px;
      font-weight: 700;
      padding: 6px 8px;
      border-radius: 6px;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .card-monto-box {
      background-color: #ECFDF5;
      border: 1px solid #A7F3D0;
      color: #047857;
      font-size: 12px;
      font-weight: 800;
      padding: 6px 10px;
      border-radius: 6px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      white-space: nowrap;
    }

    /* CARD FOOTER - TWO CLEAN BALANCED ROWS */
    .kanban-card-footer {
      border-top: 1px solid #F1F5F9;
      padding-top: 10px;
      margin-top: 4px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .card-action-btns-row {
      display: flex;
      align-items: center;
      gap: 6px;
      width: 100%;
    }

    .btn-wa-pill {
      flex: 1;
      background: #25D366;
      color: white !important;
      font-size: 11px;
      font-weight: 700;
      padding: 6px 8px;
      border-radius: 6px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      transition: all 0.15s ease;
      white-space: nowrap;
      box-shadow: 0 2px 4px rgba(37, 211, 102, 0.2);
    }
    .btn-wa-pill:hover {
      background: #1EBE5D;
    }

    .btn-email-pill {
      flex: 1;
      background: var(--primary);
      color: white !important;
      font-size: 11px;
      font-weight: 700;
      padding: 6px 8px;
      border-radius: 6px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
      transition: all 0.15s ease;
      white-space: nowrap;
      box-shadow: 0 2px 4px rgba(30, 136, 136, 0.2);
    }
    .btn-email-pill:hover {
      background: var(--primary-hover);
    }

    .card-stage-move-row {
      display: flex;
      align-items: center;
      gap: 6px;
      width: 100%;
    }

    .quick-stage-select {
      flex: 1;
      font-size: 11px;
      padding: 4px 6px;
      border-radius: 6px;
      border: 1px solid #CBD5E1;
      background: #FFFFFF;
      color: #475569;
      font-weight: 600;
      cursor: pointer;
      width: 100%;
    }

    @media (max-width: 1200px) {
      .pipeline-steps-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }
    @media (max-width: 768px) {
      .pipeline-steps-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

  <!-- SIDEBAR NAVIGATION -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- MAIN -->
  <main class="main-container">
    
    <!-- PAGE TITLE -->
    <div class="page-header">
      <div class="page-title-group">
        <h1>Pipeline Comercial de Clínicas &amp; Hospitales</h1>
        <p class="page-subtitle">Gestión del embudo de prospección, agendamiento de tallaje en clínica y cotizaciones corporativas</p>
      </div>

      <div class="header-actions">
        <!-- View switcher -->
        <div class="view-switcher">
          <a href="clients.php?view=table<?= $search ? '&search='.urlencode($search) : '' ?>" class="view-btn <?= $view_mode === 'table' ? 'active' : '' ?>">
            📑 Tabla
          </a>
          <a href="clients.php?view=kanban<?= $search ? '&search='.urlencode($search) : '' ?>" class="view-btn <?= $view_mode === 'kanban' ? 'active' : '' ?>">
            📋 Tablero
          </a>
        </div>

        <button type="button" class="btn btn-primary" onclick="openModal('modal-new-client')">
          + Nueva Clínica
        </button>
      </div>
    </div>

    <!-- FILTER BAR -->
    <div class="table-card" style="padding: 14px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
      <form method="GET" action="clients.php" style="display: flex; gap: 10px; align-items: center; flex-grow: 1; max-width: 540px;">
        <input type="hidden" name="view" value="<?= htmlspecialchars($view_mode) ?>">
        <div class="search-input-wrap" style="flex-grow: 1;">
          <span class="search-icon">🔍</span>
          <input type="text" name="search" class="search-input" placeholder="Buscar por clínica, doctor/contacto o comuna..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn btn-secondary btn-sm" style="font-weight: 700;">Buscar</button>
        <?php if ($search): ?>
          <a href="clients.php?view=<?= $view_mode ?>" class="btn btn-secondary btn-sm" style="color: var(--text-muted);">Limpiar</a>
        <?php endif; ?>
      </form>

      <div style="font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
        <span>Mostrando <strong><?= count($clients) ?></strong> instituciones registradas</span>
        <?php if ($search): ?>
          <span class="badge badge-teal">Filtrado por: "<?= htmlspecialchars($search) ?>"</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- MAIN VIEW: TABLE (DEFAULT) OR KANBAN -->
    <?php if ($view_mode === 'table'): ?>
      
      <!-- TABLE VIEW (NOW DEFAULT) -->
      <div class="table-card" style="margin-bottom: 24px;">
        <table class="crm-table">
          <thead>
            <tr>
              <th>Institución / Clínica</th>
              <th>Contacto</th>
              <th>Teléfono / WhatsApp</th>
              <th>Estado Actual</th>
              <th>Tallaje / Cotización</th>
              <th>Último Envío</th>
              <th style="text-align: right;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($clients as $cli): 
              $st_info = get_status_info($cli['estado']);
              $phone_clean = preg_replace('/[^0-9]/', '', $cli['telefono'] ?? '');
            ?>
              <tr>
                <td>
                  <div class="client-name-bold">🏥 <?= htmlspecialchars($cli['empresa']) ?></div>
                  <div class="client-meta">📍 <?= htmlspecialchars($cli['region_comuna'] ?: 'RM') ?> • 👥 <?= $cli['tamano_equipo'] ?> profesionales</div>
                </td>
                <td>
                  <div style="font-weight: 700; color: #0F172A;"><?= htmlspecialchars($cli['contacto_nombre']) ?></div>
                  <div class="client-meta"><?= htmlspecialchars($cli['cargo']) ?> • <?= htmlspecialchars($cli['email']) ?></div>
                </td>
                <td>
                  <?php if ($phone_clean): ?>
                    <a href="https://wa.me/<?= $phone_clean ?>?text=Hola%20<?= urlencode($cli['contacto_nombre']) ?>,%20le%20escribo%20de%20Suitable" target="_blank" class="btn-wa-pill" style="display: inline-flex; padding: 4px 10px;">
                      💬 <?= htmlspecialchars($cli['telefono']) ?>
                    </a>
                  <?php else: ?>
                    <span style="color: var(--text-subtle);">No registrado</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?= $st_info['badge'] ?>"><?= $st_info['label'] ?></span>
                </td>
                <td>
                  <?php if ($cli['fecha_tallaje']): ?>
                    <div style="font-size: 12px; color: #7E22CE; font-weight: 700;">
                      📏 Tallaje: <?= date('d/m/Y', strtotime($cli['fecha_tallaje'])) ?>
                    </div>
                  <?php endif; ?>
                  <?php if ($cli['monto_cotizacion']): ?>
                    <div style="font-size: 12px; color: #059669; font-weight: 800;">
                      💰 $<?= number_format($cli['monto_cotizacion'], 0, ',', '.') ?> CLP
                    </div>
                  <?php endif; ?>
                  <?php if (!$cli['fecha_tallaje'] && !$cli['monto_cotizacion']): ?>
                    <span style="color: var(--text-subtle); font-size: 12px;">Sin agendar</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="font-size: 12px;">
                    <?= $cli['ultimo_envio_fecha'] ? date('d/m/Y H:i', strtotime($cli['ultimo_envio_fecha'])) : 'Pendiente' ?>
                  </div>
                  <?php if ($cli['ultimo_envio_tipo']): ?>
                    <span style="font-size: 10px; font-weight: 700; color: #0F766E;">
                      <?= $cli['ultimo_envio_tipo'] === 'plantilla_2' ? 'Plantilla 2 B2B' : 'Plantilla 1' ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td style="text-align: right;">
                  <button type="button" class="btn btn-secondary btn-sm" onclick='editClient(<?= json_encode($cli) ?>)'>
                    ✏️ Editar
                  </button>
                  <a href="send_outreach.php?client_id=<?= $cli['id'] ?>" class="btn btn-primary btn-sm">
                    ✉️ Enviar
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    <?php else: ?>

      <!-- KANBAN BOARD CONTAINER -->
      <div class="kanban-board-wrapper">
        <?php foreach ($columns as $status_key => $col): ?>
          <div class="kanban-col" id="col-<?= $status_key ?>" style="--col-color: <?= $col['color'] ?>;">
            
            <!-- COLUMN HEADER -->
            <div class="kanban-col-header">
              <div class="kanban-col-header-top">
                <span class="kanban-col-title" title="<?= htmlspecialchars($col['title']) ?>">
                  <span><?= $col['icon'] ?></span>
                  <span><?= $col['title'] ?></span>
                </span>
                <span class="kanban-count"><?= count($col['clients']) ?></span>
              </div>
              <?php if ($col['total_monto'] > 0): ?>
                <div class="kanban-col-amount">
                  💰 $<?= number_format($col['total_monto'], 0, ',', '.') ?> CLP
                </div>
              <?php else: ?>
                <div style="font-size: 10.5px; color: var(--text-muted);">
                  0 cotizaciones
                </div>
              <?php endif; ?>
            </div>

            <!-- CARDS CONTAINER -->
            <div class="kanban-cards">
              <?php if (empty($col['clients'])): ?>
                <div style="font-size: 12px; color: var(--text-subtle); text-align: center; padding: 28px 10px; background: #FFFFFF; border-radius: 8px; border: 1px dashed #CBD5E1;">
                  Sin clínicas en esta etapa
                </div>
              <?php else: ?>
                <?php foreach ($col['clients'] as $cli): 
                  $phone_clean = preg_replace('/[^0-9]/', '', $cli['telefono'] ?? '');
                ?>
                  <div class="kanban-card" onclick="editClient(<?= htmlspecialchars(json_encode($cli)) ?>)">
                    
                    <!-- CLINIC NAME -->
                    <div class="card-clinic-title">
                      <span>🏥</span>
                      <span><?= htmlspecialchars($cli['empresa']) ?></span>
                    </div>

                    <!-- CONTACT PERSON -->
                    <div class="card-contact-row">
                      <span>👤</span>
                      <div>
                        <strong><?= htmlspecialchars($cli['contacto_nombre']) ?></strong>
                        <?php if ($cli['cargo']): ?>
                          <span style="font-size: 11px; color: var(--text-muted); display: block; line-height: 1.2;"><?= htmlspecialchars($cli['cargo']) ?></span>
                        <?php endif; ?>
                      </div>
                    </div>

                    <!-- TAGS: COMUNA & TEAM -->
                    <div class="card-tags-row">
                      <span class="card-pill-tag">📍 <?= htmlspecialchars($cli['region_comuna'] ?: 'RM') ?></span>
                      <span class="card-pill-tag">👥 <?= $cli['tamano_equipo'] ?> profesionales</span>
                    </div>

                    <!-- TALLAJE SCHEDULED BOX -->
                    <?php if ($cli['fecha_tallaje']): ?>
                      <div class="card-tallaje-box">
                        <span>🗓️</span>
                        <span><strong>Tallaje en Clínica:</strong> <?= date('d/m/Y', strtotime($cli['fecha_tallaje'])) ?></span>
                      </div>
                    <?php endif; ?>

                    <!-- QUOTE AMOUNT BOX -->
                    <?php if ($cli['monto_cotizacion']): ?>
                      <div class="card-monto-box">
                        <span>💰 Cotización:</span>
                        <span>$<?= number_format($cli['monto_cotizacion'], 0, ',', '.') ?> CLP</span>
                      </div>
                    <?php endif; ?>

                    <!-- CARD ACTIONS: 2 CLEAN ROWS -->
                    <div class="kanban-card-footer" onclick="event.stopPropagation();">
                      
                      <!-- ROW 1: WHATSAPP + EMAIL -->
                      <div class="card-action-btns-row">
                        <?php if ($phone_clean): ?>
                          <a href="https://wa.me/<?= $phone_clean ?>?text=Hola%20<?= urlencode($cli['contacto_nombre']) ?>,%20le%20escribo%20de%20Suitable%20Uniformes%20Cl%C3%ADnicos" target="_blank" class="btn-wa-pill" title="Conversar por WhatsApp">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                            WhatsApp
                          </a>
                        <?php endif; ?>

                        <a href="send_outreach.php?client_id=<?= $cli['id'] ?>" class="btn-email-pill" title="Impactar con Campaña o Correo Brevo">
                          ✉️ Correo
                        </a>
                      </div>

                      <!-- ROW 2: CLEAN QUICK MOVE STAGE -->
                      <div class="card-stage-move-row">
                        <select class="quick-stage-select" title="Mover rápidamente de etapa" onchange="quickMoveStage(<?= $cli['id'] ?>, this.value)">
                          <option value="" disabled selected>Avanzar etapa... ▾</option>
                          <option value="nuevo">1. Nuevos Leads</option>
                          <option value="correo_1_enviado">2. Correo 1 (Flex)</option>
                          <option value="correo_2_enviado">3. Correo 2 (B2B)</option>
                          <option value="tallaje_agendado">4. Tallaje en Terreno</option>
                          <option value="cotizacion_enviada">5. Cotización Enviada</option>
                          <option value="ganado">6. Venta Ganada</option>
                        </select>
                      </div>

                    </div>

                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>

    <!-- DETALLE GRÁFICO DEL EMBUDO COMERCIAL: UBICADO ABAJO DE LA TABLA COMO FUE SOLICITADO -->
    <div class="pipeline-guide-card" id="pipeline-guide-box">
      
      <div class="pipeline-guide-header">
        <div>
          <span class="guide-tag">🏥 METODOLOGÍA COMERCIAL B2B SUITABLE</span>
          <div class="guide-title">
            <span>Ruta del Embudo de Ventas: ¿Qué se hace en cada etapa?</span>
          </div>
          <p class="guide-subtitle">
            Cada prospecto médico avanza en 6 pasos estratégicos: desde el primer correo hasta la prueba presencial de tallas y la orden corporativa.
          </p>
        </div>

        <!-- KPI SUMMARY PILLS -->
        <div class="guide-stats-row">
          <div class="guide-stat-pill">
            <span class="guide-stat-val" style="color: #059669;">$<?= number_format($total_pipeline_monto, 0, ',', '.') ?> CLP</span>
            <span class="guide-stat-lbl">💰 Monto en Pipeline</span>
          </div>
          <div class="guide-stat-pill">
            <span class="guide-stat-val" style="color: #7E22CE;"><?= $total_tallajes ?> agendados</span>
            <span class="guide-stat-lbl">📏 Tallajes en Terreno</span>
          </div>
          <div class="guide-stat-pill">
            <span class="guide-stat-val" style="color: var(--primary);"><?= number_format($total_personal, 0, ',', '.') ?> pers.</span>
            <span class="guide-stat-lbl">👥 Equipo a Uniformar</span>
          </div>
        </div>
      </div>

      <!-- 6-STEP PROCESS GRID -->
      <div class="pipeline-steps-grid">
        
        <!-- PASO 1 -->
        <div class="pipeline-step-box" style="--step-accent: #3B82F6;" onclick="filterOrScroll('nuevo')">
          <span class="step-number-tag" style="background: #EFF6FF; color: #1D4ED8;">Paso 1 • Entrada</span>
          <div class="step-box-title">📥 1. Prospección</div>
          <p class="step-box-desc">
            Carga de bases clínicas (CSV o manual). Identificación de jefaturas médicas, adquisiciones o RRHH.
          </p>
          <div class="step-box-action">
            <span>🎯</span> Acción: Calificar datos
          </div>
        </div>

        <!-- PASO 2 -->
        <div class="pipeline-step-box" style="--step-accent: #6366F1;" onclick="filterOrScroll('correo_1_enviado')">
          <span class="step-number-tag" style="background: #EEF2FF; color: #4338CA;">Paso 2 • Primer Contacto</span>
          <div class="step-box-title">✉️ 2. Presentación Flex</div>
          <p class="step-box-desc">
            Envío de <strong>Plantilla 1 Brevo</strong>. Foco en telas con elastano (Flex), repelencia a fluidos y catálogo clínico.
          </p>
          <div class="step-box-action">
            <span>📩</span> Acción: Enviar Correo 1
          </div>
        </div>

        <!-- PASO 3 -->
        <div class="pipeline-step-box" style="--step-accent: #8B5CF6;" onclick="filterOrScroll('correo_2_enviado')">
          <span class="step-number-tag" style="background: #F5F3FF; color: #6D28D9;">Paso 3 • Propuesta Valor</span>
          <div class="step-box-title">🚀 3. Propuesta B2B</div>
          <p class="step-box-desc">
            Envío de <strong>Plantilla 2 con IA</strong>. Enfoque: <em>Fabricación 100% Chilena</em>, <em>6 Meses de Garantía</em> y propuesta de tallaje.
          </p>
          <div class="step-box-action">
            <span>✨</span> Acción: Ofrecer Tallaje
          </div>
        </div>

        <!-- PASO 4: TALLAJE EN TERRENO (⭐ CLAVE DE VENTA) -->
        <div class="pipeline-step-box step-key-highlight" style="--step-accent: #F59E0B;" onclick="filterOrScroll('tallaje_agendado')">
          <span class="step-badge-key">⭐ CLAVE SUITABLE</span>
          <span class="step-number-tag" style="background: #FEF3C7; color: #B45309;">Paso 4 • En Terreno</span>
          <div class="step-box-title">📏 4. Tallaje Clínico</div>
          <p class="step-box-desc">
            <strong>Visita presencial a la clínica</strong> con percheros y talleros (XS a 3XL). Los médicos se prueban en vivo asegurando calce perfecto.
          </p>
          <div class="step-box-action" style="background: #FEF3C7; color: #B45309; border-color: #FDE68A;">
            <span>🗓️</span> Acción: Agendar Visita
          </div>
        </div>

        <!-- PASO 5 -->
        <div class="pipeline-step-box" style="--step-accent: #10B981;" onclick="filterOrScroll('cotizacion_enviada')">
          <span class="step-number-tag" style="background: #ECFDF5; color: #047857;">Paso 5 • Propuesta</span>
          <div class="step-box-title">💼 5. Cotización Formal</div>
          <p class="step-box-desc">
            Emisión de cotización consolidada con precios por volumen corporativo, desglose de tallas y bordado institucional.
          </p>
          <div class="step-box-action">
            <span>📄</span> Acción: Enviar Cotización
          </div>
        </div>

        <!-- PASO 6 -->
        <div class="pipeline-step-box" style="--step-accent: #059669;" onclick="filterOrScroll('ganado')">
          <span class="step-number-tag" style="background: #ECFDF5; color: #065F46;">Paso 6 • Cierre</span>
          <div class="step-box-title">🏆 6. Convenio Cerrado</div>
          <p class="step-box-desc">
            Venta ganada con orden de compra. Confección en taller Las Condes, entrega y seguimiento para ciclo de recompra (114 días).
          </p>
          <div class="step-box-action" style="background: #DCFCE7; color: #15803D; border-color: #BBF7D0;">
            <span>🎉</span> Acción: Producción y Entrega
          </div>
        </div>

      </div>

    </div>

  </main>

  <!-- MODAL: NUEVA CLÍNICA -->
  <div class="modal-backdrop" id="modal-new-client">
    <div class="modal-box">
      <div class="modal-header">
        <h3 class="modal-title">Registrar Nueva Clínica o Institución</h3>
        <button type="button" class="modal-close" onclick="closeModal('modal-new-client')">&times;</button>
      </div>
      <form id="form-new-client" onsubmit="saveClient(event, 'create')">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Nombre de la Institución / Clínica *</label>
            <input type="text" name="empresa" class="form-control" placeholder="Ej. RedSalud Providencia" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nombre del Contacto *</label>
              <input type="text" name="contacto_nombre" class="form-control" placeholder="Ej. Dra. Pamela Soto" required>
            </div>
            <div class="form-group">
              <label class="form-label">Cargo</label>
              <input type="text" name="cargo" class="form-control" placeholder="Ej. Jefa de Enfermería / Adquisiciones">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Correo Electrónico *</label>
              <input type="email" name="email" class="form-control" placeholder="contacto@clinica.cl" required>
            </div>
            <div class="form-group">
              <label class="form-label">Teléfono / WhatsApp</label>
              <input type="text" name="telefono" class="form-control" placeholder="+56912345678">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Comuna / Región</label>
              <input type="text" name="region_comuna" class="form-control" placeholder="Ej. Las Condes, Santiago" value="Santiago, RM">
            </div>
            <div class="form-group">
              <label class="form-label">Tamaño Estimado del Equipo</label>
              <input type="number" name="tamano_equipo" class="form-control" value="20" min="1">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Notas Comerciales / Requerimientos</label>
            <textarea name="notas" class="form-control" rows="3" placeholder="Colores solicitados, necesidad de bordado, etc."></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('modal-new-client')">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar Prospecto →</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: EDITAR CLÍNICA & TALLAJE -->
  <div class="modal-backdrop" id="modal-edit-client">
    <div class="modal-box">
      <div class="modal-header">
        <h3 class="modal-title" id="edit-modal-title">Seguimiento de Clínica</h3>
        <button type="button" class="modal-close" onclick="closeModal('modal-edit-client')">&times;</button>
      </div>
      <form id="form-edit-client" onsubmit="saveClient(event, 'update')">
        <input type="hidden" name="id" id="edit-id">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Institución</label>
              <input type="text" name="empresa" id="edit-empresa" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">Estado en Pipeline</label>
              <select name="estado" id="edit-estado" class="form-control">
                <option value="nuevo">1. Nuevos Leads</option>
                <option value="correo_1_enviado">2. Correo 1 Enviado (Flex)</option>
                <option value="correo_2_enviado">3. Correo 2 Enviado (B2B)</option>
                <option value="tallaje_agendado">4. Tallaje en Terreno Agendado</option>
                <option value="cotizacion_enviada">5. Cotización Enviada</option>
                <option value="ganado">6. Venta Ganada (Garantía 6M)</option>
                <option value="perdido">Descartado / Pausado</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Contacto</label>
              <input type="text" name="contacto_nombre" id="edit-contacto" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">Correo</label>
              <input type="email" name="email" id="edit-email" class="form-control" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Teléfono</label>
              <input type="text" name="telefono" id="edit-telefono" class="form-control">
            </div>
            <div class="form-group">
              <label class="form-label">Fecha de Sesión de Tallaje (En Terreno)</label>
              <input type="date" name="fecha_tallaje" id="edit-tallaje" class="form-control">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Monto de Cotización ($ CLP)</label>
              <input type="number" name="monto_cotizacion" id="edit-monto" class="form-control" placeholder="Ej. 1850000">
            </div>
            <div class="form-group">
              <label class="form-label">Profesionales a Vestir</label>
              <input type="number" name="tamano_equipo" id="edit-equipo" class="form-control">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Notas Comerciales</label>
            <textarea name="notas" id="edit-notas" class="form-control" rows="3"></textarea>
          </div>
        </div>

        <div class="modal-footer" style="display: flex; justify-content: space-between;">
          <button type="button" class="btn btn-secondary" style="color: #E11D48;" onclick="deleteClient()">
            🗑️ Eliminar
          </button>
          <div style="display: flex; gap: 8px;">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit-client')">Cancelar</button>
            <button type="submit" class="btn btn-primary">Actualizar Cambios</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>
    function filterOrScroll(statusKey) {
      // If we are in table mode, switch to kanban or filter
      const currentUrl = new URL(window.location.href);
      if (currentUrl.searchParams.get('view') === 'table' || !currentUrl.searchParams.get('view')) {
        window.location.href = 'clients.php?view=kanban#col-' + statusKey;
        return;
      }

      const col = document.getElementById('col-' + statusKey);
      if (col) {
        col.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        col.style.transition = 'all 0.3s ease';
        col.style.transform = 'scale(1.02)';
        col.style.boxShadow = '0 0 0 3px var(--primary)';
        setTimeout(() => {
          col.style.transform = '';
          col.style.boxShadow = '';
        }, 1200);
      }
    }

    async function quickMoveStage(clientId, newStage) {
      if (!newStage) return;
      const formData = new FormData();
      formData.append('action', 'update_status');
      formData.append('client_id', clientId);
      formData.append('status', newStage);

      try {
        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          showToast('✓ ' + data.message, 'success');
          setTimeout(() => location.reload(), 450);
        } else {
          showToast(data.error || 'Error al actualizar', 'error');
        }
      } catch (err) {
        showToast('Error de conexión', 'error');
      }
    }

    function editClient(client) {
      document.getElementById('edit-id').value = client.id;
      document.getElementById('edit-empresa').value = client.empresa || '';
      document.getElementById('edit-contacto').value = client.contacto_nombre || '';
      document.getElementById('edit-email').value = client.email || '';
      document.getElementById('edit-telefono').value = client.telefono || '';
      document.getElementById('edit-estado').value = client.estado || 'nuevo';
      document.getElementById('edit-tallaje').value = client.fecha_tallaje || '';
      document.getElementById('edit-monto').value = client.monto_cotizacion || '';
      document.getElementById('edit-equipo').value = client.tamano_equipo || '15';
      document.getElementById('edit-notas').value = client.notas || '';
      document.getElementById('edit-modal-title').innerText = 'Seguimiento: ' + client.empresa;
      openModal('modal-edit-client');
    }

    async function saveClient(event, actionType) {
      event.preventDefault();
      const form = event.target;
      const formData = new FormData(form);
      formData.append('action', actionType === 'create' ? 'create_client' : 'update_client');

      try {
        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, 'success');
          setTimeout(() => location.reload(), 600);
        } else {
          showToast(data.error || 'Error al guardar', 'error');
        }
      } catch (err) {
        showToast('Error de conexión', 'error');
      }
    }

    async function deleteClient() {
      const id = document.getElementById('edit-id').value;
      if (!confirm('¿Está seguro de eliminar esta institución del pipeline?')) return;

      const formData = new FormData();
      formData.append('action', 'delete_client');
      formData.append('id', id);

      try {
        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, 'success');
          setTimeout(() => location.reload(), 600);
        } else {
          showToast(data.error || 'Error al eliminar', 'error');
        }
      } catch (err) {
        showToast('Error al conectar con el servidor', 'error');
      }
    }

    // Auto open new client if query string has ?action=new
    if (new URLSearchParams(window.location.search).get('action') === 'new') {
      openModal('modal-new-client');
    }
  </script>
</body>
</html>
