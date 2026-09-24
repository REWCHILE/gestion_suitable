@extends('layouts.app')

@section('title', 'Editor de Correo StampReady con Asistentes IA Contextuales | Suitable')

@section('content')

<!-- ESTILOS EXCLUSIVOS DEL WORKSPACE TIPO STAMPREADY CON ASISTENTES IA CONTEXTUALES -->
<style>
  /* WORKSPACE GENERAL (STAMPREADY STYLE) */
  .sr-workspace {
    display: flex;
    gap: 0;
    background: #E2E8F0;
    border-radius: 12px;
    overflow: hidden;
    min-height: 86vh;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
    position: relative;
    border: 1px solid #CBD5E1;
  }

  /* LEFT TOOLBAR (ICONOS OSCUROS ESTILO STAMPREADY) */
  .sr-toolbar-dark {
    width: 62px;
    background: #0F172A;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px 0;
    gap: 12px;
    flex-shrink: 0;
    z-index: 30;
    border-right: 1px solid #1E293B;
  }

  .sr-tool-btn {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: transparent;
    border: none;
    color: #94A3B8;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 19px;
    transition: all 0.18s ease;
    position: relative;
  }

  .sr-tool-btn:hover {
    background: rgba(255, 255, 255, 0.12);
    color: #FFFFFF;
    transform: scale(1.05);
  }

  .sr-tool-btn.active {
    background: #1E8888;
    color: #FFFFFF;
    box-shadow: 0 4px 14px rgba(30, 136, 136, 0.5);
  }

  .sr-tool-btn .sr-tooltip {
    display: none;
    position: absolute;
    left: 56px;
    top: 50%;
    transform: translateY(-50%);
    background: #0F172A;
    color: #FFFFFF;
    font-size: 11px;
    font-weight: 700;
    padding: 5px 11px;
    border-radius: 6px;
    white-space: nowrap;
    box-shadow: 0 4px 14px rgba(0,0,0,0.3);
    z-index: 100;
    border: 1px solid #334155;
    pointer-events: none;
  }

  .sr-tool-btn:hover .sr-tooltip {
    display: block;
  }

  .sr-tool-badge {
    position: absolute;
    top: 3px;
    right: 3px;
    width: 9px;
    height: 9px;
    background: #10B981;
    border-radius: 50%;
    box-shadow: 0 0 6px #10B981;
  }

  /* SIDEBAR PANEL DESPLEGABLE */
  .sr-side-panel {
    width: 325px;
    background: #FFFFFF;
    border-right: 1px solid #CBD5E1;
    overflow-y: auto;
    padding: 20px 18px;
    flex-shrink: 0;
    transition: width 0.25s ease;
    z-index: 25;
  }

  /* WORKBENCH CANVAS CENTRAL (EL ÁREA DONDE VIVE EL EMAIL) */
  .sr-canvas-workbench {
    flex: 1;
    background: #EEF2F4;
    overflow-y: auto;
    padding: 26px 20px 80px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
  }

  /* WORKSPACE TOP HEADER BAR */
  .sr-topbar {
    width: 100%;
    background: #FFFFFF;
    border-bottom: 1px solid #CBD5E1;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
  }

  /* EMAIL CONTAINER DENTRO DEL CANVAS */
  .sr-email-canvas {
    width: 100%;
    max-width: 600px;
    background: #FFFFFF;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
    overflow: hidden;
    transition: max-width 0.3s ease;
    position: relative;
    user-select: auto;
  }

  /* MÓDULOS DE CORREO */
  .sr-module-block {
    position: relative;
    transition: outline 0.15s ease;
  }

  .sr-module-block:hover {
    outline: 2px dashed #1E8888;
    outline-offset: -2px;
  }

  /* BARRA DE ACCIÓN DE MÓDULO */
  .sr-module-toolbar {
    position: absolute;
    top: 6px;
    right: 6px;
    background: #0F172A;
    border-radius: 6px;
    padding: 3px 6px;
    display: none;
    align-items: center;
    gap: 4px;
    z-index: 20;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  }

  .sr-module-block:hover .sr-module-toolbar {
    display: inline-flex;
  }

  .sr-mod-btn {
    background: transparent;
    border: none;
    color: #CCFBF1;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 6px;
    cursor: pointer;
    border-radius: 4px;
    transition: background 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 2px;
  }

  .sr-mod-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #FFFFFF;
  }

  /* --- RECONOCIMIENTO INTELIGENTE VISUAL EN CANVAS --- */

  /* 1. IMAGEN DETECTADA */
  .sr-detect-image {
    position: relative;
    cursor: pointer;
    transition: outline 0.15s ease, box-shadow 0.15s ease;
  }

  .sr-detect-image:hover {
    outline: 2.5px solid #3B82F6 !important;
    outline-offset: -2.5px;
  }

  .sr-detect-image.sr-selected-el {
    outline: 3px solid #2563EB !important;
    box-shadow: 0 0 15px rgba(37, 99, 235, 0.4) !important;
  }

  .sr-tag-image-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    background: #2563EB;
    color: #FFFFFF;
    font-size: 10px;
    font-weight: 800;
    padding: 4px 9px;
    border-radius: 4px;
    display: none;
    align-items: center;
    gap: 4px;
    z-index: 15;
    box-shadow: 0 3px 8px rgba(0,0,0,0.3);
    pointer-events: none;
    letter-spacing: 0.4px;
  }

  .sr-detect-image:hover .sr-tag-image-badge {
    display: inline-flex;
  }

  /* 2. TEXTO DETECTADO */
  .sr-detect-text {
    position: relative;
    transition: outline 0.15s ease, background 0.15s ease;
    border-radius: 3px;
    padding: 1px 3px;
    cursor: text;
  }

  .sr-detect-text:hover {
    outline: 2px dashed #10B981;
    background-color: rgba(16, 185, 129, 0.08);
  }

  .sr-detect-text:focus,
  .sr-detect-text.sr-selected-el {
    outline: 2.5px solid #10B981 !important;
    background-color: #FFFFFF !important;
    color: #0F172A !important;
    box-shadow: 0 0 12px rgba(16, 185, 129, 0.3);
  }

  /* 3. BOTÓN DETECTADO */
  .sr-detect-button {
    position: relative;
    cursor: pointer;
    transition: transform 0.15s ease, outline 0.15s ease;
  }

  .sr-detect-button:hover {
    outline: 2.5px dashed #F59E0B;
    outline-offset: 2px;
  }

  .sr-detect-button.sr-selected-el {
    outline: 3px solid #D97706 !important;
    box-shadow: 0 0 15px rgba(217, 119, 6, 0.4);
  }

  /* BARRA FLOTANTE SMART TEXT AI (SOBRE EL CANVAS) */
  #smartTextAiPill {
    position: absolute;
    display: none;
    background: #0F172A;
    color: #FFFFFF;
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 11px;
    font-weight: 700;
    z-index: 1000;
    box-shadow: 0 8px 24px rgba(0,0,0,0.35);
    align-items: center;
    gap: 6px;
    animation: fadeIn 0.15s ease;
    border: 1px solid #334155;
  }

  .pill-action-btn {
    background: transparent;
    border: none;
    color: #CCFBF1;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    padding: 3px 8px;
    border-radius: 4px;
    transition: background 0.15s ease;
  }

  .pill-action-btn:hover {
    background: rgba(255, 255, 255, 0.18);
    color: #FFFFFF;
  }

  /* CARDS DE RECONOCIMIENTO IA EN EL SIDEBAR INSPECTOR */
  .ai-recognition-card {
    border-radius: 8px;
    padding: 12px 14px;
    margin-bottom: 14px;
    border: 1.5px solid #E2E8F0;
  }

  .ai-recognition-card.image-mode {
    background: #EFF6FF;
    border-color: #93C5FD;
  }

  .ai-recognition-card.text-mode {
    background: #ECFDF5;
    border-color: #A7F3D0;
  }

  .ai-recognition-card.button-mode {
    background: #FFFBEB;
    border-color: #FDE68A;
  }

  .ai-speech-bubble {
    font-size: 11.5px;
    color: #334155;
    line-height: 1.5;
    margin: 6px 0 0 0;
    font-style: italic;
  }

  /* BOTÓN ESTELAR DE GENERACIÓN IA */
  .btn-star-ai {
    width: 100%;
    background: linear-gradient(135deg, #1E8888 0%, #0D9488 100%);
    color: white;
    font-weight: 800;
    font-size: 12px;
    padding: 10px 14px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.18s ease;
    margin-bottom: 12px;
  }

  .btn-star-ai:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(13, 148, 136, 0.5);
  }

  /* CHIPS Y SWATCHES */
  .swatch-chip {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    border: 1.5px solid #CBD5E1;
    background: white;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
  }

  .swatch-chip.active {
    border-color: #1E8888;
    background: #E6F4F4;
    color: #115E59;
  }

  /* MODAL DE IMÁGENES FLOTANTE */
  #smartImageAiModal {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(4px);
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }

  .smart-img-box {
    background: #FFFFFF;
    width: 100%;
    max-width: 540px;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0,0,0,0.3);
  }
</style>

<!-- TOP CONTROL BAR -->
<div class="sr-topbar">
  <div style="display: flex; align-items: center; gap: 12px;">
    <a href="{{ route('campaigns.index') }}" class="btn btn-secondary btn-sm" style="font-weight: 700;">
      ← Campañas
    </a>
    <input type="text" id="sr_campaign_name" value="{{ $initialPreset['name'] ?? 'Campaña Clínicas B2B - ' . date('d/m/Y') }}" style="border: 1px solid #CBD5E1; border-radius: 6px; padding: 6px 10px; font-size: 13px; font-weight: 800; color: #0F172A; width: 280px;" title="Nombre interno de la campaña">
    <a href="{{ route('campaigns.presets') }}" class="badge" style="background: #E6F4F4; color: #115E59; font-size: 11px; font-weight: 800; text-decoration: none; padding: 5px 10px; border-radius: 6px;">
      🎨 Presets (20)
    </a>
  </div>

  <!-- CENTER: DEVICE SWITCHER -->
  <div style="display: inline-flex; background: #F1F5F9; border-radius: 6px; padding: 2px; border: 1px solid #CBD5E1;">
    <button type="button" id="btn_sr_desktop" onclick="setCanvasDevice('desktop')" style="border: none; background: white; color: #0F172A; font-weight: 700; font-size: 12px; padding: 5px 14px; border-radius: 4px; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
      🖥️ Desktop (600px)
    </button>
    <button type="button" id="btn_sr_mobile" onclick="setCanvasDevice('mobile')" style="border: none; background: transparent; color: #64748B; font-weight: 700; font-size: 12px; padding: 5px 14px; border-radius: 4px; cursor: pointer;">
      📱 Móvil (385px)
    </button>
  </div>

  <!-- RIGHT: ACTIONS -->
  <div style="display: flex; align-items: center; gap: 8px;">
    <button type="button" class="btn btn-secondary btn-sm" onclick="copyPureEmailHtml()" style="font-weight: 700; color: #0F766E;">
      📋 Copiar HTML Brevo
    </button>
    <button type="button" class="btn btn-primary btn-sm" id="btn_save_canvas" onclick="saveStampReadyCampaign()" style="background: #1E8888; font-weight: 800; padding: 7px 18px;">
      💾 Guardar Campaña
    </button>
  </div>
</div>

<!-- WORKSPACE LAYOUT (TIPO STAMPREADY) -->
<div class="sr-workspace">

  <!-- LEFT DARK TOOLBAR -->
  <div class="sr-toolbar-dark">
    <!-- 1. INSPECTOR CONTEXTUAL IA (STAR FEATURE) -->
    <button type="button" class="sr-tool-btn active" id="tool_btn_inspector" onclick="switchSideTool('inspector')" title="Inspector Contextual IA">
      <span>🤖</span>
      <span class="sr-tool-badge" id="tool_inspector_badge" title="Elemento Activo"></span>
      <span class="sr-tooltip">Inspector Contextual IA</span>
    </button>

    <!-- 2. MÓDULOS DE CORREO -->
    <button type="button" class="sr-tool-btn" id="tool_btn_modules" onclick="switchSideTool('modules')" title="Módulos & Estructuras">
      <span>🧩</span>
      <span class="sr-tooltip">Módulos del Correo</span>
    </button>

    <!-- 3. COPILOTO GENERAL IA -->
    <button type="button" class="sr-tool-btn" id="tool_btn_copilot" onclick="switchSideTool('copilot')" title="Copiloto General IA">
      <span>🧠</span>
      <span class="sr-tooltip">Copiloto General IA</span>
    </button>

    <!-- 4. COLORES DE MARCA -->
    <button type="button" class="sr-tool-btn" id="tool_btn_styles" onclick="switchSideTool('styles')" title="Colores de Marca">
      <span>🎨</span>
      <span class="sr-tooltip">Colores Institucionales</span>
    </button>

    <!-- 5. AUDIENCIA CRM -->
    <button type="button" class="sr-tool-btn" id="tool_btn_crm" onclick="switchSideTool('crm')" title="Audiencia CRM">
      <span>👥</span>
      <span class="sr-tooltip">Audiencia CRM</span>
    </button>
  </div>

  <!-- SIDEBAR PANEL DESPLEGABLE -->
  <div class="sr-side-panel" id="sr_side_panel">

    <!-- PANEL TAB 1: 🤖 INSPECTOR CONTEXTUAL IA (RECONOCE TEXTO, IMAGEN, BOTÓN O MÓDULO) -->
    <div id="panel_inspector">
      
      <!-- SUBPANEL A: IMAGEN DETECTADA -->
      <div id="inspector_view_image">
        <div class="ai-recognition-card image-mode">
          <span style="font-size: 10px; font-weight: 800; color: #1D4ED8; text-transform: uppercase; letter-spacing: 0.5px;">📸 IMAGEN DETECTADA</span>
          <h4 id="inspector_img_title" style="margin: 4px 0 2px 0; font-size: 13px; font-weight: 800; color: #0F172A;">Portada Principal (Hero)</h4>
          <p class="ai-speech-bubble">
            🤖 "He detectado una imagen clínica. Aquí debes generar o elegir una fotografía fotorrealista acorde a este correo."
          </p>
        </div>

        <!-- ⚡ BOTÓN ESTELAR (1 CLIC): GENERAR CONTEXTUAL -->
        <button type="button" class="btn-star-ai" id="btn_inspector_quick_ai_img" onclick="generateImageFromCurrentEmailContext()">
          <span>✨ Generar con IA según este Correo (3s)</span>
        </button>

        <!-- SELECTOR DE TEMÁTICA CLÍNICA -->
        <div style="margin-bottom: 12px;">
          <span style="font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; display: block; margin-bottom: 6px;">Especialidad / Temática:</span>
          <div style="display: flex; gap: 5px; flex-wrap: wrap;">
            <button type="button" class="badge" onclick="setSmartImgTheme('equipo')" style="border: none; background: #F1F5F9; color: #0F172A; cursor: pointer; padding: 5px 8px; font-size: 11px;">👨‍⚕️ Equipo Clínico</button>
            <button type="button" class="badge" onclick="setSmartImgTheme('tela')" style="border: none; background: #F1F5F9; color: #0F172A; cursor: pointer; padding: 5px 8px; font-size: 11px;">💧 Tela Antifluido</button>
            <button type="button" class="badge" onclick="setSmartImgTheme('tallaje')" style="border: none; background: #F1F5F9; color: #0F172A; cursor: pointer; padding: 5px 8px; font-size: 11px;">📏 Tallaje Terreno</button>
            <button type="button" class="badge" onclick="setSmartImgTheme('dental')" style="border: none; background: #F1F5F9; color: #0F172A; cursor: pointer; padding: 5px 8px; font-size: 11px;">🦷 Odontología</button>
            <button type="button" class="badge" onclick="setSmartImgTheme('quirofano')" style="border: none; background: #F1F5F9; color: #0F172A; cursor: pointer; padding: 5px 8px; font-size: 11px;">🏥 Quirófano</button>
            <button type="button" class="badge" onclick="setSmartImgTheme('estetica')" style="border: none; background: #F1F5F9; color: #0F172A; cursor: pointer; padding: 5px 8px; font-size: 11px;">💆‍♀️ Estética</button>
          </div>
        </div>

        <!-- SELECTOR DE COLOR SCRUB -->
        <div style="margin-bottom: 12px;">
          <span style="font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; display: block; margin-bottom: 6px;">Color de Uniformes:</span>
          <div style="display: flex; gap: 5px; flex-wrap: wrap;">
            <button type="button" class="swatch-chip active" id="swatch_insp_petroleo" onclick="setSmartImgColor('petroleo')">
              <span style="width: 9px; height: 9px; border-radius: 50%; background: #1E8888;"></span>
              <span>Petróleo</span>
            </button>
            <button type="button" class="swatch-chip" id="swatch_insp_marino" onclick="setSmartImgColor('marino')">
              <span style="width: 9px; height: 9px; border-radius: 50%; background: #1E3A8A;"></span>
              <span>Marino</span>
            </button>
            <button type="button" class="swatch-chip" id="swatch_insp_grafito" onclick="setSmartImgColor('grafito')">
              <span style="width: 9px; height: 9px; border-radius: 50%; background: #334155;"></span>
              <span>Grafito</span>
            </button>
            <button type="button" class="swatch-chip" id="swatch_insp_verde" onclick="setSmartImgColor('verde')">
              <span style="width: 9px; height: 9px; border-radius: 50%; background: #047857;"></span>
              <span>Verde</span>
            </button>
          </div>
        </div>

        <!-- PROMPT LIBRE ASISTIDO -->
        <div style="margin-bottom: 12px;">
          <span style="font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; display: block; margin-bottom: 4px;">Instrucción personalizada con IA:</span>
          <div style="display: flex; gap: 6px;">
            <input type="text" id="inspector_img_prompt" class="form-control" placeholder="Ej: Doctora sonriendo con delantal blanco..." style="font-size: 11.5px;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="dictateIntoInput('inspector_img_prompt')" title="Dictar por voz">🎙️</button>
          </div>
          <button type="button" class="btn btn-secondary btn-sm" id="btn_run_inspector_img_custom" onclick="executeSmartImageGenerationFromInspector()" style="width: 100%; margin-top: 6px; font-weight: 700; font-size: 11px; background: #0F172A; color: white;">
            ⚡ Generar Fotografía Personalizada (3s)
          </button>
        </div>

        <!-- FOTOS OFICIALES SUITABLE -->
        <div style="border-top: 1px solid #E2E8F0; padding-top: 12px;">
          <span style="font-size: 10.5px; font-weight: 700; color: #64748B; display: block; margin-bottom: 6px;">O elige una fotografía de catálogo:</span>
          <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px;">
            <div onclick="applyDirectImage('{{ asset('images/hero-grupo-clinico.jpg') }}', 'hero-grupo-clinico.jpg')" style="height: 52px; border-radius: 6px; overflow: hidden; cursor: pointer; border: 1.5px solid #CBD5E1;" title="Equipo Clínico">
              <img src="{{ asset('images/hero-grupo-clinico.jpg') }}" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div onclick="applyDirectImage('{{ asset('images/tela-antifluidos-macro.jpg') }}', 'tela-antifluidos-macro.jpg')" style="height: 52px; border-radius: 6px; overflow: hidden; cursor: pointer; border: 1.5px solid #CBD5E1;" title="Tela Antifluido">
              <img src="{{ asset('images/tela-antifluidos-macro.jpg') }}" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div onclick="applyDirectImage('{{ asset('images/servicio-tallaje-terreno.jpg') }}', 'servicio-tallaje-terreno.jpg')" style="height: 52px; border-radius: 6px; overflow: hidden; cursor: pointer; border: 1.5px solid #CBD5E1;" title="Tallaje en Terreno">
              <img src="{{ asset('images/servicio-tallaje-terreno.jpg') }}" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
          </div>
        </div>
      </div>

      <!-- SUBPANEL B: TEXTO DETECTADO -->
      <div id="inspector_view_text" style="display: none;">
        <div class="ai-recognition-card text-mode">
          <span style="font-size: 10px; font-weight: 800; color: #047857; text-transform: uppercase; letter-spacing: 0.5px;">✍️ TEXTO DETECTADO</span>
          <h4 id="inspector_text_title" style="margin: 4px 0 2px 0; font-size: 13px; font-weight: 800; color: #0F172A;">Título Principal</h4>
          <p class="ai-speech-bubble">
            🤖 "He detectado un texto. Puedes editarlo directamente sobre el correo o usar estos asistentes IA especializados:"
          </p>
        </div>

        <!-- CAMPO DE EDICIÓN EN VIVO -->
        <div style="margin-bottom: 12px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
            <span style="font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase;">Contenido:</span>
            <button type="button" class="btn btn-secondary btn-sm" onclick="dictateIntoCurrentText()" style="padding: 2px 8px; font-size: 11px; font-weight: 700;">🎙️ Dictar</button>
          </div>
          <textarea id="inspector_text_content" class="form-control" rows="3" style="font-size: 12px;" oninput="syncInspectorTextToCanvas()"></textarea>
        </div>

        <!-- ASISTENCIAS IA RÁPIDAS (1 CLIC) -->
        <div style="margin-bottom: 14px;">
          <span style="font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; display: block; margin-bottom: 6px;">Asistentes IA Rápidos:</span>
          <div style="display: flex; flex-direction: column; gap: 6px;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="runTextSmartAction('persuasivo')" style="text-align: left; font-size: 11.5px; font-weight: 700; display: flex; align-items: center; gap: 6px;">
              <span>🎯</span> <span>Hacer más Persuasivo B2B (Directores Médicos)</span>
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="runTextSmartAction('garantia')" style="text-align: left; font-size: 11.5px; font-weight: 700; display: flex; align-items: center; gap: 6px;">
              <span>🛡️</span> <span>Destacar 6 Meses de Garantía de Fábrica</span>
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="runTextSmartAction('corto')" style="text-align: left; font-size: 11.5px; font-weight: 700; display: flex; align-items: center; gap: 6px;">
              <span>✂️</span> <span>Acortar y Hacer más Sintético (Móvil)</span>
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="runTextSmartAction('formal')" style="text-align: left; font-size: 11.5px; font-weight: 700; display: flex; align-items: center; gap: 6px;">
              <span>💼</span> <span>Tono Formal Institucional para Hospitales</span>
            </button>
          </div>
        </div>

        <!-- INSTRUCCIÓN LIBRE PARA ESTE TEXTO -->
        <div style="border-top: 1px solid #E2E8F0; padding-top: 12px;">
          <span style="font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; display: block; margin-bottom: 4px;">Instrucción libre para este texto:</span>
          <div style="display: flex; gap: 6px;">
            <input type="text" id="inspector_text_instruction" class="form-control" placeholder="Ej: Menciona 15% de descuento..." style="font-size: 11.5px;">
            <button type="button" class="btn btn-primary btn-sm" onclick="runCustomTextAction()" style="background: #1E8888; font-weight: 700;">✨</button>
          </div>
        </div>
      </div>

      <!-- SUBPANEL C: BOTÓN / CTA DETECTADO -->
      <div id="inspector_view_button" style="display: none;">
        <div class="ai-recognition-card button-mode">
          <span style="font-size: 10px; font-weight: 800; color: #B45309; text-transform: uppercase; letter-spacing: 0.5px;">🔘 BOTÓN / CTA DETECTADO</span>
          <h4 id="inspector_btn_title" style="margin: 4px 0 2px 0; font-size: 13px; font-weight: 800; color: #0F172A;">Llamada a la Acción</h4>
          <p class="ai-speech-bubble">
            🤖 "He detectado una llamada a la acción. Te sugiero copys de alta respuesta comercial:"
          </p>
        </div>

        <!-- TEXTO DEL BOTÓN -->
        <div style="margin-bottom: 12px;">
          <label style="font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase;">Texto del Botón:</label>
          <input type="text" id="inspector_btn_text" class="form-control" style="font-size: 12px;" oninput="syncInspectorBtnToCanvas()">
        </div>

        <!-- COPYS DE ALTA CONVERSIÓN -->
        <div style="margin-bottom: 14px;">
          <span style="font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; display: block; margin-bottom: 6px;">Copys Recomendados:</span>
          <div style="display: flex; flex-direction: column; gap: 5px;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="setInspectorBtnCopy('Solicitar Visita de Muestras y Tallaje →')" style="text-align: left; font-size: 11px;">
              👔 Solicitar Visita de Muestras y Tallaje →
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="setInspectorBtnCopy('Cotizar por WhatsApp con un Asesor →')" style="text-align: left; font-size: 11px;">
              💬 Cotizar por WhatsApp con un Asesor →
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="setInspectorBtnCopy('Solicitar Muestrario de Telas Antifluido →')" style="text-align: left; font-size: 11px;">
              💧 Solicitar Muestrario de Telas Antifluido →
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="setInspectorBtnCopy('Agendar Muestras en mi Clínica →')" style="text-align: left; font-size: 11px;">
              📅 Agendar Muestras en mi Clínica →
            </button>
          </div>
        </div>

        <!-- ENLACE DEL BOTÓN -->
        <div>
          <label style="font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase;">Enlace de Destino (URL o WhatsApp):</label>
          <input type="text" id="inspector_btn_url" class="form-control" value="https://suitable.cl/clinicas-y-centros/" style="font-size: 11.5px;" oninput="syncInspectorBtnUrlToCanvas()">
          <button type="button" class="btn btn-secondary btn-sm" onclick="setBtnToWhatsApp()" style="width: 100%; margin-top: 6px; font-size: 11px; font-weight: 700; color: #047857;">
            💬 Enlazar a WhatsApp Suitable (+56 9 3302 3278)
          </button>
        </div>
      </div>

      <!-- SUBPANEL D: MÓDULO O RESUMEN GENERAL -->
      <div id="inspector_view_module" style="display: none;">
        <div class="ai-recognition-card" style="background: #F8FAFC; border-color: #CBD5E1;">
          <span style="font-size: 10px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">🧩 MÓDULO DETECTADO</span>
          <h4 id="inspector_mod_title" style="margin: 4px 0 2px 0; font-size: 13px; font-weight: 800; color: #0F172A;">Estructura del Correo</h4>
          <p class="ai-speech-bubble">
            🤖 "Haz clic sobre cualquier elemento del correo (texto, fotografía o botón) para activar su asistente contextual específico."
          </p>
        </div>
      </div>

    </div>

    <!-- PANEL TAB 2: 🧩 MÓDULOS DE CORREO -->
    <div id="panel_modules" style="display: none;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h4 style="margin: 0; font-size: 13px; font-weight: 800; color: #0F172A; text-transform: uppercase;">🧩 Módulos del Correo</h4>
        <span style="font-size: 10.5px; color: #1E8888; font-weight: 700;">Haz clic para navegar</span>
      </div>

      <div style="display: flex; flex-direction: column; gap: 8px;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="scrollToModule('mod_header')" style="text-align: left; display: flex; justify-content: space-between; padding: 8px 10px; font-weight: 700; font-size: 12px;">
          <span>🔤 1. Cabecera &amp; Menú</span>
          <span>👁️</span>
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="scrollToModule('mod_hero')" style="text-align: left; display: flex; justify-content: space-between; padding: 8px 10px; font-weight: 700; font-size: 12px;">
          <span>📌 2. Portada Hero Banner</span>
          <span>👁️</span>
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="scrollToModule('mod_products')" style="text-align: left; display: flex; justify-content: space-between; padding: 8px 10px; font-weight: 700; font-size: 12px;">
          <span>🛍️ 3. Productos / Pilares (3 Col)</span>
          <span>👁️</span>
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="scrollToModule('mod_showcase')" style="text-align: left; display: flex; justify-content: space-between; padding: 8px 10px; font-weight: 700; font-size: 12px;">
          <span>🎨 4. Muestrario / Mini-Galería</span>
          <span>👁️</span>
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="scrollToModule('mod_guarantee')" style="text-align: left; display: flex; justify-content: space-between; padding: 8px 10px; font-weight: 700; font-size: 12px;">
          <span>🛡️ 5. Sello de Garantía</span>
          <span>👁️</span>
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="scrollToModule('mod_footer')" style="text-align: left; display: flex; justify-content: space-between; padding: 8px 10px; font-weight: 700; font-size: 12px;">
          <span>📞 6. Pie de Página &amp; Contacto</span>
          <span>👁️</span>
        </button>
      </div>

      <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid #E2E8F0;">
        <span style="font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; display: block; margin-bottom: 8px;">Asunto &amp; Preheader:</span>
        <div class="form-group" style="margin-bottom: 8px;">
          <label style="font-size: 11px; font-weight: 700;">Asunto (Subject):</label>
          <input type="text" id="sr_subject_input" class="form-control" value="{{ $initialPreset['subject'] ?? '[Convenio Clínico] Uniformes médicos con 6 meses de garantía directa de fábrica y servicio de tallaje' }}" style="font-size: 11.5px;" oninput="syncSubjectToCanvas()">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label style="font-size: 11px; font-weight: 700;">Preheader:</label>
          <input type="text" id="sr_preheader_input" class="form-control" value="{{ $initialPreset['preheader'] ?? 'Somos fabricantes chilenos de uniformes clínicos antifluidos. Servicio exclusivo de tallaje en su clínica y 6 meses de garantía.' }}" style="font-size: 11.5px;" oninput="syncSubjectToCanvas()">
        </div>
      </div>
    </div>

    <!-- PANEL TAB 3: 🧠 ASISTENTE COPILOTO IA -->
    <div id="panel_copilot" style="display: none;">
      <h4 style="margin: 0 0 10px 0; font-size: 13px; font-weight: 800; color: #0F172A; text-transform: uppercase;">🧠 Copiloto IA Suitable</h4>
      <p style="font-size: 11.5px; color: #64748B; margin-bottom: 12px;">Pídele al asistente que alinee toda la propuesta comercial o genere ideas de campaña:</p>

      <div style="margin-bottom: 12px;">
        <label style="font-size: 11px; font-weight: 700;">Motor IA Activo:</label>
        <select id="sr_ai_provider" class="form-control" style="font-size: 12px;">
          @foreach($aiProviders as $k => $prov)
            <option value="{{ $k }}" {{ $activeAiProvider === $k ? 'selected' : '' }}>{{ $prov['icon'] }} {{ $prov['name'] }}</option>
          @endforeach
        </select>
      </div>

      <div style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="runCopilotPrompt('Genera una propuesta B2B para clínicas dentales destacando telas resistentes y tallaje presencial')" style="text-align: left; font-size: 11px;">
          🦷 Alinear a Odontología
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="runCopilotPrompt('Genera una propuesta para clínicas quirúrgicas destacando bioseguridad y telas antifluidos Flex')" style="text-align: left; font-size: 11px;">
          🏥 Alinear a Pabellón Quirúrgico
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="runCopilotPrompt('Genera una propuesta para centros médicos destacando el servicio de tallaje en terreno con percheros')" style="text-align: left; font-size: 11px;">
          📏 Alinear a Tallaje en Terreno
        </button>
      </div>

      <div class="form-group">
        <label style="font-size: 11px; font-weight: 700;">Instrucción libre o dictado (🎙️):</label>
        <textarea id="sr_copilot_prompt" class="form-control" rows="3" placeholder="Ej: Haz el correo más urgente para compras de invierno..." style="font-size: 11.5px;"></textarea>
      </div>
      <button type="button" class="btn btn-primary btn-sm" id="btn_run_copilot" onclick="executeCopilotAction()" style="width: 100%; background: #1E8888; font-weight: 700;">
        ✨ Aplicar al Correo Completo
      </button>
    </div>

    <!-- PANEL TAB 4: 🎨 ESTILOS & COLORES -->
    <div id="panel_styles" style="display: none;">
      <h4 style="margin: 0 0 10px 0; font-size: 13px; font-weight: 800; color: #0F172A; text-transform: uppercase;">🎨 Paleta de Color Institucional</h4>
      <p style="font-size: 11.5px; color: #64748B; margin-bottom: 14px;">Cambia el color institucional en todos los módulos de inmediato:</p>

      <div style="display: flex; flex-direction: column; gap: 8px;">
        <button type="button" class="swatch-chip active" id="swatch_brand_teal" onclick="applyBrandColorTheme('#1E8888', '#146161')">
          <span style="width: 14px; height: 14px; border-radius: 50%; background: #1E8888;"></span>
          <span>Azul Petróleo Suitable (Oficial)</span>
        </button>
        <button type="button" class="swatch-chip" id="swatch_brand_navy" onclick="applyBrandColorTheme('#1E3A8A', '#0F2B5B')">
          <span style="width: 14px; height: 14px; border-radius: 50%; background: #1E3A8A;"></span>
          <span>Azul Marino Institucional</span>
        </button>
        <button type="button" class="swatch-chip" id="swatch_brand_graphite" onclick="applyBrandColorTheme('#334155', '#1E293B')">
          <span style="width: 14px; height: 14px; border-radius: 50%; background: #334155;"></span>
          <span>Grafito Quirúrgico</span>
        </button>
        <button type="button" class="swatch-chip" id="swatch_brand_green" onclick="applyBrandColorTheme('#047857', '#064E3B')">
          <span style="width: 14px; height: 14px; border-radius: 50%; background: #047857;"></span>
          <span>Verde Quirófano</span>
        </button>
        <button type="button" class="swatch-chip" id="swatch_brand_wine" onclick="applyBrandColorTheme('#881337', '#4C0519')">
          <span style="width: 14px; height: 14px; border-radius: 50%; background: #881337;"></span>
          <span>Burdeo / Vino Tinto</span>
        </button>
      </div>
    </div>

    <!-- PANEL TAB 5: 👥 CRM AUDIENCIA -->
    <div id="panel_crm" style="display: none;">
      <h4 style="margin: 0 0 10px 0; font-size: 13px; font-weight: 800; color: #0F172A; text-transform: uppercase;">👥 Destinatarios CRM</h4>
      <div class="form-group">
        <label style="font-size: 11px; font-weight: 700;">Grupo de Contactos:</label>
        <select id="sr_group_id" class="form-control" style="font-size: 12px;">
          <option value="0">Todos los contactos ({{ \App\Models\Client::count() }})</option>
          @foreach($groups as $g)
            <option value="{{ $g->id }}">{{ $g->name }} ({{ $g->clients_count }})</option>
          @endforeach
        </select>
      </div>
    </div>

  </div>

  <!-- CENTER WORKBENCH (CANVAS CENTRAL DONDE SE EDITA EL EMAIL) -->
  <div class="sr-canvas-workbench">
    
    <!-- BANDEJA DE ENTRADA SIMULATOR BAR -->
    <div style="width: 100%; max-width: 600px; background: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; font-size: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
        <span style="color: #64748B; font-size: 10px; text-transform: uppercase; font-weight: 700;">Simulador de Bandeja de Entrada (Gmail / Outlook):</span>
        <span style="color: #1E8888; font-size: 11px; font-weight: 700; cursor: pointer;" onclick="switchSideTool('modules')">✏️ Editar Asunto</span>
      </div>
      <div style="font-weight: 800; color: #0F172A;" id="canvas_subject_display">
        {{ $initialPreset['subject'] ?? '[Convenio Clínico] Uniformes médicos con 6 meses de garantía directa de fábrica y servicio de tallaje' }}
      </div>
      <div style="color: #64748B; font-size: 11px;" id="canvas_preheader_display">
        {{ $initialPreset['preheader'] ?? 'Somos fabricantes chilenos de uniformes clínicos antifluidos. Servicio exclusivo de tallaje en su clínica y 6 meses de garantía.' }}
      </div>
    </div>

    <!-- EL EMAIL CANVAS INTERACTIVO (ESTILO STAMPREADY) -->
    <div class="sr-email-canvas" id="sr_email_canvas">
      
      <!-- MÓDULO 1: BARRA SUPERIOR & CABECERA -->
      <div class="sr-module-block" id="mod_header" data-sr-type="module" data-sr-label="Cabecera y Navegación">
        <div class="sr-module-toolbar">
          <button type="button" class="sr-mod-btn" onclick="moduleMove('mod_header', -1)">▲</button>
          <button type="button" class="sr-mod-btn" onclick="moduleMove('mod_header', 1)">▼</button>
          <button type="button" class="sr-mod-btn" onclick="quickAiRewriteBlock('header')">✨ IA</button>
        </div>

        <!-- TOP BAR -->
        <div style="background: #0F2B2B; color: #A4B8B8; font-size: 10.5px; padding: 7px 18px; display: flex; justify-content: space-between; align-items: center;">
          <span class="sr-detect-text" id="el_topbar_text" data-sr-type="text" data-sr-label="Barra Superior">Convenios &amp; Ventas Corporativas | Suitable Chile</span>
          <span style="color: #6CD2D2;">Ver en el navegador</span>
        </div>

        <!-- BRAND & MENU HEADER -->
        <div id="header_bg_container" style="background: #1E8888; padding: 22px 20px 18px 20px; text-align: center; color: white;">
          <div class="sr-detect-text" id="el_brand_title" data-sr-type="text" data-sr-label="Nombre de Marca" style="font-size: 24px; font-weight: 900; letter-spacing: 2px; color: #FFFFFF;">SUITABLE</div>
          <div class="sr-detect-text" id="el_brand_tagline" data-sr-type="text" data-sr-label="Bajada de Marca" style="color: #CCFBF1; font-size: 11px; margin-top: 3px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
            Uniformes Clínicos de Alta Gama • Confección Nacional
          </div>

          <!-- SUBMENU CATEGORÍAS (COMO MORELIA) -->
          <div style="display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; margin-top: 12px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.2); font-size: 11px; font-weight: 700; color: #CCFBF1;">
            <span class="sr-detect-text" id="el_nav_1" data-sr-type="text" data-sr-label="Menú Categoría 1">Scrubs Mujer</span> • 
            <span class="sr-detect-text" id="el_nav_2" data-sr-type="text" data-sr-label="Menú Categoría 2">Scrubs Hombre</span> • 
            <span class="sr-detect-text" id="el_nav_3" data-sr-type="text" data-sr-label="Menú Categoría 3">Delantales</span> • 
            <span class="sr-detect-text" id="el_nav_4" data-sr-type="text" data-sr-label="Menú Categoría 4">Tallaje en Terreno</span> • 
            <span class="sr-detect-text" id="el_nav_5" data-sr-type="text" data-sr-label="Menú Categoría 5">Telas Flex</span>
          </div>
        </div>
      </div>

      <!-- MÓDULO 2: HERO BANNER (PORTADA PRINCIPAL CON RECONOCIMIENTO INTELIGENTE) -->
      <div class="sr-module-block" id="mod_hero" data-sr-type="module" data-sr-label="Portada Principal (Hero)">
        <div class="sr-module-toolbar">
          <button type="button" class="sr-mod-btn" onclick="moduleMove('mod_hero', -1)">▲</button>
          <button type="button" class="sr-mod-btn" onclick="moduleMove('mod_hero', 1)">▼</button>
          <button type="button" class="sr-mod-btn" onclick="triggerSmartImageGenerator('hero')">📸 IA Imagen</button>
          <button type="button" class="sr-mod-btn" onclick="quickAiRewriteBlock('hero')">✨ IA Texto</button>
        </div>

        <!-- HERO IMAGE (CLICKABLE & AI RECOGNIZED) -->
        <div class="sr-detect-image" onclick="selectImageElement('hero', 'Fotografía Portada Hero')" data-sr-type="image" data-sr-label="Fotografía Portada Hero" title="Haz clic para generar o cambiar esta fotografía con IA">
          <div class="sr-tag-image-badge">📸 IMAGEN DETECTADA • GENERAR CON IA</div>
          <img id="el_hero_img" src="{{ asset('images/' . ($initialPreset['hero_image'] ?? 'hero-grupo-clinico.jpg')) }}" alt="Hero Banner" style="width: 100%; height: auto; display: block; object-fit: cover; max-height: 280px;">
        </div>

        <!-- HERO CONTENT -->
        <div id="hero_content_container" style="background: linear-gradient(135deg, #1E8888 0%, #146161 100%); padding: 30px 24px; text-align: center; color: white;">
          <div class="sr-detect-text" id="el_hero_badge" data-sr-type="text" data-sr-label="Etiqueta Superior" style="display: inline-block; background: rgba(0,0,0,0.2); padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 800; letter-spacing: 0.8px; margin-bottom: 12px; border: 1px solid rgba(255,255,255,0.25);">
            ✦ PROPUESTA CORPORATIVA INSTITUCIONAL
          </div>
          <h1 class="sr-detect-text" id="el_hero_title" data-sr-type="text" data-sr-label="Título Portada Hero" style="font-size: 21px; font-weight: 800; color: #FFFFFF; margin: 0 0 12px 0; line-height: 1.3;">
            {{ $initialPreset['hero_title'] ?? 'Distinción y Confort para su Equipo Médico' }}
          </h1>
          <p class="sr-detect-text" id="el_hero_desc" data-sr-type="text" data-sr-label="Descripción Portada Hero" style="font-size: 13px; color: #E6F7F7; line-height: 1.6; margin: 0 auto 20px auto; max-width: 480px;">
            {{ $initialPreset['hero_desc'] ?? 'En Suitable confeccionamos uniformes clínicos de alto rendimiento con telas antifluidos de última generación y respaldo integral de fábrica. Llevamos muestras en vivo a su clínica para que su equipo pruebe tallas antes de comprar.' }}
          </p>
          <div style="margin-top: 10px;">
            <a href="https://suitable.cl/clinicas-y-centros/" target="_blank" class="sr-detect-button" id="el_hero_btn" data-sr-type="button" data-sr-label="Botón Principal de Portada" onclick="selectButtonElement(event, 'el_hero_btn', 'Botón de Portada')" style="background: #FFFFFF; color: #146161; padding: 12px 24px; border-radius: 6px; font-size: 12.5px; font-weight: 800; text-decoration: none; display: inline-block; text-transform: uppercase; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
              Solicitar Visita de Muestras y Tallaje →
            </a>
          </div>
        </div>
      </div>

      <!-- MÓDULO 3: PRODUCTOS / PILARES ("LOS MÁS ELEGIDOS" ESTILO MORELIA) -->
      <div class="sr-module-block" id="mod_products" data-sr-type="module" data-sr-label="Productos y Pilares (3 Columnas)" style="padding: 28px 20px; background: #FFFFFF; text-align: center;">
        <div class="sr-module-toolbar">
          <button type="button" class="sr-mod-btn" onclick="moduleMove('mod_products', -1)">▲</button>
          <button type="button" class="sr-mod-btn" onclick="moduleMove('mod_products', 1)">▼</button>
          <button type="button" class="sr-mod-btn" onclick="quickAiRewriteBlock('products')">✨ IA Texto</button>
        </div>

        <h2 class="sr-detect-text" id="el_products_title" data-sr-type="text" data-sr-label="Título Bloque Productos" style="font-size: 18px; font-weight: 800; color: #0F172A; margin: 0 0 6px 0;">
          Los más elegidos por instituciones de salud
        </h2>
        <p class="sr-detect-text" id="el_products_sub" data-sr-type="text" data-sr-label="Bajada Bloque Productos" style="font-size: 12.5px; color: #64748B; margin: 0 0 22px 0;">
          Confección ergonómica y telas de alta resistencia a lavados clínicos
        </p>

        <!-- 3 COLUMNAS -->
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; text-align: center;">
          
          <!-- COL 1 -->
          <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
              <div class="sr-detect-image" onclick="selectImageElement('pilar1', 'Foto Pilar 1 (Scrub Flex)')" data-sr-type="image" data-sr-label="Foto Pilar 1" style="height: 100px; border-radius: 6px; overflow: hidden; background: #E2E8F0; margin-bottom: 8px;">
                <div class="sr-tag-image-badge">📸 IA</div>
                <img id="img_pilar_1" src="{{ asset('images/hero-grupo-clinico.jpg') }}" style="width: 100%; height: 100%; object-fit: cover;">
              </div>
              <strong class="sr-detect-text" id="el_p1_title" data-sr-type="text" data-sr-label="Título Pilar 1" style="font-size: 12px; color: #0F172A; display: block;">{{ $initialPreset['pilar1_title'] ?? 'Scrub Flex Ergonómico' }}</strong>
              <span class="sr-detect-text" id="el_p1_badge" data-sr-type="text" data-sr-label="Precio/Detalle Pilar 1" style="font-size: 10px; color: #1E8888; font-weight: 800; display: block; margin: 2px 0 6px 0;">Flex 4-Way • $24.900</span>
              <p class="sr-detect-text" id="el_p1_desc" data-sr-type="text" data-sr-label="Descripción Pilar 1" style="font-size: 10.5px; color: #64748B; line-height: 1.4; margin: 0 0 10px 0;">{{ $initialPreset['pilar1_desc'] ?? 'Confección directa sin intermediarios con 6 meses de garantía.' }}</p>
            </div>
            <div>
              <a href="#" class="sr-detect-button" id="el_p1_btn" data-sr-type="button" data-sr-label="Botón Pilar 1" onclick="selectButtonElement(event, 'el_p1_btn', 'Botón Cotizar Pilar 1')" style="background: #1E8888; color: white; padding: 5px 12px; border-radius: 4px; font-size: 10px; font-weight: 700; text-decoration: none; display: inline-block;">Cotizar →</a>
            </div>
          </div>

          <!-- COL 2 -->
          <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
              <div class="sr-detect-image" onclick="selectImageElement('pilar2', 'Foto Pilar 2 (Delantal Antifluido)')" data-sr-type="image" data-sr-label="Foto Pilar 2" style="height: 100px; border-radius: 6px; overflow: hidden; background: #E2E8F0; margin-bottom: 8px;">
                <div class="sr-tag-image-badge">📸 IA</div>
                <img id="img_pilar_2" src="{{ asset('images/tela-antifluidos-macro.jpg') }}" style="width: 100%; height: 100%; object-fit: cover;">
              </div>
              <strong class="sr-detect-text" id="el_p2_title" data-sr-type="text" data-sr-label="Título Pilar 2" style="font-size: 12px; color: #0F172A; display: block;">{{ $initialPreset['pilar2_title'] ?? 'Delantal Antifluido' }}</strong>
              <span class="sr-detect-text" id="el_p2_badge" data-sr-type="text" data-sr-label="Precio/Detalle Pilar 2" style="font-size: 10px; color: #1E8888; font-weight: 800; display: block; margin: 2px 0 6px 0;">Repelente • $19.900</span>
              <p class="sr-detect-text" id="el_p2_desc" data-sr-type="text" data-sr-label="Descripción Pilar 2" style="font-size: 10.5px; color: #64748B; line-height: 1.4; margin: 0 0 10px 0;">{{ $initialPreset['pilar2_desc'] ?? 'Barrera biosegura contra salpicaduras y fluidos corporales.' }}</p>
            </div>
            <div>
              <a href="#" class="sr-detect-button" id="el_p2_btn" data-sr-type="button" data-sr-label="Botón Pilar 2" onclick="selectButtonElement(event, 'el_p2_btn', 'Botón Cotizar Pilar 2')" style="background: #1E8888; color: white; padding: 5px 12px; border-radius: 4px; font-size: 10px; font-weight: 700; text-decoration: none; display: inline-block;">Cotizar →</a>
            </div>
          </div>

          <!-- COL 3 -->
          <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
              <div class="sr-detect-image" onclick="selectImageElement('pilar3', 'Foto Pilar 3 (Tallaje en Terreno)')" data-sr-type="image" data-sr-label="Foto Pilar 3" style="height: 100px; border-radius: 6px; overflow: hidden; background: #E2E8F0; margin-bottom: 8px;">
                <div class="sr-tag-image-badge">📸 IA</div>
                <img id="img_pilar_3" src="{{ asset('images/servicio-tallaje-terreno.jpg') }}" style="width: 100%; height: 100%; object-fit: cover;">
              </div>
              <strong class="sr-detect-text" id="el_p3_title" data-sr-type="text" data-sr-label="Título Pilar 3" style="font-size: 12px; color: #0F172A; display: block;">{{ $initialPreset['pilar3_title'] ?? 'Tallaje en Terreno' }}</strong>
              <span class="sr-detect-text" id="el_p3_badge" data-sr-type="text" data-sr-label="Precio/Detalle Pilar 3" style="font-size: 10px; color: #1E8888; font-weight: 800; display: block; margin: 2px 0 6px 0;">Curva XS a 3XL • Sin Costo</span>
              <p class="sr-detect-text" id="el_p3_desc" data-sr-type="text" data-sr-label="Descripción Pilar 3" style="font-size: 10.5px; color: #64748B; line-height: 1.4; margin: 0 0 10px 0;">{{ $initialPreset['pilar3_desc'] ?? 'Percheros móviles en su clínica para probar tallas en vivo.' }}</p>
            </div>
            <div>
              <a href="#" class="sr-detect-button" id="el_p3_btn" data-sr-type="button" data-sr-label="Botón Pilar 3" onclick="selectButtonElement(event, 'el_p3_btn', 'Botón Agendar Pilar 3')" style="background: #1E8888; color: white; padding: 5px 12px; border-radius: 4px; font-size: 10px; font-weight: 700; text-decoration: none; display: inline-block;">Agendar →</a>
            </div>
          </div>

        </div>
      </div>

      <!-- MÓDULO 4: MUESTRARIO / MINI-CATEGORÍAS -->
      <div class="sr-module-block" id="mod_showcase" data-sr-type="module" data-sr-label="Muestrario de Categorías" style="padding: 18px 20px; background: #F8FAFC; border-top: 1px solid #E2E8F0; text-align: center;">
        <div class="sr-module-toolbar">
          <button type="button" class="sr-mod-btn" onclick="moduleMove('mod_showcase', -1)">▲</button>
          <button type="button" class="sr-mod-btn" onclick="moduleMove('mod_showcase', 1)">▼</button>
        </div>
        <span class="sr-detect-text" id="el_showcase_title" data-sr-type="text" data-sr-label="Título de Muestrario" style="font-size: 11px; font-weight: 800; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">
          Líneas de Especialidad Suitable
        </span>
        <div style="display: flex; justify-content: center; gap: 8px; flex-wrap: wrap;">
          <span class="badge sr-detect-text" id="el_chip_1" data-sr-type="text" data-sr-label="Chip 1" style="background: white; border: 1px solid #CBD5E1; color: #0F172A; font-size: 10.5px; padding: 4px 10px;">Scrubs Clínicos</span>
          <span class="badge sr-detect-text" id="el_chip_2" data-sr-type="text" data-sr-label="Chip 2" style="background: white; border: 1px solid #CBD5E1; color: #0F172A; font-size: 10.5px; padding: 4px 10px;">Delantales</span>
          <span class="badge sr-detect-text" id="el_chip_3" data-sr-type="text" data-sr-label="Chip 3" style="background: white; border: 1px solid #CBD5E1; color: #0F172A; font-size: 10.5px; padding: 4px 10px;">Servicio Tallaje</span>
          <span class="badge sr-detect-text" id="el_chip_4" data-sr-type="text" data-sr-label="Chip 4" style="background: white; border: 1px solid #CBD5E1; color: #0F172A; font-size: 10.5px; padding: 4px 10px;">Telas Flex 4-Way</span>
          <span class="badge sr-detect-text" id="el_chip_5" data-sr-type="text" data-sr-label="Chip 5" style="background: white; border: 1px solid #CBD5E1; color: #0F172A; font-size: 10.5px; padding: 4px 10px;">Bordados Digitales</span>
        </div>
      </div>

      <!-- MÓDULO 5: GARANTÍA & RESPALDO -->
      <div class="sr-module-block" id="mod_guarantee" data-sr-type="module" data-sr-label="Sello de Garantía" style="padding: 16px 20px; background: #E6F4F4; border-top: 1px solid #99D5D5; text-align: center;">
        <div class="sr-module-toolbar">
          <button type="button" class="sr-mod-btn" onclick="moduleMove('mod_guarantee', -1)">▲</button>
          <button type="button" class="sr-mod-btn" onclick="moduleMove('mod_guarantee', 1)">▼</button>
        </div>
        <strong class="sr-detect-text" id="el_guar_title" data-sr-type="text" data-sr-label="Título Garantía" style="font-size: 13px; color: #115E59; display: block; margin-bottom: 3px;">
          🛡️ 6 Meses de Garantía Oficial de Fábrica
        </strong>
        <span class="sr-detect-text" id="el_guar_desc" data-sr-type="text" data-sr-label="Detalle Garantía" style="font-size: 11px; color: #146161; display: block;">
          Respaldo directo del fabricante chileno ante cualquier defecto de costura o tela.
        </span>
      </div>

      <!-- MÓDULO 6: FOOTER -->
      <div class="sr-module-block" id="mod_footer" data-sr-type="module" data-sr-label="Pie de Página" style="background: #0F172A; color: #94A3B8; padding: 22px 20px; text-align: center; font-size: 11px;">
        <div class="sr-module-toolbar">
          <button type="button" class="sr-mod-btn" onclick="moduleMove('mod_footer', -1)">▲</button>
        </div>
        <div style="color: #FFFFFF; font-weight: 700; margin-bottom: 4px;" class="sr-detect-text" id="el_footer_title" data-sr-type="text" data-sr-label="Footer Marca">Suitable Chile • Confección Médica de Alta Gama</div>
        <div style="margin-bottom: 6px;">
          WhatsApp: <span class="sr-detect-text" id="el_footer_wa" data-sr-type="text" data-sr-label="Footer WhatsApp" style="color: #CCFBF1;">+56 9 3302 3278</span> | Fono: <span class="sr-detect-text" id="el_footer_ph" data-sr-type="text" data-sr-label="Footer Teléfono">+56 2 2987 6543</span>
        </div>
        <div class="sr-detect-text" id="el_footer_addr" data-sr-type="text" data-sr-label="Footer Dirección" style="margin-bottom: 8px;">
          Santiago, Chile • Talleres Centrales • Envíos a Todo el País
        </div>
        <div style="font-size: 10px; color: #64748B;">
          ¿Desea dejar de recibir correos corporativos? <span style="text-decoration: underline; color: #94A3B8;">Darse de baja</span>
        </div>
      </div>

    </div>

  </div>

</div>

<!-- SMART TEXT AI FLOATING PILL (SE POSICIONA SOBRE EL TEXTO AL HACER CLIC) -->
<div id="smartTextAiPill">
  <span>✍️ Texto IA:</span>
  <button type="button" class="pill-action-btn" onclick="runTextSmartAction('persuasivo')">🎯 Persuasivo</button>
  <button type="button" class="pill-action-btn" onclick="runTextSmartAction('garantia')">🛡️ Garantía</button>
  <button type="button" class="pill-action-btn" onclick="runTextSmartAction('corto')">✂️ Acortar</button>
  <button type="button" class="pill-action-btn" onclick="dictateIntoCurrentText()">🎙️ Dictar</button>
  <button type="button" class="pill-action-btn" onclick="closeSmartTextPill()" style="color: #94A3B8;">✕</button>
</div>

<!-- SMART IMAGE AI MODAL (SE ABRE AL HACER CLIC O DESDE EL INSPECTOR) -->
<div id="smartImageAiModal" onclick="handleImageModalBgClick(event)">
  <div class="smart-img-box">
    
    <div style="background: #0F172A; color: white; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center;">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 20px;">📸</span>
        <div>
          <h3 style="margin: 0; font-size: 14px; font-weight: 800;">Asistente de Imagen Médica con IA</h3>
          <span id="smartImgTargetLabel" style="font-size: 10.5px; color: #94A3B8;">Elemento: Portada Hero Banner</span>
        </div>
      </div>
      <button type="button" onclick="closeSmartImageModal()" style="background: none; border: none; color: #94A3B8; font-size: 22px; cursor: pointer;">✕</button>
    </div>

    <div style="padding: 18px 20px;">
      
      <!-- ⚡ BOTÓN ESTELAR RÁPIDO EN MODAL -->
      <button type="button" class="btn-star-ai" onclick="generateImageFromCurrentEmailContext()">
        <span>✨ Generar Fotografía según el Contexto de este Correo (3s)</span>
      </button>

      <!-- TEMÁTICAS RÁPIDAS -->
      <div style="margin-bottom: 12px;">
        <span style="font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase;">1. Tipo de Fotografía Clínica:</span>
        <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px;">
          <button type="button" class="badge" onclick="setSmartImgTheme('equipo')" style="border: none; background: #F1F5F9; color: #0F172A; cursor: pointer; padding: 5px 9px;">👨‍⚕️ Equipo Clínico</button>
          <button type="button" class="badge" onclick="setSmartImgTheme('tela')" style="border: none; background: #F1F5F9; color: #0F172A; cursor: pointer; padding: 5px 9px;">💧 Micro Tela Antifluido</button>
          <button type="button" class="badge" onclick="setSmartImgTheme('tallaje')" style="border: none; background: #F1F5F9; color: #0F172A; cursor: pointer; padding: 5px 9px;">📏 Percheros Terreno</button>
          <button type="button" class="badge" onclick="setSmartImgTheme('dental')" style="border: none; background: #F1F5F9; color: #0F172A; cursor: pointer; padding: 5px 9px;">🦷 Odontología</button>
          <button type="button" class="badge" onclick="setSmartImgTheme('quirofano')" style="border: none; background: #F1F5F9; color: #0F172A; cursor: pointer; padding: 5px 9px;">🏥 Quirófano</button>
          <button type="button" class="badge" onclick="setSmartImgTheme('estetica')" style="border: none; background: #F1F5F9; color: #0F172A; cursor: pointer; padding: 5px 9px;">💆‍♀️ Estética</button>
        </div>
      </div>

      <!-- PALETA DE COLOR DE SCRUBS -->
      <div style="margin-bottom: 14px;">
        <span style="font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase;">2. Color de Uniformes:</span>
        <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px;">
          <button type="button" class="swatch-chip active" id="modal_swatch_petroleo" onclick="setSmartImgColor('petroleo')">
            <span style="width: 10px; height: 10px; border-radius: 50%; background: #1E8888;"></span>
            <span>Azul Petróleo</span>
          </button>
          <button type="button" class="swatch-chip" id="modal_swatch_marino" onclick="setSmartImgColor('marino')">
            <span style="width: 10px; height: 10px; border-radius: 50%; background: #1E3A8A;"></span>
            <span>Marino</span>
          </button>
          <button type="button" class="swatch-chip" id="modal_swatch_grafito" onclick="setSmartImgColor('grafito')">
            <span style="width: 10px; height: 10px; border-radius: 50%; background: #334155;"></span>
            <span>Grafito</span>
          </button>
          <button type="button" class="swatch-chip" id="modal_swatch_verde" onclick="setSmartImgColor('verde')">
            <span style="width: 10px; height: 10px; border-radius: 50%; background: #047857;"></span>
            <span>Verde</span>
          </button>
        </div>
      </div>

      <!-- GENERADOR PROMPT / VOZ -->
      <div style="display: flex; gap: 8px; margin-bottom: 14px;">
        <input type="text" id="smart_ai_img_prompt" class="form-control" placeholder="Instrucción adicional (opcional)..." style="font-size: 12px;">
        <button type="button" class="btn btn-primary btn-sm" id="btn_run_smart_img" onclick="executeSmartImageGeneration()" style="background: #1E8888; font-weight: 800; white-space: nowrap; padding: 0 16px;">
          ⚡ Generar (3s)
        </button>
      </div>

      <!-- FOTOS BASE SUITABLE -->
      <div style="border-top: 1px solid #E2E8F0; padding-top: 12px;">
        <span style="font-size: 11px; font-weight: 700; color: #64748B; display: block; margin-bottom: 6px;">O elige una fotografía de catálogo:</span>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
          <div onclick="applyDirectImage('{{ asset('images/hero-grupo-clinico.jpg') }}', 'hero-grupo-clinico.jpg')" style="height: 55px; border-radius: 6px; overflow: hidden; cursor: pointer; border: 1.5px solid #CBD5E1;">
            <img src="{{ asset('images/hero-grupo-clinico.jpg') }}" style="width: 100%; height: 100%; object-fit: cover;">
          </div>
          <div onclick="applyDirectImage('{{ asset('images/tela-antifluidos-macro.jpg') }}', 'tela-antifluidos-macro.jpg')" style="height: 55px; border-radius: 6px; overflow: hidden; cursor: pointer; border: 1.5px solid #CBD5E1;">
            <img src="{{ asset('images/tela-antifluidos-macro.jpg') }}" style="width: 100%; height: 100%; object-fit: cover;">
          </div>
          <div onclick="applyDirectImage('{{ asset('images/servicio-tallaje-terreno.jpg') }}', 'servicio-tallaje-terreno.jpg')" style="height: 55px; border-radius: 6px; overflow: hidden; cursor: pointer; border: 1.5px solid #CBD5E1;">
            <img src="{{ asset('images/servicio-tallaje-terreno.jpg') }}" style="width: 100%; height: 100%; object-fit: cover;">
          </div>
        </div>
      </div>

    </div>

  </div>
</div>

@endsection

@push('scripts')
<script>
  let activeElementImg = null;
  let activeElementText = null;
  let activeElementBtn = null;
  let activeImageTargetKey = 'hero';
  let smartImgTheme = 'equipo';
  let smartImgColor = 'petroleo';

  // --- 1. DETECCIÓN AUTOMÁTICA Y RECONOCIMIENTO INTELIGENTE ---
  function initSmartAiInspectors() {
    const canvas = document.getElementById('sr_email_canvas');
    if (!canvas) return;

    // A. Detectar Textos Editables
    canvas.querySelectorAll('.sr-detect-text').forEach(el => {
      el.setAttribute('contenteditable', 'true');
      el.setAttribute('spellcheck', 'false');

      el.addEventListener('click', (e) => {
        e.stopPropagation();
        selectTextElement(el);
      });

      el.addEventListener('input', () => {
        if (activeElementText === el) {
          document.getElementById('inspector_text_content').value = el.innerText.trim();
        }
      });
    });

    // B. Detectar Módulos al hacer clic en zonas vacías
    canvas.querySelectorAll('.sr-module-block').forEach(mod => {
      mod.addEventListener('click', (e) => {
        if (e.target === mod) {
          selectModuleElement(mod);
        }
      });
    });

    // Inicializar con la portada Hero seleccionada para que el usuario vea el inspector de imagen de inmediato
    selectImageElement('hero', 'Fotografía Portada Hero');
  }

  // --- 2. SELECCIÓN CONTEXTUAL: IMAGEN ---
  function selectImageElement(targetKey, label) {
    activeImageTargetKey = targetKey;
    clearSelectionHighlights();

    // Buscar el contenedor
    let imgWrap = null;
    if (targetKey === 'hero') imgWrap = document.getElementById('el_hero_img').closest('.sr-detect-image');
    else if (targetKey === 'pilar1') imgWrap = document.getElementById('img_pilar_1').closest('.sr-detect-image');
    else if (targetKey === 'pilar2') imgWrap = document.getElementById('img_pilar_2').closest('.sr-detect-image');
    else if (targetKey === 'pilar3') imgWrap = document.getElementById('img_pilar_3').closest('.sr-detect-image');

    if (imgWrap) imgWrap.classList.add('sr-selected-el');

    // Cambiar Inspector a Modo Imagen
    switchSideTool('inspector');
    document.getElementById('inspector_view_image').style.display = 'block';
    document.getElementById('inspector_view_text').style.display = 'none';
    document.getElementById('inspector_view_button').style.display = 'none';
    document.getElementById('inspector_view_module').style.display = 'none';

    document.getElementById('inspector_img_title').innerText = label || ('Imagen: ' + targetKey);
    document.getElementById('smartImgTargetLabel').innerText = 'Elemento: ' + (label || targetKey);
    closeSmartTextPill();
  }

  // --- 3. SELECCIÓN CONTEXTUAL: TEXTO ---
  function selectTextElement(el) {
    activeElementText = el;
    clearSelectionHighlights();
    el.classList.add('sr-selected-el');

    // Cambiar Inspector a Modo Texto
    switchSideTool('inspector');
    document.getElementById('inspector_view_image').style.display = 'none';
    document.getElementById('inspector_view_text').style.display = 'block';
    document.getElementById('inspector_view_button').style.display = 'none';
    document.getElementById('inspector_view_module').style.display = 'none';

    const label = el.getAttribute('data-sr-label') || 'Texto Seleccionado';
    document.getElementById('inspector_text_title').innerText = label;
    document.getElementById('inspector_text_content').value = el.innerText.trim();

    // Posicionar píldora flotante rápida sobre el texto
    positionSmartTextPill(el);
  }

  // --- 4. SELECCIÓN CONTEXTUAL: BOTÓN ---
  function selectButtonElement(e, btnId, label) {
    if (e) e.preventDefault();
    const btn = document.getElementById(btnId);
    if (!btn) return;
    activeElementBtn = btn;
    clearSelectionHighlights();
    btn.classList.add('sr-selected-el');

    // Cambiar Inspector a Modo Botón
    switchSideTool('inspector');
    document.getElementById('inspector_view_image').style.display = 'none';
    document.getElementById('inspector_view_text').style.display = 'none';
    document.getElementById('inspector_view_button').style.display = 'block';
    document.getElementById('inspector_view_module').style.display = 'none';

    document.getElementById('inspector_btn_title').innerText = label || 'Botón de Acción';
    document.getElementById('inspector_btn_text').value = btn.innerText.trim();
    document.getElementById('inspector_btn_url').value = btn.getAttribute('href') || 'https://suitable.cl/clinicas-y-centros/';
    closeSmartTextPill();
  }

  // --- 5. SELECCIÓN CONTEXTUAL: MÓDULO ---
  function selectModuleElement(mod) {
    clearSelectionHighlights();
    switchSideTool('inspector');
    document.getElementById('inspector_view_image').style.display = 'none';
    document.getElementById('inspector_view_text').style.display = 'none';
    document.getElementById('inspector_view_button').style.display = 'none';
    document.getElementById('inspector_view_module').style.display = 'block';

    const label = mod.getAttribute('data-sr-label') || 'Módulo del Correo';
    document.getElementById('inspector_mod_title').innerText = label;
    closeSmartTextPill();
  }

  function clearSelectionHighlights() {
    document.querySelectorAll('.sr-selected-el').forEach(el => el.classList.remove('sr-selected-el'));
  }

  // --- 6. SINCRONIZACIÓN EN VIVO DESDE EL INSPECTOR ---
  function syncInspectorTextToCanvas() {
    if (activeElementText) {
      activeElementText.innerText = document.getElementById('inspector_text_content').value;
    }
  }

  function syncInspectorBtnToCanvas() {
    if (activeElementBtn) {
      activeElementBtn.innerText = document.getElementById('inspector_btn_text').value;
    }
  }

  function syncInspectorBtnUrlToCanvas() {
    if (activeElementBtn) {
      activeElementBtn.setAttribute('href', document.getElementById('inspector_btn_url').value);
    }
  }

  function setInspectorBtnCopy(copy) {
    document.getElementById('inspector_btn_text').value = copy;
    syncInspectorBtnToCanvas();
    showToast('✓ Texto de botón actualizado', 'success');
  }

  function setBtnToWhatsApp() {
    const waUrl = "https://wa.me/56933023278?text=" + encodeURIComponent("Hola Suitable, solicito cotización corporativa y catálogo para mi clínica.");
    document.getElementById('inspector_btn_url').value = waUrl;
    syncInspectorBtnUrlToCanvas();
    showToast('✓ Botón vinculado a WhatsApp oficial Suitable', 'success');
  }

  // --- 7. BARRA FLOTANTE SMART TEXT AI ---
  function positionSmartTextPill(el) {
    const pill = document.getElementById('smartTextAiPill');
    const rect = el.getBoundingClientRect();
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

    pill.style.top = (rect.top + scrollTop - 38) + 'px';
    pill.style.left = Math.max(10, (rect.left + scrollLeft + (rect.width / 2) - 150)) + 'px';
    pill.style.display = 'inline-flex';
  }

  function closeSmartTextPill() {
    const pill = document.getElementById('smartTextAiPill');
    if (pill) pill.style.display = 'none';
  }

  async function runTextSmartAction(type) {
    if (!activeElementText) return;
    const origText = activeElementText.innerText.trim();
    if (!origText) return;

    let instruction = 'Mejorar redacción profesional B2B para clínicas';
    if (type === 'persuasivo') instruction = 'Hacer el texto más persuasivo comercialmente enfocado en directores médicos y comités de compras';
    if (type === 'garantia') instruction = 'Reescribir enfatizando los 6 meses de garantía directa de fábrica y confección 100% chilena';
    if (type === 'corto') instruction = 'Hacer el texto más corto, directo y conciso para lectura en móvil';
    if (type === 'formal') instruction = 'Reescribir con tono formal corporativo para instituciones de salud y hospitales';

    showToast('✨ Asistente IA reescribiendo texto...', 'info');

    try {
      const token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
      const res = await fetch("{{ route('campaigns.ai_rewrite_snippet') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: JSON.stringify({ text: origText, instruction: instruction, provider: document.getElementById('sr_ai_provider').value })
      });
      const data = await res.json();
      if (data.success && data.improved_text) {
        activeElementText.innerText = data.improved_text;
        document.getElementById('inspector_text_content').value = data.improved_text;
        showToast('✓ Texto mejorado por IA', 'success');
        closeSmartTextPill();
      }
    } catch(e) {
      showToast('Error al procesar con IA', 'error');
    }
  }

  async function runCustomTextAction() {
    const instruction = document.getElementById('inspector_text_instruction').value.trim();
    if (!instruction || !activeElementText) return;
    const origText = activeElementText.innerText.trim();

    showToast('✨ Aplicando instrucción personalizada con IA...', 'info');
    try {
      const token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
      const res = await fetch("{{ route('campaigns.ai_rewrite_snippet') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: JSON.stringify({ text: origText, instruction: instruction, provider: document.getElementById('sr_ai_provider').value })
      });
      const data = await res.json();
      if (data.success && data.improved_text) {
        activeElementText.innerText = data.improved_text;
        document.getElementById('inspector_text_content').value = data.improved_text;
        showToast('✓ Texto actualizado', 'success');
      }
    } catch(e) {
      showToast('Error al procesar', 'error');
    }
  }

  function dictateIntoCurrentText() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
      showToast('El dictado por voz requiere Google Chrome o Microsoft Edge', 'warning');
      return;
    }
    const rec = new SpeechRecognition();
    rec.lang = 'es-CL';
    rec.onstart = () => showToast('🎙️ Escuchando... habla ahora', 'info');
    rec.onresult = (e) => {
      const text = e.results[0][0].transcript;
      if (activeElementText) {
        activeElementText.innerText = text;
        document.getElementById('inspector_text_content').value = text;
      }
    };
    rec.start();
  }

  function dictateIntoInput(inputId) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
      showToast('El dictado por voz requiere Google Chrome o Edge', 'warning');
      return;
    }
    const rec = new SpeechRecognition();
    rec.lang = 'es-CL';
    rec.onstart = () => showToast('🎙️ Escuchando prompt...', 'info');
    rec.onresult = (e) => {
      const text = e.results[0][0].transcript;
      document.getElementById(inputId).value = text;
    };
    rec.start();
  }

  // --- 8. ASISTENTE DE IMAGEN CONTEXTUAL INTELIGENTE (LA JOYA DEL USUARIO) ---

  // GENERAR IMAGEN AUTOMÁTICA SEGÚN EL CONTEXTO DEL CORREO
  async function generateImageFromCurrentEmailContext() {
    showToast('🧠 Asistente IA analizando el contexto de este correo para generar la foto perfecta...', 'info');

    // 1. Analizar el contexto del correo actual
    const subject = document.getElementById('sr_subject_input').value.toLowerCase();
    const heroTitle = document.getElementById('el_hero_title').innerText.toLowerCase();
    const fullContext = subject + ' ' + heroTitle;

    let autoTheme = 'equipo';
    if (fullContext.includes('dental') || fullContext.includes('odontolog')) autoTheme = 'dental';
    else if (fullContext.includes('quirofano') || fullContext.includes('pabellon') || fullContext.includes('cirug')) autoTheme = 'quirofano';
    else if (fullContext.includes('tela') || fullContext.includes('antifluido') || fullContext.includes('repelente')) autoTheme = 'tela';
    else if (fullContext.includes('tallaje') || fullContext.includes('perchero') || fullContext.includes('terreno')) autoTheme = 'tallaje';
    else if (fullContext.includes('estetica') || fullContext.includes('dermatolog')) autoTheme = 'estetica';

    smartImgTheme = autoTheme;

    // 2. Generar con Pollinations Turbo (3.7s)
    showToast(`📸 Generando fotografía médica con IA (Temática: ${autoTheme.toUpperCase()}, Color: ${smartImgColor})...`, 'info');

    try {
      const token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
      const res = await fetch("{{ route('campaigns.ai_generate_image') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: JSON.stringify({ theme: autoTheme, color: smartImgColor, prompt: '' })
      });
      const data = await res.json();
      if (data.success && data.image_url) {
        applyDirectImage(data.image_url, data.image_name);
        showToast('✓ ¡Fotografía fotorrealista generada e insertada!', 'success');
        closeSmartImageModal();
      } else {
        showToast(data.error || 'Error al generar imagen', 'error');
      }
    } catch(e) {
      showToast('Error de comunicación con el motor de IA', 'error');
    }
  }

  function triggerSmartImageGenerator(targetKey) {
    activeImageTargetKey = targetKey;
    const labelMap = {
      'hero': 'Portada Principal (Hero Banner)',
      'pilar1': 'Producto 1 (Scrub Flex)',
      'pilar2': 'Producto 2 (Delantal Antifluido)',
      'pilar3': 'Producto 3 (Tallaje en Terreno)'
    };
    document.getElementById('smartImgTargetLabel').innerText = 'Elemento: ' + (labelMap[targetKey] || targetKey);
    document.getElementById('smartImageAiModal').style.display = 'flex';
  }

  function closeSmartImageModal() {
    document.getElementById('smartImageAiModal').style.display = 'none';
  }

  function handleImageModalBgClick(e) {
    if (e.target.id === 'smartImageAiModal') closeSmartImageModal();
  }

  function setSmartImgTheme(theme) {
    smartImgTheme = theme;
    const map = {
      'equipo': 'Equipo médico multidisciplinario en clínica privada con uniformes Suitable',
      'tela': 'Macro fotografía de tela Flex repelente antifluidos con gotas de agua',
      'tallaje': 'Servicio de tallaje con percheros móviles en clínica privada Suitable',
      'dental': 'Equipo odontológico moderno con ambos clínicos Suitable',
      'quirofano': 'Equipo quirúrgico con scrubs azul de pabellón Suitable',
      'estetica': 'Doctora en centro de dermatología y estética con scrub Suitable'
    };
    document.getElementById('inspector_img_prompt').value = map[theme] || '';
    document.getElementById('smart_ai_img_prompt').value = map[theme] || '';
    showToast(`✓ Temática: ${theme.toUpperCase()}`, 'info');
  }

  function setSmartImgColor(color) {
    smartImgColor = color;
    document.querySelectorAll('.swatch-chip').forEach(c => c.classList.remove('active'));
    
    const tModal = document.getElementById('modal_swatch_' + color);
    if (tModal) tModal.classList.add('active');

    const tInsp = document.getElementById('swatch_insp_' + color);
    if (tInsp) tInsp.classList.add('active');

    showToast(`✓ Color de scrubs: ${color.toUpperCase()}`, 'info');
  }

  async function executeSmartImageGenerationFromInspector() {
    const prompt = document.getElementById('inspector_img_prompt').value.trim();
    executeSmartImageWithPrompt(prompt, 'btn_run_inspector_img_custom');
  }

  async function executeSmartImageGeneration() {
    const prompt = document.getElementById('smart_ai_img_prompt').value.trim();
    executeSmartImageWithPrompt(prompt, 'btn_run_smart_img');
  }

  async function executeSmartImageWithPrompt(prompt, btnId) {
    const btn = document.getElementById(btnId);
    if (btn) {
      btn.disabled = true;
      btn.innerText = 'Generando...';
    }
    showToast('🎨 Diseñando fotografía clínica con IA (3s Turbo)...', 'info');

    try {
      const token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
      const res = await fetch("{{ route('campaigns.ai_generate_image') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: JSON.stringify({ theme: smartImgTheme, color: smartImgColor, prompt: prompt })
      });
      const data = await res.json();
      if (data.success && data.image_url) {
        applyDirectImage(data.image_url, data.image_name);
        showToast('✓ ¡Fotografía generada e insertada!', 'success');
        closeSmartImageModal();
      } else {
        showToast(data.error || 'Error al generar imagen', 'error');
      }
    } catch(e) {
      showToast('Error de comunicación con el motor de IA', 'error');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerText = '⚡ Generar Fotografía Personalizada (3s)';
      }
    }
  }

  function applyDirectImage(url, filename) {
    if (activeImageTargetKey === 'hero') {
      document.getElementById('el_hero_img').src = url;
    } else if (activeImageTargetKey === 'pilar1') {
      document.getElementById('img_pilar_1').src = url;
    } else if (activeImageTargetKey === 'pilar2') {
      document.getElementById('img_pilar_2').src = url;
    } else if (activeImageTargetKey === 'pilar3') {
      document.getElementById('img_pilar_3').src = url;
    }
    closeSmartImageModal();
  }

  // --- 9. ACCIONES DE MÓDULOS (STAMPREADY STYLE) ---
  function moduleMove(modId, direction) {
    const mod = document.getElementById(modId);
    if (!mod) return;
    if (direction === -1 && mod.previousElementSibling) {
      mod.parentNode.insertBefore(mod, mod.previousElementSibling);
    } else if (direction === 1 && mod.nextElementSibling) {
      mod.parentNode.insertBefore(mod.nextElementSibling, mod);
    }
  }

  function scrollToModule(modId) {
    const el = document.getElementById(modId);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function quickAiRewriteBlock(blockType) {
    if (blockType === 'header') {
      selectTextElement(document.getElementById('el_brand_tagline'));
    } else if (blockType === 'hero') {
      selectTextElement(document.getElementById('el_hero_title'));
    } else if (blockType === 'products') {
      selectTextElement(document.getElementById('el_products_title'));
    }
  }

  // --- 10. SIDEBAR TOOL TABS ---
  function switchSideTool(toolKey) {
    document.querySelectorAll('.sr-tool-btn').forEach(b => b.classList.remove('active'));
    const targetBtn = document.getElementById('tool_btn_' + toolKey);
    if (targetBtn) targetBtn.classList.add('active');

    document.getElementById('panel_inspector').style.display = (toolKey === 'inspector') ? 'block' : 'none';
    document.getElementById('panel_modules').style.display = (toolKey === 'modules') ? 'block' : 'none';
    document.getElementById('panel_copilot').style.display = (toolKey === 'copilot') ? 'block' : 'none';
    document.getElementById('panel_styles').style.display = (toolKey === 'styles') ? 'block' : 'none';
    document.getElementById('panel_crm').style.display = (toolKey === 'crm') ? 'block' : 'none';
  }

  function setCanvasDevice(device) {
    const canvas = document.getElementById('sr_email_canvas');
    const btnD = document.getElementById('btn_sr_desktop');
    const btnM = document.getElementById('btn_sr_mobile');

    if (device === 'mobile') {
      canvas.style.maxWidth = '385px';
      btnM.style.background = 'white'; btnM.style.color = '#0F172A';
      btnD.style.background = 'transparent'; btnD.style.color = '#64748B';
    } else {
      canvas.style.maxWidth = '600px';
      btnD.style.background = 'white'; btnD.style.color = '#0F172A';
      btnM.style.background = 'transparent'; btnM.style.color = '#64748B';
    }
  }

  function applyBrandColorTheme(color1, color2) {
    document.getElementById('header_bg_container').style.background = color1;
    document.getElementById('hero_content_container').style.background = `linear-gradient(135deg, ${color1} 0%, ${color2} 100%)`;
    document.querySelectorAll('.sr-detect-button').forEach(btn => {
      if (btn.id !== 'el_hero_btn') btn.style.background = color1;
    });
    showToast('✓ Paleta de color corporativa aplicada', 'success');
  }

  function syncSubjectToCanvas() {
    document.getElementById('canvas_subject_display').innerText = document.getElementById('sr_subject_input').value;
    document.getElementById('canvas_preheader_display').innerText = document.getElementById('sr_preheader_input').value;
  }

  // --- 11. COPILOTO GENERAL IA ---
  function runCopilotPrompt(prompt) {
    document.getElementById('sr_copilot_prompt').value = prompt;
    executeCopilotAction();
  }

  async function executeCopilotAction() {
    const prompt = document.getElementById('sr_copilot_prompt').value.trim();
    if (!prompt) return;

    const btn = document.getElementById('btn_run_copilot');
    btn.disabled = true;
    btn.innerText = 'Rediseñando con IA...';
    showToast('🧠 Copiloto IA reescribiendo la propuesta...', 'info');

    try {
      const token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
      const res = await fetch("{{ route('campaigns.ai_generate') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: JSON.stringify({ prompt: prompt, provider: document.getElementById('sr_ai_provider').value })
      });
      const data = await res.json();
      if (data.success && data.content) {
        if (data.content.subject) {
          document.getElementById('sr_subject_input').value = data.content.subject;
          syncSubjectToCanvas();
        }
        if (data.content.hero_title) document.getElementById('el_hero_title').innerText = data.content.hero_title;
        if (data.content.hero_desc) document.getElementById('el_hero_desc').innerText = data.content.hero_desc;
        showToast('✓ ¡Propuesta comercial actualizada con Copiloto IA!', 'success');
      }
    } catch(e) {
      showToast('Error con el Copiloto IA', 'error');
    } finally {
      btn.disabled = false;
      btn.innerText = '✨ Aplicar al Correo Completo';
    }
  }

  // --- 12. GUARDAR CAMPAÑA ---
  async function saveStampReadyCampaign() {
    const btn = document.getElementById('btn_save_canvas');
    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span> <span>Guardando...</span>';

    const heroImgSrc = document.getElementById('el_hero_img').src;
    const heroImageFilename = heroImgSrc.substring(heroImgSrc.lastIndexOf('/') + 1);

    const payload = {
      name: document.getElementById('sr_campaign_name').value,
      group_id: document.getElementById('sr_group_id').value,
      preset_template: '{{ $selectedPresetId ?? "clasica" }}',
      subject: document.getElementById('sr_subject_input').value,
      preheader: document.getElementById('sr_preheader_input').value,
      hero_title: document.getElementById('el_hero_title').innerText.trim(),
      hero_desc: document.getElementById('el_hero_desc').innerText.trim(),
      hero_image: heroImageFilename,
      pilar1_title: document.getElementById('el_p1_title').innerText.trim(),
      pilar1_desc: document.getElementById('el_p1_desc').innerText.trim(),
      pilar2_title: document.getElementById('el_p2_title').innerText.trim(),
      pilar2_desc: document.getElementById('el_p2_desc').innerText.trim(),
      pilar3_title: document.getElementById('el_p3_title').innerText.trim(),
      pilar3_desc: document.getElementById('el_p3_desc').innerText.trim(),
      ai_provider: document.getElementById('sr_ai_provider').value
    };

    try {
      const token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
      const res = await fetch("{{ route('campaigns.store') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        showToast('✓ ¡Campaña guardada con éxito en Laravel!', 'success');
        setTimeout(() => window.location.href = "{{ route('campaigns.index') }}", 1000);
      } else {
        showToast(data.error || 'Error al guardar', 'error');
      }
    } catch(e) {
      showToast('Error de comunicación', 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = '💾 Guardar Campaña';
    }
  }

  // --- 13. COPIAR HTML BREVO ---
  function copyPureEmailHtml() {
    const canvas = document.getElementById('sr_email_canvas');
    navigator.clipboard.writeText(canvas.innerHTML);
    alert('✓ Código HTML copiado al portapapeles. ¡Listo para pegar en Brevo!');
  }

  // Inicializar al cargar el DOM
  document.addEventListener('DOMContentLoaded', () => {
    initSmartAiInspectors();
  });
</script>
@endpush
