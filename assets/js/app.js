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
        if (!sidebar || !overlay || !menuToggle) return;   // halaman tanpa sidebar (login, register)
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
    if (!rfid || !wrap || !state) return;   // bukan halaman form pegawai
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

// ===== Halaman Izin / Cuti (manager/izin_cuti/index.php) =====
(function () {
    'use strict';

    const panel = document.getElementById('izin-panel');
    if (!panel) return;                      // bukan halaman ini

    // Data dikirim dari PHP lewat atribut data-izin (JSON)
    const data = JSON.parse(panel.dataset.izin || '[]');

    const rows  = document.getElementById('izin-rows');
    const stats = document.getElementById('izin-stats');
    const tabs  = document.getElementById('izin-tabs');
    const dlg   = document.getElementById('izin-viewer');

    const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    let filter = 'semua', kata = '', aktif = null;

    // ----- Helper -----
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const cap = (s) => s.charAt(0).toUpperCase() + s.slice(1);
    const tgl = (s) => { const [y, m, d] = s.split('-'); return `${+d} ${BULAN[m - 1]} ${y}`; };
    const durasi  = (r) => Math.round((new Date(r.selesai) - new Date(r.mulai)) / 864e5) + 1;
    const periode = (r) => durasi(r) > 1 ? `${tgl(r.mulai)} - ${tgl(r.selesai)}` : tgl(r.mulai);

    // Gambar surat contoh (placeholder) sampai upload dari pegawai tersedia
    function suratContoh(r) {
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="595" height="760" viewBox="0 0 595 760">
            <rect width="595" height="760" fill="#fff"/>
            <text x="50" y="70" font-family="Arial" font-size="20" font-weight="bold">SURAT PERMOHONAN ${esc(r.jenis.toUpperCase())}</text>
            <line x1="50" y1="85" x2="545" y2="85" stroke="#111" stroke-width="2"/>
            <text x="50" y="130" font-family="Arial" font-size="14">Yang bertanda tangan di bawah ini:</text>
            <text x="50" y="165" font-family="Arial" font-size="14">Nama      : ${esc(r.nama)}</text>
            <text x="50" y="190" font-family="Arial" font-size="14">Jabatan  : ${esc(r.jabatan)}</text>
            <text x="50" y="235" font-family="Arial" font-size="14">Mengajukan ${esc(r.jenis.toLowerCase())} pada ${periode(r)}</text>
            <text x="50" y="260" font-family="Arial" font-size="14">dengan alasan:</text>
            <text x="50" y="285" font-family="Arial" font-size="14" fill="#374151">${esc(r.alasan.slice(0, 60))}</text>
            <text x="50" y="680" font-family="Arial" font-size="14">Hormat saya,</text>
            <text x="50" y="740" font-family="Arial" font-size="14">${esc(r.nama)}</text>
            <text x="297" y="400" font-family="Arial" font-size="12" fill="#9ca3af" text-anchor="middle">(contoh dokumen, ganti dengan foto/scan asli)</text>
        </svg>`;
        return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
    }

    function toast(msg) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.classList.add('show');
        clearTimeout(toast.timer);
        toast.timer = setTimeout(() => t.classList.remove('show'), 2400);
    }

    // ----- Keputusan setuju / tolak -----
    function putuskan(id, status) {
        const r = data.find((x) => x.id === id);
        if (!r || r.status !== 'menunggu') return false;
        if (!confirm(`${status === 'disetujui' ? 'Setujui' : 'Tolak'} pengajuan ${r.nama}?`)) return false;
        r.status = status;
        render();
        toast(`Pengajuan ${r.nama} ${status}.`);
        return true;
    }

    // ----- Tampilan -----
    function render() {
        const n = (s) => data.filter((r) => r.status === s).length;

        stats.innerHTML = [['Menunggu', n('menunggu')], ['Disetujui', n('disetujui')], ['Ditolak', n('ditolak')]]
            .map(([l, v]) => `<article class="card stat"><span class="stat-label">${l}</span><p class="stat-value">${v} <small>Pengajuan</small></p></article>`)
            .join('');

        tabs.querySelectorAll('button').forEach((b) => {
            const s = b.dataset.status;
            b.innerHTML = `${cap(s)} <span class="count">${s === 'semua' ? data.length : n(s)}</span>`;
            b.classList.toggle('active', s === filter);
        });

        const kw = kata.toLowerCase();
        const list = data
            .filter((r) => (filter === 'semua' || r.status === filter)
                && (r.nama.toLowerCase().includes(kw) || r.kode.toLowerCase().includes(kw)))
            .sort((a, b) => (b.status === 'menunggu') - (a.status === 'menunggu') || b.mulai.localeCompare(a.mulai));

        rows.innerHTML = list.map((r) => `
            <tr>
                <td data-label="Pegawai"><span class="strong">${esc(r.nama)}</span><br><small class="muted">${esc(r.kode)} &middot; ${esc(r.jabatan)}</small></td>
                <td data-label="Jenis">${esc(r.jenis)}</td>
                <td data-label="Periode">${periode(r)}</td>
                <td data-label="Durasi">${durasi(r)} hari</td>
                <td data-label="Dokumen">${r.surat ? `<button type="button" class="btn-doc" data-id="${r.id}">Lihat Surat</button>` : '<span class="muted">Belum ada</span>'}</td>
                <td data-label="Status"><span class="st st-${esc(r.status)}">${cap(r.status)}</span></td>
                <td data-label="Aksi">${r.status === 'menunggu'
                    ? `<div class="aksi"><button type="button" class="btn-aksi setuju" data-act="disetujui" data-id="${r.id}">Setujui</button><button type="button" class="btn-aksi tolak" data-act="ditolak" data-id="${r.id}">Tolak</button></div>`
                    : '<span class="muted">-</span>'}</td>
            </tr>`).join('') || '<tr><td colspan="7" class="empty">Tidak ada pengajuan yang sesuai.</td></tr>';
    }

    // ----- Pop-up dokumen -----
    function bukaSurat(id) {
        aktif = data.find((x) => x.id === id);
        document.getElementById('vNama').textContent   = aktif.nama;
        document.getElementById('vInfo').textContent   = `${aktif.jenis} \u00b7 ${periode(aktif)}`;
        document.getElementById('vAlasan').textContent = 'Alasan: ' + aktif.alasan;
        document.getElementById('vBody').innerHTML     = `<img src="${suratContoh(aktif)}" alt="Surat ${esc(aktif.nama)}">`;

        const menunggu = aktif.status === 'menunggu';
        document.getElementById('vSetuju').style.display = menunggu ? '' : 'none';
        document.getElementById('vTolak').style.display  = menunggu ? '' : 'none';
        document.getElementById('vStatus').textContent   = menunggu ? '' : 'Status: ' + cap(aktif.status);
        dlg.showModal();
    }

    // ----- Event -----
    tabs.addEventListener('click', (e) => {
        const b = e.target.closest('button');
        if (!b) return;
        filter = b.dataset.status;
        render();
    });

    document.getElementById('izin-q').addEventListener('input', (e) => {
        kata = e.target.value.trim();
        render();
    });

    rows.addEventListener('click', (e) => {
        const b = e.target.closest('button');
        if (!b) return;
        const id = +b.dataset.id;
        if (b.classList.contains('btn-doc')) bukaSurat(id);
        else if (b.dataset.act) putuskan(id, b.dataset.act);
    });

    document.getElementById('vSetuju').addEventListener('click', () => { if (putuskan(aktif.id, 'disetujui')) dlg.close(); });
    document.getElementById('vTolak').addEventListener('click',  () => { if (putuskan(aktif.id, 'ditolak'))   dlg.close(); });
    document.getElementById('vClose').addEventListener('click',  () => dlg.close());
    dlg.addEventListener('click', (e) => { if (e.target === dlg) dlg.close(); });

    render();
})();

// ===== Halaman Login & Registrasi (auth/login.php, auth/register.php) =====
(function () {
    'use strict';

    const form = document.querySelector('.login-form');
    if (!form) return;                       // bukan halaman login/registrasi

    const btn   = form.querySelector('.login-btn');
    const label = btn ? btn.textContent : '';

    // Tiap kolom password punya tombol tampil/sembunyi dan peringatan Caps Lock sendiri
    form.querySelectorAll('.field').forEach((field) => {
        const input  = field.querySelector('input[type="password"]');
        const toggle = field.querySelector('.pw-toggle');
        const caps   = field.querySelector('.caps');
        if (!input) return;

        toggle?.addEventListener('click', () => {
            const tampil = input.type === 'password';
            input.type = tampil ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', tampil ? 'true' : 'false');
            toggle.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
            input.focus();
        });

        if (caps) {
            const cekCaps = (e) => { if (e.getModifierState) caps.hidden = !e.getModifierState('CapsLock'); };
            input.addEventListener('keydown', cekCaps);
            input.addEventListener('keyup', cekCaps);
            input.addEventListener('blur', () => { caps.hidden = true; });
        }
    });

    // Cegah klik ganda saat form dikirim
    form.addEventListener('submit', () => {
        if (!btn) return;
        btn.disabled = true;
        btn.textContent = 'Memproses...';
    });
    window.addEventListener('pageshow', () => {   // tombol Back di browser
        if (!btn) return;
        btn.disabled = false;
        btn.textContent = label;
    });
})();