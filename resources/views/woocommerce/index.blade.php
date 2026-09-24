@extends('layouts.app')

@section('title', 'WooCommerce Sync & Catálogo | Suitable')

@section('content')
<div class="page-header">
  <div class="page-title-group">
    <h1>Integración WooCommerce Suitable.cl</h1>
    <p class="page-subtitle">Sincronización de pedidos, clientes y catálogo de productos</p>
  </div>
  <div class="header-actions">
    <button type="button" class="btn btn-primary btn-sm" onclick="syncWooCommerce()">
      🔄 Sincronizar Ahora
    </button>
  </div>
</div>

<div class="table-card" style="padding: 22px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">Historial de Pedidos Sincronizados</h3>
    <span class="badge" style="background:#ECFDF5; color:#059669; font-weight:800;">Conexión Activa con Suitable.cl</span>
  </div>

  <table class="crm-table">
    <thead>
      <tr>
        <th>N° Orden</th>
        <th>Cliente</th>
        <th>Ciudad / Comuna</th>
        <th>Monto Total</th>
        <th>Ítems</th>
        <th>Estado</th>
        <th>Fecha</th>
      </tr>
    </thead>
    <tbody>
      @foreach($orders as $ord)
        <tr>
          <td><strong>#{{ $ord->wc_order_id }}</strong></td>
          <td>
            <div>{{ $ord->customer_name }}</div>
            <div style="font-size: 11px; color: #64748B;">{{ $ord->customer_email }}</div>
          </td>
          <td>{{ $ord->customer_city }}</td>
          <td style="font-weight: 700; color: #059669;">
            ${{ number_format($ord->total_amount, 0, ',', '.') }}
          </td>
          <td>{{ $ord->items_count }} prendas</td>
          <td><span class="badge badge-emerald">{{ ucfirst($ord->status) }}</span></td>
          <td style="font-size: 12px; color: #64748B;">
            {{ \Carbon\Carbon::parse($ord->date_created)->format('d/m/Y H:i') }}
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

<script>
  async function syncWooCommerce() {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    showToast('Iniciando sincronización con WooCommerce...', 'info');

    try {
      const res = await fetch("{{ route('woocommerce.sync') }}", {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
      });
      const data = await res.json();
      showToast(data.message, 'success');
    } catch (e) {
      showToast('Error en la sincronización', 'error');
    }
  }
</script>
@endsection
