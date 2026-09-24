@extends('layouts.app')

@section('title', 'Estudio Creador de Campañas con IA | Suitable')

@section('content')
<div class="page-header">
  <div class="page-title-group">
    <h1>Estudio Arquitecto de Campañas con IA</h1>
    <p class="page-subtitle">Genere propuestas coherentes y personalizadas con Gemini, Groq o Claude y previsualice en vivo</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('campaigns.index') }}" class="btn btn-secondary btn-sm">← Volver a Campañas</a>
  </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
  
  <!-- LEFT COLUMN: CONTROLS & AI GENERATOR -->
  <div class="table-card" style="padding: 24px;">
    
    <!-- 1. TARGET GROUP -->
    <div class="form-group">
      <label class="form-label" style="font-weight: 800;">1. Seleccionar Grupo / Destinatarios</label>
      <select id="group_id" class="form-control" onchange="updateTargetCount(this)">
        <option value="0" data-count="{{ \App\Models\Client::count() }}">-- Todos los clientes registrados ({{ \App\Models\Client::count() }}) --</option>
        @foreach($groups as $g)
          <option value="{{ $g->id }}" data-count="{{ $g->clients_count }}" {{ $selectedGroupId == $g->id ? 'selected' : '' }}>
            {{ $g->name }} ({{ $g->clients_count }} contactos)
          </option>
        @endforeach
      </select>
      <div style="margin-top: 6px; font-size: 12px; color: #1E8888; font-weight: 700;">
        👥 Destinatarios estimados: <span id="target_display">{{ $targetCount }}</span> instituciones
      </div>
    </div>

    <!-- 2. AI PROVIDER -->
    <div class="form-group" style="margin-top: 18px;">
      <label class="form-label" style="font-weight: 800;">2. Motor de Inteligencia Artificial</label>
      <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
        @foreach($aiProviders as $key => $prov)
          <label style="border: 1px solid #CBD5E1; border-radius: 6px; padding: 10px; text-align: center; cursor: pointer; font-size: 12px;">
            <input type="radio" name="ai_provider" value="{{ $key }}" {{ $activeAiProvider === $key ? 'checked' : '' }}>
            <div style="font-size: 16px; margin: 4px 0;">{{ $prov['icon'] }}</div>
            <strong style="display: block;">{{ $prov['name'] }}</strong>
            <span style="font-size: 10px; color: #64748B;">{{ $prov['badge'] }}</span>
          </label>
        @endforeach
      </div>
    </div>

    <!-- 3. AI ONE-CLICK PROPOSAL -->
    <div style="margin: 20px 0; background: linear-gradient(135deg, #E6F4F4 0%, #CCFBF1 100%); border: 1px solid #99D5D5; border-radius: 8px; padding: 16px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
        <span style="font-weight: 800; font-size: 13px; color: #0F766E;">✨ Generador Rápido Gemini</span>
        <span class="badge" style="background: #0F766E; color: white;">1 Clic</span>
      </div>
      <p style="font-size: 12px; color: #115E59; margin: 0 0 12px 0;">
        Genera una propuesta comercial persuasiva enfocada en los 4 pilares: Fabricación chilena, 6 meses de garantía, tecnología Flex 4-Way y tallaje presencial sin costo.
      </p>
      <button type="button" class="btn btn-primary btn-sm" style="width: 100%;" onclick="requestAiProposal()">
        ✨ Pedir a Gemini propuesta coherente para este segmento
      </button>
    </div>

    <!-- 4. CAMPAIGN DETAILS FORM -->
    <form id="campaign_form" onsubmit="saveCampaign(event)">
      <div class="form-group">
        <label class="form-label">Nombre Interno de la Campaña *</label>
        <input type="text" id="campaign_name" name="name" class="form-control" value="Campaña Clínicas B2B - {{ date('d/m/Y') }}" required>
      </div>

      <div class="form-group">
        <label class="form-label">Asunto del Correo (Subject) *</label>
        <input type="text" id="campaign_subject" name="subject" class="form-control" value="[Convenio Clínico] Uniformes médicos con 6 meses de garantía directa de fábrica y servicio de tallaje" required oninput="updatePreview()">
      </div>

      <div class="form-group">
        <label class="form-label">Preheader (Texto previo en bandeja) *</label>
        <input type="text" id="campaign_preheader" name="preheader" class="form-control" value="Somos fabricantes chilenos de uniformes clínicos antifluidos. Servicio exclusivo de tallaje en su clínica y 6 meses de garantía." oninput="updatePreview()">
      </div>

      <div class="form-group">
        <label class="form-label">Titular Principal del Correo</label>
        <input type="text" id="hero_title" class="form-control" value="Equipe a su personal de salud con la confianza de fabricantes directos" oninput="updatePreview()">
      </div>

      <div class="form-group">
        <label class="form-label">Cuerpo de la Propuesta</label>
        <textarea id="hero_desc" class="form-control" rows="4" oninput="updatePreview()">En Suitable confeccionamos uniformes clínicos de alto rendimiento con telas antifluidos de última generación y respaldo integral de fábrica. Llevamos muestras en vivo a su clínica para que su equipo pruebe tallas antes de comprar.</textarea>
      </div>

      <div style="display: flex; gap: 12px; margin-top: 20px;">
        <button type="submit" class="btn btn-primary" style="flex: 1; padding: 12px;">
          💾 Guardar Campaña en Laravel
        </button>
      </div>
    </form>

  </div>

  <!-- RIGHT COLUMN: LIVE HTML EMAIL PREVIEW -->
  <div class="table-card" style="padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <h3 style="font-size: 15px; font-weight: 800; margin: 0; color: #0F172A;">Vista Previa en Vivo del Correo</h3>
      <span class="badge" style="background:#E2E8F0; color:#475569;">Desktop / Móvil</span>
    </div>

    <!-- PREVIEW CONTAINER -->
    <div style="border: 1px solid #CBD5E1; border-radius: 8px; overflow: hidden; background: #FFFFFF; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
      
      <!-- EMAIL HEADER -->
      <div style="background: #1E8888; padding: 18px 24px; text-align: center;">
        <img src="https://suitable.cl/wp-content/uploads/2025/04/logo_blanco-350x128.png" alt="Suitable" style="height: 36px;">
        <div style="color: #CCFBF1; font-size: 11px; margin-top: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
          Uniformes Clínicos de Alta Calidad • Confección Nacional
        </div>
      </div>

      <!-- EMAIL HERO -->
      <div style="padding: 28px 24px; text-align: center; border-bottom: 1px solid #F1F5F9;">
        <h2 id="prev_hero_title" style="font-size: 20px; font-weight: 800; color: #0F172A; margin: 0 0 12px 0; line-height: 1.3;">
          Equipe a su personal de salud con la confianza de fabricantes directos
        </h2>
        <p id="prev_hero_desc" style="font-size: 13.5px; color: #475569; line-height: 1.6; margin: 0 auto 20px auto; max-width: 480px;">
          En Suitable confeccionamos uniformes clínicos de alto rendimiento con telas antifluidos de última generación y respaldo integral de fábrica. Llevamos muestras en vivo a su clínica para que su equipo pruebe tallas antes de comprar.
        </p>
        <a href="https://suitable.cl/clinicas-y-centros/" target="_blank" style="background: #1E8888; color: white; padding: 12px 24px; border-radius: 6px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-block;">
          Solicitar Visita de Muestras y Tallaje →
        </a>
      </div>

      <!-- 3 PILARS -->
      <div style="padding: 24px; background: #F8FAFC; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 6px; padding: 14px; text-align: center;">
          <div style="font-size: 20px; margin-bottom: 4px;">🇨🇱</div>
          <strong style="font-size: 12px; color: #0F172A; display: block;">Fábrica Chilena</strong>
          <span style="font-size: 11px; color: #64748B;">6 meses de garantía directa</span>
        </div>
        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 6px; padding: 14px; text-align: center;">
          <div style="font-size: 20px; margin-bottom: 4px;">📏</div>
          <strong style="font-size: 12px; color: #0F172A; display: block;">Tallaje en Clínica</strong>
          <span style="font-size: 11px; color: #64748B;">Percheros con curva XS-3XL</span>
        </div>
        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 6px; padding: 14px; text-align: center;">
          <div style="font-size: 20px; margin-bottom: 4px;">🛡️</div>
          <strong style="font-size: 12px; color: #0F172A; display: block;">Tela Flex Antifluidos</strong>
          <span style="font-size: 11px; color: #64748B;">Elasticidad 4-way y confort</span>
        </div>
      </div>

      <!-- FOOTER -->
      <div style="padding: 18px; text-align: center; background: #0F172A; color: #94A3B8; font-size: 11px;">
        <div>Suitable Uniformes Clínicos • Santiago, Chile</div>
        <div style="margin-top: 4px;">Garantía extendida de 6 meses en confección y costuras.</div>
      </div>

    </div>
  </div>

</div>

<script>
  function updateTargetCount(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('target_display').innerText = opt.getAttribute('data-count') || '0';
  }

  function updatePreview() {
    document.getElementById('prev_hero_title').innerText = document.getElementById('hero_title').value;
    document.getElementById('prev_hero_desc').innerText = document.getElementById('hero_desc').value;
  }

  async function requestAiProposal() {
    const prov = document.querySelector('input[name="ai_provider"]:checked').value;
    const token = document.querySelector('meta[name="csrf-token"]').content;
    showToast('Consultando a ' + prov.toUpperCase() + '...', 'info');

    try {
      const res = await fetch("{{ route('campaigns.ai_generate') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ provider: prov, campaign_type: 'clinicas_b2b', prompt: 'Generar propuesta' })
      });
      const data = await res.json();
      if (data.content) {
        document.getElementById('hero_desc').value = data.content;
        updatePreview();
        showToast('✓ Propuesta generada con éxito', 'success');
      }
    } catch (e) {
      showToast('Error al conectar con IA', 'error');
    }
  }

  async function saveCampaign(e) {
    e.preventDefault();
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const payload = {
      name: document.getElementById('campaign_name').value,
      group_id: document.getElementById('group_id').value,
      subject: document.getElementById('campaign_subject').value,
      preheader: document.getElementById('campaign_preheader').value,
      ai_provider: document.querySelector('input[name="ai_provider"]:checked').value,
      ai_prompt: document.getElementById('hero_desc').value,
      status: 'borrador'
    };

    try {
      const res = await fetch("{{ route('campaigns.store') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        showToast('✓ Campaña guardada en Laravel', 'success');
        setTimeout(() => location.href = "{{ route('campaigns.index') }}", 600);
      }
    } catch (e) {
      showToast('Error al guardar campaña', 'error');
    }
  }
</script>
@endsection
