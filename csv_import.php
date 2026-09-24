<?php
require_once __DIR__ . '/config.php';
require_auth();

$user = current_user();
$db = get_db();

// Fetch existing groups
$groups = $db->query("SELECT * FROM contact_groups ORDER BY name ASC")->fetchAll();

$import_results = null;

// Handle CSV form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_import'])) {
    $group_id = intval($_POST['group_id'] ?? 0);
    $new_group_name = trim($_POST['new_group_name'] ?? '');
    $csv_content = '';

    // If new group was requested
    if ($group_id === -1 && $new_group_name) {
        $stmt_g = $db->prepare("INSERT INTO contact_groups (name, description) VALUES (?, 'Grupo creado desde importación CSV')");
        $stmt_g->execute([$new_group_name]);
        $group_id = $db->lastInsertId();
    }

    // Read CSV file or textarea
    if (!empty($_FILES['csv_file']['tmp_name']) && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
        $csv_content = file_get_contents($_FILES['csv_file']['tmp_name']);
    } elseif (!empty($_POST['csv_text'])) {
        $csv_content = trim($_POST['csv_text']);
    }

    if ($csv_content) {
        // Normalize line endings
        $lines = preg_split('/\r\n|\r|\n/', trim($csv_content));
        if (count($lines) >= 1) {
            // Detect delimiter (, or ;)
            $first_line = $lines[0];
            $delimiter = substr_count($first_line, ';') > substr_count($first_line, ',') ? ';' : ',';
            
            $headers = str_getcsv(array_shift($lines), $delimiter);
            $headers = array_map(function($h) { return strtolower(trim(str_replace(['"', "'", "\xEF\xBB\xBF"], '', $h))); }, $headers);

            // Mapping dictionary
            $col_map = [
                'empresa' => -1,
                'contacto' => -1,
                'email' => -1,
                'telefono' => -1,
                'cargo' => -1,
                'comuna' => -1,
                'equipo' => -1,
            ];

            foreach ($headers as $idx => $h) {
                if (preg_match('/(empresa|clinica|centro|institucion|hospital|organizacion)/i', $h)) $col_map['empresa'] = $idx;
                if (preg_match('/(nombre|contacto|representante|persona)/i', $h)) $col_map['contacto'] = $idx;
                if (preg_match('/(email|correo|mail)/i', $h)) $col_map['email'] = $idx;
                if (preg_match('/(tel|fono|whatsapp|celular|phone)/i', $h)) $col_map['telefono'] = $idx;
                if (preg_match('/(cargo|puesto|rol|profesion)/i', $h)) $col_map['cargo'] = $idx;
                if (preg_match('/(comuna|ciudad|region|ubicacion)/i', $h)) $col_map['comuna'] = $idx;
                if (preg_match('/(equipo|personal|tamano|cantidad|num)/i', $h)) $col_map['equipo'] = $idx;
            }

            // Fallback if headers were missing or simple
            if ($col_map['empresa'] === -1 && isset($headers[0])) $col_map['empresa'] = 0;
            if ($col_map['contacto'] === -1 && isset($headers[1])) $col_map['contacto'] = 1;
            if ($col_map['email'] === -1 && isset($headers[2])) $col_map['email'] = 2;

            $inserted = 0;
            $updated = 0;
            $errors = 0;

            $stmt_check = $db->prepare("SELECT id FROM clients WHERE email = ?");
            $stmt_insert = $db->prepare("
                INSERT INTO clients (empresa, contacto_nombre, email, telefono, cargo, region_comuna, tamano_equipo, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'nuevo')
            ");
            $stmt_update = $db->prepare("
                UPDATE clients SET empresa = ?, contacto_nombre = ?, telefono = ?, cargo = ?, region_comuna = ?, tamano_equipo = ?
                WHERE id = ?
            ");
            $stmt_member = $db->prepare("INSERT OR IGNORE INTO group_members (group_id, client_id) VALUES (?, ?)");

            foreach ($lines as $line) {
                if (!trim($line)) continue;
                $row = str_getcsv($line, $delimiter);

                $email = filter_var(trim($row[$col_map['email']] ?? ''), FILTER_VALIDATE_EMAIL);
                if (!$email) {
                    $errors++;
                    continue;
                }

                $empresa = trim($row[$col_map['empresa']] ?? '') ?: 'Clínica / Institución';
                $contacto = trim($row[$col_map['contacto']] ?? '') ?: 'Encargado/a de Adquisiciones';
                $telefono = trim($row[$col_map['telefono']] ?? '');
                $cargo = trim($row[$col_map['cargo']] ?? '') ?: 'Jefatura de Salud';
                $comuna = trim($row[$col_map['comuna']] ?? '') ?: 'Santiago, RM';
                $equipo = intval($row[$col_map['equipo']] ?? 20) ?: 20;

                $stmt_check->execute([$email]);
                $existing = $stmt_check->fetch();

                if ($existing) {
                    $cid = $existing['id'];
                    $stmt_update->execute([$empresa, $contacto, $telefono, $cargo, $comuna, $equipo, $cid]);
                    $updated++;
                } else {
                    $stmt_insert->execute([$empresa, $contacto, $email, $telefono, $cargo, $comuna, $equipo]);
                    $cid = $db->lastInsertId();
                    $inserted++;
                }

                // Associate to group if chosen
                if ($group_id > 0) {
                    $stmt_member->execute([$group_id, $cid]);
                }
            }

            $import_results = [
                'inserted' => $inserted,
                'updated' => $updated,
                'errors' => $errors,
                'group_id' => $group_id
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Importador de Listas CSV | Suitable</title>
  <link rel="stylesheet" href="assets/css/app.css?v=<?= time() ?>">
  <style>
    .upload-zone {
      border: 2px dashed #CBD5E1;
      border-radius: var(--radius-md);
      padding: 30px;
      text-align: center;
      background-color: var(--bg-subtle);
      cursor: pointer;
      transition: var(--transition);
    }
    .upload-zone:hover {
      border-color: var(--primary);
      background-color: var(--primary-light);
    }
    .format-guide-box {
      background-color: #F8FAFC;
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      padding: 20px;
      font-size: 13px;
    }
    .csv-code-sample {
      background-color: #0F172A;
      color: #38BDF8;
      padding: 12px;
      border-radius: var(--radius-sm);
      font-family: monospace;
      font-size: 12px;
      overflow-x: auto;
      margin-top: 10px;
    }
  </style>
</head>
<body>

  <!-- SIDEBAR NAVIGATION -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <main class="main-container">
    
    <div class="page-header">
      <div class="page-title-group">
        <h1>Importar Listas de Clínicas &amp; Prospectos (CSV)</h1>
        <p class="page-subtitle">Suba archivos CSV de bases de datos de salud, detecte columnas automáticamente y organice en grupos</p>
      </div>

      <div class="header-actions">
        <a href="scratch/plantilla_ejemplo_suitable.csv" download="plantilla_ejemplo_suitable.csv" class="btn btn-secondary btn-sm" onclick="downloadSampleCsv(event)">
          📄 Descargar Plantilla Ejemplo CSV
        </a>
      </div>
    </div>

    <?php if ($import_results): ?>
      <div style="background-color: #ECFDF5; border: 1px solid #6EE7B7; border-radius: var(--radius-md); padding: 20px; margin-bottom: 24px;">
        <h3 style="color: #065F46; font-size: 16px; font-weight: 700; margin-bottom: 8px;">
          ✓ ¡Importación Procesada Exitosamente!
        </h3>
        <div style="font-size: 13px; color: #047857; display: flex; gap: 20px; flex-wrap: wrap;">
          <span><strong><?= $import_results['inserted'] ?></strong> Nuevos Contactos Creados</span>
          <span><strong><?= $import_results['updated'] ?></strong> Contactos Actualizados</span>
          <span><strong><?= $import_results['errors'] ?></strong> Filas Omitidas (sin correo válido)</span>
        </div>
        <div style="margin-top: 14px; display: flex; gap: 10px;">
          <a href="clients.php" class="btn btn-primary btn-sm">Ver en Pipeline →</a>
          <a href="send_outreach.php?group_id=<?= $import_results['group_id'] ?>" class="btn btn-secondary btn-sm">Crear Campaña para este Grupo →</a>
        </div>
      </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
      
      <!-- FORM DE IMPORTACIÓN -->
      <div class="table-card" style="padding: 28px;">
        <form method="POST" action="csv_import.php" enctype="multipart/form-data">
          <input type="hidden" name="do_import" value="1">

          <!-- 1. ASIGNACIÓN A GRUPO -->
          <div class="form-group">
            <label class="form-label">Asignar a Grupo / Segmento de Clínicas</label>
            <select name="group_id" id="group_selector" class="form-control" onchange="toggleNewGroupInput(this.value)">
              <option value="0">-- No asignar a ningún grupo (Solo añadir a Pipeline) --</option>
              <?php foreach ($groups as $g): ?>
                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
              <?php endforeach; ?>
              <option value="-1">➕ Crear un nuevo grupo para esta lista...</option>
            </select>
          </div>

          <!-- INPUT PARA NUEVO GRUPO -->
          <div class="form-group" id="new_group_container" style="display: none; background-color: #F0FDF4; padding: 14px; border-radius: var(--radius-sm); border: 1px solid #BBF7D0;">
            <label class="form-label" style="color: #166534;">Nombre del Nuevo Grupo:</label>
            <input type="text" name="new_group_name" id="new_group_name" class="form-control" placeholder="Ej. Clínicas Dentales Viña del Mar">
          </div>

          <!-- 2. ARCHIVO CSV -->
          <div class="form-group">
            <label class="form-label">Opción A: Subir Archivo (.csv)</label>
            <div class="upload-zone" onclick="document.getElementById('csv_file').click()">
              <div style="font-size: 32px; margin-bottom: 8px;">📁</div>
              <strong style="color: var(--text-main); font-size: 14px;">Haga clic para seleccionar archivo CSV</strong>
              <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Compatible con Excel, Google Sheets, HubSpot (.csv delimitado por coma o punto y coma)</div>
              <div id="file_selected_name" style="margin-top: 8px; font-weight: 700; color: var(--primary);"></div>
              <input type="file" id="csv_file" name="csv_file" accept=".csv,text/csv" style="display: none;" onchange="handleFileSelected(this)">
            </div>
          </div>

          <div style="text-align: center; margin: 18px 0; color: var(--text-subtle); font-weight: 700; font-size: 12px;">
            O TAMBIÉN PUEDE
          </div>

          <!-- 3. PEGAR TEXTO CSV -->
          <div class="form-group">
            <label class="form-label">Opción B: Pegar datos CSV directamente</label>
            <textarea name="csv_text" id="csv_text" class="form-control" rows="6" placeholder="Clinica,Nombre,Email,Telefono,Cargo,Comuna&#10;Clinica Alemana,Dr. Pedro Ruiz,pruiz@alemana.cl,+56911223344,Director Medico,Vitacura&#10;RedSalud Maipu,Maria Gomez,mgomez@redsalud.cl,+56922334455,Jefa Enfermeria,Maipu"></textarea>
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 14px;">
            📥 Procesar e Importar Contactos →
          </button>
        </form>
      </div>

      <!-- GUÍA DE FORMATO -->
      <div>
        <div class="format-guide-box">
          <h3 style="font-size: 15px; font-weight: 700; color: var(--text-main); margin-bottom: 10px;">
            💡 Detección Inteligente de Columnas
          </h3>
          <p style="color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
            El orquestador mapea automáticamente los nombres de columna más comunes en Chile:
          </p>

          <ul style="padding-left: 18px; color: var(--text-main); font-size: 12px; line-height: 1.8;">
            <li><strong>Empresa:</strong> <code>Clinica</code>, <code>Empresa</code>, <code>Institucion</code></li>
            <li><strong>Contacto:</strong> <code>Nombre</code>, <code>Contacto</code>, <code>Representante</code></li>
            <li><strong>Correo:</strong> <code>Email</code>, <code>Correo</code>, <code>Mail</code> (Requerido)</li>
            <li><strong>Teléfono:</strong> <code>Telefono</code>, <code>WhatsApp</code>, <code>Celular</code></li>
            <li><strong>Cargo:</strong> <code>Cargo</code>, <code>Rol</code>, <code>Especialidad</code></li>
            <li><strong>Comuna:</strong> <code>Comuna</code>, <code>Ciudad</code>, <code>Region</code></li>
            <li><strong>Personal:</strong> <code>Equipo</code>, <code>Cantidad</code>, <code>N_Empleados</code></li>
          </ul>

          <div style="margin-top: 16px;">
            <strong style="font-size: 12px; color: var(--text-main);">Ejemplo recomendado de formato:</strong>
            <div class="csv-code-sample">
Clinica,Nombre,Email,Telefono,Cargo,Comuna
Clinica Las Condes,Dra. Marcela C.,mcontreras@clc.cl,+56991234567,Jefa Pabellon,Las Condes
Centro Dental Providencia,Dr. Felipe M.,fm@odontosalud.cl,+56987654321,Director,Providencia
            </div>
          </div>
        </div>
      </div>

    </div>

  </main>

  <script src="assets/js/app.js"></script>
  <script>
    function toggleNewGroupInput(val) {
      const container = document.getElementById('new_group_container');
      const input = document.getElementById('new_group_name');
      if (val === '-1') {
        container.style.display = 'block';
        input.required = true;
        input.focus();
      } else {
        container.style.display = 'none';
        input.required = false;
      }
    }

    function handleFileSelected(input) {
      if (input.files && input.files[0]) {
        document.getElementById('file_selected_name').innerText = '✓ Archivo cargado: ' + input.files[0].name;
      }
    }

    function downloadSampleCsv(e) {
      e.preventDefault();
      const csvContent = "data:text/csv;charset=utf-8," + encodeURIComponent(
        "Clinica,Nombre,Email,Telefono,Cargo,Comuna,Equipo\n" +
        "Clinica Los Andes,Dr. Patricio Silva,psilva@clinicalosandes.cl,+56991234455,Director Medico,Santiago,40\n" +
        "Centro Medico San Cristobal,Veronica Morales,vmorales@sancristobal.cl,+56988776655,Jefa Enfermeria,Providencia,25\n" +
        "Clinica Dental Mayor,Dr. Javier Lagos,jlagos@dentalmayor.cl,+56977665544,Coordinador Clinico,Las Condes,18\n" +
        "Hospital Clinico San Borja,Rodrigo Castillo,rcastillo@hospital.cl,+56966554433,Encargado Adquisiciones,Santiago Centro,90"
      );
      const link = document.createElement("a");
      link.setAttribute("href", csvContent);
      link.setAttribute("download", "plantilla_contactos_suitable.csv");
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  </script>
  <script src="assets/js/app.js"></script>
</body>
</html>
