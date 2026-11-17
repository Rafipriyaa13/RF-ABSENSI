<?php
// auth/register.php
// Akun dibuat manager (tanpa password). Di sini pegawai mengisi password miliknya sendiri.
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../config/database.php';

if (sudah_login()) {
    header('Location: ' . dashboard_url($_SESSION['role']));
    exit;
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email      = strtolower(trim($_POST['email'] ?? ''));
    $password   = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password minimal 8 karakter.';
    }
    if ($password !== $konfirmasi) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }

    if (!$errors) {
        // Hanya akun aktif yang sudah ditambahkan manager dan belum punya password
        $stmt = $pdo->prepare("
            SELECT id FROM users
            WHERE email = ? AND status = 'aktif' AND password_hash IS NULL
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // "AND password_hash IS NULL" memastikan dua permintaan bersamaan tidak saling menimpa
            $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND password_hash IS NULL");
            $upd->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);

            if ($upd->rowCount() === 1) {
                set_flash('sukses', 'Registrasi berhasil. Silakan masuk dengan email dan password barumu.');
                header('Location: /auth/login.php');
                exit;
            }
        }

        // Satu pesan untuk semua penyebab (email belum didaftarkan, sudah registrasi, nonaktif)
        // supaya orang luar tidak bisa mengecek email mana yang terdaftar.
        $errors[] = 'Email tidak dapat didaftarkan. Pastikan sama dengan email yang diberikan manager. '
                  . 'Jika sudah pernah registrasi, silakan masuk.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrasi - Richeese Factory</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/form.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body>
<div class="auth-wrap">
    <div class="card auth-card">
        <div class="logo">Richeese<small>Factory</small></div>
        <div>
            <h1>Registrasi akun</h1>
            <p>Gunakan email yang didaftarkan manager outlet, lalu buat passwordmu sendiri.</p>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="fsec-body" autocomplete="on">
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus>
            </div>
            <div class="field">
                <label for="password">Buat password</label>
                <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
                <span class="hint">Minimal 8 karakter.</span>
            </div>
            <div class="field">
                <label for="konfirmasi">Konfirmasi password</label>
                <input type="password" id="konfirmasi" name="konfirmasi" minlength="8" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn">Daftar</button>
        </form>

        <div class="auth-links">
            <a href="login.php">Sudah punya akun? Masuk</a>
        </div>
    </div>
</div>
</body>
</html>