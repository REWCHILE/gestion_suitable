@extends('layouts.app')

@section('title', 'Cerebro Suitable & Inteligencia de Negocio | Suitable')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
  <div class="page-title-group">
    <div style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 4px;">
      <span style="font-size: 20px;">🧠</span>
      <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #0F172A;">Cerebro Suitable &amp; Inteligencia de Ventas</h1>
    </div>
    <p class="page-subtitle" style="margin: 0; color: #64748B; font-size: 13.5px;">
      Consolidación de ventas WooCommerce (Suitable.cl), proyecciones de demanda textil y tendencias de intención de compra en Chile
    </p>
  </div>
  <div class="header-actions" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
    <button type="button" class="btn btn-secondary btn-sm" onclick="syncWooCommerce(this)" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
      <span>🔄</span>
      <span>Sincronizar WooCommerce</span>
    </button>
    <button type="button" class="btn btn-primary btn-sm" onclick="runAiDiagnostic()" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; box-shadow: 0 2px 4px rgba(30,136,136,0.25);">
      <span>⚡</span>
      <span>Diagnóstico con IA Suitable</span>
    </button>
  </div>
</div>

<!-- INFO BANNER: AUTOMATIZACIÓN DE MÉTRICAS -->
<div style="background: linear-gradient(135deg, #F0FDF4 0%, #E6F4F4 100%); border: 1px solid #99F6E4; border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
  <div style="display: flex; align-items: center; gap: 12px;">
    <div style="width: 38px; height: 38px; border-radius: 8px; background: #1E8888; color: white; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
      ✨
    </div>
    <div>
      <div style="font-size: 13px; font-weight: 700; color: #115E59; margin-bottom: 2px;">
        Métricas Automatizadas en Tiempo Real
      </div>
      <div style="font-size: 12px; color: #134E4A; line-height: 1.4;">
        El <strong>Cerebro Suitable</strong> integra automáticamente los pedidos de WooCommerce, calcula ticket promedio, estima compras recurrentes de clínicas y cruza el volumen con la fábrica. No requiere ingreso manual de datos.
      </div>
    </div>
  </div>
  <span class="badge" style="background: #1E8888; color: white; font-weight: 700; padding: 5px 12px; border-radius: 20px; font-size: 11px;">
    ● Conexión Activa Suitable.cl
  </span>
</div>

<!-- 1. CARDS DE VENTAS WOOCOMMERCE & CONVERSIÓN -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
  
  <div class="table-card" style="padding: 20px; border-top: 4px solid #059669;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
      <span style="font-size: 11.5px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px;">Ventas Totales WooCommerce</span>
      <span style="font-size: 16px;">🛍️</span>
    </div>
    <div style="font-size: 26px; font-weight: 800; color: #059669; margin: 4px 0;">
      ${{ number_format($totalRevenue, 0, ',', '.') }}
    </div>
    <div style="font-size: 12px; color: #64748B;">
      <strong style="color: #0F172A;">{{ $totalOrders }}</strong> órdenes sincronizadas
    </div>
  </div>

  <div class="table-card" style="padding: 20px; border-top: 4px solid #1E8888;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
      <span style="font-size: 11.5px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px;">Ticket Promedio (AOV)</span>
      <span style="font-size: 16px;">💳</span>
    </div>
    <div style="font-size: 26px; font-weight: 800; color: #1E8888; margin: 4px 0;">
      ${{ number_format($avgOrderValue, 0, ',', '.') }}
    </div>
    <div style="font-size: 12px; color: #64748B;">
      Por pedido institucional / clínico
    </div>
  </div>

  <div class="table-card" style="padding: 20px; border-top: 4px solid #0284C7;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
      <span style="font-size: 11.5px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px;">Prendas Clínicas Vendidas</span>
      <span style="font-size: 16px;">🩺</span>
    </div>
    <div style="font-size: 26px; font-weight: 800; color: #0284C7; margin: 4px 0;">
      {{ number_format($totalItemsSold) }} <span style="font-size: 14px; font-weight: 600;">prendas</span>
    </div>
    <div style="font-size: 12px; color: #64748B;">
      Uniformes Flex, scrubs y accesorios
    </div>
  </div>

  <div class="table-card" style="padding: 20px; border-top: 4px solid #8B5CF6;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
      <span style="font-size: 11.5px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px;">Tasa de Conversión (CVR)</span>
      <span style="font-size: 16px;">🎯</span>
    </div>
    <div style="font-size: 26px; font-weight: 800; color: #8B5CF6; margin: 4px 0;">
      {{ number_format($avgCvr, 2) }}%
    </div>
    <div style="font-size: 12px; color: #64748B;">
      CTR Publicitario: <strong>{{ number_format($avgCtr, 2) }}%</strong>
    </div>
  </div>

</div>

<!-- 2. PROYECCIONES PREDICTIVAS DE DEMANDA TEXTIL (30, 60, 90 DÍAS) -->
<div style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <div style="font-size: 15px; font-weight: 800; color: #0F172A; display: flex; align-items: center; gap: 8px;">
      <span>📈</span>
      <span>Proyección Predictiva de Demanda Textil (Fábrica Suitable)</span>
    </div>
    <span class="badge" style="background: #F1F5F9; color: #475569; font-size: 11px;">Algoritmo ML Predictivo</span>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
    
    <div class="table-card" style="padding: 18px; background: #FFFFFF;">
      <div style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase;">Proyección 30 Días</div>
      <div style="font-size: 22px; font-weight: 800; color: #059669; margin: 6px 0;">
        ${{ number_format($forecast30, 0, ',', '.') }} <span style="font-size: 11px; color: #64748B;">CLP</span>
      </div>
      <div style="font-size: 12px; color: #64748B;">
        Demanda estimada: ~<strong>{{ round($forecast30 / 25000) }}</strong> prendas
      </div>
    </div>

    <div class="table-card" style="padding: 18px; background: #FFFFFF;">
      <div style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase;">Proyección 60 Días</div>
      <div style="font-size: 22px; font-weight: 800; color: #0284C7; margin: 6px 0;">
        ${{ number_format($forecast60, 0, ',', '.') }} <span style="font-size: 11px; color: #64748B;">CLP</span>
      </div>
      <div style="font-size: 12px; color: #64748B;">
        Ciclo de reposición clínicas activas
      </div>
    </div>

    <div class="table-card" style="padding: 18px; background: #FFFFFF;">
      <div style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase;">Proyección 90 Días (Trimestre)</div>
      <div style="font-size: 22px; font-weight: 800; color: #7E22CE; margin: 6px 0;">
        ${{ number_format($forecast90, 0, ',', '.') }} <span style="font-size: 11px; color: #64748B;">CLP</span>
      </div>
      <div style="font-size: 12px; color: #64748B;">
        Peak de convenios y licitaciones
      </div>
    </div>

    <div class="table-card" style="padding: 18px; background: #F0FDF4; border: 1px solid #BBF7D0;">
      <div style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase;">Ciclo de Recompra Clínica</div>
      <div style="font-size: 22px; font-weight: 800; color: #15803D; margin: 6px 0;">
        {{ $repurchaseCycle }} Días
      </div>
      <div style="font-size: 12px; color: #166534;">
        Frecuencia media de reabastecimiento
      </div>
    </div>

  </div>
</div>

<!-- BOX DE DIAGNÓSTICO IA (OCULTO HASTA QUE SE EJECUTA) -->
<div id="ai_diagnostic_box" style="display: none; background: #FFFFFF; border: 2px solid #1E8888; border-radius: 8px; padding: 22px; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(30,136,136,0.15);">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <div style="display: flex; align-items: center; gap: 8px;">
      <span class="badge" style="background: #E6F4F4; color: #146161; font-weight: 800; font-size: 12px;">🤖 Diagnóstico IA Suitable</span>
      <span id="diagnostic_provider_badge" style="font-size: 11px; color: #64748B;"></span>
    </div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('ai_diagnostic_box').style.display = 'none'">✕ Cerrar</button>
  </div>
  <div id="diagnostic_text" style="font-size: 13px; line-height: 1.7; color: #1E293B; background: #F8FAFC; padding: 18px; border-radius: 6px; white-space: pre-wrap;"></div>
</div>

<!-- 3. TENDENCIAS DE ROTACIÓN DE WOOCOMMERCE & ÚLTIMOS PEDIDOS -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; margin-bottom: 24px;">
  
  <!-- A: ROTACIÓN DE PRODUCTOS WOOCOMMERCE -->
  <div class="table-card" style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <div>
        <h3 style="font-size: 15px; font-weight: 800; color: #0F172A; margin: 0;">Tendencias de Compra &amp; Rotación</h3>
        <p style="font-size: 11.5px; color: #64748B; margin: 2px 0 0 0;">Prendas con mayor aceleración de pedidos en WooCommerce</p>
      </div>
      <span class="badge badge-emerald" style="font-size: 10.5px;">WooCommerce Live</span>
    </div>

    <table class="crm-table">
      <thead>
        <tr>
          <th>Prenda / Línea</th>
          <th>Crecimiento</th>
          <th>Unidades</th>
          <th>Velocidad</th>
        </tr>
      </thead>
      <tbody>
        @foreach($risingProducts as $rp)
          <tr>
            <td>
              <strong style="color: #0F172A; font-size: 12.5px;">{{ $rp['name'] }}</strong>
              <div style="font-size: 11px; color: #64748B;">{{ $rp['cat'] }} • ${{ number_format($rp['price'], 0, ',', '.') }}</div>
            </td>
            <td>
              <span style="color: #059669; font-weight: 800; font-size: 12.5px;">{{ $rp['growth'] }}</span>
            </td>
            <td>
              <span style="font-weight: 700; color: #0F172A;">{{ $rp['units'] }} un.</span>
            </td>
            <td>
              <span class="badge {{ $rp['badge'] }}" style="font-size: 10px;">{{ $rp['velocity'] }}</span>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <!-- B: ÚLTIMOS PEDIDOS DE WOOCOMMERCE -->
  <div class="table-card" style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <div>
        <h3 style="font-size: 15px; font-weight: 800; color: #0F172A; margin: 0;">Últimos Pedidos Sincronizados</h3>
        <p style="font-size: 11.5px; color: #64748B; margin: 2px 0 0 0;">Transacciones recientes registradas en Suitable.cl</p>
      </div>
      <a href="{{ route('woocommerce.index') }}" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 4px 10px;">Ver Todos →</a>
    </div>

    <table class="crm-table">
      <thead>
        <tr>
          <th>N° Orden</th>
          <th>Cliente / Comuna</th>
          <th>Total (CLP)</th>
          <th>Prendas</th>
        </tr>
      </thead>
      <tbody>
        @foreach($recentOrders as $ro)
          <tr>
            <td>
              <strong style="color: #1E8888;">#{{ $ro->wc_order_id }}</strong>
            </td>
            <td>
              <div style="font-weight: 600; color: #0F172A; font-size: 12.5px;">{{ $ro->customer_name }}</div>
              <div style="font-size: 11px; color: #64748B;">{{ $ro->customer_city ?: 'Santiago' }}</div>
            </td>
            <td>
              <strong style="color: #059669;">${{ number_format($ro->total_amount, 0, ',', '.') }}</strong>
            </td>
            <td>
              <span class="badge badge-teal" style="font-size: 10px;">{{ $ro->items_count }} un.</span>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

</div>

<!-- 4. PALABRAS CLAVE CON MAYOR CRECIMIENTO EN CHILE -->
<div class="table-card" style="padding: 22px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
    <div>
      <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">Palabras Clave con Mayor Crecimiento en Chile</h3>
      <p style="font-size: 12px; color: #64748B; margin: 2px 0 0 0;">Intenciones de búsqueda clínica captadas para alinear el catálogo y las campañas de email</p>
    </div>
    <span class="badge" style="background:#E6F4F4; color:#146161; font-weight:800; font-size: 11px;">Algoritmo ML Activo</span>
  </div>

  <table class="crm-table">
    <thead>
      <tr>
        <th>Término / Búsqueda Clínica</th>
        <th>Volumen Mensual</th>
        <th>Crecimiento Intermensual</th>
        <th>Categoría</th>
        <th>Nivel de Intención de Compra</th>
      </tr>
    </thead>
    <tbody>
      @foreach($trends as $tr)
        <tr>
          <td>
            <strong style="color: #0F172A; font-size: 13px;">{{ $tr->keyword }}</strong>
          </td>
          <td>
            <span style="font-weight: 700; color: #0F172A;">{{ number_format($tr->search_volume) }}</span> búsquedas
          </td>
          <td>
            <span style="color: #059669; font-weight: 800;">+{{ number_format($tr->growth_rate, 1) }}% ↑</span>
          </td>
          <td>
            <span class="badge" style="background: #F1F5F9; color: #475569;">{{ $tr->category }}</span>
          </td>
          <td>
            @if(stripos($tr->intent_level, 'Muy Alta') !== false)
              <span class="badge badge-emerald">{{ $tr->intent_level }}</span>
            @elseif(stripos($tr->intent_level, 'Alta') !== false)
              <span class="badge badge-blue">{{ $tr->intent_level }}</span>
            @else
              <span class="badge badge-purple">{{ $tr->intent_level }}</span>
            @endif
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

<script>
  async function syncWooCommerce(btn) {
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span>⏳ Sincronizando...</span>';
    btn.disabled = true;

    try {
      const token = document.querySelector('meta[name="csrf-token"]').content;
      const res = await fetch("{{ route('woocommerce.sync') }}", {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
      });
      const data = await res.json();
      showToast(data.message || 'Sincronización con WooCommerce completada', 'success');
      setTimeout(() => window.location.reload(), 1200);
    } catch (e) {
      showToast('Error al conectar con WooCommerce', 'error');
      btn.innerHTML = originalText;
      btn.disabled = false;
    }
  }

  async function runAiDiagnostic() {
    const box = document.getElementById('ai_diagnostic_box');
    const textEl = document.getElementById('diagnostic_text');
    const badgeEl = document.getElementById('diagnostic_provider_badge');

    box.style.display = 'block';
    textEl.innerText = 'Consultando al Cerebro de IA con los datos reales de ventas de WooCommerce y tendencias de búsqueda...';
    badgeEl.innerText = 'Analizando...';

    try {
      const token = document.querySelector('meta[name="csrf-token"]').content;
      const res = await fetch("{{ route('ml_brain.diagnostic') }}", {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({})
      });
      const data = await res.json();
      if (data.success) {
        textEl.innerText = data.diagnostic;
        badgeEl.innerText = 'Motor: ' + data.provider;
        showToast('Diagnóstico estratégico de IA generado', 'success');
      } else {
        textEl.innerText = 'No se pudo generar el diagnóstico.';
      }
    } catch (e) {
      textEl.innerText = 'Ocurrió un error al procesar el diagnóstico con IA.';
      showToast('Error al invocar IA', 'error');
    }
  }
</script>
@endsection
