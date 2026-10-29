// assets/js/app.js
(function () {
    'use strict';

    const sidebar      = document.getElementById('sidebar');
    const overlay      = document.getElementById('overlay');
    const menuToggle   = document.getElementById('menuToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const refreshBtn   = document.getElementById('refreshBtn');
    const statusBadge  = document.getElementById('statusBadge');
    const DESKTOP_MIN  = 992;

    // ----- Sidebar (mobile / tablet) -----
    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('show');
        menuToggle.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        menuToggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    menuToggle?.addEventListener('click', () =>
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar()
    );
    sidebarClose?.addEventListener('click', closeSidebar);
    overlay?.addEventListener('click', closeSidebar);

    // Tutup dengan tombol Esc
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeSidebar();
    });

    // Tutup otomatis saat layar dibesarkan ke desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth >= DESKTOP_MIN) closeSidebar();
    });

    // Tutup setelah klik menu (mobile)
    sidebar?.querySelectorAll('.nav-link').forEach((link) =>
        link.addEventListener('click', () => {
            if (window.innerWidth < DESKTOP_MIN) closeSidebar();
        })
    );

    // ----- Tombol refresh -----
    refreshBtn?.addEventListener('click', () => {
        refreshBtn.classList.add('spin');
        setTimeout(() => window.location.reload(), 300);
    });

    // ----- Status online / offline -----
    function updateStatus() {
        if (!statusBadge) return;
        const online = navigator.onLine;
        statusBadge.classList.toggle('offline', !online);
        statusBadge.querySelector('span:last-child').textContent = online ? 'Online' : 'Offline';
    }
    window.addEventListener('online', updateStatus);
    window.addEventListener('offline', updateStatus);
    updateStatus();
})();

(function () {
    // --- Isi cepat durasi kontrak ---
    var mulai = document.getElementById('kontrak_mulai');
    var selesai = document.getElementById('kontrak_selesai');
    function fmt(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    document.querySelectorAll('[data-bulan]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var awal = mulai.value ? new Date(mulai.value + 'T00:00:00') : new Date();
            if (!mulai.value) mulai.value = fmt(awal);
            var akhir = new Date(awal.getFullYear(), awal.getMonth() + parseInt(btn.dataset.bulan, 10), awal.getDate());
            selesai.value = fmt(akhir);
        });
    });

    // --- Kolom RFID: reader USB mengetik UID lalu menekan Enter ---
    var rfid = document.getElementById('rfid_uid');
    var wrap = document.getElementById('rfid-wrap');
    var state = document.getElementById('rfid-state');
    function tampil() {
        wrap.classList.toggle('filled', rfid.value !== '');
        if (rfid.value !== '' && document.activeElement !== rfid) state.textContent = 'Kartu terbaca.';
    }
    rfid.addEventListener('focus', function () {
        wrap.classList.add('ready');
        state.textContent = 'Siap membaca. Tempelkan kartu pada reader.';
    });
    rfid.addEventListener('blur', function () {
        wrap.classList.remove('ready');
        state.textContent = rfid.value ? 'Kartu terbaca.' : 'Klik kolom untuk mulai membaca kartu.';
        tampil();
    });
    rfid.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {          // Enter dari reader jangan mengirim form
            e.preventDefault();
            rfid.value = rfid.value.trim().toUpperCase();
            state.textContent = 'Kartu terbaca: ' + rfid.value;
            tampil();
        }
    });
    rfid.addEventListener('input', tampil);
    tampil();
})();
