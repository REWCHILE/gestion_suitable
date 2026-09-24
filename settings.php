<?php
require_once __DIR__ . '/config.php';
require_auth();

$user = current_user();
$db = get_db();

$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $keys = [
        'active_ai_provider', 'groq_api_key', 'groq_model',
        'openai_api_key', 'openai_model', 'claude_api_key', 'claude_model',
        'gemini_api_key', 'gemini_model', 'brevo_api_key',
        'sender_email', 'sender_name', 'wc_store_url', 'wc_consumer_key', 'wc_consumer_secret'
    ];

    foreach ($keys as $k) {
        if (isset($_POST[$k])) {
            set_setting($k, trim($_POST[$k]));
        }
    }
    $success_msg = 'Configuración guardada exitosamente.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ajustes de IA &amp; Plataforma | Suitable</title>
  <link rel="stylesheet" href="assets/css/app.css?v=<?= time() ?>">
  <style>
    .settings-section {
      background: white;
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      padding: 24px;
      margin-bottom: 24px;
      box-shadow: var(--shadow-sm);
    }
    .provider-box {
      border: 1px solid var(--border-light);
      border-radius: var(--radius-sm);
      padding: 16px;
      margin-bottom: 16px;
      background: var(--bg-subtle);
    }
  </style>
</head>
<body>

  <!-- SIDEBAR NAVIGATION -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <main class="main-container" style="max-width: 900px;">
    
    <div class="page-header">
      <div class="page-title-group">
        <h1>Ajustes de Inteligencia Artificial &amp; Conexiones</h1>
        <p class="page-subtitle">Configure sus credenciales para Groq, OpenAI, Claude, Gemini, Brevo y WooCommerce</p>
      </div>
    </div>

    <?php if ($success_msg): ?>
      <div style="background-color: #ECFDF5; border: 1px solid #6EE7B7; border-radius: var(--radius-md); padding: 14px; margin-bottom: 20px; color: #065F46; font-size: 13px; font-weight: 700;">
        ✓ <?= htmlspecialchars($success_msg) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="settings.php">
      <input type="hidden" name="save_settings" value="1">

      <!-- SECCIÓN 1: PROVEEDOR PREDETERMINADO -->
      <div class="settings-section">
        <h2 style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 6px;">
          🤖 Motor de IA Predeterminado
        </h2>
        <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px;">
          Seleccione qué modelo de IA procesará por defecto las solicitudes del orquestador y del Cerebro de ML
        </p>

        <?php $active_prov = get_setting('active_ai_provider', 'groq'); ?>
        <div class="form-group">
          <select name="active_ai_provider" class="form-control" style="font-weight: 700;">
            <option value="groq" <?= $active_prov === 'groq' ? 'selected' : '' ?>>⚡ Groq (Llama 3.3 - Ultra Rápido y Económico)</option>
            <option value="openai" <?= $active_prov === 'openai' ? 'selected' : '' ?>>🧠 OpenAI (ChatGPT GPT-4o / GPT-4o-mini)</option>
            <option value="claude" <?= $active_prov === 'claude' ? 'selected' : '' ?>>🎭 Anthropic (Claude 3.5 Sonnet / Haiku)</option>
            <option value="gemini" <?= $active_prov === 'gemini' ? 'selected' : '' ?>>✨ Google (Gemini 1.5 Flash / 2.0)</option>
          </select>
        </div>
      </div>

      <!-- SECCIÓN 2: LLAVES DE API POR PROVEEDOR -->
      <div class="settings-section">
        <h2 style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 16px;">
          🔑 Credenciales de IA
        </h2>

        <!-- GROQ -->
        <div class="provider-box">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <strong style="color: var(--text-main);">⚡ Groq Cloud</strong>
            <button type="button" class="btn btn-secondary btn-sm" onclick="testKey('groq')">Probar Conexión</button>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" style="font-size: 11px;">API Key (gsk_...)</label>
              <input type="password" name="groq_api_key" id="key-groq" class="form-control" value="<?= htmlspecialchars(get_setting('groq_api_key')) ?>" placeholder="gsk_...">
            </div>
            <div class="form-group">
              <label class="form-label" style="font-size: 11px;">Modelo</label>
              <input type="text" name="groq_model" class="form-control" value="<?= htmlspecialchars(get_setting('groq_model', 'llama-3.3-70b-versatile')) ?>">
            </div>
          </div>
        </div>

        <!-- OPENAI -->
        <div class="provider-box">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <strong style="color: var(--text-main);">🧠 OpenAI (ChatGPT)</strong>
            <button type="button" class="btn btn-secondary btn-sm" onclick="testKey('openai')">Probar Conexión</button>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" style="font-size: 11px;">API Key (sk-proj-...)</label>
              <input type="password" name="openai_api_key" id="key-openai" class="form-control" value="<?= htmlspecialchars(get_setting('openai_api_key')) ?>" placeholder="sk-...">
            </div>
            <div class="form-group">
              <label class="form-label" style="font-size: 11px;">Modelo</label>
              <input type="text" name="openai_model" class="form-control" value="<?= htmlspecialchars(get_setting('openai_model', 'gpt-4o-mini')) ?>">
            </div>
          </div>
        </div>

        <!-- CLAUDE -->
        <div class="provider-box">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <strong style="color: var(--text-main);">🎭 Anthropic (Claude)</strong>
            <button type="button" class="btn btn-secondary btn-sm" onclick="testKey('claude')">Probar Conexión</button>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" style="font-size: 11px;">API Key (sk-ant-...)</label>
              <input type="password" name="claude_api_key" id="key-claude" class="form-control" value="<?= htmlspecialchars(get_setting('claude_api_key')) ?>" placeholder="sk-ant-...">
            </div>
            <div class="form-group">
              <label class="form-label" style="font-size: 11px;">Modelo</label>
              <input type="text" name="claude_model" class="form-control" value="<?= htmlspecialchars(get_setting('claude_model', 'claude-3-5-sonnet-20241022')) ?>">
            </div>
          </div>
        </div>

        <!-- GEMINI -->
        <div class="provider-box">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <strong style="color: var(--text-main);">✨ Google (Gemini)</strong>
            <button type="button" class="btn btn-secondary btn-sm" onclick="testKey('gemini')">Probar Conexión</button>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" style="font-size: 11px;">API Key (AIzaSy...)</label>
              <input type="password" name="gemini_api_key" id="key-gemini" class="form-control" value="<?= htmlspecialchars(get_setting('gemini_api_key')) ?>" placeholder="AIzaSy...">
            </div>
            <div class="form-group">
              <label class="form-label" style="font-size: 11px;">Modelo</label>
              <input type="text" name="gemini_model" class="form-control" value="<?= htmlspecialchars(get_setting('gemini_model', 'gemini-1.5-flash')) ?>">
            </div>
          </div>
        </div>

      </div>

      <!-- SECCIÓN 3: BREVO Y CORREO CORPORATIVO -->
      <div class="settings-section">
        <h2 style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 16px;">
          📬 Brevo (ex Sendinblue) &amp; Envío
        </h2>
        <div class="form-group">
          <label class="form-label">Brevo API Key v3 (xkeysib-...)</label>
          <input type="password" name="brevo_api_key" class="form-control" value="<?= htmlspecialchars(get_setting('brevo_api_key')) ?>" placeholder="xkeysib-...">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Correo Remitente</label>
            <input type="email" name="sender_email" class="form-control" value="<?= htmlspecialchars(get_setting('sender_email', 'ventas@suitable.cl')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Nombre Remitente</label>
            <input type="text" name="sender_name" class="form-control" value="<?= htmlspecialchars(get_setting('sender_name', 'Suitable Uniformes Clínicos')) ?>">
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="padding: 12px 24px; font-size: 14px; font-weight: 700;">
        💾 Guardar Toda la Configuración
      </button>
    </form>

  </main>

  <script src="assets/js/app.js"></script>
  <script>
    async function testKey(provider) {
      showToast(`Probando conexión con ${provider.toUpperCase()}...`, 'success');
      const formData = new FormData();
      formData.append('action', 'test_ai_key');
      formData.append('provider', provider);

      try {
        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          showToast(data.message, 'success');
        } else {
          showToast(data.message || 'Falló la prueba', 'error');
        }
      } catch (err) {
        showToast('Error de comunicación con el servidor', 'error');
      }
    }
  </script>
</body>
</html>
