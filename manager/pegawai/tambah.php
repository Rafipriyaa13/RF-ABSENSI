<?php
// manager/pegawai/tambah.php
require_once __DIR__ . '/../../includes/Functions.php';
require_once __DIR__ . '/../../config/database.php';
// require_once __DIR__ . '/../../includes/Auth.php';
// cek_role('manager');   // aktifkan setelah login selesai

$pageTitle    = 'Tambah Pegawai';
$pageSubtitle = 'Menambahkan data pegawai baru ke sistem';
$activeMenu   = 'kepegawaian';

/** Kode berikutnya: RF-EMP01, RF-EMP02, ... */
function buat_kode(PDO $pdo): string
{
    $n = $pdo->query("
        SELECT COALESCE(MAX(substring(kode_pegawai from 'RF-EMP([0-9]+)')::int), 0) + 1 FROM users
    ")->fetchColumn();
    return 'RF-EMP' . str_pad((string) $n, 2, '0', STR_PAD_LEFT);
}

function tanggal_valid(string $t): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $t);
    return $d && $d->format('Y-m-d') === $t;
}

$errors = [];
$old = ['nama' => '', 'email' => '', 'role' => 'pegawai', 'jabatan' => '',
        'kontrak_mulai' => '', 'kontrak_selesai' => '', 'rfid_uid' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) {
        $old[$k] = trim($_POST[$k] ?? $old[$k]);
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
        $stmt = $pdo->prepare("
            INSERT INTO users (kode_pegawai, nama, email, role, jabatan,
                               kontrak_mulai, kontrak_selesai, rfid_uid, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aktif')
        ");

        $sukses = false;
        for ($i = 0; $i < 3 && !$sukses; $i++) {      // ulangi bila kode bentrok dengan input bersamaan
            try {
                $stmt->execute([
                    buat_kode($pdo), $old['nama'], $old['email'], $old['role'],
                    $old['jabatan']        ?: null,
                    $old['kontrak_mulai']  ?: null,   // string kosong ditolak kolom DATE
                    $old['kontrak_selesai'] ?: null,
                    $old['rfid_uid']       ?: null,   // dan melanggar UNIQUE bila dobel
                ]);
                $sukses = true;
            } catch (PDOException $ex) {
                if ($ex->getCode() !== '23505') {
                    $errors[] = 'Terjadi kesalahan saat menyimpan data.';
                    break;
                }
                $msg = $ex->errorInfo[2] ?? '';
                if (str_contains($msg, 'kode_pegawai')) {
                    continue;
                }
                $errors[] = str_contains($msg, 'rfid')  ? 'Kartu RFID ini sudah dipakai pegawai lain.'
                          : (str_contains($msg, 'email') ? 'Email sudah terdaftar.' : 'Data sudah terdaftar.');
                break;
            }
        }

        if ($sukses) {
            set_flash('sukses', 'Pegawai ditambahkan. Minta pegawai registrasi memakai email ' . $old['email'] . '.');
            header('Location: /manager/pegawai/index.php');
            exit;
        }
        if (!$errors) {
            $errors[] = 'Gagal membuat kode pegawai. Coba simpan lagi.';
        }
    }
}

$kodeBerikut = buat_kode($pdo);
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
                <p>Email dipakai pegawai untuk registrasi dan login.</p>
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
                    <span class="hint">Pegawai membuat password sendiri saat registrasi dengan email ini.</span>
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
                <p>Boleh dikosongkan dan didaftarkan nanti lewat halaman edit.</p>
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
            <button type="submit" class="btn">Simpan pegawai</button>
        </div>
    </form>

    <aside class="card side">
        <div class="kode-box">
            <small>Kode pegawai</small>
            <b><?= e($kodeBerikut) ?></b>
            <span>Dibuat otomatis saat disimpan.</span>
        </div>
        <div>
            <h3>Setelah disimpan</h3>
            <ol class="steps">
                <li><b>Data masuk sistem</b>Pegawai belum bisa login karena belum punya password.</li>
                <li><b>Pegawai registrasi</b>Buka halaman registrasi dan isi email yang sama untuk membuat password.</li>
                <li><b>Siap dipakai</b>Pegawai login atau menempelkan kartu untuk absen.</li>
            </ol>
        </div>
    </aside>
</div>

<?php include __DIR__ . '/../../includes/Footer.php'; ?>