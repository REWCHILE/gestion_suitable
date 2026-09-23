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
  <link rel="stylesheet" href="assets/css/app.css">
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
          <li><a href="clients.php" class="nav-link">👥 Pipeline Clínicas</a></li>
          <li><a href="groups.php" class="nav-link">🏷️ Grupos</a></li>
          <li><a href="csv_import.php" class="nav-link">📥 Importar CSV</a></li>
          <li><a href="campaigns.php" class="nav-link active">🚀 Campañas IA</a></li>
          <li><a href="send_outreach.php" class="nav-link">✉️ Orquestador</a></li>
          <li><a href="templates_view.php" class="nav-link">📑 Plantillas</a></li>
          <li><a href="settings.php" class="nav-link">⚙️ Ajustes IA</a></li>
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
        <h1>Historial de Campañas &amp; Disparos B2B</h1>
        <p class="page-subtitle">Registro de impactos a grupos clínicos, estado de entrega y plantillas utilizadas</p>
      </div>

      <div class="header-actions">
        <a href="send_outreach.php" class="btn btn-primary">
          ✨ Lanzar Nueva Campaña con IA
        </a>
      </div>
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
