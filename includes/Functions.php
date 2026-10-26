<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($teks) {
    return htmlspecialchars($teks ?? '', ENT_QUOTES, 'UTF-8');
}

function set_flash($tipe, $pesan) {
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function get_flash() {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}
function tgl_indo($tanggal) {
    if (!$tanggal) return '';
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $parts = explode('-', $tanggal);
    if (count($parts) !== 3) return $tanggal;
    [$tahun, $bln, $tgl] = $parts;
    return (int)$tgl . ' ' . ($bulan[(int)$bln] ?? '') . ' ' . $tahun;
}