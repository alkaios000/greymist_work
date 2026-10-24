<?php
require '../includes/db.php';
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        $stmt = $pdo->prepare("
            INSERT INTO GM_USERS (nickname, username, email, password)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $_POST['nickname'],
            $_POST['username'],
            $_POST['email'],
            password_hash($_POST['password'], PASSWORD_DEFAULT)
        ]);

        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['nickname'] = $_POST['nickname'];

        header("Location: ../user/dashboard.php");
        exit;

    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            // Cek field mana yang duplikat
            $msg = $e->getMessage();
            if (stripos($msg, 'username') !== false) {
                $error = 'Username "' . htmlspecialchars($_POST['username']) . '" sudah digunakan, coba username lain.';
            } elseif (stripos($msg, 'email') !== false) {
                $error = 'Email "' . htmlspecialchars($_POST['email']) . '" sudah terdaftar, gunakan email lain.';
            } else {
                $error = 'Data yang kamu masukkan sudah terdaftar sebelumnya.';
            }
        } else {
            $error = 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.';
        }
    }

}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Greymist · Daftar</title>
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
  .register-wrapper {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 320px;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .register-title {
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
  .strength-meter {
    margin-top: 4px;
    height: 3px;
    background: #E5E7EB;
    border-radius: 3px;
    overflow: hidden;
  }
  .strength-fill {
    width: 0%;
    height: 100%;
    background: #D1D5DB;
    border-radius: 3px;
    transition: width 0.2s, background 0.2s;
  }
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
  .btn-register { background: #3B82F6; color: #FFFFFF; }
  .btn-register:hover { background: #2563EB; }
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
  .success-message {
    background: #F0FDF4;
    color: #16A34A;
    padding: 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.8125rem;
    margin-bottom: 1rem;
    text-align: center;
    width: 100%;
  }
  .login-row {
    margin-top: 1.5rem;
    font-size: 0.8rem;
    color: #9CA3AF;
    display: flex;
    gap: 0.4rem;
    align-items: center;
  }
  .login-row a { color: #3B82F6; text-decoration: none; font-weight: 600; transition: color 0.2s; }
  .login-row a:hover { color: #2563EB; }
</style>
</head>
<body>
<div id="tsparticles"></div>

<div class="register-wrapper">
  <div class="register-title">GREYMIST</div>

  <?php if (!empty($error)): ?>
  <div class="error-message"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
  <div class="success-message"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form id="registerForm" method="post" style="width:100%;" novalidate>
    <div class="form-group">
      <input type="text" id="nickname" name="nickname" class="form-control" placeholder="Nama panggilan" autocomplete="nickname" required>
    </div>
    <div class="form-group">
      <input type="text" id="username" name="username" class="form-control" placeholder="Username" autocomplete="username" required>
    </div>
    <div class="form-group">
      <input type="email" id="email" name="email" class="form-control" placeholder="Email" autocomplete="email" required>
    </div>
    <div class="form-group">
      <input type="password" id="passwordField" name="password" class="form-control" placeholder="Kata sandi (min. 6 karakter)" autocomplete="new-password" required>
      <button type="button" class="toggle-pwd" id="togglePassword" aria-label="Tampilkan sandi">
        <i class="far fa-eye-slash" id="toggleIcon"></i>
      </button>
      <div class="strength-meter"><div class="strength-fill" id="strengthFill"></div></div>
    </div>

    <div id="messageContainer"></div>

    <div class="button-group">
      <a href="login.php" class="btn btn-back">Back</a>
      <button type="submit" class="btn btn-register" id="registerButton">Daftar</button>
    </div>
  </form>

  <div class="login-row">
    <span>Sudah punya akun?</span>
    <a href="login.php">Masuk</a>
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
    const strengthFill = document.getElementById('strengthFill');

    if (toggleBtn && passwordField) {
      toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const isPass = passwordField.getAttribute('type') === 'password';
        passwordField.setAttribute('type', isPass ? 'text' : 'password');
        toggleIcon.classList.toggle('fa-eye-slash', !isPass);
        toggleIcon.classList.toggle('fa-eye', isPass);
      });
    }

    if (passwordField && strengthFill) {
      passwordField.addEventListener('input', function() {
        const val = passwordField.value;
        if (!val) { strengthFill.style.width = '0%'; return; }
        let s = 0;
        if (val.length >= 6) s++;
        if (val.length >= 10) s++;
        if (/[A-Z]/.test(val)) s++;
        if (/[0-9]/.test(val)) s++;
        if (/[^A-Za-z0-9]/.test(val)) s++;
        const colors = ['#EF4444','#F97316','#EAB308','#22C55E','#16A34A'];
        const widths = [20, 40, 60, 80, 100];
        strengthFill.style.width = widths[s - 1] + '%';
        strengthFill.style.background = colors[s - 1] || '#D1D5DB';
      });
    }

    const registerForm = document.getElementById('registerForm');
    const registerBtn = document.getElementById('registerButton');
    const messageContainer = document.getElementById('messageContainer');
    const nicknameInput = document.getElementById('nickname');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const pwdInput = document.getElementById('passwordField');

    function showMessage(type, text) {
      if (messageContainer) {
        messageContainer.innerHTML = `<div class="${type}-message">${escapeHtml(text)}</div>`;
      }
    }
    function escapeHtml(str) {
      if (!str) return '';
      return str.replace(/[&<>]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[m]));
    }

    if (registerForm) {
      registerForm.addEventListener('submit', function(e) {
        if (messageContainer) messageContainer.innerHTML = '';
        const n = nicknameInput?.value.trim();
        const u = usernameInput?.value.trim();
        const em = emailInput?.value.trim();
        const p = pwdInput?.value;
        if (!n) { e.preventDefault(); showMessage('error','Nama panggilan tidak boleh kosong'); return; }
        if (n.length < 2) { e.preventDefault(); showMessage('error','Nama panggilan minimal 2 karakter'); return; }
        if (!u) { e.preventDefault(); showMessage('error','Username harus diisi'); return; }
        if (u.length < 3) { e.preventDefault(); showMessage('error','Username minimal 3 karakter'); return; }
        if (!/^[a-zA-Z0-9_]+$/.test(u)) { e.preventDefault(); showMessage('error','Username hanya huruf, angka, underscore'); return; }
        if (!em) { e.preventDefault(); showMessage('error','Email harus diisi'); return; }
        if (!/^[^\s@]+@([^\s@]+\.)+[^\s@]+$/.test(em)) { e.preventDefault(); showMessage('error','Format email tidak valid'); return; }
        if (!p) { e.preventDefault(); showMessage('error','Kata sandi harus diisi'); return; }
        if (p.length < 6) { e.preventDefault(); showMessage('error','Kata sandi minimal 6 karakter'); return; }
        if (registerBtn && !registerBtn.classList.contains('btn-loading')) {
          registerBtn.classList.add('btn-loading');
          registerBtn.disabled = true;
          registerBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }
      });
    }

    [nicknameInput, usernameInput, emailInput, pwdInput].forEach(input => {
      input?.addEventListener('input', () => { if (messageContainer) messageContainer.innerHTML = ''; });
    });
  })();
</script>
</body>
</html>