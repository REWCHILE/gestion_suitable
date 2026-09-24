<?php
require_once __DIR__ . '/config.php';
require_auth();

$user = current_user();
$db = get_db();

// Metrics queries
$total_clients = $db->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$new_clients = $db->query("SELECT COUNT(*) FROM clients WHERE estado = 'nuevo'")->fetchColumn();
$sent_email_1 = $db->query("SELECT COUNT(*) FROM clients WHERE estado = 'correo_1_enviado'")->fetchColumn();
$sent_email_2 = $db->query("SELECT COUNT(*) FROM clients WHERE estado = 'correo_2_enviado'")->fetchColumn();
$tallajes_agendados = $db->query("SELECT COUNT(*) FROM clients WHERE estado = 'tallaje_agendado'")->fetchColumn();
$cotizaciones = $db->query("SELECT COUNT(*) FROM clients WHERE estado = 'cotizacion_enviada'")->fetchColumn();
$ganados = $db->query("SELECT COUNT(*) FROM clients WHERE estado = 'ganado'")->fetchColumn();
$total_pipeline_monto = $db->query("SELECT SUM(monto_cotizacion) FROM clients WHERE monto_cotizacion IS NOT NULL")->fetchColumn() ?: 0;

// Recent clients
$recent_clients = $db->query("
    SELECT c.*, u.name as asignado_nombre 
    FROM clients c 
    LEFT JOIN users u ON c.asignado_a = u.id 
    ORDER BY c.updated_at DESC 
    LIMIT 6
")->fetchAll();

// Upcoming tallaje sessions
$upcoming_tallajes = $db->query("
    SELECT * FROM clients 
    WHERE estado = 'tallaje_agendado' AND fecha_tallaje IS NOT NULL 
    ORDER BY fecha_tallaje ASC 
    LIMIT 4
")->fetchAll();

// Email logs count
$total_emails_sent = $db->query("SELECT COUNT(*) FROM email_logs")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard B2B | Suitable Outreach &amp; CRM</title>
  <link rel="stylesheet" href="assets/css/app.css?v=<?= time() ?>">
</head>
<body>

  <!-- SIDEBAR NAVIGATION -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main class="main-container">
    
    <!-- PAGE TITLE -->
    <div class="page-header">
      <div class="page-title-group">
        <h1>Panel de Control Comercial &amp; Envíos</h1>
        <p class="page-subtitle">Seguimiento de convenios clínicos, servicios de tallaje y campañas B2B</p>
      </div>

      <div class="header-actions">
        <a href="clients.php?action=new" class="btn btn-secondary">+ Nueva Clínica</a>
        <a href="send_outreach.php" class="btn btn-primary">✉️ Iniciar Envío de Correo</a>
      </div>
    </div>

    <!-- KPIS GRID -->
    <div class="kpi-grid">
      <!-- KPI 1 -->
      <div class="kpi-card">
        <div class="kpi-header">
          <span class="kpi-title">Clínicas en Seguimiento</span>
          <div class="kpi-icon" style="background-color: #E0F2FE; color: #0284C7;">🏥</div>
        </div>
        <div class="kpi-val"><?= $total_clients ?></div>
        <div class="kpi-trend"><?= $new_clients ?> prospectos nuevos por contactar</div>
      </div>

      <!-- KPI 2 -->
      <div class="kpi-card">
        <div class="kpi-header">
          <span class="kpi-title">Correos Orquestados</span>
          <div class="kpi-icon" style="background-color: #CCFBF1; color: #0F766E;">📬</div>
        </div>
        <div class="kpi-val"><?= $sent_email_1 + $sent_email_2 + $total_emails_sent ?></div>
        <div class="kpi-trend"><?= $sent_email_2 ?> con Plantilla 2 B2B</div>
      </div>

      <!-- KPI 3 -->
      <div class="kpi-card">
        <div class="kpi-header">
          <span class="kpi-title">Tallajes en Terreno</span>
          <div class="kpi-icon" style="background-color: #F3E8FF; color: #7E22CE;">📏</div>
        </div>
        <div class="kpi-val"><?= $tallajes_agendados ?></div>
        <div class="kpi-trend">Sesiones presenciales agendadas</div>
      </div>

      <!-- KPI 4 -->
      <div class="kpi-card">
        <div class="kpi-header">
          <span class="kpi-title">Cotizaciones Activas</span>
          <div class="kpi-icon" style="background-color: #FEF3C7; color: #D97706;">📄</div>
        </div>
        <div class="kpi-val"><?= $cotizaciones ?></div>
        <div class="kpi-trend">$<?= number_format($total_pipeline_monto, 0, ',', '.') ?> CLP en pipeline</div>
      </div>

      <!-- KPI 5 -->
      <div class="kpi-card">
        <div class="kpi-header">
          <span class="kpi-title">Ventas Ganadas</span>
          <div class="kpi-icon" style="background-color: #D1FAE5; color: #059669;">🏆</div>
        </div>
        <div class="kpi-val"><?= $ganados ?></div>
        <div class="kpi-trend">Con garantía de 6 meses activa</div>
      </div>
    </div>

    <!-- PIPELINE FUNNEL -->
    <div class="funnel-container">
      <div class="funnel-header">
        <span class="funnel-title">Embudo de Conversión B2B (Pipeline)</span>
        <a href="clients.php" style="font-size: 13px; font-weight: 600;">Ver tablero completo →</a>
      </div>

      <?php
      $f_total = max(1, $total_clients);
      $p_nuevo = ($new_clients / $f_total) * 100;
      $p_e1 = ($sent_email_1 / $f_total) * 100;
      $p_e2 = ($sent_email_2 / $f_total) * 100;
      $p_tallaje = ($tallajes_agendados / $f_total) * 100;
      $p_cotiz = ($cotizaciones / $f_total) * 100;
      $p_ganado = ($ganados / $f_total) * 100;
      ?>
      <div class="funnel-bar">
        <div class="funnel-segment" style="width: <?= $p_nuevo ?>%; background-color: #94A3B8;" title="Nuevos"></div>
        <div class="funnel-segment" style="width: <?= $p_e1 ?>%; background-color: #0284C7;" title="Correo 1"></div>
        <div class="funnel-segment" style="width: <?= $p_e2 ?>%; background-color: #1E8888;" title="Correo 2 B2B"></div>
        <div class="funnel-segment" style="width: <?= $p_tallaje ?>%; background-color: #8B5CF6;" title="Tallaje"></div>
        <div class="funnel-segment" style="width: <?= $p_cotiz ?>%; background-color: #D97706;" title="Cotización"></div>
        <div class="funnel-segment" style="width: <?= $p_ganado ?>%; background-color: #059669;" title="Ganados"></div>
      </div>

      <div class="funnel-legend">
        <div class="legend-item"><span class="legend-dot" style="background-color: #94A3B8;"></span> Nuevo (<?= $new_clients ?>)</div>
        <div class="legend-item"><span class="legend-dot" style="background-color: #0284C7;"></span> Correo 1 (<?= $sent_email_1 ?>)</div>
        <div class="legend-item"><span class="legend-dot" style="background-color: #1E8888;"></span> Correo 2 B2B (<?= $sent_email_2 ?>)</div>
        <div class="legend-item"><span class="legend-dot" style="background-color: #8B5CF6;"></span> Tallaje Agendado (<?= $tallajes_agendados ?>)</div>
        <div class="legend-item"><span class="legend-dot" style="background-color: #D97706;"></span> Cotización (<?= $cotizaciones ?>)</div>
        <div class="legend-item"><span class="legend-dot" style="background-color: #059669;"></span> Cerrado Ganado (<?= $ganados ?>)</div>
      </div>
    </div>

    <!-- TWO COLUMNS: UPCOMING TALLAJES & RECENT CLIENTS -->
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; align-items: start;">
      
      <!-- UPCOMING TALLAJE SESSIONS -->
      <div class="table-card">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-light); background-color: #FAF5FF; display: flex; align-items: center; justify-content: space-between;">
          <strong style="color: #6B21A8; font-size: 14px;">📏 Próximos Tallajes en Terreno</strong>
          <span class="badge badge-purple"><?= count($upcoming_tallajes) ?> citas</span>
        </div>

        <div style="padding: 16px 20px;">
          <?php if (empty($upcoming_tallajes)): ?>
            <p style="font-size: 13px; color: var(--text-muted); text-align: center; padding: 20px 0;">
              No hay sesiones de tallaje agendadas para los próximos días.
            </p>
          <?php else: ?>
            <?php foreach ($upcoming_tallajes as $t): ?>
              <div style="padding: 12px; border: 1px solid var(--border-light); border-radius: var(--radius-sm); margin-bottom: 10px; background-color: var(--bg-subtle);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                  <strong style="font-size: 13px; color: var(--text-main);"><?= htmlspecialchars($t['empresa']) ?></strong>
                  <span style="font-size: 11px; font-weight: 700; color: #7E22CE; background-color: #F3E8FF; padding: 2px 6px; border-radius: 4px;">
                    📅 <?= date('d/m/Y', strtotime($t['fecha_tallaje'])) ?>
                  </span>
                </div>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                  👤 <?= htmlspecialchars($t['contacto_nombre']) ?> (<?= htmlspecialchars($t['cargo']) ?>)
                </div>
                <div style="font-size: 11px; color: #0F766E; margin-top: 4px;">
                  📍 <?= htmlspecialchars($t['region_comuna']) ?> &nbsp;|&nbsp; 👥 <?= $t['tamano_equipo'] ?> profesionales
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- RECENT CLIENTS TABLE -->
      <div class="table-card">
        <div class="table-header-bar">
          <strong style="font-size: 15px; color: var(--text-main);">Clínicas y Prospectos Recientes</strong>
          <a href="clients.php" class="btn btn-secondary btn-sm">Ver Todos</a>
        </div>

        <table class="crm-table">
          <thead>
            <tr>
              <th>Institución / Clínica</th>
              <th>Contacto</th>
              <th>Estado Actual</th>
              <th>Último Contacto</th>
              <th style="text-align: right;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent_clients as $rc): 
              $s = get_status_info($rc['estado']);
            ?>
              <tr>
                <td>
                  <div class="client-name-bold"><?= htmlspecialchars($rc['empresa']) ?></div>
                  <div class="client-meta">📍 <?= htmlspecialchars($rc['region_comuna']) ?> • 👥 <?= $rc['tamano_equipo'] ?> colaboradores</div>
                </td>
                <td>
                  <div><?= htmlspecialchars($rc['contacto_nombre']) ?></div>
                  <div class="client-meta"><?= htmlspecialchars($rc['cargo']) ?></div>
                </td>
                <td>
                  <span class="badge <?= $s['badge'] ?>"><?= $s['label'] ?></span>
                </td>
                <td>
                  <div style="font-size: 12px; color: var(--text-muted);">
                    <?= $rc['ultimo_envio_fecha'] ? date('d/m/Y H:i', strtotime($rc['ultimo_envio_fecha'])) : 'Sin envíos aún' ?>
                  </div>
                  <?php if ($rc['ultimo_envio_tipo']): ?>
                    <span style="font-size: 10px; font-weight: 700; color: #0F766E;">
                      <?= $rc['ultimo_envio_tipo'] === 'plantilla_2' ? 'Plantilla 2 B2B' : 'Plantilla 1' ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td style="text-align: right;">
                  <a href="send_outreach.php?client_id=<?= $rc['id'] ?>" class="btn btn-outline-primary btn-sm" title="Enviar correo">
                    ✉️ Enviar
                  </a>
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
