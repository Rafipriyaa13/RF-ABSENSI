<?php
require_once __DIR__ . '/includes/Functions.php';

$pageTitle = 'Selamat Datang';
$layout    = 'public';  

include __DIR__ . '/includes/Header.php';
?>

<section class="hero">
    <h1>Sistem Monitoring Absensi Richeese Factory</h1>
    <p>Jadwal shift, presensi, dan izin dalam satu tempat.</p>
    <a class="btn" href="/auth/login.php">Masuk</a>
</section>

<?php include __DIR__ . '/includes/Footer.php'; ?>