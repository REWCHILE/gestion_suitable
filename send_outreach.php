<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ai_service.php';
require_auth();

$user = current_user();
$db = get_db();

// Preselected parameters
$selected_group_id = intval($_GET['group_id'] ?? 0);
$selected_client_id = intval($_GET['client_id'] ?? 0);
$active_tab = $_GET['tab'] ?? 'ai_wizard'; // 'ai_wizard' or 'manual'

// Fetch groups and clients
$groups = $db->query("SELECT * FROM contact_groups ORDER BY name ASC")->fetchAll();
$clients = $db->query("SELECT id, empresa, contacto_nombre, email, cargo, region_comuna FROM clients ORDER BY empresa ASC")->fetchAll();

// Target count
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

// Baseline draft for the campaign architect
$initial_draft = [
    'campaign_name' => 'Campaña B2B Clínicas - ' . date('d/m/Y'),
    'subject' => '[Convenio Clínico] Uniformes médicos con 6 meses de garantía directa de fábrica y servicio de tallaje',
    'preheader' => 'Somos fabricantes chilenos de uniformes clínicos antifluidos. Servicio exclusivo de tallaje en su clínica y 6 meses de garantía.',
    'hero_title' => 'Equipe a sus grupos de trabajo clínico con la confianza de fabricantes directos',
    'hero_desc' => 'Estimado/a <strong>{{ contact.NOMBRE | default: "Director/a o Encargado/a de Adquisiciones" }}</strong> de <strong>{{ contact.EMPRESA | default: "su institución" }}</strong>: En <strong>Suitable</strong> confeccionamos uniformes clínicos de alto rendimiento con telas antifluidos de última generación y respaldo integral de fábrica.',
    'hero_cta_text' => 'Cotizar Dotación para mi Clínica →',
    'hero_cta_url' => 'https://suitable.cl/clinicas-y-centros/',
    'hero_image' => 'hero-grupo-clinico.jpg',
    'template_id' => 2,
    'pilar1_title' => 'Somos Fabricantes Chilenos con 6 Meses de Garantía',
    'pilar1_desc' => 'Al tratar directamente con la fábrica, su institución accede a mejores costos por volumen, reposición permanente y garantía extendida de 6 meses que cubre confección, costuras y tela.',
    'pilar2_title' => '📏 Servicio de Tallaje a su Equipo Clínico',
    'pilar2_desc' => 'Evite devoluciones y tallas incorrectas. Coordinamos una sesión de tallaje directamente en su clínica con curva de muestras (XS a 3XL) sin costo.',
    'pilar3_title' => 'Telas Antifluidos con Tecnología Flex 4-Way',
    'pilar3_desc' => 'Máxima repelencia a fluidos y salpicaduras con elasticidad multidireccional que asegura confort total en jornadas hospitalarias de alta exigencia.',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agente Creador de Campañas con IA | Suitable B2B</title>
  <link rel="stylesheet" href="assets/css/app.css?v=<?= time() ?>">
  <style>
    /* STUDIO NAVIGATION TABS */
    .studio-tabs {
      display: flex;
      gap: 12px;
      margin-bottom: 20px;
      border-bottom: 1px solid var(--border-light);
      padding-bottom: 12px;
    }
    .studio-tab-btn {
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 700;
      font-size: 13px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: 1px solid transparent;
      background: var(--bg-surface);
      color: var(--text-muted);
      transition: all 0.2s ease;
      text-decoration: none;
    }
    .studio-tab-btn:hover {
      color: var(--primary);
      background: var(--primary-light);
      border-color: rgba(30, 136, 136, 0.2);
    }
    .studio-tab-btn.active {
      background: #1E8888;
      color: #FFFFFF;
      box-shadow: 0 4px 12px rgba(30, 136, 136, 0.25);
    }

    /* STEPPER BAR */
    .agent-stepper {
      background: #FFFFFF;
      border: 1px solid var(--border-light);
      border-radius: 12px;
      padding: 16px 20px;
      margin-bottom: 24px;
      box-shadow: var(--shadow-sm);
    }
    .stepper-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 14px;
    }
    .stepper-steps {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 10px;
    }
    .step-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 12px;
      border-radius: 8px;
      background: #F8FAFC;
      border: 1px solid #E2E8F0;
      transition: all 0.25s ease;
    }
    .step-item.active {
      background: #E6F4F4;
      border-color: #1E8888;
      box-shadow: 0 2px 8px rgba(30, 136, 136, 0.15);
    }
    .step-item.done {
      background: #F0FDF4;
      border-color: #86EFAC;
    }
    .step-circle {
      width: 24px;
      height: 24px;
      min-width: 24px;
      border-radius: 50%;
      background: #CBD5E1;
      color: #FFFFFF;
      font-size: 11px;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.25s ease;
    }
    .step-item.active .step-circle {
      background: #1E8888;
    }
    .step-item.done .step-circle {
      background: #10B981;
    }
    .step-text {
      font-size: 12px;
      font-weight: 700;
      color: var(--text-muted);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .step-item.active .step-text {
      color: #135A5A;
    }
    .step-item.done .step-text {
      color: #065F46;
    }

    /* AI STUDIO SPLIT LAYOUT */
    .ai-studio-grid {
      display: grid;
      grid-template-columns: 460px 1fr;
      gap: 24px;
      align-items: start;
    }

    /* LEFT CHAT PANE */
    .chat-studio-card {
      background: #FFFFFF;
      border: 1px solid var(--border-light);
      border-radius: 12px;
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      height: 780px;
      overflow: hidden;
    }
    .chat-header {
      padding: 14px 18px;
      background: linear-gradient(135deg, #0F2B2B 0%, #174E4E 100%);
      color: #FFFFFF;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0;
    }
    .agent-avatar-badge {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: #1E8888;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2);
    }
    .chat-body {
      flex: 1 1 auto;
      overflow-y: auto;
      padding: 18px;
      display: flex;
      flex-direction: column;
      gap: 14px;
      background: #F8FAFC;
    }
    .chat-body::-webkit-scrollbar {
      width: 6px;
    }
    .chat-body::-webkit-scrollbar-thumb {
      background: #CBD5E1;
      border-radius: 4px;
    }

    /* CHAT BUBBLES */
    .chat-msg {
      display: flex;
      gap: 10px;
      max-width: 90%;
      animation: fadeInMsg 0.3s ease;
    }
    @keyframes fadeInMsg {
      from { opacity: 0; transform: translateY(6px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .chat-msg.agent {
      align-self: flex-start;
    }
    .chat-msg.user {
      align-self: flex-end;
      flex-direction: row-reverse;
    }
    .msg-avatar {
      width: 30px;
      height: 30px;
      min-width: 30px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      flex-shrink: 0;
    }
    .chat-msg.agent .msg-avatar {
      background: #1E8888;
      color: #FFFFFF;
    }
    .chat-msg.user .msg-avatar {
      background: #0284C7;
      color: #FFFFFF;
    }
    .msg-bubble {
      padding: 12px 16px;
      border-radius: 12px;
      font-size: 13px;
      line-height: 1.5;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .chat-msg.agent .msg-bubble {
      background: #FFFFFF;
      color: #1E293B;
      border: 1px solid #E2E8F0;
      border-top-left-radius: 2px;
    }
    .chat-msg.user .msg-bubble {
      background: #1E8888;
      color: #FFFFFF;
      border-top-right-radius: 2px;
    }
    .msg-bubble strong {
      color: #0F172A;
    }
    .chat-msg.user .msg-bubble strong {
      color: #FFFFFF;
    }

    /* SUGGESTED CHIPS */
    .chips-container {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-top: 6px;
    }
    .chip-btn {
      background: #FFFFFF;
      border: 1px solid #B8E4E4;
      color: #135A5A;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.15s ease;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .chip-btn:hover {
      background: #1E8888;
      color: #FFFFFF;
      border-color: #1E8888;
      transform: translateY(-1px);
    }

    /* IMAGE DRAWER SELECTOR */
    .images-drawer {
      padding: 10px 16px;
      background: #FFFFFF;
      border-top: 1px solid #E2E8F0;
      flex-shrink: 0;
    }
    .images-grid-mini {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
      margin-top: 6px;
    }
    .img-card-opt {
      border: 2px solid #E2E8F0;
      border-radius: 6px;
      padding: 4px;
      cursor: pointer;
      text-align: center;
      transition: all 0.2s ease;
      background: #F8FAFC;
    }
    .img-card-opt:hover {
      border-color: #1E8888;
    }
    .img-card-opt.active {
      border-color: #1E8888;
      background: #E6F4F4;
    }
    .img-card-opt img {
      width: 100%;
      height: 44px;
      object-fit: cover;
      border-radius: 4px;
      display: block;
    }
    .img-card-opt span {
      font-size: 10px;
      font-weight: 700;
      display: block;
      margin-top: 2px;
      color: #334155;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* CHAT INPUT AREA */
    .chat-input-area {
      padding: 12px 16px;
      background: #FFFFFF;
      border-top: 1px solid #E2E8F0;
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .input-row {
      display: flex;
      gap: 8px;
    }
    .chat-text-input {
      flex: 1;
      padding: 10px 14px;
      border: 1px solid #CBD5E1;
      border-radius: 8px;
      font-size: 13px;
      color: #0F172A;
      outline: none;
      transition: border-color 0.2s ease;
    }
    .chat-text-input:focus {
      border-color: #1E8888;
      box-shadow: 0 0 0 2px rgba(30, 136, 136, 0.2);
    }
    .btn-agent-send {
      background: #1E8888;
      color: #FFFFFF;
      border: none;
      padding: 10px 16px;
      border-radius: 8px;
      font-weight: 700;
      font-size: 13px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: background 0.15s ease;
    }
    .btn-agent-send:hover {
      background: #156B6B;
    }

    /* ACTION BUTTONS IN CHAT */
    .chat-actions-bottom {
      display: flex;
      gap: 8px;
    }
    .btn-accept-campaign {
      flex: 1;
      background: linear-gradient(135deg, #10B981 0%, #059669 100%);
      color: #FFFFFF;
      border: none;
      padding: 11px 16px;
      border-radius: 8px;
      font-weight: 800;
      font-size: 13px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
      transition: transform 0.15s ease;
    }
    .btn-accept-campaign:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
    }
    .btn-copy-brevo-mini {
      background: #F1F5F9;
      color: #475569;
      border: 1px solid #CBD5E1;
      padding: 11px 12px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .btn-copy-brevo-mini:hover {
      background: #E2E8F0;
      color: #0F172A;
    }

    /* RIGHT PREVIEW STUDIO */
    .preview-studio-card {
      background: #FFFFFF;
      border: 1px solid var(--border-light);
      border-radius: 12px;
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      height: 780px;
      overflow: hidden;
    }
    .preview-studio-header {
      padding: 12px 18px;
      background: #F8FAFC;
      border-bottom: 1px solid #E2E8F0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0;
    }
    .subject-live-bar {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12px;
      color: #334155;
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;
      max-width: 55%;
    }
    .preview-canvas {
      flex: 1 1 auto;
      background: #E2E8F0;
      display: flex;
      justify-content: center;
      padding: 16px;
      overflow-y: auto;
    }
    .email-live-iframe {
      width: 100%;
      max-width: 620px;
      height: 100%;
      min-height: 680px;
      border: none;
      background: #FFFFFF;
      border-radius: 8px;
      box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
      transition: max-width 0.3s ease, opacity 0.2s ease;
    }

    /* MODAL CONFIRMATION & DISPATCH */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(4px);
      z-index: 10000;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.2s ease;
    }
    .modal-overlay.open {
      opacity: 1;
      pointer-events: auto;
    }
    .modal-dialog {
      background: #FFFFFF;
      border-radius: 14px;
      width: 100%;
      max-width: 580px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.2);
      overflow: hidden;
      transform: translateY(14px);
      transition: transform 0.25s ease;
    }
    .modal-overlay.open .modal-dialog {
      transform: translateY(0);
    }
    .modal-header {
      padding: 18px 24px;
      background: linear-gradient(135deg, #0F2B2B 0%, #174E4E 100%);
      color: #FFFFFF;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .modal-body {
      padding: 24px;
    }
    .modal-footer {
      padding: 16px 24px;
      background: #F8FAFC;
      border-top: 1px solid #E2E8F0;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    /* TYPING INDICATOR */
    .typing-dots span {
      display: inline-block;
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #1E8888;
      margin: 0 2px;
      animation: bounceDot 1.2s infinite ease-in-out both;
    }
    .typing-dots span:nth-child(1) { animation-delay: -0.32s; }
    .typing-dots span:nth-child(2) { animation-delay: -0.16s; }
    @keyframes bounceDot {
      0%, 80%, 100% { transform: scale(0); }
      40% { transform: scale(1); }
    }

    @media (max-width: 1100px) {
      .ai-studio-grid {
        grid-template-columns: 1fr;
      }
      .chat-studio-card, .preview-studio-card {
        height: 650px;
      }
    }
  </style>
</head>
<body>

  <!-- SIDEBAR NAVIGATION -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <main class="main-container">
    
    <!-- PAGE TITLE BAR -->
    <div class="page-header">
      <div class="page-title-group">
        <h1>✨ Arquitecto &amp; Creador de Campañas B2B con IA</h1>
        <p class="page-subtitle">Construcción conversacional paso a paso: defina el concepto, refine la estructura y previsualice en tiempo real hasta su aprobación</p>
      </div>

      <div class="header-actions">
        <a href="campaigns.php" class="btn btn-secondary btn-sm">📋 Historial de Campañas</a>
        <a href="templates_view.php" class="btn btn-secondary btn-sm">📑 Ver Plantillas Brevo</a>
      </div>
    </div>

    <!-- STUDIO TABS -->
    <div class="studio-tabs">
      <button type="button" class="studio-tab-btn <?= $active_tab === 'ai_wizard' ? 'active' : '' ?>" onclick="switchStudioTab('ai_wizard')">
        ✨ Asistente Conversacional IA (Paso a Paso)
      </button>
      <button type="button" class="studio-tab-btn <?= $active_tab === 'manual' ? 'active' : '' ?>" onclick="switchStudioTab('manual')">
        ⚙️ Orquestador Manual &amp; Formulario B2B
      </button>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: ASISTENTE CONVERSACIONAL IA         -->
    <!-- ========================================== -->
    <div id="tab_ai_wizard" style="<?= $active_tab === 'ai_wizard' ? 'display: block;' : 'display: none;' ?>">
      
      <!-- STEPPER BAR (5 PASOS) -->
      <div class="agent-stepper">
        <div class="stepper-header">
          <div>
            <strong style="font-size: 14px; color: var(--text-main);">Flujo de Creación con el Agente de Suitable</strong>
            <span style="font-size: 12px; color: var(--text-muted); margin-left: 8px;">Guía interactiva hacia la campaña definitiva</span>
          </div>
          <div style="font-size: 12px; font-weight: 700; color: #1E8888;" id="stepper-status-badge">
            Paso 1 de 5: Definición del Concepto
          </div>
        </div>

        <div class="stepper-steps">
          <div class="step-item active" id="step-pill-1">
            <div class="step-circle">1</div>
            <div class="step-text">💡 Concepto</div>
          </div>
          <div class="step-item" id="step-pill-2">
            <div class="step-circle">2</div>
            <div class="step-text">👥 Audiencia</div>
          </div>
          <div class="step-item" id="step-pill-3">
            <div class="step-circle">3</div>
            <div class="step-text">🎨 Estructura</div>
          </div>
          <div class="step-item" id="step-pill-4">
            <div class="step-circle">4</div>
            <div class="step-text">💬 Conversar &amp; Pulir</div>
          </div>
          <div class="step-item" id="step-pill-5">
            <div class="step-circle">5</div>
            <div class="step-text">✅ Aprobado</div>
          </div>
        </div>
      </div>

      <!-- STUDIO GRID -->
      <div class="ai-studio-grid">
        
        <!-- LEFT: CHAT STUDIO PANE -->
        <div class="chat-studio-card">
          
          <!-- CHAT HEADER -->
          <div class="chat-header">
            <div style="display: flex; align-items: center; gap: 10px;">
              <div class="agent-avatar-badge">🤖</div>
              <div>
                <div style="font-weight: 800; font-size: 13px;">Arquitecto de Campañas Suitable</div>
                <div style="font-size: 11px; color: #9FD6D6; display: flex; align-items: center; gap: 4px;">
                  <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #10B981;"></span>
                  En línea | Especialista B2B Confección Clínica
                </div>
              </div>
            </div>

            <!-- PROVIDER SELECTOR & RESET -->
            <div style="display: flex; align-items: center; gap: 8px;">
              <select id="chat_ai_provider" class="form-control" style="width: auto; padding: 4px 8px; font-size: 11px; font-weight: 700; height: 28px; background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3);" onchange="handleAiProviderChange(this.value)">
                <?php foreach ($ai_providers as $k => $prov): ?>
                  <option value="<?= $k ?>" <?= $active_ai_provider === $k ? 'selected' : '' ?> style="color: black;">
                    <?= $prov['icon'] ?> <?= $prov['name'] ?>
                  </option>
                <?php endforeach; ?>
              </select>

              <button type="button" class="btn btn-secondary btn-sm" onclick="resetAgentConversation()" title="Reiniciar conversación" style="padding: 4px 8px; font-size: 11px;">
                🔄
              </button>
            </div>
          </div>

          <!-- CHAT MESSAGE FEED -->
          <div class="chat-body" id="chat-feed">
            <!-- Initial Agent Welcome -->
            <div class="chat-msg agent">
              <div class="msg-avatar">🩺</div>
              <div>
                <div class="msg-bubble">
                  ¡Hola! 👋 Soy tu <strong>Arquitecto y Diseñador de Campañas B2B de Suitable</strong>.<br><br>
                  Vamos a estructurar juntos una propuesta irresistible para tus clínicas paso a paso.<br><br>
                  Para empezar: <strong>¿Cuál es el concepto u objetivo de esta nueva campaña?</strong><br>
                  <em>(Por ejemplo: Colección de Invierno, Reactivación de Clínicas, Promoción de Tallaje en Terreno, Enfoque Odontológico Antifluido, o Convenio Directo de Fábrica con 6 Meses de Garantía)</em>
                </div>

                <!-- Initial Chips -->
                <div class="chips-container" id="chips-area">
                  <button type="button" class="chip-btn" onclick="sendChipMessage('📏 Campaña: Servicio de Tallaje Gratis en Terreno')">
                    📏 Tallaje Gratis en Terreno
                  </button>
                  <button type="button" class="chip-btn" onclick="sendChipMessage('❄️ Campaña: Colección Invierno & Polar Corporativo')">
                    ❄️ Temporada Invierno
                  </button>
                  <button type="button" class="chip-btn" onclick="sendChipMessage('🛡️ Campaña: 6 Meses de Garantía y Venta Directa')">
                    🛡️ 6 Meses Garantía Fábrica
                  </button>
                  <button type="button" class="chip-btn" onclick="sendChipMessage('🦷 Campaña: Especial Clínicas Dentales Antifluido')">
                    🦷 Clínicas Dentales
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- IMAGE SELECTOR DRAWER -->
          <div class="images-drawer">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">
                📸 Imagen de Portada en el Correo:
              </span>
              <span id="current-img-label" style="font-size: 11px; color: #1E8888; font-weight: 700;">
                hero-grupo-clinico.jpg
              </span>
            </div>
            <div class="images-grid-mini">
              <div class="img-card-opt active" id="img-opt-hero" onclick="switchHeroImage('hero-grupo-clinico.jpg')">
                <img src="hero-grupo-clinico.jpg" alt="Equipo Clínico">
                <span>Equipo Clínico</span>
              </div>
              <div class="img-card-opt" id="img-opt-tela" onclick="switchHeroImage('tela-antifluidos-macro.jpg')">
                <img src="tela-antifluidos-macro.jpg" alt="Tela Antifluidos">
                <span>Tela Antifluido</span>
              </div>
              <div class="img-card-opt" id="img-opt-tallaje" onclick="switchHeroImage('servicio-tallaje-terreno.jpg')">
                <img src="servicio-tallaje-terreno.jpg" alt="Tallaje en Terreno">
                <span>Tallaje en Terreno</span>
              </div>
            </div>
          </div>

          <!-- INPUT AREA -->
          <div class="chat-input-area">
            <div class="input-row">
              <input type="text" id="agent-user-input" class="chat-text-input" placeholder="Escribe tu idea, pide cambios al correo o di 'lo acepto'..." onkeydown="handleInputKey(event)">
              <button type="button" class="btn-agent-send" id="btn-send-agent" onclick="handleSendAgentMessage()">
                Enviar ↵
              </button>
            </div>

            <!-- BOTTOM ACTION BUTTONS -->
            <div class="chat-actions-bottom">
              <button type="button" class="btn-accept-campaign" id="btn-main-accept" onclick="openLaunchModal()">
                ✅ Acepto la Campaña y Lanzar Envíos
              </button>
              <button type="button" class="btn-copy-brevo-mini" onclick="copyCurrentBrevoHtml()" title="Copiar código HTML optimizado para Brevo">
                📋 Copiar HTML
              </button>
            </div>
          </div>

        </div>

        <!-- RIGHT: LIVE PREVIEW STUDIO -->
        <div class="preview-studio-card">
          
          <div class="preview-studio-header">
            <div class="subject-live-bar" title="Asunto actual de la campaña">
              <strong style="color: #1E8888;">✉️ Asunto:</strong>
              <span id="preview-subject-text"><?= htmlspecialchars($initial_draft['subject']) ?></span>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
              <span class="badge badge-teal" style="font-size: 11px;">⚡ Vista en Vivo</span>
              <button type="button" class="btn btn-secondary btn-sm" onclick="setStudioPreviewWidth('620px')">🖥️ Desktop</button>
              <button type="button" class="btn btn-secondary btn-sm" onclick="setStudioPreviewWidth('380px')">📱 Móvil</button>
            </div>
          </div>

          <div class="preview-canvas">
            <iframe id="studio-preview-iframe" class="email-live-iframe" src="email_corporativo_suitable_2.html"></iframe>
          </div>

        </div>

      </div>

    </div>

    <!-- ========================================== -->
    <!-- TAB 2: MODO MANUAL AVANZADO B2B            -->
    <!-- ========================================== -->
    <div id="tab_manual" style="<?= $active_tab === 'manual' ? 'display: block;' : 'display: none;' ?>">
      
      <div style="display: grid; grid-template-columns: 380px 1fr; gap: 24px; align-items: start;">
        
        <!-- COLUMNA IZQUIERDA: CONFIGURACIÓN MANUAL -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
          
          <div class="panel-card">
            <div class="panel-header">
              <strong style="font-size: 14px; color: var(--text-main);">1. Audiencia Objetivo</strong>
              <span class="badge badge-teal" id="manual-audience-count"><?= $target_count ?> contactos</span>
            </div>
            <div class="panel-body">
              
              <div class="form-group">
                <label class="form-label">Destinatarios Objetivo</label>
                <select id="manual_target_type" class="form-control" onchange="handleManualTargetChange(this.value)">
                  <option value="group" <?= $selected_group_id > 0 ? 'selected' : '' ?>>Por Grupo de Clínicas</option>
                  <option value="single" <?= $selected_client_id > 0 ? 'selected' : '' ?>>Clínica Individual Específica</option>
                  <option value="all" <?= ($selected_group_id == 0 && $selected_client_id == 0) ? 'selected' : '' ?>>Toda la base de datos (<?= count($clients) ?> clínicas)</option>
                </select>
              </div>

              <div class="form-group" id="manual_group_wrap" style="<?= $selected_client_id > 0 ? 'display: none;' : '' ?>">
                <label class="form-label">Seleccionar Grupo</label>
                <select id="manual_selected_group" class="form-control" onchange="updateManualGroupCount(this.value)">
                  <option value="0">-- Elija un grupo --</option>
                  <?php foreach ($groups as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= $selected_group_id == $g['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($g['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group" id="manual_client_wrap" style="<?= $selected_client_id > 0 ? '' : 'display: none;' ?>">
                <label class="form-label">Seleccionar Clínica</label>
                <select id="manual_selected_client" class="form-control">
                  <?php foreach ($clients as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $selected_client_id == $c['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($c['empresa']) ?> (<?= htmlspecialchars($c['contacto_nombre']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

            </div>
          </div>

          <div class="panel-card">
            <div class="panel-header">
              <strong style="font-size: 14px; color: var(--text-main);">2. Campaña &amp; Envío</strong>
            </div>
            <div class="panel-body">
              <div class="form-group">
                <label class="form-label">Nombre de Campaña</label>
                <input type="text" id="manual_campaign_name" class="form-control" value="Campaña B2B Clínicas - <?= date('d/m/Y') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Asunto del Correo</label>
                <input type="text" id="manual_campaign_subject" class="form-control" value="[Convenio Clínico] Uniformes médicos con 6 meses de garantía directa de fábrica y servicio de tallaje">
              </div>
              <div class="form-group">
                <label class="form-label">Modo de Envío</label>
                <select id="manual_dispatch_mode" class="form-control">
                  <option value="simulacion">🧪 Simulación &amp; Registro CRM (Prueba segura)</option>
                  <option value="brevo_api">🚀 Envío Directo vía Brevo API v3</option>
                </select>
              </div>

              <button type="button" class="btn btn-primary" style="width: 100%; padding: 12px;" onclick="launchManualCampaign()">
                🚀 Disparar Campaña Manual
              </button>
            </div>
          </div>

        </div>

        <!-- COLUMNA DERECHA: PREVIEW MANUAL -->
        <div class="panel-card">
          <div class="panel-header">
            <strong style="font-size: 14px; color: var(--text-main);">Previsualización Manual</strong>
            <button type="button" class="btn btn-secondary btn-sm" onclick="setManualPreviewWidth('620px')">Desktop</button>
          </div>
          <div class="preview-frame-container" style="background: #E2E8F0; padding: 20px; display: flex; justify-content: center;">
            <iframe id="manual-preview-iframe" class="preview-iframe" style="width: 100%; max-width: 620px; height: 750px; background: white; border: none; border-radius: 8px;" src="email_corporativo_suitable_2.html"></iframe>
          </div>
        </div>

      </div>

    </div>

  </main>

  <!-- ======================================================== -->
  <!-- MODAL DE CONFIRMACIÓN & LANZAMIENTO ("LO ACEPTO")         -->
  <!-- ======================================================== -->
  <div class="modal-overlay" id="launch-modal">
    <div class="modal-dialog">
      
      <div class="modal-header">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 20px;">🚀</span>
          <strong>Confirmar y Disparar Campaña B2B</strong>
        </div>
        <button type="button" onclick="closeLaunchModal()" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer;">&times;</button>
      </div>

      <div class="modal-body">
        
        <div style="background: #E6F4F4; border: 1px solid #B8E4E4; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
          <div style="font-size: 24px;">🎉</div>
          <div style="font-size: 12px; color: #135A5A;">
            <strong>¡Estructura de campaña aprobada con el Agente!</strong><br>
            Verifique los parámetros finales de destinatarios antes de disparar el envío institucional.
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Nombre de la Campaña</label>
          <input type="text" id="modal_campaign_name" class="form-control" value="Campaña B2B Clínicas - <?= date('d/m/Y') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Asunto Aprobado (Subject)</label>
          <input type="text" id="modal_campaign_subject" class="form-control" value="">
        </div>

        <div class="form-group">
          <label class="form-label">Audiencia Objetivo</label>
          <select id="modal_target_type" class="form-control" onchange="handleModalTargetChange(this.value)">
            <option value="all">Toda la base de datos (<?= count($clients) ?> clínicas registradas)</option>
            <option value="group">Por Grupo Específico de Clínicas</option>
            <option value="single">A una Clínica Individual de Prueba</option>
          </select>
        </div>

        <div class="form-group" id="modal_group_wrap" style="display: none;">
          <label class="form-label">Seleccionar Grupo de Clínicas</label>
          <select id="modal_selected_group" class="form-control">
            <?php foreach ($groups as $g): ?>
              <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group" id="modal_client_wrap" style="display: none;">
          <label class="form-label">Seleccionar Clínica</label>
          <select id="modal_selected_client" class="form-control">
            <?php foreach ($clients as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['empresa']) ?> (<?= htmlspecialchars($c['contacto_nombre']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Modo de Envío</label>
          <select id="modal_dispatch_mode" class="form-control">
            <option value="simulacion">🧪 Simulación &amp; Registro CRM (Guarda en pipeline sin enviar emails reales)</option>
            <option value="brevo_api">🚀 Envío Directo vía Brevo API v3 (Disparo real a bandejas de entrada)</option>
          </select>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeLaunchModal()">
          Seguir Conversando
        </button>
        <button type="button" class="btn btn-primary" id="btn-confirm-dispatch" onclick="executeFinalDispatch()">
          🚀 Confirmar y Disparar Envíos
        </button>
      </div>

    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>
    // State management for AI Campaign Architect
    let currentDraft = <?= json_encode($initial_draft, JSON_UNESCAPED_UNICODE) ?>;
    let chatHistory = [];
    let currentStep = 1;
    let isWaitingAgent = false;

    // Initialize initial render
    document.addEventListener('DOMContentLoaded', () => {
      syncPreviewWithDraft(currentDraft);
    });

    function switchStudioTab(tab) {
      document.getElementById('tab_ai_wizard').style.display = tab === 'ai_wizard' ? 'block' : 'none';
      document.getElementById('tab_manual').style.display = tab === 'manual' ? 'block' : 'none';
      
      const btns = document.querySelectorAll('.studio-tab-btn');
      btns[0].classList.toggle('active', tab === 'ai_wizard');
      btns[1].classList.toggle('active', tab === 'manual');
    }

    function setStudioPreviewWidth(w) {
      document.getElementById('studio-preview-iframe').style.maxWidth = w;
    }

    function setManualPreviewWidth(w) {
      document.getElementById('manual-preview-iframe').style.maxWidth = w;
    }

    function handleInputKey(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        handleSendAgentMessage();
      }
    }

    function sendChipMessage(text) {
      document.getElementById('agent-user-input').value = text;
      handleSendAgentMessage();
    }

    function switchHeroImage(imgName) {
      currentDraft.hero_image = imgName;
      document.getElementById('current-img-label').innerText = imgName;
      
      document.getElementById('img-opt-hero').classList.toggle('active', imgName === 'hero-grupo-clinico.jpg');
      document.getElementById('img-opt-tela').classList.toggle('active', imgName === 'tela-antifluidos-macro.jpg');
      document.getElementById('img-opt-tallaje').classList.toggle('active', imgName === 'servicio-tallaje-terreno.jpg');

      // Update preview immediately
      syncPreviewWithDraft(currentDraft);

      // Tell agent
      appendUserMessage(`Cambia la imagen principal por ${imgName}`);
      callAgentEndpoint(`He cambiado la imagen de portada a ${imgName}`);
    }

    function updateStepperUI(step) {
      currentStep = step;
      const stepLabels = [
        'Paso 1 de 5: Definición del Concepto',
        'Paso 2 de 5: Audiencia Objetivo & Tono',
        'Paso 3 de 5: Estructuración & Propuesta Visual',
        'Paso 4 de 5: Ajustes, Preguntas & Diálogo',
        'Paso 5 de 5: ¡Campaña Aprobada y Lista para Envío!'
      ];
      document.getElementById('stepper-status-badge').innerText = stepLabels[step - 1] || `Paso ${step} de 5`;

      for (let i = 1; i <= 5; i++) {
        const el = document.getElementById(`step-pill-${i}`);
        if (!el) continue;
        el.classList.remove('active', 'done');
        if (i < step) {
          el.classList.add('done');
        } else if (i === step) {
          el.classList.add('active');
        }
      }
    }

    function appendUserMessage(text) {
      const feed = document.getElementById('chat-feed');
      const div = document.createElement('div');
      div.className = 'chat-msg user';
      div.innerHTML = `
        <div class="msg-avatar">👤</div>
        <div class="msg-bubble">${escapeHtml(text)}</div>
      `;
      feed.appendChild(div);
      feed.scrollTop = feed.scrollHeight;
      chatHistory.push({ role: 'user', content: text });
    }

    function appendAgentMessage(htmlText, chips = []) {
      const feed = document.getElementById('chat-feed');
      const div = document.createElement('div');
      div.className = 'chat-msg agent';

      let chipsHtml = '';
      if (chips && chips.length > 0) {
        chipsHtml = `<div class="chips-container" style="margin-top: 8px;">` +
          chips.map(c => `<button type="button" class="chip-btn" onclick="sendChipMessage('${escapeHtml(c)}')">${escapeHtml(c)}</button>`).join('') +
          `</div>`;
      }

      div.innerHTML = `
        <div class="msg-avatar">🩺</div>
        <div>
          <div class="msg-bubble">${htmlText}</div>
          ${chipsHtml}
        </div>
      `;
      feed.appendChild(div);
      feed.scrollTop = feed.scrollHeight;
      chatHistory.push({ role: 'assistant', content: htmlText });
    }

    function showTypingIndicator() {
      const feed = document.getElementById('chat-feed');
      const typingDiv = document.createElement('div');
      typingDiv.id = 'agent-typing-indicator';
      typingDiv.className = 'chat-msg agent';
      typingDiv.innerHTML = `
        <div class="msg-avatar">🩺</div>
        <div class="msg-bubble" style="padding: 10px 14px;">
          <div class="typing-dots">
            <span></span><span></span><span></span>
          </div>
        </div>
      `;
      feed.appendChild(typingDiv);
      feed.scrollTop = feed.scrollHeight;
    }

    function removeTypingIndicator() {
      const el = document.getElementById('agent-typing-indicator');
      if (el) el.remove();
    }

    async function handleSendAgentMessage() {
      const input = document.getElementById('agent-user-input');
      const text = input.value.trim();
      if (!text || isWaitingAgent) return;

      input.value = '';
      appendUserMessage(text);
      await callAgentEndpoint(text);
    }

    async function callAgentEndpoint(messageText) {
      isWaitingAgent = true;
      document.getElementById('btn-send-agent').disabled = true;
      showTypingIndicator();

      const provider = document.getElementById('chat_ai_provider').value;
      const formData = new FormData();
      formData.append('action', 'ai_campaign_chat');
      formData.append('provider', provider);
      formData.append('message', messageText);
      formData.append('history', JSON.stringify(chatHistory.slice(-8)));
      formData.append('draft', JSON.stringify(currentDraft));

      try {
        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        removeTypingIndicator();

        if (data.success) {
          // Update draft
          if (data.email_draft) {
            currentDraft = data.email_draft;
            syncPreviewWithDraft(currentDraft, data.html_preview);
          }

          // Update Stepper
          if (data.step) {
            updateStepperUI(data.step);
          }

          // Append reply
          appendAgentMessage(data.agent_reply, data.suggested_chips || []);

          // Check if accepted
          if (data.accepted || data.step === 5) {
            showToast('¡Campaña Aprobada! Abriendo confirmación de lanzamiento...', 'success');
            setTimeout(() => {
              openLaunchModal();
            }, 1200);
          }
        } else {
          appendAgentMessage('⚠️ Ocurrió un error al procesar la respuesta. Por favor intenta nuevamente.');
        }
      } catch (err) {
        removeTypingIndicator();
        appendAgentMessage('⚠️ Error de conexión con el agente. Verifica tu conexión.');
      } finally {
        isWaitingAgent = false;
        document.getElementById('btn-send-agent').disabled = false;
        document.getElementById('agent-user-input').focus();
      }
    }

    function syncPreviewWithDraft(draft, optionalHtml = null) {
      // Update subject bar
      if (draft.subject) {
        document.getElementById('preview-subject-text').innerText = draft.subject;
        document.getElementById('modal_campaign_subject').value = draft.subject;
      }
      if (draft.campaign_name) {
        document.getElementById('modal_campaign_name').value = draft.campaign_name;
      }

      // Update image drawer active state
      if (draft.hero_image) {
        const baseImg = draft.hero_image.split('/').pop();
        document.getElementById('current-img-label').innerText = baseImg;
        document.getElementById('img-opt-hero').classList.toggle('active', baseImg === 'hero-grupo-clinico.jpg');
        document.getElementById('img-opt-tela').classList.toggle('active', baseImg === 'tela-antifluidos-macro.jpg');
        document.getElementById('img-opt-tallaje').classList.toggle('active', baseImg === 'servicio-tallaje-terreno.jpg');
      }

      const iframe = document.getElementById('studio-preview-iframe');
      if (optionalHtml) {
        iframe.srcdoc = optionalHtml;
      } else {
        // Render via API
        const fd = new FormData();
        fd.append('action', 'render_campaign_preview');
        fd.append('draft', JSON.stringify(draft));
        fetch('api.php', { method: 'POST', body: fd })
          .then(r => r.text())
          .then(html => {
            iframe.srcdoc = html;
          })
          .catch(() => {});
      }
    }

    function resetAgentConversation() {
      if (!confirm('¿Deseas reiniciar la conversación con el Agente para crear una nueva campaña?')) return;
      document.getElementById('chat-feed').innerHTML = '';
      chatHistory = [];
      updateStepperUI(1);
      
      appendAgentMessage(
        `¡Hola de nuevo! 👋 Vamos a estructurar una nueva campaña B2B desde cero.<br><br><strong>¿De qué trata el concepto u objetivo de esta campaña?</strong>`,
        [
          '📏 Servicio de Tallaje Gratis en Terreno',
          '❄️ Temporada Invierno & Polar',
          '🛡️ 6 Meses de Garantía de Fábrica',
          '🦷 Clínicas Dentales & Bioseguridad'
        ]
      );
    }

    function openLaunchModal() {
      document.getElementById('modal_campaign_subject').value = currentDraft.subject || '';
      document.getElementById('modal_campaign_name').value = currentDraft.campaign_name || `Campaña B2B - ${new Date().toLocaleDateString()}`;
      document.getElementById('launch-modal').classList.add('open');
    }

    function closeLaunchModal() {
      document.getElementById('launch-modal').classList.remove('open');
    }

    function handleModalTargetChange(val) {
      document.getElementById('modal_group_wrap').style.display = val === 'group' ? 'block' : 'none';
      document.getElementById('modal_client_wrap').style.display = val === 'single' ? 'block' : 'none';
    }

    async function executeFinalDispatch() {
      const btn = document.getElementById('btn-confirm-dispatch');
      btn.disabled = true;
      btn.innerText = '🚀 Disparando Campaña...';

      const name = document.getElementById('modal_campaign_name').value.trim();
      const subject = document.getElementById('modal_campaign_subject').value.trim();
      const targetType = document.getElementById('modal_target_type').value;
      const groupId = document.getElementById('modal_selected_group').value;
      const clientId = document.getElementById('modal_selected_client').value;
      const mode = document.getElementById('modal_dispatch_mode').value;

      const fd = new FormData();
      fd.append('action', 'launch_campaign');
      fd.append('name', name);
      fd.append('subject', subject);
      fd.append('target_type', targetType);
      fd.append('group_id', groupId);
      fd.append('client_id', clientId);
      fd.append('template_id', currentDraft.template_id || 2);
      fd.append('mode', mode);

      try {
        const res = await fetch('api.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, 'success');
          setTimeout(() => {
            window.location.href = 'campaigns.php';
          }, 1200);
        } else {
          showToast(data.error || 'Error al lanzar campaña', 'error');
          btn.disabled = false;
          btn.innerText = '🚀 Confirmar y Disparar Envíos';
        }
      } catch (e) {
        showToast('Error de comunicación con el servidor', 'error');
        btn.disabled = false;
        btn.innerText = '🚀 Confirmar y Disparar Envíos';
      }
    }

    async function copyCurrentBrevoHtml() {
      const fd = new FormData();
      fd.append('action', 'render_campaign_preview');
      fd.append('draft', JSON.stringify(currentDraft));
      try {
        const res = await fetch('api.php', { method: 'POST', body: fd });
        const html = await res.text();
        copyToClipboard(html, '¡Código HTML copiado! Péguelo directamente en Brevo');
      } catch (e) {
        showToast('Error al obtener código HTML', 'error');
      }
    }

    function escapeHtml(str) {
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function handleAiProviderChange(prov) {
      showToast(`Motor de IA cambiado a ${prov.toUpperCase()}`, 'success');
    }

    // Manual mode helper functions
    function handleManualTargetChange(val) {
      document.getElementById('manual_group_wrap').style.display = val === 'group' ? 'block' : 'none';
      document.getElementById('manual_client_wrap').style.display = val === 'single' ? 'block' : 'none';
    }

    async function updateManualGroupCount(groupId) {
      if (groupId == 0) return;
      try {
        const res = await fetch(`api.php?action=get_group_count&group_id=${groupId}`);
        const data = await res.json();
        document.getElementById('manual-audience-count').innerText = `${data.count} contactos`;
      } catch (e) {}
    }

    async function launchManualCampaign() {
      const name = document.getElementById('manual_campaign_name').value.trim();
      const subject = document.getElementById('manual_campaign_subject').value.trim();
      const targetType = document.getElementById('manual_target_type').value;
      const groupId = document.getElementById('manual_selected_group').value;
      const clientId = document.getElementById('manual_selected_client').value;
      const mode = document.getElementById('manual_dispatch_mode').value;

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
      formData.append('template_id', 2);
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
  </script>
</body>
</html>
