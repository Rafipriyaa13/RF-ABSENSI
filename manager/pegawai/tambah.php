<?php
require_once '../../includes/Functions.php';
require_once '../../config/database.php';
// require_once '../../includes/auth.php';
// cek_role('manager');   // aktifkan setelah login selesai

$errors = [];
$old = ['nama' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['nama']  = trim($_POST['nama'] ?? '');
    $old['email'] = strtolower(trim($_POST['email'] ?? ''));

    if ($old['nama'] === '') {
        $errors[] = 'Nama wajib diisi.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (nama, email, role, status)
                VALUES (?, ?, 'pegawai', 'aktif')
            ");
            $stmt->execute([$old['nama'], $old['email']]);

            set_flash('sukses', 'Pegawai ditambahkan. Minta pegawai registrasi memakai email tersebut.');
            header('Location: index.php');
            exit;
        } catch (PDOException $ex) {
            $errors[] = $ex->getCode() === '23505'
                ? 'Email sudah terdaftar.'
                : 'Terjadi kesalahan saat menyimpan data.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Pegawai</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <h1>Tambah Pegawai</h1>

    <?php if ($errors): ?>
        <ul class="alert-error">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="nama">Nama lengkap</label>
        <input type="text" id="nama" name="nama" value="<?= e($old['nama']) ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>

        <button type="submit">Simpan</button>
        <a href="index.php">Batal</a>
    </form>
</body>
</html>