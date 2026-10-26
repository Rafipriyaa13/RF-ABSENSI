<?php
$menu = [
    'dashboard'   => ['Dashboard',   '/manager/dashboard.php'],
    'kepegawaian' => ['Kepegawaian', '/manager/pegawai/index.php'],
    'jadwal'      => ['Jadwal',      '/manager/jadwal/index.php'],
    'absensi'     => ['Absensi',     '/manager/absensi/log.php'],
    'izin'        => ['Izin / Cuti', '/manager/izin_cuti/index.php'],
    'laporan'     => ['Laporan',     '/manager/laporan/rekap.php'],
];
$namaLogin = $_SESSION['nama'] ?? 'Store Manager';
?>
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <div class="logo"><img src="/assets/foto/image.png" alt="Logo"></div>
        <button class="nav-close" type="button" data-nav-toggle aria-label="Tutup menu">&times;</button>
    </div>
    <nav class="nav">
        <?php foreach ($menu as $key => [$label, $url]): ?>
            <a href="<?= $url ?>" class="<?= ($activeMenu ?? '') === $key ? 'active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="me">
        <div class="avatar"></div>
        <div>
            <b><?= e($namaLogin) ?></b>
            <a href="/auth/logout.php">Keluar</a>
        </div>
    </div>
</aside>
<div class="overlay" data-nav-toggle></div>