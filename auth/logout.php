<?php
// auth/logout.php
require_once __DIR__ . '/../includes/Functions.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Kosongkan semua variabel di dalam $_SESSION (user_id, role, nama, dll.)
$_SESSION = [];

// 3. Hapus cookie session dari browser pengguna untuk keamanan ekstra
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 4. Hancurkan session di server sepenuhnya
session_destroy();

// 5. Mulai session baru sebentar hanya untuk mengirim notifikasi sukses logout
session_start();
set_flash('sukses', 'Anda telah berhasil keluar dari sistem.');

// 6. Arahkan kembali ke halaman login
header('Location: /auth/login.php');
exit;