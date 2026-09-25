@foreach($orders as $ord)
  <tr class="order-row-item">
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
