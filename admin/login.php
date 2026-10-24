<?php
session_start();
require_once '../includes/db.php';

if (isset($_SESSION['admin_id']) && isset($_SESSION['is_admin'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $stmt = $pdo->prepare("SELECT user_id, username, nickname, password, is_admin FROM gm_users WHERE username = ? AND is_admin = 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['user_id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_nickname'] = $admin['nickname'];
            $_SESSION['is_admin'] = true;
            
            header("Location: index.php");
            exit;
        } else {
            $error = 'Username atau password salah, atau Anda bukan admin';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Greymist</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
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
        }

        .form-control {
            width: 100%;
            background: #F3F4F6;
            border: none;
            padding: 0.75rem 1.25rem;
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

        .form-control::placeholder {
            color: #9CA3AF;
        }

        .form-control:focus {
            background: #E5E7EB;
            box-shadow: 0 0 0 2px rgba(156, 163, 175, 0.2);
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
        }

        .btn-back {
            background: #EFF6FF;
            color: #3B82F6;
        }

        .btn-back:hover {
            background: #DBEAFE;
            color: #2563EB;
        }

        .btn-login {
            background: #3B82F6;
            color: #FFFFFF;
        }

        .btn-login:hover {
            background: #2563EB;
        }

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
    </style>
</head>
<body>
    <div id="tsparticles"></div>
    
    <div class="login-wrapper">
        <div class="login-title">GREYMIST</div>
        
        <?php if ($error): ?>
        <div class="error-message">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="post" style="width: 100%;">
            <div class="form-group">
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <div class="button-group">
                <a href="../index.php" class="btn btn-back">Back</a>
                <button type="submit" class="btn btn-login">Login</button>
            </div>
        </form>
    </div>

    <script>
        tsParticles.load("tsparticles", {
            fpsLimit: 60,
            interactivity: {
                events: {
                    onClick: { enable: false },
                    onHover: { enable: true, mode: "grab" },
                    resize: true
                },
                modes: {
                    grab: { distance: 180, links: { opacity: 0.5 } }
                }
            },
            particles: {
                color: { value: "#60A5FA" },
                links: {
                    color: "#93C5FD",
                    distance: 150,
                    enable: true,
                    opacity: 0.45,
                    width: 1
                },
                collisions: { enable: false },
                move: {
                    direction: "none",
                    enable: true,
                    outModes: { default: "bounce" },
                    random: true,
                    speed: 0.8,
                    straight: false
                },
                number: {
                    density: { enable: true, area: 800 },
                    value: 80
                },
                opacity: {
                    value: 0.5,
                    animation: { enable: true, speed: 1, minimumValue: 0.1 }
                },
                shape: { type: "circle" },
                size: { value: { min: 1, max: 3 } }
            },
            detectRetina: true
        });
    </script>
</body>
</html>