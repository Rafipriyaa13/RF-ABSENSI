<?php
// auth/lupa_password.php
require_once __DIR__ . '/../includes/Functions.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../config/database.php';

if (sudah_login()) {
    header('Location: ' . dashboard_url($_SESSION['role']));
    exit;
}

$pageTitle = 'Lupa Password — ';
$error     = '';
$sukses    = '';
$email     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Masukkan format email yang valid.';
    } else {
        // Cek apakah email terdaftar dan akun aktif
        $stmt = $pdo->prepare("SELECT id, nama, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['status'] === 'aktif') {
            // 1. Hapus token lama milik email ini jika ada
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

            // 2. Buat token acak 64 karakter & masa berlaku 1 jam
            // 2. Buat token acak 64 karakter & masa berlaku 1 jam langsung pakai jam Database
            $token = bin2hex(random_bytes(32));

            $ins = $pdo->prepare("
    INSERT INTO password_resets (email, token, expires_at) 
    VALUES (?, ?, NOW() + INTERVAL '1 hour')
");
            $ins->execute([$email, $token]);
            // 3. Susun link reset password
            $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host      = $_SERVER['HTTP_HOST'];
            $resetLink = $protocol . $host . '/auth/reset_password.php?token=' . $token;

            // 4. Kirim email menggunakan PHPMailer (jika terinstal)
            $emailTerkirim = false;
            $autoloadPath  = __DIR__ . '/../vendor/autoload.php';

            if (file_exists($autoloadPath)) {
                require_once $autoloadPath;
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'raffnugraha13@gmail.com'; // Ganti dengan alamat Gmail Anda
                        $mail->Password   = 'dnxx rjwh gurc zjvr';          // Paste 16 digit App Password di sini
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        $mail->setFrom('raffnugraha13@gmail.com', 'Sistem Absensi Richeese Factory');
                        $mail->addAddress($email, $user['nama']);

                        $mail->isHTML(true);
                        $mail->Subject = 'Reset Password Akun Absensi';
                        $mail->Body    = "
                            <p>Halo <b>" . e($user['nama']) . "</b>,</p>
                            <p>Kami menerima permintaan untuk mengatur ulang password akun Anda. Klik tautan di bawah ini (berlaku selama 1 jam):</p>
                            <p><a href='$resetLink' style='padding:10px 16px;background:#e8182c;color:#fff;text-decoration:none;border-radius:6px;display:inline-block;'>Reset Password Sekarang</a></p>
                            <p>Atau salin link ini ke browser: <br><small>$resetLink</small></p>
                            <p>Jika Anda tidak merasa meminta reset password, abaikan email ini.</p>
                        ";

                        $mail->send();
                        $emailTerkirim = true;
                    } catch (Exception $e) {
                        error_log("Gagal kirim email reset: " . $mail->ErrorInfo);
                    }
                }
            }

            if ($emailTerkirim) {
                $sukses = 'Tautan reset password telah dikirim ke email <b>' . e($email) . '</b>. Silakan cek kotak masuk atau folder spam.';
            } else {
                // Fallback jika di localhost belum pasang PHPMailer/SMTP
                $sukses = 'Tautan reset password berhasil dibuat! (Mode Localhost tanpa SMTP): <br><a href="' . e($resetLink) . '" style="text-decoration:underline;font-weight:600;">Klik di sini untuk Reset Password</a>';
            }
        } else {
            // Pesan netral demi keamanan (mencegah enumeration email)
            $sukses = 'Jika email tersebut terdaftar dan aktif, tautan reset password telah dikirimkan.';
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

        <h1>Lupa Password</h1>
        <p class="login-sub">Masukkan email terdaftar untuk menerima tautan reset password.</p>

        <?php if ($error): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php elseif ($sukses): ?>
            <div class="alert alert-sukses" role="status"><?= $sukses ?></div>
        <?php endif; ?>

        <form method="post" action="" class="login-form" id="forgotForm">
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="nama@richeese.co.id" value="<?= e($email) ?>" required autofocus>
            </div>

            <button type="submit" class="login-btn" id="submitBtn">Kirim Tautan Reset</button>
        </form>

        <p class="login-foot"><a href="/auth/login.php" style="font-weight:600;">← Kembali ke halaman Masuk</a></p>
    </main>

    <script>
        document.getElementById('forgotForm')?.addEventListener('submit', function() {
            var btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Mengirim...';
        });
    </script>
</body>

</html>