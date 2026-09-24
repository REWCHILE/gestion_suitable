@extends('layouts.app')

@section('title', 'Cerebro Suitable & Inteligencia de Negocio | Suitable')

@section('content')
<!-- SCRIPT CDN MARKDOWN CON FALLBACK ROBUSTO -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<style>
  /* ESTILOS DE PESTAÑAS PRINCIPALES */
  .brain-tab-nav {
    display: flex;
    gap: 8px;
    border-bottom: 2px solid #E2E8F0;
    margin-bottom: 24px;
    padding-bottom: 0;
  }
  .brain-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    font-size: 14px;
    font-weight: 700;
    color: #64748B;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: -2px;
  }
  .brain-tab-btn:hover {
    color: #0F172A;
    background: #F8FAFC;
    border-radius: 6px 6px 0 0;
  }
  .brain-tab-btn.active {
    color: #1E8888;
    border-bottom-color: #1E8888;
    background: #FFFFFF;
  }
  .brain-tab-btn .badge-pill {
    background: #F1F5F9;
    color: #475569;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 600;
  }
  .brain-tab-btn.active .badge-pill {
    background: #E6F4F4;
    color: #115E59;
  }

  /* ESTILOS DE CHAT / PENSAMIENTOS */
  .thoughts-container {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 20px;
    min-height: 650px;
    align-items: stretch;
  }
  @media (max-width: 900px) {
    .thoughts-container {
      grid-template-columns: 1fr;
    }
  }

  .thoughts-sidebar {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    height: 720px;
  }
  .thoughts-sidebar-header {
    padding: 16px;
    border-bottom: 1px solid #E2E8F0;
    background: #F8FAFC;
  }
  .thoughts-list {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
  .thought-item {
    padding: 12px 14px;
    border-radius: 8px;
    border: 1px solid transparent;
    background: #FFFFFF;
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    flex-direction: column;
    gap: 5px;
    position: relative;
  }
  .thought-item:hover {
    background: #F8FAFC;
    border-color: #CBD5E1;
  }
  .thought-item.active {
    background: #F0FDFA;
    border-color: #99F6E4;
    box-shadow: 0 2px 4px rgba(30,136,136,0.08);
  }
  .thought-item-title {
    font-size: 13px;
    font-weight: 700;
    color: #0F172A;
    line-height: 1.35;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding-right: 28px;
  }
  .thought-item.active .thought-item-title {
    color: #115E59;
  }
  .thought-item-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 11px;
    color: #64748B;
  }
  .thought-item-delete {
    position: absolute;
    top: 10px;
    right: 8px;
    opacity: 0.6;
    background: #F1F5F9;
    border: 1px solid #E2E8F0;
    color: #64748B;
    cursor: pointer;
    font-size: 12px;
    padding: 3px 6px;
    border-radius: 4px;
    transition: all 0.15s ease;
    z-index: 5;
  }
  .thought-item:hover .thought-item-delete {
    opacity: 1;
  }
  .thought-item-delete:hover {
    color: #FFFFFF;
    background: #EF4444;
    border-color: #DC2626;
  }

  /* CHAT MAIN WORKSPACE */
  .thoughts-main {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    height: 720px;
  }
  .thoughts-header {
    padding: 14px 20px;
    border-bottom: 1px solid #E2E8F0;
    background: #FFFFFF;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
  }
  .thoughts-messages {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    background: #F8FAFC;
  }
  .chat-msg {
    display: flex;
    gap: 14px;
    max-width: 88%;
  }
  .chat-msg.user {
    align-self: flex-end;
    flex-direction: row-reverse;
  }
  .chat-msg.assistant {
    align-self: flex-start;
    max-width: 95%;
  }
  .chat-avatar {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
  }
  .chat-msg.user .chat-avatar {
    background: #0F172A;
    color: #FFFFFF;
  }
  .chat-msg.assistant .chat-avatar {
    background: #1E8888;
    color: #FFFFFF;
    box-shadow: 0 2px 6px rgba(30,136,136,0.3);
  }
  .chat-bubble {
    padding: 16px 20px;
    border-radius: 10px;
    font-size: 13.5px;
    line-height: 1.6;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  }
  .chat-msg.user .chat-bubble {
    background: #115E59;
    color: #FFFFFF;
    border-bottom-right-radius: 2px;
  }
  .chat-msg.assistant .chat-bubble {
    background: #FFFFFF;
    color: #1E293B;
    border: 1px solid #E2E8F0;
    border-bottom-left-radius: 2px;
    flex: 1;
  }

  .chat-msg-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 11.5px;
    color: #64748B;
    border-bottom: 1px solid #F1F5F9;
    padding-bottom: 6px;
  }
  .chat-input-bar {
    padding: 16px 20px;
    background: #FFFFFF;
    border-top: 1px solid #E2E8F0;
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  .chat-input-wrapper {
    display: flex;
    gap: 10px;
    align-items: flex-end;
  }
  .chat-textarea {
    flex: 1;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    padding: 12px 14px;
    font-size: 13.5px;
    line-height: 1.5;
    font-family: inherit;
    resize: none;
    min-height: 54px;
    max-height: 160px;
    outline: none;
    transition: border-color 0.2s;
  }
  .chat-textarea:focus {
    border-color: #1E8888;
    box-shadow: 0 0 0 3px rgba(30,136,136,0.12);
  }
  .chat-send-btn {
    background: #1E8888;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0 20px;
    height: 54px;
    font-weight: 700;
    font-size: 13.5px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(30,136,136,0.25);
    flex-shrink: 0;
  }
  .chat-send-btn:hover {
    background: #146161;
    transform: translateY(-1px);
  }
  .chat-send-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
  }

  /* QUICK CHIPS SUGGESTIONS */
  .quick-chips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 12px;
    margin-top: 16px;
  }
  .quick-chip-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    padding: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
  }
  .quick-chip-card:hover {
    border-color: #1E8888;
    background: #F0FDFA;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(30,136,136,0.1);
  }
  .quick-chip-title {
    font-size: 13px;
    font-weight: 700;
    color: #0F172A;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .quick-chip-desc {
    font-size: 11.5px;
    color: #64748B;
    line-height: 1.4;
  }

  /* MARKDOWN STYLING DENTRO DEL SISTEMA */
  .markdown-body {
    font-size: 13.5px;
    line-height: 1.7;
    color: #1E293B;
  }
  .markdown-body h1, .markdown-body h2, .markdown-body h3, .markdown-body h4 {
    color: #0F172A;
    font-weight: 800;
    margin-top: 18px;
    margin-bottom: 10px;
    line-height: 1.3;
  }
  .markdown-body h1 { font-size: 20px; }
  .markdown-body h2 {
    font-size: 16.5px;
    border-bottom: 2px solid #E2E8F0;
    padding-bottom: 6px;
  }
  .markdown-body h3 {
    font-size: 14.5px;
    color: #115E59;
  }
  .markdown-body p {
    margin-bottom: 12px;
  }
  .markdown-body ul, .markdown-body ol {
    margin: 8px 0 14px 22px;
    padding: 0;
  }
  .markdown-body li {
    margin-bottom: 5px;
  }
  .markdown-body strong {
    color: #0F172A;
    font-weight: 700;
  }
  .markdown-body table {
    width: 100%;
    border-collapse: collapse;
    margin: 16px 0;
    font-size: 12.5px;
    background: #FFFFFF;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid #CBD5E1;
  }
  .markdown-body th {
    background: #F0FDFA;
    color: #115E59;
    font-weight: 700;
    padding: 10px 14px;
    text-align: left;
    border: 1px solid #CBD5E1;
  }
  .markdown-body td {
    padding: 8px 14px;
    border: 1px solid #E2E8F0;
    color: #334155;
  }
  .markdown-body tr:nth-child(even) td {
    background: #F8FAFC;
  }
  .markdown-body blockquote {
    border-left: 4px solid #1E8888;
    padding: 8px 16px;
    margin: 14px 0;
    background: #F0FDFA;
    color: #134E4A;
    border-radius: 0 6px 6px 0;
    font-style: italic;
  }
  .markdown-body hr {
    border: 0;
    border-top: 1px solid #E2E8F0;
    margin: 18px 0;
  }
  .markdown-body code {
    background: #F1F5F9;
    color: #0F172A;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
    font-family: monospace;
  }
  .markdown-body pre {
    background: #0F172A;
    color: #F8FAFC;
    padding: 14px 18px;
    border-radius: 8px;
    overflow-x: auto;
    margin: 14px 0;
  }
  .markdown-body pre code {
    background: transparent;
    color: inherit;
    padding: 0;
  }
</style>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
  <div class="page-title-group">
    <div style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 4px;">
      <span style="font-size: 22px;">🧠</span>
      <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #0F172A;">Cerebro Suitable &amp; Inteligencia de Negocio</h1>
    </div>
    <p class="page-subtitle" style="margin: 0; color: #64748B; font-size: 13.5px;">
      Agente ejecutivo de razonamiento comercial, proyecciones textiles de fábrica y métricas en tiempo real
    </p>
  </div>
  <div class="header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
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

<!-- NAVEGACIÓN POR PESTAÑAS (SUBPESTAÑAS) -->
<div class="brain-tab-nav">
  <button type="button" class="brain-tab-btn {{ $tab !== 'thoughts' ? 'active' : '' }}" id="tab_btn_metrics" onclick="switchBrainTab('metrics')">
    <span>📊</span>
    <span>Inteligencia &amp; Proyecciones</span>
  </button>
  <button type="button" class="brain-tab-btn {{ $tab === 'thoughts' ? 'active' : '' }}" id="tab_btn_thoughts" onclick="switchBrainTab('thoughts')">
    <span>💭</span>
    <span>Pensamientos &amp; Agente Cerebro</span>
    <span class="badge-pill" id="thoughts_count_pill">{{ $conversations->count() }}</span>
  </button>
</div>

<!-- ========================================== -->
<!-- PESTAÑA 1: INTELIGENCIA & PROYECCIONES -->
<!-- ========================================== -->
<div id="brain_pane_metrics" style="{{ $tab === 'thoughts' ? 'display: none;' : 'display: block;' }}">

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
          Peak de convenios y dotaciones clínicas
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

  <!-- BOX DE DIAGNÓSTICO IA (MARKDOWN COMPLETO + BOTÓN INICIAR DIÁLOGO) -->
  <div id="ai_diagnostic_box" style="display: none; background: #FFFFFF; border: 2px solid #1E8888; border-radius: 10px; padding: 22px; margin-bottom: 24px; box-shadow: 0 4px 20px rgba(30,136,136,0.15);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span class="badge" style="background: #E6F4F4; color: #146161; font-weight: 800; font-size: 12px; padding: 6px 12px;">
          🤖 Diagnóstico IA Suitable
        </span>
        <span id="diagnostic_provider_badge" style="font-size: 12px; font-weight: 600; color: #0F172A;"></span>
      </div>
      <div style="display: flex; gap: 8px; align-items: center;">
        <button type="button" class="btn btn-primary btn-sm" id="btn_dialog_from_diagnostic" onclick="startDialogFromDiagnostic()" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; background: #1E8888; border-color: #1E8888;">
          <span>💬</span>
          <span>Iniciar Diálogo sobre este Diagnóstico</span>
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('ai_diagnostic_box').style.display = 'none'">✕ Cerrar</button>
      </div>
    </div>
    
    <!-- CONTENIDO FORMATEADO CON MARKDOWN -->
    <div id="diagnostic_markdown_container" class="markdown-body" style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 22px; border-radius: 8px;">
      <div style="display: flex; align-items: center; gap: 10px; color: #64748B;">
        <span class="spinner-border spinner-border-sm" role="status"></span>
        <span>Consultando al Cerebro de IA con los datos reales de ventas de WooCommerce y tendencias de búsqueda...</span>
      </div>
    </div>
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

</div>

<!-- ========================================== -->
<!-- PESTAÑA 2: PENSAMIENTOS & AGENTE CEREBRO -->
<!-- ========================================== -->
<div id="brain_pane_thoughts" style="{{ $tab === 'thoughts' ? 'display: block;' : 'display: none;' }}">

  <div class="thoughts-container">
    
    <!-- SIDEBAR: HISTORIAL DE PENSAMIENTOS Y CONVERSACIONES -->
    <div class="thoughts-sidebar">
      <div class="thoughts-sidebar-header">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
          <div style="font-weight: 800; font-size: 14px; color: #0F172A; display: flex; align-items: center; gap: 6px;">
            <span>💭</span>
            <span>Pensamientos</span>
          </div>
          <span class="badge" style="background: #E2E8F0; color: #475569; font-size: 10.5px;" id="sidebar_count_badge">
            {{ $conversations->count() }} guardados
          </span>
        </div>
        <button type="button" class="btn btn-primary" onclick="startNewThought()" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 700; font-size: 13px; padding: 9px 14px;">
          <span>➕</span>
          <span>Nuevo Pensamiento</span>
        </button>
      </div>

      <div class="thoughts-list" id="thoughts_list_container">
        @forelse($conversations as $conv)
          <div class="thought-item {{ ($activeConversation && $activeConversation->id === $conv->id) ? 'active' : '' }}" 
               id="thought_item_{{ $conv->id }}" 
               onclick="loadThought({{ $conv->id }})">
            <div class="thought-item-title" title="{{ $conv->title }}">{{ $conv->title }}</div>
            <div class="thought-item-meta">
              <span>{{ $conv->updated_at->format('d/m/Y H:i') }}</span>
              <span class="badge" style="font-size: 10px; background: #F1F5F9; color: #475569;">
                {{ $conv->messages_count }} msgs
              </span>
            </div>
            <button type="button" class="thought-item-delete" onclick="event.stopPropagation(); deleteThought({{ $conv->id }});" title="Eliminar pensamiento">
              🗑️
            </button>
          </div>
        @empty
          <div style="text-align: center; padding: 40px 16px; color: #94A3B8;" id="no_thoughts_placeholder">
            <div style="font-size: 28px; margin-bottom: 8px;">🧠</div>
            <div style="font-size: 12.5px; font-weight: 600; color: #64748B;">No hay pensamientos aún</div>
            <div style="font-size: 11px; margin-top: 4px;">Inicia un diálogo o propón una idea para que el agente Suitable la desarrolle y la guarde en la memoria.</div>
          </div>
        @endforelse
      </div>
    </div>

    <!-- MAIN CHAT WORKSPACE -->
    <div class="thoughts-main">
      
      <!-- HEADER CON SELECTOR DE PROVEEDOR IA -->
      <div class="thoughts-header">
        <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 200px;">
          <div style="width: 34px; height: 34px; border-radius: 8px; background: #E6F4F4; color: #115E59; display: flex; align-items: center; justify-content: center; font-size: 18px;">
            💡
          </div>
          <div>
            <h3 id="current_thought_title" style="margin: 0; font-size: 15px; font-weight: 800; color: #0F172A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 420px;">
              {{ $activeConversation ? $activeConversation->title : 'Nuevo Pensamiento Estratégico' }}
            </h3>
            <div style="font-size: 11.5px; color: #64748B; margin-top: 1px;" id="current_thought_subtitle">
              {{ $activeConversation ? 'Pensamiento guardado en memoria • ' . $activeConversation->updated_at->format('d/m/Y H:i') : 'Inicia un diálogo para razonar con el Cerebro Suitable' }}
            </div>
          </div>
        </div>

        <!-- SELECTOR DE MOTOR DE IA -->
        <div style="display: flex; align-items: center; gap: 10px;">
          <label style="font-size: 11.5px; font-weight: 700; color: #475569; margin: 0; display: flex; align-items: center; gap: 4px;">
            <span>Motor:</span>
          </label>
          <select id="chat_provider_select" class="form-control" style="font-size: 12px; padding: 6px 10px; height: auto; min-width: 210px; border-radius: 6px; font-weight: 600;">
            @foreach($providersStatus as $pid => $pinfo)
              <option value="{{ $pid }}" {{ $pid === $activeProvider ? 'selected' : '' }}>
                {{ $pinfo['active'] ? '🟢' : '🔴' }} {{ $pinfo['name'] }}
              </option>
            @endforeach
          </select>
          <a href="{{ route('settings.index') }}" title="Configurar credenciales en Ajustes" style="font-size: 16px; color: #64748B; text-decoration: none;">⚙️</a>
        </div>
      </div>

      <!-- TIMELINE DE MENSAJES -->
      <div class="thoughts-messages" id="chat_messages_timeline">
        @if(!$activeConversation || $activeConversation->messages->isEmpty())
          <!-- EMPTY STATE CON CHIPS DE SUGERENCIA -->
          <div id="chat_empty_hero" style="text-align: center; max-width: 640px; margin: 30px auto; padding: 10px;">
            <div style="width: 58px; height: 58px; border-radius: 16px; background: linear-gradient(135deg, #1E8888 0%, #115E59 100%); color: white; display: inline-flex; align-items: center; justify-content: center; font-size: 28px; box-shadow: 0 4px 14px rgba(30,136,136,0.3); margin-bottom: 14px;">
              🧠
            </div>
            <h2 style="font-size: 19px; font-weight: 800; color: #0F172A; margin: 0 0 8px 0;">
              Cerebro Suitable AI — Asesor Estratégico
            </h2>
            <p style="font-size: 13px; color: #64748B; line-height: 1.5; margin: 0 0 20px 0;">
              Tengo acceso en tiempo real a las ventas de Suitable (${{ number_format($totalRevenue, 0, ',', '.') }} CLP, {{ $totalOrders }} pedidos), catálogo técnico Flex 4-Way, ciclo de reposición de 114 días y los {{ $totalClients }} contactos en el CRM.
              <br><strong>¿Qué oportunidad comercial, modelo de negocio o idea deseas potenciar hoy?</strong>
            </p>

            <div class="quick-chips-grid">
              <div class="quick-chip-card" onclick="useQuickPrompt('¿Cómo podemos estructurar una propuesta comercial irresistible para clínicas privadas de más de 50 profesionales de la salud aprovechando el servicio de tallaje en terreno y 6 meses de garantía?')">
                <div class="quick-chip-title">
                  <span>🏥</span>
                  <span>Estrategia B2B Clínicas</span>
                </div>
                <div class="quick-chip-desc">
                  Propuesta comercial con tallaje a domicilio y garantía de 6 meses.
                </div>
              </div>

              <div class="quick-chip-card" onclick="useQuickPrompt('El ticket promedio actual es de ${{ number_format($avgOrderValue, 0, ',', '.') }} CLP. Diseña una estrategia concreta de cross-selling con gorros personalizados y bordados para elevar el AOV sobre $400.000 CLP.')">
                <div class="quick-chip-title">
                  <span>💳</span>
                  <span>Maximizar Ticket (AOV)</span>
                </div>
                <div class="quick-chip-desc">
                  Cross-selling de accesorios técnicos para subir compras institucionales.
                </div>
              </div>

              <div class="quick-chip-card" onclick="useQuickPrompt('Tengo contactos categorizados en listas como \'Clientes Antiguos\' y \'Encuesta\'. ¿Qué gancho o promoción textil podemos proponerles esta semana para reactivar recompras inmediatas?')">
                <div class="quick-chip-title">
                  <span>🔄</span>
                  <span>Reactivación de Clientes</span>
                </div>
                <div class="quick-chip-desc">
                  Campaña y oferta para recuperar clínicas del ciclo de 114 días.
                </div>
              </div>

              <div class="quick-chip-card" onclick="useQuickPrompt('Proyectamos una demanda textil de ${{ number_format($forecast90, 0, ',', '.') }} CLP para los próximos 90 días. ¿Cómo deberíamos programar el abastecimiento de telas Flex 4-Way y la capacidad del taller?')">
                <div class="quick-chip-title">
                  <span>🧵</span>
                  <span>Demanda &amp; Stock de Telas</span>
                </div>
                <div class="quick-chip-desc">
                  Planificación fabril para absorber peak de dotaciones sin quiebres.
                </div>
              </div>
            </div>
          </div>
        @else
          <!-- MENSAJES GUARDADOS -->
          @foreach($activeConversation->messages as $msg)
            <div class="chat-msg {{ $msg->role }}">
              <div class="chat-avatar">
                {{ $msg->role === 'user' ? '👤' : '🧠' }}
              </div>
              <div class="chat-bubble">
                <div class="chat-msg-header">
                  <strong>{{ $msg->role === 'user' ? 'Tú' : 'Cerebro Suitable (' . strtoupper($msg->provider ?: 'AI') . ')' }}</strong>
                  <span>{{ $msg->created_at->format('H:i') }}</span>
                </div>
                @if($msg->role === 'user')
                  <div style="white-space: pre-wrap; word-break: break-word;">{{ $msg->content }}</div>
                @else
                  <div class="markdown-body" id="msg_body_{{ $msg->id }}">
                    {!! nl2br(e($msg->content)) !!}
                  </div>
                @endif
              </div>
            </div>
          @endforeach
        @endif
      </div>

      <!-- INPUT BAR DE ESCRITURA -->
      <div class="chat-input-bar">
        <div class="chat-input-wrapper">
          <textarea id="chat_user_input" class="chat-textarea" placeholder="Escribe tu consulta o propone una idea de negocio... (Presiona Enter para enviar, Shift+Enter para nueva línea)" rows="2" onkeydown="handleChatKey(event)"></textarea>
          <button type="button" class="chat-send-btn" id="btn_send_chat" onclick="sendChatMessage()">
            <span>Enviar</span>
            <span>🚀</span>
          </button>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #94A3B8;">
          <span>💡 El agente razona con mentalidad CCO B2B y guarda automáticamente cada pensamiento en la memoria del sistema.</span>
          <span id="chat_status_indicator">Listo</span>
        </div>
      </div>

    </div>

  </div>

</div>

<!-- JAVASCRIPT REACTIVO & CONTROLADORES DE CEREBRO SUITABLE -->
<script>
  // RUTAS ABSOLUTAS DINÁMICAS BASADAS EN LARAVEL
  const BRAIN_ROUTES = {
    conversations: "{{ url('ml-brain/conversations') }}",
    createConversation: "{{ route('ml_brain.create_conversation') }}",
    diagnostic: "{{ route('ml_brain.diagnostic') }}",
    sync: "{{ route('woocommerce.sync') }}",
  };

  let currentConversationId = {{ $activeConversation ? $activeConversation->id : 'null' }};
  let lastDiagnosticText = '';

  // PARSER DE MARKDOWN ROBUSTO CON FALLBACK INTEGRADO
  function parseMarkdown(md) {
    if (!md) return '';
    try {
      if (typeof marked !== 'undefined' && marked.parse) {
        return marked.parse(md);
      }
    } catch (e) {
      console.warn('Error en marked.parse, usando parser nativo:', e);
    }
    return nativeMarkdownParser(md);
  }

  function nativeMarkdownParser(text) {
    let html = text
      .replace(/^### (.*$)/gim, '<h3>$1</h3>')
      .replace(/^## (.*$)/gim, '<h2>$1</h2>')
      .replace(/^# (.*$)/gim, '<h1>$1</h1>')
      .replace(/\*\*(.*?)\*\*/gim, '<strong>$1</strong>')
      .replace(/\*(.*?)\*/gim, '<em>$1</em>')
      .replace(/`([^`]+)`/gim, '<code>$1</code>')
      .replace(/^\> (.*$)/gim, '<blockquote>$1</blockquote>')
      .replace(/^---$/gim, '<hr>');

    // Listas con viñetas
    html = html.replace(/^\s*[\*\-]\s+(.*$)/gim, '<ul><li>$1</li></ul>');
    html = html.replace(/<\/ul>\s*<ul>/gim, '');

    // Listas numeradas
    html = html.replace(/^\s*\d+\.\s+(.*$)/gim, '<ol><li>$1</li></ol>');
    html = html.replace(/<\/ol>\s*<ol>/gim, '');

    // Párrafos y saltos
    html = html.split('\n\n').map(p => {
      if (p.trim().startsWith('<h') || p.trim().startsWith('<ul') || p.trim().startsWith('<ol') || p.trim().startsWith('<blockquote') || p.trim().startsWith('<table') || p.trim().startsWith('<hr')) {
        return p;
      }
      return '<p>' + p.replace(/\n/g, '<br>') + '</p>';
    }).join('\n');

    return html;
  }

  // AL CARGAR LA PÁGINA, RENDERIZAR MENSAJES MARKDOWN EXISTENTES
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.thoughts-messages .markdown-body').forEach(el => {
      const raw = el.innerText || el.textContent;
      if (raw && (raw.includes('#') || raw.includes('*') || raw.includes('|') || raw.includes('- '))) {
        el.innerHTML = parseMarkdown(raw);
      }
    });
    scrollChatToBottom();
  });

  // CAMBIO DE PESTAÑAS
  function switchBrainTab(tab) {
    const paneMetrics = document.getElementById('brain_pane_metrics');
    const paneThoughts = document.getElementById('brain_pane_thoughts');
    const btnMetrics = document.getElementById('tab_btn_metrics');
    const btnThoughts = document.getElementById('tab_btn_thoughts');

    if (tab === 'thoughts') {
      paneMetrics.style.display = 'none';
      paneThoughts.style.display = 'block';
      btnMetrics.classList.remove('active');
      btnThoughts.classList.add('active');
      scrollChatToBottom();
      document.getElementById('chat_user_input').focus();
    } else {
      paneThoughts.style.display = 'none';
      paneMetrics.style.display = 'block';
      btnThoughts.classList.remove('active');
      btnMetrics.classList.add('active');
    }

    // Actualizar URL sin recargar
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url);
  }

  // SINCRONIZAR WOOCOMMERCE
  async function syncWooCommerce(btn) {
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span>⏳ Sincronizando...</span>';
    btn.disabled = true;

    try {
      const token = document.querySelector('meta[name="csrf-token"]').content;
      const res = await fetch(BRAIN_ROUTES.sync, {
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

  // EJECUTAR DIAGNÓSTICO IA (MARKDOWN RESPETADO)
  async function runAiDiagnostic() {
    const box = document.getElementById('ai_diagnostic_box');
    const container = document.getElementById('diagnostic_markdown_container');
    const badgeEl = document.getElementById('diagnostic_provider_badge');

    box.style.display = 'block';
    container.innerHTML = `
      <div style="display: flex; align-items: center; gap: 10px; color: #115E59; padding: 10px 0;">
        <span class="spinner-border spinner-border-sm" role="status"></span>
        <span style="font-weight: 600;">El Cerebro Suitable está analizando los 125 pedidos de WooCommerce, ticket promedio y proyecciones de demanda textil...</span>
      </div>
    `;
    badgeEl.innerText = 'Razonando...';

    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    try {
      const token = document.querySelector('meta[name="csrf-token"]').content;
      const provider = document.getElementById('chat_provider_select') ? document.getElementById('chat_provider_select').value : 'groq';

      const res = await fetch(BRAIN_ROUTES.diagnostic, {
        method: 'POST',
        headers: { 
          'X-CSRF-TOKEN': token, 
          'Accept': 'application/json', 
          'Content-Type': 'application/json' 
        },
        body: JSON.stringify({ provider: provider })
      });

      const data = await res.json();

      if (data.success && data.diagnostic) {
        lastDiagnosticText = data.diagnostic;
        container.innerHTML = parseMarkdown(data.diagnostic);
        badgeEl.innerHTML = `<span style="color:#059669;">●</span> Motor: <strong>${data.provider}</strong> ${data.model ? '(' + data.model + ')' : ''}`;
        showToast('Diagnóstico estratégico de IA generado exitosamente', 'success');
      } else {
        container.innerHTML = `<div style="color: #DC2626; padding: 10px;">⚠️ ${data.error || 'No se pudo generar el diagnóstico con este motor.'}</div>`;
        badgeEl.innerText = 'Error';
      }
    } catch (e) {
      container.innerHTML = '<div style="color: #DC2626; padding: 10px;">Ocurrió un error al procesar el diagnóstico con IA. Verifique su API Key en Ajustes.</div>';
      badgeEl.innerText = 'Error';
      showToast('Error al invocar IA', 'error');
    }
  }

  // INICIAR DIÁLOGO DESDE EL DIAGNÓSTICO
  async function startDialogFromDiagnostic() {
    switchBrainTab('thoughts');

    const initialPrompt = lastDiagnosticText 
      ? "He generado el siguiente Diagnóstico Ejecutivo de Suitable:\n\n" + lastDiagnosticText + "\n\n¿Por cuál de estas recomendaciones estratégicas me sugieres comenzar primero y cuál es el plan de acción para esta semana?"
      : "¿Cuáles son las 3 acciones comerciales más urgentes para captar convenios clínicos con Suitable este mes?";

    await createAndOpenThought("Diagnóstico Estratégico Inicial", initialPrompt);
  }

  // SUGERENCIAS RÁPIDAS
  function useQuickPrompt(text) {
    document.getElementById('chat_user_input').value = text;
    sendChatMessage();
  }

  // ENVIAR MENSAJE DE CHAT
  async function sendChatMessage() {
    const input = document.getElementById('chat_user_input');
    const text = input.value.trim();
    if (!text) return;

    const btn = document.getElementById('btn_send_chat');
    const provider = document.getElementById('chat_provider_select').value;
    const timeline = document.getElementById('chat_messages_timeline');
    const statusEl = document.getElementById('chat_status_indicator');

    btn.disabled = true;
    input.disabled = true;
    statusEl.innerHTML = '<span style="color:#1E8888;">🧠 Razonando respuesta...</span>';

    const emptyHero = document.getElementById('chat_empty_hero');
    if (emptyHero) emptyHero.remove();

    appendMessageToTimeline('user', text, 'Tú', new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
    input.value = '';
    scrollChatToBottom();

    const loadingId = 'loading_bubble_' + Date.now();
    appendLoadingBubble(loadingId);
    scrollChatToBottom();

    try {
      const token = document.querySelector('meta[name="csrf-token"]').content;

      // Si no hay conversación activa, primero la creamos
      if (!currentConversationId) {
        const createRes = await fetch(BRAIN_ROUTES.createConversation, {
          method: 'POST',
          headers: { 
            'X-CSRF-TOKEN': token, 
            'Accept': 'application/json', 
            'Content-Type': 'application/json' 
          },
          body: JSON.stringify({ 
            title: text.substring(0, 45) + (text.length > 45 ? '...' : ''),
            provider: provider
          })
        });
        const createData = await createRes.json();
        if (createData.success && createData.conversation) {
          currentConversationId = createData.conversation.id;
        }
      }

      // Enviar mensaje al endpoint con URL completa
      const sendRes = await fetch(`${BRAIN_ROUTES.conversations}/${currentConversationId}/messages`, {
        method: 'POST',
        headers: { 
          'X-CSRF-TOKEN': token, 
          'Accept': 'application/json', 
          'Content-Type': 'application/json' 
        },
        body: JSON.stringify({ 
          message: text,
          provider: provider
        })
      });

      const sendData = await sendRes.json();
      removeLoadingBubble(loadingId);

      if (sendData.success && sendData.assistant_message) {
        const asst = sendData.assistant_message;
        appendMessageToTimeline(
          'assistant', 
          asst.content, 
          `Cerebro Suitable (${(asst.provider || provider).toUpperCase()})`,
          new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        );

        if (sendData.conversation_title) {
          document.getElementById('current_thought_title').innerText = sendData.conversation_title;
        }

        statusEl.innerText = 'Respuesta completada';
        refreshConversationsSidebar();
      } else {
        const errMsg = sendData.assistant_message ? sendData.assistant_message.content : (sendData.error || 'Error al comunicarse con la IA');
        appendMessageToTimeline('assistant', errMsg, 'Error de IA', '');
        statusEl.innerText = 'Error al responder';
      }
    } catch (e) {
      removeLoadingBubble(loadingId);
      appendMessageToTimeline('assistant', '⚠️ Ocurrió un error inesperado al procesar la solicitud.', 'Sistema', '');
      statusEl.innerText = 'Error de conexión';
    } finally {
      btn.disabled = false;
      input.disabled = false;
      input.focus();
      scrollChatToBottom();
    }
  }

  // ATALHO ENTER PARA ENVIAR
  function handleChatKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendChatMessage();
    }
  }

  // AGREGAR BURBUJA DE MENSAJE AL TIMELINE
  function appendMessageToTimeline(role, content, author, time) {
    const timeline = document.getElementById('chat_messages_timeline');
    const msgDiv = document.createElement('div');
    msgDiv.className = `chat-msg ${role}`;

    const avatar = role === 'user' ? '👤' : '🧠';
    const parsedContent = role === 'user' ? escapeHtml(content) : parseMarkdown(content);

    msgDiv.innerHTML = `
      <div class="chat-avatar">${avatar}</div>
      <div class="chat-bubble">
        <div class="chat-msg-header">
          <strong>${author}</strong>
          <span>${time}</span>
        </div>
        <div class="${role === 'user' ? '' : 'markdown-body'}" style="${role === 'user' ? 'white-space: pre-wrap; word-break: break-word;' : ''}">
          ${parsedContent}
        </div>
      </div>
    `;

    timeline.appendChild(msgDiv);
  }

  // BURBUJA DE CARGA (PENSANDO)
  function appendLoadingBubble(id) {
    const timeline = document.getElementById('chat_messages_timeline');
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'chat-msg assistant';
    loadingDiv.id = id;
    loadingDiv.innerHTML = `
      <div class="chat-avatar">🧠</div>
      <div class="chat-bubble" style="background: #FFFFFF; border: 1px solid #E2E8F0;">
        <div style="display: flex; align-items: center; gap: 8px; color: #1E8888; font-size: 13px;">
          <span class="spinner-border spinner-border-sm" role="status"></span>
          <span>Razonando y estructurando propuesta estratégica...</span>
        </div>
      </div>
    `;
    timeline.appendChild(loadingDiv);
  }

  function removeLoadingBubble(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
  }

  function scrollChatToBottom() {
    const timeline = document.getElementById('chat_messages_timeline');
    if (timeline) {
      timeline.scrollTop = timeline.scrollHeight;
    }
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
  }

  // INICIAR NUEVO PENSAMIENTO EN BLANCO
  function startNewThought() {
    currentConversationId = null;
    document.querySelectorAll('.thought-item').forEach(el => el.classList.remove('active'));
    document.getElementById('current_thought_title').innerText = 'Nuevo Pensamiento Estratégico';
    document.getElementById('current_thought_subtitle').innerText = 'Inicia un diálogo para razonar con el Cerebro Suitable';
    
    const timeline = document.getElementById('chat_messages_timeline');
    timeline.innerHTML = `
      <div id="chat_empty_hero" style="text-align: center; max-width: 640px; margin: 30px auto; padding: 10px;">
        <div style="width: 58px; height: 58px; border-radius: 16px; background: linear-gradient(135deg, #1E8888 0%, #115E59 100%); color: white; display: inline-flex; align-items: center; justify-content: center; font-size: 28px; box-shadow: 0 4px 14px rgba(30,136,136,0.3); margin-bottom: 14px;">
          🧠
        </div>
        <h2 style="font-size: 19px; font-weight: 800; color: #0F172A; margin: 0 0 8px 0;">
          Cerebro Suitable AI — Asesor Estratégico
        </h2>
        <p style="font-size: 13px; color: #64748B; line-height: 1.5; margin: 0 0 20px 0;">
          Tengo acceso en tiempo real a las ventas de Suitable (${{ number_format($totalRevenue, 0, ',', '.') }} CLP, {{ $totalOrders }} pedidos), catálogo técnico Flex 4-Way, ciclo de reposición de 114 días y los {{ $totalClients }} contactos en el CRM.
          <br><strong>¿Qué oportunidad comercial, modelo de negocio o idea deseas potenciar hoy?</strong>
        </p>

        <div class="quick-chips-grid">
          <div class="quick-chip-card" onclick="useQuickPrompt('¿Cómo podemos estructurar una propuesta comercial irresistible para clínicas privadas de más de 50 profesionales de la salud aprovechando el servicio de tallaje en terreno y 6 meses de garantía?')">
            <div class="quick-chip-title">
              <span>🏥</span>
              <span>Estrategia B2B Clínicas</span>
            </div>
            <div class="quick-chip-desc">
              Propuesta comercial con tallaje a domicilio y garantía de 6 meses.
            </div>
          </div>

          <div class="quick-chip-card" onclick="useQuickPrompt('El ticket promedio actual es de ${{ number_format($avgOrderValue, 0, ',', '.') }} CLP. Diseña una estrategia concreta de cross-selling con gorros personalizados y bordados para elevar el AOV sobre $400.000 CLP.')">
            <div class="quick-chip-title">
              <span>💳</span>
              <span>Maximizar Ticket (AOV)</span>
            </div>
            <div class="quick-chip-desc">
              Cross-selling de accesorios técnicos para subir compras institucionales.
            </div>
          </div>

          <div class="quick-chip-card" onclick="useQuickPrompt('Tengo contactos categorizados en listas como \'Clientes Antiguos\' y \'Encuesta\'. ¿Qué gancho o promoción textil podemos proponerles esta semana para reactivar recompras inmediatas?')">
            <div class="quick-chip-title">
              <span>🔄</span>
              <span>Reactivación de Clientes</span>
            </div>
            <div class="quick-chip-desc">
              Campaña y oferta para recuperar clínicas del ciclo de 114 días.
            </div>
          </div>

          <div class="quick-chip-card" onclick="useQuickPrompt('Proyectamos una demanda textil de ${{ number_format($forecast90, 0, ',', '.') }} CLP para los próximos 90 días. ¿Cómo deberíamos programar el abastecimiento de telas Flex 4-Way y la capacidad del taller?')">
            <div class="quick-chip-title">
              <span>🧵</span>
              <span>Demanda &amp; Stock de Telas</span>
            </div>
            <div class="quick-chip-desc">
              Planificación fabril para absorber peak de dotaciones sin quiebres.
            </div>
          </div>
        </div>
      </div>
    `;

    document.getElementById('chat_user_input').value = '';
    document.getElementById('chat_user_input').focus();
  }

  // CREAR Y ABRIR PENSAMIENTO CON PROMPT INICIAL
  async function createAndOpenThought(title, initialPrompt) {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const provider = document.getElementById('chat_provider_select').value;
    const timeline = document.getElementById('chat_messages_timeline');

    startNewThought();
    appendMessageToTimeline('user', initialPrompt, 'Tú', new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));

    const loadingId = 'loading_bubble_' + Date.now();
    appendLoadingBubble(loadingId);
    scrollChatToBottom();

    try {
      const res = await fetch(BRAIN_ROUTES.createConversation, {
        method: 'POST',
        headers: { 
          'X-CSRF-TOKEN': token, 
          'Accept': 'application/json', 
          'Content-Type': 'application/json' 
        },
        body: JSON.stringify({
          title: title,
          provider: provider,
          initial_prompt: initialPrompt
        })
      });

      const data = await res.json();
      removeLoadingBubble(loadingId);

      if (data.success && data.conversation) {
        currentConversationId = data.conversation.id;
        document.getElementById('current_thought_title').innerText = data.conversation.title;
        document.getElementById('current_thought_subtitle').innerText = 'Pensamiento guardado en memoria • Justo ahora';

        timeline.innerHTML = '';
        data.conversation.messages.forEach(m => {
          appendMessageToTimeline(
            m.role, 
            m.content, 
            m.role === 'user' ? 'Tú' : `Cerebro Suitable (${(m.provider || provider).toUpperCase()})`,
            new Date(m.created_at || Date.now()).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
          );
        });

        refreshConversationsSidebar();
        scrollChatToBottom();
      }
    } catch (e) {
      removeLoadingBubble(loadingId);
      appendMessageToTimeline('assistant', 'Error al crear la conversación con IA.', 'Error', '');
    }
  }

  // CARGAR PENSAMIENTO GUARDADO
  async function loadThought(id) {
    currentConversationId = id;
    document.querySelectorAll('.thought-item').forEach(el => el.classList.remove('active'));
    const itemEl = document.getElementById('thought_item_' + id);
    if (itemEl) itemEl.classList.add('active');

    const timeline = document.getElementById('chat_messages_timeline');
    timeline.innerHTML = `
      <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #64748B; gap: 8px;">
        <span class="spinner-border spinner-border-sm" role="status"></span>
        <span>Cargando conversación guardada...</span>
      </div>
    `;

    try {
      const res = await fetch(`${BRAIN_ROUTES.conversations}/${id}`, {
        headers: { 'Accept': 'application/json' }
      });
      const data = await res.json();

      if (data.success && data.conversation) {
        const conv = data.conversation;
        document.getElementById('current_thought_title').innerText = conv.title;
        document.getElementById('current_thought_subtitle').innerText = `Pensamiento guardado • ${new Date(conv.updated_at).toLocaleString()}`;
        if (conv.provider && document.getElementById('chat_provider_select')) {
          document.getElementById('chat_provider_select').value = conv.provider;
        }

        timeline.innerHTML = '';
        if (conv.messages && conv.messages.length > 0) {
          conv.messages.forEach(m => {
            appendMessageToTimeline(
              m.role, 
              m.content, 
              m.role === 'user' ? 'Tú' : `Cerebro Suitable (${(m.provider || 'AI').toUpperCase()})`,
              new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            );
          });
        } else {
          timeline.innerHTML = '<div style="text-align: center; color: #94A3B8; padding: 40px;">No hay mensajes en esta conversación aún. Escribe para comenzar.</div>';
        }

        scrollChatToBottom();
        document.getElementById('chat_user_input').focus();
      } else {
        timeline.innerHTML = `<div style="color: #DC2626; padding: 20px;">Error al cargar la conversación: ${data.error || 'No encontrada'}</div>`;
      }
    } catch (e) {
      console.error('Error cargando conversacion:', e);
      timeline.innerHTML = '<div style="color: #DC2626; padding: 20px;">Error al cargar la conversación.</div>';
    }
  }

  // ELIMINAR PENSAMIENTO
  async function deleteThought(id) {
    if (!confirm('¿Estás seguro de eliminar este pensamiento de la memoria?')) return;

    try {
      const token = document.querySelector('meta[name="csrf-token"]').content;
      const res = await fetch(`${BRAIN_ROUTES.conversations}/${id}`, {
        method: 'DELETE',
        headers: { 
          'X-CSRF-TOKEN': token, 
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      });
      const data = await res.json();
      if (data.success) {
        showToast('Pensamiento eliminado con éxito', 'info');
        
        // Quitar de inmediato el elemento del DOM
        const el = document.getElementById('thought_item_' + id);
        if (el) el.remove();

        if (currentConversationId == id) {
          startNewThought();
        }
        refreshConversationsSidebar();
      } else {
        showToast('No se pudo eliminar el pensamiento', 'error');
      }
    } catch (e) {
      console.error('Error eliminando pensamiento:', e);
      showToast('Error de conexión al eliminar pensamiento', 'error');
    }
  }

  // ACTUALIZAR SIDEBAR DE CONVERSACIONES
  async function refreshConversationsSidebar() {
    try {
      const res = await fetch(BRAIN_ROUTES.conversations, {
        headers: { 'Accept': 'application/json' }
      });
      const data = await res.json();
      if (data.success && data.conversations) {
        const container = document.getElementById('thoughts_list_container');
        document.getElementById('sidebar_count_badge').innerText = `${data.conversations.length} guardados`;
        const pill = document.getElementById('thoughts_count_pill');
        if (pill) pill.innerText = data.conversations.length;

        if (data.conversations.length === 0) {
          container.innerHTML = `
            <div style="text-align: center; padding: 40px 16px; color: #94A3B8;" id="no_thoughts_placeholder">
              <div style="font-size: 28px; margin-bottom: 8px;">🧠</div>
              <div style="font-size: 12.5px; font-weight: 600; color: #64748B;">No hay pensamientos aún</div>
              <div style="font-size: 11px; margin-top: 4px;">Inicia un diálogo para razonar con el Cerebro Suitable.</div>
            </div>
          `;
          return;
        }

        container.innerHTML = data.conversations.map(c => `
          <div class="thought-item ${currentConversationId == c.id ? 'active' : ''}" 
               id="thought_item_${c.id}" 
               onclick="loadThought(${c.id})">
            <div class="thought-item-title" title="${escapeHtml(c.title)}">${escapeHtml(c.title)}</div>
            <div class="thought-item-meta">
              <span>${c.updated_at_formatted}</span>
              <span class="badge" style="font-size: 10px; background: #F1F5F9; color: #475569;">
                ${c.messages_count} msgs
              </span>
            </div>
            <button type="button" class="thought-item-delete" onclick="event.stopPropagation(); deleteThought(${c.id});" title="Eliminar pensamiento">
              🗑️
            </button>
          </div>
        `).join('');
      }
    } catch (e) {
      console.warn('Error refrescando sidebar de pensamientos:', e);
    }
  }
</script>
@endsection
