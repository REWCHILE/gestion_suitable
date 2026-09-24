@extends('layouts.app')

@section('title', 'Analítica & Rendimiento de Tráfico | Suitable')

@section('content')
<div class="page-header">
  <div class="page-title-group">
    <h1>Analítica de Tráfico &amp; Rendimiento Publicitario</h1>
    <p class="page-subtitle">Monitoreo de CPA, CTR, Conversión (CVR) y Valor Promedio de Orden (AOV) en Suitable.cl</p>
  </div>
</div>

<!-- STATS CARDS -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
  <div class="table-card" style="padding: 18px;">
    <div style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase;">Sesiones Totales (30d)</div>
    <div style="font-size: 26px; font-weight: 800; color: #0F172A; margin: 4px 0;">{{ number_format($totalSessions) }}</div>
    <div style="font-size: 11px; color: #1E8888;">Tráfico calificado salud</div>
  </div>

  <div class="table-card" style="padding: 18px;">
    <div style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase;">Ingresos Totales (30d)</div>
    <div style="font-size: 26px; font-weight: 800; color: #059669; margin: 4px 0;">${{ number_format($totalRevenue, 0, ',', '.') }}</div>
    <div style="font-size: 11px; color: #059669;">{{ $totalOrders }} órdenes generadas</div>
  </div>

  <div class="table-card" style="padding: 18px;">
    <div style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase;">Gasto Publicitario (Ads)</div>
    <div style="font-size: 26px; font-weight: 800; color: #D97706; margin: 4px 0;">${{ number_format($totalAdSpend, 0, ',', '.') }}</div>
    <div style="font-size: 11px; color: #64748B;">Meta Ads + Google Ads</div>
  </div>

  <div class="table-card" style="padding: 18px;">
    <div style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase;">CPA Promedio (Costo Adq.)</div>
    <div style="font-size: 26px; font-weight: 800; color: #8B5CF6; margin: 4px 0;">${{ number_format($avgCpa, 0, ',', '.') }}</div>
    <div style="font-size: 11px; color: #8B5CF6;">Por cliente adquirido</div>
  </div>

  <div class="table-card" style="padding: 18px;">
    <div style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase;">CTR Promedio</div>
    <div style="font-size: 26px; font-weight: 800; color: #0284C7; margin: 4px 0;">{{ number_format($avgCtr, 2) }}%</div>
    <div style="font-size: 11px; color: #0284C7;">Click Through Rate</div>
  </div>
</div>

<!-- DAILY METRICS TABLE -->
<div class="table-card" style="padding: 20px;">
  <h3 style="font-size: 15px; font-weight: 800; margin: 0 0 16px 0; color: #0F172A;">Métricas Diarias de Rendimiento</h3>
  <table class="crm-table">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Sesiones</th>
        <th>Visitantes</th>
        <th>Órdenes</th>
        <th>Ingresos (CLP)</th>
        <th>Inversión Ads</th>
        <th>CTR (%)</th>
        <th>CPA (CLP)</th>
        <th>CVR (%)</th>
        <th>Ticket Prom. (AOV)</th>
      </tr>
    </thead>
    <tbody>
      @foreach($metrics as $m)
        <tr>
          <td><strong>{{ \Carbon\Carbon::parse($m->period_date)->format('d/m/Y') }}</strong></td>
          <td>{{ number_format($m->sessions) }}</td>
          <td>{{ number_format($m->visitors) }}</td>
          <td><span class="badge badge-teal">{{ $m->orders_count }}</span></td>
          <td style="font-weight: 700; color: #059669;">${{ number_format($m->revenue, 0, ',', '.') }}</td>
          <td style="color: #64748B;">${{ number_format($m->ad_spend, 0, ',', '.') }}</td>
          <td>{{ number_format($m->ctr, 2) }}%</td>
          <td style="font-weight: 700;">${{ number_format($m->cpa, 0, ',', '.') }}</td>
          <td>{{ number_format($m->cvr, 2) }}%</td>
          <td style="color: #1E8888; font-weight: 700;">${{ number_format($m->aov, 0, ',', '.') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
