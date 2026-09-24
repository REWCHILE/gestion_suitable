<?php
require_once __DIR__ . '/config.php';
require_auth();

$user = current_user();

$t1_content = file_exists(__DIR__ . '/email_corporativo_suitable_1.html') ? file_get_contents(__DIR__ . '/email_corporativo_suitable_1.html') : '';
$t2_content = file_exists(__DIR__ . '/email_corporativo_suitable_2.html') ? file_get_contents(__DIR__ . '/email_corporativo_suitable_2.html') : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Visor de Plantillas Brevo | Suitable</title>
  <link rel="stylesheet" href="assets/css/app.css?v=<?= time() ?>">
  <style>
    .templates-view-layout {
      display: grid;
      grid-template-columns: 340px 1fr;
      gap: 24px;
      align-items: start;
    }
    .tpl-card-select {
      background: white;
      border: 2px solid var(--border-light);
      border-radius: var(--radius-md);
      padding: 18px;
      margin-bottom: 14px;
      cursor: pointer;
      transition: var(--transition);
    }
    .tpl-card-select.active {
      border-color: var(--primary);
      background-color: var(--primary-light);
      box-shadow: var(--shadow-sm);
    }
    .tpl-frame-container {
      background-color: #E2E8F0;
      border-radius: var(--radius-md);
      padding: 24px;
      display: flex;
      justify-content: center;
      min-height: 800px;
    }
    .tpl-iframe {
      width: 100%;
      max-width: 620px;
      height: 850px;
      border: none;
      background: white;
      border-radius: 8px;
      box-shadow: var(--shadow-lg);
      transition: max-width 0.3s ease;
    }
  </style>
</head>
<body>

  <!-- SIDEBAR NAVIGATION -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <main class="main-container">
    
    <div class="page-header">
      <div class="page-title-group">
        <h1>Catálogo de Plantillas de Correo Corporativo</h1>
        <p class="page-subtitle">Diseños HTML profesionales testeados y optimizados para el editor Brevo (ex Sendinblue)</p>
      </div>

      <div class="header-actions">
        <button type="button" class="btn btn-primary" onclick="copyCurrentTemplate()">
          📋 Copiar Código para Brevo
        </button>
      </div>
    </div>

    <div class="templates-view-layout">
      
      <!-- SELECTOR LATERAL -->
      <div>
        
        <!-- CARD PLANTILLA 1 -->
        <div class="tpl-card-select" id="card-t1" onclick="switchTemplate(1)">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <strong style="color: var(--text-main); font-size: 15px;">Plantilla 1: Institucional</strong>
            <span class="badge badge-blue">General</span>
          </div>
          <p style="font-size: 12px; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
            Enfoque de presentación de marca Suitable: Tecnología Flex, propiedades antifluidos, bordados personalizados y catálogo de mujer/hombre.
          </p>
          <div style="font-size: 11px; color: #0369A1; font-weight: 600;">
            ✓ Compatible con Brevo (`{{ unsubscribe }}`)
          </div>
        </div>

        <!-- CARD PLANTILLA 2 -->
        <div class="tpl-card-select active" id="card-t2" onclick="switchTemplate(2)">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <strong style="color: var(--text-main); font-size: 15px;">Plantilla 2: B2B Alto Impacto</strong>
            <span class="badge badge-teal">Recomendada</span>
          </div>
          <p style="font-size: 12px; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
            Enfoque 100% B2B con imágenes autogeneradas: Grupos de trabajo clínico, macro de antifluidos, <strong>fabricación nacional directa</strong>, <strong>6 meses de garantía</strong> y <strong>servicio de tallaje en terreno</strong>.
          </p>
          <div style="font-size: 11px; color: #0F766E; font-weight: 600;">
            ✓ Alta conversión B2B para adquisiciones
          </div>
        </div>

        <!-- DETALLES BREVO -->
        <div style="background-color: var(--bg-surface); border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 18px; margin-top: 20px;">
          <h4 style="font-size: 13px; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">
            🚀 Variables Brevo Integradas
          </h4>
          <ul style="font-size: 12px; color: var(--text-muted); padding-left: 18px; line-height: 1.7;">
            <li><code>{{ contact.NOMBRE }}</code>: Nombre contacto</li>
            <li><code>{{ contact.EMPRESA }}</code>: Institución clínica</li>
            <li><code>{{ unsubscribe }}</code>: Enlace de desuscripción</li>
            <li><code>{{ mirror }}</code>: Ver en el navegador</li>
          </ul>
        </div>

      </div>

      <!-- PREVIEW CONTAINER -->
      <div>
        <div class="table-card" style="margin-bottom: 16px; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <strong id="active-tpl-title" style="font-size: 14px; color: var(--text-main);">Plantilla 2 (B2B Fabricantes &amp; Tallaje)</strong>
            <span class="badge badge-teal" id="active-tpl-tag">HTML Listo</span>
          </div>

          <div style="display: flex; gap: 8px;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="setDeviceWidth('620px')">🖥️ Vista Desktop</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="setDeviceWidth('375px')">📱 Vista Móvil</button>
            <a id="download-tpl-btn" href="email_corporativo_suitable_2.html" download class="btn btn-outline-primary btn-sm">💾 Descargar</a>
          </div>
        </div>

        <div class="tpl-frame-container">
          <iframe id="main-tpl-iframe" class="tpl-iframe" src="email_corporativo_suitable_2.html"></iframe>
        </div>
      </div>

    </div>

  </main>

  <script src="assets/js/app.js"></script>
  <script>
    let activeTemplate = 2;

    function switchTemplate(num) {
      activeTemplate = num;
      document.getElementById('card-t1').classList.toggle('active', num === 1);
      document.getElementById('card-t2').classList.toggle('active', num === 2);

      const fileName = num === 1 ? 'email_corporativo_suitable_1.html' : 'email_corporativo_suitable_2.html';
      document.getElementById('main-tpl-iframe').src = fileName;
      document.getElementById('download-tpl-btn').href = fileName;
      
      document.getElementById('active-tpl-title').innerText = num === 1 
        ? 'Plantilla 1: Institucional / Flex' 
        : 'Plantilla 2: B2B Fabricantes, 6M Garantía & Tallaje';
    }

    function setDeviceWidth(width) {
      document.getElementById('main-tpl-iframe').style.maxWidth = width;
    }

    async function copyCurrentTemplate() {
      const fileName = activeTemplate === 1 ? 'email_corporativo_suitable_1.html' : 'email_corporativo_suitable_2.html';
      try {
        const res = await fetch(fileName);
        const html = await res.text();
        copyToClipboard(html, '¡Código HTML copiado! Péguelo en la sección "Pegar mi código" de Brevo.');
      } catch (err) {
        showToast('Error al leer el archivo de plantilla', 'error');
      }
    }
  </script>
  <script src="assets/js/app.js"></script>
</body>
</html>
