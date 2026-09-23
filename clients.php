<?php
require_once __DIR__ . '/config.php';
require_auth();

$user = current_user();
$db = get_db();

// Filter parameters
$status_filter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$view_mode = $_GET['view'] ?? 'kanban'; // 'kanban' or 'table'

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

// Group clients by status for Kanban
$columns = [
    'nuevo' => ['title' => 'Nuevos', 'badge' => 'badge-gray', 'clients' => []],
    'correo_1_enviado' => ['title' => 'Correo 1 Enviado', 'badge' => 'badge-blue', 'clients' => []],
    'correo_2_enviado' => ['title' => 'Correo 2 (B2B)', 'badge' => 'badge-teal', 'clients' => []],
    'tallaje_agendado' => ['title' => 'Tallaje Agendado', 'badge' => 'badge-purple', 'clients' => []],
    'cotizacion_enviada' => ['title' => 'Cotización Enviada', 'badge' => 'badge-amber', 'clients' => []],
    'ganado' => ['title' => 'Venta Ganada', 'badge' => 'badge-emerald', 'clients' => []],
];

foreach ($clients as $c) {
    $st = $c['estado'];
    if (isset($columns[$st])) {
        $columns[$st]['clients'][] = $c;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pipeline de Clínicas | Suitable Outreach</title>
  <link rel="stylesheet" href="assets/css/app.css">
  <style>
    .view-switcher {
      display: flex;
      background-color: var(--bg-subtle);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-sm);
      padding: 3px;
      gap: 2px;
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
    }
    .view-btn.active {
      background-color: white;
      color: var(--primary);
      box-shadow: var(--shadow-sm);
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
          <li><a href="clients.php" class="nav-link active">👥 Pipeline</a></li>
          <li><a href="groups.php" class="nav-link">🏷️ Grupos</a></li>
          <li><a href="csv_import.php" class="nav-link">📥 Importar CSV</a></li>
          <li><a href="campaigns.php" class="nav-link">🚀 Campañas IA</a></li>
          <li><a href="send_outreach.php" class="nav-link">✉️ Orquestador</a></li>
          <li><a href="analytics.php" class="nav-link">📈 Analítica &amp; Tráfico</a></li>
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

  <!-- MAIN -->
  <main class="main-container">
    
    <div class="page-header">
      <div class="page-title-group">
        <h1>Pipeline Comercial de Clínicas &amp; Hospitales</h1>
        <p class="page-subtitle">Gestione el embudo de ventas, agendamiento de tallaje y seguimiento corporativo</p>
      </div>

      <div class="header-actions">
        <!-- View switcher -->
        <div class="view-switcher">
          <a href="clients.php?view=kanban<?= $search ? '&search='.urlencode($search) : '' ?>" class="view-btn <?= $view_mode === 'kanban' ? 'active' : '' ?>">
            📋 Tablero
          </a>
          <a href="clients.php?view=table<?= $search ? '&search='.urlencode($search) : '' ?>" class="view-btn <?= $view_mode === 'table' ? 'active' : '' ?>">
            📑 Tabla
          </a>
        </div>

        <button type="button" class="btn btn-primary" onclick="openModal('modal-new-client')">
          + Nueva Clínica
        </button>
      </div>
    </div>

    <!-- FILTER BAR -->
    <div class="table-card" style="padding: 14px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
      <form method="GET" action="clients.php" style="display: flex; gap: 10px; align-items: center; flex-grow: 1; max-width: 500px;">
        <input type="hidden" name="view" value="<?= htmlspecialchars($view_mode) ?>">
        <div class="search-input-wrap" style="flex-grow: 1;">
          <span class="search-icon">🔍</span>
          <input type="text" name="search" class="search-input" placeholder="Buscar clínica, contacto o comuna..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn btn-secondary btn-sm">Buscar</button>
        <?php if ($search): ?>
          <a href="clients.php?view=<?= $view_mode ?>" class="btn btn-secondary btn-sm" style="color: var(--text-muted);">Limpiar</a>
        <?php endif; ?>
      </form>

      <div style="font-size: 13px; color: var(--text-muted);">
        Mostrando <strong><?= count($clients) ?></strong> instituciones
      </div>
    </div>

    <?php if ($view_mode === 'kanban'): ?>
      <!-- KANBAN BOARD -->
      <div class="kanban-grid">
        <?php foreach ($columns as $status_key => $col): ?>
          <div class="kanban-col">
            <div class="kanban-col-header">
              <span class="kanban-col-title"><?= $col['title'] ?></span>
              <span class="kanban-count"><?= count($col['clients']) ?></span>
            </div>

            <div class="kanban-cards">
              <?php if (empty($col['clients'])): ?>
                <div style="font-size: 12px; color: var(--text-subtle); text-align: center; padding: 24px 0;">
                  Sin clínicas en esta etapa
                </div>
              <?php else: ?>
                <?php foreach ($col['clients'] as $cli): ?>
                  <div class="kanban-card" onclick="editClient(<?= htmlspecialchars(json_encode($cli)) ?>)">
                    <div class="kanban-card-title"><?= htmlspecialchars($cli['empresa']) ?></div>
                    <div class="kanban-card-sub">
                      👤 <?= htmlspecialchars($cli['contacto_nombre']) ?><br>
                      📍 <?= htmlspecialchars($cli['region_comuna']) ?> • 👥 <?= $cli['tamano_equipo'] ?> personas
                    </div>

                    <?php if ($cli['fecha_tallaje']): ?>
                      <div style="font-size: 11px; background-color: #F3E8FF; color: #7E22CE; padding: 3px 6px; border-radius: 4px; margin-bottom: 8px; font-weight: 600;">
                        📏 Tallaje: <?= date('d/m/Y', strtotime($cli['fecha_tallaje'])) ?>
                      </div>
                    <?php endif; ?>

                    <?php if ($cli['monto_cotizacion']): ?>
                      <div style="font-size: 11px; font-weight: 700; color: #059669; margin-bottom: 6px;">
                        💰 $<?= number_format($cli['monto_cotizacion'], 0, ',', '.') ?> CLP
                      </div>
                    <?php endif; ?>

                    <div class="kanban-card-footer" onclick="event.stopPropagation();">
                      <?php if ($cli['telefono']): 
                        $phone_clean = preg_replace('/[^0-9]/', '', $cli['telefono']);
                      ?>
                        <a href="https://wa.me/<?= $phone_clean ?>?text=Hola%20<?= urlencode($cli['contacto_nombre']) ?>,%20le%20escribo%20de%20Suitable%20Uniformes%20Cl%C3%ADnicos" target="_blank" style="color: #25D366; font-weight: 700; font-size: 12px;" title="Chatear por WhatsApp">
                          💬 WhatsApp
                        </a>
                      <?php else: ?>
                        <span></span>
                      <?php endif; ?>

                      <a href="send_outreach.php?client_id=<?= $cli['id'] ?>" class="btn btn-outline-primary btn-sm" style="padding: 3px 8px; font-size: 11px;">
                        ✉️ Enviar Correo
                      </a>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <!-- TABLE VIEW -->
      <div class="table-card">
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
              $phone_clean = preg_replace('/[^0-9]/', '', $cli['telefono']);
            ?>
              <tr>
                <td>
                  <div class="client-name-bold"><?= htmlspecialchars($cli['empresa']) ?></div>
                  <div class="client-meta">📍 <?= htmlspecialchars($cli['region_comuna']) ?> • 👥 <?= $cli['tamano_equipo'] ?> profesionales</div>
                </td>
                <td>
                  <div style="font-weight: 600;"><?= htmlspecialchars($cli['contacto_nombre']) ?></div>
                  <div class="client-meta"><?= htmlspecialchars($cli['cargo']) ?> • <?= htmlspecialchars($cli['email']) ?></div>
                </td>
                <td>
                  <?php if ($cli['telefono']): ?>
                    <a href="https://wa.me/<?= $phone_clean ?>" target="_blank" style="color: #25D366; font-weight: 700;">
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
                    <div style="font-size: 12px; color: #7E22CE; font-weight: 600;">
                      📏 <?= date('d/m/Y', strtotime($cli['fecha_tallaje'])) ?>
                    </div>
                  <?php endif; ?>
                  <?php if ($cli['monto_cotizacion']): ?>
                    <div style="font-size: 12px; color: #059669; font-weight: 700;">
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
    <?php endif; ?>

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
                <option value="nuevo">Nuevo Prospecto</option>
                <option value="correo_1_enviado">Correo 1 Enviado</option>
                <option value="correo_2_enviado">Correo 2 (B2B) Enviado</option>
                <option value="tallaje_agendado">Tallaje en Terreno Agendado</option>
                <option value="cotizacion_enviada">Cotización Enviada</option>
                <option value="ganado">Venta Ganada (Garantía 6 Meses)</option>
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
              <label class="form-label">Fecha de Sesión de Tallaje</label>
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
