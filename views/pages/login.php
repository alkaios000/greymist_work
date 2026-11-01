<?php
/**
 * File: views/pages/login.php
 * Fungsi: Tampilan halaman Login Pengguna Greymist ultra-minimalis, tanpa card pembungkus,
 *         tema putih bersih dengan skala kompak dan animasi interactive node mesh yang jelas.
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — <?= e($site_settings['site_name'] ?? 'Greymist') ?></title>
    <link rel="icon" href="<?= e(base_url('assets/web-icon/greymist.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(base_url('assets/fontawesome-free-6.7.2-web/css/all.min.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">

    <style>
        :root {
            --bg-color: #ffffff;
            --text-title: #0f172a;
            --text-desc: #64748b;
            --text-label: #475569;
            --input-bg: #ffffff;
            --input-border: #cbd5e1;
            --input-border-focus: #0078ff;
            --input-color: #0f172a;
            --input-placeholder: #94a3b8;
            --input-focus-shadow: 0 0 0 3px rgba(0, 120, 255, 0.16);
            --btn-bg: linear-gradient(135deg, #0078ff 0%, #005fd6 100%);
            --btn-hover-shadow: 0 5px 16px rgba(0, 120, 255, 0.32);
            --alert-err-bg: #fef2f2;
            --alert-err-border: #fecaca;
            --alert-err-text: #b91c1c;
            --alert-succ-bg: #ecfdf5;
            --alert-succ-border: #a7f3d0;
            --alert-succ-text: #047857;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            font-size: 13.25px;
            -webkit-text-size-adjust: 100%;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-title);
            min-height: 100vh;
            width: 100vw;
            overflow-x: hidden;
            overflow-y: auto;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Canvas Animasi Node-Node Modern (Jelas & Kontras) */
        #gm-nodes-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: auto;
            animation: gmCanvasIn 0.9s cubic-bezier(0.16, 1, 0.3, 1) backwards;
        }

        @keyframes gmCanvasIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Keyframes Animasi IN Komponen (Smooth Staggered Blur-Slide-In) */
        @keyframes gmElementIn {
            0% {
                opacity: 0;
                transform: translateY(14px);
                filter: blur(4px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
                filter: blur(0);
            }
        }

        /* Form Login Ultra-Minimalis Kompak (Tanpa Card Pembungkus) */
        .gm-login-wrap {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 250px;
            padding: 16px 10px;
            margin: auto;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }

        /* Header Brand: Hanya Tulisan Greymist (Tanpa Logo) - IN Step 1 */
        .gm-brand-header {
            margin-bottom: 14px;
            text-align: center;
            animation: gmElementIn 0.55s cubic-bezier(0.16, 1, 0.3, 1) 0.06s backwards;
            will-change: opacity, transform, filter;
        }

        .gm-brand-name {
            font-family: 'Poppins', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-title);
            letter-spacing: -0.03em;
            line-height: 1.1;
            user-select: none;
        }

        /* Flash Alerts Kompak */
        .gm-alert {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 11px;
            border-radius: 6px;
            font-size: 0.78rem;
            margin-bottom: 14px;
            backdrop-filter: blur(8px);
            animation: gmElementIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.12s backwards;
        }

        .gm-alert-error {
            background: var(--alert-err-bg);
            border: 1px solid var(--alert-err-border);
            color: var(--alert-err-text);
        }

        .gm-alert-success {
            background: var(--alert-succ-bg);
            border: 1px solid var(--alert-succ-border);
            color: var(--alert-succ-text);
        }

        @keyframes gmFadeDown {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Form Groups (Skala Kompak) - Animasi IN Bertahap */
        .gm-form-group {
            margin-bottom: 12px;
            text-align: left;
            will-change: opacity, transform, filter;
        }

        /* IN Step 2: Input Username */
        .gm-form-group:nth-of-type(1) {
            animation: gmElementIn 0.55s cubic-bezier(0.16, 1, 0.3, 1) 0.16s backwards;
        }

        /* IN Step 3: Input Password */
        .gm-form-group:nth-of-type(2) {
            animation: gmElementIn 0.55s cubic-bezier(0.16, 1, 0.3, 1) 0.26s backwards;
        }

        .gm-form-label {
            display: block;
            font-size: 0.62rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            color: var(--text-label);
            margin-bottom: 5px;
        }

        .gm-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .gm-input-icon {
            position: absolute;
            left: 11px;
            color: var(--input-placeholder);
            font-size: 12px;
            pointer-events: none;
            transition: color 0.15s ease;
        }

        /* Input Field Skala Lebih Kecil */
        .gm-input-field {
            width: 100%;
            height: 30px;
            padding: 0 34px 0 32px;
            font-family: inherit;
            font-size: 0.74rem;
            color: var(--input-color);
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 5px;
            outline: none;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .gm-input-field::placeholder {
            color: var(--input-placeholder);
            font-size: 0.78rem;
        }

        .gm-input-field:hover {
            border-color: #94a3b8;
        }

        .gm-input-field:focus {
            border-color: var(--input-border-focus);
            box-shadow: var(--input-focus-shadow);
        }

        .gm-input-wrap:focus-within .gm-input-icon {
            color: var(--input-border-focus);
        }

        /* Tombol Intip Password */
        .gm-toggle-pw {
            position: absolute;
            right: 8px;
            background: none;
            border: none;
            color: var(--input-placeholder);
            font-size: 12px;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.15s ease;
        }

        .gm-toggle-pw:hover {
            color: var(--text-title);
        }

        /* Tombol Submit Skala Lebih Kecil - IN Step 4 */
        .gm-submit-btn {
            width: 100%;
            height: 30px;
            margin-top: 8px;
            border: none;
            border-radius: 5px;
            background: var(--btn-bg);
            color: #ffffff;
            font-family: inherit;
            font-size: 0.74rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            box-shadow: var(--btn-hover-shadow);
            transition: transform 0.12s ease, box-shadow 0.15s ease, filter 0.15s ease;
            animation: gmElementIn 0.55s cubic-bezier(0.16, 1, 0.3, 1) 0.36s backwards;
            will-change: opacity, transform, filter;
        }

        .gm-submit-btn:hover {
            transform: translateY(-1px);
            filter: brightness(1.05);
            box-shadow: 0 6px 18px rgba(0, 120, 255, 0.38);
        }

        .gm-submit-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(0, 120, 255, 0.25);
        }
    
        @media (prefers-reduced-motion: reduce) {
            #gm-nodes-canvas,
            .gm-brand-header,
            .gm-alert,
            .gm-form-group,
            .gm-submit-btn {
                animation: none !important;
                opacity: 1 !important;
                transform: none !important;
                filter: none !important;
            }
        }
    </style>
</head>
<body>

<!-- Canvas Latar Belakang Animasi Node-Node Modern (Jelas & Kontras) -->
<canvas id="gm-nodes-canvas"></canvas>



<!-- Form Login Utama (Murni Minimalis Tanpa Card Pembungkus & Tanpa Logo) -->
<div class="gm-login-wrap">
    <!-- Brand Header: Hanya Tulisan Greymist -->
    <div class="gm-brand-header">
        <h1 class="gm-brand-name"><?= e($site_settings['site_name'] ?? 'Greymist') ?></h1>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="gm-alert gm-alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <span><?= e($_SESSION['flash_success']) ?></span>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="gm-alert gm-alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?= e($_SESSION['flash_error']) ?></span>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <form method="POST" action="<?= e(action_url('login')) ?>">
        <?= csrf_field() ?>

        <div class="gm-form-group">
            <label class="gm-form-label" for="username"></label>
            <div class="gm-input-wrap">
                <i class="fa-regular fa-user gm-input-icon"></i>
                <input type="text" class="gm-input-field" id="username" name="username" placeholder="username" required autofocus autocomplete="username">
            </div>
        </div>

        <div class="gm-form-group">
            <label class="gm-form-label" for="password"></label>
            <div class="gm-input-wrap">
                <i class="fa-solid fa-lock gm-input-icon"></i>
                <input type="password" class="gm-input-field" id="password" name="password" placeholder="password" required autocomplete="current-password">
                <button type="button" class="gm-toggle-pw" id="gmTogglePwBtn" title="Lihat / Sembunyikan Password">
                    <i class="fa-regular fa-eye" id="gmTogglePwIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="gm-submit-btn">
            <span>ad astra abyssosque</span>
            <i class="fa-solid fa-arrow-right" style="font-size: 11px;"></i>
        </button>
    </form>
</div>

<script>
    /* =========================================================================
       1. TOGGLE LIHAT / SEMBUNYIKAN PASSWORD
       ========================================================================= */
    const pwInput = document.getElementById('password');
    const togglePwBtn = document.getElementById('gmTogglePwBtn');
    const togglePwIcon = document.getElementById('gmTogglePwIcon');

    if (togglePwBtn && pwInput) {
        togglePwBtn.addEventListener('click', () => {
            const isPassword = pwInput.type === 'password';
            pwInput.type = isPassword ? 'text' : 'password';
            if (togglePwIcon) {
                togglePwIcon.className = isPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
            }
        });
    }

    /* =========================================================================
       2. INTERACTIVE NODE CONSTELLATION MESH ANIMATION (PERJELAS & TEGAS)
       ========================================================================= */
    (function () {
        const canvas = document.getElementById('gm-nodes-canvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        let width = 0;
        let height = 0;
        let dpr = 1;

        function resizeCanvas() {
            dpr = window.devicePixelRatio || 1;
            width = window.innerWidth;
            height = window.innerHeight;
            canvas.width = Math.floor(width * dpr);
            canvas.height = Math.floor(height * dpr);
            canvas.style.width = width + 'px';
            canvas.style.height = height + 'px';
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.scale(dpr, dpr);
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        // Kerapatan node lebih rapat & dinamis
        const nodeDensity = 11000;
        const targetNodeCount = Math.min(115, Math.max(65, Math.floor((width * height) / nodeDensity)));
        const nodes = [];

        // Interaksi kursor mouse dengan radius threshold lebih kecil & intim (80px)
        const mouse = {
            x: null,
            y: null,
            maxDist: 80
        };

        window.addEventListener('mousemove', (e) => {
            mouse.x = e.clientX;
            mouse.y = e.clientY;
        });

        window.addEventListener('mouseleave', () => {
            mouse.x = null;
            mouse.y = null;
        });

        // Efek ripple / gelombang energi saat klik
        const ripples = [];
        window.addEventListener('click', (e) => {
            ripples.push({
                x: e.clientX,
                y: e.clientY,
                radius: 4,
                maxRadius: 190,
                alpha: 0.50
            });
        });

        class NodeParticle {
            constructor() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                // Kecepatan gerak tenang, anggun & alami (0.35px - 0.65px)
                const speed = Math.random() * 0.3 + 0.35;
                const angle = Math.random() * Math.PI * 2;
                this.vx = Math.cos(angle) * speed;
                this.vy = Math.sin(angle) * speed;
                // Sudut deviasi untuk pergerakan dinamis acak halus
                this.wanderAngle = Math.random() * Math.PI * 2;
                this.wanderSpeed = Math.random() * 0.02 + 0.01;
                // Ukuran node sedikit lebih tegas (2.2px - 4.0px)
                this.radius = Math.random() * 1.8 + 2.2;
                // Transparansi lembut & tembus pandang (soft translucent)
                this.baseAlpha = Math.random() * 0.16 + 0.26;
                this.pulse = Math.random() * Math.PI * 2;
                this.pulseSpeed = Math.random() * 0.02 + 0.012;
            }

            update() {
                // Pergerakan acak dinamis: deviasi arah mengapung secara halus
                this.wanderAngle += this.wanderSpeed;
                this.vx += Math.cos(this.wanderAngle) * 0.03 + (Math.random() - 0.5) * 0.02;
                this.vy += Math.sin(this.wanderAngle) * 0.03 + (Math.random() - 0.5) * 0.02;

                // Efek Repulsion Tepat Sasaran: Menjauh saat kursor sangat dekat (threshold 80px)
                if (mouse.x !== null && mouse.y !== null) {
                    const dx = this.x - mouse.x;
                    const dy = this.y - mouse.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    const repelDist = mouse.maxDist; // 80px
                    if (dist < repelDist && dist > 1) {
                        // Tolakan responsif dan halus hanya pada jarak dekat
                        const force = Math.pow(1 - dist / repelDist, 1.3) * 3.2;
                        const nx = dx / dist;
                        const ny = dy / dist;
                        this.vx += nx * force * 0.35;
                        this.vy += ny * force * 0.35;
                    }
                }

                // Damping friksi agar tidak lepas kendali
                this.vx *= 0.94;
                this.vy *= 0.94;

                // Batasi laju kecepatan alami agar tenang dan anggun (0.35 - 1.1 px/frame)
                const currentSpeed = Math.hypot(this.vx, this.vy);
                if (currentSpeed < 0.35) {
                    const boost = 0.35 / (currentSpeed || 1);
                    this.vx *= boost;
                    this.vy *= boost;
                } else if (currentSpeed > 1.1) {
                    const slow = 1.1 / currentSpeed;
                    this.vx *= slow;
                    this.vy *= slow;
                }

                this.x += this.vx;
                this.y += this.vy;

                // Pantulan dinding lembut
                if (this.x < 0) { this.x = 0; this.vx *= -1; }
                else if (this.x > width) { this.x = width; this.vx *= -1; }

                if (this.y < 0) { this.y = 0; this.vy *= -1; }
                else if (this.y > height) { this.y = height; this.vy *= -1; }

                this.pulse += this.pulseSpeed;
            }

            draw() {
                const pulseScale = 0.92 + Math.sin(this.pulse) * 0.18;
                const r = this.radius * pulseScale;
                const alpha = Math.min(1, this.baseAlpha * pulseScale);

                // 1. Lapisan Aura Berpendar Lembut Transparan (Soft Blue Halo)
                const glowRadius = r * 2.6;
                const glowGrad = ctx.createRadialGradient(this.x, this.y, 0, this.x, this.y, glowRadius);
                glowGrad.addColorStop(0, `rgba(147, 197, 253, ${alpha * 0.35})`);
                glowGrad.addColorStop(0.5, `rgba(191, 219, 254, ${alpha * 0.12})`);
                glowGrad.addColorStop(1, 'rgba(219, 234, 254, 0)');

                ctx.beginPath();
                ctx.arc(this.x, this.y, glowRadius, 0, Math.PI * 2);
                ctx.fillStyle = glowGrad;
                ctx.fill();

                // 2. Inti Node Transparan: Soft Blue (#3b82f6)
                ctx.beginPath();
                ctx.arc(this.x, this.y, r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(59, 130, 246, ${alpha})`;
                ctx.fill();
            }
        }

        // Inisialisasi node-node
        for (let i = 0; i < targetNodeCount; i++) {
            nodes.push(new NodeParticle());
        }

        const maxLineDistance = 145;

        function renderFrame() {
            ctx.clearRect(0, 0, width, height);

            // 1. Garis jaring penghubung antar node yang berdekatan (Biru Gelap Bernada Tegas)
            const len = nodes.length;
            for (let i = 0; i < len; i++) {
                const a = nodes[i];
                for (let j = i + 1; j < len; j++) {
                    const b = nodes[j];
                    const dx = a.x - b.x;
                    const dy = a.y - b.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);

                    if (dist < maxLineDistance) {
                        const factor = 1 - (dist / maxLineDistance);
                        const lineAlpha = factor * 0.20;

                        ctx.beginPath();
                        ctx.moveTo(a.x, a.y);
                        ctx.lineTo(b.x, b.y);
                        ctx.strokeStyle = `rgba(96, 165, 250, ${lineAlpha})`;
                        ctx.lineWidth = 1.0;
                        ctx.stroke();
                    }
                }

                // 2. Garis interaksi kursor mouse saat mendekat (Soft Blue)
                if (mouse.x !== null && mouse.y !== null) {
                    const dx = a.x - mouse.x;
                    const dy = a.y - mouse.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < mouse.maxDist) {
                        const factor = 1 - (dist / mouse.maxDist);
                        const mouseLineAlpha = factor * 0.30;

                        ctx.beginPath();
                        ctx.moveTo(a.x, a.y);
                        ctx.lineTo(mouse.x, mouse.y);
                        ctx.strokeStyle = `rgba(59, 130, 246, ${mouseLineAlpha})`;
                        ctx.lineWidth = 1.0;
                        ctx.stroke();
                    }
                }

                // Update & Render Node
                a.update();
                a.draw();
            }

            // 3. Efek gelombang riak klik (Soft Blue Ripple)
            for (let i = ripples.length - 1; i >= 0; i--) {
                const rip = ripples[i];
                rip.radius += 5.2;
                rip.alpha *= 0.93;

                ctx.beginPath();
                ctx.arc(rip.x, rip.y, rip.radius, 0, Math.PI * 2);
                ctx.strokeStyle = `rgba(96, 165, 250, ${rip.alpha * 0.75})`;
                ctx.lineWidth = 1.8;
                ctx.stroke();

                if (rip.alpha < 0.02 || rip.radius > rip.maxRadius) {
                    ripples.splice(i, 1);
                }
            }

            requestAnimationFrame(renderFrame);
        }

        renderFrame();
    })();
</script>
</body>
</html>