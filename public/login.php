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
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
  <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
  <link rel="stylesheet" href="assets/css/app.css?v=<?= time() ?>">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: radial-gradient(circle at 15% 20%, rgba(224, 242, 241, 0.7) 0%, transparent 45%),
                  radial-gradient(circle at 85% 80%, rgba(204, 251, 241, 0.6) 0%, transparent 50%),
                  linear-gradient(135deg, #F8FAFC 0%, #EBF4F6 100%);
      padding: 20px;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      overflow: hidden;
      position: relative;
    }
    #interactive-canvas {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      z-index: 1;
      pointer-events: none;
    }
    .login-card {
      position: relative;
      z-index: 10;
      background: rgba(255, 255, 255, 0.90);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border-radius: 20px;
      padding: 44px 38px;
      max-width: 440px;
      width: 100%;
      box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.15),
                  0 0 0 1px rgba(255, 255, 255, 0.8),
                  0 0 0 2px rgba(30, 136, 136, 0.08);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .login-header {
      text-align: center;
      margin-bottom: 28px;
    }
    .login-logo {
      height: 48px;
      margin-bottom: 12px;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.06));
    }
    .login-title {
      font-size: 21px;
      font-weight: 800;
      color: #0F172A;
      margin: 0;
      letter-spacing: -0.02em;
    }
    .login-subtitle {
      font-size: 13px;
      color: #64748B;
      margin-top: 6px;
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
      background: rgba(255, 255, 255, 0.95);
      box-sizing: border-box;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-control:focus {
      border-color: #1E8888;
      box-shadow: 0 0 0 3px rgba(30, 136, 136, 0.15);
      background: #FFFFFF;
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
      transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
      box-shadow: 0 4px 14px rgba(30, 136, 136, 0.35);
    }
    .btn-submit:hover {
      background: #166969;
      box-shadow: 0 6px 18px rgba(30, 136, 136, 0.45);
    }
    .btn-submit:active {
      transform: scale(0.99);
    }
    .login-footer-badge {
      margin-top: 24px;
      text-align: center;
      font-size: 11.5px;
      color: #64748B;
      font-weight: 600;
    }
  </style>
</head>
<body>

  <canvas id="interactive-canvas"></canvas>

  <div class="login-card">
    <div class="login-header">
      <img src="images/logo_suitable.png" alt="Suitable" class="login-logo" onerror="this.src='https://suitable.cl/wp-content/uploads/2025/04/logo_verde-350x128.png'">
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
        <input type="email" id="email" name="email" class="form-control" placeholder="admin@suitable.cl" required autofocus>
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

  <script>
    (function() {
      const canvas = document.getElementById('interactive-canvas');
      const ctx = canvas.getContext('2d');

      let width = window.innerWidth;
      let height = window.innerHeight;
      let dpr = window.devicePixelRatio || 1;

      function resize() {
        width = window.innerWidth;
        height = window.innerHeight;
        canvas.width = width * dpr;
        canvas.height = height * dpr;
        ctx.scale(dpr, dpr);
      }
      resize();
      window.addEventListener('resize', resize);

      const mouse = {
        x: null,
        y: null,
        radius: 170,
        active: false
      };

      window.addEventListener('mousemove', (e) => {
        mouse.x = e.clientX;
        mouse.y = e.clientY;
        mouse.active = true;
      });

      window.addEventListener('mouseleave', () => {
        mouse.active = false;
        mouse.x = null;
        mouse.y = null;
      });

      window.addEventListener('touchmove', (e) => {
        if (e.touches.length > 0) {
          mouse.x = e.touches[0].clientX;
          mouse.y = e.touches[0].clientY;
          mouse.active = true;
        }
      }, { passive: true });

      window.addEventListener('touchend', () => {
        mouse.active = false;
        mouse.x = null;
        mouse.y = null;
      });

      const ripples = [];
      window.addEventListener('mousedown', (e) => {
        ripples.push({
          x: e.clientX,
          y: e.clientY,
          radius: 5,
          maxRadius: 180,
          opacity: 0.7
        });
      });

      const colors = [
        'rgba(30, 136, 136, 0.75)',
        'rgba(13, 148, 136, 0.70)',
        'rgba(20, 184, 166, 0.80)',
        'rgba(45, 212, 191, 0.85)',
        'rgba(56, 189, 248, 0.65)'
      ];

      const particleCount = width < 768 ? 45 : 85;
      const particles = [];

      class Particle {
        constructor() {
          this.reset(true);
        }

        reset(initial = false) {
          this.x = Math.random() * width;
          this.y = Math.random() * height;
          this.vx = (Math.random() - 0.5) * 0.9;
          this.vy = (Math.random() - 0.5) * 0.9;
          this.baseVx = this.vx;
          this.baseVy = this.vy;
          this.radius = Math.random() * 2.4 + 1.6;
          this.color = colors[Math.floor(Math.random() * colors.length)];
          this.angle = Math.random() * Math.PI * 2;
          this.angleSpeed = (Math.random() - 0.5) * 0.02;
        }

        update() {
          this.angle += this.angleSpeed;
          this.x += this.vx + Math.cos(this.angle) * 0.25;
          this.y += this.vy + Math.sin(this.angle) * 0.25;

          this.vx += (this.baseVx - this.vx) * 0.04;
          this.vy += (this.baseVy - this.vy) * 0.04;

          if (mouse.active && mouse.x !== null) {
            const dx = mouse.x - this.x;
            const dy = mouse.y - this.y;
            const dist = Math.sqrt(dx * dx + dy * dy);

            if (dist < mouse.radius) {
              const force = (mouse.radius - dist) / mouse.radius;
              const angle = Math.atan2(dy, dx);
              const pushX = Math.cos(angle) * force * 7;
              const pushY = Math.sin(angle) * force * 7;
              this.vx -= pushX;
              this.vy -= pushY;
            }
          }

          for (let i = 0; i < ripples.length; i++) {
            const rip = ripples[i];
            const rdx = this.x - rip.x;
            const rdy = this.y - rip.y;
            const rdist = Math.sqrt(rdx * rdx + rdy * rdy);
            if (Math.abs(rdist - rip.radius) < 25) {
              const push = (1 - (rip.radius / rip.maxRadius)) * 4;
              const angle = Math.atan2(rdy, rdx);
              this.vx += Math.cos(angle) * push;
              this.vy += Math.sin(angle) * push;
            }
          }

          if (this.x < -20) this.x = width + 20;
          if (this.x > width + 20) this.x = -20;
          if (this.y < -20) this.y = height + 20;
          if (this.y > height + 20) this.y = -20;
        }

        draw() {
          ctx.beginPath();
          ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
          ctx.fillStyle = this.color;
          ctx.shadowBlur = 10;
          ctx.shadowColor = this.color;
          ctx.fill();
          ctx.shadowBlur = 0;
        }
      }

      for (let i = 0; i < particleCount; i++) {
        particles.push(new Particle());
      }

      function animate() {
        ctx.clearRect(0, 0, width, height);

        if (mouse.active && mouse.x !== null) {
          const auraGrad = ctx.createRadialGradient(
            mouse.x, mouse.y, 0,
            mouse.x, mouse.y, mouse.radius
          );
          auraGrad.addColorStop(0, 'rgba(30, 136, 136, 0.14)');
          auraGrad.addColorStop(0.5, 'rgba(45, 212, 191, 0.06)');
          auraGrad.addColorStop(1, 'rgba(255, 255, 255, 0)');
          ctx.fillStyle = auraGrad;
          ctx.beginPath();
          ctx.arc(mouse.x, mouse.y, mouse.radius, 0, Math.PI * 2);
          ctx.fill();
        }

        for (let i = ripples.length - 1; i >= 0; i--) {
          const rip = ripples[i];
          rip.radius += 4.5;
          rip.opacity *= 0.95;

          ctx.beginPath();
          ctx.arc(rip.x, rip.y, rip.radius, 0, Math.PI * 2);
          ctx.strokeStyle = `rgba(30, 136, 136, ${rip.opacity * 0.5})`;
          ctx.lineWidth = 2;
          ctx.stroke();

          if (rip.radius > rip.maxRadius || rip.opacity < 0.02) {
            ripples.splice(i, 1);
          }
        }

        const maxDist = 125;
        for (let i = 0; i < particles.length; i++) {
          for (let j = i + 1; j < particles.length; j++) {
            const dx = particles[i].x - particles[j].x;
            const dy = particles[i].y - particles[j].y;
            const dist = Math.sqrt(dx * dx + dy * dy);

            if (dist < maxDist) {
              const alpha = (1 - (dist / maxDist)) * 0.32;
              ctx.beginPath();
              ctx.moveTo(particles[i].x, particles[i].y);
              ctx.lineTo(particles[j].x, particles[j].y);
              ctx.strokeStyle = `rgba(30, 136, 136, ${alpha})`;
              ctx.lineWidth = 0.9;
              ctx.stroke();
            }
          }

          if (mouse.active && mouse.x !== null) {
            const mdx = particles[i].x - mouse.x;
            const mdy = particles[i].y - mouse.y;
            const mdist = Math.sqrt(mdx * mdx + mdy * mdy);

            if (mdist < 150) {
              const mAlpha = (1 - (mdist / 150)) * 0.55;
              ctx.beginPath();
              ctx.moveTo(particles[i].x, particles[i].y);
              ctx.lineTo(mouse.x, mouse.y);
              ctx.strokeStyle = `rgba(45, 212, 191, ${mAlpha})`;
              ctx.lineWidth = 1.3;
              ctx.stroke();
            }
          }
        }

        for (let i = 0; i < particles.length; i++) {
          particles[i].update();
          particles[i].draw();
        }

        requestAnimationFrame(animate);
      }

      animate();
    })();
  </script>
</body>
</html>
