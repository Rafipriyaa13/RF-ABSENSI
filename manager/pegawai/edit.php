<?php
// manager/pegawai/edit.php
require_once __DIR__ . '/../../includes/Functions.php';
require_once __DIR__ . '/../../config/database.php';
// require_once __DIR__ . '/../../includes/Auth.php';
// cek_role('manager');   // aktifkan setelah login selesai

// 1. Validasi parameter ID dari URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    set_flash('error', 'ID pegawai tidak valid.');
    header('Location: /manager/pegawai/index.php');
    exit;
}

// 2. Ambil data pegawai dari database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('error', 'Data pegawai tidak ditemukan.');
    header('Location: /manager/pegawai/index.php');
    exit;
}

$pageTitle    = 'Edit Pegawai';
$pageSubtitle = 'Perbarui data pegawai ' . $user['kode_pegawai'];
$activeMenu   = 'kepegawaian';

function tanggal_valid(string $t): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $t);
    return $d && $d->format('Y-m-d') === $t;
}

$errors = [];
// Isi variabel awal dengan data lama dari DB
$old = [
    'nama'            => $user['nama'] ?? '',
    'email'           => $user['email'] ?? '',
    'role'            => $user['role'] ?? 'pegawai',
    'jabatan'         => $user['jabatan'] ?? '',
    'kontrak_mulai'   => $user['kontrak_mulai'] ?? '',
    'kontrak_selesai' => $user['kontrak_selesai'] ?? '',
    'rfid_uid'        => $user['rfid_uid'] ?? '',
    'status'          => $user['status'] ?? 'aktif',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) {
        if (isset($_POST[$k])) {
            $old[$k] = trim($_POST[$k]);
        }
    }
    $old['email']    = strtolower($old['email']);
    $old['rfid_uid'] = strtoupper($old['rfid_uid']);

    if ($old['nama'] === '' || mb_strlen($old['nama']) > 100) {
        $errors[] = 'Nama wajib diisi (maksimal 100 karakter).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 100) {
        $errors[] = 'Format email tidak valid.';
    }
    if (!in_array($old['role'], ['pegawai', 'manager'], true)) {
        $errors[] = 'Role akses tidak valid.';
    }
    if (!in_array($old['status'], ['aktif', 'nonaktif'], true)) {
        $errors[] = 'Status pegawai tidak valid.';
    }
    if (mb_strlen($old['jabatan']) > 50) {
        $errors[] = 'Jabatan maksimal 50 karakter.';
    }
    foreach (['kontrak_mulai' => 'mulai', 'kontrak_selesai' => 'selesai'] as $k => $label) {
        if ($old[$k] !== '' && !tanggal_valid($old[$k])) {
            $errors[] = "Tanggal $label kontrak tidak valid.";
        }
    }
    if ($old['kontrak_mulai'] !== '' && $old['kontrak_selesai'] !== ''
        && $old['kontrak_selesai'] < $old['kontrak_mulai']) {
        $errors[] = 'Tanggal selesai kontrak tidak boleh sebelum tanggal mulai.';
    }
    if (strlen($old['rfid_uid']) > 30) {
        $errors[] = 'ID kartu RFID terlalu panjang (maksimal 30 karakter).';
    }

    if (!$errors) {
        try {
            $updateStmt = $pdo->prepare("
                UPDATE users 
                SET nama = ?, email = ?, role = ?, jabatan = ?,
                    kontrak_mulai = ?, kontrak_selesai = ?, rfid_uid = ?, status = ?
                WHERE id = ?
            ");
            $updateStmt->execute([
                $old['nama'],
                $old['email'],
                $old['role'],
                $old['jabatan']         ?: null,
                $old['kontrak_mulai']   ?: null,
                $old['kontrak_selesai'] ?: null,
                $old['rfid_uid']        ?: null,
                $old['status'],
                $id
            ]);

            set_flash('sukses', 'Data pegawai ' . $user['kode_pegawai'] . ' berhasil diperbarui.');
            header('Location: /manager/pegawai/index.php');
            exit;

        } catch (PDOException $ex) {
            $msg = $ex->errorInfo[2] ?? '';
            if ($ex->getCode() === '23505') {
                if (str_contains($msg, 'rfid')) {
                    $errors[] = 'Kartu RFID ini sudah dipakai pegawai lain.';
                } elseif (str_contains($msg, 'email')) {
                    $errors[] = 'Email sudah terdaftar oleh pegawai lain.';
                } else {
                    $errors[] = 'Data sudah terdaftar pada pegawai lain.';
                }
            } else {
                $errors[] = 'Terjadi kesalahan saat menyimpan perubahan.';
            }
        }
    }
}

$daftarJabatan = ['Store Manager', 'Cashier', 'Kitchen Staff', 'Crew'];

include __DIR__ . '/../../includes/Header.php';
?>
<link rel="stylesheet" href="/assets/css/form.css">

<div class="form-layout">
    <form method="POST" action="" class="card form" autocomplete="off">

        <?php if ($errors): ?>
            <ul class="form-errors">
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <section class="fsec">
            <div class="fsec-head">
                <h2>Data akun</h2>
                <p>Perbarui informasi akun dan hak akses pegawai.</p>
            </div>
            <div class="fsec-body">
                <div class="field">
                    <label for="nama">Nama lengkap</label>
                    <input type="text" id="nama" name="nama" value="<?= e($old['nama']) ?>"
                           placeholder="Contoh: Siti Rahma" maxlength="100" required autofocus>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= e($old['email']) ?>"
                           placeholder="nama@email.com" maxlength="100" required>
                </div>
                <div class="grid2">
                    <div class="field">
                        <label for="role">Role akses</label>
                        <select id="role" name="role">
                            <option value="pegawai" <?= $old['role'] === 'pegawai' ? 'selected' : '' ?>>Pegawai</option>
                            <option value="manager" <?= $old['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="jabatan">Jabatan <span>(opsional)</span></label>
                        <input type="text" id="jabatan" name="jabatan" value="<?= e($old['jabatan']) ?>"
                               list="jabatan-list" placeholder="Contoh: Cashier" maxlength="50">
                        <datalist id="jabatan-list">
                            <?php foreach ($daftarJabatan as $j): ?><option value="<?= e($j) ?>"><?php endforeach; ?>
                        </datalist>
                    </div>
                </div>
                <div class="field">
                    <label for="status">Status Kepegawaian</label>
                    <select id="status" name="status">
                        <option value="aktif" <?= $old['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= $old['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="fsec">
            <div class="fsec-head">
                <h2>Masa kontrak</h2>
                <p>Pegawai yang kontraknya hampir habis ditandai di daftar kepegawaian.</p>
            </div>
            <div class="fsec-body">
                <div class="grid2">
                    <div class="field">
                        <label for="kontrak_mulai">Mulai</label>
                        <input type="date" id="kontrak_mulai" name="kontrak_mulai" value="<?= e($old['kontrak_mulai']) ?>">
                    </div>
                    <div class="field">
                        <label for="kontrak_selesai">Selesai</label>
                        <input type="date" id="kontrak_selesai" name="kontrak_selesai" value="<?= e($old['kontrak_selesai']) ?>">
                    </div>
                </div>
                <div class="chips">
                    <small>Isi cepat durasi:</small>
                    <button type="button" class="chip" data-bulan="6">6 bulan</button>
                    <button type="button" class="chip" data-bulan="12">1 tahun</button>
                    <button type="button" class="chip" data-bulan="24">2 tahun</button>
                </div>
            </div>
        </section>

        <section class="fsec">
            <div class="fsec-head">
                <h2>Kartu RFID</h2>
                <p>Tempelkan kartu RFID baru untuk memperbarui UID kartu pegawai ini.</p>
            </div>
            <div class="fsec-body">
                <div class="field">
                    <label for="rfid_uid">ID kartu</label>
                    <div class="rfid" id="rfid-wrap">
                        <span class="rfid-dot"></span>
                        <input type="text" id="rfid_uid" name="rfid_uid" value="<?= e($old['rfid_uid']) ?>"
                               placeholder="Klik kolom ini, lalu tempelkan kartu" maxlength="30">
                    </div>
                    <span class="rfid-state" id="rfid-state">Klik kolom untuk mulai membaca kartu.</span>
                </div>
            </div>
        </section>

        <div class="form-actions">
            <a class="btn-ghost" href="/manager/pegawai/index.php">Batal</a>
            <button type="submit" class="btn">Simpan perubahan</button>
        </div>
    </form>

    <aside class="card side">
        <div class="kode-box">
            <small>Kode pegawai</small>
            <b><?= e($user['kode_pegawai']) ?></b>
            <span>Kode pegawai bersifat unik dan permanen.</span>
        </div>
        <div>
            <h3>Informasi Edit</h3>
            <ol class="steps">
                <li><b>Identitas</b>Ubah nama, email, atau hak akses sesuai posisi kerja terbaru.</li>
                <li><b>Perpanjang Kontrak</b>Gunakan tombol cepat untuk menambah masa berlaku kontrak pegawai.</li>
                <li><b>Ganti Kartu RFID</b>Cukup timpa nilai pada kolom RFID jika kartu pegawai hilang atau diganti.</li>
            </ol>
        </div>
    </aside>
</div>

<?php include __DIR__ . '/../../includes/Footer.php'; ?>