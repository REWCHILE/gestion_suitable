<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_user_data = current_user();
?>
<!-- STYLES EMBEDDED TO PREVENT ANY BROWSER CACHE ISSUES -->
<style>
/* Reset & Critical Left Sidebar Layout */
.app-sidebar {
  position: fixed !important;
  left: 0 !important;
  top: 0 !important;
  bottom: 0 !important;
  height: 100vh !important;
  width: 70px !important;
  background-color: #FFFFFF !important;
  border-right: 1px solid #E2E8F0 !important;
  z-index: 9999 !important;
  display: flex !important;
  flex-direction: column !important;
  transition: width 0.22s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.22s ease !important;
  overflow: hidden !important;
  box-shadow: 2px 0 10px rgba(15, 23, 42, 0.05) !important;
  user-select: none;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
}

/* Hover expansion */
.app-sidebar:hover {
  width: 260px !important;
  box-shadow: 0 10px 35px rgba(15, 23, 42, 0.18) !important;
}

/* Pinned state */
body.sidebar-pinned .app-sidebar {
  width: 260px !important;
  box-shadow: 2px 0 10px rgba(15, 23, 42, 0.05) !important;
}

/* Main Container Left Margin Sync */
.main-container {
  margin-left: 70px !important;
  transition: margin-left 0.22s cubic-bezier(0.4, 0, 0.2, 1) !important;
}
body.sidebar-pinned .main-container {
  margin-left: 260px !important;
}

/* Sidebar Header */
.sidebar-header {
  height: 64px !important;
  padding: 0 14px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  border-bottom: 1px solid #E2E8F0 !important;
  flex-shrink: 0 !important;
  overflow: hidden !important;
  background: #FFFFFF !important;
}

.sidebar-brand {
  display: flex !important;
  align-items: center !important;
  gap: 10px !important;
  text-decoration: none !important;
  min-width: 46px !important;
  overflow: hidden !important;
  justify-content: center !important;
}

.app-sidebar:hover .sidebar-brand,
body.sidebar-pinned .sidebar-brand {
  justify-content: flex-start !important;
}

/* LOGO PROTAGONISMO AL COLAPSAR */
.sidebar-logo-icon-wrap {
  width: 48px !important;
  height: 48px !important;
  min-width: 48px !important;
  border-radius: 12px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  background: #FFFFFF !important;
  border: 1.5px solid #CCFBF1 !important;
  box-shadow: 0 3px 10px rgba(30, 136, 136, 0.18) !important;
  flex-shrink: 0 !important;
  transition: all 0.2s ease !important;
}

.sidebar-logo-icon-wrap img {
  width: 36px !important;
  height: 36px !important;
  object-fit: contain !important;
}

.sidebar-logo-icon-wrap:hover {
  transform: scale(1.06) !important;
  box-shadow: 0 4px 14px rgba(30, 136, 136, 0.28) !important;
  border-color: #1E8888 !important;
}

/* LOGO COMPLETO MÁS GRANDE EN EXPANDIDO */
.sidebar-logo-full {
  height: 38px !important;
  max-width: 165px !important;
  object-fit: contain !important;
  display: none !important;
  opacity: 0;
  transition: opacity 0.2s ease !important;
}

.app-sidebar:hover .sidebar-logo-full,
body.sidebar-pinned .sidebar-logo-full {
  display: block !important;
  opacity: 1 !important;
}

.app-sidebar:hover .sidebar-logo-icon-wrap,
body.sidebar-pinned .sidebar-logo-icon-wrap {
  display: none !important;
}

/* Pin Toggle Button */
.sidebar-pin-btn {
  background: transparent !important;
  border: 1px solid transparent !important;
  border-radius: 6px !important;
  width: 32px !important;
  height: 32px !important;
  display: none !important;
  align-items: center !important;
  justify-content: center !important;
  cursor: pointer !important;
  color: #64748B !important;
  transition: all 0.2s ease !important;
  flex-shrink: 0 !important;
  padding: 0 !important;
}

.app-sidebar:hover .sidebar-pin-btn,
body.sidebar-pinned .sidebar-pin-btn {
  display: flex !important;
}

.sidebar-pin-btn:hover {
  background: #F1F5F9 !important;
  color: #1E8888 !important;
  border-color: #CBD5E1 !important;
}

body.sidebar-pinned .sidebar-pin-btn {
  background: #E6F4F4 !important;
  color: #1E8888 !important;
  border-color: rgba(30, 136, 136, 0.3) !important;
}

.pin-svg {
  transition: transform 0.2s ease, stroke 0.2s ease !important;
  transform: rotate(45deg);
}

body.sidebar-pinned .pin-svg {
  transform: rotate(0deg) !important;
  stroke: #1E8888 !important;
}

/* Division Badge */
.sidebar-division {
  padding: 10px 16px 4px 16px !important;
  display: none !important;
  opacity: 0;
  transition: opacity 0.2s ease !important;
}

.app-sidebar:hover .sidebar-division,
body.sidebar-pinned .sidebar-division {
  display: block !important;
  opacity: 1 !important;
}

.brand-division-tag-mini {
  display: inline-block !important;
  font-size: 10px !important;
  font-weight: 800 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
  background: #E6F4F4 !important;
  color: #1E8888 !important;
  padding: 3px 8px !important;
  border-radius: 4px !important;
  border: 1px solid rgba(30, 136, 136, 0.2) !important;
}

/* Navigation Menu */
.sidebar-nav {
  flex: 1 1 auto !important;
  overflow-y: auto !important;
  overflow-x: hidden !important;
  padding: 8px 8px !important;
}

.sidebar-nav::-webkit-scrollbar {
  width: 4px;
}
.sidebar-nav::-webkit-scrollbar-thumb {
  background: #CBD5E1;
  border-radius: 4px;
}

.sidebar-menu {
  list-style: none !important;
  padding: 0 !important;
  margin: 0 !important;
  display: flex !important;
  flex-direction: column !important;
  gap: 3px !important;
}

.sidebar-link {
  display: flex !important;
  align-items: center !important;
  padding: 8px 10px !important;
  border-radius: 8px !important;
  color: #475569 !important;
  text-decoration: none !important;
  font-size: 13px !important;
  font-weight: 600 !important;
  transition: background-color 0.15s ease, color 0.15s ease !important;
  height: 40px !important;
  overflow: hidden !important;
  white-space: nowrap !important;
}

.sidebar-link:hover {
  background-color: #F8FAFC !important;
  color: #1E8888 !important;
}

.sidebar-link.active {
  background-color: #E6F4F4 !important;
  color: #1E8888 !important;
  font-weight: 700 !important;
  box-shadow: inset 3px 0 0 #1E8888 !important;
}

.sidebar-icon {
  font-size: 18px !important;
  width: 34px !important;
  min-width: 34px !important;
  text-align: center !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  flex-shrink: 0 !important;
}

.sidebar-label {
  opacity: 0;
  margin-left: 8px !important;
  transition: opacity 0.2s ease !important;
  white-space: nowrap !important;
  display: none !important;
}

.app-sidebar:hover .sidebar-label,
body.sidebar-pinned .sidebar-label {
  display: inline-block !important;
  opacity: 1 !important;
}

/* Sidebar Footer: User & Logout */
.sidebar-footer {
  padding: 10px 12px !important;
  border-top: 1px solid #E2E8F0 !important;
  background-color: #F8FAFC !important;
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  flex-shrink: 0 !important;
  height: 60px !important;
  overflow: hidden !important;
}

.sidebar-user {
  display: flex !important;
  align-items: center !important;
  gap: 10px !important;
  overflow: hidden !important;
}

.sidebar-user .user-avatar {
  width: 36px !important;
  height: 36px !important;
  min-width: 36px !important;
  border-radius: 50% !important;
  background: linear-gradient(135deg, #1E8888 0%, #115353 100%) !important;
  color: #FFFFFF !important;
  font-weight: 700 !important;
  font-size: 14px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  box-shadow: 0 2px 6px rgba(30, 136, 136, 0.25) !important;
  flex-shrink: 0 !important;
}

.sidebar-user-info {
  display: none !important;
  opacity: 0;
  transition: opacity 0.2s ease !important;
  overflow: hidden !important;
}

.app-sidebar:hover .sidebar-user-info,
body.sidebar-pinned .sidebar-user-info {
  display: block !important;
  opacity: 1 !important;
}

.sidebar-user-name {
  font-size: 12px !important;
  font-weight: 700 !important;
  color: #0F172A !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  max-width: 130px !important;
  line-height: 1.2 !important;
}

.sidebar-logout-btn {
  text-decoration: none !important;
  font-size: 16px !important;
  padding: 6px !important;
  border-radius: 6px !important;
  transition: all 0.2s ease !important;
  display: none !important;
  color: #64748B !important;
}

.app-sidebar:hover .sidebar-logout-btn,
body.sidebar-pinned .sidebar-logout-btn {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.sidebar-logout-btn:hover {
  background-color: #FEE2E2 !important;
  color: #DC2626 !important;
}
</style>

<!-- SIDEBAR NAVIGATION -->
<aside id="app-sidebar" class="app-sidebar" aria-label="Navegación principal">
  
  <!-- SIDEBAR HEADER: LOGO & PIN -->
  <div class="sidebar-header">
    <a href="index.php" class="sidebar-brand" title="Suitable B2B">
      <div class="sidebar-logo-icon-wrap" title="Suitable Monograma">
        <img src="public/images/logo_icon_suitable.png" alt="Suitable Logo">
      </div>
      <img src="public/images/logo_suitable.png" alt="Suitable" class="sidebar-logo-full">
    </a>
    <button type="button" id="sidebar-pin-btn" class="sidebar-pin-btn" title="Anclar menú fijado a la izquierda" onclick="toggleSidebarPin()">
      <svg class="pin-svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="17" x2="12" y2="22"></line>
        <path d="M5 17h14v-2l-2-2V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v8l-2 2v2z"></path>
      </svg>
    </button>
  </div>

  <!-- DIVISION BADGE -->
  <div class="sidebar-division">
    <span class="brand-division-tag-mini">B2B Outreach</span>
  </div>

  <!-- NAVIGATION MENU -->
  <nav class="sidebar-nav">
    <ul class="sidebar-menu">
      <li>
        <a href="index.php" class="sidebar-link <?= $current_page === 'index.php' ? 'active' : '' ?>" title="Dashboard">
          <span class="sidebar-icon">📊</span>
          <span class="sidebar-label">Dashboard</span>
        </a>
      </li>
      <li>
        <a href="clients.php" class="sidebar-link <?= $current_page === 'clients.php' ? 'active' : '' ?>" title="Pipeline Clínicas">
          <span class="sidebar-icon">👥</span>
          <span class="sidebar-label">Pipeline Clínicas</span>
        </a>
      </li>
      <li>
        <a href="groups.php" class="sidebar-link <?= $current_page === 'groups.php' ? 'active' : '' ?>" title="Grupos & Segmentos">
          <span class="sidebar-icon">🏷️</span>
          <span class="sidebar-label">Grupos</span>
        </a>
      </li>
      <li>
        <a href="csv_import.php" class="sidebar-link <?= $current_page === 'csv_import.php' ? 'active' : '' ?>" title="Importar CSV">
          <span class="sidebar-icon">📥</span>
          <span class="sidebar-label">Importar CSV</span>
        </a>
      </li>
      <li>
        <a href="campaigns.php" class="sidebar-link <?= $current_page === 'campaigns.php' ? 'active' : '' ?>" title="Campañas IA">
          <span class="sidebar-icon">🚀</span>
          <span class="sidebar-label">Campañas IA</span>
        </a>
      </li>
      <li>
        <a href="send_outreach.php" class="sidebar-link <?= $current_page === 'send_outreach.php' ? 'active' : '' ?>" title="Orquestador de Envíos">
          <span class="sidebar-icon">✉️</span>
          <span class="sidebar-label">Orquestador</span>
        </a>
      </li>
      <li>
        <a href="analytics.php" class="sidebar-link <?= $current_page === 'analytics.php' ? 'active' : '' ?>" title="Analítica & Tráfico">
          <span class="sidebar-icon">📈</span>
          <span class="sidebar-label">Analítica &amp; Tráfico</span>
        </a>
      </li>
      <li>
        <a href="ml_brain.php" class="sidebar-link <?= $current_page === 'ml_brain.php' ? 'active' : '' ?>" title="Cerebro de ML">
          <span class="sidebar-icon">🧠</span>
          <span class="sidebar-label">Cerebro ML</span>
        </a>
      </li>
      <li>
        <a href="wc_wizard.php" class="sidebar-link <?= $current_page === 'wc_wizard.php' ? 'active' : '' ?>" title="Wizard WooCommerce">
          <span class="sidebar-icon">🧙</span>
          <span class="sidebar-label">Wizard WooCommerce</span>
        </a>
      </li>
      <li>
        <a href="templates_view.php" class="sidebar-link <?= $current_page === 'templates_view.php' ? 'active' : '' ?>" title="Mis Plantillas (20 Diseños Visuales)">
          <span class="sidebar-icon">🎨</span>
          <span class="sidebar-label">Mis Plantillas (20)</span>
        </a>
      </li>
      <li>
        <a href="settings.php" class="sidebar-link <?= $current_page === 'settings.php' ? 'active' : '' ?>" title="Ajustes & IA">
          <span class="sidebar-icon">⚙️</span>
          <span class="sidebar-label">Ajustes &amp; IA</span>
        </a>
      </li>
    </ul>
  </nav>

  <!-- SIDEBAR FOOTER: USER & LOGOUT -->
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar"><?= strtoupper(substr($current_user_data['name'] ?? 'U', 0, 1)) ?></div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= htmlspecialchars($current_user_data['name'] ?? 'Usuario') ?></div>
        <span class="role-tag <?= ($current_user_data['role'] ?? '') === 'admin' ? 'role-admin' : 'role-enviador' ?>" style="font-size: 10px; padding: 1px 6px;">
          <?= strtoupper($current_user_data['role'] ?? 'ENVIADOR') ?>
        </span>
      </div>
    </div>
    <a href="logout.php" class="sidebar-logout-btn" title="Cerrar sesión">
      <span>🚪</span>
      <span class="sidebar-logout-label">Salir</span>
    </a>
  </div>

</aside>

<!-- IMMEDIATE PIN SCRIPT (NO FLICKER) -->
<script>
(function() {
  if (localStorage.getItem('suitable_sidebar_pinned') === 'true') {
    document.body.classList.add('sidebar-pinned');
  }
})();

function toggleSidebarPin() {
  const isPinned = document.body.classList.toggle('sidebar-pinned');
  localStorage.setItem('suitable_sidebar_pinned', isPinned ? 'true' : 'false');
  if (typeof showToast === 'function') {
    showToast(isPinned ? '📌 Menú lateral anclado a la izquierda' : '🔓 Menú lateral desanclado (modo flotante en hover)', 'success');
  }
}
</script>
