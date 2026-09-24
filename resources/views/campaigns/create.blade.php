@extends('layouts.app')

@section('title', 'Estudio Creador Modular con IA | Suitable')

@section('content')
<!-- ESTILOS EXCLUSIVOS DEL ESTUDIO MODULAR -->
<style>
  .preset-card {
    border: 2px solid #E2E8F0;
    border-radius: 10px;
    padding: 12px 10px;
    cursor: pointer;
    background: #FFFFFF;
    transition: all 0.2s ease;
    text-align: center;
    position: relative;
  }
  .preset-card:hover {
    border-color: #99D5D5;
    transform: translateY(-2px);
  }
  .preset-card.active {
    border-color: #1E8888;
    background: #E6F4F4;
    box-shadow: 0 4px 12px rgba(30, 136, 136, 0.2);
  }
  .preset-card.active .preset-badge {
    display: block !important;
  }

  .voice-record-btn {
    background: #F1F5F9;
    border: 1px solid #CBD5E1;
    color: #475569;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.2s ease;
  }
  .voice-record-btn:hover {
    background: #E2E8F0;
    color: #0F172A;
  }
  .voice-record-btn.recording {
    background: #FEE2E2 !important;
    border-color: #EF4444 !important;
    color: #DC2626 !important;
    animation: voicePulse 1.2s infinite ease-in-out;
  }

  @keyframes voicePulse {
    0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5); }
    70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
    100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
  }

  .preview-section-wrapper {
    position: relative;
    transition: outline 0.2s ease;
  }
  .preview-section-wrapper:hover {
    outline: 2px dashed #1E8888;
    outline-offset: -2px;
  }
  .section-edit-trigger {
    display: none;
    position: absolute;
    top: 8px;
    right: 8px;
    background: #0F2B2B;
    color: #CCFBF1;
    border: 1px solid rgba(255,255,255,0.3);
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 6px;
    cursor: pointer;
    z-index: 20;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
  }
  .preview-section-wrapper:hover .section-edit-trigger {
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }
</style>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
  <div class="page-title-group">
    <div style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 4px;">
      <span style="font-size: 24px;">🎨</span>
      <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #0F172A;">Estudio Modular de Campañas con IA</h1>
    </div>
    <p class="page-subtitle" style="margin: 0; color: #64748B; font-size: 13.5px;">
      Presets modulares estilo Brevo, generador de imágenes IA por prompt o voz (🎙️) y edición sección por sección
    </p>
  </div>
  <div class="header-actions">
    <a href="{{ route('campaigns.index') }}" class="btn btn-secondary btn-sm" style="font-weight: 700;">
      ← Volver a Campañas
    </a>
  </div>
</div>

<div style="display: grid; grid-template-columns: 1.15fr 1fr; gap: 24px; align-items: start;">
  
  <!-- LEFT COLUMN: CONTROLS & MODULAR BUILDER -->
  <div class="table-card" style="padding: 24px;">

    <!-- PASO 1: SELECCIONAR PRESET DE ESTRUCTURA MODULAR (20 PRESETS B2B) -->
    <div class="form-group" style="margin-bottom: 22px; background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px; padding: 18px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
        <div>
          <label class="form-label" style="font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 8px; font-size: 13.5px;">
            <span>📐</span> 1. Estructura y Preset B2B
            <span style="font-size: 11px; background: #1E8888; color: white; padding: 2px 7px; border-radius: 10px; font-weight: 700;">20 Disponibles</span>
          </label>
          <div style="font-size: 11.5px; color: #64748B; margin-top: 2px;">
            Preset activo: <strong id="active_preset_name_display" style="color: #1E8888;">{{ $initialPreset['name'] ?? 'Clásica B2B' }}</strong> ({{ $initialPreset['category'] ?? 'B2B & Clínicas' }})
          </div>
        </div>
        
        <div style="display: flex; gap: 6px;">
          <button type="button" class="btn btn-secondary btn-sm" onclick="scrollPresetsCarousel(-1)" title="Anterior preset" style="padding: 5px 9px;">◀</button>
          <button type="button" class="btn btn-secondary btn-sm" onclick="scrollPresetsCarousel(1)" title="Siguiente preset" style="padding: 5px 9px;">▶</button>
          <button type="button" class="btn btn-secondary btn-sm" onclick="openPresetsModal()" style="font-weight: 700; color: #1E8888; border-color: #1E8888; padding: 5px 10px; font-size: 11.5px; background: white;">
            <span>📚</span> Ver los 20 Presets
          </button>
        </div>
      </div>

      <!-- CAROUSEL TRACK CON LOS 20 PRESETS -->
      <div id="presets_carousel_track" style="display: flex; gap: 10px; overflow-x: auto; scroll-behavior: smooth; padding-bottom: 8px; scrollbar-width: thin;">
        @foreach($allPresets as $p)
          <div class="preset-card {{ ($selectedPresetId ?? 'clasica') === $p['id'] ? 'active' : '' }}" 
               id="preset_card_{{ $p['id'] }}" 
               onclick="selectPreset('{{ $p['id'] }}')"
               style="flex: 0 0 155px; min-width: 155px; padding: 12px 10px; border: 2px solid {{ ($selectedPresetId ?? 'clasica') === $p['id'] ? '#1E8888' : '#E2E8F0' }}; border-radius: 8px; background: white; cursor: pointer; text-align: center; position: relative; transition: all 0.2s ease;">
            <div style="font-size: 22px; margin-bottom: 4px;">{{ $p['icon'] }}</div>
            <strong style="font-size: 11.5px; color: #0F172A; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $p['name'] }}">{{ $p['name'] }}</strong>
            <span style="font-size: 9.5px; color: #64748B; display: block; line-height: 1.2; margin-top: 2px;">{{ $p['category'] }}</span>
            <span class="preset-badge" style="display: {{ ($selectedPresetId ?? 'clasica') === $p['id'] ? 'block' : 'none' }}; position: absolute; top: 4px; right: 4px; background: #1E8888; color: white; font-size: 8px; font-weight: 800; padding: 1px 4px; border-radius: 4px;">ACTIVO</span>
          </div>
        @endforeach
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; padding-top: 8px; border-top: 1px solid #E2E8F0; flex-wrap: wrap; gap: 6px;">
        <span style="font-size: 11px; color: #64748B;" id="preset_desc_display">{{ $initialPreset['description'] ?? '' }}</span>
        <button type="button" class="btn btn-sm btn-secondary" onclick="applyPresetTexts()" style="font-size: 10.5px; padding: 3px 8px; white-space: nowrap; color: #0F766E; font-weight: 700;">
          ⚡ Aplicar textos del Preset
        </button>
      </div>
    </div>

    <!-- PASO 2: DESTINATARIOS CRM & MOTOR IA -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 22px;">
      <!-- DESTINATARIOS -->
      <div>
        <label class="form-label" style="font-weight: 700; font-size: 12px; color: #0F172A;">
          👥 Audiencia CRM
        </label>
        <select id="group_id" class="form-control" onchange="updateTargetCount(this)" style="font-size: 12.5px;">
          <option value="0" data-count="{{ \App\Models\Client::count() }}">Todos ({{ \App\Models\Client::count() }} contactos)</option>
          @foreach($groups as $g)
            <option value="{{ $g->id }}" data-count="{{ $g->clients_count }}" {{ $selectedGroupId == $g->id ? 'selected' : '' }}>
              {{ $g->name }} ({{ $g->clients_count }})
            </option>
          @endforeach
        </select>
      </div>

      <!-- MOTOR IA -->
      <div>
        <label class="form-label" style="font-weight: 700; font-size: 12px; color: #0F172A;">
          🧠 Motor de IA
        </label>
        <select id="ai_provider_select" class="form-control" style="font-size: 12.5px;">
          @foreach($aiProviders as $key => $prov)
            <option value="{{ $key }}" {{ $activeAiProvider === $key ? 'selected' : '' }}>
              {{ $prov['icon'] }} {{ $prov['name'] }}
            </option>
          @endforeach
        </select>
      </div>
    </div>

    <!-- PASO 3: FOTOS INSTITUCIONALES VS GENERADOR IA POR PROMPT O VOZ -->
    <div class="form-group" style="margin-bottom: 22px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 16px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <label class="form-label" style="font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 6px;">
          <span>📸</span> 3. Imagen de Cabecera (Hero Banner)
        </label>
        
        <!-- PESTAÑAS: STOCK VS GENERADOR IA -->
        <div style="display: inline-flex; background: #E2E8F0; border-radius: 6px; padding: 2px;">
          <button type="button" id="tab_btn_stock" onclick="switchImageTab('stock')" style="border: none; background: white; color: #0F172A; font-weight: 700; font-size: 11px; padding: 4px 10px; border-radius: 4px; cursor: pointer;">
            Fotos Suitable
          </button>
          <button type="button" id="tab_btn_ai" onclick="switchImageTab('ai')" style="border: none; background: transparent; color: #64748B; font-weight: 700; font-size: 11px; padding: 4px 10px; border-radius: 4px; cursor: pointer;">
            ✨ Crear con IA (FLUX)
          </button>
        </div>
      </div>

      <!-- TAB 1: STOCK FOTOS SUITABLE -->
      <div id="image_tab_stock">
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
          <div class="hero-img-card active" id="card_img_equipo" onclick="selectHeroImage('hero-grupo-clinico.jpg', 'card_img_equipo')" style="border: 2px solid #1E8888; border-radius: 8px; overflow: hidden; cursor: pointer; background: white; box-shadow: 0 2px 6px rgba(30,136,136,0.2);">
            <div style="height: 64px; overflow: hidden; background: #0F172A; position: relative;">
              <img src="{{ asset('images/hero-grupo-clinico.jpg') }}" alt="Equipo Clínico" style="width: 100%; height: 100%; object-fit: cover;">
              <span class="img-badge" style="position: absolute; top: 4px; right: 4px; background: #1E8888; color: white; font-size: 8px; font-weight: 800; padding: 1px 4px; border-radius: 3px;">✓ ACTIVO</span>
            </div>
            <div style="padding: 5px; text-align: center; font-size: 11px; font-weight: 700; color: #0F172A;">
              👨‍⚕️ Equipo Clínico
            </div>
          </div>

          <div class="hero-img-card" id="card_img_tela" onclick="selectHeroImage('tela-antifluidos-macro.jpg', 'card_img_tela')" style="border: 2px solid #E2E8F0; border-radius: 8px; overflow: hidden; cursor: pointer; background: white;">
            <div style="height: 64px; overflow: hidden; background: #0F172A; position: relative;">
              <img src="{{ asset('images/tela-antifluidos-macro.jpg') }}" alt="Tela Antifluido" style="width: 100%; height: 100%; object-fit: cover;">
              <span class="img-badge" style="display: none; position: absolute; top: 4px; right: 4px; background: #1E8888; color: white; font-size: 8px; font-weight: 800; padding: 1px 4px; border-radius: 3px;">✓ ACTIVO</span>
            </div>
            <div style="padding: 5px; text-align: center; font-size: 11px; font-weight: 700; color: #0F172A;">
              🛡️ Tela Antifluido
            </div>
          </div>

          <div class="hero-img-card" id="card_img_tallaje" onclick="selectHeroImage('servicio-tallaje-terreno.jpg', 'card_img_tallaje')" style="border: 2px solid #E2E8F0; border-radius: 8px; overflow: hidden; cursor: pointer; background: white;">
            <div style="height: 64px; overflow: hidden; background: #0F172A; position: relative;">
              <img src="{{ asset('images/servicio-tallaje-terreno.jpg') }}" alt="Tallaje en Terreno" style="width: 100%; height: 100%; object-fit: cover;">
              <span class="img-badge" style="display: none; position: absolute; top: 4px; right: 4px; background: #1E8888; color: white; font-size: 8px; font-weight: 800; padding: 1px 4px; border-radius: 3px;">✓ ACTIVO</span>
            </div>
            <div style="padding: 5px; text-align: center; font-size: 11px; font-weight: 700; color: #0F172A;">
              📏 Tallaje en Terreno
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: GENERADOR IA (FLUX) CON PROMPT Y VOZ -->
      <div id="image_tab_ai" style="display: none;">
        <div style="background: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 8px; padding: 12px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <span style="font-size: 11.5px; font-weight: 800; color: #0F172A;">
              ✨ Describe la imagen deseada (o dítala por voz):
            </span>
            <button type="button" class="voice-record-btn" id="btn_voice_img" onclick="toggleVoiceRecord('ai_image_prompt', 'btn_voice_img')" title="Grabar tu voz">
              <span>🎙️</span>
              <span id="voice_status_img">Dictar</span>
            </button>
          </div>

          <div style="display: flex; gap: 8px;">
            <input type="text" id="ai_image_prompt" class="form-control" placeholder="Ej: Doctores en pabellón con scrubs azul marino Suitable..." style="font-size: 12px;">
            <button type="button" class="btn btn-primary btn-sm" id="btn_run_gen_img" onclick="generateAiImageAction()" style="white-space: nowrap; font-weight: 700; background: #1E8888; display: inline-flex; align-items: center; gap: 6px;">
              <span>🎨</span>
              <span>Generar Imagen</span>
            </button>
          </div>

          <!-- CHIPS RAPIDOS -->
          <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px;">
            <button type="button" class="badge" onclick="setImgPromptChip('Equipo médico de clínica dental con scrubs antifluido Suitable')" style="border: none; background: #F1F5F9; color: #475569; cursor: pointer;">🦷 Odontología</button>
            <button type="button" class="badge" onclick="setImgPromptChip('Enfermeras y médicos en sala de operaciones con uniformes azul quirúrgico')" style="border: none; background: #F1F5F9; color: #475569; cursor: pointer;">🏥 Quirófano</button>
            <button type="button" class="badge" onclick="setImgPromptChip('Detalle macro de tela impermeable repelente a fluidos con gotas de agua')" style="border: none; background: #F1F5F9; color: #475569; cursor: pointer;">💧 Micro Tela</button>
            <button type="button" class="badge" onclick="setImgPromptChip('Percheros móviles con uniformes clínicos en sala de clínica privada')" style="border: none; background: #F1F5F9; color: #475569; cursor: pointer;">📏 Percheros Terreno</button>
          </div>
        </div>
      </div>

    </div>

    <!-- PASO 4: ASISTENTE IA PARA GENERAR PROPUESTA COMPLETA (CON VOZ 🎙️) -->
    <div style="margin-bottom: 22px; background: linear-gradient(135deg, #E6F4F4 0%, #CCFBF1 100%); border: 1px solid #99D5D5; border-radius: 10px; padding: 16px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
        <span style="font-weight: 800; font-size: 13px; color: #0F766E;">
          ✨ Asistente IA para Propuesta Comercial
        </span>
        <button type="button" class="voice-record-btn" id="btn_voice_prompt" onclick="toggleVoiceRecord('ai_custom_focus', 'btn_voice_prompt')" title="Grabar instrucción por voz">
          <span>🎙️</span>
          <span id="voice_status_prompt">Hablar</span>
        </button>
      </div>

      <div style="display: flex; gap: 8px;">
        <input type="text" id="ai_custom_focus" class="form-control" placeholder="Escribe o dicta tu idea (ej: Clínicas dentales, temporada de invierno...)" style="font-size: 12px; background: white;">
        <button type="button" class="btn btn-primary btn-sm" id="btn_ai_align" onclick="requestAiProposal()" style="font-weight: 700; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: linear-gradient(135deg, #1E8888 0%, #0F5E68 100%);">
          <span>✨</span>
          <span>Armar Propuesta</span>
        </button>
      </div>
    </div>

    <!-- PASO 5: FORMULARIO DE EDICIÓN MODULAR -->
    <form id="campaign_form" onsubmit="saveCampaign(event)">
      <input type="hidden" id="preset_template" name="preset_template" value="clasica">
      <input type="hidden" id="hero_image" name="hero_image" value="hero-grupo-clinico.jpg">

      <!-- SECCIÓN A: METADATOS BÁSICOS -->
      <div style="border-bottom: 1px solid #E2E8F0; padding-bottom: 14px; margin-bottom: 14px;">
        <div class="form-group" style="margin-bottom: 12px;">
          <label class="form-label" style="font-weight: 700; font-size: 12px;">Nombre de la Campaña *</label>
          <input type="text" id="campaign_name" name="name" class="form-control" value="Campaña Clínicas B2B - {{ date('d/m/Y') }}" required>
        </div>

        <div class="form-group" style="margin-bottom: 12px;">
          <label class="form-label" style="font-weight: 700; font-size: 12px;">Asunto del Correo (Subject) *</label>
          <input type="text" id="campaign_subject" name="subject" class="form-control" value="[Convenio Clínico] Uniformes médicos con 6 meses de garantía directa de fábrica y servicio de tallaje" required oninput="updatePreview()">
        </div>

        <div class="form-group" style="margin-bottom: 6px;">
          <label class="form-label" style="font-weight: 700; font-size: 12px;">Preheader (Bandeja de entrada) *</label>
          <input type="text" id="campaign_preheader" name="preheader" class="form-control" value="Somos fabricantes chilenos de uniformes clínicos antifluidos. Servicio exclusivo de tallaje en su clínica y 6 meses de garantía." oninput="updatePreview()">
        </div>
      </div>

      <!-- SECCIÓN B: HERO BANNER & TEXTO -->
      <div id="editor_section_hero" style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 14px; margin-bottom: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <strong style="font-size: 12px; color: #0F172A;">📌 Cabecera (Hero Section)</strong>
          <button type="button" class="btn btn-secondary btn-sm" onclick="openSectionRewriteModal('hero')" style="padding: 2px 8px; font-size: 11px;">✨ Mejorar Hero con IA</button>
        </div>
        <div class="form-group" style="margin-bottom: 8px;">
          <input type="text" id="hero_title" name="hero_title" class="form-control" value="Equipe a su personal de salud con la confianza de fabricantes directos" placeholder="Titular principal" oninput="updatePreview()">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <textarea id="hero_desc" name="hero_desc" class="form-control" rows="3" placeholder="Texto descriptivo persuasivo" oninput="updatePreview()">En Suitable confeccionamos uniformes clínicos de alto rendimiento con telas antifluidos de última generación y respaldo integral de fábrica. Llevamos muestras en vivo a su clínica para que su equipo pruebe tallas antes de comprar.</textarea>
        </div>
      </div>

      <!-- SECCIÓN C: DINÁMICA SEGÚN PRESET (PILARES / SPLIT / SHOWCASE / TALLAJE) -->
      <div id="editor_section_modular" style="background: #F1F5F9; border-radius: 8px; padding: 14px; margin-bottom: 20px;">
        
        <!-- MODULAR BLOCK: PILARES (Preset Clásica) -->
        <div id="modular_edit_clasica">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <strong style="font-size: 12px; color: #475569; text-transform: uppercase;">🏛️ 3 Pilares en Columnas</strong>
            <button type="button" class="btn btn-secondary btn-sm" onclick="openSectionRewriteModal('pillars')" style="padding: 2px 8px; font-size: 11px;">✨ Mejorar con IA</button>
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
            <div>
              <input type="text" id="pilar1_title" name="pilar1_title" class="form-control" value="Fabricación 100% Chilena" style="font-size: 11.5px; margin-bottom: 4px;" oninput="updatePreview()">
              <textarea id="pilar1_desc" name="pilar1_desc" class="form-control" rows="2" style="font-size: 11px;" oninput="updatePreview()">Confección directa sin intermediarios con garantía.</textarea>
            </div>
            <div>
              <input type="text" id="pilar2_title" name="pilar2_title" class="form-control" value="Telas Flex Antifluidos" style="font-size: 11.5px; margin-bottom: 4px;" oninput="updatePreview()">
              <textarea id="pilar2_desc" name="pilar2_desc" class="form-control" rows="2" style="font-size: 11px;" oninput="updatePreview()">Elasticidad 4-Way y bioseguridad contra fluidos.</textarea>
            </div>
            <div>
              <input type="text" id="pilar3_title" name="pilar3_title" class="form-control" value="Garantía 6 Meses y Tallaje" style="font-size: 11.5px; margin-bottom: 4px;" oninput="updatePreview()">
              <textarea id="pilar3_desc" name="pilar3_desc" class="form-control" rows="2" style="font-size: 11px;" oninput="updatePreview()">Percheros en la clínica para probar tallas.</textarea>
            </div>
          </div>
        </div>

        <!-- MODULAR BLOCK: SPLIT 50/50 -->
        <div id="modular_edit_split" style="display: none;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <strong style="font-size: 12px; color: #475569; text-transform: uppercase;">⚖️ Bloque Split (Foto Izquierda / Texto Derecha)</strong>
            <button type="button" class="btn btn-secondary btn-sm" onclick="openSectionRewriteModal('split')" style="padding: 2px 8px; font-size: 11px;">✨ Mejorar con IA</button>
          </div>
          <div class="form-group" style="margin-bottom: 8px;">
            <label style="font-size: 11px; font-weight: 700;">Título del Bloque Split</label>
            <input type="text" id="split_title" class="form-control" value="Ingeniería Textil para Personal de Salud" style="font-size: 12px;" oninput="updatePreview()">
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label style="font-size: 11px; font-weight: 700;">Contenido Explicativo &amp; Beneficios</label>
            <textarea id="split_content" name="split_content" class="form-control" rows="3" style="font-size: 11.5px;" oninput="updatePreview()">Nuestras telas Flex 4-Way combinan máxima libertad de movimiento con tecnología hidrófuga que repele fluidos. Servicio de personalización con bordados y pruebas de calce presenciales.</textarea>
          </div>
        </div>

        <!-- MODULAR BLOCK: SHOWCASE 2x2 -->
        <div id="modular_edit_showcase" style="display: none;">
          <strong style="font-size: 12px; color: #475569; text-transform: uppercase; display: block; margin-bottom: 8px;">🖼️ Catálogo 4 Productos en Grilla</strong>
          <p style="font-size: 11.5px; color: #64748B; margin: 0;">Se incluirán en el correo las 4 líneas principales: Scrubs Médicos, Línea Antifluidos, Tallaje en Clínica y Delantales Clínicos con sus fotos y botones de cotización.</p>
        </div>

        <!-- MODULAR BLOCK: TALLAJE 1-2-3 -->
        <div id="modular_edit_tallaje" style="display: none;">
          <strong style="font-size: 12px; color: #475569; text-transform: uppercase; display: block; margin-bottom: 8px;">📏 Servicio en Terreno: 3 Fases</strong>
          <p style="font-size: 11.5px; color: #64748B; margin: 0;">El correo presentará de forma gráfica las 3 etapas: 1) Coordinación de visita, 2) Prueba con percheros móviles XS-3XL en la clínica, y 3) Confección y despacho garantizado.</p>
        </div>

      </div>

      <!-- BOTÓN GUARDAR -->
      <div style="display: flex; gap: 12px;">
        <button type="submit" id="btn_save_campaign" class="btn btn-primary" style="flex: 1; padding: 12px; font-size: 14px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
          <span>💾</span>
          <span>Guardar Campaña Modular en Laravel</span>
        </button>
      </div>
    </form>

  </div>

  <!-- RIGHT COLUMN: LIVE HTML EMAIL PREVIEW STUDIO -->
  <div class="table-card" style="padding: 24px; position: sticky; top: 20px;">
    
    <!-- PREVIEW TOOLBAR -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
      <div>
        <h3 style="font-size: 15px; font-weight: 800; margin: 0; color: #0F172A;">Vista Previa en Vivo</h3>
        <p style="margin: 2px 0 0 0; font-size: 11.5px; color: #64748B;">Pasa el mouse sobre cualquier sección para editarla (✏️)</p>
      </div>
      
      <!-- DEVICE TOGGLES -->
      <div style="display: inline-flex; background: #F1F5F9; border-radius: 6px; padding: 2px; border: 1px solid #E2E8F0;">
        <button type="button" id="btn_mode_desktop" onclick="setPreviewDevice('desktop')" style="border: none; background: white; color: #0F172A; font-weight: 700; font-size: 11.5px; padding: 5px 10px; border-radius: 4px; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
          🖥️ Desktop (600px)
        </button>
        <button type="button" id="btn_mode_mobile" onclick="setPreviewDevice('mobile')" style="border: none; background: transparent; color: #64748B; font-weight: 700; font-size: 11.5px; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
          📱 Móvil (385px)
        </button>
      </div>
    </div>

    <!-- SUBJECT SIMULATOR BAR -->
    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 8px 12px; margin-bottom: 12px; font-size: 12px;">
      <div style="color: #64748B; font-size: 10px; text-transform: uppercase; font-weight: 700; margin-bottom: 2px;">Bandeja de entrada:</div>
      <div style="font-weight: 800; color: #0F172A;" id="prev_subject_text">
        [Convenio Clínico] Uniformes médicos con 6 meses de garantía directa de fábrica y servicio de tallaje
      </div>
      <div style="color: #64748B; font-size: 11px;" id="prev_preheader_text">
        Somos fabricantes chilenos de uniformes clínicos antifluidos. Servicio exclusivo de tallaje en su clínica y 6 meses de garantía.
      </div>
    </div>

    <!-- PREVIEW FRAME CONTAINER -->
    <div id="preview_wrapper" style="width: 100%; max-width: 600px; margin: 0 auto; border: 1px solid #CBD5E1; border-radius: 8px; overflow: hidden; background: #FFFFFF; box-shadow: 0 6px 18px rgba(0,0,0,0.08); transition: max-width 0.3s ease;">
      
      <!-- TOP MIRROR LINK BAR -->
      <div style="background: #0F2B2B; color: #A4B8B8; font-size: 10px; padding: 6px 14px; display: flex; justify-content: space-between; align-items: center;">
        <span>Convenios &amp; Ventas Corporativas | Suitable Chile</span>
        <span style="color: #6CD2D2;">Ver en el navegador</span>
      </div>

      <!-- HEADER / LOGO (SECTION 1) -->
      <div class="preview-section-wrapper" style="background: #1E8888; padding: 18px 24px; text-align: center;">
        <button type="button" class="section-edit-trigger" onclick="focusEditorSection('hero')">✏️ Editar Cabecera</button>
        <img src="https://suitable.cl/wp-content/uploads/2025/04/logo_blanco-350x128.png" alt="Suitable" style="height: 34px;">
        <div style="color: #CCFBF1; font-size: 10.5px; margin-top: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
          Uniformes Clínicos de Alta Gama • Confección Nacional
        </div>
      </div>

      <!-- HERO PHOTO (SECTION 2) -->
      <div class="preview-section-wrapper" style="background: #0F172A; line-height: 0; text-align: center; max-height: 220px; overflow: hidden; position: relative;">
        <button type="button" class="section-edit-trigger" onclick="switchImageTab('ai')">🎨 Cambiar Foto con IA</button>
        <img id="prev_hero_img" src="{{ asset('images/hero-grupo-clinico.jpg') }}" alt="Hero Banner" style="width: 100%; height: auto; display: block; object-fit: cover;">
      </div>

      <!-- HERO CONTENT (SECTION 3) -->
      <div class="preview-section-wrapper" style="background: linear-gradient(135deg, #1E8888 0%, #146161 100%); padding: 30px 24px; text-align: center; color: white;">
        <button type="button" class="section-edit-trigger" onclick="focusEditorSection('hero')">✏️ Editar Hero</button>
        <div style="display: inline-block; background: rgba(0,0,0,0.2); padding: 4px 12px; border-radius: 20px; font-size: 10px; font-weight: 800; letter-spacing: 0.8px; margin-bottom: 12px; border: 1px solid rgba(255,255,255,0.25);">
          ✦ PROPUESTA CORPORATIVA INSTITUCIONAL
        </div>
        <h2 id="prev_hero_title" style="font-size: 20px; font-weight: 800; color: #FFFFFF; margin: 0 0 12px 0; line-height: 1.3;">
          Equipe a su personal de salud con la confianza de fabricantes directos
        </h2>
        <p id="prev_hero_desc" style="font-size: 13px; color: #E6F7F7; line-height: 1.6; margin: 0 auto 20px auto; max-width: 480px;">
          En Suitable confeccionamos uniformes clínicos de alto rendimiento con telas antifluidos de última generación y respaldo integral de fábrica. Llevamos muestras en vivo a su clínica para que su equipo pruebe tallas antes de comprar.
        </p>
        <a href="https://suitable.cl/clinicas-y-centros/" target="_blank" style="background: #FFFFFF; color: #146161; padding: 12px 24px; border-radius: 6px; font-size: 12.5px; font-weight: 800; text-decoration: none; display: inline-block; text-transform: uppercase; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
          Solicitar Visita de Muestras y Tallaje →
        </a>
      </div>

      <!-- PREVIEW MODULAR BLOCKS -->

      <!-- 1. PREVIEW: CLASICA (3 PILARES) -->
      <div id="prev_block_clasica" class="preview-section-wrapper" style="padding: 20px; background: #F8FAFC; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; border-top: 1px solid #E2E8F0;">
        <button type="button" class="section-edit-trigger" onclick="focusEditorSection('modular')">✏️ Editar Pilares</button>
        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 6px; padding: 10px; text-align: center;">
          <div style="font-size: 18px; margin-bottom: 2px;">🇨🇱</div>
          <strong id="prev_pilar1_title" style="font-size: 11.5px; color: #0F172A; display: block;">Fabricación 100% Chilena</strong>
          <span id="prev_pilar1_desc" style="font-size: 10.5px; color: #64748B; display: block; line-height: 1.3; margin-top: 2px;">Confección directa sin intermediarios.</span>
        </div>
        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 6px; padding: 10px; text-align: center;">
          <div style="font-size: 18px; margin-bottom: 2px;">🛡️</div>
          <strong id="prev_pilar2_title" style="font-size: 11.5px; color: #0F172A; display: block;">Telas Flex Antifluidos</strong>
          <span id="prev_pilar2_desc" style="font-size: 10.5px; color: #64748B; display: block; line-height: 1.3; margin-top: 2px;">Elasticidad 4-Way y bioseguridad.</span>
        </div>
        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 6px; padding: 10px; text-align: center;">
          <div style="font-size: 18px; margin-bottom: 2px;">📏</div>
          <strong id="prev_pilar3_title" style="font-size: 11.5px; color: #0F172A; display: block;">Garantía 6 Meses y Tallaje</strong>
          <span id="prev_pilar3_desc" style="font-size: 10.5px; color: #64748B; display: block; line-height: 1.3; margin-top: 2px;">Percheros en la clínica para probar tallas.</span>
        </div>
      </div>

      <!-- 2. PREVIEW: SPLIT 50/50 -->
      <div id="prev_block_split" class="preview-section-wrapper" style="display: none; padding: 24px 20px; background: #FFFFFF; border-top: 1px solid #E2E8F0;">
        <button type="button" class="section-edit-trigger" onclick="focusEditorSection('modular')">✏️ Editar Split</button>
        <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 16px; align-items: center;">
          <img id="prev_split_img" src="{{ asset('images/tela-antifluidos-macro.jpg') }}" alt="Detalle" style="width: 100%; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
          <div>
            <span style="display: inline-block; background: #E6F4F4; color: #146161; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 10px; margin-bottom: 6px;">✦ TECNOLOGÍA TEXTIL</span>
            <h4 id="prev_split_title" style="margin: 0 0 6px 0; font-size: 15px; color: #0F172A; font-weight: 800;">Ingeniería Textil para Personal de Salud</h4>
            <p id="prev_split_content" style="margin: 0 0 12px 0; font-size: 11.5px; line-height: 1.5; color: #475569;">
              Nuestras telas Flex 4-Way combinan máxima libertad de movimiento con tecnología hidrófuga que repele fluidos.
            </p>
            <a href="https://suitable.cl/clinicas-y-centros/" target="_blank" style="background: #1E8888; color: white; padding: 8px 14px; border-radius: 6px; font-size: 11.5px; font-weight: 700; text-decoration: none; display: inline-block;">
              Solicitar Muestra Textil →
            </a>
          </div>
        </div>
      </div>

      <!-- 3. PREVIEW: SHOWCASE 2x2 -->
      <div id="prev_block_showcase" class="preview-section-wrapper" style="display: none; padding: 20px; background: #FFFFFF; border-top: 1px solid #E2E8F0; text-align: center;">
        <button type="button" class="section-edit-trigger" onclick="focusEditorSection('modular')">✏️ Ver Catálogo</button>
        <strong style="font-size: 14px; color: #0F172A; display: block; margin-bottom: 12px;">Líneas Destacadas de Uniformes Clínicos</strong>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; text-align: left;">
          <div style="border: 1px solid #E2E8F0; border-radius: 6px; padding: 8px; background: #F8FAFC;">
            <img src="{{ asset('images/hero-grupo-clinico.jpg') }}" style="width: 100%; height: 75px; object-fit: cover; border-radius: 4px;">
            <strong style="font-size: 11px; display: block; margin-top: 4px; color: #0F172A;">Scrubs Médicos</strong>
            <span style="font-size: 10px; color: #64748B;">Flex 4-Way Antifluido</span>
          </div>
          <div style="border: 1px solid #E2E8F0; border-radius: 6px; padding: 8px; background: #F8FAFC;">
            <img src="{{ asset('images/tela-antifluidos-macro.jpg') }}" style="width: 100%; height: 75px; object-fit: cover; border-radius: 4px;">
            <strong style="font-size: 11px; display: block; margin-top: 4px; color: #0F172A;">Línea Antifluidos</strong>
            <span style="font-size: 10px; color: #64748B;">Barrera Biosegura</span>
          </div>
        </div>
      </div>

      <!-- 4. PREVIEW: TALLAJE 1-2-3 -->
      <div id="prev_block_tallaje" class="preview-section-wrapper" style="display: none; padding: 20px; background: #FFFFFF; border-top: 1px solid #E2E8F0; text-align: center;">
        <button type="button" class="section-edit-trigger" onclick="focusEditorSection('modular')">✏️ Ver Pasos</button>
        <span style="background: #E6F4F4; color: #146161; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 10px;">PROCESO EN TERRENO</span>
        <h4 style="margin: 6px 0 12px 0; font-size: 14px; color: #0F172A; font-weight: 800;">3 Pasos para Renovar su Dotación</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px;">
          <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 8px;">
            <div style="width: 24px; height: 24px; line-height: 24px; border-radius: 50%; background: #1E8888; color: white; font-weight: 800; font-size: 11px; margin: 0 auto 4px auto;">1</div>
            <strong style="font-size: 10.5px; display: block; color: #0F172A;">Coordinamos</strong>
            <span style="font-size: 9.5px; color: #64748B;">Día y hora clínica.</span>
          </div>
          <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 8px;">
            <div style="width: 24px; height: 24px; line-height: 24px; border-radius: 50%; background: #1E8888; color: white; font-weight: 800; font-size: 11px; margin: 0 auto 4px auto;">2</div>
            <strong style="font-size: 10.5px; display: block; color: #0F172A;">Percheros</strong>
            <span style="font-size: 9.5px; color: #64748B;">Pruebas XS a 3XL.</span>
          </div>
          <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 8px;">
            <div style="width: 24px; height: 24px; line-height: 24px; border-radius: 50%; background: #1E8888; color: white; font-weight: 800; font-size: 11px; margin: 0 auto 4px auto;">3</div>
            <strong style="font-size: 10.5px; display: block; color: #0F172A;">Entrega</strong>
            <span style="font-size: 9.5px; color: #64748B;">6 meses garantía.</span>
          </div>
        </div>
      </div>

      <!-- FOOTER -->
      <div style="padding: 16px; text-align: center; background: #0F172A; color: #94A3B8; font-size: 10.5px; line-height: 1.5;">
        <div>Suitable Uniformes Clínicos • Confección Nacional • Santiago, Chile</div>
        <div style="margin-top: 2px; color: #64748B;">Garantía extendida de 6 meses directa de fábrica.</div>
      </div>

    </div>
  </div>

</div>

<!-- MODAL: ASISTENTE IA PARA REESCRIBIR SECCIÓN ESPECÍFICA -->
<div id="section_rewrite_modal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
  <div style="background: white; border-radius: 12px; width: 100%; max-width: 500px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0F172A; display: flex; align-items: center; gap: 8px;">
        <span>✨</span> Reescribir Sección con IA: <span id="rewrite_section_title" style="color: #1E8888;">Hero</span>
      </h3>
      <button type="button" onclick="closeSectionRewriteModal()" style="border: none; background: transparent; font-size: 20px; cursor: pointer; color: #94A3B8;">✕</button>
    </div>

    <div style="margin-bottom: 14px;">
      <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
        <span>¿Qué cambio deseas realizar en esta sección?</span>
        <button type="button" class="voice-record-btn" id="btn_voice_modal" onclick="toggleVoiceRecord('rewrite_instruction_input', 'btn_voice_modal')">
          <span>🎙️</span> <span>Dictar</span>
        </button>
      </label>
      <textarea id="rewrite_instruction_input" class="form-control" rows="3" placeholder="Ej: Haz este titular más contundente para jefes de adquisiciones de clínicas privadas, enfatizando la garantía..."></textarea>
    </div>

    <div style="display: flex; gap: 10px; justify-content: flex-end;">
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeSectionRewriteModal()">Cancelar</button>
      <button type="button" class="btn btn-primary btn-sm" id="btn_apply_rewrite" onclick="applySectionRewrite()" style="font-weight: 700; background: #1E8888;">
        ✨ Aplicar Mejora a la Sección
      </button>
    </div>
  </div>
</div>

<!-- MODAL: CATÁLOGO DE 20 PRESETS EN CUADRÍCULA -->
<div id="modal_presets_picker" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.75); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 20px;">
  <div style="background: white; border-radius: 14px; width: 100%; max-width: 980px; max-height: 88vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.3);">
    <div style="padding: 16px 22px; background: #0F172A; color: white; display: flex; justify-content: space-between; align-items: center;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 22px;">📚</span>
        <div>
          <strong style="font-size: 16px;">Catálogo de 20 Presets de Correo B2B Suitable</strong>
          <p style="font-size: 11.5px; color: #94A3B8; margin: 0;">Selecciona cualquier plantilla para cargar su diseño y textos recomendados</p>
        </div>
      </div>
      <button type="button" onclick="closePresetsModal()" style="background: none; border: none; color: #94A3B8; font-size: 22px; cursor: pointer; padding: 0 8px;">✕</button>
    </div>

    <!-- SEARCH & FILTER TABS -->
    <div style="padding: 12px 20px; background: #F8FAFC; border-bottom: 1px solid #E2E8F0; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
      <input type="text" id="modalPresetSearch" oninput="filterModalPresets()" placeholder="🔍 Buscar entre los 20 presets..." class="form-control" style="font-size: 12.5px; max-width: 280px; padding: 7px 12px;">
      <div style="display: flex; gap: 6px; overflow-x: auto; flex: 1;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="filterModalCategory('all', this)" style="font-size: 11px; padding: 5px 10px; background: #E6F4F4; color: #115E59; font-weight: 700;">Todos (20)</button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="filterModalCategory('clinicas', this)" style="font-size: 11px; padding: 5px 10px;">🏥 Clínicas</button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="filterModalCategory('tallaje', this)" style="font-size: 11px; padding: 5px 10px;">📏 Tallaje</button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="filterModalCategory('especialidades', this)" style="font-size: 11px; padding: 5px 10px;">🦷 Especialidades</button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="filterModalCategory('ofertas', this)" style="font-size: 11px; padding: 5px 10px;">⚡ Ofertas</button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="filterModalCategory('finanzas', this)" style="font-size: 11px; padding: 5px 10px;">📊 Finanzas</button>
      </div>
    </div>

    <!-- PRESETS GRID -->
    <div style="flex: 1; overflow-y: auto; padding: 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 14px;" id="modal_presets_grid">
      @foreach($allPresets as $p)
        <div class="modal-preset-item" 
             data-category="{{ $p['category_slug'] ?? 'clinicas' }}" 
             data-text="{{ strtolower($p['name'] . ' ' . $p['description'] . ' ' . $p['subject']) }}"
             onclick="selectPreset('{{ $p['id'] }}'); applyPresetTexts(); closePresetsModal();"
             style="border: 1.5px solid #E2E8F0; border-radius: 10px; padding: 14px; background: white; cursor: pointer; transition: all 0.2s ease; display: flex; flex-direction: column; justify-content: space-between;">
          <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
              <span style="font-size: 10px; font-weight: 800; background: #F1F5F9; color: #475569; padding: 2px 6px; border-radius: 4px; text-transform: uppercase;">{{ $p['category'] }}</span>
              <span style="font-size: 10px; font-weight: 700; background: #E6F4F4; color: #115E59; padding: 2px 6px; border-radius: 4px;">{{ $p['badge'] }}</span>
            </div>
            <strong style="font-size: 13.5px; color: #0F172A; display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
              <span>{{ $p['icon'] }}</span>
              <span>{{ $p['name'] }}</span>
            </strong>
            <p style="font-size: 11.5px; color: #64748B; line-height: 1.4; margin: 0 0 10px 0;">{{ $p['description'] }}</p>
          </div>
          <div style="font-size: 10.5px; color: #1E8888; font-weight: 700; display: flex; align-items: center; justify-content: space-between; padding-top: 8px; border-top: 1px solid #F1F5F9;">
            <span>Tipo: {{ ucfirst($p['layout_type']) }}</span>
            <span style="background: #1E8888; color: white; padding: 3px 8px; border-radius: 4px;">Cargar Preset →</span>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</div>

<script>
  let activeSectionForRewrite = 'hero';
  let voiceRecognition = null;
  let isRecordingVoice = false;
  let activeVoiceTargetInput = null;
  let activeVoiceButton = null;

  const ALL_PRESETS = @json($allPresets);

  const imageAssets = {
    'hero-grupo-clinico.jpg': "{{ asset('images/hero-grupo-clinico.jpg') }}",
    'tela-antifluidos-macro.jpg': "{{ asset('images/tela-antifluidos-macro.jpg') }}",
    'servicio-tallaje-terreno.jpg': "{{ asset('images/servicio-tallaje-terreno.jpg') }}"
  };

  // --- 1. PRESET SELECTOR (20 PRESETS B2B) ---
  function selectPreset(presetKey) {
    document.getElementById('preset_template').value = presetKey;
    const preset = ALL_PRESETS.find(p => p.id === presetKey) || ALL_PRESETS[0];

    // Actualizar labels en la UI
    const nameDisplay = document.getElementById('active_preset_name_display');
    if (nameDisplay) nameDisplay.innerText = preset.name;
    const descDisplay = document.getElementById('preset_desc_display');
    if (descDisplay) descDisplay.innerText = preset.description;

    // Actualizar estilo en el carousel
    document.querySelectorAll('#presets_carousel_track .preset-card').forEach(card => {
      const isCur = (card.id === 'preset_card_' + presetKey);
      card.classList.toggle('active', isCur);
      card.style.borderColor = isCur ? '#1E8888' : '#E2E8F0';
      const badge = card.querySelector('.preset-badge');
      if (badge) badge.style.display = isCur ? 'block' : 'none';
      if (isCur) {
        card.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
      }
    });

    const layoutType = preset.layout_type || 'pillars';

    // Mostrar formulario modular correspondiente
    document.getElementById('modular_edit_clasica').style.display = (layoutType === 'pillars') ? 'block' : 'none';
    document.getElementById('modular_edit_split').style.display = (layoutType === 'split') ? 'block' : 'none';
    document.getElementById('modular_edit_showcase').style.display = (layoutType === 'showcase') ? 'block' : 'none';
    document.getElementById('modular_edit_tallaje').style.display = (layoutType === 'process') ? 'block' : 'none';

    // Mostrar bloque correspondiente en la vista previa en vivo
    document.getElementById('prev_block_clasica').style.display = (layoutType === 'pillars') ? 'grid' : 'none';
    document.getElementById('prev_block_split').style.display = (layoutType === 'split') ? 'block' : 'none';
    document.getElementById('prev_block_showcase').style.display = (layoutType === 'showcase') ? 'block' : 'none';
    document.getElementById('prev_block_tallaje').style.display = (layoutType === 'process') ? 'block' : 'none';

    updatePreview();
  }

  function applyPresetTexts() {
    const presetKey = document.getElementById('preset_template').value;
    const preset = ALL_PRESETS.find(p => p.id === presetKey) || ALL_PRESETS[0];

    if (preset.subject) document.getElementById('subject').value = preset.subject;
    if (preset.preheader) document.getElementById('preheader').value = preset.preheader;
    if (preset.hero_title) document.getElementById('hero_title').value = preset.hero_title;
    if (preset.hero_desc) document.getElementById('hero_desc').value = preset.hero_desc;

    if (preset.pilar1_title) document.getElementById('pilar1_title').value = preset.pilar1_title;
    if (preset.pilar1_desc) document.getElementById('pilar1_desc').value = preset.pilar1_desc;
    if (preset.pilar2_title) document.getElementById('pilar2_title').value = preset.pilar2_title;
    if (preset.pilar2_desc) document.getElementById('pilar2_desc').value = preset.pilar2_desc;
    if (preset.pilar3_title) document.getElementById('pilar3_title').value = preset.pilar3_title;
    if (preset.pilar3_desc) document.getElementById('pilar3_desc').value = preset.pilar3_desc;

    if (preset.hero_image) {
      const imgKey = preset.hero_image.includes('tela') ? 'card_img_tela' : (preset.hero_image.includes('tallaje') ? 'card_img_tallaje' : 'card_img_equipo');
      selectHeroImage(preset.hero_image, imgKey);
    }

    updatePreview();
    showToast(`✓ Textos recomendados del preset "${preset.name}" aplicados`, 'success');
  }

  function scrollPresetsCarousel(dir) {
    const track = document.getElementById('presets_carousel_track');
    if (track) {
      track.scrollBy({ left: dir * 260, behavior: 'smooth' });
    }
  }

  function openPresetsModal() {
    document.getElementById('modal_presets_picker').style.display = 'flex';
  }

  function closePresetsModal() {
    document.getElementById('modal_presets_picker').style.display = 'none';
  }

  function filterModalCategory(category, btn) {
    document.querySelectorAll('#modal_presets_picker .btn-secondary').forEach(b => {
      b.style.background = '';
      b.style.color = '';
    });
    if (btn) {
      btn.style.background = '#E6F4F4';
      btn.style.color = '#115E59';
      btn.style.fontWeight = '700';
    }

    const items = document.querySelectorAll('.modal-preset-item');
    items.forEach(it => {
      const cat = it.getAttribute('data-category');
      it.style.display = (category === 'all' || cat === category) ? 'flex' : 'none';
    });
  }

  function filterModalPresets() {
    const q = document.getElementById('modalPresetSearch').value.toLowerCase().trim();
    const items = document.querySelectorAll('.modal-preset-item');
    items.forEach(it => {
      const text = it.getAttribute('data-text');
      it.style.display = (!q || text.includes(q)) ? 'flex' : 'none';
    });
  }

  // --- 2. TABS DE IMÁGENES: STOCK VS IA ---
  function switchImageTab(tab) {
    const tabStock = document.getElementById('image_tab_stock');
    const tabAi = document.getElementById('image_tab_ai');
    const btnStock = document.getElementById('tab_btn_stock');
    const btnAi = document.getElementById('tab_btn_ai');

    if (tab === 'ai') {
      tabStock.style.display = 'none';
      tabAi.style.display = 'block';
      btnAi.style.background = 'white';
      btnAi.style.color = '#0F172A';
      btnStock.style.background = 'transparent';
      btnStock.style.color = '#64748B';
    } else {
      tabStock.style.display = 'block';
      tabAi.style.display = 'none';
      btnStock.style.background = 'white';
      btnStock.style.color = '#0F172A';
      btnAi.style.background = 'transparent';
      btnAi.style.color = '#64748B';
    }
  }

  function setImgPromptChip(text) {
    document.getElementById('ai_image_prompt').value = text;
  }

  function selectHeroImage(imgName, cardId) {
    document.getElementById('hero_image').value = imgName;

    // Actualizar estilos de cards
    document.querySelectorAll('.hero-img-card').forEach(c => {
      c.style.borderColor = '#E2E8F0';
      c.style.boxShadow = 'none';
      const b = c.querySelector('.img-badge');
      if (b) b.style.display = 'none';
    });

    const activeCard = document.getElementById(cardId);
    if (activeCard) {
      activeCard.style.borderColor = '#1E8888';
      activeCard.style.boxShadow = '0 2px 8px rgba(30,136,136,0.25)';
      const badge = activeCard.querySelector('.img-badge');
      if (badge) badge.style.display = 'block';
    }

    // Actualizar preview en vivo
    const prevImg = document.getElementById('prev_hero_img');
    const targetUrl = imageAssets[imgName] || ("{{ asset('images') }}/" + imgName);
    if (prevImg) {
      prevImg.src = targetUrl;
    }
  }

  // --- 3. GENERADOR DE IMÁGENES FLUX CON IA ---
  async function generateAiImageAction() {
    const promptInput = document.getElementById('ai_image_prompt');
    const prompt = promptInput.value.trim();
    if (!prompt) {
      showToast('Escribe o dicta por voz qué imagen deseas generar', 'warning');
      return;
    }

    const btn = document.getElementById('btn_run_gen_img');
    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span> <span>Creando imagen...</span>';

    showToast('Generando fotografía médica con FLUX AI...', 'info');

    try {
      const token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
      const res = await fetch("{{ route('campaigns.ai_generate_image') }}", {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json', 
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ prompt: prompt, section: 'hero' })
      });

      const data = await res.json();
      if (data.success && data.image_url) {
        showToast('✓ Imagen fotográfica generada e insertada', 'success');
        
        // Guardar nombre y registrar en imageAssets
        document.getElementById('hero_image').value = data.image_name;
        imageAssets[data.image_name] = data.image_url;

        // Actualizar preview inmediatamente
        const prevImg = document.getElementById('prev_hero_img');
        if (prevImg) prevImg.src = data.image_url;

        // Actualizar card visual
        const stockContainer = document.querySelector('#image_tab_stock > div');
        if (stockContainer) {
          const newCardId = 'card_img_' + Date.now();
          const cardHtml = `
            <div class="hero-img-card active" id="${newCardId}" onclick="selectHeroImage('${data.image_name}', '${newCardId}')" style="border: 2px solid #1E8888; border-radius: 8px; overflow: hidden; cursor: pointer; background: white; box-shadow: 0 2px 6px rgba(30,136,136,0.2);">
              <div style="height: 64px; overflow: hidden; background: #0F172A; position: relative;">
                <img src="${data.image_url}" alt="Generada con IA" style="width: 100%; height: 100%; object-fit: cover;">
                <span class="img-badge" style="position: absolute; top: 4px; right: 4px; background: #1E8888; color: white; font-size: 8px; font-weight: 800; padding: 1px 4px; border-radius: 3px;">✓ IA</span>
              </div>
              <div style="padding: 5px; text-align: center; font-size: 11px; font-weight: 700; color: #0F172A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                ✨ Creada con IA
              </div>
            </div>`;
          stockContainer.insertAdjacentHTML('afterbegin', cardHtml);
          switchImageTab('stock');
        }
      } else {
        showToast(data.error || 'No se pudo generar la imagen con IA', 'error');
      }
    } catch (e) {
      showToast('Error de comunicación con el motor de imágenes', 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<span>🎨</span> <span>Generar Imagen</span>';
    }
  }

  // --- 4. DICTADO POR VOZ (SPEECH RECOGNITION 🎙️) ---
  function initVoiceRecognition() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) return null;

    const rec = new SpeechRecognition();
    rec.lang = 'es-CL';
    rec.continuous = true;
    rec.interimResults = true;

    rec.onstart = function() {
      isRecordingVoice = true;
      if (activeVoiceButton) {
        activeVoiceButton.classList.add('recording');
        const txt = activeVoiceButton.querySelector('span:last-child');
        if (txt) txt.innerText = 'Grabando...';
      }
      showToast('🎙️ Micrófono activo: habla ahora...', 'info');
    };

    rec.onresult = function(event) {
      let interim = '';
      let final = '';
      for (let i = event.resultIndex; i < event.results.length; ++i) {
        if (event.results[i].isFinal) {
          final += event.results[i][0].transcript;
        } else {
          interim += event.results[i][0].transcript;
        }
      }
      if (activeVoiceTargetInput) {
        const base = activeVoiceTargetInput.dataset.preVoice || '';
        activeVoiceTargetInput.value = (base + ' ' + final + ' ' + interim).trim();
        updatePreview();
      }
    };

    rec.onerror = function(event) {
      stopVoiceRecording();
      showToast('Micrófono detenido o sin permisos', 'warning');
    };

    rec.onend = function() {
      stopVoiceRecording();
    };

    return rec;
  }

  function toggleVoiceRecord(inputId, btnId) {
    const input = document.getElementById(inputId);
    const btn = document.getElementById(btnId);
    activeVoiceTargetInput = input;
    activeVoiceButton = btn;

    if (input) input.dataset.preVoice = input.value;

    if (isRecordingVoice) {
      stopVoiceRecording();
    } else {
      if (!voiceRecognition) {
        voiceRecognition = initVoiceRecognition();
      }
      if (voiceRecognition) {
        try {
          voiceRecognition.start();
        } catch (e) {
          console.log(e);
        }
      } else {
        showToast('El dictado por voz requiere Google Chrome o Microsoft Edge', 'warning');
      }
    }
  }

  function stopVoiceRecording() {
    isRecordingVoice = false;
    if (voiceRecognition) {
      try { voiceRecognition.stop(); } catch(e) {}
    }
    document.querySelectorAll('.voice-record-btn').forEach(b => {
      b.classList.remove('recording');
      const txt = b.querySelector('span:last-child');
      if (txt) {
        if (b.id.includes('img')) txt.innerText = 'Dictar';
        else if (b.id.includes('modal')) txt.innerText = 'Dictar';
        else txt.innerText = 'Hablar';
      }
    });
  }

  // --- 5. ACTUALIZACIÓN EN VIVO (LIVE PREVIEW) ---
  function updatePreview() {
    document.getElementById('prev_subject_text').innerText = document.getElementById('campaign_subject').value;
    document.getElementById('prev_preheader_text').innerText = document.getElementById('campaign_preheader').value;
    document.getElementById('prev_hero_title').innerText = document.getElementById('hero_title').value;
    document.getElementById('prev_hero_desc').innerText = document.getElementById('hero_desc').value;

    // Pilares
    document.getElementById('prev_pilar1_title').innerText = document.getElementById('pilar1_title').value;
    document.getElementById('prev_pilar1_desc').innerText = document.getElementById('pilar1_desc').value;
    document.getElementById('prev_pilar2_title').innerText = document.getElementById('pilar2_title').value;
    document.getElementById('prev_pilar2_desc').innerText = document.getElementById('pilar2_desc').value;
    document.getElementById('prev_pilar3_title').innerText = document.getElementById('pilar3_title').value;
    document.getElementById('prev_pilar3_desc').innerText = document.getElementById('pilar3_desc').value;

    // Split
    const splitTitleInput = document.getElementById('split_title');
    const splitContentInput = document.getElementById('split_content');
    if (splitTitleInput) document.getElementById('prev_split_title').innerText = splitTitleInput.value;
    if (splitContentInput) document.getElementById('prev_split_content').innerText = splitContentInput.value;
  }

  function setPreviewDevice(mode) {
    const wrap = document.getElementById('preview_wrapper');
    const btnD = document.getElementById('btn_mode_desktop');
    const btnM = document.getElementById('btn_mode_mobile');

    if (mode === 'mobile') {
      wrap.style.maxWidth = '385px';
      btnM.style.background = 'white';
      btnM.style.color = '#0F172A';
      btnM.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
      btnD.style.background = 'transparent';
      btnD.style.color = '#64748B';
      btnD.style.boxShadow = 'none';
    } else {
      wrap.style.maxWidth = '600px';
      btnD.style.background = 'white';
      btnD.style.color = '#0F172A';
      btnD.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
      btnM.style.background = 'transparent';
      btnM.style.color = '#64748B';
      btnM.style.boxShadow = 'none';
    }
  }

  function focusEditorSection(sectionKey) {
    const el = document.getElementById('editor_section_' + sectionKey);
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      el.style.boxShadow = '0 0 0 3px rgba(30,136,136,0.4)';
      setTimeout(() => el.style.boxShadow = 'none', 1400);
    }
  }

  // --- 6. MODAL DE REESCRITURA DE SECCIÓN ESPECÍFICA ---
  function openSectionRewriteModal(sectionKey) {
    activeSectionForRewrite = sectionKey;
    document.getElementById('rewrite_section_title').innerText = sectionKey.toUpperCase();
    document.getElementById('rewrite_instruction_input').value = '';
    document.getElementById('section_rewrite_modal').style.display = 'flex';
  }
  function closeSectionRewriteModal() {
    stopVoiceRecording();
    document.getElementById('section_rewrite_modal').style.display = 'none';
  }

  async function applySectionRewrite() {
    const instruction = document.getElementById('rewrite_instruction_input').value.trim();
    if (!instruction) {
      showToast('Ingresa una indicación para reescribir la sección', 'warning');
      return;
    }

    const btn = document.getElementById('btn_apply_rewrite');
    btn.disabled = true;
    btn.innerText = 'Reescribiendo con IA...';

    let currentText = '';
    if (activeSectionForRewrite === 'hero') {
      currentText = document.getElementById('hero_title').value + ' ' + document.getElementById('hero_desc').value;
    } else if (activeSectionForRewrite === 'split') {
      currentText = document.getElementById('split_content').value;
    } else {
      currentText = document.getElementById('pilar1_desc').value;
    }

    try {
      const token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
      const prov = document.getElementById('ai_provider_select').value;

      const res = await fetch("{{ route('campaigns.ai_rewrite_section') }}", {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json', 
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ 
          section: activeSectionForRewrite, 
          instruction: instruction, 
          current_text: currentText,
          provider: prov
        })
      });

      const data = await res.json();
      if (data.success && data.data) {
        if (activeSectionForRewrite === 'hero') {
          if (data.data.title) document.getElementById('hero_title').value = data.data.title;
          if (data.data.desc) document.getElementById('hero_desc').value = data.data.desc;
        } else if (activeSectionForRewrite === 'split') {
          if (data.data.title) document.getElementById('split_title').value = data.data.title;
          if (data.data.desc) document.getElementById('split_content').value = data.data.desc;
        } else {
          if (data.data.title) document.getElementById('pilar1_title').value = data.data.title;
          if (data.data.desc) document.getElementById('pilar1_desc').value = data.data.desc;
        }
        updatePreview();
        showToast('✓ Sección actualizada con éxito', 'success');
        closeSectionRewriteModal();
      } else {
        showToast('No se pudo reescribir la sección', 'error');
      }
    } catch (e) {
      showToast('Error de comunicación con el motor de IA', 'error');
    } finally {
      btn.disabled = false;
      btn.innerText = '✨ Aplicar Mejora a la Sección';
    }
  }

  // --- 7. ASISTENTE GENERAL DE IA ---
  async function requestAiProposal() {
    stopVoiceRecording();
    const prov = document.getElementById('ai_provider_select').value;
    const heroImg = document.getElementById('hero_image').value;
    const customFocus = document.getElementById('ai_custom_focus').value;

    const btn = document.getElementById('btn_ai_align');
    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span> <span>Armando...</span>';

    showToast('Consultando propuesta completa a ' + prov.toUpperCase() + '...', 'info');

    try {
      const token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
      const res = await fetch("{{ route('campaigns.ai_generate') }}", {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json', 
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ 
          provider: prov, 
          campaign_type: 'clinicas_b2b', 
          hero_image: heroImg,
          prompt: customFocus 
        })
      });

      const data = await res.json();
      if (data.success && data.draft) {
        const d = data.draft;
        if (d.subject) document.getElementById('campaign_subject').value = d.subject;
        if (d.preheader) document.getElementById('campaign_preheader').value = d.preheader;
        if (d.hero_title) document.getElementById('hero_title').value = d.hero_title;
        if (d.hero_desc) document.getElementById('hero_desc').value = d.hero_desc;

        if (d.pilar1_title) document.getElementById('pilar1_title').value = d.pilar1_title;
        if (d.pilar1_desc) document.getElementById('pilar1_desc').value = d.pilar1_desc;
        if (d.pilar2_title) document.getElementById('pilar2_title').value = d.pilar2_title;
        if (d.pilar2_desc) document.getElementById('pilar2_desc').value = d.pilar2_desc;
        if (d.pilar3_title) document.getElementById('pilar3_title').value = d.pilar3_title;
        if (d.pilar3_desc) document.getElementById('pilar3_desc').value = d.pilar3_desc;

        const splitContent = document.getElementById('split_content');
        if (splitContent && d.hero_desc) splitContent.value = d.hero_desc;

        updatePreview();
        showToast('✓ Propuesta estructurada cargada en la plantilla', 'success');
      } else {
        showToast(data.error || 'No se pudo generar la propuesta con IA', 'warning');
      }
    } catch (e) {
      showToast('Error al conectar con el servicio de IA', 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<span>✨</span> <span>Armar Propuesta</span>';
    }
  }

  // --- 8. GUARDAR CAMPAÑA ---
  async function saveCampaign(e) {
    e.preventDefault();
    stopVoiceRecording();

    const btn = document.getElementById('btn_save_campaign');
    btn.disabled = true;
    btn.innerText = 'Guardando campaña modular...';

    const token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';

    const payload = {
      name: document.getElementById('campaign_name').value,
      group_id: document.getElementById('group_id').value,
      preset_template: document.getElementById('preset_template').value,
      subject: document.getElementById('campaign_subject').value,
      preheader: document.getElementById('campaign_preheader').value,
      hero_title: document.getElementById('hero_title').value,
      hero_desc: document.getElementById('hero_desc').value,
      hero_image: document.getElementById('hero_image').value,
      pilar1_title: document.getElementById('pilar1_title').value,
      pilar1_desc: document.getElementById('pilar1_desc').value,
      pilar2_title: document.getElementById('pilar2_title').value,
      pilar2_desc: document.getElementById('pilar2_desc').value,
      pilar3_title: document.getElementById('pilar3_title').value,
      pilar3_desc: document.getElementById('pilar3_desc').value,
      split_content: document.getElementById('split_content') ? document.getElementById('split_content').value : '',
      ai_provider: document.getElementById('ai_provider_select').value,
      ai_prompt: document.getElementById('ai_custom_focus').value || 'Generada en estudio modular',
      status: 'borrador'
    };

    try {
      const res = await fetch("{{ route('campaigns.store') }}", {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json', 
          'X-CSRF-TOKEN': token, 
          'Accept': 'application/json' 
        },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        showToast('✓ Campaña modular guardada exitosamente en Laravel', 'success');
        setTimeout(() => location.href = "{{ route('campaigns.index') }}", 700);
      } else {
        showToast(data.error || 'Error al guardar la campaña', 'error');
      }
    } catch (e) {
      showToast('Error al guardar campaña', 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<span>💾</span> <span>Guardar Campaña Modular en Laravel</span>';
    }
  }

  function updateTargetCount(sel) {
    const opt = sel.options[sel.selectedIndex];
  }
</script>
@endsection
