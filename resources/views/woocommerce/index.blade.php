@extends('layouts.app')

@section('title', 'WooCommerce Sync & Asistente | Suitable')

@section('content')
<div class="page-header" style="margin-bottom: 24px;">
  <div class="page-title-group">
    <div style="display: flex; align-items: center; gap: 10px;">
      <h1 style="margin: 0;">Integración WooCommerce Suitable.cl</h1>
      @if($totalOrders > 0)
        <span class="badge" style="background:#ECFDF5; color:#059669; font-weight:800; font-size:12px;">
          🟢 CONECTADO ({{ $totalOrders }} PEDIDOS REALES)
        </span>
      @else
        <span class="badge" style="background:#FEF3C7; color:#92400E; font-weight:800; font-size:12px;">
          🟡 ASISTENTE DE CONEXIÓN REQUERIDO
        </span>
      @endif
    </div>
    <p class="page-subtitle" style="margin-top: 4px;">
      Sincronización en tiempo real de órdenes, clientes y facturación con WordPress en <code>public_html</code> (BD: <strong>{{ $mysqlDb }}</strong>)
    </p>
  </div>
  <div class="header-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleWizard()">
      ⚙️ Asistente de Conexión
    </button>
    <button type="button" class="btn btn-danger btn-sm" onclick="confirmPurgeDemo()" style="background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5;">
      🗑️ Limpiar / Purgar Datos Demo
    </button>
    <button type="button" class="btn btn-primary btn-sm" onclick="syncWooCommerce()" id="btn-sync-main">
      🔄 Sincronizar Ahora
    </button>
  </div>
</div>

<!-- METRICS BAR -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
  <div class="table-card" style="padding: 18px;">
    <div style="font-size: 11.5px; font-weight: 700; color: #64748B; text-transform: uppercase;">Facturación WooCommerce</div>
    <div style="font-size: 26px; font-weight: 800; color: #059669; margin: 4px 0;" id="metric-revenue">
      ${{ number_format($totalRevenue, 0, ',', '.') }}
    </div>
    <div style="font-size: 11.5px; color: #64748B;">Ventas reales sincronizadas (CLP)</div>
  </div>

  <div class="table-card" style="padding: 18px;">
    <div style="font-size: 11.5px; font-weight: 700; color: #64748B; text-transform: uppercase;">Pedidos en Tienda</div>
    <div style="font-size: 26px; font-weight: 800; color: #1E8888; margin: 4px 0;" id="metric-orders">
      {{ $totalOrders }}
    </div>
    <div style="font-size: 11.5px; color: #1E8888; font-weight: 600;">Órdenes procesadas</div>
  </div>

  <div class="table-card" style="padding: 18px;">
    <div style="font-size: 11.5px; font-weight: 700; color: #64748B; text-transform: uppercase;">Clientes Únicos</div>
    <div style="font-size: 26px; font-weight: 800; color: #0F172A; margin: 4px 0;" id="metric-customers">
      {{ $uniqueCustomers }}
    </div>
    <div style="font-size: 11.5px; color: #64748B;">Compradores en Suitable.cl</div>
  </div>

  <div class="table-card" style="padding: 18px;">
    <div style="font-size: 11.5px; font-weight: 700; color: #64748B; text-transform: uppercase;">Base de Datos WordPress</div>
    <div style="font-size: 20px; font-weight: 800; color: #475569; margin: 6px 0; font-family: monospace;">
      {{ $mysqlDb }} ({{ $tablePrefix }})
    </div>
    <div style="font-size: 11.5px; color: #64748B;">
      Última sinc: {{ $lastSync ? \Carbon\Carbon::parse($lastSync)->format('d/m/Y H:i') : 'Nunca' }}
    </div>
  </div>
</div>

<!-- CONNECTION WIZARD ACCORDION / CARD -->
<div id="wizard-container" class="table-card" style="padding: 24px; margin-bottom: 24px; border: 2px solid #1E8888; background: #FFFFFF; {{ $totalOrders > 0 ? 'display: none;' : '' }}">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 18px;">
    <div>
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="background: #E6F4F4; color: #146161; font-weight: 800; font-size: 11px; padding: 4px 8px; border-radius: 4px; text-transform: uppercase;">
          Asistente de Integración
        </span>
        <h2 style="font-size: 18px; font-weight: 800; color: #0F172A; margin: 0;">
          Conectar con Base de Datos de Suitable.cl en <code>public_html</code>
        </h2>
      </div>
      <p style="font-size: 13px; color: #64748B; margin-top: 6px; margin-bottom: 0;">
        Conexión directa MySQL en el mismo servidor hacia la base de datos de tu tienda WordPress (<code>suitable_wp372</code> con prefijo <code>wp8q_</code>).
      </p>
    </div>
    @if($totalOrders > 0)
      <button type="button" class="btn btn-secondary btn-sm" onclick="toggleWizard()">
        Ocultar Asistente ▲
      </button>
    @endif
  </div>

  <!-- AUTO DETECT BANNER -->
  @if($wpConfigFound)
    <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
      <div>
        <strong style="color: #166534; font-size: 13.5px;">✨ Archivo de WordPress detectado en el servidor</strong>
        <div style="font-size: 12px; color: #15803D; margin-top: 2px;">
          Se encontró <code>public_html/wp-config.php</code>. Puedes cargar las credenciales exactas con 1 clic.
        </div>
      </div>
      <button type="button" class="btn btn-sm" onclick="detectWpConfig()" style="background: #16A34A; color: white; border: none; font-weight: 700;">
        🔍 Auto-cargar desde public_html
      </button>
    </div>
  @endif

  <!-- WIZARD FORM -->
  <form id="wizard-form" onsubmit="event.preventDefault(); saveAndTestConnection();">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 18px;">
      <div>
        <label class="form-label" style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">
          Servidor / Host MySQL
        </label>
        <input type="text" id="db_host" name="db_host" class="form-control" value="{{ $mysqlHost }}" placeholder="localhost" required>
        <span style="font-size: 11px; color: #94A3B8;">Generalmente <code>localhost</code> o <code>127.0.0.1</code></span>
      </div>

      <div>
        <label class="form-label" style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">
          Puerto MySQL
        </label>
        <input type="text" id="db_port" name="db_port" class="form-control" value="{{ $mysqlPort }}" placeholder="3306" required>
        <span style="font-size: 11px; color: #94A3B8;">Puerto estándar: 3306</span>
      </div>

      <div>
        <label class="form-label" style="font-weight: 700; font-size: 12.5px; color: #1E8888; margin-bottom: 6px; display: block;">
          Base de Datos de WordPress
        </label>
        <input type="text" id="db_name" name="db_name" class="form-control" value="{{ $mysqlDb }}" style="border-color: #1E8888; font-weight: 700;" placeholder="suitable_wp372" required>
        <span style="font-size: 11px; color: #1E8888; font-weight: 600;">Confirmada en phpMyAdmin: <code>suitable_wp372</code></span>
      </div>

      <div>
        <label class="form-label" style="font-weight: 700; font-size: 12.5px; color: #1E8888; margin-bottom: 6px; display: block;">
          Prefijo de Tablas
        </label>
        <input type="text" id="table_prefix" name="table_prefix" class="form-control" value="{{ ($tablePrefix === 'wp_' || empty($tablePrefix)) ? 'wp8q_' : $tablePrefix }}" style="border-color: #1E8888; font-weight: 700;" placeholder="wp8q_" required>
        <span style="font-size: 11px; color: #1E8888; font-weight: 600;">Confirmado en tu phpMyAdmin: <code>wp8q_</code></span>
      </div>

      <div>
        <label class="form-label" style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">
          Usuario MySQL
        </label>
        <input type="text" id="db_user" name="db_user" class="form-control" value="{{ $mysqlUser ?: 'suitable_intranetuser' }}" placeholder="suitable_intranetuser" required>
        <span style="font-size: 11px; color: #64748B;">Usuario asignado en cPanel</span>
      </div>

      <div>
        <label class="form-label" style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">
          Contraseña MySQL
        </label>
        <input type="password" id="db_pass" name="db_pass" class="form-control" value="{{ $mysqlPass }}" placeholder="Dejar en blanco para usar la de la intranet">
        <span style="font-size: 11px; color: #64748B;">Si se deja en blanco, usa automáticamente la clave MySQL del .env</span>
      </div>
    </div>

    <!-- LIVE TEST RESULT BOX -->
    <div id="test-result-box" style="display: none; padding: 14px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 13px;"></div>

    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
      <button type="button" class="btn btn-secondary" onclick="testConnection()" id="btn-test">
        🧪 Probar Conexión
      </button>
      <button type="button" class="btn btn-secondary" onclick="saveSettings()" id="btn-save">
        💾 Guardar Ajustes
      </button>
      <button type="button" class="btn btn-primary" onclick="syncWooCommerce()" id="btn-sync-wizard">
        🚀 Guardar y Sincronizar Pedidos Reales
      </button>
    </div>
  </form>

  <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 14px; margin-top: 18px; font-size: 12px; color: #475569; line-height: 1.5;">
    <strong>💡 Permisos en cPanel:</strong> Si al probar conexión aparece <em>"Access denied"</em>, simplemente ingresa a <strong>cPanel → Bases de Datos MySQL → Añadir usuario a la base de datos</strong>, selecciona el usuario (ej: <code>suitable_intranetuser</code>) y agrégalo a la base de datos <code>suitable_wp372</code> con privilegios de lectura (SELECT).
  </div>
</div>

<!-- ORDERS TABLE -->
<div class="table-card" style="padding: 22px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
    <div>
      <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">Historial de Pedidos Reales Sincronizados</h3>
      <div style="font-size: 12px; color: #64748B; margin-top: 2px;">
        Mostrando {{ $orders->count() }} de {{ $totalOrders }} órdenes registradas en MySQL
      </div>
    </div>
    @if($totalOrders > 0)
      <span class="badge" style="background:#ECFDF5; color:#059669; font-weight:800;">
        Conexión Activa con Suitable.cl
      </span>
    @endif
  </div>

  @if($orders->count() > 0)
    <table class="crm-table">
      <thead>
        <tr>
          <th>N° Orden</th>
          <th>Cliente</th>
          <th>Ciudad / Comuna</th>
          <th>Monto Total</th>
          <th>Ítems</th>
          <th>Medio de Pago</th>
          <th>Estado</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody id="orders-tbody">
        @foreach($orders as $ord)
          <tr>
            <td>
              <strong style="color: #1E8888;">#{{ $ord->wc_order_id }}</strong>
            </td>
            <td>
              <div style="font-weight: 700; color: #0F172A;">{{ $ord->customer_name }}</div>
              <div style="font-size: 11px; color: #64748B;">{{ $ord->customer_email }}</div>
            </td>
            <td>
              <span style="font-size: 12px; color: #475569;">{{ $ord->customer_city ?: 'Santiago' }}</span>
            </td>
            <td style="font-weight: 800; color: #059669; font-size: 13.5px;">
              ${{ number_format($ord->total_amount, 0, ',', '.') }}
            </td>
            <td>
              <span class="badge" style="background: #F1F5F9; color: #334155; font-size: 11px;">
                {{ $ord->items_count }} {{ $ord->items_count == 1 ? 'prenda' : 'prendas' }}
              </span>
            </td>
            <td style="font-size: 11.5px; color: #64748B;">
              {{ $ord->payment_method ?: 'Webpay Plus' }}
            </td>
            <td>
              @php
                $st = strtolower($ord->status);
                $badgeStyle = match($st) {
                  'completed' => 'background:#ECFDF5; color:#059669;',
                  'processing' => 'background:#EFF6FF; color:#1D4ED8;',
                  'on-hold', 'pending' => 'background:#FEF3C7; color:#B45309;',
                  'cancelled', 'failed' => 'background:#FEE2E2; color:#B91C1C;',
                  default => 'background:#F1F5F9; color:#475569;'
                };
              @endphp
              <span class="badge" style="{{ $badgeStyle }} font-weight: 700; text-transform: capitalize;">
                {{ $ord->status }}
              </span>
            </td>
            <td style="font-size: 12px; color: #64748B; white-space: nowrap;">
              {{ \Carbon\Carbon::parse($ord->date_created)->format('d/m/Y H:i') }}
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <div style="margin-top: 20px;">
      {{ $orders->links() }}
    </div>
  @else
    <!-- EMPTY STATE -->
    <div style="text-align: center; padding: 48px 20px;">
      <div style="font-size: 42px; margin-bottom: 12px;">🛍️</div>
      <h3 style="font-size: 17px; font-weight: 800; color: #0F172A; margin: 0 0 6px 0;">
        No hay pedidos registrados (Data Demo Eliminada)
      </h3>
      <p style="font-size: 13px; color: #64748B; max-width: 480px; margin: 0 auto 20px; line-height: 1.5;">
        La base de datos se encuentra completamente limpia. Utiliza el Asistente arriba para probar la conexión con la base de datos <strong>suitable_wp372</strong> y sincronizar tus órdenes reales.
      </p>
      <button type="button" class="btn btn-primary" onclick="showWizardAndScroll()">
        🚀 Configurar Asistente de Conexión
      </button>
    </div>
  @endif
</div>

<script>
  function toggleWizard() {
    const w = document.getElementById('wizard-container');
    w.style.display = (w.style.display === 'none' || w.style.display === '') ? 'block' : 'none';
  }

  function showWizardAndScroll() {
    const w = document.getElementById('wizard-container');
    w.style.display = 'block';
    w.scrollIntoView({ behavior: 'smooth' });
  }

  async function detectWpConfig() {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    showToast('Buscando archivo wp-config.php en el servidor...', 'info');

    try {
      const res = await fetch("{{ route('woocommerce.detect_wp') }}", {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
      });
      const data = await res.json();

      if (data.success) {
        document.getElementById('db_name').value = data.db_name;
        document.getElementById('db_user').value = data.db_user;
        if (data.db_pass) document.getElementById('db_pass').value = data.db_pass;
        document.getElementById('db_host').value = data.db_host;
        document.getElementById('table_prefix').value = data.db_prefix;

        showToast('¡Configuración detectada automáticamente!', 'success');
        testConnection();
      } else {
        showToast(data.message, 'warning');
      }
    } catch (e) {
      showToast('Error al auto-detectar configuración', 'error');
    }
  }

  async function testConnection() {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const btn = document.getElementById('btn-test');
    const resultBox = document.getElementById('test-result-box');

    btn.disabled = true;
    btn.innerText = '🧪 Probando conexión...';
    resultBox.style.display = 'block';
    resultBox.style.background = '#EFF6FF';
    resultBox.style.border = '1px solid #BFDBFE';
    resultBox.style.color = '#1E40AF';
    resultBox.innerHTML = '⏳ Conectando a MySQL y verificando tablas con prefijo <code>' + document.getElementById('table_prefix').value + '</code>...';

    const payload = {
      db_host: document.getElementById('db_host').value,
      db_port: document.getElementById('db_port').value,
      db_name: document.getElementById('db_name').value,
      db_user: document.getElementById('db_user').value,
      db_pass: document.getElementById('db_pass').value,
      table_prefix: document.getElementById('table_prefix').value
    };

    try {
      const res = await fetch("{{ route('woocommerce.test_connection') }}", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });
      
      let data;
      try {
        data = await res.json();
      } catch (jsonErr) {
        throw new Error('Respuesta inesperada del servidor (HTTP ' + res.status + ')');
      }

      if (data.success) {
        if (data.prefix) {
          document.getElementById('table_prefix').value = data.prefix;
        }
        resultBox.style.background = '#F0FDF4';
        resultBox.style.border = '1px solid #BBF7D0';
        resultBox.style.color = '#166534';
        resultBox.innerHTML = `<strong>✅ ${data.message}</strong><br><span style="font-size: 12px;">Base: <code>${data.database}</code> | Prefijo: <code>${data.prefix}</code> | Pedidos listos para importar: <strong>${data.orders_found}</strong></span>`;
        showToast('Conexión con WooCommerce exitosa (' + data.orders_found + ' pedidos)', 'success');
      } else {
        resultBox.style.background = '#FEF2F2';
        resultBox.style.border = '1px solid #FECACA';
        resultBox.style.color = '#991B1B';
        resultBox.innerHTML = `<strong>❌ Error:</strong> ${data.message}`;
        showToast(data.message, 'error');
      }
    } catch (e) {
      resultBox.style.background = '#FEF2F2';
      resultBox.style.border = '1px solid #FECACA';
      resultBox.style.color = '#991B1B';
      resultBox.innerHTML = `<strong>❌ Error:</strong> ${e.message}`;
      showToast('Error de conexión: ' + e.message, 'error');
    } finally {
      btn.disabled = false;
      btn.innerText = '🧪 Probar Conexión';
    }
  }

  async function saveSettings() {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const btn = document.getElementById('btn-save');

    btn.disabled = true;
    btn.innerText = 'Guardando...';

    const payload = {
      db_host: document.getElementById('db_host').value,
      db_port: document.getElementById('db_port').value,
      db_name: document.getElementById('db_name').value,
      db_user: document.getElementById('db_user').value,
      db_pass: document.getElementById('db_pass').value,
      table_prefix: document.getElementById('table_prefix').value
    };

    try {
      const res = await fetch("{{ route('woocommerce.save_settings') }}", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      showToast(data.message, data.success ? 'success' : 'error');
    } catch (e) {
      showToast('Error al guardar ajustes', 'error');
    } finally {
      btn.disabled = false;
      btn.innerText = '💾 Guardar Ajustes';
    }
  }

  async function syncWooCommerce() {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const btnMain = document.getElementById('btn-sync-main');
    const btnWiz = document.getElementById('btn-sync-wizard');

    if (btnMain) { btnMain.disabled = true; btnMain.innerText = '⏳ Sincronizando...'; }
    if (btnWiz) { btnWiz.disabled = true; btnWiz.innerText = '⏳ Sincronizando...'; }

    showToast('Iniciando sincronización con base de datos de Suitable.cl...', 'info');

    const payload = {
      db_host: document.getElementById('db_host')?.value,
      db_port: document.getElementById('db_port')?.value,
      db_name: document.getElementById('db_name')?.value,
      db_user: document.getElementById('db_user')?.value,
      db_pass: document.getElementById('db_pass')?.value,
      table_prefix: document.getElementById('table_prefix')?.value
    };

    try {
      const res = await fetch("{{ route('woocommerce.sync') }}", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });
      
      let data;
      try {
        data = await res.json();
      } catch (jsonErr) {
        throw new Error('Respuesta inesperada del servidor (HTTP ' + res.status + ')');
      }

      if (data.success) {
        if (data.prefix) {
          document.getElementById('table_prefix').value = data.prefix;
        }
        showToast(data.message, 'success');
        setTimeout(() => {
          window.location.reload();
        }, 1200);
      } else {
        showToast(data.message, 'error');
      }
    } catch (e) {
      showToast('Error durante la sincronización: ' + e.message, 'error');
    } finally {
      if (btnMain) { btnMain.disabled = false; btnMain.innerText = '🔄 Sincronizar Ahora'; }
      if (btnWiz) { btnWiz.disabled = false; btnWiz.innerText = '🚀 Guardar y Sincronizar Pedidos Reales'; }
    }
  }

  async function confirmPurgeDemo() {
    if (!confirm('¿Estás seguro de que deseas eliminar y purgar toda la data demo de pedidos? Esto dejará la base de datos lista para importar tus órdenes reales de Suitable.cl.')) {
      return;
    }

    const token = document.querySelector('meta[name="csrf-token"]').content;
    showToast('Purgando datos de prueba...', 'info');

    try {
      const res = await fetch("{{ route('woocommerce.purge_demo') }}", {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        }
      });
      const data = await res.json();

      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        showToast(data.message, 'error');
      }
    } catch (e) {
      showToast('Error al purgar data', 'error');
    }
  }
</script>
@endsection
