<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Suitable Outreach | B2B Uniformes Clínicos')</title>
  
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v={{ time() }}">
  <style>
    /* BRAND SCROLLBAR (SUITABLE TEAL #1E8888) */
    ::-webkit-scrollbar { width: 9px; height: 9px; }
    ::-webkit-scrollbar-track { background: #E6F4F4; border-radius: 6px; }
    ::-webkit-scrollbar-thumb { background: #1E8888; border-radius: 6px; border: 2px solid #E6F4F4; }
    ::-webkit-scrollbar-thumb:hover { background: #156B6B; }
    * { scrollbar-color: #1E8888 #E6F4F4; scrollbar-width: thin; }

    /* RESET & CRITICAL LEFT SIDEBAR */
    .app-sidebar {
      position: fixed !important;
      left: 0 !important; top: 0 !important; bottom: 0 !important;
      height: 100vh !important; width: 70px !important;
      background-color: #FFFFFF !important;
      border-right: 1px solid #E2E8F0 !important;
      z-index: 9999 !important;
      display: flex !important; flex-direction: column !important;
      transition: width 0.22s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.22s ease !important;
      overflow: hidden !important;
      box-shadow: 2px 0 10px rgba(15, 23, 42, 0.05) !important;
      user-select: none;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
    }
    .app-sidebar:hover {
      width: 260px !important;
      box-shadow: 0 10px 35px rgba(15, 23, 42, 0.18) !important;
    }
    body.sidebar-pinned .app-sidebar {
      width: 260px !important;
      box-shadow: 2px 0 10px rgba(15, 23, 42, 0.05) !important;
    }
    .main-container {
      margin-left: 70px !important;
      transition: margin-left 0.22s cubic-bezier(0.4, 0, 0.2, 1) !important;
      padding: 24px 32px;
      min-height: 100vh;
      background-color: #F8FAFC;
    }
    body.sidebar-pinned .main-container {
      margin-left: 260px !important;
    }
    .sidebar-header {
      height: 68px !important; padding: 0 12px !important;
      display: flex !important; align-items: center !important; justify-content: center !important;
      border-bottom: 1px solid #E2E8F0 !important; flex-shrink: 0 !important;
      overflow: hidden !important; background: #FFFFFF !important;
      transition: all 0.22s ease !important;
    }
    .app-sidebar:hover .sidebar-header, body.sidebar-pinned .sidebar-header {
      padding: 0 16px !important;
      justify-content: space-between !important;
    }
    .sidebar-brand {
      display: flex !important; align-items: center !important; gap: 10px !important;
      text-decoration: none !important; min-width: 46px !important; overflow: hidden !important;
      justify-content: center !important;
    }
    .app-sidebar:hover .sidebar-brand, body.sidebar-pinned .sidebar-brand {
      justify-content: flex-start !important;
    }
    /* LOGO PROTAGONISMO AL COLAPSAR */
    .sidebar-logo-icon-wrap {
      width: 48px !important; height: 48px !important; min-width: 48px !important;
      border-radius: 12px !important; display: flex !important; align-items: center !important; justify-content: center !important;
      background: #FFFFFF !important; border: 1.5px solid #CCFBF1 !important;
      box-shadow: 0 3px 10px rgba(30, 136, 136, 0.18) !important; flex-shrink: 0 !important;
      transition: all 0.2s ease !important;
    }
    .sidebar-logo-icon-wrap img {
      width: 36px !important; height: 36px !important; object-fit: contain !important;
    }
    .sidebar-logo-icon-wrap:hover {
      transform: scale(1.06) !important;
      box-shadow: 0 4px 14px rgba(30, 136, 136, 0.28) !important;
      border-color: #1E8888 !important;
    }
    /* LOGO COMPLETO MÁS GRANDE EN EXPANDIDO */
    .sidebar-logo-full {
      height: 38px !important; max-width: 165px !important; object-fit: contain !important;
      display: none !important; opacity: 0; transition: opacity 0.2s ease !important;
    }
    .app-sidebar:hover .sidebar-logo-full, body.sidebar-pinned .sidebar-logo-full {
      display: block !important; opacity: 1 !important;
    }
    .app-sidebar:hover .sidebar-logo-icon-wrap, body.sidebar-pinned .sidebar-logo-icon-wrap {
      display: none !important;
    }
    .sidebar-pin-btn {
      background: transparent !important; border: 1px solid transparent !important;
      border-radius: 6px !important; width: 32px !important; height: 32px !important;
      display: none !important; align-items: center !important; justify-content: center !important;
      cursor: pointer !important; color: #64748B !important; transition: all 0.2s ease !important;
      flex-shrink: 0 !important; padding: 0 !important;
    }
    .app-sidebar:hover .sidebar-pin-btn, body.sidebar-pinned .sidebar-pin-btn {
      display: flex !important;
    }
    .sidebar-pin-btn:hover { background: #F1F5F9 !important; color: #1E8888 !important; border-color: #CBD5E1 !important; }
    body.sidebar-pinned .sidebar-pin-btn { background: #E6F4F4 !important; color: #1E8888 !important; border-color: rgba(30, 136, 136, 0.3) !important; }
    .pin-svg { transition: transform 0.2s ease, stroke 0.2s ease !important; transform: rotate(45deg); }
    body.sidebar-pinned .pin-svg { transform: rotate(0deg) !important; stroke: #1E8888 !important; }
    .sidebar-division { padding: 10px 16px 4px 16px !important; display: none !important; opacity: 0; transition: opacity 0.2s ease !important; }
    .app-sidebar:hover .sidebar-division, body.sidebar-pinned .sidebar-division { display: block !important; opacity: 1 !important; }
    .brand-division-tag-mini {
      display: inline-block !important; font-size: 10px !important; font-weight: 800 !important;
      text-transform: uppercase !important; letter-spacing: 0.5px !important;
      background: #E6F4F4 !important; color: #1E8888 !important;
      padding: 3px 8px !important; border-radius: 4px !important; border: 1px solid rgba(30, 136, 136, 0.2) !important;
    }
    .sidebar-nav { flex: 1 1 auto !important; overflow-y: auto !important; overflow-x: hidden !important; padding: 8px 8px !important; }
    .sidebar-menu { list-style: none !important; padding: 0 !important; margin: 0 !important; display: flex !important; flex-direction: column !important; gap: 3px !important; }
    .sidebar-link {
      display: flex !important; align-items: center !important; padding: 8px 10px !important;
      border-radius: 8px !important; color: #475569 !important; text-decoration: none !important;
      font-size: 13px !important; font-weight: 600 !important; transition: background-color 0.15s ease, color 0.15s ease !important;
      height: 40px !important; overflow: hidden !important; white-space: nowrap !important;
    }
    .sidebar-link:hover { background-color: #F8FAFC !important; color: #1E8888 !important; }
    .sidebar-link.active {
      background-color: #E6F4F4 !important; color: #1E8888 !important; font-weight: 700 !important;
      box-shadow: inset 3px 0 0 #1E8888 !important;
    }
    .sidebar-icon { font-size: 18px !important; width: 34px !important; min-width: 34px !important; text-align: center !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0 !important; }
    .sidebar-label { opacity: 0; margin-left: 8px !important; transition: opacity 0.2s ease !important; white-space: nowrap !important; display: none !important; }
    .app-sidebar:hover .sidebar-label, body.sidebar-pinned .sidebar-label { display: inline-block !important; opacity: 1 !important; }
    .sidebar-footer {
      padding: 10px 10px !important; border-top: 1px solid #E2E8F0 !important;
      background-color: #F8FAFC !important; display: flex !important; align-items: center !important;
      justify-content: center !important; flex-shrink: 0 !important; min-height: 60px !important; overflow: hidden !important;
      flex-direction: column !important; gap: 8px !important;
    }
    .app-sidebar:hover .sidebar-footer, body.sidebar-pinned .sidebar-footer {
      padding: 10px 14px !important;
      flex-direction: row !important;
      justify-content: space-between !important;
    }
    .sidebar-user { display: flex !important; align-items: center !important; gap: 10px !important; overflow: hidden !important; }
    .sidebar-user .user-avatar {
      width: 36px !important; height: 36px !important; min-width: 36px !important;
      border-radius: 50% !important; background: linear-gradient(135deg, #1E8888 0%, #115353 100%) !important;
      color: #FFFFFF !important; font-weight: 700 !important; font-size: 14px !important;
      display: flex !important; align-items: center !important; justify-content: center !important;
      box-shadow: 0 2px 6px rgba(30, 136, 136, 0.25) !important; flex-shrink: 0 !important;
    }
    .sidebar-user-info { display: none !important; opacity: 0; transition: opacity 0.2s ease !important; overflow: hidden !important; }
    .app-sidebar:hover .sidebar-user-info, body.sidebar-pinned .sidebar-user-info { display: block !important; opacity: 1 !important; }
    .sidebar-user-name { font-size: 12px !important; font-weight: 700 !important; color: #0F172A !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; max-width: 95px !important; }

    /* BOTÓN CERRAR SESIÓN ESTILIZADO */
    .sidebar-logout-btn {
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 5px !important;
      background: #FEF2F2 !important;
      color: #DC2626 !important;
      border: 1px solid #FECACA !important;
      border-radius: 6px !important;
      padding: 6px 9px !important;
      font-size: 11px !important;
      font-weight: 700 !important;
      text-decoration: none !important;
      transition: all 0.18s ease !important;
      cursor: pointer !important;
      white-space: nowrap !important;
    }
    .sidebar-logout-btn:hover {
      background: #DC2626 !important;
      color: #FFFFFF !important;
      border-color: #DC2626 !important;
      box-shadow: 0 2px 8px rgba(220, 38, 38, 0.35) !important;
    }
    .sidebar-logout-label {
      display: none !important;
    }
    .app-sidebar:hover .sidebar-logout-label, body.sidebar-pinned .sidebar-logout-label {
      display: inline !important;
    }
  </style>
  @stack('styles')
</head>
<body>

  <!-- IMMEDIATE PIN SCRIPT (NO FLICKER) -->
  <script>
    if (localStorage.getItem('suitable_sidebar_pinned') === 'true') {
      document.body.classList.add('sidebar-pinned');
    }
    function toggleSidebarPin() {
      const isPinned = document.body.classList.toggle('sidebar-pinned');
      localStorage.setItem('suitable_sidebar_pinned', isPinned ? 'true' : 'false');
      if (typeof showToast === 'function') {
        showToast(isPinned ? '📌 Menú lateral anclado a la izquierda' : '🔓 Menú lateral desanclado (modo flotante en hover)', 'success');
      }
    }
  </script>

  <!-- SIDEBAR NAVIGATION -->
  <aside id="app-sidebar" class="app-sidebar" aria-label="Navegación principal">
    
    <!-- HEADER -->
    <div class="sidebar-header">
      <a href="{{ route('dashboard') }}" class="sidebar-brand" title="Suitable B2B">
        <div class="sidebar-logo-icon-wrap" title="Suitable Monograma">
          <img src="{{ asset('images/logo_icon_suitable.png') }}" alt="Suitable Logo">
        </div>
        <img src="{{ asset('images/logo_suitable.png') }}" alt="Suitable" class="sidebar-logo-full">
      </a>
      <button type="button" id="sidebar-pin-btn" class="sidebar-pin-btn" title="Anclar menú fijado a la izquierda" onclick="toggleSidebarPin()">
        <svg class="pin-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="17" x2="12" y2="22"></line>
          <path d="M5 17h14v-2l-2-2V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v8l-2 2v2z"></path>
        </svg>
      </button>
    </div>

    <!-- BADGE -->
    <div class="sidebar-division">
      <span class="brand-division-tag-mini">Laravel 11 • B2B</span>
    </div>

    <!-- MENU -->
    <nav class="sidebar-nav">
      <ul class="sidebar-menu">
        <li>
          <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
            <span class="sidebar-icon">📊</span>
            <span class="sidebar-label">Dashboard</span>
          </a>
        </li>
        <li>
          <a href="{{ route('clients.index') }}" class="sidebar-link {{ request()->routeIs('clients.*') ? 'active' : '' }}" title="Pipeline Clínicas">
            <span class="sidebar-icon">👥</span>
            <span class="sidebar-label">Pipeline Clínicas</span>
          </a>
        </li>
        <li>
          <a href="{{ route('groups.index') }}" class="sidebar-link {{ request()->routeIs('groups.*') ? 'active' : '' }}" title="Grupos & Segmentos">
            <span class="sidebar-icon">🏷️</span>
            <span class="sidebar-label">Grupos</span>
          </a>
        </li>
        <li>
          <a href="{{ route('import.index') }}" class="sidebar-link {{ request()->routeIs('import.*') ? 'active' : '' }}" title="Importar CSV & Brevo">
            <span class="sidebar-icon">📥</span>
            <span class="sidebar-label">Importar Brevo/CSV</span>
          </a>
        </li>
        <li>
          <a href="{{ route('campaigns.index') }}" class="sidebar-link {{ request()->routeIs('campaigns.index') ? 'active' : '' }}" title="Campañas IA">
            <span class="sidebar-icon">🚀</span>
            <span class="sidebar-label">Campañas</span>
          </a>
        </li>
        <li>
          <a href="{{ route('campaigns.presets') }}" class="sidebar-link {{ request()->routeIs('campaigns.presets') ? 'active' : '' }}" title="Mis Plantillas (20 Diseños Visuales)">
            <span class="sidebar-icon">🎨</span>
            <span class="sidebar-label">Mis Plantillas (20)</span>
          </a>
        </li>
        <li>
          <a href="{{ route('campaigns.create') }}" class="sidebar-link {{ request()->routeIs('campaigns.create') ? 'active' : '' }}" title="Estudio Campañas con IA">
            <span class="sidebar-icon">✉️</span>
            <span class="sidebar-label">Estudio IA</span>
          </a>
        </li>
        <li>
          <a href="{{ route('analytics.index') }}" class="sidebar-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}" title="Analítica & Tráfico">
            <span class="sidebar-icon">📈</span>
            <span class="sidebar-label">Analítica &amp; Tráfico</span>
          </a>
        </li>
        <li>
          <a href="{{ route('ml_brain.index') }}" class="sidebar-link {{ request()->routeIs('ml_brain.*') || request()->routeIs('cerebro_suitable.*') ? 'active' : '' }}" title="Cerebro Suitable (Inteligencia & Ventas)">
            <span class="sidebar-icon">🧠</span>
            <span class="sidebar-label">Cerebro Suitable</span>
          </a>
        </li>
        <li>
          <a href="{{ route('woocommerce.index') }}" class="sidebar-link {{ request()->routeIs('woocommerce.*') ? 'active' : '' }}" title="WooCommerce Sync">
            <span class="sidebar-icon">🧙</span>
            <span class="sidebar-label">WooCommerce</span>
          </a>
        </li>
        <li>
          <a href="{{ route('settings.index') }}" class="sidebar-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" title="Ajustes & IA">
            <span class="sidebar-icon">⚙️</span>
            <span class="sidebar-label">Ajustes &amp; SMTP</span>
          </a>
        </li>
      </ul>
    </nav>

    <!-- FOOTER -->
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="user-avatar" title="Usuario Administrador">S</div>
        <div class="sidebar-user-info">
          <div class="sidebar-user-name">Suitable Admin</div>
          <span style="font-size: 10px; color: #1E8888; font-weight: 800;">ADMINISTRADOR</span>
        </div>
      </div>
      <a href="{{ route('logout') }}" class="sidebar-logout-btn" title="Cerrar sesión de la plataforma">
        <span>🚪</span>
        <span class="sidebar-logout-label">Cerrar Sesión</span>
      </a>
    </div>

  </aside>

  <!-- MAIN VIEW CONTAINER -->
  <main class="main-container">
    @yield('content')
  </main>

  <script src="{{ asset('assets/js/app.js') }}"></script>
  @stack('scripts')
</body>
</html>
