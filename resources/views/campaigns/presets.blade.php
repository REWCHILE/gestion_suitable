@extends('layouts.app')

@section('title', 'Catálogo de 20 Presets de Correo B2B | Suitable')

@push('styles')
<style>
  .presets-header {
    background: linear-gradient(135deg, #0F172A 0%, #134E4A 100%);
    border-radius: 14px;
    padding: 28px 32px;
    color: #FFFFFF;
    margin-bottom: 24px;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
  }
  .presets-title {
    font-size: 24px;
    font-weight: 800;
    margin: 0 0 6px 0;
    letter-spacing: -0.5px;
  }
  .presets-subtitle {
    font-size: 13.5px;
    color: #CCFBF1;
    margin: 0;
    max-width: 620px;
    line-height: 1.5;
  }

  /* CATEGORY CHIPS */
  .category-nav {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 12px;
    margin-bottom: 20px;
    scrollbar-width: thin;
  }
  .category-chip {
    background: #FFFFFF;
    border: 1.5px solid #E2E8F0;
    color: #475569;
    padding: 8px 16px;
    border-radius: 24px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
  }
  .category-chip:hover {
    border-color: #1E8888;
    color: #1E8888;
    background: #F0FDFA;
  }
  .category-chip.active {
    background: #1E8888;
    color: #FFFFFF;
    border-color: #1E8888;
    box-shadow: 0 4px 12px rgba(30, 136, 136, 0.3);
  }
  .chip-counter {
    background: rgba(0, 0, 0, 0.08);
    padding: 2px 7px;
    border-radius: 10px;
    font-size: 11px;
  }
  .category-chip.active .chip-counter {
    background: rgba(255, 255, 255, 0.25);
  }

  /* SEARCH & STATS BAR */
  .filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
    gap: 16px;
    flex-wrap: wrap;
  }
  .search-box {
    position: relative;
    flex: 1;
    max-width: 420px;
  }
  .search-box input {
    width: 100%;
    padding: 10px 14px 10px 38px;
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    font-size: 13px;
    outline: none;
    transition: border-color 0.2s ease;
  }
  .search-box input:focus {
    border-color: #1E8888;
    box-shadow: 0 0 0 3px rgba(30, 136, 136, 0.15);
  }
  .search-box-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
    font-size: 14px;
  }

  /* PRESET CARDS GRID */
  .presets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
  }
  .preset-card {
    background: #FFFFFF;
    border: 1.5px solid #E2E8F0;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.22s ease;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
    position: relative;
  }
  .preset-card:hover {
    border-color: #1E8888;
    transform: translateY(-3px);
    box-shadow: 0 12px 24px rgba(30, 136, 136, 0.12);
  }
  .preset-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
  }
  .preset-category-tag {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 3px 8px;
    border-radius: 6px;
    background: #F1F5F9;
    color: #475569;
  }
  .preset-badge-tag {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    background: #E6F4F4;
    color: #115E59;
  }
  .preset-title {
    font-size: 16px;
    font-weight: 800;
    color: #0F172A;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .preset-desc {
    font-size: 12.5px;
    color: #475569;
    line-height: 1.5;
    margin: 0 0 14px 0;
    min-height: 54px;
  }
  .preset-subject-preview {
    background: #F8FAFC;
    border: 1px dashed #CBD5E1;
    border-radius: 6px;
    padding: 8px 10px;
    font-size: 11.5px;
    color: #334155;
    margin-bottom: 16px;
  }
  .preset-actions {
    display: flex;
    gap: 8px;
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid #F1F5F9;
  }
  .btn-preview-modal {
    flex: 1;
    background: #F8FAFC;
    color: #334155;
    border: 1px solid #CBD5E1;
    padding: 8px 10px;
    font-size: 12px;
    font-weight: 700;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    transition: all 0.15s ease;
  }
  .btn-preview-modal:hover {
    background: #FFFFFF;
    color: #1E8888;
    border-color: #1E8888;
  }
  .btn-use-preset {
    flex: 1.2;
    background: #1E8888;
    color: #FFFFFF;
    border: 1px solid #1E8888;
    padding: 8px 10px;
    font-size: 12px;
    font-weight: 700;
    border-radius: 6px;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    transition: all 0.15s ease;
  }
  .btn-use-preset:hover {
    background: #146161;
    border-color: #146161;
    color: #FFFFFF;
  }

  /* MODAL PREVIEW EN VIVO */
  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(4px);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .modal-container {
    background: #FFFFFF;
    border-radius: 14px;
    width: 100%;
    max-width: 900px;
    height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0,0,0,0.3);
  }
  .modal-header-bar {
    padding: 16px 22px;
    background: #0F172A;
    color: #FFFFFF;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .modal-body-frame {
    flex: 1;
    background: #E2E8F0;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    padding: 16px;
  }
  .preview-iframe {
    width: 620px;
    height: 100%;
    border: none;
    border-radius: 8px;
    background: #FFFFFF;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    transition: width 0.3s ease;
  }
  .view-toggle-btn {
    background: #1E293B;
    border: 1px solid #334155;
    color: #94A3B8;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
  }
  .view-toggle-btn.active {
    background: #1E8888;
    color: #FFFFFF;
    border-color: #1E8888;
  }
</style>
@endpush

@section('content')
<div class="page-container">

  <!-- HEADER -->
  <div class="presets-header">
    <div>
      <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
        <span style="font-size: 24px;">📚</span>
        <h1 class="presets-title">Catálogo de 20 Presets de Correo Corporativo</h1>
      </div>
      <p class="presets-subtitle">
        Estructuras B2B de alto impacto diseñadas para directores médicos, encargados de adquisiciones y comités de compras. Selecciona cualquier plantilla para lanzarla directamente en el Estudio IA.
      </p>
    </div>
    <div style="display: flex; gap: 10px;">
      <a href="{{ route('campaigns.index') }}" class="btn btn-secondary" style="font-weight: 700;">
        ← Volver a Campañas
      </a>
      <a href="{{ route('campaigns.create') }}" class="btn btn-primary" style="background: #1E8888; border-color: #1E8888; font-weight: 700;">
        + Crear en Blanco
      </a>
    </div>
  </div>

  <!-- CATEGORY TABS -->
  <div class="category-nav">
    @foreach($categories as $cat)
      <a href="javascript:void(0)" 
         class="category-chip {{ $cat['slug'] === 'all' ? 'active' : '' }}" 
         onclick="filterCategory('{{ $cat['slug'] }}', this)">
        <span>{{ $cat['icon'] }}</span>
        <span>{{ $cat['name'] }}</span>
        <span class="chip-counter">{{ $cat['count'] }}</span>
      </a>
    @endforeach
  </div>

  <!-- SEARCH & STATS BAR -->
  <div class="filter-bar">
    <div class="search-box">
      <span class="search-box-icon">🔍</span>
      <input type="text" id="presetSearchInput" oninput="searchPresets()" placeholder="Buscar entre los 20 presets por palabra clave, especialidad...">
    </div>
    <div style="font-size: 13px; color: #64748B; font-weight: 600;" id="resultsCounter">
      Mostrando <strong>20 presets</strong> disponibles para Suitable B2B
    </div>
  </div>

  <!-- GRID DE 20 PRESETS -->
  <div class="presets-grid" id="presetsGrid">
    @foreach($allPresets as $p)
      <div class="preset-card" data-category="{{ $p['category_slug'] ?? 'clinicas' }}" data-title="{{ strtolower($p['name'] . ' ' . $p['description'] . ' ' . $p['subject']) }}">
        <div>
          <div class="preset-top">
            <span class="preset-category-tag">{{ $p['category'] }}</span>
            <span class="preset-badge-tag">{{ $p['badge'] }}</span>
          </div>
          <h3 class="preset-title">
            <span>{{ $p['icon'] }}</span>
            <span>{{ $p['name'] }}</span>
          </h3>
          <p class="preset-desc">{{ $p['description'] }}</p>

          <div class="preset-subject-preview">
            <strong style="color: #0F172A; display: block; font-size: 10.5px; text-transform: uppercase; margin-bottom: 2px;">Asunto sugerido:</strong>
            <span style="font-style: italic;">"{{ $p['subject'] }}"</span>
          </div>
        </div>

        <div class="preset-actions">
          <button type="button" class="btn-preview-modal" onclick="openLivePreview('{{ $p['id'] }}', '{{ addslashes($p['name']) }}')">
            <span>👁️</span>
            <span>Ver Diseño</span>
          </button>
          <a href="{{ route('campaigns.create', ['preset' => $p['id']]) }}" class="btn-use-preset">
            <span>🚀</span>
            <span>Usar Preset</span>
          </a>
        </div>
      </div>
    @endforeach
  </div>

</div>

<!-- MODAL PREVIEW EN VIVO CON SWITCH MÓVIL / ESCRITORIO -->
<div class="modal-overlay" id="previewModal">
  <div class="modal-container">
    <div class="modal-header-bar">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 18px;">✉️</span>
        <strong id="modalPresetTitle" style="font-size: 15px;">Vista Previa del Preset</strong>
      </div>
      
      <!-- CONTROLES DESKTOP / MOBILE -->
      <div style="display: flex; align-items: center; gap: 8px;">
        <button type="button" class="view-toggle-btn active" id="btnDesk" onclick="setPreviewDevice('desktop')">
          🖥️ Escritorio (620px)
        </button>
        <button type="button" class="view-toggle-btn" id="btnMob" onclick="setPreviewDevice('mobile')">
          📱 Móvil (375px)
        </button>
        <a href="#" id="modalUseBtn" class="btn btn-sm btn-primary" style="background: #10B981; border-color: #10B981; font-weight: 700; margin-left: 10px;">
          🚀 Usar este Preset en Estudio
        </a>
        <button type="button" onclick="closeLivePreview()" style="background: none; border: none; color: #94A3B8; font-size: 18px; cursor: pointer; padding: 0 8px;">✕</button>
      </div>
    </div>

    <div class="modal-body-frame">
      <iframe src="about:blank" id="modalIframe" class="preview-iframe"></iframe>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  let currentActiveCategory = 'all';

  function filterCategory(categorySlug, element) {
    currentActiveCategory = categorySlug;

    document.querySelectorAll('.category-chip').forEach(el => el.classList.remove('active'));
    if (element) element.classList.add('active');

    searchPresets();
  }

  function searchPresets() {
    const query = document.getElementById('presetSearchInput').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.preset-card');
    let visibleCount = 0;

    cards.forEach(card => {
      const cardCategory = card.getAttribute('data-category');
      const cardText = card.getAttribute('data-title');

      const matchesCat = (currentActiveCategory === 'all' || cardCategory === currentActiveCategory);
      const matchesQuery = (!query || cardText.includes(query));

      if (matchesCat && matchesQuery) {
        card.style.display = 'flex';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    document.getElementById('resultsCounter').innerHTML = `Mostrando <strong>${visibleCount} presets</strong> de 20`;
  }

  function openLivePreview(presetId, presetName) {
    document.getElementById('modalPresetTitle').innerText = presetName;
    document.getElementById('modalIframe').src = "{{ url('/campaigns/presets') }}/" + presetId + "/preview";
    document.getElementById('modalUseBtn').href = "{{ route('campaigns.create') }}?preset=" + presetId;
    document.getElementById('previewModal').style.display = 'flex';
  }

  function closeLivePreview() {
    document.getElementById('previewModal').style.display = 'none';
    document.getElementById('modalIframe').src = 'about:blank';
  }

  function setPreviewDevice(device) {
    const iframe = document.getElementById('modalIframe');
    const btnDesk = document.getElementById('btnDesk');
    const btnMob = document.getElementById('btnMob');

    if (device === 'mobile') {
      iframe.style.width = '375px';
      btnMob.classList.add('active');
      btnDesk.classList.remove('active');
    } else {
      iframe.style.width = '620px';
      btnDesk.classList.add('active');
      btnMob.classList.remove('active');
    }
  }

  // Cerrar modal al presionar ESC o fondo
  document.getElementById('previewModal').addEventListener('click', function(e) {
    if (e.target === this) closeLivePreview();
  });
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLivePreview();
  });
</script>
@endpush
