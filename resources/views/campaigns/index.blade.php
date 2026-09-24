@extends('layouts.app')

@section('title', 'Campañas B2B | Suitable')

@section('content')
<div class="page-header">
  <div class="page-title-group">
    <h1>Campañas de Outreach B2B con IA</h1>
    <p class="page-subtitle">Historial de campañas por correo electrónico y estado de entrega</p>
  </div>

  <div class="header-actions">
    <a href="{{ route('campaigns.create') }}" class="btn btn-primary btn-sm">
      + Crear Nueva Campaña con IA
    </a>
  </div>
</div>

<div class="table-card">
  <table class="crm-table">
    <thead>
      <tr>
        <th>Nombre de Campaña</th>
        <th>Segmento / Grupo</th>
        <th>Asunto del Correo</th>
        <th>Proveedor IA</th>
        <th>Destinatarios</th>
        <th>Estado</th>
        <th>Fecha</th>
      </tr>
    </thead>
    <tbody>
      @forelse($campaigns as $camp)
        <tr>
          <td>
            <strong>{{ $camp->name }}</strong>
          </td>
          <td>
            @if($camp->group)
              <span class="badge" style="background:#E6F4F4; color:#146161; font-weight:700;">
                👥 {{ $camp->group->name }}
              </span>
            @else
              <span style="color:#64748B; font-size:12px;">Todos los clientes</span>
            @endif
          </td>
          <td>
            <div style="font-size:12.5px; font-weight:600; color:#0F172A;">{{ $camp->subject }}</div>
            <div style="font-size:11px; color:#64748B;">{{ Str::limit($camp->preheader, 45) }}</div>
          </td>
          <td>
            <span class="badge" style="background:#F1F5F9; color:#475569; font-weight:800; text-transform:uppercase;">
              {{ $camp->ai_provider ?: 'Groq' }}
            </span>
          </td>
          <td>
            <strong>{{ $camp->sent_count }} / {{ $camp->total_count }}</strong>
          </td>
          <td>
            @if($camp->status === 'enviada')
              <span class="badge badge-emerald">Enviada</span>
            @else
              <span class="badge badge-blue">{{ ucfirst($camp->status) }}</span>
            @endif
          </td>
          <td style="font-size:12px; color:#64748B;">
            {{ $camp->created_at ? $camp->created_at->format('d/m/Y H:i') : '-' }}
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" style="text-align: center; padding: 40px; color: #64748B;">
            Aún no has creado campañas. Haz clic en <strong>"+ Crear Nueva Campaña con IA"</strong> para orquestar la primera.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
