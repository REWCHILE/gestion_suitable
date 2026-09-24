@extends('layouts.app')

@section('title', 'Ajustes & Configuración | Suitable')

@section('content')
<div class="page-header">
  <div class="page-title-group">
    <h1>Ajustes del Sistema &amp; Proveedores de IA</h1>
    <p class="page-subtitle">Configure credenciales de IA, parámetros de marca y conexión SMTP Stealth para VPS Postfix / cPanel</p>
  </div>
</div>

<form id="settings_form" onsubmit="saveSettings(event)">
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    
    <!-- LEFT: AI KEYS & BRAND -->
    <div class="table-card" style="padding: 24px;">
      <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0 0 16px 0;">
        🤖 Llaves de Inteligencia Artificial (API Keys)
      </h3>

      <div class="form-group">
        <label class="form-label">Google Gemini API Key</label>
        <input type="password" name="gemini_api_key" class="form-control" value="{{ $settings['gemini_api_key'] ?? '' }}" placeholder="AIzaSy...">
      </div>

      <div class="form-group">
        <label class="form-label">Groq API Key (Llama 3.3)</label>
        <input type="password" name="groq_api_key" class="form-control" value="{{ $settings['groq_api_key'] ?? '' }}" placeholder="gsk_...">
      </div>

      <div class="form-group">
        <label class="form-label">OpenAI API Key (ChatGPT)</label>
        <input type="password" name="openai_api_key" class="form-control" value="{{ $settings['openai_api_key'] ?? '' }}" placeholder="sk-...">
      </div>

      <div class="form-group">
        <label class="form-label">Anthropic Claude API Key</label>
        <input type="password" name="claude_api_key" class="form-control" value="{{ $settings['claude_api_key'] ?? '' }}" placeholder="sk-ant-...">
      </div>

      <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 24px 0 16px 0;">
        🏷️ Parámetros de Marca Suitable
      </h3>

      <div class="form-group">
        <label class="form-label">Nombre del Remitente</label>
        <input type="text" name="sender_name" class="form-control" value="{{ $settings['sender_name'] ?? 'Suitable Uniformes Clínicos' }}">
      </div>

      <div class="form-group">
        <label class="form-label">Correo Electrónico Remitente</label>
        <input type="email" name="sender_email" class="form-control" value="{{ $settings['sender_email'] ?? 'ventas@suitable.cl' }}">
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 14px;">
        💾 Guardar Toda la Configuración
      </button>
    </div>

    <!-- RIGHT: SMTP & VPS POSTFIX -->
    <div class="table-card" style="padding: 24px;">
      <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0 0 16px 0;">
        🚀 Conexión SMTP / VPS Postfix Stealth
      </h3>

      <div class="form-group">
        <label class="form-label">Servidor SMTP (Host)</label>
        <input type="text" name="smtp_host" id="smtp_host" class="form-control" value="{{ $settings['smtp_host'] ?? '127.0.0.1' }}" placeholder="mail.suitable.cl o IP del VPS">
      </div>

      <div class="form-group">
        <label class="form-label">Puerto SMTP</label>
        <input type="text" name="smtp_port" id="smtp_port" class="form-control" value="{{ $settings['smtp_port'] ?? '587' }}">
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
      <div style="margin-top: 20px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 14px;">
        <label class="form-label" style="font-weight: 700;">Prueba Rápida de Envío Stealth</label>
        <div style="display: flex; gap: 8px;">
          <input type="email" id="test_recipient" class="form-control" placeholder="correo@destino.cl" value="ventas@suitable.cl">
          <button type="button" class="btn btn-secondary btn-sm" onclick="testSmtp()">Probar SMTP</button>
        </div>
      </div>
    </div>

  </div>
</form>

<script>
  async function saveSettings(e) {
    e.preventDefault();
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
    } catch (err) {
      showToast('Error al guardar configuración', 'error');
    }
  }

  async function testSmtp() {
    const email = document.getElementById('test_recipient').value;
    const token = document.querySelector('meta[name="csrf-token"]').content;
    showToast('Probando conexión SMTP...', 'info');

    try {
      const res = await fetch("{{ route('settings.test_smtp') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: JSON.stringify({ test_email: email })
      });
      const data = await res.json();
      showToast(data.message, 'success');
    } catch (err) {
      showToast('Error en la prueba SMTP', 'error');
    }
  }
</script>
@endsection
