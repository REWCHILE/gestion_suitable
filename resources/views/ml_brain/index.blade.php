@extends('layouts.app')

@section('title', 'Cerebro de ML & Inteligencia de Búsqueda | Suitable')

@section('content')
<div class="page-header">
  <div class="page-title-group">
    <h1>Cerebro de ML &amp; Tendencias de Demanda</h1>
    <p class="page-subtitle">Algoritmos de detección temprana de intención de compra en uniformes clínicos y scrubs</p>
  </div>
</div>

<div class="table-card" style="padding: 22px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">Palabras Clave con Mayor Crecimiento en Chile</h3>
    <span class="badge" style="background:#E6F4F4; color:#146161; font-weight:800;">Algoritmo ML Activo</span>
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
            <strong style="color: #0F172A;">{{ $tr->keyword }}</strong>
          </td>
          <td>
            <span style="font-weight: 700;">{{ number_format($tr->search_volume) }}</span> búsquedas
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
@endsection
