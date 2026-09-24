@extends('layouts.app')

@section('title', 'Campañas B2B & Historial | Suitable')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
  <div class="page-title-group">
    <div style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 4px;">
      <span style="font-size: 22px;">🚀</span>
      <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #0F172A;">Campañas de Outreach B2B &amp; Plantillas</h1>
    </div>
    <p class="page-subtitle" style="margin: 0; color: #64748B; font-size: 13.5px;">
      Gestión de envíos masivos, previsualización interactiva de correos y orquestación con IA
    </p>
  </div>

  <div class="header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
    <a href="{{ route('campaigns.preview') }}" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700;">
      <span>👁️</span>
      <span>Ver Plantilla Completa</span>
    </a>
    <a href="{{ route('campaigns.create') }}" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; box-shadow: 0 2px 6px rgba(30,136,136,0.3);">
      <span>✨</span>
      <span>+ Crear Nueva Campaña con IA</span>
    </a>
  </div>
</div>

<!-- HERO CARD: PLANTILLA MAESTRA VISUAL & ACCIONES RÁPIDAS -->
<div style="background: linear-gradient(135deg, #0F2B2B 0%, #134E4A 100%); color: #FFFFFF; border-radius: 12px; padding: 22px 26px; margin-bottom: 26px; display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; box-shadow: 0 4px 18px rgba(15, 43, 43, 0.25);">
  <div style="display: flex; align-items: center; gap: 18px; max-width: 680px;">
    <div style="width: 52px; height: 52px; border-radius: 12px; background: rgba(30, 136, 136, 0.45); display: flex; align-items: center; justify-content: center; font-size: 26px; flex-shrink: 0; border: 1px solid rgba(255,255,255,0.2);">
      📧
    </div>
    <div>
      <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
        <strong style="font-size: 16px; letter-spacing: 0.2px;">Plantilla Maestra: Email Corporativo Clínicas B2B</strong>
        <span style="background: #10B981; color: white; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 10px; text-transform: uppercase;">
          Diseño Activo
        </span>
      </div>
      <p style="font-size: 12.5px; color: #CCFBF1; margin: 0; line-height: 1.5;">
        Estructura responsive probada con: <strong>Fabricación chilena directa</strong>, <strong>telas Flex 4-Way antifluido</strong>, <strong>6 meses de garantía integral</strong> y <strong>servicio de tallaje en terreno</strong> con percheros en la clínica.
      </p>
    </div>
  </div>

  <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
    <a href="{{ route('campaigns.preview') }}" class="btn btn-secondary btn-sm" style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); font-weight: 700; padding: 10px 16px;">
      <span>👁️</span>
      <span>Simulador Móvil / Desktop</span>
    </a>
    <a href="{{ route('campaigns.preview_html') }}" target="_blank" class="btn btn-primary btn-sm" style="background: #1E8888; border-color: #1E8888; font-weight: 700; padding: 10px 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
      <span>🌐</span>
      <span>Abrir en el Navegador →</span>
    </a>
  </div>
</div>

<!-- TABLA DE CAMPAÑAS CON ACCIONES OPERATIVAS -->
<div class="table-card">
  <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #E2E8F0; flex-wrap: wrap; gap: 10px;">
    <div>
      <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: #0F172A;">Campañas Registradas en el Sistema</h3>
      <p style="margin: 2px 0 0 0; font-size: 12px; color: #64748B;">Lanza envíos masivos, previsualiza el diseño o dispara pruebas a tu correo</p>
    </div>
    <span class="badge" style="background: #F1F5F9; color: #475569; font-weight: 700;">
      {{ $campaigns->count() }} campañas • {{ $totalClients }} contactos en CRM
    </span>
  </div>

  <table class="crm-table">
    <thead>
      <tr>
        <th>Nombre de Campaña</th>
        <th>Segmento / Grupo</th>
        <th>Asunto del Correo</th>
        <th>Motor IA</th>
        <th>Destinatarios</th>
        <th>Estado</th>
        <th>Fecha</th>
        <th style="text-align: right; min-width: 240px;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      @forelse($campaigns as $camp)
        <tr id="row_campaign_{{ $camp->id }}">
          <td>
            <strong style="color: #0F172A; font-size: 13px;">{{ $camp->name }}</strong>
          </td>
          <td>
            @if($camp->group)
              <span class="badge" style="background:#E6F4F4; color:#146161; font-weight:700;">
                👥 {{ $camp->group->name }}
              </span>
            @else
              <span class="badge" style="background:#F1F5F9; color:#475569;">Todos los clientes ({{ $totalClients }})</span>
            @endif
          </td>
          <td>
            <div style="font-size:12.5px; font-weight:600; color:#0F172A; max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
              {{ $camp->subject }}
            </div>
            <div style="font-size:11px; color:#64748B; max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
              {{ Str::limit($camp->preheader, 50) }}
            </div>
          </td>
          <td>
            <span class="badge" style="background:#F1F5F9; color:#475569; font-weight:800; text-transform:uppercase;">
              {{ $camp->ai_provider ?: 'Groq' }}
            </span>
          </td>
          <td>
            <strong style="color: #0F172A;">{{ $camp->sent_count }} / {{ $camp->total_count ?: $totalClients }}</strong>
          </td>
          <td>
            @if($camp->status === 'enviada')
              <span class="badge badge-emerald" id="badge_status_{{ $camp->id }}">● Enviada</span>
            @else
              <span class="badge badge-blue" id="badge_status_{{ $camp->id }}">{{ ucfirst($camp->status) }}</span>
            @endif
          </td>
          <td style="font-size:11.5px; color:#64748B; white-space: nowrap;">
            {{ $camp->created_at ? $camp->created_at->format('d/m/Y H:i') : '-' }}
          </td>
          <td style="text-align: right; white-space: nowrap;">
            <div style="display: inline-flex; gap: 6px; align-items: center;">
              <!-- 1. PREVISUALIZAR -->
              <a href="{{ route('campaigns.preview_campaign', $camp->id) }}" class="btn btn-secondary btn-sm" title="Previsualizar correo completo" style="padding: 5px 9px; font-size: 11.5px; font-weight: 700;">
                <span>👁️</span>
                <span>Ver</span>
              </a>

              <!-- 2. EDITAR -->
              <a href="{{ route('campaigns.edit', $camp->id) }}" class="btn btn-secondary btn-sm" title="Editar campaña, textos e imagen" style="padding: 5px 9px; font-size: 11.5px; font-weight: 700; color: #1E8888; border-color: #1E8888;">
                <span>✏️</span>
                <span>Editar</span>
              </a>

              <!-- 3. LANZAR / ENVIAR -->
              <button type="button" class="btn btn-primary btn-sm" onclick="openSendModal({{ $camp->id }}, '{{ addslashes($camp->name) }}', {{ $camp->total_count ?: $totalClients }})" style="padding: 5px 10px; font-size: 11.5px; font-weight: 700; background: #059669; border-color: #059669;">
                <span>🚀</span>
                <span>Lanzar</span>
              </button>

              <!-- 4. ENVIAR PRUEBA -->
              <button type="button" class="btn btn-secondary btn-sm" onclick="openTestModal({{ $camp->id }})" title="Enviar correo de prueba a mi correo" style="padding: 5px 8px; font-size: 11.5px;">
                <span>✉️</span>
              </button>

              <!-- 5. ELIMINAR -->
              <button type="button" class="btn btn-secondary btn-sm" onclick="deleteCampaign({{ $camp->id }})" title="Eliminar campaña" style="padding: 5px 8px; font-size: 11.5px; color: #EF4444;">
                <span>🗑️</span>
              </button>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8" style="text-align: center; padding: 50px 20px; color: #64748B;">
            <div style="font-size: 32px; margin-bottom: 8px;">🚀</div>
            <div style="font-size: 14px; font-weight: 700; color: #0F172A;">No hay campañas creadas aún</div>
            <p style="font-size: 12px; margin: 4px 0 16px 0;">Crea tu primera campaña asistida por IA o previsualiza la plantilla corporativa.</p>
            <a href="{{ route('campaigns.create') }}" class="btn btn-primary btn-sm" style="font-weight: 700;">
              + Crear Nueva Campaña con IA
            </a>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
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
      Ingresa la dirección donde deseas recibir el correo de prueba para verificar su visualización en tu cliente de correo (Gmail, Outlook, etc.).
    </p>

    <div class="form-group" style="margin-bottom: 18px;">
      <label class="form-label" style="font-weight: 700;">Dirección de Email de Prueba:</label>
      <input type="email" id="test_email_input" class="form-control" placeholder="tu-correo@clinica.cl o gmail.com" value="ventas@suitable.cl" style="padding: 10px 14px; font-size: 14px;">
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
        <span>Lanzamiento de Campaña B2B</span>
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
  let targetCampaignId = null;

  function openTestModal(id) {
    targetCampaignId = id;
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
        
        // Actualizar visualmente la fila
        const badge = document.getElementById('badge_status_' + targetCampaignId);
        if (badge) {
          badge.className = 'badge badge-emerald';
          badge.innerText = '● Enviada';
        }
        setTimeout(() => {
          window.location.reload();
        }, 1200);
      } else {
        showToast(data.error || 'Error al lanzar campaña', 'error');
      }
    } catch (e) {
      showToast('Error de conexión o ejecución al procesar envío', 'error');
    } finally {
      btn.disabled = false;
      btn.innerText = 'Confirmar y Disparar Campaña';
    }
  }

  async function deleteCampaign(id) {
    if (!confirm('¿Estás seguro de eliminar esta campaña?')) return;

    try {
      const token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
      const res = await fetch(`{{ url('campaigns') }}/${id}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        }
      });
      const data = await res.json();
      if (data.success) {
        showToast('Campaña eliminada correctamente', 'info');
        const row = document.getElementById('row_campaign_' + id);
        if (row) row.remove();
      } else {
        showToast('No se pudo eliminar la campaña', 'error');
      }
    } catch (e) {
      showToast('Error al eliminar la campaña', 'error');
    }
  }
</script>
@endsection
