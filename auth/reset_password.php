<?php
// auth/reset_password.php
require_once __DIR__ . '/../includes/Functions.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../config/database.php';

if (sudah_login()) {
    header('Location: ' . dashboard_url($_SESSION['role']));
    exit;
}

$pageTitle = 'Buat Password Baru — ';
$token     = trim($_GET['token'] ?? '');
$error     = '';
$valid     = false;
$resetData = null;

// 1. Validasi token dari URL
if ($token !== '') {
  $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$resetData = $stmt->fetch();

    if ($resetData) {
        $valid = true;
    } else {
        $error = 'Tautan reset password tidak valid atau sudah kedaluwarsa (lebih dari 1 jam).';
    }
} else {
    $error = 'Token reset password tidak ditemukan.';
}

// 2. Proses simpan password baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $password   = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if (strlen($password) < 6) {
        $error = 'Password baru minimal terdiri dari 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        try {
            $pdo->beginTransaction();

            // Update password_hash di tabel users
            $hashBaru = password_hash($password, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $upd->execute([$hashBaru, $resetData['email']]);

            // Hapus token agar tidak bisa dipakai dua kali
            $del = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->execute([$resetData['email']]);

            $pdo->commit();

            set_flash('sukses', 'Password berhasil diperbarui. Silakan masuk menggunakan password baru Anda.');
            header('Location: /auth/login.php');
            exit;
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Terjadi kesalahan saat memperbarui password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?>Richeese Factory</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time(); ?>">
</head>

<body class="login-page">
    <main class="card login-card">
        <div class="login-logo"><img src="/assets/foto/image.png" alt="Richeese Factory"></div>

        <h1>Password Baru</h1>
        <p class="login-sub">Silakan buat password baru untuk akun Anda.</p>

        <?php if ($error): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($valid): ?>
            <form method="post" action="" class="login-form">
                <div class="field">
                    <label for="password">Password Baru</label>
                    <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" minlength="6" required autofocus>
                </div>

                <div class="field">
                    <label for="konfirmasi">Ulangi Password Baru</label>
                    <input type="password" id="konfirmasi" name="konfirmasi" placeholder="Ketik ulang password baru" minlength="6" required>
                </div>

                <button type="submit" class="login-btn">Simpan Password Baru</button>
            </form>
        <?php else: ?>
            <p class="login-foot" style="margin-top:16px;">
                <a href="/auth/lupa_password.php" class="btn" style="justify-content:center;width:100%;">Minta Tautan Baru</a>
            </p>
        <?php endif; ?>

        <p class="login-foot"><a href="/auth/login.php">← Kembali ke halaman Masuk</a></p>
    </main>
</body>

</html>