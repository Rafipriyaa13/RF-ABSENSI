<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

$errors = [];
$email = '';

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
        $stmt = $pdo->prepare("SELECT id, password_hash, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = 'Email belum terdaftar. Hubungi manager outlet.';
        } elseif ($user['password_hash'] !== null) {
            $errors[] = 'Akun sudah teregistrasi. Silakan login.';
        } elseif ($user['status'] !== 'aktif') {
            $errors[] = 'Akun tidak aktif. Hubungi manager outlet.';
        } else {
            // "AND password_hash IS NULL" mencegah dua registrasi bersamaan menimpa satu sama lain
            $upd = $pdo->prepare("
                UPDATE users SET password_hash = ?
                WHERE id = ? AND password_hash IS NULL
            ");
            $upd->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);

            if ($upd->rowCount() === 1) {
                set_flash('sukses', 'Registrasi berhasil. Silakan login.');
                header('Location: login.php');
                exit;
            }
            $errors[] = 'Registrasi gagal. Coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrasi Pegawai</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <h1>Registrasi Akun</h1>
    <p>Gunakan email yang didaftarkan oleh manager outlet.</p>

    <?php if ($errors): ?>
        <ul class="alert-error">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required>

        <label for="password">Buat password</label>
        <input type="password" id="password" name="password" minlength="8" required>

        <label for="konfirmasi">Konfirmasi password</label>
        <input type="password" id="konfirmasi" name="konfirmasi" minlength="8" required>

        <button type="submit">Daftar</button>
        <a href="login.php">Sudah punya akun? Login</a>
    </form>
</body>
</html>