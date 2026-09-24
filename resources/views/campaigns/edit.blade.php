@extends('layouts.app')

@section('title', 'Editar Campaña #' . $campaign->id . ' | Suitable')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
  <div class="page-title-group">
    <div style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 4px;">
      <span style="font-size: 22px;">✏️</span>
      <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #0F172A;">
        Editar Campaña: <span style="color: #1E8888;">{{ $campaign->name }}</span>
      </h1>
      <span class="badge {{ $campaign->status === 'enviada' ? 'badge-emerald' : 'badge-blue' }}" style="font-size: 11px;">
        {{ ucfirst($campaign->status) }}
      </span>
    </div>
    <p class="page-subtitle" style="margin: 0; color: #64748B; font-size: 13.5px;">
      Modifique los textos, el asunto, la imagen de portada y los pilares estratégicos con previsualización en vivo
    </p>
  </div>
  <div class="header-actions" style="display: flex; gap: 10px;">
    <a href="{{ route('campaigns.preview_campaign', $campaign->id) }}" class="btn btn-secondary btn-sm" style="font-weight: 700;">
      👁️ Ver Renderizado Completo
    </a>
    <a href="{{ route('campaigns.index') }}" class="btn btn-secondary btn-sm" style="font-weight: 700;">
      ← Volver a Campañas
    </a>
  </div>
</div>

<div style="display: grid; grid-template-columns: 1.15fr 1fr; gap: 24px; align-items: start;">
  
  <!-- LEFT COLUMN: CONTROLS & EDIT FORM -->
  <div class="table-card" style="padding: 24px;">
    
    <!-- 1. TARGET GROUP -->
    <div class="form-group" style="margin-bottom: 20px;">
      <label class="form-label" style="font-weight: 800; color: #0F172A; display: flex; align-items: center; gap: 6px;">
        <span>👥</span> 1. Segmento de Destinatarios (CRM)
      </label>
      <select id="group_id" class="form-control" onchange="updateTargetCount(this)" style="font-size: 13.5px;">
        <option value="0" data-count="{{ \App\Models\Client::count() }}" {{ empty($campaign->group_id) ? 'selected' : '' }}>
          -- Toda la base institucional ({{ \App\Models\Client::count() }} contactos) --
        </option>
        @foreach($groups as $g)
          <option value="{{ $g->id }}" data-count="{{ $g->clients_count }}" {{ $campaign->group_id == $g->id ? 'selected' : '' }}>
            {{ $g->name }} ({{ $g->clients_count }} contactos)
          </option>
        @endforeach
      </select>
      <div style="margin-top: 6px; font-size: 12px; color: #1E8888; font-weight: 700;">
        🎯 Audiencia actual: <span id="target_display">{{ $targetCount }}</span> instituciones de salud
      </div>
    </div>

    <!-- 2. AI PROVIDER SELECTOR -->
    <div class="form-group" style="margin-bottom: 20px;">
      <label class="form-label" style="font-weight: 800; color: #0F172A; display: flex; align-items: center; gap: 6px;">
        <span>🧠</span> 2. Motor de Inteligencia Artificial para Asistencia
      </label>
      <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
        @foreach($aiProviders as $key => $prov)
          <label id="provider_card_{{ $key }}" style="border: 2px solid {{ $activeAiProvider === $key ? '#1E8888' : '#E2E8F0' }}; background: {{ $activeAiProvider === $key ? '#E6F4F4' : '#FFFFFF' }}; border-radius: 8px; padding: 10px 6px; text-align: center; cursor: pointer; transition: all 0.2s ease;">
            <input type="radio" name="ai_provider" value="{{ $key }}" {{ $activeAiProvider === $key ? 'checked' : '' }} onchange="selectProviderUI('{{ $key }}')" style="display: none;">
            <div style="font-size: 18px; margin-bottom: 2px;">{{ $prov['icon'] }}</div>
            <strong style="display: block; font-size: 12px; color: #0F172A;">{{ $prov['name'] }}</strong>
            <span style="font-size: 10px; color: #64748B;">{{ $prov['badge'] }}</span>
          </label>
        @endforeach
      </div>
    </div>

    <!-- 3. HERO IMAGE SELECTOR DRAWER -->
    @php
      $currentHero = $campaign->hero_image ?: 'hero-grupo-clinico.jpg';
    @endphp
    <div class="form-group" style="margin-bottom: 22px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 16px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <label class="form-label" style="font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 6px;">
          <span>📸</span> 3. Imagen de Cabecera (Hero Banner)
        </label>
        <span id="selected_img_label" style="font-size: 11px; font-weight: 700; color: #1E8888; background: #E6F4F4; padding: 2px 8px; border-radius: 10px;">
          {{ $currentHero }}
        </span>
      </div>

      <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;" id="hero_image_cards">
        <!-- CARD 1: EQUIPO CLÍNICO -->
        <div class="hero-img-card {{ $currentHero === 'hero-grupo-clinico.jpg' ? 'active' : '' }}" id="card_img_equipo" onclick="selectHeroImage('hero-grupo-clinico.jpg', 'card_img_equipo')" style="border: 2px solid {{ $currentHero === 'hero-grupo-clinico.jpg' ? '#1E8888' : '#E2E8F0' }}; border-radius: 8px; overflow: hidden; cursor: pointer; background: white; box-shadow: {{ $currentHero === 'hero-grupo-clinico.jpg' ? '0 2px 6px rgba(30,136,136,0.2)' : 'none' }}; transition: all 0.2s ease;">
          <div style="height: 72px; overflow: hidden; background: #0F172A; position: relative;">
            <img src="{{ asset('images/hero-grupo-clinico.jpg') }}" alt="Equipo Clínico" style="width: 100%; height: 100%; object-fit: cover;">
            <span class="img-badge" style="display: {{ $currentHero === 'hero-grupo-clinico.jpg' ? 'block' : 'none' }}; position: absolute; top: 4px; right: 4px; background: #1E8888; color: white; font-size: 9px; font-weight: 800; padding: 1px 5px; border-radius: 4px;">✓ ACTIVO</span>
          </div>
          <div style="padding: 6px; text-align: center; font-size: 11px; font-weight: 700; color: #0F172A;">
            👨‍⚕️ Equipo Clínico
          </div>
        </div>

        <!-- CARD 2: TELA ANTIFLUIDO -->
        <div class="hero-img-card {{ $currentHero === 'tela-antifluidos-macro.jpg' ? 'active' : '' }}" id="card_img_tela" onclick="selectHeroImage('tela-antifluidos-macro.jpg', 'card_img_tela')" style="border: 2px solid {{ $currentHero === 'tela-antifluidos-macro.jpg' ? '#1E8888' : '#E2E8F0' }}; border-radius: 8px; overflow: hidden; cursor: pointer; background: white; box-shadow: {{ $currentHero === 'tela-antifluidos-macro.jpg' ? '0 2px 6px rgba(30,136,136,0.2)' : 'none' }}; transition: all 0.2s ease;">
          <div style="height: 72px; overflow: hidden; background: #0F172A; position: relative;">
            <img src="{{ asset('images/tela-antifluidos-macro.jpg') }}" alt="Tela Antifluido" style="width: 100%; height: 100%; object-fit: cover;">
            <span class="img-badge" style="display: {{ $currentHero === 'tela-antifluidos-macro.jpg' ? 'block' : 'none' }}; position: absolute; top: 4px; right: 4px; background: #1E8888; color: white; font-size: 9px; font-weight: 800; padding: 1px 5px; border-radius: 4px;">✓ ACTIVO</span>
          </div>
          <div style="padding: 6px; text-align: center; font-size: 11px; font-weight: 700; color: #0F172A;">
            🛡️ Tela Antifluido
          </div>
        </div>

        <!-- CARD 3: TALLAJE EN TERRENO -->
        <div class="hero-img-card {{ $currentHero === 'servicio-tallaje-terreno.jpg' ? 'active' : '' }}" id="card_img_tallaje" onclick="selectHeroImage('servicio-tallaje-terreno.jpg', 'card_img_tallaje')" style="border: 2px solid {{ $currentHero === 'servicio-tallaje-terreno.jpg' ? '#1E8888' : '#E2E8F0' }}; border-radius: 8px; overflow: hidden; cursor: pointer; background: white; box-shadow: {{ $currentHero === 'servicio-tallaje-terreno.jpg' ? '0 2px 6px rgba(30,136,136,0.2)' : 'none' }}; transition: all 0.2s ease;">
          <div style="height: 72px; overflow: hidden; background: #0F172A; position: relative;">
            <img src="{{ asset('images/servicio-tallaje-terreno.jpg') }}" alt="Tallaje en Terreno" style="width: 100%; height: 100%; object-fit: cover;">
            <span class="img-badge" style="display: {{ $currentHero === 'servicio-tallaje-terreno.jpg' ? 'block' : 'none' }}; position: absolute; top: 4px; right: 4px; background: #1E8888; color: white; font-size: 9px; font-weight: 800; padding: 1px 5px; border-radius: 4px;">✓ ACTIVO</span>
          </div>
          <div style="padding: 6px; text-align: center; font-size: 11px; font-weight: 700; color: #0F172A;">
            📏 Tallaje en Terreno
          </div>
        </div>
      </div>

      <!-- GEMINI ALIGNMENT ACTION -->
      <div style="margin-top: 12px; display: flex; gap: 8px;">
        <input type="text" id="ai_custom_focus" class="form-control" placeholder="Instrucción adicional para rediseñar (ej: Más formal, enfoque en directores...)" style="font-size: 12px;">
        <button type="button" class="btn btn-primary btn-sm" id="btn_ai_align" onclick="requestAiProposal()" style="font-weight: 700; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: linear-gradient(135deg, #1E8888 0%, #0F5E68 100%);">
          <span>✨</span>
          <span>Pedir a IA propuesta para esta imagen</span>
        </button>
      </div>
    </div>

    <!-- 4. CAMPAIGN DETAILS FORM -->
    <form id="edit_campaign_form" onsubmit="updateCampaign(event)">
      <input type="hidden" id="hero_image" name="hero_image" value="{{ $currentHero }}">

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label" style="font-weight: 700; font-size: 12.5px;">Nombre Interno de la Campaña *</label>
        <input type="text" id="campaign_name" name="name" class="form-control" value="{{ $campaign->name }}" required>
      </div>

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label" style="font-weight: 700; font-size: 12.5px;">Asunto del Correo (Subject) *</label>
        <input type="text" id="campaign_subject" name="subject" class="form-control" value="{{ $campaign->subject }}" required oninput="updatePreview()">
      </div>

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label" style="font-weight: 700; font-size: 12.5px;">Preheader (Texto visible en la bandeja de entrada) *</label>
        <input type="text" id="campaign_preheader" name="preheader" class="form-control" value="{{ $campaign->preheader }}" oninput="updatePreview()">
      </div>

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label" style="font-weight: 700; font-size: 12.5px;">Titular Principal del Correo (Hero Title)</label>
        <input type="text" id="hero_title" name="hero_title" class="form-control" value="{{ $campaign->hero_title ?: 'Equipe a su personal de salud con la confianza de fabricantes directos' }}" oninput="updatePreview()">
      </div>

      <div class="form-group" style="margin-bottom: 16px;">
        <label class="form-label" style="font-weight: 700; font-size: 12.5px;">Cuerpo de la Propuesta Comercial</label>
        <textarea id="hero_desc" name="hero_desc" class="form-control" rows="4" oninput="updatePreview()">{{ $campaign->hero_desc ?: 'En Suitable confeccionamos uniformes clínicos de alto rendimiento con telas antifluidos de última generación y respaldo integral de fábrica. Llevamos muestras en vivo a su clínica para que su equipo pruebe tallas antes de comprar.' }}</textarea>
      </div>

      <!-- 3 VALUE PILLARS -->
      <div style="background: #F1F5F9; border-radius: 8px; padding: 14px; margin-bottom: 20px;">
        <span style="font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; display: block; margin-bottom: 10px;">
          🏛️ 3 Pilares Estratégicos del Correo
        </span>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
          <div>
            <label style="font-size: 11px; font-weight: 700; color: #0F172A;">Pilar 1 (Fábrica)</label>
            <input type="text" id="pilar1_title" name="pilar1_title" class="form-control" value="{{ $campaign->pilar1_title ?: 'Fabricación 100% Chilena' }}" style="font-size: 11.5px; margin-bottom: 4px;" oninput="updatePreview()">
            <textarea id="pilar1_desc" name="pilar1_desc" class="form-control" rows="2" style="font-size: 11px;" oninput="updatePreview()">{{ $campaign->pilar1_desc ?: 'Confección directa sin intermediarios con garantía de fábrica.' }}</textarea>
          </div>
          <div>
            <label style="font-size: 11px; font-weight: 700; color: #0F172A;">Pilar 2 (Tela Flex)</label>
            <input type="text" id="pilar2_title" name="pilar2_title" class="form-control" value="{{ $campaign->pilar2_title ?: 'Telas Flex Antifluidos' }}" style="font-size: 11.5px; margin-bottom: 4px;" oninput="updatePreview()">
            <textarea id="pilar2_desc" name="pilar2_desc" class="form-control" rows="2" style="font-size: 11px;" oninput="updatePreview()">{{ $campaign->pilar2_desc ?: 'Elasticidad multidireccional 4-Way y bioseguridad contra fluidos.' }}</textarea>
          </div>
          <div>
            <label style="font-size: 11px; font-weight: 700; color: #0F172A;">Pilar 3 (Garantía &amp; Tallas)</label>
            <input type="text" id="pilar3_title" name="pilar3_title" class="form-control" value="{{ $campaign->pilar3_title ?: 'Garantía 6 Meses y Tallaje' }}" style="font-size: 11.5px; margin-bottom: 4px;" oninput="updatePreview()">
            <textarea id="pilar3_desc" name="pilar3_desc" class="form-control" rows="2" style="font-size: 11px;" oninput="updatePreview()">{{ $campaign->pilar3_desc ?: 'Percheros en la clínica para probar tallas antes de ordenar.' }}</textarea>
          </div>
        </div>
      </div>

      <div style="display: flex; gap: 12px;">
        <button type="submit" id="btn_save_changes" class="btn btn-primary" style="flex: 1; padding: 12px; font-size: 14px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
          <span>💾</span>
          <span>Actualizar Cambios en Campaña</span>
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
        <p style="margin: 2px 0 0 0; font-size: 11.5px; color: #64748B;">Renderizado en tiempo real</p>
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
      <div style="color: #64748B; font-size: 10.5px; text-transform: uppercase; font-weight: 700; margin-bottom: 2px;">Bandeja de entrada:</div>
      <div style="font-weight: 800; color: #0F172A;" id="prev_subject_text">
        {{ $campaign->subject }}
      </div>
      <div style="color: #64748B; font-size: 11px;" id="prev_preheader_text">
        {{ $campaign->preheader }}
      </div>
    </div>

    <!-- PREVIEW FRAME CONTAINER -->
    <div id="preview_wrapper" style="width: 100%; max-width: 600px; margin: 0 auto; border: 1px solid #CBD5E1; border-radius: 8px; overflow: hidden; background: #FFFFFF; box-shadow: 0 6px 18px rgba(0,0,0,0.08); transition: max-width 0.3s ease;">
      
      <!-- TOP MIRROR LINK BAR -->
      <div style="background: #0F2B2B; color: #A4B8B8; font-size: 10px; padding: 6px 14px; display: flex; justify-content: space-between; align-items: center;">
        <span>Convenios &amp; Ventas Corporativas | Suitable Chile</span>
        <span style="color: #6CD2D2;">Ver en el navegador</span>
      </div>

      <!-- EMAIL HEADER LOGO -->
      <div style="background: #1E8888; padding: 18px 24px; text-align: center;">
        <img src="https://suitable.cl/wp-content/uploads/2025/04/logo_blanco-350x128.png" alt="Suitable" style="height: 34px;">
        <div style="color: #CCFBF1; font-size: 10.5px; margin-top: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
          Uniformes Clínicos de Alta Gama • Confección Nacional
        </div>
      </div>

      <!-- HERO PHOTO -->
      <div style="background: #0F172A; line-height: 0; text-align: center; max-height: 220px; overflow: hidden;">
        <img id="prev_hero_img" src="{{ asset('images/' . $currentHero) }}" alt="Hero Banner" style="width: 100%; height: auto; display: block; object-fit: cover;">
      </div>

      <!-- HERO CONTENT -->
      <div style="background: linear-gradient(135deg, #1E8888 0%, #146161 100%); padding: 30px 24px; text-align: center; color: white;">
        <div style="display: inline-block; background: rgba(0,0,0,0.2); padding: 4px 12px; border-radius: 20px; font-size: 10px; font-weight: 800; letter-spacing: 0.8px; margin-bottom: 12px; border: 1px solid rgba(255,255,255,0.25);">
          ✦ PROPUESTA CORPORATIVA INSTITUCIONAL
        </div>
        <h2 id="prev_hero_title" style="font-size: 20px; font-weight: 800; color: #FFFFFF; margin: 0 0 12px 0; line-height: 1.3;">
          {{ $campaign->hero_title ?: 'Equipe a su personal de salud con la confianza de fabricantes directos' }}
        </h2>
        <p id="prev_hero_desc" style="font-size: 13px; color: #E6F7F7; line-height: 1.6; margin: 0 auto 20px auto; max-width: 480px;">
          {{ $campaign->hero_desc ?: 'En Suitable confeccionamos uniformes clínicos de alto rendimiento con telas antifluidos de última generación y respaldo integral de fábrica. Llevamos muestras en vivo a su clínica para que su equipo pruebe tallas antes de comprar.' }}
        </p>
        <a href="https://suitable.cl/clinicas-y-centros/" target="_blank" style="background: #FFFFFF; color: #146161; padding: 12px 24px; border-radius: 6px; font-size: 12.5px; font-weight: 800; text-decoration: none; display: inline-block; text-transform: uppercase; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
          Solicitar Visita de Muestras y Tallaje →
        </a>
      </div>

      <!-- 3 PILARS GRID -->
      <div style="padding: 20px; background: #F8FAFC; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; border-top: 1px solid #E2E8F0;">
        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 6px; padding: 10px; text-align: center;">
          <div style="font-size: 18px; margin-bottom: 2px;">🇨🇱</div>
          <strong id="prev_pilar1_title" style="font-size: 11.5px; color: #0F172A; display: block;">{{ $campaign->pilar1_title ?: 'Fabricación 100% Chilena' }}</strong>
          <span id="prev_pilar1_desc" style="font-size: 10.5px; color: #64748B; display: block; line-height: 1.3; margin-top: 2px;">{{ $campaign->pilar1_desc ?: 'Confección directa sin intermediarios con garantía.' }}</span>
        </div>
        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 6px; padding: 10px; text-align: center;">
          <div style="font-size: 18px; margin-bottom: 2px;">🛡️</div>
          <strong id="prev_pilar2_title" style="font-size: 11.5px; color: #0F172A; display: block;">{{ $campaign->pilar2_title ?: 'Telas Flex Antifluidos' }}</strong>
          <span id="prev_pilar2_desc" style="font-size: 10.5px; color: #64748B; display: block; line-height: 1.3; margin-top: 2px;">{{ $campaign->pilar2_desc ?: 'Elasticidad 4-Way y bioseguridad contra fluidos.' }}</span>
        </div>
        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 6px; padding: 10px; text-align: center;">
          <div style="font-size: 18px; margin-bottom: 2px;">📏</div>
          <strong id="prev_pilar3_title" style="font-size: 11.5px; color: #0F172A; display: block;">{{ $campaign->pilar3_title ?: 'Garantía 6 Meses y Tallaje' }}</strong>
          <span id="prev_pilar3_desc" style="font-size: 10.5px; color: #64748B; display: block; line-height: 1.3; margin-top: 2px;">{{ $campaign->pilar3_desc ?: 'Percheros en la clínica para probar tallas.' }}</span>
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

<script>
  const imageAssets = {
    'hero-grupo-clinico.jpg': "{{ asset('images/hero-grupo-clinico.jpg') }}",
    'tela-antifluidos-macro.jpg': "{{ asset('images/tela-antifluidos-macro.jpg') }}",
    'servicio-tallaje-terreno.jpg': "{{ asset('images/servicio-tallaje-terreno.jpg') }}"
  };

  function selectProviderUI(key) {
    ['gemini', 'groq', 'claude', 'openai'].forEach(p => {
      const card = document.getElementById('provider_card_' + p);
      if (card) {
        if (p === key) {
          card.style.borderColor = '#1E8888';
          card.style.background = '#E6F4F4';
        } else {
          card.style.borderColor = '#E2E8F0';
          card.style.background = '#FFFFFF';
        }
      }
    });
  }

  function selectHeroImage(imgName, cardId) {
    document.getElementById('hero_image').value = imgName;
    document.getElementById('selected_img_label').innerText = imgName;

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
    if (prevImg && imageAssets[imgName]) {
      prevImg.src = imageAssets[imgName];
    }
  }

  function updateTargetCount(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('target_display').innerText = opt.getAttribute('data-count') || '0';
  }

  function updatePreview() {
    document.getElementById('prev_subject_text').innerText = document.getElementById('campaign_subject').value;
    document.getElementById('prev_preheader_text').innerText = document.getElementById('campaign_preheader').value;
    document.getElementById('prev_hero_title').innerText = document.getElementById('hero_title').value;
    document.getElementById('prev_hero_desc').innerText = document.getElementById('hero_desc').value;

    document.getElementById('prev_pilar1_title').innerText = document.getElementById('pilar1_title').value;
    document.getElementById('prev_pilar1_desc').innerText = document.getElementById('pilar1_desc').value;
    document.getElementById('prev_pilar2_title').innerText = document.getElementById('pilar2_title').value;
    document.getElementById('prev_pilar2_desc').innerText = document.getElementById('pilar2_desc').value;
    document.getElementById('prev_pilar3_title').innerText = document.getElementById('pilar3_title').value;
    document.getElementById('prev_pilar3_desc').innerText = document.getElementById('pilar3_desc').value;
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

  async function requestAiProposal() {
    const provInput = document.querySelector('input[name="ai_provider"]:checked');
    const prov = provInput ? provInput.value : 'groq';
    const heroImg = document.getElementById('hero_image').value;
    const customFocus = document.getElementById('ai_custom_focus').value;

    const btn = document.getElementById('btn_ai_align');
    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span> <span>Generando propuesta...</span>';

    showToast('Consultando propuesta inteligente a ' + prov.toUpperCase() + '...', 'info');

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

        updatePreview();
        showToast('✓ Propuesta generada e integrada en la plantilla', 'success');
      } else {
        showToast(data.error || 'No se pudo generar la propuesta con IA', 'warning');
      }
    } catch (e) {
      showToast('Error al conectar con el servicio de IA', 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<span>✨</span> <span>Pedir a IA propuesta para esta imagen</span>';
    }
  }

  async function updateCampaign(e) {
    e.preventDefault();
    const btn = document.getElementById('btn_save_changes');
    btn.disabled = true;
    btn.innerText = 'Actualizando campaña...';

    const token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
    const provInput = document.querySelector('input[name="ai_provider"]:checked');

    const payload = {
      name: document.getElementById('campaign_name').value,
      group_id: document.getElementById('group_id').value,
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
      ai_provider: provInput ? provInput.value : '{{ $campaign->ai_provider ?: 'groq' }}',
      ai_prompt: document.getElementById('ai_custom_focus').value || 'Actualizada en estudio',
      status: '{{ $campaign->status }}'
    };

    try {
      const res = await fetch("{{ route('campaigns.update', $campaign->id) }}", {
        method: 'PUT',
        headers: { 
          'Content-Type': 'application/json', 
          'X-CSRF-TOKEN': token, 
          'Accept': 'application/json' 
        },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        showToast('✓ Campaña actualizada exitosamente', 'success');
        setTimeout(() => location.href = "{{ route('campaigns.index') }}", 700);
      } else {
        showToast(data.error || 'Error al actualizar campaña', 'error');
      }
    } catch (e) {
      showToast('Error de comunicación al actualizar campaña', 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<span>💾</span> <span>Actualizar Cambios en Campaña</span>';
    }
  }
</script>
@endsection
