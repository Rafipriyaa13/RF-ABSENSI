<?php
// Bagian backend AWAL =======================
require_once __DIR__ . '/../includes/Functions.php';
require_once __DIR__ . '/../config/database.php';

/* =====================================================================
 * KONTRAK DATA (untuk backend)
 * FORM (POST ke halaman ini):
 *   email     string
 *   password  string
 *   ingat     '1' jika kotak "Ingat saya" dicentang (tidak ada jika tidak dicentang)
 *
 *  $error  string|null  pesan jika login gagal (mis. 'Email atau password salah.')
 *  $old    ['email'=>]  isian email yang dipertahankan saat gagal (password TIDAK dikembalikan)
 *  $flash  ['tipe'=>'sukses'|'error', 'pesan'=>] atau null (mis. pesan "Anda telah keluar" dari logout.php)
 *
 * Setelah login sukses: simpan data user di $_SESSION lalu redirect sesuai role
 * (mis. manager -> /manager/dashboard.php), lalu exit;
 * ===================================================================== */
$error = null;                 // DUMMY: isi string untuk melihat tampilan error
$old   = ['email' => ''];
$flash = get_flash();

$pageTitle = 'Masuk';
// Bagian backend AKHIR ======================
//============================================

// Bagian halaman frontend ==================================
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Richeese Factory</title>
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
                <input type="email" id="email" name="email" placeholder="nama@richeese.co.id" value="<?= e($old['email']) ?>" autocomplete="username" required autofocus>
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

        <p class="login-foot">Lupa password? Hubungi admin pusat.</p>
    </main>

    <script>
        (function () {
            var form = document.getElementById('loginForm');
            var pw = document.getElementById('password');
            var toggle = document.getElementById('pwToggle');
            var caps = document.getElementById('caps');
            var btn = document.getElementById('loginBtn');

            // Tampilkan / sembunyikan password
            toggle.addEventListener('click', function () {
                var tampil = pw.type === 'password';
                pw.type = tampil ? 'text' : 'password';
                toggle.setAttribute('aria-pressed', tampil ? 'true' : 'false');
                toggle.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
                pw.focus();
            });

            // Peringatan Caps Lock
            function cekCaps(e) {
                if (e.getModifierState) caps.hidden = !e.getModifierState('CapsLock');
            }
            pw.addEventListener('keydown', cekCaps);
            pw.addEventListener('keyup', cekCaps);
            pw.addEventListener('blur', function () { caps.hidden = true; });

            // Cegah klik ganda saat mengirim
            form.addEventListener('submit', function () {
                btn.disabled = true;
                btn.textContent = 'Memproses...';
            });
            // Tombol kembali aktif jika halaman dibuka lagi dari riwayat browser
            window.addEventListener('pageshow', function () {
                btn.disabled = false;
                btn.textContent = 'Masuk';
            });
        })();
    </script>
</body>

</html>