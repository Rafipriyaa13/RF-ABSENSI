<?php
require_once __DIR__ . '/../includes/Functions.php';   
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi — Richeese Factory</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time(); ?>">
</head>

<body class="login-page">
    <main class="card login-card">
        <div class="login-logo"><img src="/assets/foto/image.png" alt="Richeese Factory"></div>

        <h1>Registrasi</h1>
        <p class="login-sub">Gunakan email yang didaftarkan manager, lalu buat passwordmu sendiri.</p>

        <?php if ($errors): ?>
            <div class="alert alert-error" role="alert">
                <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="" class="login-form" id="regForm" autocomplete="on">
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="nama@richeese.co.id" value="<?= e($email) ?>" autocomplete="username" required autofocus>
            </div>

            <div class="field">
                <label for="password">Buat password</label>
                <div class="pw">
                    <input type="password" id="password" name="password" placeholder="Minimal 8 karakter" minlength="8" autocomplete="new-password" required>
                    <button type="button" class="pw-toggle" data-pw-toggle="password" aria-label="Tampilkan password" aria-pressed="false">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" />
                            <circle cx="12" cy="12" r="3" />
                            <path class="slash" d="M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                <span class="field-hint" id="hintPanjang">Minimal 8 karakter.</span>
                <span class="caps" id="caps" hidden>Caps Lock aktif</span>
            </div>

            <div class="field">
                <label for="konfirmasi">Konfirmasi password</label>
                <div class="pw">
                    <input type="password" id="konfirmasi" name="konfirmasi" placeholder="Ulangi password" minlength="8" autocomplete="new-password" required>
                    <button type="button" class="pw-toggle" data-pw-toggle="konfirmasi" aria-label="Tampilkan password" aria-pressed="false">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" />
                            <circle cx="12" cy="12" r="3" />
                            <path class="slash" d="M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                <span class="field-hint" id="hintCocok" hidden></span>
            </div>

            <button type="submit" class="login-btn" id="regBtn">Daftar</button>
        </form>

        <p class="login-foot">Sudah punya akun? <a href="/auth/login.php">Masuk</a></p>
    </main>

    <script>
        (function() {
            var form = document.getElementById('regForm');
            var pw = document.getElementById('password');
            var kf = document.getElementById('konfirmasi');
            var caps = document.getElementById('caps');
            var btn = document.getElementById('regBtn');
            var hintPanjang = document.getElementById('hintPanjang');
            var hintCocok = document.getElementById('hintCocok');

            // Tombol tampilkan / sembunyikan password (untuk kedua kolom)
            [].forEach.call(document.querySelectorAll('[data-pw-toggle]'), function(t) {
                t.addEventListener('click', function() {
                    var input = document.getElementById(t.dataset.pwToggle);
                    var tampil = input.type === 'password';
                    input.type = tampil ? 'text' : 'password';
                    t.setAttribute('aria-pressed', tampil ? 'true' : 'false');
                    t.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
                    input.focus();
                });
            });

            // Peringatan Caps Lock
            function cekCaps(e) {
                if (e.getModifierState) caps.hidden = !e.getModifierState('CapsLock');
            }
            [pw, kf].forEach(function(el) {
                el.addEventListener('keydown', cekCaps);
                el.addEventListener('keyup', cekCaps);
                el.addEventListener('blur', function() { caps.hidden = true; });
            });

            // Petunjuk langsung: panjang password & kecocokan konfirmasi
            function periksa() {
                var cukup = pw.value.length >= 8;
                hintPanjang.classList.toggle('ok', cukup);

                if (kf.value === '') {
                    hintCocok.hidden = true;
                    return;
                }
                var cocok = kf.value === pw.value;
                hintCocok.hidden = false;
                hintCocok.textContent = cocok ? 'Password cocok.' : 'Password belum cocok.';
                hintCocok.classList.toggle('ok', cocok);
                hintCocok.classList.toggle('bad', !cocok);
            }
            pw.addEventListener('input', periksa);
            kf.addEventListener('input', periksa);

            form.addEventListener('submit', function() {
                btn.disabled = true;
                btn.textContent = 'Memproses...';
            });
            window.addEventListener('pageshow', function() {
                btn.disabled = false;
                btn.textContent = 'Daftar';
            });
        })();
    </script>
</body>

</html>