@extends('layouts.app')

@section('title', 'Visualizador de Campaña & Vista Previa | Suitable')

@section('content')
<style>
  .preview-topbar {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  }
  .preview-device-toggle {
    display: inline-flex;
    background: #F1F5F9;
    padding: 4px;
    border-radius: 8px;
    gap: 4px;
  }
  .device-btn {
    border: none;
    background: transparent;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12.5px;
    font-weight: 700;
    color: #64748B;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
  }
  .device-btn:hover {
    color: #0F172A;
  }
  .device-btn.active {
    background: #FFFFFF;
    color: #1E8888;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }
  .preview-stage {
    background: #CBD5E1;
    border-radius: 12px;
    padding: 30px 16px;
    display: flex;
    justify-content: center;
    min-height: 850px;
    transition: all 0.3s ease;
  }
  .preview-frame-wrapper {
    width: 620px;
    max-width: 100%;
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 10px 30px rgba(0,0,0,0.18);
    border-radius: 12px;
    overflow: hidden;
    background: #FFFFFF;
  }
  .preview-frame-wrapper.mobile {
    width: 385px;
  }
  .preview-frame-wrapper.fluid {
    width: 100%;
    max-width: 900px;
  }
  .preview-iframe {
    width: 100%;
    height: 1200px;
    border: none;
    display: block;
    background: #FFFFFF;
  }
</style>

<!-- CABECERA DE CONTROL DE VISTA PREVIA -->
<div class="preview-topbar">
  <div style="display: flex; align-items: center; gap: 14px;">
    <a href="{{ route('campaigns.index') }}" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700;">
      <span>←</span>
      <span>Volver a Campañas</span>
    </a>
    <div>
      <div style="display: flex; align-items: center; gap: 8px;">
        <h2 style="margin: 0; font-size: 16px; font-weight: 800; color: #0F172A;">
          {{ $title }}
        </h2>
        <span class="badge badge-emerald" style="font-size: 10px;">Plantilla HTML Lista</span>
      </div>
      <div style="font-size: 12px; color: #64748B; margin-top: 2px;">
        <strong>Asunto:</strong> {{ $subject }}
      </div>
    </div>
  </div>

  <!-- SELECTOR DE DISPOSITIVO (RESPONSIVE SIMULATOR) -->
  <div class="preview-device-toggle">
    <button type="button" class="device-btn active" id="btn_device_desktop" onclick="setDevice('desktop')">
      <span>🖥️</span>
      <span>Escritorio (600px)</span>
    </button>
    <button type="button" class="device-btn" id="btn_device_mobile" onclick="setDevice('mobile')">
      <span>📱</span>
      <span>Móvil (385px)</span>
    </button>
    <button type="button" class="device-btn" id="btn_device_fluid" onclick="setDevice('fluid')">
      <span>🌐</span>
      <span>100% Pantalla</span>
    </button>
  </div>

  <!-- ACCIONES DE CAMPAÑA -->
  <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
    <a href="{{ $htmlUrl }}" target="_blank" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
      <span>🌐</span>
      <span>Abrir en Pestaña Nueva</span>
    </a>
    <button type="button" class="btn btn-secondary btn-sm" onclick="copyHtmlSource()" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
      <span>📋</span>
      <span>Copiar HTML</span>
    </button>
    <button type="button" class="btn btn-secondary btn-sm" onclick="openTestModal()" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; color: #1E8888;">
      <span>✉️</span>
      <span>Enviar Prueba</span>
    </button>
    @if($campaign)
      <button type="button" class="btn btn-primary btn-sm" onclick="openSendModal({{ $campaign->id }}, '{{ addslashes($campaign->name) }}', {{ $campaign->total_count ?: 540 }})" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; box-shadow: 0 2px 6px rgba(30,136,136,0.3);">
        <span>🚀</span>
        <span>Lanzar Campaña</span>
      </button>
    @endif
  </div>
</div>

<!-- ESCENARIO DE VISUALIZACIÓN INTERACTIVA -->
<div class="preview-stage">
  <div class="preview-frame-wrapper" id="frame_wrapper">
    <iframe src="{{ $htmlUrl }}" id="preview_iframe" class="preview-iframe" title="Email Preview"></iframe>
  </div>
</div>

<!-- MODAL: ENVIAR CORREO DE PRUEBA -->
<div id="test_email_modal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(3px);">
  <div style="background: white; border-radius: 12px; width: 100%; max-width: 480px; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0F172A; display: flex; align-items: center; gap: 8px;">
        <span>✉️</span>
        <span>Enviar Correo de Prueba</span>
      </h3>
      <button type="button" onclick="closeTestModal()" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #94A3B8;">✕</button>
    </div>
    
    <p style="font-size: 13px; color: #64748B; margin-top: 0; margin-bottom: 16px;">
      Ingrese su dirección de correo para recibir una muestra idéntica a la que recibirán los directores médicos y encargados de adquisiciones.
    </p>

    <div class="form-group" style="margin-bottom: 18px;">
      <label class="form-label" style="font-weight: 700;">Dirección de Email de Prueba:</label>
      <input type="email" id="test_email_input" class="form-control" placeholder="ejemplo@clinica.cl o su-correo@gmail.com" value="ventas@suitable.cl" style="padding: 10px 14px; font-size: 14px;">
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 10px;">
      <button type="button" class="btn btn-secondary" onclick="closeTestModal()">Cancelar</button>
      <button type="button" class="btn btn-primary" id="btn_submit_test" onclick="submitTestEmail()" style="font-weight: 700;">
        <span>Enviar Prueba Ahora</span>
      </button>
    </div>
  </div>
</div>

<!-- MODAL: LANZAR CAMPAÑA -->
<div id="send_campaign_modal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(3px);">
  <div style="background: white; border-radius: 12px; width: 100%; max-width: 520px; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0F172A; display: flex; align-items: center; gap: 8px;">
        <span>🚀</span>
        <span>Confirmar Lanzamiento de Campaña</span>
      </h3>
      <button type="button" onclick="closeSendModal()" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #94A3B8;">✕</button>
    </div>

    <div style="background: #F0FDFA; border: 1px solid #99F6E4; border-radius: 8px; padding: 14px; margin-bottom: 18px;">
      <div style="font-weight: 700; color: #115E59; font-size: 13.5px;" id="send_modal_name">Campaña B2B</div>
      <div style="font-size: 12.5px; color: #134E4A; margin-top: 4px;">
        Se enviará a <strong id="send_modal_count">540</strong> contactos institucionales registrados en el CRM.
      </div>
    </div>

    <div class="form-group" style="margin-bottom: 18px;">
      <label class="form-label" style="font-weight: 700;">Modalidad de Envío:</label>
      <select id="send_mode_select" class="form-control" style="padding: 10px; font-weight: 600;">
        <option value="simulacion" selected>🧪 Simulación de Entrega (Registra métricas y logs sin tocar servidor SMTP)</option>
        <option value="real">⚡ Envío Real a través de Servidor SMTP / Postfix</option>
      </select>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 10px;">
      <button type="button" class="btn btn-secondary" onclick="closeSendModal()">Cancelar</button>
      <button type="button" class="btn btn-primary" id="btn_submit_send" onclick="submitSendCampaign()" style="font-weight: 700; background: #059669; border-color: #059669;">
        <span>Confirmar y Disparar Campaña</span>
      </button>
    </div>
  </div>
</div>

<script>
  let targetCampaignId = {{ $campaign ? $campaign->id : 'null' }};

  function setDevice(device) {
    const wrapper = document.getElementById('frame_wrapper');
    const btnDesktop = document.getElementById('btn_device_desktop');
    const btnMobile = document.getElementById('btn_device_mobile');
    const btnFluid = document.getElementById('btn_device_fluid');

    [btnDesktop, btnMobile, btnFluid].forEach(b => b.classList.remove('active'));

    wrapper.classList.remove('mobile', 'fluid');

    if (device === 'mobile') {
      wrapper.classList.add('mobile');
      btnMobile.classList.add('active');
    } else if (device === 'fluid') {
      wrapper.classList.add('fluid');
      btnFluid.classList.add('active');
    } else {
      btnDesktop.classList.add('active');
    }
  }

  async function copyHtmlSource() {
    try {
      const res = await fetch("{{ $htmlUrl }}");
      const html = await res.text();
      await navigator.clipboard.writeText(html);
      showToast('Código HTML copiado al portapapeles exitosamente', 'success');
    } catch (e) {
      showToast('Error al copiar código HTML', 'error');
    }
  }

  function openTestModal() {
    document.getElementById('test_email_modal').style.display = 'flex';
  }
  function closeTestModal() {
    document.getElementById('test_email_modal').style.display = 'none';
  }

  function openSendModal(id, name, count) {
    targetCampaignId = id;
    document.getElementById('send_modal_name').innerText = name;
    document.getElementById('send_modal_count').innerText = count;
    document.getElementById('send_campaign_modal').style.display = 'flex';
  }
  function closeSendModal() {
    document.getElementById('send_campaign_modal').style.display = 'none';
  }

  async function submitTestEmail() {
    const email = document.getElementById('test_email_input').value.trim();
    if (!email) {
      showToast('Ingrese un email de prueba válido', 'warning');
      return;
    }

    const btn = document.getElementById('btn_submit_test');
    btn.disabled = true;
    btn.innerText = 'Enviando prueba...';

    try {
      const token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
      const url = targetCampaignId 
        ? `{{ url('campaigns') }}/${targetCampaignId}/test-email`
        : `{{ url('campaigns') }}/1/test-email`;

      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': token,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ email: email })
      });

      const data = await res.json();
      if (data.success) {
        showToast(data.message || 'Correo de prueba enviado con éxito', 'success');
        closeTestModal();
      } else {
        showToast(data.error || 'No se pudo enviar la prueba', 'error');
      }
    } catch (e) {
      showToast('Error de conexión al enviar prueba', 'error');
    } finally {
      btn.disabled = false;
      btn.innerText = 'Enviar Prueba Ahora';
    }
  }

  async function submitSendCampaign() {
    if (!targetCampaignId) return;

    const mode = document.getElementById('send_mode_select').value;
    const btn = document.getElementById('btn_submit_send');
    btn.disabled = true;
    btn.innerText = 'Disparando campaña...';

    try {
      const token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
      const res = await fetch(`{{ url('campaigns') }}/${targetCampaignId}/send`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': token,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ mode: mode })
      });

      const data = await res.json();
      if (data.success) {
        showToast(data.message || 'Campaña lanzada con éxito', 'success');
        closeSendModal();
        setTimeout(() => {
          window.location.href = "{{ route('campaigns.index') }}";
        }, 1200);
      } else {
        showToast(data.error || 'Error al lanzar campaña', 'error');
      }
    } catch (e) {
      showToast('Error de conexión al procesar envío', 'error');
    } finally {
      btn.disabled = false;
      btn.innerText = 'Confirmar y Disparar Campaña';
    }
  }
</script>
@endsection
