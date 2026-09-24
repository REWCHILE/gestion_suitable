<?php
require_once __DIR__ . '/config.php';
require_auth();

$user = current_user();
$db = get_db();

// Fetch groups with client counts
$groups = $db->query("
    SELECT g.*, COUNT(gm.client_id) as total_members 
    FROM contact_groups g 
    LEFT JOIN group_members gm ON g.id = gm.group_id 
    GROUP BY g.id 
    ORDER BY g.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Grupos &amp; Segmentación | Suitable Outreach</title>
  <link rel="stylesheet" href="assets/css/app.css?v=<?= time() ?>">
  <style>
    .groups-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 20px;
    }
    .group-card {
      background-color: var(--bg-surface);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      padding: 24px;
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      border-top: 4px solid var(--primary);
      transition: var(--transition);
    }
    .group-card:hover {
      box-shadow: var(--shadow-md);
      transform: translateY(-2px);
    }
  </style>
</head>
<body>

  <!-- SIDEBAR NAVIGATION -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <main class="main-container">
    
    <div class="page-header">
      <div class="page-title-group">
        <h1>Grupos &amp; Segmentos de Clínicas</h1>
        <p class="page-subtitle">Organice sus prospectos por especialidad, región o volumen para impactarlos con campañas personalizadas</p>
      </div>

      <div class="header-actions">
        <a href="csv_import.php" class="btn btn-secondary btn-sm">📥 Importar Lista CSV</a>
        <button type="button" class="btn btn-primary btn-sm" onclick="openModal('modal-new-group')">
          + Crear Nuevo Grupo
        </button>
      </div>
    </div>

    <!-- GROUPS GRID -->
    <div class="groups-grid">
      <?php foreach ($groups as $g): ?>
        <div class="group-card" style="border-top-color: <?= htmlspecialchars($g['color']) ?>;">
          <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
              <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main);">
                <?= htmlspecialchars($g['name']) ?>
              </h3>
              <span class="badge badge-teal" style="background-color: #E6F4F4; color: #146161;">
                👥 <?= $g['total_members'] ?> contactos
              </span>
            </div>
            
            <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 18px;">
              <?= htmlspecialchars($g['description'] ?: 'Sin descripción detallada.') ?>
            </p>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-light); padding-top: 14px; margin-top: 10px;">
            <a href="clients.php?group=<?= $g['id'] ?>" style="font-size: 12px; font-weight: 600; color: var(--text-muted);">
              Ver miembros →
            </a>
            <a href="send_outreach.php?group_id=<?= $g['id'] ?>" class="btn btn-primary btn-sm">
              🚀 Crear Campaña
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </main>

  <!-- MODAL: NUEVO GRUPO -->
  <div class="modal-backdrop" id="modal-new-group">
    <div class="modal-box">
      <div class="modal-header">
        <h3 class="modal-title">Crear Nuevo Segmento de Contactos</h3>
        <button type="button" class="modal-close" onclick="closeModal('modal-new-group')">&times;</button>
      </div>
      <form id="form-new-group" onsubmit="createGroup(event)">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Nombre del Grupo *</label>
            <input type="text" name="name" class="form-control" placeholder="Ej. Red de Hospitales Públicos RM" required>
          </div>

          <div class="form-group">
            <label class="form-label">Descripción del Segmento</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Características del segmento (ej. equipos grandes, requerimiento de tela antifluidos, etc.)"></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Color Identificador</label>
            <input type="color" name="color" class="form-control" value="#1E8888" style="height: 40px; padding: 2px;">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('modal-new-group')">Cancelar</button>
          <button type="submit" class="btn btn-primary">Crear Grupo →</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>
    async function createGroup(e) {
      e.preventDefault();
      const form = e.target;
      const formData = new FormData(form);
      formData.append('action', 'create_group');

      try {
        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, 'success');
          setTimeout(() => location.reload(), 600);
        } else {
          showToast(data.error || 'Error al crear grupo', 'error');
        }
      } catch (err) {
        showToast('Error de conexión con el servidor', 'error');
      }
    }
  </script>
  <script src="assets/js/app.js"></script>
</body>
</html>
