@extends('layouts.app')

@section('title', 'Grupos & Segmentación | Suitable')

@section('content')
<div class="page-header">
  <div class="page-title-group">
    <h1>Grupos &amp; Segmentos de Clínicas</h1>
    <p class="page-subtitle">Organice sus prospectos por especialidad, región o volumen para impactarlos con campañas personalizadas</p>
  </div>

  <div class="header-actions">
    <a href="{{ route('import.index') }}" class="btn btn-secondary btn-sm">📥 Importar Brevo/CSV</a>
    <button type="button" class="btn btn-primary btn-sm" onclick="openModal('modal-new-group')">
      + Crear Nuevo Grupo
    </button>
  </div>
</div>

<!-- GROUPS GRID -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
  @foreach ($groups as $g)
    <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-top: 4px solid {{ $g->color ?: '#1E8888' }}; border-radius: 10px; padding: 22px; box-shadow: 0 1px 4px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between;">
      <div>
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
          <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">
            {{ $g->name }}
          </h3>
          <span class="badge" style="background-color: #E6F4F4; color: #146161; font-weight: 800;">
            👥 {{ $g->clients_count }} contactos
          </span>
        </div>
        
        <p style="font-size: 13px; color: #64748B; line-height: 1.5; margin: 10px 0 18px 0;">
          {{ $g->description ?: 'Sin descripción detallada.' }}
        </p>
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #F1F5F9; padding-top: 14px; margin-top: 10px;">
        <a href="{{ route('clients.index', ['group' => $g->id]) }}" style="font-size: 13px; font-weight: 700; color: #1E8888; text-decoration: none;">
          Ver miembros ({{ $g->clients_count }}) →
        </a>
        <a href="{{ route('campaigns.create', ['group_id' => $g->id]) }}" class="btn btn-primary btn-sm">
          🚀 Crear Campaña
        </a>
      </div>
    </div>
  @endforeach
</div>

<!-- MODAL NEW GROUP -->
<div class="modal-backdrop" id="modal-new-group" style="display: none;">
  <div class="modal-box">
    <div class="modal-header">
      <h3 class="modal-title">Crear Nuevo Segmento de Contactos</h3>
      <button type="button" class="modal-close" onclick="closeModal('modal-new-group')">&times;</button>
    </div>
    <form id="form-new-group" onsubmit="saveGroup(event)">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Nombre del Grupo *</label>
          <input type="text" name="name" class="form-control" placeholder="Ej. Red de Hospitales Públicos RM" required>
        </div>

        <div class="form-group">
          <label class="form-label">Descripción del Segmento</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Características del segmento (ej. equipos grandes, telas antifluidos, etc.)"></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Color Identificador</label>
          <input type="color" name="color" class="form-control" value="#1E8888" style="height: 40px; padding: 2px;">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-new-group')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Crear Grupo →</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openModal(id) { document.getElementById(id).style.display = 'flex'; }
  function closeModal(id) { document.getElementById(id).style.display = 'none'; }

  async function saveGroup(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const token = document.querySelector('meta[name="csrf-token"]').content;

    try {
      const res = await fetch("{{ route('groups.store') }}", {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => location.reload(), 600);
      } else {
        showToast(data.error || 'Error al crear grupo', 'error');
      }
    } catch (err) {
      showToast('Error de conexión', 'error');
    }
  }
</script>
@endsection
