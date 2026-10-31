/**
 * File: assets/JS/program_function/chart-engine.js
 * Fungsi: Universal Greymist Chart Engine.
 *         Menginisialisasi, merender, dan menyediakan dummy data otomatis untuk
 *         Chart.js (bar, doughnut, line, dll) di seluruh workspace editor.
 */

(function (window) {
    'use strict';

    // CDN Presets
    const CDN_CHARTJS = 'https://cdn.jsdelivr.net/npm/chart.js';

    /**
     * Memuat skrip CDN secara dinamis jika belum tersedia
     */
    window.loadGmCdnScript = function (url) {
        return new Promise((resolve, reject) => {
            if (!url) return resolve();
            if (window.Chart && url.includes('chart.js')) return resolve();

            const existing = document.querySelector(`script[src="${url}"]`);
            if (existing) {
                if (window.Chart) return resolve();
                existing.addEventListener('load', () => resolve());
                existing.addEventListener('error', (e) => reject(e));
                return;
            }

            const s = document.createElement('script');
            s.src = url;
            s.async = true;
            s.onload = () => resolve();
            s.onerror = (e) => reject(e);
            document.head.appendChild(s);
        });
    };

    /**
     * Menyediakan preset konfigurasi data dummy yang profesional dan menarik
     */
    function getDefaultDummyConfig(type, cardText) {
        const lower = (cardText || '').toLowerCase();

        // Deteksi jenis berdasarkan teks konteks jika tipe umum
        let finalType = type || 'bar';
        if (!type || type === 'auto') {
            if (lower.includes('kategori') || lower.includes('layanan') || lower.includes('distribusi') || lower.includes('pie') || lower.includes('doughnut') || lower.includes('lingkaran') || lower.includes('sumber')) {
                finalType = 'doughnut';
            } else if (lower.includes('tren') || lower.includes('garis') || lower.includes('line') || lower.includes('pertumbuhan')) {
                finalType = 'line';
            } else {
                finalType = 'bar';
            }
        }

        if (finalType === 'doughnut' || finalType === 'pie') {
            return {
                library: 'chartjs',
                cdn: CDN_CHARTJS,
                type: 'doughnut',
                data: {
                    labels: ['Layanan Publik', 'Kesehatan', 'Pendidikan', 'Perizinan'],
                    datasets: [{
                        label: 'Distribusi Penggunaan',
                        data: [40, 25, 20, 15],
                        backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 14,
                                font: { size: 11, family: 'Inter, sans-serif' },
                                color: '#475569'
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { size: 12, weight: 'bold' },
                            bodyFont: { size: 11 },
                            padding: 8,
                            cornerRadius: 6
                        }
                    },
                    cutout: '65%'
                }
            };
        }

        if (finalType === 'line') {
            return {
                library: 'chartjs',
                cdn: CDN_CHARTJS,
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
                    datasets: [{
                        label: 'Pertumbuhan',
                        data: [35, 48, 62, 78, 70, 92],
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.12)',
                        borderWidth: 2.5,
                        tension: 0.35,
                        fill: true,
                        pointBackgroundColor: '#2563eb',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#64748b' } },
                        y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 11 }, color: '#64748b' }, beginAtZero: true }
                    }
                }
            };
        }

        // Default: Bar Chart (Batang)
        return {
            library: 'chartjs',
            cdn: CDN_CHARTJS,
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
                datasets: [{
                    label: 'Pendapatan (Juta Rp)',
                    data: [45, 58, 70, 82, 78, 95],
                    backgroundColor: '#2563eb',
                    borderRadius: 6,
                    borderSkipped: false,
                    hoverBackgroundColor: '#1d4ed8'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 11 },
                        padding: 8,
                        cornerRadius: 6
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, family: 'Inter, sans-serif' }, color: '#64748b' }
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { size: 11, family: 'Inter, sans-serif' }, color: '#64748b' },
                        beginAtZero: true
                    }
                }
            }
        };
    }

    /**
     * Fallback Canvas 2D manual jika Chart.js gagal dimuat
     */
    function renderCanvas2DFallback(canvas, config) {
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        const w = canvas.width = canvas.parentElement ? canvas.parentElement.clientWidth || 360 : 360;
        const h = canvas.height = canvas.parentElement ? canvas.parentElement.clientHeight || 240 : 240;

        ctx.clearRect(0, 0, w, h);

        const type = (config.type || 'bar').toLowerCase();

        if (type === 'doughnut' || type === 'pie') {
            const centerX = w / 2;
            const centerY = h / 2 - 14;
            const radius = Math.min(centerX, centerY) - 18;
            const innerRadius = type === 'doughnut' ? radius * 0.62 : 0;
            const slices = (config.data && config.data.datasets && config.data.datasets[0] && config.data.datasets[0].data) || [40, 25, 20, 15];
            const colors = (config.data && config.data.datasets && config.data.datasets[0] && config.data.datasets[0].backgroundColor) || ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6'];
            const total = slices.reduce((a, b) => a + b, 0);

            let startAngle = -Math.PI / 2;
            for (let i = 0; i < slices.length; i++) {
                const sliceAngle = (slices[i] / total) * 2 * Math.PI;
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
                ctx.arc(centerX, centerY, innerRadius, startAngle + sliceAngle, startAngle, true);
                ctx.closePath();
                ctx.fillStyle = colors[i % colors.length];
                ctx.fill();
                startAngle += sliceAngle;
            }

            // Legend mini
            const labels = (config.data && config.data.labels) || ['Layanan 1', 'Layanan 2', 'Layanan 3', 'Layanan 4'];
            ctx.font = '10px Inter, sans-serif';
            ctx.textAlign = 'left';
            let lx = 14;
            const ly = h - 14;
            for (let i = 0; i < labels.length && i < 4; i++) {
                ctx.fillStyle = colors[i % colors.length];
                ctx.fillRect(lx, ly - 8, 8, 8);
                ctx.fillStyle = '#64748b';
                ctx.fillText(labels[i], lx + 12, ly);
                lx += ctx.measureText(labels[i]).width + 24;
            }
        } else {
            // Bar chart fallback
            const labels = (config.data && config.data.labels) || ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'];
            const values = (config.data && config.data.datasets && config.data.datasets[0] && config.data.datasets[0].data) || [45, 58, 70, 82, 78, 95];
            const maxVal = Math.max(...values, 100);

            const padLeft = 40;
            const padBottom = 30;
            const padTop = 20;
            const padRight = 20;
            const chartW = w - padLeft - padRight;
            const chartH = h - padTop - padBottom;
            const barW = Math.max(16, (chartW / values.length) * 0.55);
            const gap = chartW / values.length;

            // Baseline
            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(padLeft, h - padBottom);
            ctx.lineTo(w - padRight, h - padBottom);
            ctx.stroke();

            // Bars
            ctx.font = '11px Inter, sans-serif';
            ctx.textAlign = 'center';
            for (let i = 0; i < values.length; i++) {
                const bH = (values[i] / maxVal) * chartH;
                const bx = padLeft + i * gap + (gap - barW) / 2;
                const by = h - padBottom - bH;

                ctx.fillStyle = '#2563eb';
                ctx.beginPath();
                ctx.roundRect ? ctx.roundRect(bx, by, barW, bH, [4, 4, 0, 0]) : ctx.rect(bx, by, barW, bH);
                ctx.fill();

                // Month label
                ctx.fillStyle = '#64748b';
                ctx.fillText(labels[i] || '', bx + barW / 2, h - padBottom + 16);
            }
        }
    }

    /**
     * Inisialisasi universal untuk semua chart di dalam container atau workspace
     */
    window.initGmCharts = async function (container) {
        const root = container || document.getElementById('gm-frame-preview') || document.body;
        if (!root) return;

        // Pastikan Chart.js sudah siap di memori
        if (!window.Chart) {
            try {
                await window.loadGmCdnScript(CDN_CHARTJS);
            } catch (_) {}
        }

        // Kumpulkan semua elemen canvas atau kartu chart
        const targets = new Set();

        // 1. Kartu chart standar
        root.querySelectorAll('.gm-chart-card, [data-chart-type], [data-chart-lib]').forEach(card => targets.add(card));

        // 2. Canvas dengan class chart
        root.querySelectorAll('canvas.gm-chart-canvas, canvas[data-chart], .gm-chart-canvas-wrap canvas').forEach(canvas => {
            const card = canvas.closest('.gm-chart-card, [data-chart-type]') || canvas.parentElement;
            if (card) targets.add(card);
            else targets.add(canvas);
        });

        // 3. Script data konfigurasi chart
        root.querySelectorAll('script[data-gm-chart-config]').forEach(sc => {
            const card = sc.closest('.gm-chart-card') || sc.parentElement;
            if (card) targets.add(card);
        });

        for (const el of targets) {
            try {
                const isCanvas = el.tagName && el.tagName.toLowerCase() === 'canvas';
                const card = isCanvas ? el.closest('.gm-chart-card') || el.parentElement : el;
                let canvas = isCanvas ? el : (card.querySelector('.gm-chart-canvas') || card.querySelector('canvas'));

                // Jika tidak ada canvas di dalam wrap, buat otomatis
                if (!canvas) {
                    let wrap = card.querySelector('.gm-chart-canvas-wrap') || card.querySelector('.gm-chart-container');
                    if (!wrap) {
                        wrap = document.createElement('div');
                        wrap.className = 'gm-object gm-chart-canvas-wrap';
                        wrap.style.cssText = 'position: relative; width: 100%; height: 260px; min-height: 220px;';
                        card.appendChild(wrap);
                    }
                    canvas = document.createElement('canvas');
                    canvas.className = 'gm-object gm-chart-canvas';
                    canvas.style.cssText = 'display: block; width: 100% !important; height: 100% !important;';
                    wrap.appendChild(canvas);
                }

                // Perbaiki layout wrapper agar responsif
                const wrap = canvas.parentElement;
                if (wrap) {
                    if (!wrap.style.position) wrap.style.position = 'relative';
                    if (!wrap.style.width) wrap.style.width = '100%';
                    const curHeight = parseInt(window.getComputedStyle(wrap).height, 10);
                    if (!curHeight || curHeight < 100) {
                        wrap.style.height = '260px';
                        wrap.style.minHeight = '220px';
                    }
                }
                canvas.style.display = 'block';
                canvas.style.width = '100%';
                canvas.style.height = '100%';

                // Ambil atau buat konfigurasi chart
                let configScript = card.querySelector('script[data-gm-chart-config]');
                let config = null;

                if (configScript && configScript.textContent.trim()) {
                    try {
                        config = JSON.parse(configScript.textContent.trim());
                    } catch (_) {
                        config = null;
                    }
                }

                // Jika belum ada konfigurasi atau data kosong, isi dengan preset dummy data cerdas
                const cardText = card.textContent || '';
                const detectedType = card.dataset.chartType || (config && config.type) || null;

                if (!config || !config.data || !Array.isArray(config.data.datasets) || config.data.datasets.length === 0 || !config.data.datasets[0].data || config.data.datasets[0].data.length === 0) {
                    config = getDefaultDummyConfig(detectedType, cardText);
                    if (!configScript) {
                        configScript = document.createElement('script');
                        configScript.setAttribute('data-gm-chart-config', 'true');
                        card.appendChild(configScript);
                    }
                    configScript.textContent = JSON.stringify(config);
                }

                if (config.library === 'apexcharts') {
                    await window.loadGmCdnScript(config.cdn || 'https://cdn.jsdelivr.net/npm/apexcharts');
                    const apexCont = card.querySelector('.gm-apex-chart-container') || card.querySelector('.gm-chart-canvas-wrap') || wrap;
                    if (apexCont && window.ApexCharts) {
                        if (canvas) canvas.style.display = 'none';
                        apexCont.innerHTML = '';
                        const chart = new window.ApexCharts(apexCont, config.options || {});
                        chart.render();
                    }
                } else if (config.library === 'echarts') {
                    await window.loadGmCdnScript(config.cdn || 'https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js');
                    const echCont = card.querySelector('.gm-chart-canvas-wrap') || wrap;
                    if (echCont && window.echarts) {
                        const echChart = window.echarts.init(echCont);
                        echChart.setOption(config.options || {});
                    }
                } else if (window.Chart) {
                    if (canvas._gmChart) {
                        try { canvas._gmChart.destroy(); } catch (_) {}
                    }

                    // Pastikan maintainAspectRatio false agar mengisi kotak pembungkus
                    if (!config.options) config.options = {};
                    config.options.responsive = true;
                    config.options.maintainAspectRatio = false;

                    canvas._gmChart = new window.Chart(canvas, {
                        type: config.type || detectedType || 'bar',
                        data: config.data,
                        options: config.options
                    });
                } else {
                    // Fallback Canvas 2D
                    renderCanvas2DFallback(canvas, config);
                }

            } catch (err) {
                console.warn('[Greymist Chart Engine] Gagal merender elemen chart:', err);
            }
        }
    };

    // Observer otomatis untuk memantau penambahan elemen chart baru oleh AI atau Drag & Drop
    let chartInitDebounce = null;
    function scheduleChartInit() {
        if (chartInitDebounce) cancelAnimationFrame(chartInitDebounce);
        chartInitDebounce = requestAnimationFrame(() => {
            window.initGmCharts();
        });
    }

    // Inisialisasi awal saat dokumen siap
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleChartInit);
    } else {
        setTimeout(scheduleChartInit, 100);
    }
    window.addEventListener('load', scheduleChartInit);

    // Event listener Greymist
    document.addEventListener('gm-dom-tab-activated', scheduleChartInit);
    document.addEventListener('gm-frame-switched', scheduleChartInit);
    document.addEventListener('gm-ai-dom-updated', scheduleChartInit);

    // MutationObserver untuk memantau perubahan DOM di workspace
    const observer = new MutationObserver((mutations) => {
        let shouldInit = false;
        for (const mut of mutations) {
            for (const node of mut.addedNodes) {
                if (node.nodeType === 1) {
                    if (node.classList?.contains('gm-chart-card') ||
                        node.classList?.contains('gm-chart-canvas') ||
                        node.querySelector?.('.gm-chart-card, .gm-chart-canvas, script[data-gm-chart-config]')) {
                        shouldInit = true;
                        break;
                    }
                }
            }
            if (shouldInit) break;
        }
        if (shouldInit) {
            scheduleChartInit();
        }
    });

    // Pasang observer pada body atau preview
    function attachObserver() {
        const target = document.getElementById('gm-frame-preview') || document.body;
        if (target) {
            observer.observe(target, { childList: true, subtree: true });
        } else {
            setTimeout(attachObserver, 200);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachObserver);
    } else {
        attachObserver();
    }

})(window);
