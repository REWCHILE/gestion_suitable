@extends('layouts.app')

@section('title', 'Pipeline de Clínicas & Embudo B2B | Suitable')

@section('content')
<div class="page-header">
  <div class="page-title-group">
    <h1>Pipeline Comercial de Clínicas &amp; Hospitales</h1>
    <p class="page-subtitle">Gestión del embudo de prospección, agendamiento de tallaje en clínica y cotizaciones corporativas</p>
  </div>

  <div class="header-actions">
    <!-- View switcher -->
    <div style="display: flex; background-color: #F1F5F9; border: 1px solid #CBD5E1; border-radius: 6px; padding: 3px; gap: 3px;">
      <a href="{{ route('clients.index', array_merge(request()->query(), ['view' => 'table'])) }}" 
         style="padding: 6px 14px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 4px; {{ $viewMode === 'table' ? 'background: #FFFFFF; color: #1E8888; font-weight: 800; box-shadow: 0 1px 3px rgba(0,0,0,0.1);' : 'color: #64748B;' }}">
        📑 Tabla
      </a>
      <a href="{{ route('clients.index', array_merge(request()->query(), ['view' => 'kanban'])) }}" 
         style="padding: 6px 14px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 4px; {{ $viewMode === 'kanban' ? 'background: #FFFFFF; color: #1E8888; font-weight: 800; box-shadow: 0 1px 3px rgba(0,0,0,0.1);' : 'color: #64748B;' }}">
        📋 Tablero
      </a>
    </div>

    <button type="button" class="btn btn-primary" onclick="openModal('modal-new-client')">
      + Nueva Clínica
    </button>
  </div>
</div>

<!-- FILTER BAR -->
<div class="table-card" style="padding: 14px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
  <form method="GET" action="{{ route('clients.index') }}" style="display: flex; gap: 10px; align-items: center; flex-grow: 1; max-width: 580px;">
    <input type="hidden" name="view" value="{{ $viewMode }}">
    @if($groupFilter > 0)
      <input type="hidden" name="group" value="{{ $groupFilter }}">
    @endif
    <div class="search-input-wrap" style="flex-grow: 1;">
      <span class="search-icon">🔍</span>
      <input type="text" name="search" class="search-input" placeholder="Buscar por clínica, doctor/contacto o comuna..." value="{{ $search }}">
    </div>
    <button type="submit" class="btn btn-secondary btn-sm" style="font-weight: 700;">Buscar</button>
    @if($search || $groupFilter)
      <a href="{{ route('clients.index', ['view' => $viewMode]) }}" class="btn btn-secondary btn-sm" style="color: var(--text-muted);">Ver Todos</a>
    @endif
  </form>

  <div style="font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
    @if($currentGroup)
      <span class="badge" style="background-color: #E6F4F4; color: #146161; font-weight: 700; border: 1px solid #99D5D5; padding: 5px 12px; font-size: 12.5px; display: inline-flex; align-items: center; gap: 6px;">
        👥 Grupo: <strong>{{ $currentGroup->name }}</strong> ({{ count($clients) }} contactos)
        <a href="{{ route('clients.index', array_merge(request()->except('group'), ['view' => $viewMode])) }}" style="color: #E11D48; text-decoration: none; margin-left: 6px; font-weight: 900;" title="Quitar filtro de grupo">✕</a>
      </span>
    @else
      <span>Mostrando <strong>{{ count($clients) }}</strong> instituciones registradas</span>
    @endif
    @if($search)
      <span class="badge badge-teal">Búsqueda: "{{ $search }}"</span>
    @endif
  </div>
</div>

@if($viewMode === 'table')
  <!-- TABLE VIEW -->
  <div class="table-card" style="margin-bottom: 24px;">
    <table class="crm-table">
      <thead>
        <tr>
          <th>Institución / Clínica</th>
          <th>Contacto</th>
          <th>Teléfono / WhatsApp</th>
          <th>Estado Actual</th>
          <th>Comuna</th>
          <th>Notas / Origen</th>
        </tr>
      </thead>
      <tbody>
        @forelse($clients as $cli)
          @php
            $phoneClean = preg_replace('/[^0-9]/', '', $cli->telefono ?? '');
          @endphp
          <tr>
            <td>
              <strong>{{ $cli->empresa }}</strong>
              <div style="font-size: 11px; color: #64748B;">Equipo aprox: {{ $cli->tamano_equipo ?: 15 }} pers.</div>
            </td>
            <td>
              <div>{{ $cli->contacto_nombre }}</div>
              <div style="font-size: 11px; color: #64748B;">{{ $cli->cargo ?: 'Profesional de Salud' }}</div>
              <a href="mailto:{{ $cli->email }}" style="font-size: 11px; color: #1E8888; text-decoration: none;">{{ $cli->email }}</a>
            </td>
            <td>
              @if($phoneClean)
                <a href="https://wa.me/{{ $phoneClean }}" target="_blank" class="btn btn-sm" style="background:#25D366; color:white; font-size:11px; font-weight:700; padding:4px 8px; border-radius:4px; text-decoration:none;">
                  💬 {{ $cli->telefono }}
                </a>
              @else
                <span style="color:#94A3B8; font-size:12px;">Sin teléfono</span>
              @endif
            </td>
            <td>
              @if($cli->estado === 'ganado')
                <span class="badge badge-emerald" style="background:#ECFDF5; color:#059669; font-weight:700;">Venta Cerrada</span>
              @elseif($cli->estado === 'tallaje_agendado')
                <span class="badge badge-purple" style="background:#FAF5FF; color:#7E22CE; font-weight:700;">Tallaje en Terreno</span>
              @elseif($cli->estado === 'cotizacion_enviada')
                <span class="badge badge-amber" style="background:#FFFBEB; color:#D97706; font-weight:700;">Cotización Enviada</span>
              @else
                <span class="badge badge-blue" style="background:#EFF6FF; color:#2563EB; font-weight:700;">{{ ucfirst(str_replace('_', ' ', $cli->estado)) }}</span>
              @endif
            </td>
            <td style="font-size: 12px; color: #475569;">
              {{ $cli->region_comuna }}
            </td>
            <td style="font-size: 11px; color: #64748B; max-width: 220px;">
              {{ strlen($cli->notas ?? '') > 50 ? substr($cli->notas, 0, 47) . '...' : ($cli->notas ?? '') }}
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align: center; padding: 40px; color: #64748B;">
              No se encontraron contactos con los filtros seleccionados.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@else
  <!-- KANBAN VIEW -->
  <div style="display: flex; gap: 16px; overflow-x: auto; padding-bottom: 20px;">
    @foreach($columns as $statusKey => $col)
      <div style="flex: 0 0 290px; background: #F8FAFC; border: 1px solid #E2E8F0; border-top: 4px solid {{ $col['color'] }}; border-radius: 10px; padding: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
          <h4 style="font-size: 14px; font-weight: 800; margin: 0; color: #0F172A;">
            {{ $col['icon'] }} {{ $col['title'] }}
          </h4>
          <span style="background: #E2E8F0; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 800;">
            {{ count($col['clients']) }}
          </span>
        </div>
        <div style="display: flex; flex-direction: column; gap: 10px;">
          @foreach($col['clients'] as $c)
            <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
              <div style="font-weight: 800; font-size: 13px; color: #0F172A;">{{ $c->empresa }}</div>
              <div style="font-size: 12px; color: #64748B; margin-top: 2px;">{{ $c->contacto_nombre }}</div>
              <div style="font-size: 11px; color: #1E8888; margin-top: 2px;">{{ $c->email }}</div>
              <div style="font-size: 10.5px; color: #94A3B8; margin-top: 6px;">📍 {{ $c->region_comuna }}</div>
            </div>
          @endforeach
        </div>
      </div>
    @endforeach
  </div>
@endif

<!-- MODAL NEW CLIENT -->
<div class="modal-backdrop" id="modal-new-client" style="display: none;">
  <div class="modal-box">
    <div class="modal-header">
      <h3 class="modal-title">Registrar Nueva Institución / Clínica</h3>
      <button type="button" class="modal-close" onclick="closeModal('modal-new-client')">&times;</button>
    </div>
    <form id="form-new-client" onsubmit="saveClient(event)">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Institución / Clínica / Centro *</label>
          <input type="text" name="empresa" class="form-control" placeholder="Ej. RedSalud Providencia" required>
        </div>
        <div class="form-group">
          <label class="form-label">Nombre del Contacto *</label>
          <input type="text" name="contacto_nombre" class="form-control" placeholder="Ej. Dra. Marcela Contreras" required>
        </div>
        <div class="form-group">
          <label class="form-label">Correo Electrónico *</label>
          <input type="email" name="email" class="form-control" placeholder="contacto@clinica.cl" required>
        </div>
        <div class="form-group">
          <label class="form-label">Teléfono / WhatsApp</label>
          <input type="text" name="telefono" class="form-control" placeholder="+56 9 1234 5678">
        </div>
        <div class="form-group">
          <label class="form-label">Cargo / Especialidad</label>
          <input type="text" name="cargo" class="form-control" placeholder="Directora Médica / Adquisiciones">
        </div>
        <div class="form-group">
          <label class="form-label">Comuna / Región</label>
          <input type="text" name="region_comuna" class="form-control" value="Santiago, RM">
        </div>
        <div class="form-group">
          <label class="form-label">Asignar a Grupo</label>
          <select name="group_id" class="form-control">
            <option value="">-- Sin grupo específico --</option>
            @foreach($allGroups as $g)
              <option value="{{ $g->id }}" {{ $groupFilter == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-new-client')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar en Pipeline →</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openModal(id) { document.getElementById(id).style.display = 'flex'; }
  function closeModal(id) { document.getElementById(id).style.display = 'none'; }

  async function saveClient(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const token = document.querySelector('meta[name="csrf-token"]').content;

    try {
      const res = await fetch("{{ route('clients.store') }}", {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => location.reload(), 600);
      } else {
        showToast(data.error || 'Error al guardar', 'error');
      }
    } catch (err) {
      showToast('Error de conexión', 'error');
    }
  }
</script>
@endsection
