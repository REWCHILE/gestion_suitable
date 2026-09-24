@extends('layouts.app')

@section('title', 'Mis Plantillas • Galería Visual de 20 Diseños B2B | Suitable')

@push('styles')
<style>
  /* MIS PLANTILLAS - ESTILO BREVO / MAILCHIMP VISUAL GALLERY */
  .tpl-gallery-layout {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 28px;
    align-items: start;
  }

  @media (max-width: 900px) {
    .tpl-gallery-layout {
      grid-template-columns: 1fr;
    }
  }

  /* LEFT CATEGORY SIDEBAR */
  .tpl-sidebar {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 16px 12px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    position: sticky;
    top: 20px;
  }

  .tpl-sidebar-title {
    font-size: 11px;
    font-weight: 800;
    color: #94A3B8;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    padding: 4px 10px 10px 10px;
    margin-bottom: 6px;
    border-bottom: 1px solid #F1F5F9;
  }

  .tpl-cat-btn {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    text-decoration: none;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    margin-bottom: 3px;
    text-align: left;
  }

  .tpl-cat-btn:hover {
    background: #F8FAFC;
    color: #1E8888;
  }

  .tpl-cat-btn.active {
    background: #E6F4F4;
    color: #115E59;
    font-weight: 700;
  }

  .tpl-cat-pill {
    background: #F1F5F9;
    color: #64748B;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 12px;
    transition: all 0.15s ease;
  }

  .tpl-cat-btn.active .tpl-cat-pill {
    background: #115E59;
    color: #FFFFFF;
  }

  /* TOP ACTION BAR */
  .tpl-top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 22px;
  }

  .tpl-main-title {
    font-size: 24px;
    font-weight: 800;
    color: #0F172A;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .tpl-count-badge {
    background: #E6F4F4;
    color: #1E8888;
    font-size: 12px;
    font-weight: 800;
    padding: 3px 10px;
    border-radius: 14px;
    border: 1px solid rgba(30, 136, 136, 0.2);
  }

  .tpl-search-input {
    width: 280px;
    padding: 9px 14px 9px 36px;
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    font-size: 13px;
    outline: none;
    transition: all 0.2s ease;
  }

  .tpl-search-input:focus {
    border-color: #1E8888;
    box-shadow: 0 0 0 3px rgba(30, 136, 136, 0.15);
  }

  /* GRID DE PLANTILLAS VISUALES */
  .tpl-visual-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 22px;
  }

  /* TARJETA DE PLANTILLA (ESTILO BREVO) */
  .tpl-card {
    background: #FFFFFF;
    border: 2px solid #E2E8F0;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    transition: all 0.22s ease;
    position: relative;
    cursor: pointer;
  }

  .tpl-card:hover, .tpl-card.selected {
    border-color: #2563EB; /* Borde azul de selección estilo Brevo */
    box-shadow: 0 12px 28px rgba(37, 99, 235, 0.16);
    transform: translateY(-3px);
  }

  /* VIEWPORT MINIATURA DE EMAIL */
  .tpl-viewport {
    position: relative;
    width: 100%;
    height: 410px;
    background: #F8FAFC;
    overflow: hidden;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    justify-content: center;
  }

  /* PISTA DE DESLIZAMIENTO SUAVE VERTICAL (EFECTO BEHANCE / THEMEFOREST) */
  .tpl-scroll-track {
    width: 100%;
    height: 100%;
    position: relative;
    display: flex;
    justify-content: center;
    transform: translateY(0);
    transition: transform 7.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    will-change: transform;
  }

  /* AL POSAR EL MOUSE: DESLIZAMIENTO LENTO DE ARRIBA A ABAJO */
  .tpl-card:hover .tpl-scroll-track {
    transform: translateY(-520px);
  }

  /* AL RETIRAR EL MOUSE: REGRESO SUAVE A LA CABECERA */
  .tpl-card:not(:hover) .tpl-scroll-track {
    transition: transform 1.2s ease-out;
  }

  .tpl-mini-iframe {
    width: 620px;
    height: 1600px;
    border: none;
    pointer-events: none;
    transform-origin: top center;
    background: #FFFFFF;
  }

  /* BADGE INDICADOR DE DESPLAZAMIENTO */
  .tpl-scroll-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(30, 136, 136, 0.9);
    backdrop-filter: blur(4px);
    color: #FFFFFF;
    font-size: 10px;
    font-weight: 800;
    padding: 3px 9px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 4px;
    opacity: 0;
    transform: translateY(-5px);
    transition: all 0.22s ease;
    pointer-events: none;
    z-index: 15;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
  }

  .tpl-card:hover .tpl-scroll-badge {
    opacity: 1;
    transform: translateY(0);
  }

  /* DOCK FLOTANTE DE ACCIONES INFERIOR (CRISTAL OSCURO - NO TAPA EL CORREO) */
  .tpl-floating-dock {
    position: absolute;
    bottom: 12px;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    background: rgba(15, 23, 42, 0.88);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 30px;
    padding: 5px 8px;
    display: flex;
    align-items: center;
    gap: 6px;
    opacity: 0;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 20;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
    white-space: nowrap;
  }

  .tpl-card:hover .tpl-floating-dock {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }

  .btn-dock-use {
    background: #2563EB;
    color: #FFFFFF;
    border: none;
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.4);
    transition: all 0.15s ease;
  }

  .btn-dock-use:hover {
    background: #1D4ED8;
    transform: scale(1.04);
    color: #FFFFFF;
  }

  .btn-dock-action {
    background: rgba(255, 255, 255, 0.15);
    color: #FFFFFF;
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 20px;
    padding: 6px 12px;
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
  }

  .btn-dock-action:hover {
    background: rgba(255, 255, 255, 0.3);
    color: #FFFFFF;
  }

  /* INFO INFERIOR DE LA TARJETA */
  .tpl-card-info {
    padding: 14px 16px;
    background: #FFFFFF;
  }

  .tpl-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
  }

  .tpl-name {
    font-size: 14px;
    font-weight: 800;
    color: #0F172A;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .tpl-tag {
    font-size: 10.5px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 6px;
    background: #F1F5F9;
    color: #475569;
    white-space: nowrap;
  }

  .tpl-desc-mini {
    font-size: 12px;
    color: #64748B;
    line-height: 1.4;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  /* MODAL HD PREVIEW */
  .preview-modal-bg {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(4px);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }

  .preview-modal-box {
    background: #FFFFFF;
    width: 100%;
    max-width: 900px;
    height: 90vh;
    border-radius: 14px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px rgba(0,0,0,0.3);
  }

  .preview-modal-header {
    background: #0F172A;
    color: #FFFFFF;
    padding: 14px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .preview-modal-body {
    flex: 1;
    background: #E2E8F0;
    padding: 20px;
    overflow-y: auto;
    display: flex;
    justify-content: center;
  }

  .preview-modal-iframe {
    background: #FFFFFF;
    width: 600px;
    height: 100%;
    border: none;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    transition: width 0.3s ease;
  }
</style>
@endpush

@section('content')

<!-- TOP TOOLBAR -->
<div class="tpl-top-bar">
  <div>
    <h1 class="tpl-main-title">
      <span>🎨</span>
      <span>Mis plantillas</span>
      <span class="tpl-count-badge" id="visible-count-badge">20 plantillas</span>
    </h1>
    <p style="margin: 4px 0 0 0; color: #64748B; font-size: 13.5px;">
      Catálogo visual interactivo de correos corporativos listos para usar en campañas B2B y editor Brevo
    </p>
  </div>

  <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
    <div style="position: relative;">
      <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 14px;">🔍</span>
      <input type="text" id="tplSearchInput" class="tpl-search-input" placeholder="Buscar por nombre o especialidad..." oninput="handleFilterGrid()">
    </div>

    <a href="{{ route('campaigns.create') }}" class="btn btn-primary" style="background: #1E8888; display: inline-flex; align-items: center; gap: 6px; font-weight: 700; padding: 9px 16px; border-radius: 8px;">
      <span>✨</span>
      <span>Crear en Estudio IA</span>
    </a>
  </div>
</div>

<!-- MAIN GALLERY LAYOUT -->
<div class="tpl-gallery-layout">

  <!-- LEFT FILTER COLUMN -->
  <aside class="tpl-sidebar">
    <div class="tpl-sidebar-title">Categorías &amp; Filtros</div>

    <button type="button" class="tpl-cat-btn {{ $selectedCategory === 'all' ? 'active' : '' }}" onclick="filterCategory('all', this)">
      <span>📑 Todas las plantillas</span>
      <span class="tpl-cat-pill">20</span>
    </button>

    @foreach($categories as $cat)
      @if($cat['slug'] !== 'all')
        <button type="button" class="tpl-cat-btn {{ $selectedCategory === $cat['slug'] ? 'active' : '' }}" onclick="filterCategory('{{ $cat['slug'] }}', this)">
          <span>{{ $cat['icon'] }} {{ $cat['name'] }}</span>
          <span class="tpl-cat-pill">{{ $cat['count'] }}</span>
        </button>
      @endif
    @endforeach

    <div style="margin-top: 16px; padding-top: 14px; border-top: 1px solid #F1F5F9;">
      <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px; font-size: 11.5px; color: #475569;">
        <strong style="color: #0F172A; display: block; margin-bottom: 4px;">💡 ¿Cómo funciona?</strong>
        Pasa el cursor sobre cualquier diseño para ver las acciones rápidas o haz clic para previsualizarlo en tamaño real.
      </div>
    </div>
  </aside>

  <!-- MAIN TEMPLATES GRID -->
  <div>
    <div class="tpl-visual-grid" id="templatesGrid">
      @foreach($allPresets as $p)
        <div class="tpl-card" data-category="{{ $p['category_slug'] ?? 'clinicas' }}" data-name="{{ strtolower($p['name']) }} {{ strtolower($p['description']) }}">
          
          <!-- VIEWPORT CON DESLIZAMIENTO SUAVE AL POSAR EL MOUSE (THEMEFOREST / BEHANCE) -->
          <div class="tpl-viewport">
            <div class="tpl-scroll-track">
              <iframe 
                src="{{ route('campaigns.preset_html', ['preset' => $p['id']]) }}" 
                class="tpl-mini-iframe" 
                loading="lazy" 
                scrolling="no" 
                tabindex="-1"
                title="{{ $p['name'] }}"
              ></iframe>
            </div>

            <!-- BADGE INDICADOR DE DESPLAZAMIENTO -->
            <div class="tpl-scroll-badge">
              <span>⬇️ Desplazando plantilla...</span>
            </div>

            <!-- DOCK FLOTANTE DE ACCIONES (NO TAPA LA PLANTILLA) -->
            <div class="tpl-floating-dock">
              <a href="{{ route('campaigns.create', ['preset' => $p['id']]) }}" class="btn-dock-use" title="Usar esta plantilla">
                <span>🚀 Usar</span>
              </a>
              <button type="button" class="btn-dock-action" onclick="openHdModal('{{ $p['id'] }}', '{{ addslashes($p['name']) }}')" title="Vista Previa HD">
                <span>👁️ HD</span>
              </button>
              <button type="button" class="btn-dock-action" onclick="copyPresetHtmlDirect('{{ $p['id'] }}')" title="Copiar HTML para Brevo">
                <span>📋 Brevo</span>
              </button>
            </div>
          </div>

          <!-- FOOTER DE LA TARJETA -->
          <div class="tpl-card-info">
            <div class="tpl-card-header">
              <span class="tpl-name" title="{{ $p['name'] }}">{{ $p['icon'] }} {{ $p['name'] }}</span>
              <span class="tpl-tag">{{ $p['category'] }}</span>
            </div>
            <p class="tpl-desc-mini">{{ $p['description'] }}</p>
          </div>

        </div>
      @endforeach
    </div>

    <!-- EMPTY STATE -->
    <div id="noResultsMsg" style="display: none; text-align: center; padding: 60px 20px; background: white; border-radius: 12px; border: 1px dashed #CBD5E1;">
      <span style="font-size: 40px; display: block; margin-bottom: 12px;">🔍</span>
      <h3 style="font-size: 18px; font-weight: 800; color: #0F172A; margin: 0 0 6px 0;">No se encontraron plantillas</h3>
      <p style="color: #64748B; font-size: 13px; margin: 0 0 16px 0;">Intenta con otros términos de búsqueda o selecciona otra categoría.</p>
      <button type="button" class="btn btn-secondary btn-sm" onclick="resetFilters()">Restablecer Filtros</button>
    </div>
  </div>

</div>

<!-- MODAL DE VISTA PREVIA HD RESPONSIVE (DESKTOP / MOBILE) -->
<div id="previewModalHd" class="preview-modal-bg" onclick="handleModalBgClick(event)">
  <div class="preview-modal-box">
    
    <!-- HEADER -->
    <div class="preview-modal-header">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 20px;">📧</span>
        <div>
          <h3 id="modalPresetTitle" style="margin: 0; font-size: 16px; font-weight: 800; color: #FFFFFF;">Vista Previa</h3>
          <span style="font-size: 11px; color: #94A3B8;">Editor HTML Brevo Responsive</span>
        </div>
      </div>

      <!-- DEVICE SWITCHER -->
      <div style="display: inline-flex; background: rgba(255,255,255,0.1); border-radius: 6px; padding: 2px;">
        <button type="button" id="btnModalDesktop" onclick="setModalDevice('desktop')" style="border: none; background: #FFFFFF; color: #0F172A; font-size: 11.5px; font-weight: 700; padding: 5px 12px; border-radius: 4px; cursor: pointer;">
          🖥️ Desktop (600px)
        </button>
        <button type="button" id="btnModalMobile" onclick="setModalDevice('mobile')" style="border: none; background: transparent; color: #E2E8F0; font-size: 11.5px; font-weight: 700; padding: 5px 12px; border-radius: 4px; cursor: pointer;">
          📱 Móvil (385px)
        </button>
      </div>

      <div style="display: flex; align-items: center; gap: 8px;">
        <a id="modalUseBtn" href="#" class="btn btn-primary btn-sm" style="background: #1E8888; font-weight: 700; border: none;">
          🚀 Usar y Editar en Estudio IA
        </a>
        <button type="button" onclick="closeHdModal()" style="background: transparent; border: none; color: #94A3B8; font-size: 24px; cursor: pointer; line-height: 1;">
          ✕
        </button>
      </div>
    </div>

    <!-- BODY -->
    <div class="preview-modal-body">
      <iframe id="modalPreviewIframe" class="preview-modal-iframe" src="about:blank"></iframe>
    </div>

  </div>
</div>

@endsection

@push('scripts')
<script>
  let activeCategory = '{{ $selectedCategory }}' || 'all';

  // Auto-escala los iframes para que quepan con precisión milimétrica en cualquier resolución
  function autoScaleMiniIframes() {
    document.querySelectorAll('.tpl-viewport').forEach(vp => {
      const w = vp.clientWidth;
      const iframe = vp.querySelector('.tpl-mini-iframe');
      if (iframe && w > 0) {
        const scale = w / 620;
        iframe.style.transform = `scale(${scale})`;
      }
    });
  }

  window.addEventListener('resize', autoScaleMiniIframes);
  window.addEventListener('load', autoScaleMiniIframes);
  setTimeout(autoScaleMiniIframes, 200);

  // Filtrado por categoría
  function filterCategory(catSlug, btn) {
    activeCategory = catSlug;
    document.querySelectorAll('.tpl-cat-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    handleFilterGrid();
  }

  // Filtrado combinado categoría + búsqueda
  function handleFilterGrid() {
    const q = document.getElementById('tplSearchInput').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.tpl-card');
    let visibleCount = 0;

    cards.forEach(card => {
      const cardCat = card.getAttribute('data-category');
      const cardName = card.getAttribute('data-name');

      const matchesCat = (activeCategory === 'all' || cardCat === activeCategory);
      const matchesSearch = (!q || cardName.includes(q));

      if (matchesCat && matchesSearch) {
        card.style.display = 'flex';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    document.getElementById('visible-count-badge').innerText = `${visibleCount} plantillas`;
    document.getElementById('noResultsMsg').style.display = (visibleCount === 0) ? 'block' : 'none';

    setTimeout(autoScaleMiniIframes, 50);
  }

  function resetFilters() {
    document.getElementById('tplSearchInput').value = '';
    const firstBtn = document.querySelector('.tpl-cat-btn');
    filterCategory('all', firstBtn);
  }

  // MODAL HD
  let currentModalPresetId = '';

  function openHdModal(presetId, presetName) {
    currentModalPresetId = presetId;
    document.getElementById('modalPresetTitle').innerText = presetName;
    document.getElementById('modalUseBtn').href = "{{ url('/campaigns/create') }}?preset=" + presetId;
    
    const iframe = document.getElementById('modalPreviewIframe');
    iframe.src = "{{ url('/campaigns/presets') }}/" + presetId + "/preview";

    setModalDevice('desktop');
    document.getElementById('previewModalHd').style.display = 'flex';
  }

  function closeHdModal() {
    document.getElementById('previewModalHd').style.display = 'none';
    document.getElementById('modalPreviewIframe').src = 'about:blank';
  }

  function handleModalBgClick(e) {
    if (e.target.id === 'previewModalHd') {
      closeHdModal();
    }
  }

  function setModalDevice(device) {
    const iframe = document.getElementById('modalPreviewIframe');
    const btnD = document.getElementById('btnModalDesktop');
    const btnM = document.getElementById('btnModalMobile');

    if (device === 'mobile') {
      iframe.style.width = '385px';
      btnM.style.background = '#FFFFFF';
      btnM.style.color = '#0F172A';
      btnD.style.background = 'transparent';
      btnD.style.color = '#E2E8F0';
    } else {
      iframe.style.width = '600px';
      btnD.style.background = '#FFFFFF';
      btnD.style.color = '#0F172A';
      btnM.style.background = 'transparent';
      btnM.style.color = '#E2E8F0';
    }
  }

  // Copia directa de HTML de preset
  async function copyPresetHtmlDirect(presetId) {
    try {
      const res = await fetch("{{ url('/campaigns/presets') }}/" + presetId + "/preview");
      const html = await res.text();
      await navigator.clipboard.writeText(html);
      alert('✓ Código HTML de la plantilla copiado al portapapeles. ¡Listo para pegar en Brevo!');
    } catch(e) {
      alert('Error al copiar el código HTML');
    }
  }
</script>
@endpush
