document.addEventListener('DOMContentLoaded', () => {
    const animGrid = document.getElementById('gm-anim-grid');
    const animBoxes = document.querySelectorAll('.gm-anim-box');
    const animDuration = document.getElementById('gm-anim-duration');
    const animDelay = document.getElementById('gm-anim-delay');
    const animLoop = document.getElementById('gm-anim-loop');
    const playBtn = document.getElementById('gm-anim-play-btn');

    if (!animGrid) return; // safeguard

    // 1. Efek Hover Thumbnail (Memainkan animasi secara loop saat disorot)
    animBoxes.forEach(box => {
        box.addEventListener('mouseenter', () => {
            const anim = box.dataset.anim;
            if (!anim) return;
            const inner = box.querySelector('.gm-anim-inner');
            if(inner) inner.classList.add(anim, 'gm-anim-loop-hover');
        });
        
        box.addEventListener('mouseleave', () => {
            const anim = box.dataset.anim;
            if (!anim) return;
            // Hapus animasi dan panggil reflow untuk mereset posisinya
            const inner = box.querySelector('.gm-anim-inner');
            if(inner) {
                inner.classList.remove(anim, 'gm-anim-loop-hover');
                void inner.offsetWidth; 
            }
        });

        // Event Klik pada thumbnail
        box.addEventListener('click', () => {
            // Ubah class active di UI thumbnail
            animBoxes.forEach(b => b.classList.remove('active'));
            box.classList.add('active');

            // Terapkan ke object aktif
            applyAnimationToObject();
        });
    });

    // 2. Fungsi membaca animasi dari objek aktif ke UI
    function readAnimationFromObject() {
        // Cegah overwrite jika user sedang mengetik di dalam input
        if (document.activeElement === animDuration || 
            document.activeElement === animDelay || 
            document.activeElement === animLoop) {
            return;
        }
        
        const activeObj = typeof getAllActiveObjects === 'function' ? getAllActiveObjects()[0] : document.querySelector('.gm-object.gm-active');
        
        if (!activeObj) {
            // Reset UI jika tidak ada yang dipilih (KOSONGKAN INPUT)
            animBoxes.forEach(b => b.classList.remove('active'));
            const noneBox = document.querySelector('.gm-anim-box[data-anim=""]');
            if(noneBox) noneBox.classList.add('active');
            
            animDuration.value = "";
            animDelay.value = "";
            animLoop.value = "";
            return;
        }

        const animClass = activeObj.dataset.gmAnim || "";
        // Jika object punya animasi, baca. Jika tidak, kosongkan agar tidak membingungkan
        const dur = activeObj.dataset.gmAnimDur || "";
        const del = activeObj.dataset.gmAnimDel || "";
        const loop = activeObj.dataset.gmAnimLoop || "";

        // Update active box
        animBoxes.forEach(b => b.classList.remove('active'));
        const targetBox = document.querySelector(`.gm-anim-box[data-anim="${animClass}"]`);
        if (targetBox) targetBox.classList.add('active');
        else {
            const noneBox = document.querySelector('.gm-anim-box[data-anim=""]');
            if(noneBox) noneBox.classList.add('active');
        }

        // Update setting inputs
        animDuration.value = dur;
        animDelay.value = del;
        animLoop.value = loop;
    }

    // 3. Fungsi menerapkan animasi ke objek aktif
    function applyAnimationToObject() {
        const activeObj = typeof getAllActiveObjects === 'function' ? getAllActiveObjects()[0] : document.querySelector('.gm-object.gm-active');
        if (!activeObj) return;

        // Ambil nilai dari box yang active dan input
        const activeBox = document.querySelector('.gm-anim-box.active');
        const val = activeBox ? activeBox.dataset.anim : "";
        const dur = animDuration.value || "1";
        const del = animDelay.value || "0";
        const loop = animLoop.value || "1";

        // Bersihkan kelas animasi lama di object
        if (activeObj.dataset.gmAnim) {
            activeObj.classList.remove('gm-anim-base', activeObj.dataset.gmAnim);
        }

        if (val) {
            // Set dataset
            activeObj.dataset.gmAnim = val;
            activeObj.dataset.gmAnimDur = dur;
            activeObj.dataset.gmAnimDel = del;
            activeObj.dataset.gmAnimLoop = loop;

            // Set style CSS variable
            activeObj.style.setProperty('--gm-anim-duration', dur + 's');
            activeObj.style.setProperty('--gm-anim-delay', del + 's');
            activeObj.style.animationIterationCount = loop;

            // Tambahkan class
            activeObj.classList.add('gm-anim-base', val);
        } else {
            // Jika memilih "Tidak ada"
            delete activeObj.dataset.gmAnim;
            delete activeObj.dataset.gmAnimDur;
            delete activeObj.dataset.gmAnimDel;
            delete activeObj.dataset.gmAnimLoop;
            activeObj.style.removeProperty('--gm-anim-duration');
            activeObj.style.removeProperty('--gm-anim-delay');
            activeObj.style.animationIterationCount = '';
        }
        
        // Beri tahu Live Preview window untuk tidak membekukan animasi kali ini
        if (typeof BroadcastChannel !== 'undefined') {
            new BroadcastChannel('greymist-live-preview').postMessage({ type: 'force-play-anim' });
        }
    }

    // 4. Fungsi memutar ulang animasi untuk preview di editor
    function playPreview() {
        const activeObj = typeof getAllActiveObjects === 'function' ? getAllActiveObjects()[0] : document.querySelector('.gm-object.gm-active');
        if (!activeObj) return;

        const val = activeObj.dataset.gmAnim;
        if (!val) return;

        // Memaksa reflow agar animasi jalan ulang
        activeObj.classList.remove(val);
        void activeObj.offsetWidth; 
        activeObj.classList.add(val);

        // Beri tahu Live Preview window untuk tidak membekukan animasi kali ini
        if (typeof BroadcastChannel !== 'undefined') {
            new BroadcastChannel('greymist-live-preview').postMessage({ type: 'force-play-anim' });
        }
    }

    // Event listener change (agar setting diterapkan saat user selesai mengetik/tekan Enter)
    animDuration.addEventListener('change', applyAnimationToObject);
    animDelay.addEventListener('change', applyAnimationToObject);
    animLoop.addEventListener('change', applyAnimationToObject);
    playBtn.addEventListener('click', playPreview);

    // Mencegah mousedown pada UI Animasi menghilangkan focus dari activeObject
    document.querySelector('.gm-anim-panel')?.addEventListener('mousedown', (e) => {
        // Jangan stop propagation untuk elemen input agar tetap bisa diklik
        if(e.target.tagName !== 'INPUT' && e.target.tagName !== 'SELECT') {
            e.preventDefault();
        }
    });

    // ==========================================
    // ROBUST SYNC: PANTAU PERUBAHAN ACTIVE OBJECT
    // ==========================================
    
    // 1. Lewat mousedown di canvas (agar respon lebih instan dari click)
    document.addEventListener("mousedown", e => {
        if (e.target.closest(".gm-object") || !e.target.closest(".gm-anim-panel")) {
            setTimeout(readAnimationFromObject, 50);
        }
    });

    // 2. Lewat MutationObserver (jika class gm-active pindah lewat script/shortcut)
    const observer = new MutationObserver(() => {
        setTimeout(readAnimationFromObject, 50);
    });
    observer.observe(document.body, { 
        attributes: true, 
        subtree: true, 
        attributeFilter: ["class"] 
    });

    // 3. Custom Event jika ada
    document.addEventListener("objectSelected", () => {
        setTimeout(readAnimationFromObject, 50);
    });
    
    // Inisialisasi awal
    setTimeout(readAnimationFromObject, 50);
});
