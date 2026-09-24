<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ai_service.php';
require_auth();

$user = current_user();
$db = get_db();

// Preselected group or client
$selected_group_id = intval($_GET['group_id'] ?? 0);
$selected_client_id = intval($_GET['client_id'] ?? 0);

// Fetch groups and clients
$groups = $db->query("SELECT * FROM contact_groups ORDER BY name ASC")->fetchAll();
$clients = $db->query("SELECT id, empresa, contacto_nombre, email, cargo, region_comuna FROM clients ORDER BY empresa ASC")->fetchAll();

// Get target contact count
$target_count = 0;
if ($selected_group_id > 0) {
    $stmt_c = $db->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ?");
    $stmt_c->execute([$selected_group_id]);
    $target_count = $stmt_c->fetchColumn();
} elseif ($selected_client_id > 0) {
    $target_count = 1;
} else {
    $target_count = count($clients);
}

// Available AI Providers
$ai_providers = AIService::getAvailableProviders();
$active_ai_provider = get_setting('active_ai_provider', 'groq');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Orquestador de Envíos B2B con IA | Suitable</title>
  <link rel="stylesheet" href="assets/css/app.css?v=<?= time() ?>">
  <style>
    .orchestrator-layout {
      display: grid;
      grid-template-columns: 380px 1fr;
      gap: 24px;
      align-items: start;
    }
    .panel-card {
      background-color: var(--bg-surface);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-sm);
      overflow: hidden;
    }
    .panel-header {
      padding: 16px 20px;
      border-bottom: 1px solid var(--border-light);
      background-color: var(--bg-subtle);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .panel-body {
      padding: 20px;
    }
    .ai-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 10px;
      background: linear-gradient(135deg, #0F766E 0%, #1E8888 100%);
      color: white;
      font-size: 11px;
      font-weight: 700;
      border-radius: var(--radius-pill);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .ai-preset-btn {
      text-align: left;
      width: 100%;
      padding: 8px 12px;
      font-size: 12px;
      border: 1px solid var(--border-light);
      border-radius: var(--radius-sm);
      background: white;
      color: var(--text-main);
      cursor: pointer;
      margin-bottom: 6px;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .ai-preset-btn:hover {
      background-color: var(--primary-light);
      border-color: var(--primary);
      color: var(--primary);
    }
    .preview-frame-container {
      background-color: #E2E8F0;
      border-radius: var(--radius-md);
      padding: 16px;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 600px;
    }
    .preview-iframe {
      width: 100%;
      max-width: 620px;
      height: 750px;
      border: none;
      background: white;
      border-radius: 8px;
      box-shadow: var(--shadow-lg);
    }
    .template-selector-cards {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 16px;
    }
    .template-opt-card {
      border: 2px solid var(--border-light);
      border-radius: var(--radius-sm);
      padding: 12px;
      cursor: pointer;
      transition: var(--transition);
      text-align: center;
      background: white;
    }
    .template-opt-card.active {
      border-color: var(--primary);
      background-color: var(--primary-light);
    }
    .template-opt-title {
      font-weight: 700;
      font-size: 13px;
      color: var(--text-main);
      margin-top: 4px;
    }
    .template-opt-desc {
      font-size: 11px;
      color: var(--text-muted);
      margin-top: 2px;
    }
  </style>
</head>
<body>

  <!-- SIDEBAR NAVIGATION -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <main class="main-container">
    
    <div class="page-header">
      <div class="page-title-group">
        <h1>Orquestador de Envíos B2B Potenciado por IA</h1>
        <p class="page-subtitle">Configure la audiencia, personalice con Groq / ChatGPT / Claude / Gemini y lance campañas de alto impacto</p>
      </div>

      <div class="header-actions">
        <a href="templates_view.php" class="btn btn-secondary btn-sm">📑 Ver Código de Plantillas</a>
      </div>
    </div>

    <div class="orchestrator-layout">
      
      <!-- COLUMNA IZQUIERDA: CONFIGURACIÓN & COPILOTO IA -->
      <div style="display: flex; flex-direction: column; gap: 20px;">
        
        <!-- PANEL AUDIENCIA & PLANTILLA -->
        <div class="panel-card">
          <div class="panel-header">
            <strong style="font-size: 14px; color: var(--text-main);">1. Audiencia &amp; Plantilla</strong>
            <span class="badge badge-teal" id="badge-audience-count"><?= $target_count ?> contactos</span>
          </div>
          <div class="panel-body">
            
            <!-- SELECCIONAR DESTINATARIO -->
            <div class="form-group">
              <label class="form-label">Destinatarios Objetivo</label>
              <select id="target_type" class="form-control" onchange="handleTargetChange(this.value)">
                <option value="group" <?= $selected_group_id > 0 ? 'selected' : '' ?>>Por Grupo de Clínicas</option>
                <option value="single" <?= $selected_client_id > 0 ? 'selected' : '' ?>>Clínica Individual Específica</option>
                <option value="all" <?= ($selected_group_id == 0 && $selected_client_id == 0) ? 'selected' : '' ?>>Toda la base de datos (<?= count($clients) ?> clínicas)</option>
              </select>
            </div>

            <!-- SELECTOR DE GRUPOS -->
            <div class="form-group" id="group_select_wrap" style="<?= $selected_client_id > 0 ? 'display: none;' : '' ?>">
              <label class="form-label">Seleccionar Grupo</label>
              <select id="selected_group" class="form-control" onchange="updateGroupCount(this.value)">
                <option value="0">-- Elija un grupo --</option>
                <?php foreach ($groups as $g): ?>
                  <option value="<?= $g['id'] ?>" <?= $selected_group_id == $g['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- SELECTOR DE CONTACTO INDIVIDUAL -->
            <div class="form-group" id="client_select_wrap" style="<?= $selected_client_id > 0 ? '' : 'display: none;' ?>">
              <label class="form-label">Seleccionar Clínica</label>
              <select id="selected_client" class="form-control" onchange="updatePreviewWithClient(this.value)">
                <?php foreach ($clients as $c): ?>
                  <option value="<?= $c['id'] ?>" <?= $selected_client_id == $c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['empresa']) ?> (<?= htmlspecialchars($c['contacto_nombre']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- SELECTOR DE PLANTILLA -->
            <div class="form-group">
              <label class="form-label">Seleccionar Plantilla de Correo</label>
              <div class="template-selector-cards">
                
                <div class="template-opt-card" id="card-tpl-1" onclick="selectTemplate(1)">
                  <div style="font-size: 20px;">🏥</div>
                  <div class="template-opt-title">Plantilla 1</div>
                  <div class="template-opt-desc">Institucional / Flex / Bordado</div>
                </div>

                <div class="template-opt-card active" id="card-tpl-2" onclick="selectTemplate(2)">
                  <div style="font-size: 20px;">🇨🇱</div>
                  <div class="template-opt-title">Plantilla 2 (B2B)</div>
                  <div class="template-opt-desc">Fabricantes, 6M Garantía &amp; Tallaje</div>
                </div>

              </div>
              <input type="hidden" id="selected_template_id" value="2">
            </div>

          </div>
        </div>

        <!-- PANEL COPILOTO DE IA -->
        <div class="panel-card" style="border-top: 4px solid #1E8888;">
          <div class="panel-header" style="background-color: #F0F9F9;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span class="ai-badge">✨ Copiloto IA</span>
              <strong style="font-size: 13px; color: #0F766E;">Generador B2B</strong>
            </div>
            
            <!-- SELECTOR PROVEEDOR IA -->
            <select id="ai_provider" class="form-control" style="width: auto; padding: 4px 8px; font-size: 12px; font-weight: 700; height: 30px;" onchange="handleAiProviderChange(this.value)">
              <?php foreach ($ai_providers as $k => $prov): ?>
                <option value="<?= $k ?>" <?= $active_ai_provider === $k ? 'selected' : '' ?>>
                  <?= $prov['icon'] ?> <?= $prov['name'] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="panel-body">
            
            <label class="form-label" style="font-size: 12px;">Acciones Rápidas con IA:</label>
            <div style="margin-bottom: 14px;">
              <button type="button" class="ai-preset-btn" onclick="triggerAiPreset('asuntos')">
                🎯 Generar 5 Asuntos de Alta Apertura
              </button>
              <button type="button" class="ai-preset-btn" onclick="triggerAiPreset('tallaje')">
                📏 Gancho: Servicio de Tallaje y 6 Meses de Garantía
              </button>
              <button type="button" class="ai-preset-btn" onclick="triggerAiPreset('dental')">
                🦷 Copy especializado para Clínicas Dentales
              </button>
              <button type="button" class="ai-preset-btn" onclick="triggerAiPreset('hospital')">
                🏥 Copy especializado para Jefaturas de Adquisiciones
              </button>
            </div>

            <!-- PROMPT PERSONALIZADO -->
            <div class="form-group">
              <label class="form-label" style="font-size: 12px;">O escriba una instrucción para la IA:</label>
              <textarea id="ai_custom_prompt" class="form-control" rows="2" placeholder="Ej. Redacta un asunto enfocado en convenios de reposición sin intermediarios..."></textarea>
            </div>

            <button type="button" class="btn btn-outline-primary btn-sm" style="width: 100%;" onclick="runAiGeneration()">
              ✨ Generar con <span id="current_ai_name">Groq</span> →
            </button>

            <!-- CAJA DE RESULTADO IA -->
            <div id="ai_result_box" style="display: none; margin-top: 14px; padding: 12px; background-color: #F8FAFC; border: 1px solid var(--border-light); border-radius: var(--radius-sm); font-size: 12px;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <strong style="color: var(--primary);">Respuesta de la IA:</strong>
                <span id="ai_latency_badge" style="font-size: 10px; color: var(--text-muted);"></span>
              </div>
              <div id="ai_result_text" style="white-space: pre-wrap; color: var(--text-main); line-height: 1.4; max-height: 200px; overflow-y: auto;"></div>
              
              <div style="margin-top: 10px; display: flex; gap: 8px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="applyAiAsSubject()" style="font-size: 11px;">
                  Usar como Asunto
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="copyToClipboard(document.getElementById('ai_result_text').innerText)" style="font-size: 11px;">
                  Copiar Texto
                </button>
              </div>
            </div>

          </div>
        </div>

        <!-- PANEL DATOS DEL MENSAJE & ENVÍO -->
        <div class="panel-card">
          <div class="panel-header">
            <strong style="font-size: 14px; color: var(--text-main);">2. Asunto &amp; Lanzamiento</strong>
          </div>
          <div class="panel-body">
            
            <div class="form-group">
              <label class="form-label">Nombre de la Campaña</label>
              <input type="text" id="campaign_name" class="form-control" value="Campaña B2B Clínicas - <?= date('d/m/Y') ?>">
            </div>

            <div class="form-group">
              <label class="form-label">Asunto del Correo (Subject)</label>
              <input type="text" id="campaign_subject" class="form-control" value="[Convenio Clínico] Uniformes médicos con 6 meses de garantía directa de fábrica y servicio de tallaje">
            </div>

            <div class="form-group">
              <label class="form-label">Modo de Envío</label>
              <select id="dispatch_mode" class="form-control">
                <option value="simulacion">🧪 Simulación &amp; Registro CRM (Prueba segura sin enviar correo real)</option>
                <option value="brevo_api">🚀 Envío Directo vía Brevo API v3</option>
              </select>
            </div>

            <button type="button" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 14px;" onclick="launchCampaign()">
              🚀 Lanzar Campaña / Disparar Envíos
            </button>

            <button type="button" class="btn btn-secondary" style="width: 100%; margin-top: 10px; font-size: 13px;" onclick="copyBrevoCode()">
              📋 Copiar Código HTML Listo para Brevo
            </button>

          </div>
        </div>

      </div>

      <!-- COLUMNA DERECHA: PREVIEW EN TIEMPO REAL -->
      <div>
        <div class="panel-card">
          <div class="panel-header">
            <div style="display: flex; align-items: center; gap: 10px;">
              <strong style="font-size: 14px; color: var(--text-main);">Previsualización en Vivo</strong>
              <span id="preview-tpl-badge" class="badge badge-teal">Plantilla 2 B2B</span>
            </div>

            <div style="display: flex; gap: 8px;">
              <button type="button" class="btn btn-secondary btn-sm" onclick="setPreviewWidth('620px')">🖥️ Desktop</button>
              <button type="button" class="btn btn-secondary btn-sm" onclick="setPreviewWidth('380px')">📱 Móvil</button>
            </div>
          </div>

          <div class="preview-frame-container">
            <iframe id="email-preview-iframe" class="preview-iframe" src="email_corporativo_suitable_2.html"></iframe>
          </div>
        </div>
      </div>

    </div>

  </main>

  <script src="assets/js/app.js"></script>
  <script>
    let currentTemplateId = 2;

    function selectTemplate(tplId) {
      currentTemplateId = tplId;
      document.getElementById('selected_template_id').value = tplId;
      
      document.getElementById('card-tpl-1').classList.toggle('active', tplId === 1);
      document.getElementById('card-tpl-2').classList.toggle('active', tplId === 2);
      
      const fileName = tplId === 1 ? 'email_corporativo_suitable_1.html' : 'email_corporativo_suitable_2.html';
      document.getElementById('email-preview-iframe').src = fileName;
      document.getElementById('preview-tpl-badge').innerText = tplId === 1 ? 'Plantilla 1 Institucional' : 'Plantilla 2 B2B Fabricante';

      if (tplId === 1) {
        document.getElementById('campaign_subject').value = 'Uniformes Clínicos de Alto Rendimiento para su Equipo Médico | Suitable Chile';
      } else {
        document.getElementById('campaign_subject').value = '[Convenio Clínico] Uniformes de Fabricación Nacional para su Equipo con 6 Meses de Garantía y Servicio de Tallaje | Suitable';
      }
    }

    function setPreviewWidth(width) {
      document.getElementById('email-preview-iframe').style.maxWidth = width;
    }

    function handleTargetChange(val) {
      document.getElementById('group_select_wrap').style.display = val === 'group' ? 'block' : 'none';
      document.getElementById('client_select_wrap').style.display = val === 'single' ? 'block' : 'none';
      
      if (val === 'all') {
        document.getElementById('badge-audience-count').innerText = '<?= count($clients) ?> contactos';
      } else if (val === 'single') {
        document.getElementById('badge-audience-count').innerText = '1 contacto';
      }
    }

    async function updateGroupCount(groupId) {
      if (groupId == 0) return;
      try {
        const res = await fetch(`api.php?action=get_group_count&group_id=${groupId}`);
        const data = await res.json();
        document.getElementById('badge-audience-count').innerText = `${data.count} contactos`;
      } catch (e) {}
    }

    function handleAiProviderChange(prov) {
      const names = {
        'groq': 'Groq (Llama 3.3)',
        'openai': 'OpenAI (ChatGPT)',
        'claude': 'Anthropic (Claude 3.5)',
        'gemini': 'Google (Gemini)'
      };
      document.getElementById('current_ai_name').innerText = names[prov] || prov;
    }

    function triggerAiPreset(type) {
      let prompt = '';
      if (type === 'asuntos') {
        prompt = 'Genera 5 opciones de Asuntos (Subject Lines) B2B irresistibles para directores médicos y jefes de adquisiciones de clínicas en Chile, destacando que somos fabricantes nacionales, 6 meses de garantía y servicio de tallaje en la clínica.';
      } else if (type === 'tallaje') {
        prompt = 'Redacta un párrafo persuasivo invitando a una clínica a coordinar el Servicio Exclusivo de Tallaje en su institución para evitar errores de tallas en su dotación de uniformes médicos antifluidos.';
      } else if (type === 'dental') {
        prompt = 'Redacta un gancho comercial enfocado en clínicas dentales y centros odontológicos en Chile, destacando tela Flex 4-way, alta bioseguridad ante salpicaduras y bordado de logos.';
      } else if (type === 'hospital') {
        prompt = 'Redacta una propuesta formal para el departamento de compras y adquisiciones de un hospital, destacando ventas por volumen, reposición garantizada durante el año y 6 meses de garantía.';
      }
      document.getElementById('ai_custom_prompt').value = prompt;
      runAiGeneration();
    }

    async function runAiGeneration() {
      const prompt = document.getElementById('ai_custom_prompt').value.trim();
      const provider = document.getElementById('ai_provider').value;
      if (!prompt) {
        showToast('Por favor elija una opción rápida o ingrese una instrucción', 'error');
        return;
      }

      showToast(`Consultando con ${provider.toUpperCase()}...`, 'success');
      
      const formData = new FormData();
      formData.append('action', 'ai_generate');
      formData.append('provider', provider);
      formData.append('prompt', prompt);

      try {
        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) {
          document.getElementById('ai_result_box').style.display = 'block';
          document.getElementById('ai_result_text').innerText = data.content;
          document.getElementById('ai_latency_badge').innerText = `${data.latency_ms}ms (${data.provider})`;
          if (data.is_fallback) {
            showToast('Generado con motor local. Configure su API Key en Ajustes.', 'success');
          } else {
            showToast(`¡Generado con ${data.provider}!`, 'success');
          }
        } else {
          showToast(data.error || 'Error en respuesta de IA', 'error');
        }
      } catch (err) {
        showToast('Error de conexión con el servicio de IA', 'error');
      }
    }

    function applyAiAsSubject() {
      const text = document.getElementById('ai_result_text').innerText;
      // Extract first line if multiple
      const lines = text.split('\n').filter(l => l.trim().length > 5);
      if (lines.length > 0) {
        let first = lines[0].replace(/^[0-9]+\.\s*/, '').replace(/^[-*]\s*/, '').replace(/["']/g, '');
        document.getElementById('campaign_subject').value = first;
        showToast('Asunto actualizado', 'success');
      }
    }

    async function launchCampaign() {
      const name = document.getElementById('campaign_name').value.trim();
      const subject = document.getElementById('campaign_subject').value.trim();
      const targetType = document.getElementById('target_type').value;
      const groupId = document.getElementById('selected_group').value;
      const clientId = document.getElementById('selected_client').value;
      const templateId = currentTemplateId;
      const mode = document.getElementById('dispatch_mode').value;

      if (!subject) {
        showToast('Ingrese un asunto para la campaña', 'error');
        return;
      }

      const formData = new FormData();
      formData.append('action', 'launch_campaign');
      formData.append('name', name);
      formData.append('subject', subject);
      formData.append('target_type', targetType);
      formData.append('group_id', groupId);
      formData.append('client_id', clientId);
      formData.append('template_id', templateId);
      formData.append('mode', mode);

      try {
        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, 'success');
          setTimeout(() => {
            window.location.href = 'campaigns.php';
          }, 1000);
        } else {
          showToast(data.error || 'Error al lanzar campaña', 'error');
        }
      } catch (e) {
        showToast('Error de comunicación con el servidor', 'error');
      }
    }

    async function copyBrevoCode() {
      const tplFile = currentTemplateId === 1 ? 'email_corporativo_suitable_1.html' : 'email_corporativo_suitable_2.html';
      try {
        const res = await fetch(tplFile);
        const html = await res.text();
        copyToClipboard(html, '¡Código HTML copiado! Péguelo directamente en Brevo');
      } catch (e) {
        showToast('Error al leer el archivo HTML', 'error');
      }
    }
  </script>
  <script src="assets/js/app.js"></script>
</body>
</html>
