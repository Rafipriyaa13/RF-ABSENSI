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

// ----- Modal (buka/tutup) -----
(function () {
    'use strict';

    let activeModal = null;
    let lastFocus = null;

    function openModal(modal) {
        lastFocus = document.activeElement;
        modal.hidden = false;
        activeModal = modal;
        document.body.style.overflow = 'hidden';
        modal.querySelector('input:not([readonly]), select, textarea')?.focus();
    }

    function closeModal() {
        if (!activeModal) return;
        activeModal.hidden = true;
        activeModal = null;
        document.body.style.overflow = '';
        lastFocus?.focus();
    }

    // Tombol pembuka: <button data-modal-open="idModal">
    document.querySelectorAll('[data-modal-open]').forEach((btn) =>
        btn.addEventListener('click', () => {
            const modal = document.getElementById(btn.dataset.modalOpen);
            if (modal) openModal(modal);
        })
    );

    // Tombol penutup (X, Batal, area gelap): [data-modal-close]
    document.querySelectorAll('[data-modal-close]').forEach((el) =>
        el.addEventListener('click', closeModal)
    );

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
})();