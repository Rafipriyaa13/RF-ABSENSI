<?php
$menu = [
    'dashboard' => ['Dashboard',       '/pegawai/dashboard.php'],
    'absensi'   => ['Absensi Pegawai', '/pegawai/absensi.php'],
    'jadwal'    => ['Jadwal Shift',    '/pegawai/jadwal.php'],
    'ajukan'    => ['Izin Cuti',          '/pegawai/izin_cuti/ajukan.php'],
];
// $namaLogin bisa diisi oleh halaman; jika tidak, ambil dari session.
$namaLogin = $namaLogin ?? ($_SESSION['nama'] ?? 'Pegawai');
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
        <!-- Klik foto / nama untuk membuka halaman profil -->
        <a href="/pegawai/profil.php" class="me-avatar" aria-label="Buka profil saya" tabindex="-1">
            <span class="avatar"></span>
        </a>
        <div>
            <a href="/pegawai/profil.php" class="me-name" title="Buka profil saya"><b><?= e($namaLogin) ?></b></a>
            <a href="/auth/logout.php">Keluar</a>
        </div>
    </div>
</aside>
<div class="overlay" data-nav-toggle></div>