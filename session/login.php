<?php
require '../includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['user'];
    $pass = $_POST['password'];

    $stmt = $pdo->prepare("
        SELECT * FROM GM_USERS
        WHERE username = ? OR email = ?
    ");
    $stmt->execute([$user, $user]);
    $u = $stmt->fetch();

    if ($u && password_verify($pass, $u['password'])) {
        $_SESSION['user_id'] = $u['user_id'];
        $_SESSION['nickname'] = $u['nickname'];
        header("Location: ../user/dashboard.php");
        exit;
    }

    $error = "Username / password salah";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Greymist — Masuk ke akun</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/tsparticles@2.12.0/tsparticles.bundle.min.js"></script>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: #FFFFFF;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #333333;
    overflow: hidden;
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
  }
  #tsparticles {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    z-index: 1;
    pointer-events: auto;
  }
  .login-wrapper {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 320px;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .login-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #4B5563;
    letter-spacing: 0.05em;
    margin-bottom: 1.5rem;
    text-transform: uppercase;
  }
  .form-group {
    width: 100%;
    margin-bottom: 0.75rem;
    position: relative;
  }
  .form-control {
    width: 100%;
    background: #F3F4F6;
    border: none;
    padding: 0.75rem 2.5rem 0.75rem 1.25rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    color: #4B5563;
    font-family: inherit;
    transition: all 0.2s;
    outline: none;
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
  }
  .form-control::placeholder { color: #9CA3AF; }
  .form-control:focus {
    background: #E5E7EB;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
  }
  .toggle-pwd {
    position: absolute;
    right: 0.9rem;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #9CA3AF;
    cursor: pointer;
    font-size: 0.85rem;
    padding: 0;
    display: flex;
    align-items: center;
    transition: color 0.2s;
  }
  .toggle-pwd:hover { color: #6B7280; }
  .button-group {
    display: flex;
    gap: 0.75rem;
    width: 100%;
    margin-top: 1rem;
  }
  .btn {
    flex: 1;
    padding: 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    border: none;
    font-family: inherit;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .btn-back { background: #EFF6FF; color: #3B82F6; }
  .btn-back:hover { background: #DBEAFE; color: #2563EB; }
  .btn-login { background: #3B82F6; color: #FFFFFF; }
  .btn-login:hover { background: #2563EB; }
  .btn-loading { pointer-events: none; opacity: 0.75; }
  .error-message {
    background: #FEF2F2;
    color: #EF4444;
    padding: 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.8125rem;
    margin-bottom: 1rem;
    text-align: center;
    width: 100%;
  }
  .utility-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    margin: 0.25rem 0 0.5rem;
  }
  .checkbox-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: #9CA3AF;
    cursor: pointer;
    user-select: none;
  }
  .checkbox-label input { width: 14px; height: 14px; accent-color: #3B82F6; cursor: pointer; }
  .forgot-link { font-size: 0.8rem; color: #9CA3AF; text-decoration: none; transition: color 0.2s; }
  .forgot-link:hover { color: #3B82F6; }
  .register-row {
    margin-top: 1.5rem;
    font-size: 0.8rem;
    color: #9CA3AF;
    display: flex;
    gap: 0.4rem;
    align-items: center;
  }
  .register-row a { color: #3B82F6; text-decoration: none; font-weight: 600; transition: color 0.2s; }
  .register-row a:hover { color: #2563EB; }
</style>
</head>
<body>
<div id="tsparticles"></div>

<div class="login-wrapper">
  <div class="login-title">GREYMIST</div>

  <?php if (!empty($error)): ?>
  <div class="error-message"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form id="loginForm" method="post" style="width:100%;" novalidate>
    <div class="form-group">
      <input type="text" id="username" name="user" class="form-control" placeholder="Email atau username" autocomplete="username" required>
    </div>
    <div class="form-group">
      <input type="password" id="passwordField" name="password" class="form-control" placeholder="Kata sandi" autocomplete="current-password" required>
      <button type="button" class="toggle-pwd" id="togglePassword" aria-label="Tampilkan sandi">
        <i class="far fa-eye-slash" id="toggleIcon"></i>
      </button>
    </div>

    <div class="utility-row">
      <label class="checkbox-label">
        <input type="checkbox" name="remember" id="rememberCheck"> <span>Ingat saya</span>
      </label>
      <a href="#" class="forgot-link" id="forgotLink">Lupa sandi?</a>
    </div>

    <div id="errorContainer"></div>

    <div class="button-group">
      <a href="../index.php" class="btn btn-back">Back</a>
      <button type="submit" class="btn btn-login" id="loginButton">Masuk</button>
    </div>
  </form>

  <div class="register-row">
    <span>Belum punya akun?</span>
    <a href="register.php">Daftar sekarang</a>
  </div>
</div>

<script>
  tsParticles.load("tsparticles", {
    fpsLimit: 60,
    interactivity: {
      events: { onClick: { enable: false }, onHover: { enable: true, mode: "grab" }, resize: true },
      modes: { grab: { distance: 180, links: { opacity: 0.5 } } }
    },
    particles: {
      color: { value: "#60A5FA" },
      links: { color: "#93C5FD", distance: 150, enable: true, opacity: 0.45, width: 1 },
      collisions: { enable: false },
      move: { direction: "none", enable: true, outModes: { default: "bounce" }, random: true, speed: 0.8, straight: false },
      number: { density: { enable: true, area: 800 }, value: 80 },
      opacity: { value: 0.5, animation: { enable: true, speed: 1, minimumValue: 0.1 } },
      shape: { type: "circle" },
      size: { value: { min: 1, max: 3 } }
    },
    detectRetina: true
  });

  (function() {
    const toggleBtn = document.getElementById('togglePassword');
    const passwordField = document.getElementById('passwordField');
    const toggleIcon = document.getElementById('toggleIcon');
    if (toggleBtn && passwordField) {
      toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const isPass = passwordField.getAttribute('type') === 'password';
        passwordField.setAttribute('type', isPass ? 'text' : 'password');
        toggleIcon.classList.toggle('fa-eye-slash', !isPass);
        toggleIcon.classList.toggle('fa-eye', isPass);
      });
    }

    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginButton');
    const errorContainer = document.getElementById('errorContainer');
    const usernameInput = document.getElementById('username');
    const pwdInput = document.getElementById('passwordField');

    function showError(msg) {
      if (errorContainer) errorContainer.innerHTML = `<div class="error-message">${escapeHtml(msg)}</div>`;
    }
    function escapeHtml(str) {
      if (!str) return '';
      return str.replace(/[&<>]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[m]));
    }

    if (loginForm) {
      loginForm.addEventListener('submit', function(e) {
        const u = usernameInput ? usernameInput.value.trim() : '';
        const p = pwdInput ? pwdInput.value.trim() : '';
        if (!u) { e.preventDefault(); showError('Email atau username tidak boleh kosong'); return; }
        if (!p) { e.preventDefault(); showError('Kata sandi harus diisi'); return; }
        if (u.includes('@') && !/^[^\s@]+@([^\s@]+\.)+[^\s@]+$/.test(u)) {
          e.preventDefault(); showError('Format email tidak valid'); return;
        }
        if (loginBtn && !loginBtn.classList.contains('btn-loading')) {
          loginBtn.classList.add('btn-loading');
          loginBtn.disabled = true;
          loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }
      });
    }

    document.getElementById('forgotLink')?.addEventListener('click', function(e) {
      e.preventDefault();
      showError('Fitur reset sandi akan tersedia segera.');
    });

    [usernameInput, pwdInput].forEach(input => {
      input?.addEventListener('input', () => { if (errorContainer) errorContainer.innerHTML = ''; });
    });
  })();
</script>
</body>
</html>
