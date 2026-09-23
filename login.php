<?php
require_once __DIR__ . '/config.php';

$error = '';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $db = get_db();
        $stmt = $db->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Credenciales inválidas. Por favor verifique su correo y contraseña.';
        }
    } else {
        $error = 'Por favor complete todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso Plataforma B2B | Suitable</title>
  <link rel="stylesheet" href="assets/css/app.css">
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: radial-gradient(circle at top right, #E0F2F1, #F4F7F8 60%);
      padding: 20px;
    }
    .login-card {
      background: white;
      border-radius: var(--radius-lg);
      padding: 40px;
      max-width: 440px;
      width: 100%;
      box-shadow: var(--shadow-lg);
      border: 1px solid var(--border-light);
    }
    .login-header {
      text-align: center;
      margin-bottom: 28px;
    }
    .login-logo {
      height: 48px;
      margin-bottom: 12px;
    }
    .login-title {
      font-size: 20px;
      font-weight: 800;
      color: var(--text-main);
    }
    .login-subtitle {
      font-size: 13px;
      color: var(--text-muted);
      margin-top: 4px;
    }
    .demo-users-box {
      margin-top: 24px;
      padding: 16px;
      background-color: var(--bg-subtle);
      border-radius: var(--radius-md);
      border: 1px dashed var(--border-light);
      font-size: 12px;
    }
    .demo-btn-group {
      display: flex;
      gap: 8px;
      margin-top: 10px;
    }
    .alert-error {
      background-color: #FEE2E2;
      border: 1px solid #FCA5A5;
      color: #991B1B;
      padding: 10px 14px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      margin-bottom: 18px;
    }
  </style>
</head>
<body>

  <div class="login-card">
    <div class="login-header">
      <img src="https://suitable.cl/wp-content/uploads/2025/04/logo_verde-350x128.png" alt="Suitable" class="login-logo">
      <h1 class="login-title">Plataforma B2B &amp; Outreach</h1>
      <p class="login-subtitle">Orquestación de Envíos y Seguimiento Clínico</p>
    </div>

    <?php if ($error): ?>
      <div class="alert-error">
        <strong>⚠ Error:</strong> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="form-group">
        <label class="form-label" for="email">Correo Corporativo</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="ejemplo@suitable.cl" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Contraseña</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 14px; margin-top: 8px;">
        Iniciar Sesión Segura →
      </button>
    </form>

    <!-- ACCESO RÁPIDO PARA PRUEBAS -->
    <div class="demo-users-box">
      <span style="font-weight: 700; color: var(--text-main);">⚡ Accesos Rápidos de Demostración:</span>
      <div class="demo-btn-group">
        <button type="button" class="btn btn-secondary btn-sm" onclick="setLogin('admin@suitable.cl', 'admin123')">
          👤 Administrador
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="setLogin('ventas@suitable.cl', 'ventas123')">
          ✉️ Enviador B2B
        </button>
      </div>
    </div>
  </div>

  <script>
    function setLogin(email, pass) {
      document.getElementById('email').value = email;
      document.getElementById('password').value = pass;
    }
  </script>
</body>
</html>
