@extends('layouts.app')

@section('title', 'Dashboard B2B | Suitable Uniformes Clínicos')

@section('content')
<div class="page-header">
  <div class="page-title-group">
    <h1>Dashboard Comercial &amp; Outreach B2B</h1>
    <p class="page-subtitle">Panel de control unificado: Clínicas, campañas inteligentes con IA y métricas de Suitable</p>
  </div>
  <div class="header-actions">
    <a href="{{ route('import.index') }}" class="btn btn-secondary btn-sm">📥 Importar Brevo/CSV</a>
    <a href="{{ route('campaigns.create') }}" class="btn btn-primary btn-sm">🚀 Nueva Campaña IA</a>
  </div>
</div>

<!-- STATS CARDS GRID -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
  <div class="table-card" style="padding: 18px;">
    <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">Total Instituciones</div>
    <div style="font-size: 28px; font-weight: 800; color: #0F172A; margin: 4px 0;">{{ number_format($totalClients) }}</div>
    <div style="font-size: 12px; color: #1E8888; font-weight: 600;">👥 Base enriquecida en MySQL</div>
  </div>

  <div class="table-card" style="padding: 18px;">
    <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">Grupos / Segmentos</div>
    <div style="font-size: 28px; font-weight: 800; color: #1E8888; margin: 4px 0;">{{ $totalGroups }}</div>
    <div style="font-size: 12px; color: #64748B;">🏷️ Incluye encuesta (491) y B2B</div>
  </div>

  <div class="table-card" style="padding: 18px;">
    <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">Ventas WooCommerce</div>
    @if($totalOrders > 0)
      <div style="font-size: 28px; font-weight: 800; color: #059669; margin: 4px 0;">${{ number_format($totalRevenue, 0, ',', '.') }}</div>
      <div style="font-size: 12px; color: #059669; font-weight: 600;">🛍️ {{ $totalOrders }} órdenes sincronizadas</div>
    @else
      <div style="font-size: 28px; font-weight: 800; color: #94A3B8; margin: 4px 0;">$0</div>
      <div style="font-size: 11.5px; color: #D97706; font-weight: 600;">
        ⚠️ Sin sincronizar • <a href="{{ route('woocommerce.index') }}" style="color: #1E8888; text-decoration: underline;">Conectar tienda</a>
      </div>
    @endif
  </div>

  <div class="table-card" style="padding: 18px;">
    <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">Motor de Framework</div>
    <div style="font-size: 28px; font-weight: 800; color: #8B5CF6; margin: 4px 0;">Laravel 11</div>
    <div style="font-size: 12px; color: #8B5CF6; font-weight: 600;">⚡ MySQL InnoDB + Colas</div>
  </div>
</div>

<!-- GROUPS PREVIEW GRID -->
<div class="table-card" style="padding: 20px; margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <h3 style="font-size: 16px; font-weight: 800; margin: 0; color: #0F172A;">Segmentos y Listas de Contactos</h3>
    <a href="{{ route('groups.index') }}" style="font-size: 13px; color: #1E8888; font-weight: 700; text-decoration: none;">Ver todos los grupos →</a>
  </div>
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px;">
    @foreach($groups as $g)
      <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-left: 4px solid {{ $g->color ?: '#1E8888' }}; border-radius: 8px; padding: 14px; display: flex; justify-content: space-between; align-items: center;">
        <div>
          <div style="font-size: 14px; font-weight: 800; color: #0F172A;">{{ $g->name }}</div>
          <div style="font-size: 11px; color: #64748B; margin-top: 2px;">{{ strlen($g->description ?? '') > 45 ? substr($g->description, 0, 42) . '...' : ($g->description ?? '') }}</div>
        </div>
        <div style="text-align: right;">
          <span class="badge" style="background-color: #E6F4F4; color: #146161; font-weight: 800; font-size: 12px;">{{ $g->clients_count }}</span>
          <div style="margin-top: 4px;">
            <a href="{{ route('clients.index', ['group' => $g->id]) }}" style="font-size: 11px; font-weight: 700; color: #1E8888; text-decoration: none;">Ver →</a>
          </div>
        </div>
      </div>
    @endforeach
  </div>
</div>

<!-- TWO COLUMNS: RECENT CLIENTS & RECENT ORDERS -->
<div style="display: grid; grid-template-columns: 3fr 2fr; gap: 20px;">
  <!-- RECENT CLIENTS -->
  <div class="table-card" style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <h3 style="font-size: 15px; font-weight: 800; margin: 0; color: #0F172A;">Instituciones Médicas Recientes</h3>
      <a href="{{ route('clients.index') }}" style="font-size: 12px; color: #1E8888; font-weight: 700; text-decoration: none;">Ver Pipeline →</a>
    </div>
    <table class="crm-table" style="font-size: 12.5px;">
      <thead>
        <tr>
          <th>Clínica / Institución</th>
          <th>Contacto</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        @foreach($recentClients as $rc)
          <tr>
            <td>
              <strong>{{ $rc->empresa }}</strong>
              <div style="font-size: 11px; color: #64748B;">{{ $rc->region_comuna }}</div>
            </td>
            <td>
              <div>{{ $rc->contacto_nombre }}</div>
              <div style="font-size: 11px; color: #64748B;">{{ $rc->email }}</div>
            </td>
            <td>
              <span class="badge badge-teal">{{ ucfirst(str_replace('_', ' ', $rc->estado)) }}</span>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <!-- RECENT WOOCOMMERCE ORDERS -->
  <div class="table-card" style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <h3 style="font-size: 15px; font-weight: 800; margin: 0; color: #0F172A;">Órdenes WooCommerce</h3>
      <a href="{{ route('woocommerce.index') }}" style="font-size: 12px; color: #1E8888; font-weight: 700; text-decoration: none;">Ver Catálogo / Asistente →</a>
    </div>

    @if($recentOrders->count() > 0)
      <table class="crm-table" style="font-size: 12.5px;">
        <thead>
          <tr>
            <th>Cliente</th>
            <th>Monto</th>
            <th>Fecha</th>
          </tr>
        </thead>
        <tbody>
          @foreach($recentOrders as $ro)
            <tr>
              <td>
                <strong>{{ $ro->customer_name }}</strong>
                <div style="font-size: 11px; color: #64748B;">#{{ $ro->wc_order_id }} ({{ $ro->customer_city }})</div>
              </td>
              <td style="font-weight: 700; color: #059669;">
                ${{ number_format($ro->total_amount, 0, ',', '.') }}
              </td>
              <td style="font-size: 11px; color: #64748B;">
                @php
                  $orderDate = 'Reciente';
                  if (!empty($ro->date_created)) {
                    try {
                      $orderDate = \Carbon\Carbon::parse($ro->date_created)->format('d/m/Y');
                    } catch (\Throwable $de) {
                      $orderDate = substr((string)$ro->date_created, 0, 10);
                    }
                  }
                @endphp
                {{ $orderDate }}
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <div style="text-align: center; padding: 28px 16px; background: #F8FAFC; border-radius: 8px; border: 1px dashed #CBD5E1;">
        <div style="font-size: 32px; margin-bottom: 8px;">🛍️</div>
        <div style="font-size: 13.5px; font-weight: 800; color: #0F172A; margin-bottom: 4px;">Tienda sin sincronizar</div>
        <p style="font-size: 12px; color: #64748B; margin: 0 0 14px 0; line-height: 1.4;">
          Conecta la base de datos <code>suitable_wp372</code> en <code>public_html</code> para visualizar tus órdenes reales aquí.
        </p>
        <a href="{{ route('woocommerce.index') }}" class="btn btn-primary btn-sm" style="font-size: 12px;">
          🚀 Conectar WooCommerce
        </a>
      </div>
    @endif
  </div>
</div>
@endsection
