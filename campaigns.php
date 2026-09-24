<?php
require_once __DIR__ . '/config.php';
require_auth();

$user = current_user();
$db = get_db();

// Fetch campaigns
$campaigns = $db->query("
    SELECT c.*, g.name as group_name, u.name as creator_name
    FROM campaigns c
    LEFT JOIN contact_groups g ON c.group_id = g.id
    LEFT JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
")->fetchAll();

// Fetch email logs
$logs = $db->query("
    SELECT l.*, cl.empresa, cl.contacto_nombre
    FROM email_logs l
    LEFT JOIN clients cl ON l.client_id = cl.id
    ORDER BY l.sent_at DESC
    LIMIT 25
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Campañas B2B &amp; Historial | Suitable Outreach</title>
  <link rel="stylesheet" href="assets/css/app.css?v=<?= time() ?>">
</head>
<body>

  <!-- SIDEBAR NAVIGATION -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <main class="main-container">
    
    <div class="page-header">
      <div class="page-title-group">
        <h1>Historial de Campañas &amp; Disparos B2B</h1>
        <p class="page-subtitle">Registro de impactos a grupos clínicos, estado de entrega y plantillas utilizadas</p>
      </div>

      <div class="header-actions">
        <a href="send_outreach.php" class="btn btn-primary" style="font-weight: 700; padding: 10px 18px; box-shadow: 0 4px 14px rgba(30, 136, 136, 0.3);">
          ✨ Crear Nueva Campaña con IA (Paso a Paso)
        </a>
      </div>
    </div>

    <!-- AI AGENT HIGHLIGHT CARD -->
    <div style="background: linear-gradient(135deg, #0F2B2B 0%, #174E4E 100%); color: #FFFFFF; border-radius: 12px; padding: 18px 24px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; box-shadow: 0 4px 15px rgba(15, 43, 43, 0.2);">
      <div style="display: flex; align-items: center; gap: 16px;">
        <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(30, 136, 136, 0.4); display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; border: 1px solid rgba(255,255,255,0.2);">
          🤖
        </div>
        <div>
          <div style="display: flex; align-items: center; gap: 8px;">
            <strong style="font-size: 15px; letter-spacing: 0.2px;">Nuevo: Agente y Arquitecto de Campañas B2B</strong>
            <span style="background: #10B981; color: white; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 10px; text-transform: uppercase;">Paso a Paso</span>
          </div>
          <p style="font-size: 12px; color: #BEE3E3; margin: 4px 0 0 0; max-width: 650px;">
            El agente te pide el concepto, redacta la estructura de correo B2B, selecciona las imágenes del muestrario/telas y te muestra la vista previa en vivo mientras conversas con él hasta tu aprobación final.
          </p>
        </div>
      </div>
      <a href="send_outreach.php" class="btn btn-primary btn-sm" style="background: #1E8888; border-color: #1E8888; font-weight: 700; white-space: nowrap; padding: 10px 16px;">
        Iniciar Asistente IA →
      </a>
    </div>

    <!-- CAMPAIGNS TABLE -->
    <div class="table-card" style="margin-bottom: 30px;">
      <div class="table-header-bar">
        <strong style="font-size: 15px; color: var(--text-main);">Campañas Orquestadas</strong>
        <span class="badge badge-teal"><?= count($campaigns) ?> campañas</span>
      </div>

      <?php if (empty($campaigns)): ?>
        <div style="padding: 40px; text-align: center; color: var(--text-muted);">
          <div style="font-size: 36px; margin-bottom: 8px;">🚀</div>
          <p>No se han registrado campañas aún. ¡Lance su primera campaña con el copiloto de IA!</p>
          <a href="send_outreach.php" class="btn btn-primary btn-sm" style="margin-top: 14px;">Ir al Orquestador</a>
        </div>
      <?php else: ?>
        <table class="crm-table">
          <thead>
            <tr>
              <th>Campaña / Asunto</th>
              <th>Grupo Objetivo</th>
              <th>Plantilla</th>
              <th>Contactos Impactados</th>
              <th>Estado</th>
              <th>Fecha de Envío</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($campaigns as $camp): ?>
              <tr>
                <td>
                  <div class="client-name-bold"><?= htmlspecialchars($camp['name']) ?></div>
                  <div class="client-meta">✉️ <?= htmlspecialchars($camp['subject']) ?></div>
                </td>
                <td>
                  <span class="badge badge-blue">
                    👥 <?= htmlspecialchars($camp['group_name'] ?: 'Audiencia Directa') ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?= $camp['template_id'] == 2 ? 'badge-teal' : 'badge-gray' ?>">
                    <?= $camp['template_id'] == 2 ? 'Plantilla 2 B2B' : 'Plantilla 1' ?>
                  </span>
                </td>
                <td>
                  <strong><?= $camp['sent_count'] ?></strong> / <?= $camp['total_count'] ?> clínicas
                </td>
                <td>
                  <span class="badge badge-emerald">
                    <?= ucfirst($camp['status']) ?>
                  </span>
                </td>
                <td>
                  <div style="font-size: 12px; color: var(--text-muted);">
                    <?= date('d/m/Y H:i', strtotime($camp['created_at'])) ?>
                  </div>
                  <div style="font-size: 10px; color: var(--text-subtle);">
                    Por: <?= htmlspecialchars($camp['creator_name'] ?: 'Sistema') ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- RECENT EMAIL LOGS -->
    <div class="table-card">
      <div class="table-header-bar">
        <strong style="font-size: 15px; color: var(--text-main);">Últimos 25 Envíos Detallados a Clínicas</strong>
        <span style="font-size: 12px; color: var(--text-muted);">Sincronizados con el Pipeline</span>
      </div>

      <table class="crm-table">
        <thead>
          <tr>
            <th>Institución</th>
            <th>Destinatario</th>
            <th>Plantilla</th>
            <th>Asunto</th>
            <th>Modo</th>
            <th>Fecha / Hora</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr>
              <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">
                No hay envíos registrados aún.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($logs as $l): ?>
              <tr>
                <td><strong><?= htmlspecialchars($l['empresa'] ?: 'Clínica') ?></strong></td>
                <td><?= htmlspecialchars($l['recipient_email']) ?></td>
                <td>
                  <span class="badge <?= $l['template_id'] == 2 ? 'badge-teal' : 'badge-gray' ?>">
                    T<?= $l['template_id'] ?>
                  </span>
                </td>
                <td style="font-size: 12px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                  <?= htmlspecialchars($l['subject']) ?>
                </td>
                <td>
                  <span class="badge <?= $l['status'] === 'enviado_brevo' ? 'badge-emerald' : 'badge-purple' ?>">
                    <?= $l['status'] === 'enviado_brevo' ? 'Brevo API' : 'Simulación CRM' ?>
                  </span>
                </td>
                <td style="font-size: 12px; color: var(--text-muted);">
                  <?= date('d/m/Y H:i', strtotime($l['sent_at'])) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>

  <script src="assets/js/app.js"></script>
</body>
</html>
