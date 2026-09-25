@extends('layouts.app')

@section('title', 'Grupos & Segmentación | Suitable')

@section('content')
<div class="page-header">
  <div class="page-title-group">
    <h1>Grupos &amp; Segmentos de Clínicas</h1>
    <p class="page-subtitle">Organice sus prospectos y clientes de WooCommerce para impactarlos con campañas personalizadas de vestuario clínico</p>
  </div>

  <div class="header-actions">
    <a href="{{ route('woocommerce.index') }}" class="btn btn-secondary btn-sm" title="Ver sincronización de WooCommerce">
      🛒 Pedidos WooCommerce ({{ $wooCustomers->count() }} clientes)
    </a>
    <a href="{{ route('import.index') }}" class="btn btn-secondary btn-sm">📥 Importar Brevo/CSV</a>
    <button type="button" class="btn btn-primary btn-sm" onclick="openModal('modal-new-group')">
      + Crear Nuevo Grupo
    </button>
  </div>
</div>

<!-- GROUPS GRID -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px;">
  @forelse ($groups as $g)
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

      <div style="border-top: 1px solid #F1F5F9; padding-top: 14px; margin-top: 10px; display: flex; flex-direction: column; gap: 10px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <a href="{{ route('clients.index', ['group' => $g->id]) }}" style="font-size: 13px; font-weight: 700; color: #1E8888; text-decoration: none;">
            Ver miembros ({{ $g->clients_count }}) →
          </a>
          <button type="button" class="btn btn-secondary btn-sm" onclick="openAddWcModal({{ $g->id }}, '{{ addslashes($g->name) }}')" style="font-size: 11.5px; padding: 4px 10px;">
            🛒 + Clientes Woo
          </button>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <a href="{{ route('campaigns.create', ['group_id' => $g->id]) }}" class="btn btn-primary btn-sm" style="width: 100%; text-align: center;">
            🚀 Crear Campaña para este Grupo
          </a>
        </div>
      </div>
    </div>
  @empty
    <div style="grid-column: 1 / -1; background: #FFFFFF; border: 1px dashed #CBD5E1; border-radius: 12px; padding: 48px 20px; text-align: center;">
      <div style="font-size: 40px; margin-bottom: 12px;">👥</div>
      <h3 style="font-size: 17px; font-weight: 800; color: #0F172A; margin: 0 0 6px 0;">No tienes grupos creados todavía</h3>
      <p style="font-size: 13px; color: #64748B; max-width: 460px; margin: 0 auto 20px;">
        Crea tu primer grupo para agrupar clínicas o clientes de WooCommerce y enviar campañas automatizadas con IA.
      </p>
      <button type="button" class="btn btn-primary" onclick="openModal('modal-new-group')">
        + Crear Nuevo Grupo Ahora
      </button>
    </div>
  @endforelse
</div>

<!-- MODAL NEW GROUP -->
<div class="modal-backdrop" id="modal-new-group" style="display: none; align-items: center; justify-content: center; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 9999; padding: 20px; backdrop-filter: blur(4px);">
  <div class="modal-box" style="max-width: 760px; width: 100%; max-height: 90vh; display: flex; flex-direction: column; background: #FFFFFF; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); overflow: hidden;">
    
    <div class="modal-header" style="padding: 18px 24px; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center;">
      <div>
        <h3 class="modal-title" style="margin: 0; font-size: 17px; font-weight: 800; color: #0F172A;">Crear Nuevo Segmento de Contactos</h3>
        <p style="margin: 2px 0 0 0; font-size: 12px; color: #64748B;">Asigna un nombre e incorpora clientes reales de WooCommerce o del CRM</p>
      </div>
      <button type="button" class="modal-close" onclick="closeModal('modal-new-group')" style="background: none; border: none; font-size: 24px; color: #94A3B8; cursor: pointer;">&times;</button>
    </div>

    <form id="form-new-group" onsubmit="saveGroup(event)" style="display: flex; flex-direction: column; overflow: hidden; flex: 1;">
      <div class="modal-body" style="padding: 22px 24px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 18px;">
        
        <!-- DATOS DEL GRUPO -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px;">
          <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Nombre del Grupo *</label>
            <input type="text" name="name" class="form-control" placeholder="Ej. Clientes Compradores WooCommerce" required style="width: 100%; padding: 9px 12px; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 13.5px;">
          </div>
          <div class="form-group" style="margin: 0;">
            <label class="form-label" style="font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Color Identificador</label>
            <input type="color" name="color" class="form-control" value="#1E8888" style="width: 100%; height: 40px; padding: 2px; border: 1px solid #CBD5E1; border-radius: 6px; cursor: pointer;">
          </div>
        </div>

        <div class="form-group" style="margin: 0;">
          <label class="form-label" style="font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">Descripción del Segmento</label>
          <input type="text" name="description" class="form-control" placeholder="Características del segmento (ej. compradores de uniformes Flex 4-Way, reposición 114 días)" style="width: 100%; padding: 9px 12px; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 13px;">
        </div>

        <!-- SELECTOR DE CONTACTOS: TABS WOOCOMMERCE / CRM -->
        <div style="border: 1px solid #E2E8F0; border-radius: 8px; overflow: hidden; background: #F8FAFC;">
          <div style="display: flex; border-bottom: 1px solid #E2E8F0; background: #F1F5F9;">
            <button type="button" id="tab-btn-woo" onclick="switchContactTab('woo')" style="flex: 1; padding: 10px 16px; border: none; background: #FFFFFF; font-weight: 800; font-size: 13px; color: #1E8888; border-bottom: 2px solid #1E8888; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
              🛒 Clientes WooCommerce ({{ $wooCustomers->count() }})
            </button>
            <button type="button" id="tab-btn-crm" onclick="switchContactTab('crm')" style="flex: 1; padding: 10px 16px; border: none; background: #F1F5F9; font-weight: 700; font-size: 13px; color: #64748B; border-bottom: 2px solid transparent; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
              🏥 Contactos CRM Clínicas ({{ $crmClients->count() }})
            </button>
          </div>

          <!-- PANEL 1: CLIENTES WOOCOMMERCE -->
          <div id="tab-panel-woo" style="padding: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
              <span style="font-size: 12px; color: #475569; font-weight: 600;">
                Selecciona clientes con compras reales en Suitable.cl:
              </span>
              <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="selectAllWoo(true)" style="font-size: 11.5px; padding: 4px 10px;">
                  ☑️ Seleccionar Todos ({{ $wooCustomers->count() }})
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="selectAllWoo(false)" style="font-size: 11.5px; padding: 4px 10px;">
                  ⏹️ Deseleccionar
                </button>
              </div>
            </div>

            <div style="margin-bottom: 10px;">
              <input type="text" id="search-woo" onkeyup="filterWooList()" placeholder="🔍 Filtrar por nombre, correo o comuna..." style="width: 100%; padding: 7px 12px; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 12.5px;">
            </div>

            @if($wooCustomers->count() > 0)
              <div id="woo-customer-list" style="max-height: 220px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; padding-right: 4px;">
                @foreach ($wooCustomers as $wc)
                  <label class="woo-customer-item" style="display: flex; align-items: center; justify-content: space-between; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 6px; padding: 8px 12px; cursor: pointer; transition: all 0.15s ease;" data-search="{{ strtolower($wc->customer_name . ' ' . $wc->customer_email . ' ' . $wc->customer_city) }}">
                    <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                      <input type="checkbox" name="wc_emails[]" value="{{ $wc->customer_email }}" onchange="updateSelectedCounter()" style="width: 16px; height: 16px; accent-color: #1E8888; cursor: pointer;">
                      <div style="min-width: 0;">
                        <div style="font-size: 13px; font-weight: 700; color: #0F172A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                          {{ $wc->customer_name }}
                        </div>
                        <div style="font-size: 11.5px; color: #64748B;">
                          {{ $wc->customer_email }}
                        </div>
                      </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0; margin-left: 10px;">
                      @if($wc->customer_city)
                        <span class="badge" style="background: #F1F5F9; color: #475569; font-size: 11px;">
                          📍 {{ $wc->customer_city }}
                        </span>
                      @endif
                      <span class="badge" style="background: #ECFDF5; color: #059669; font-size: 11px; font-weight: 700;">
                        ${{ number_format($wc->total_spent, 0, ',', '.') }} CLP ({{ $wc->orders_count }} ord.)
                      </span>
                    </div>
                  </label>
                @endforeach
              </div>
            @else
              <div style="padding: 24px; text-align: center; color: #64748B; font-size: 13px;">
                🛍️ No se han sincronizado clientes de WooCommerce todavía.
                <div style="margin-top: 8px;">
                  <a href="{{ route('woocommerce.index') }}" style="color: #1E8888; font-weight: 700; text-decoration: none;">
                    → Ir a WooCommerce Sync para sincronizar pedidos reales
                  </a>
                </div>
              </div>
            @endif
          </div>

          <!-- PANEL 2: CLIENTES CRM -->
          <div id="tab-panel-crm" style="padding: 14px; display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
              <span style="font-size: 12px; color: #475569; font-weight: 600;">
                Selecciona prospectos y clínicas de la base CRM:
              </span>
              <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="selectAllCrm(true)" style="font-size: 11.5px; padding: 4px 10px;">
                  ☑️ Seleccionar Todos
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="selectAllCrm(false)" style="font-size: 11.5px; padding: 4px 10px;">
                  ⏹️ Deseleccionar
                </button>
              </div>
            </div>

            <div style="margin-bottom: 10px;">
              <input type="text" id="search-crm" onkeyup="filterCrmList()" placeholder="🔍 Filtrar por clínica, contacto o comuna..." style="width: 100%; padding: 7px 12px; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 12.5px;">
            </div>

            <div id="crm-customer-list" style="max-height: 220px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; padding-right: 4px;">
              @foreach ($crmClients as $c)
                <label class="crm-customer-item" style="display: flex; align-items: center; justify-content: space-between; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 6px; padding: 8px 12px; cursor: pointer;" data-search="{{ strtolower($c->empresa . ' ' . $c->contacto_nombre . ' ' . $c->email . ' ' . $c->region_comuna) }}">
                  <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                    <input type="checkbox" name="client_ids[]" value="{{ $c->id }}" onchange="updateSelectedCounter()" style="width: 16px; height: 16px; accent-color: #1E8888; cursor: pointer;">
                    <div style="min-width: 0;">
                      <div style="font-size: 13px; font-weight: 700; color: #0F172A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $c->empresa }}
                      </div>
                      <div style="font-size: 11.5px; color: #64748B;">
                        {{ $c->contacto_nombre }} • {{ $c->email }}
                      </div>
                    </div>
                  </div>
                  @if($c->region_comuna)
                    <span class="badge" style="background: #F1F5F9; color: #475569; font-size: 11px; flex-shrink: 0; margin-left: 8px;">
                      📍 {{ $c->region_comuna }}
                    </span>
                  @endif
                </label>
              @endforeach
            </div>
          </div>
        </div>

      </div>

      <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center; background: #F8FAFC;">
        <div style="font-size: 13px; color: #475569; font-weight: 700;">
          Seleccionados: <span id="counter-total" style="color: #1E8888; font-weight: 800;">0</span> contactos
        </div>
        <div style="display: flex; gap: 10px;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('modal-new-group')">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btn-submit-group">
            🚀 Crear Grupo con <span id="counter-btn">0</span> Contactos →
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- MODAL ADD WOO CLIENTS TO EXISTING GROUP -->
<div class="modal-backdrop" id="modal-add-wc" style="display: none; align-items: center; justify-content: center; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 9999; padding: 20px; backdrop-filter: blur(4px);">
  <div class="modal-box" style="max-width: 720px; width: 100%; max-height: 90vh; display: flex; flex-direction: column; background: #FFFFFF; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden;">
    
    <div class="modal-header" style="padding: 18px 24px; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center;">
      <div>
        <h3 class="modal-title" style="margin: 0; font-size: 17px; font-weight: 800; color: #0F172A;">
          Agregar Clientes WooCommerce a <span id="existing-group-name" style="color: #1E8888;"></span>
        </h3>
        <p style="margin: 2px 0 0 0; font-size: 12px; color: #64748B;">Incorpora compradores de la tienda online directo a este grupo</p>
      </div>
      <button type="button" class="modal-close" onclick="closeModal('modal-add-wc')" style="background: none; border: none; font-size: 24px; color: #94A3B8; cursor: pointer;">&times;</button>
    </div>

    <form id="form-add-wc" onsubmit="submitAddWc(event)" style="display: flex; flex-direction: column; overflow: hidden; flex: 1;">
      <input type="hidden" id="existing-group-id" value="">
      
      <div class="modal-body" style="padding: 20px 24px; overflow-y: auto; flex: 1;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
          <input type="text" id="search-wc-existing" onkeyup="filterExistingWcList()" placeholder="🔍 Filtrar clientes por nombre, correo o comuna..." style="flex: 1; min-width: 200px; padding: 7px 12px; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 12.5px;">
          <div style="display: flex; gap: 8px;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="selectAllExistingWc(true)" style="font-size: 11.5px; padding: 4px 10px;">
              ☑️ Todos
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="selectAllExistingWc(false)" style="font-size: 11.5px; padding: 4px 10px;">
              ⏹️ Ninguno
            </button>
          </div>
        </div>

        <div id="existing-wc-list" style="max-height: 320px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px;">
          @foreach ($wooCustomers as $wc)
            <label class="existing-wc-item" style="display: flex; align-items: center; justify-content: space-between; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px; padding: 8px 12px; cursor: pointer;" data-search="{{ strtolower($wc->customer_name . ' ' . $wc->customer_email . ' ' . $wc->customer_city) }}">
              <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                <input type="checkbox" name="existing_wc_emails[]" value="{{ $wc->customer_email }}" onchange="updateExistingWcCounter()" style="width: 16px; height: 16px; accent-color: #1E8888; cursor: pointer;">
                <div style="min-width: 0;">
                  <div style="font-size: 13px; font-weight: 700; color: #0F172A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    {{ $wc->customer_name }}
                  </div>
                  <div style="font-size: 11.5px; color: #64748B;">
                    {{ $wc->customer_email }}
                  </div>
                </div>
              </div>
              <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0; margin-left: 10px;">
                @if($wc->customer_city)
                  <span class="badge" style="background: #E2E8F0; color: #475569; font-size: 11px;">
                    📍 {{ $wc->customer_city }}
                  </span>
                @endif
                <span class="badge" style="background: #ECFDF5; color: #059669; font-size: 11px; font-weight: 700;">
                  ${{ number_format($wc->total_spent, 0, ',', '.') }} CLP
                </span>
              </div>
            </label>
          @endforeach
        </div>
      </div>

      <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center; background: #F8FAFC;">
        <div style="font-size: 13px; color: #475569; font-weight: 700;">
          Seleccionados: <span id="counter-existing-wc" style="color: #1E8888; font-weight: 800;">0</span> clientes
        </div>
        <div style="display: flex; gap: 10px;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add-wc')">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btn-submit-existing-wc">
            ➕ Agregar al Grupo
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  function openModal(id) { 
    document.getElementById(id).style.display = 'flex'; 
    updateSelectedCounter();
  }
  function closeModal(id) { 
    document.getElementById(id).style.display = 'none'; 
  }

  function switchContactTab(tab) {
    const btnWoo = document.getElementById('tab-btn-woo');
    const btnCrm = document.getElementById('tab-btn-crm');
    const panelWoo = document.getElementById('tab-panel-woo');
    const panelCrm = document.getElementById('tab-panel-crm');

    if (tab === 'woo') {
      btnWoo.style.background = '#FFFFFF';
      btnWoo.style.color = '#1E8888';
      btnWoo.style.borderBottom = '2px solid #1E8888';
      btnCrm.style.background = '#F1F5F9';
      btnCrm.style.color = '#64748B';
      btnCrm.style.borderBottom = '2px solid transparent';
      panelWoo.style.display = 'block';
      panelCrm.style.display = 'none';
    } else {
      btnCrm.style.background = '#FFFFFF';
      btnCrm.style.color = '#1E8888';
      btnCrm.style.borderBottom = '2px solid #1E8888';
      btnWoo.style.background = '#F1F5F9';
      btnWoo.style.color = '#64748B';
      btnWoo.style.borderBottom = '2px solid transparent';
      panelCrm.style.display = 'block';
      panelWoo.style.display = 'none';
    }
  }

  function updateSelectedCounter() {
    const wooChecks = document.querySelectorAll('input[name="wc_emails[]"]:checked');
    const crmChecks = document.querySelectorAll('input[name="client_ids[]"]:checked');
    const total = wooChecks.length + crmChecks.length;

    const counterTotal = document.getElementById('counter-total');
    const counterBtn = document.getElementById('counter-btn');

    if (counterTotal) counterTotal.innerText = total;
    if (counterBtn) counterBtn.innerText = total;
  }

  function selectAllWoo(checked) {
    const checks = document.querySelectorAll('input[name="wc_emails[]"]');
    checks.forEach(c => {
      // Only select if not hidden by search filter
      if (c.closest('.woo-customer-item').style.display !== 'none' || !checked) {
        c.checked = checked;
      }
    });
    updateSelectedCounter();
  }

  function selectAllCrm(checked) {
    const checks = document.querySelectorAll('input[name="client_ids[]"]');
    checks.forEach(c => {
      if (c.closest('.crm-customer-item').style.display !== 'none' || !checked) {
        c.checked = checked;
      }
    });
    updateSelectedCounter();
  }

  function filterWooList() {
    const term = document.getElementById('search-woo').value.toLowerCase().trim();
    const items = document.querySelectorAll('.woo-customer-item');
    items.forEach(el => {
      const search = el.getAttribute('data-search') || '';
      el.style.display = search.includes(term) ? 'flex' : 'none';
    });
  }

  function filterCrmList() {
    const term = document.getElementById('search-crm').value.toLowerCase().trim();
    const items = document.querySelectorAll('.crm-customer-item');
    items.forEach(el => {
      const search = el.getAttribute('data-search') || '';
      el.style.display = search.includes(term) ? 'flex' : 'none';
    });
  }

  async function saveGroup(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const btn = document.getElementById('btn-submit-group');

    btn.disabled = true;
    btn.innerText = 'Guardando grupo y contactos...';

    try {
      const res = await fetch("{{ route('groups.store') }}", {
        method: 'POST',
        headers: { 
          'X-CSRF-TOKEN': token, 
          'Accept': 'application/json' 
        },
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => location.reload(), 700);
      } else {
        showToast(data.message || data.error || 'Error al crear grupo', 'error');
        btn.disabled = false;
        btn.innerText = '🚀 Crear Grupo →';
      }
    } catch (err) {
      showToast('Error de conexión con el servidor', 'error');
      btn.disabled = false;
      btn.innerText = '🚀 Crear Grupo →';
    }
  }

  // --- MODAL PARA AGREGAR A GRUPO EXISTENTE ---
  function openAddWcModal(groupId, groupName) {
    document.getElementById('existing-group-id').value = groupId;
    document.getElementById('existing-group-name').innerText = groupName;
    selectAllExistingWc(false);
    openModal('modal-add-wc');
  }

  function updateExistingWcCounter() {
    const checked = document.querySelectorAll('input[name="existing_wc_emails[]"]:checked');
    const el = document.getElementById('counter-existing-wc');
    if (el) el.innerText = checked.length;
  }

  function selectAllExistingWc(checked) {
    const checks = document.querySelectorAll('input[name="existing_wc_emails[]"]');
    checks.forEach(c => {
      if (c.closest('.existing-wc-item').style.display !== 'none' || !checked) {
        c.checked = checked;
      }
    });
    updateExistingWcCounter();
  }

  function filterExistingWcList() {
    const term = document.getElementById('search-wc-existing').value.toLowerCase().trim();
    const items = document.querySelectorAll('.existing-wc-item');
    items.forEach(el => {
      const search = el.getAttribute('data-search') || '';
      el.style.display = search.includes(term) ? 'flex' : 'none';
    });
  }

  async function submitAddWc(e) {
    e.preventDefault();
    const groupId = document.getElementById('existing-group-id').value;
    const checked = Array.from(document.querySelectorAll('input[name="existing_wc_emails[]"]:checked')).map(c => c.value);
    
    if (checked.length === 0) {
      showToast('Selecciona al menos un cliente de WooCommerce para agregar al grupo.', 'warning');
      return;
    }

    const token = document.querySelector('meta[name="csrf-token"]').content;
    const btn = document.getElementById('btn-submit-existing-wc');
    btn.disabled = true;
    btn.innerText = 'Agregando clientes...';

    try {
      const url = "{{ url('/groups') }}/" + groupId + "/add-wc-clients";
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ wc_emails: checked })
      });

      const data = await res.json();
      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => location.reload(), 700);
      } else {
        showToast(data.message || 'Error al agregar clientes', 'error');
        btn.disabled = false;
        btn.innerText = '➕ Agregar al Grupo';
      }
    } catch (err) {
      showToast('Error de conexión', 'error');
      btn.disabled = false;
      btn.innerText = '➕ Agregar al Grupo';
    }
  }
</script>
@endsection
