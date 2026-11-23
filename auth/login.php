<?php
require_once __DIR__ . '/../includes/Functions.php'; // Pastikan fungsi e() dan get_flash() tersedia
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../config/database.php';

if (sudah_login()) {
    header('Location: ' . dashboard_url($_SESSION['role']));
    exit;
}

const MAKS_GAGAL = 5;
const KUNCI_DETIK = 300;

$pageTitle = 'Masuk — ';
$flash     = get_flash();
$error     = '';
$email     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (($_SESSION['login_kunci'] ?? 0) > time()) {
        $sisa  = (int) ceil(($_SESSION['login_kunci'] - time()) / 60);
        $error = "Terlalu banyak percobaan. Coba lagi dalam $sisa menit.";
    } else {
        $stmt = $pdo->prepare("
            SELECT id, kode_pegawai, nama, email, password_hash, role, status
            FROM users WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $punyaPassword = $user && $user['password_hash'] !== null;
        $hash          = $punyaPassword ? $user['password_hash'] : password_hash('dummy', PASSWORD_DEFAULT);
        $cocok         = password_verify($password, $hash) && $punyaPassword && $user['status'] === 'aktif';

        if ($cocok) {
            session_regenerate_id(true);
            $_SESSION['user_id']      = (int) $user['id'];
            $_SESSION['kode_pegawai'] = $user['kode_pegawai'];
            $_SESSION['nama']         = $user['nama'];
            $_SESSION['role']         = $user['role'];
            unset($_SESSION['login_gagal'], $_SESSION['login_kunci']);
            
            header('Location: ' . dashboard_url($user['role']));
            exit;
        }

        $error = 'Email atau password salah.';
        $_SESSION['login_gagal'] = ($_SESSION['login_gagal'] ?? 0) + 1;
        if ($_SESSION['login_gagal'] >= MAKS_GAGAL) {
            $_SESSION['login_kunci'] = time() + KUNCI_DETIK;
            $_SESSION['login_gagal'] = 0;
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

        <h1>Masuk</h1>
        <p class="login-sub">Sistem Absensi Richeese Factory</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php elseif (!empty($flash)): ?>
            <div class="alert alert-<?= e($flash['tipe']) ?>" role="status"><?= e($flash['pesan']) ?></div>
        <?php endif; ?>

        <form method="post" action="" class="login-form" id="loginForm">
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="nama@richeese.co.id" value="<?= e($email) ?>" autocomplete="username" required autofocus>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="pw">
                    <input type="password" id="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
                    <button type="button" class="pw-toggle" id="pwToggle" aria-label="Tampilkan password" aria-pressed="false">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" />
                            <circle cx="12" cy="12" r="3" />
                            <path class="slash" d="M3 3l18 18" />
                        </svg>
                    </button>
                </div>
                <span class="caps" id="caps" hidden>Caps Lock aktif</span>
            </div>

            <label class="check"><input type="checkbox" name="ingat" value="1"> Ingat saya</label>

            <button type="submit" class="login-btn" id="loginBtn">Masuk</button>
        </form>

        <p class="login-foot">Belum punya password? <a href="/auth/register.php">Daftar di sini</a></p>
<p class="login-foot">Lupa password? <a href="/auth/lupa_password.php" style="font-weight:600;text-decoration:underline;">Reset di sini</a></p>
    </main>

    <script>
        (function() {
            var form = document.getElementById('loginForm');
            var pw = document.getElementById('password');
            var toggle = document.getElementById('pwToggle');
            var caps = document.getElementById('caps');
            var btn = document.getElementById('loginBtn');

            toggle.addEventListener('click', function() {
                var tampil = pw.type === 'password';
                pw.type = tampil ? 'text' : 'password';
                toggle.setAttribute('aria-pressed', tampil ? 'true' : 'false');
                toggle.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
                pw.focus();
            });

            function cekCaps(e) {
                if (e.getModifierState) caps.hidden = !e.getModifierState('CapsLock');
            }
            pw.addEventListener('keydown', cekCaps);
            pw.addEventListener('keyup', cekCaps);
            pw.addEventListener('blur', function() {
                caps.hidden = true;
            });

            form.addEventListener('submit', function() {
                btn.disabled = true;
                btn.textContent = 'Memproses...';
            });
            window.addEventListener('pageshow', function() {
                btn.disabled = false;
                btn.textContent = 'Masuk';
            });
        })();
    </script>
</body>

</html>