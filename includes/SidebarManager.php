<?php
/**
 * includes/SidebarManager.php
 * Set $activeMenu di halaman (mis. 'kepegawaian') agar menu aktif.
 */
$baseUrl    = defined('BASE_URL') ? BASE_URL : '/RF-ABSENSI';
$activeMenu = $activeMenu ?? '';

$icons = [
    'dashboard'   => '<path d="M3 3h7v9H3zM14 3h7v5h-7zM14 12h7v9h-7zM3 16h7v5H3z"/>',
    'logistik'    => '<path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
    'kepegawaian' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.5 3-6 6.5-6s6.5 2.5 6.5 6"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14c2.2.6 3.5 2.6 3.5 6"/>',
    'outlet'      => '<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
    'laporan'     => '<path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
    'pengaturan'  => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1"/>',
];

$menu = [
    'dashboard'   => ['Dashboard',   '/manager/index.php'],
    'logistik'    => ['Logistik',    '/manager/logistik/index.php'],
    'kepegawaian' => ['Kepegawaian', '/manager/pegawai/index.php'],
    'outlet'      => ['Outlet',      '/manager/outlet/index.php'],
    'laporan'     => ['Laporan',     '/manager/laporan/index.php'],
    'pengaturan'  => ['Pengaturan',  '/manager/pengaturan/index.php'],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <span class="logo">Richeese<small>Factory</small></span>
        <button class="icon-btn sidebar-close" id="sidebarClose" aria-label="Tutup menu">&times;</button>
    </div>

    <nav class="nav">
        <?php foreach ($menu as $key => [$label, $path]): ?>
            <a href="<?= $baseUrl . $path ?>" class="nav-link <?= $activeMenu === $key ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $icons[$key] ?></svg>
                <span><?= $label ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-user">
        <span class="avatar"></span>
        <div>
            <strong><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin Pusat') ?></strong>
            <small><?= htmlspecialchars($_SESSION['lokasi'] ?? 'Bandung') ?></small>
        </div>
    </div>
</aside>