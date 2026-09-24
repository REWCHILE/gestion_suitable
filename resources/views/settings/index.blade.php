@extends('layouts.app')

@section('title', 'Ajustes & Configuración | Suitable')

@push('styles')
<style>
  .api-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    transition: all 0.2s ease;
    cursor: default;
    max-width: 260px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .api-status-active {
    background-color: #ECFDF5;
    border: 1px solid #A7F3D0;
    color: #065F46;
  }
  .api-status-error {
    background-color: #FEF2F2;
    border: 1px solid #FECACA;
    color: #991B1B;
  }
  .api-status-checking {
    background-color: #FFFBEB;
    border: 1px solid #FDE68A;
    color: #92400E;
  }
  .status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
  }
  .dot-green {
    background-color: #10B981;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.3);
  }
  .dot-red {
    background-color: #EF4444;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.3);
  }
  .dot-yellow {
    background-color: #F59E0B;
    animation: pulse-dot 1.2s infinite ease-in-out;
  }
  @keyframes pulse-dot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.3; transform: scale(1.3); }
  }
  .error-hint {
    font-size: 11px;
    color: #DC2626;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
  }
</style>
@endpush

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
  <div class="page-title-group">
    <h1>Ajustes del Sistema &amp; Proveedores de IA</h1>
    <p class="page-subtitle">Configure credenciales de IA, parámetros de marca y conexión SMTP Stealth para VPS Postfix / cPanel</p>
  </div>
  <div class="header-actions" style="display: flex; gap: 10px; align-items: center;">
    <button type="button" class="btn btn-secondary btn-sm" onclick="checkAllStatuses(this)" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
      <span>🔄</span>
      <span>Verificar Conexiones</span>
    </button>
  </div>
</div>

<form id="settings_form" onsubmit="saveSettings(event)">
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
    
    <!-- LEFT: AI KEYS & BRAND -->
    <div class="table-card" style="padding: 24px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
        <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">
          🤖 Llaves de Inteligencia Artificial (API Keys)
        </h3>
        <span style="font-size: 11px; color: #64748B;">Estado en tiempo real</span>
      </div>

      <!-- 1. GOOGLE GEMINI -->
      <div class="form-group" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
          <label class="form-label" style="margin-bottom: 0; font-weight: 700;">Google Gemini API Key</label>
          <div id="badge_gemini" class="api-status-badge {{ $statuses['gemini']['status'] === 'active' ? 'api-status-active' : 'api-status-error' }}" title="{{ $statuses['gemini']['message'] }}">
            <span class="status-dot {{ $statuses['gemini']['status'] === 'active' ? 'dot-green' : 'dot-red' }}"></span>
            <span class="status-text">{{ $statuses['gemini']['label'] }}</span>
          </div>
        </div>
        <input type="password" name="gemini_api_key" id="input_gemini" class="form-control" value="{{ $settings['gemini_api_key'] ?? '' }}" placeholder="AIzaSy..." onblur="checkSingleProvider('gemini')">
        <div id="hint_gemini" class="error-hint" style="{{ $statuses['gemini']['status'] === 'active' ? 'display: none;' : '' }}">
          <span>⚠️</span> <span>{{ $statuses['gemini']['message'] }}</span>
        </div>
      </div>

      <!-- 2. GROQ -->
      <div class="form-group" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
          <label class="form-label" style="margin-bottom: 0; font-weight: 700;">Groq API Key (Llama 3.3)</label>
          <div id="badge_groq" class="api-status-badge {{ $statuses['groq']['status'] === 'active' ? 'api-status-active' : 'api-status-error' }}" title="{{ $statuses['groq']['message'] }}">
            <span class="status-dot {{ $statuses['groq']['status'] === 'active' ? 'dot-green' : 'dot-red' }}"></span>
            <span class="status-text">{{ $statuses['groq']['label'] }}</span>
          </div>
        </div>
        <input type="password" name="groq_api_key" id="input_groq" class="form-control" value="{{ $settings['groq_api_key'] ?? '' }}" placeholder="gsk_..." onblur="checkSingleProvider('groq')">
        <div id="hint_groq" class="error-hint" style="{{ $statuses['groq']['status'] === 'active' ? 'display: none;' : '' }}">
          <span>⚠️</span> <span>{{ $statuses['groq']['message'] }}</span>
        </div>
      </div>

      <!-- 3. OPENAI -->
      <div class="form-group" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
          <label class="form-label" style="margin-bottom: 0; font-weight: 700;">OpenAI API Key (ChatGPT)</label>
          <div id="badge_openai" class="api-status-badge {{ $statuses['openai']['status'] === 'active' ? 'api-status-active' : 'api-status-error' }}" title="{{ $statuses['openai']['message'] }}">
            <span class="status-dot {{ $statuses['openai']['status'] === 'active' ? 'dot-green' : 'dot-red' }}"></span>
            <span class="status-text">{{ $statuses['openai']['label'] }}</span>
          </div>
        </div>
        <input type="password" name="openai_api_key" id="input_openai" class="form-control" value="{{ $settings['openai_api_key'] ?? '' }}" placeholder="sk-..." onblur="checkSingleProvider('openai')">
        <div id="hint_openai" class="error-hint" style="{{ $statuses['openai']['status'] === 'active' ? 'display: none;' : '' }}">
          <span>⚠️</span> <span>{{ $statuses['openai']['message'] }}</span>
        </div>
      </div>

      <!-- 4. CLAUDE -->
      <div class="form-group" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
          <label class="form-label" style="margin-bottom: 0; font-weight: 700;">Anthropic Claude API Key</label>
          <div id="badge_claude" class="api-status-badge {{ $statuses['claude']['status'] === 'active' ? 'api-status-active' : 'api-status-error' }}" title="{{ $statuses['claude']['message'] }}">
            <span class="status-dot {{ $statuses['claude']['status'] === 'active' ? 'dot-green' : 'dot-red' }}"></span>
            <span class="status-text">{{ $statuses['claude']['label'] }}</span>
          </div>
        </div>
        <input type="password" name="claude_api_key" id="input_claude" class="form-control" value="{{ $settings['claude_api_key'] ?? '' }}" placeholder="sk-ant-..." onblur="checkSingleProvider('claude')">
        <div id="hint_claude" class="error-hint" style="{{ $statuses['claude']['status'] === 'active' ? 'display: none;' : '' }}">
          <span>⚠️</span> <span>{{ $statuses['claude']['message'] }}</span>
        </div>
      </div>

      <!-- PARÁMETROS DE MARCA SUITABLE -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin: 28px 0 14px 0; border-top: 1px solid #E2E8F0; padding-top: 20px;">
        <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">
          🏷️ Parámetros de Marca Suitable
        </h3>
        <button type="button" class="btn btn-sm" onclick="toggleBrandAiAssistant()" style="background: linear-gradient(135deg, #E6F4F4 0%, #CCFBF1 100%); color: #0F766E; font-weight: 700; border: 1px solid #99F6E4; display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 6px; cursor: pointer;">
          <span>✨</span>
          <span>Asistente IA de Remitente</span>
        </button>
      </div>

      <!-- ASISTENTE IA PARA REMITENTE & MARCA -->
      <div id="ai_brand_assistant_box" style="display: none; background: #F8FAFC; border: 1.5px solid #1E8888; border-radius: 8px; padding: 18px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(30,136,136,0.12);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 18px;">💡</span>
            <strong style="color: #0F172A; font-size: 13.5px;">Fórmulas de Alta Conversión B2B para Clínicas</strong>
          </div>
          <button type="button" onclick="toggleBrandAiAssistant()" style="background: none; border: none; font-size: 14px; color: #64748B; cursor: pointer; padding: 0 4px;">✕</button>
        </div>
        <p style="font-size: 12px; color: #475569; margin: 0 0 12px 0; line-height: 1.4;">
          El <strong>Nombre del Remitente</strong> define el 65% de la tasa de apertura en directores médicos y jefes de adquisiciones. Seleccione una fórmula probada para aplicarla automáticamente:
        </p>

        <!-- FOCUS CHIPS -->
        <div style="display: flex; gap: 6px; margin-bottom: 12px; flex-wrap: wrap;">
          <button type="button" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 4px 8px;" onclick="loadBrandAiSuggestions('General Clínicas')">🏥 Clínicas & Hospitales</button>
          <button type="button" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 4px 8px;" onclick="loadBrandAiSuggestions('Clínicas Dentales')">🦷 Odontología</button>
          <button type="button" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 4px 8px;" onclick="loadBrandAiSuggestions('Servicio de Tallaje en Terreno')">📏 Tallaje en Terreno</button>
          <button type="button" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 4px 8px;" onclick="loadBrandAiSuggestions('Fábrica Directa y Ahorro')">🏭 Fábrica Directa</button>
        </div>

        <!-- LISTA DE RECOMENDACIONES -->
        <div id="ai_brand_recommendations_container" style="display: flex; flex-direction: column; gap: 10px;"></div>

        <div style="display: flex; gap: 8px; margin-top: 14px;">
          <input type="text" id="ai_brand_custom_focus" class="form-control" style="font-size: 12px; padding: 7px 10px;" placeholder="Ej: Redactar para licitaciones públicas o veterinarias...">
          <button type="button" id="btn_generate_custom_brand" class="btn btn-primary btn-sm" onclick="generateCustomBrandAi()" style="white-space: nowrap; font-size: 11.5px; padding: 7px 12px;">
            ⚡ Redactar con IA
          </button>
        </div>
      </div>

      <div class="form-group" style="margin-bottom: 18px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
          <label class="form-label" style="margin-bottom: 0; font-weight: 700;">Nombre del Remitente</label>
          <button type="button" onclick="toggleBrandAiAssistant()" style="font-size: 11.5px; color: #1E8888; font-weight: 700; background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 4px;">
            <span>✨ Asistente IA</span>
          </button>
        </div>
        <input type="text" name="sender_name" id="sender_name" class="form-control" value="{{ $settings['sender_name'] ?? 'Suitable Uniformes Clínicos' }}" placeholder="Ej: Javier de Suitable | Convenios Clínicos">
        <div style="font-size: 11px; color: #64748B; margin-top: 4px;">
          Nombre visible en la bandeja de entrada de directores y adquisiciones.
        </div>
      </div>

      <div class="form-group" style="margin-bottom: 18px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
          <label class="form-label" style="margin-bottom: 0; font-weight: 700;">Correo Electrónico Remitente</label>
          <span style="font-size: 11px; color: #059669; font-weight: 600;">✓ Dominio corporativo Suitable.cl</span>
        </div>
        <input type="email" name="sender_email" id="sender_email" class="form-control" value="{{ $settings['sender_email'] ?? 'ventas@suitable.cl' }}" placeholder="convenios@suitable.cl">
      </div>

      <button type="submit" id="save_btn" class="btn btn-primary" style="width: 100%; margin-top: 14px; padding: 12px; font-size: 14px; font-weight: 700;">
        💾 Guardar Toda la Configuración
      </button>
    </div>

    <!-- RIGHT: SMTP & VPS POSTFIX -->
    <div class="table-card" style="padding: 24px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
        <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">
          🚀 Conexión SMTP / VPS Postfix Stealth
        </h3>
        <div id="badge_smtp" class="api-status-badge {{ $statuses['smtp']['status'] === 'active' ? 'api-status-active' : 'api-status-error' }}" title="{{ $statuses['smtp']['message'] }}">
          <span class="status-dot {{ $statuses['smtp']['status'] === 'active' ? 'dot-green' : 'dot-red' }}"></span>
          <span class="status-text">{{ $statuses['smtp']['label'] }}</span>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Servidor SMTP (Host)</label>
        <input type="text" name="smtp_host" id="smtp_host" class="form-control" value="{{ $settings['smtp_host'] ?? '127.0.0.1' }}" placeholder="mail.suitable.cl o IP del VPS" onblur="checkSingleProvider('smtp')">
        <div id="hint_smtp" class="error-hint" style="{{ $statuses['smtp']['status'] === 'active' ? 'display: none;' : '' }}">
          <span>⚠️</span> <span>{{ $statuses['smtp']['message'] }}</span>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Puerto SMTP</label>
        <input type="text" name="smtp_port" id="smtp_port" class="form-control" value="{{ $settings['smtp_port'] ?? '587' }}" onblur="checkSingleProvider('smtp')">
      </div>

      <div class="form-group">
        <label class="form-label">Usuario SMTP</label>
        <input type="text" name="smtp_user" class="form-control" value="{{ $settings['smtp_user'] ?? 'ventas@suitable.cl' }}">
      </div>

      <div class="form-group">
        <label class="form-label">Contraseña SMTP</label>
        <input type="password" name="smtp_pass" class="form-control" value="{{ $settings['smtp_pass'] ?? '' }}" placeholder="••••••••">
      </div>

      <div class="form-group">
        <label class="form-label">Encriptación</label>
        <select name="smtp_encryption" class="form-control">
          <option value="tls" {{ ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS (Puerto 587)</option>
          <option value="ssl" {{ ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL (Puerto 465)</option>
          <option value="none" {{ ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' }}>Sin Encriptación (Local / Relay)</option>
        </select>
      </div>

      <!-- TEST CONNECTION -->
      <div style="margin-top: 20px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 16px;">
        <label class="form-label" style="font-weight: 700; color: #0F172A;">Prueba Rápida de Envío Stealth</label>
        <p style="font-size: 11.5px; color: #64748B; margin: 0 0 10px 0;">Envía un correo de comprobación de cabeceras para validar la entrega en buzón.</p>
        <div style="display: flex; gap: 8px;">
          <input type="email" id="test_recipient" class="form-control" placeholder="correo@destino.cl" value="ventas@suitable.cl">
          <button type="button" class="btn btn-secondary btn-sm" onclick="testSmtp(this)">Probar Envío</button>
        </div>
      </div>
    </div>

  </div>
</form>

<script>
  function renderBadge(key, info) {
    const badge = document.getElementById('badge_' + key);
    const hint = document.getElementById('hint_' + key);
    if (!badge) return;

    badge.className = 'api-status-badge ' + (info.status === 'active' ? 'api-status-active' : 'api-status-error');
    badge.title = info.message || '';
    badge.innerHTML = `
      <span class="status-dot ${info.status === 'active' ? 'dot-green' : 'dot-red'}"></span>
      <span class="status-text">${info.label}</span>
    `;

    if (hint) {
      if (info.status === 'active') {
        hint.style.display = 'none';
      } else {
        hint.style.display = 'flex';
        hint.innerHTML = `<span>⚠️</span> <span>${info.message}</span>`;
      }
    }
  }

  function setBadgeChecking(key) {
    const badge = document.getElementById('badge_' + key);
    if (!badge) return;
    badge.className = 'api-status-badge api-status-checking';
    badge.title = 'Verificando credenciales...';
    badge.innerHTML = `
      <span class="status-dot dot-yellow"></span>
      <span class="status-text">Verificando...</span>
    `;
  }

  async function checkSingleProvider(provider) {
    setBadgeChecking(provider);
    const token = document.querySelector('meta[name="csrf-token"]').content;

    const bodyData = { provider: provider };
    if (provider === 'gemini') bodyData.gemini_api_key = document.getElementById('input_gemini').value;
    if (provider === 'groq') bodyData.groq_api_key = document.getElementById('input_groq').value;
    if (provider === 'openai') bodyData.openai_api_key = document.getElementById('input_openai').value;
    if (provider === 'claude') bodyData.claude_api_key = document.getElementById('input_claude').value;
    if (provider === 'smtp') {
      bodyData.smtp_host = document.getElementById('smtp_host').value;
      bodyData.smtp_port = document.getElementById('smtp_port').value;
    }

    try {
      const res = await fetch("{{ route('settings.check_status') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: JSON.stringify(bodyData)
      });
      const data = await res.json();
      if (data.status) {
        renderBadge(provider, data.status);
      }
    } catch (e) {
      renderBadge(provider, { status: 'error', label: 'Error', message: 'Fallo al verificar servicio' });
    }
  }

  async function checkAllStatuses(btn) {
    const providers = ['gemini', 'groq', 'openai', 'claude', 'smtp'];
    providers.forEach(p => setBadgeChecking(p));

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span>⏳ Verificando...</span>';
    }

    const token = document.querySelector('meta[name="csrf-token"]').content;
    const bodyData = {
      provider: 'all',
      gemini_api_key: document.getElementById('input_gemini').value,
      groq_api_key: document.getElementById('input_groq').value,
      openai_api_key: document.getElementById('input_openai').value,
      claude_api_key: document.getElementById('input_claude').value,
      smtp_host: document.getElementById('smtp_host').value,
      smtp_port: document.getElementById('smtp_port').value,
    };

    try {
      const res = await fetch("{{ route('settings.check_status') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: JSON.stringify(bodyData)
      });
      const data = await res.json();
      if (data.statuses) {
        Object.keys(data.statuses).forEach(key => {
          renderBadge(key, data.statuses[key]);
        });
        showToast('Estados de IA y SMTP actualizados', 'info');
      }
    } catch (e) {
      showToast('Error al verificar conexiones', 'error');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<span>🔄</span> <span>Verificar Conexiones</span>';
      }
    }
  }

  async function saveSettings(e) {
    e.preventDefault();
    const btn = document.getElementById('save_btn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span>💾 Guardando y Verificando...</span>';

    const formData = new FormData(e.target);
    const token = document.querySelector('meta[name="csrf-token"]').content;

    try {
      const res = await fetch("{{ route('settings.store') }}", {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: formData
      });
      const data = await res.json();
      showToast(data.message, 'success');

      if (data.statuses) {
        Object.keys(data.statuses).forEach(key => {
          renderBadge(key, data.statuses[key]);
        });
      }
    } catch (err) {
      showToast('Error al guardar configuración', 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  }

  async function testSmtp(btn) {
    const email = document.getElementById('test_recipient').value;
    const host = document.getElementById('smtp_host').value;
    const port = document.getElementById('smtp_port').value;
    const token = document.querySelector('meta[name="csrf-token"]').content;

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Probando...';
    showToast('Probando conexión SMTP...', 'info');

    try {
      const res = await fetch("{{ route('settings.test_smtp') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: JSON.stringify({ test_email: email, smtp_host: host, smtp_port: port })
      });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, 'success');
        if (data.verify) renderBadge('smtp', data.verify);
      } else {
        showToast(data.message, 'error');
        if (data.verify) renderBadge('smtp', data.verify);
      }
    } catch (err) {
      showToast('Error en la prueba SMTP', 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  }

  // --- ASISTENTE IA DE MARCA & REMITENTE ---
  const defaultBrandRecommendations = [
    {
      sender_name: "Javier de Suitable | Convenios Clínicos",
      sender_email: "convenios@suitable.cl",
      type: "Personal + Empresa (+38% Apertura)",
      strategy: "Combina cercanía humana con respaldo institucional. Evita los filtros de spam y genera mayor confianza en directores médicos.",
      open_rate_boost: "+38% apertura"
    },
    {
      sender_name: "Fábrica Suitable | Uniformes Médicos",
      sender_email: "ventas@suitable.cl",
      type: "Fábrica Directa (Ahorro Institucional)",
      strategy: "Comunica de inmediato confección 100% chilena sin intermediarios, punto clave para encargados de presupuesto y abastecimiento.",
      open_rate_boost: "+31% apertura"
    },
    {
      sender_name: "Suitable Chile | Tallaje en Terreno",
      sender_email: "tallaje@suitable.cl",
      type: "Servicio Exclusivo (Diferenciador)",
      strategy: "Pone el foco en el servicio presencial de llevar muestras y percheros a la clínica, resolviendo el principal temor de calces.",
      open_rate_boost: "+35% apertura"
    },
    {
      sender_name: "Dotaciones Clínicas Suitable",
      sender_email: "dotaciones@suitable.cl",
      type: "Corporativo / Licitaciones B2B",
      strategy: "Formato formal idóneo para licitaciones públicas, convenios semestrales y compras masivas de hospitales y redes de salud.",
      open_rate_boost: "+26% apertura"
    }
  ];

  function toggleBrandAiAssistant() {
    const box = document.getElementById('ai_brand_assistant_box');
    if (box.style.display === 'none' || !box.style.display) {
      box.style.display = 'block';
      if (document.getElementById('ai_brand_recommendations_container').children.length === 0) {
        renderBrandRecommendations(defaultBrandRecommendations);
      }
      box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
      box.style.display = 'none';
    }
  }

  function renderBrandRecommendations(list) {
    const container = document.getElementById('ai_brand_recommendations_container');
    container.innerHTML = '';

    list.forEach(item => {
      const card = document.createElement('div');
      card.style.cssText = 'background: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 6px; padding: 12px; transition: border-color 0.2s ease;';
      card.onmouseenter = () => card.style.borderColor = '#1E8888';
      card.onmouseleave = () => card.style.borderColor = '#CBD5E1';

      card.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 6px;">
          <div>
            <strong style="color: #0F172A; font-size: 13.5px; display: block;">${item.sender_name}</strong>
            <div style="font-size: 11px; color: #0D9488; font-weight: 700;">${item.sender_email}</div>
          </div>
          <span class="badge badge-emerald" style="font-size: 10.5px; font-weight: 700; flex-shrink: 0;">${item.open_rate_boost || item.type}</span>
        </div>
        <p style="font-size: 11.5px; color: #475569; margin: 0 0 10px 0; line-height: 1.4;">${item.strategy}</p>
        <button type="button" class="btn btn-sm" style="background: #1E8888; color: white; font-size: 11.5px; font-weight: 700; padding: 6px 12px; width: 100%; border-radius: 5px; cursor: pointer;" onclick="applyBrandRecommendation('${item.sender_name.replace(/'/g, "\\'")}', '${item.sender_email.replace(/'/g, "\\'")}')">
          👉 Aplicar este Remitente y Correo
        </button>
      `;
      container.appendChild(card);
    });
  }

  function applyBrandRecommendation(name, email) {
    const nameInput = document.getElementById('sender_name');
    const emailInput = document.getElementById('sender_email');

    nameInput.value = name;
    if (email) emailInput.value = email;

    nameInput.style.borderColor = '#10B981';
    nameInput.style.backgroundColor = '#F0FDF4';
    setTimeout(() => {
      nameInput.style.borderColor = '';
      nameInput.style.backgroundColor = '';
    }, 1200);

    showToast(`✓ Remitente aplicado: "${name}"`, 'success');
  }

  async function loadBrandAiSuggestions(focus) {
    document.getElementById('ai_brand_custom_focus').value = focus;
    generateCustomBrandAi();
  }

  async function generateCustomBrandAi() {
    const focus = document.getElementById('ai_brand_custom_focus').value;
    const currentName = document.getElementById('sender_name').value;
    const btn = document.getElementById('btn_generate_custom_brand');
    const container = document.getElementById('ai_brand_recommendations_container');

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span>⚡ Redactando...</span>';

    container.innerHTML = `
      <div style="padding: 24px; text-align: center; color: #64748B; font-size: 12.5px;">
        <span>⏳ Consultando al Asistente IA para redactar opciones de alto impacto en Chile...</span>
      </div>
    `;

    try {
      const token = document.querySelector('meta[name="csrf-token"]').content;
      const res = await fetch("{{ route('settings.ai_brand_assist') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: JSON.stringify({ focus: focus, current_name: currentName })
      });
      const data = await res.json();
      if (data.recommendations && data.recommendations.length > 0) {
        renderBrandRecommendations(data.recommendations);
        showToast('Nuevas opciones de remitente generadas con IA (' + data.provider + ')', 'success');
      } else {
        renderBrandRecommendations(defaultBrandRecommendations);
      }
    } catch (e) {
      renderBrandRecommendations(defaultBrandRecommendations);
      showToast('Se cargaron las fórmulas de alta conversión recomendadas', 'info');
    } finally {
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  }
</script>
@endsection
