@extends('layouts.app')

@section('title', 'Importador de Contactos Brevo & CSV | Suitable')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
  <div class="page-title-group">
    <h1>Importar Listas de Clínicas &amp; Prospectos</h1>
    <p class="page-subtitle">Suba archivos CSV de salud, descargue plantillas maestras o pegue tablas desde Brevo</p>
  </div>
  <div class="header-actions" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
    <a href="{{ route('import.template') }}" download="Plantilla_Maestra_Contactos_Suitable.csv" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600; padding: 10px 18px; box-shadow: 0 2px 4px rgba(30,136,136,0.2);">
      <span>📥</span>
      <span>Descargar Excel Maestro (.csv)</span>
    </a>
    <a href="{{ route('import.export') }}" download="Base_Contactos_Suitable_Completa.csv" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600; padding: 10px 18px;">
      <span>📊</span>
      <span>Exportar Base Completa (540 Contactos)</span>
    </a>
  </div>
</div>

@if(isset($importResults))
  <div style="background-color: #ECFDF5; border: 1px solid #6EE7B7; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
    <h3 style="color: #065F46; font-size: 16px; font-weight: 700; margin-bottom: 8px;">
      ✓ ¡Importación Procesada Exitosamente!
    </h3>
    <div style="font-size: 13px; color: #047857; display: flex; gap: 20px; flex-wrap: wrap;">
      <span><strong>{{ $importResults['inserted'] }}</strong> Nuevos Contactos Creados</span>
      <span><strong>{{ $importResults['updated'] }}</strong> Contactos Actualizados</span>
      <span><strong>{{ $importResults['errors'] }}</strong> Filas Omitidas</span>
    </div>
    <div style="margin-top: 14px; display: flex; gap: 10px;">
      <a href="{{ route('clients.index') }}" class="btn btn-primary btn-sm">Ver en Pipeline →</a>
      @if($importResults['group_id'] > 0)
        <a href="{{ route('campaigns.create', ['group_id' => $importResults['group_id']]) }}" class="btn btn-secondary btn-sm">Crear Campaña para este Grupo →</a>
      @endif
    </div>
  </div>
@endif

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
  
  <!-- FORM DE IMPORTACIÓN -->
  <div class="table-card" style="padding: 28px;">
    <form method="POST" action="{{ route('import.process') }}" enctype="multipart/form-data">
      @csrf

      <!-- 1. ASIGNACIÓN A GRUPO -->
      <div class="form-group">
        <label class="form-label">Asignar a Grupo / Segmento de Clínicas</label>
        <select name="group_id" id="group_selector" class="form-control" onchange="toggleNewGroupInput(this.value)">
          <option value="0">-- No asignar a ningún grupo (Solo añadir a Pipeline) --</option>
          @foreach ($groups as $g)
            <option value="{{ $g->id }}">{{ $g->name }}</option>
          @endforeach
          <option value="-1">➕ Crear un nuevo grupo para esta lista...</option>
        </select>
      </div>

      <!-- INPUT PARA NUEVO GRUPO -->
      <div class="form-group" id="new_group_container" style="display: none; background-color: #F0FDF4; padding: 14px; border-radius: 6px; border: 1px solid #BBF7D0;">
        <label class="form-label" style="color: #166534;">Nombre del Nuevo Grupo:</label>
        <input type="text" name="new_group_name" id="new_group_name" class="form-control" placeholder="Ej. Clínicas Dentales Viña del Mar">
      </div>

      <!-- 2. ARCHIVO CSV -->
      <div class="form-group">
        <label class="form-label">Opción A: Subir Archivo (.csv)</label>
        <div style="border: 2px dashed #CBD5E1; border-radius: 8px; padding: 25px; text-align: center; background: #F8FAFC; cursor: pointer;" onclick="document.getElementById('csv_file').click()">
          <div style="font-size: 30px; margin-bottom: 6px;">📁</div>
          <strong style="color: #0F172A; font-size: 14px;">Haga clic para seleccionar archivo CSV</strong>
          <div style="font-size: 12px; color: #64748B; margin-top: 4px;">Compatible con Excel, Plantilla Maestra, HubSpot y exportaciones de Brevo</div>
          <div id="file_selected_name" style="margin-top: 8px; font-weight: 700; color: #1E8888;"></div>
          <input type="file" id="csv_file" name="csv_file" accept=".csv,text/csv" style="display: none;" onchange="handleFileSelected(this)">
        </div>
      </div>

      <div style="text-align: center; margin: 18px 0; color: #94A3B8; font-weight: 700; font-size: 12px;">
        O TAMBIÉN PUEDE
      </div>

      <!-- 3. PEGAR TEXTO CSV O BREVO -->
      <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
          <label class="form-label" style="margin-bottom: 0;">Opción B: Pegar datos CSV o Copiar y Pegar desde Brevo</label>
          <span class="badge" style="background: #E0F2FE; color: #0284C7; font-size: 11px; font-weight: 700;">✓ Soporte Brevo Copiar/Pegar</span>
        </div>
        <textarea name="csv_text" id="csv_text" class="form-control" rows="6" placeholder="Pegue aquí:&#10;1) Archivo CSV tradicional o Plantilla Maestra (Empresa,Contacto,Email,Telefono,Cargo,Comuna,Tamano_Equipo,Notas)&#10;2) O copie y pegue directamente la tabla de Brevo (LISTA ENCUESTA / Contactos con enlaces y correos)."></textarea>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 14px;">
        📥 Procesar e Importar Contactos →
      </button>
    </form>
  </div>

  <!-- GUÍA DE FORMATO Y PLANTILLA -->
  <div>
    <!-- TARJETA EXCEL MAESTRO -->
    <div class="table-card" style="padding: 22px; margin-bottom: 20px; background: linear-gradient(135deg, #F0FDF4 0%, #E6F4F4 100%); border: 1px solid #99F6E4;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <span style="font-weight: 700; color: #115E59; font-size: 13.5px; display: inline-flex; align-items: center; gap: 6px;">
          <span style="font-size: 18px;">📑</span> Excel Maestro para Rellenar
        </span>
        <span class="badge" style="background: #0D9488; color: #FFFFFF; font-size: 10.5px; font-weight: 700; padding: 3px 8px;">Recomendado</span>
      </div>
      <p style="font-size: 12px; color: #134E4A; line-height: 1.5; margin-bottom: 14px;">
        Descargue la plantilla oficial formateada con las columnas maestras que el sistema reconoce. Ideal para que el cliente rellene sus listas o para prospectar clínicas en internet.
      </p>

      <div style="background: #FFFFFF; border-radius: 6px; padding: 12px; border: 1px solid #CCFBF1; margin-bottom: 14px;">
        <div style="font-size: 11px; font-weight: 700; color: #0F766E; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">Columnas que el CRM entiende:</div>
        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
          <span style="background: #F0FDFA; color: #0F766E; border: 1px solid #99F6E4; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-family: monospace; font-weight: 600;">Empresa</span>
          <span style="background: #F0FDFA; color: #0F766E; border: 1px solid #99F6E4; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-family: monospace; font-weight: 600;">Contacto</span>
          <span style="background: #F0FDFA; color: #0F766E; border: 1px solid #99F6E4; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-family: monospace; font-weight: 600;">Email*</span>
          <span style="background: #F0FDFA; color: #0F766E; border: 1px solid #99F6E4; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-family: monospace; font-weight: 600;">Telefono</span>
          <span style="background: #F0FDFA; color: #0F766E; border: 1px solid #99F6E4; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-family: monospace; font-weight: 600;">Cargo</span>
          <span style="background: #F0FDFA; color: #0F766E; border: 1px solid #99F6E4; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-family: monospace; font-weight: 600;">Comuna</span>
          <span style="background: #F0FDFA; color: #0F766E; border: 1px solid #99F6E4; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-family: monospace; font-weight: 600;">Tamano_Equipo</span>
          <span style="background: #F0FDFA; color: #0F766E; border: 1px solid #99F6E4; padding: 2px 7px; border-radius: 4px; font-size: 11px; font-family: monospace; font-weight: 600;">Notas</span>
        </div>
      </div>

      <div style="display: flex; flex-direction: column; gap: 8px;">
        <a href="{{ route('import.template') }}" download="Plantilla_Maestra_Contactos_Suitable.csv" class="btn" style="background: #1E8888; color: white; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 12.5px; font-weight: 700; padding: 10px; border-radius: 6px; text-decoration: none;">
          <span>📥</span> Descargar Plantilla Maestra (.csv)
        </a>
        <a href="{{ route('import.export') }}" download="Base_Contactos_Suitable_Completa.csv" class="btn" style="background: #0284C7; color: white; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 12.5px; font-weight: 700; padding: 10px; border-radius: 6px; text-decoration: none;">
          <span>📊</span> Exportar BD Completa (540 Clínicas)
        </a>
        <div style="font-size: 11px; color: #0F766E; text-align: center; margin-top: 4px;">
          ✓ Compatible con Excel (BOM UTF-8, tildes y caracteres chilenos)
        </div>
      </div>
    </div>

    <!-- GUÍA DE DETECCIÓN INTELIGENTE -->
    <div class="table-card" style="padding: 20px; background: #F8FAFC;">
      <div style="display: inline-flex; align-items: center; gap: 6px; background-color: #E6F4F4; color: #146161; font-weight: 700; font-size: 11px; padding: 4px 8px; border-radius: 4px; margin-bottom: 10px;">
        ⚡ Compatible con Brevo &amp; Excel
      </div>
      <h3 style="font-size: 15px; font-weight: 700; color: #0F766E; margin-bottom: 10px;">
        💡 Detección Inteligente de Contactos
      </h3>
      <p style="color: #64748B; line-height: 1.5; font-size: 12px; margin-bottom: 12px;">
        El motor mapea automáticamente columnas de CSV o interpreta tablas copiadas directamente desde el navegador en Brevo:
      </p>

      <ul style="padding-left: 18px; color: #0F172A; font-size: 12px; line-height: 1.8;">
        <li><strong>Empresa:</strong> Detección por nombre o dominio institucional</li>
        <li><strong>Contacto:</strong> Nombre deducido y formateado</li>
        <li><strong>Correo:</strong> Email validado (Requerido)</li>
        <li><strong>Teléfono:</strong> Teléfono / WhatsApp / SMS</li>
        <li><strong>Cargo:</strong> Especialidad (Dr., Ps., Kinesiólogo, etc.)</li>
        <li><strong>Dotación / Notas:</strong> Número de equipo y observaciones</li>
      </ul>

      <div style="margin-top: 14px; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 6px; padding: 10px;">
        <strong style="font-size: 11px; color: #0284C7; display: block; margin-bottom: 4px;">🚀 Soporte Directo Brevo:</strong>
        <p style="font-size: 11px; color: #64748B; margin: 0; line-height: 1.4;">
          Si abre su lista en Brevo (ej. <em>LISTA ENCUESTA</em> o <em>BD 2026</em>), presione <kbd>Ctrl+A</kbd>, copie y pegue aquí directamente.
        </p>
      </div>
    </div>
  </div>

</div>

<script>
  function toggleNewGroupInput(val) {
    const container = document.getElementById('new_group_container');
    const input = document.getElementById('new_group_name');
    if (val === '-1') {
      container.style.display = 'block';
      input.required = true;
      input.focus();
    } else {
      container.style.display = 'none';
      input.required = false;
    }
  }

  function handleFileSelected(input) {
    if (input.files && input.files[0]) {
      document.getElementById('file_selected_name').innerText = '✓ Archivo cargado: ' + input.files[0].name;
    }
  }
</script>
@endsection
