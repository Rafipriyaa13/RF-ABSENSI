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