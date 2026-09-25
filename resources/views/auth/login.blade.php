<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso Plataforma B2B | Suitable</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v={{ time() }}">
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: radial-gradient(circle at top right, #E0F2F1, #F4F7F8 60%);
      padding: 20px;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    .login-card {
      background: white;
      border-radius: 16px;
      padding: 42px;
      max-width: 440px;
      width: 100%;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(226, 232, 240, 0.8);
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
      font-size: 21px;
      font-weight: 800;
      color: #0F172A;
      margin: 0;
    }
    .login-subtitle {
      font-size: 13px;
      color: #64748B;
      margin-top: 6px;
      margin-bottom: 0;
    }
    .alert-error {
      background-color: #FEE2E2;
      border: 1px solid #FCA5A5;
      color: #991B1B;
      padding: 12px 14px;
      border-radius: 8px;
      font-size: 13px;
      margin-bottom: 20px;
      line-height: 1.4;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-label {
      display: block;
      font-size: 13px;
      font-weight: 700;
      color: #334155;
      margin-bottom: 8px;
    }
    .form-control {
      width: 100%;
      padding: 12px 14px;
      font-size: 14px;
      border: 1px solid #CBD5E1;
      border-radius: 8px;
      box-sizing: border-box;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-control:focus {
      border-color: #1E8888;
      box-shadow: 0 0 0 3px rgba(30, 136, 136, 0.15);
    }
    .btn-submit {
      width: 100%;
      padding: 13px;
      font-size: 14px;
      font-weight: 700;
      color: white;
      background: #1E8888;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.2s, transform 0.1s;
      box-shadow: 0 4px 12px rgba(30, 136, 136, 0.25);
    }
    .btn-submit:hover {
      background: #166969;
    }
    .btn-submit:active {
      transform: scale(0.99);
    }
    .login-footer-badge {
      margin-top: 24px;
      text-align: center;
      font-size: 11.5px;
      color: #94A3B8;
      font-weight: 600;
    }
  </style>
</head>
<body>

  <div class="login-card">
    <div class="login-header">
      <img src="{{ asset('images/logo_suitable.png') }}" alt="Suitable" class="login-logo" onerror="this.src='https://suitable.cl/wp-content/uploads/2025/04/logo_verde-350x128.png'">
      <h1 class="login-title">Plataforma B2B &amp; Outreach</h1>
      <p class="login-subtitle">Orquestación de Envíos y Seguimiento Clínico</p>
    </div>

    @if ($errors->any())
      <div class="alert-error">
        {{ $errors->first() }}
      </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
      @csrf

      <div class="form-group">
        <label class="form-label" for="email">Correo Corporativo</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="ejemplo@suitable.cl" value="{{ old('email') }}" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Contraseña</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn-submit">
        Iniciar Sesión Segura →
      </button>
    </form>

    <div class="login-footer-badge">
      🔒 Suitable Confección Clínica Nacional • Acceso Restringido
    </div>
  </div>

</body>
</html>
