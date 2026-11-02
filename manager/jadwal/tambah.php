<?php
// Bagian backend AWAL =======================
require_once __DIR__ . '/../../includes/Functions.php';
require_once __DIR__ . '/../../config/database.php';
// require_once __DIR__ . '/../../includes/Auth.php';
// cek_role('manager');   // aktifkan setelah login selesai

$pageTitle = 'Tambah Shift';
$pageSubtitle = 'Buat konfigurasi shift kerja baru';
$activeMenu = 'jadwal';
/* =====================================================================
 * KONTRAK DATA (untuk backend)
 * FORM (POST ke halaman ini): nama, ket, mulai (HH:MM), selesai (HH:MM)
 *
 *  $errors  array of string. Isi jika validasi gagal; akan tampil sebagai daftar error merah.
 *  $old     ['nama'=>, 'ket'=>, 'mulai'=>, 'selesai'=>]  isian yang dipertahankan saat error.
 *
 * Setelah sukses simpan: set_flash('sukses', '...'); header('Location: index.php'); exit;
 * ===================================================================== */


$errors = [];
$old = ['nama' => '', 'mulai' => '', 'selesai' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) {
        $old[$k] = trim($_POST[$k] ?? $old[$k]);
    }

    // === VALIDASI ===
    // 1) Nama wajib diisi
    if ($old['nama'] === '') {
        $errors[] = 'Nama shift wajib diisi.';
    } elseif (mb_strlen($old['nama']) > 20) {
        $errors[] = 'Nama shift maksimal 20 karakter.';
    }

    // 2) Jam mulai & selesai wajib terisi dan berformat HH:MM
    $polaJam = '/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/';

    if ($old['mulai'] === '') {
        $errors[] = 'Jam mulai wajib diisi.';
    } elseif (!preg_match($polaJam, $old['mulai'])) {
        $errors[] = 'Format jam mulai tidak valid (contoh: 07:00).';
    }

    if ($old['selesai'] === '') {
        $errors[] = 'Jam selesai wajib diisi.';
    } elseif (!preg_match($polaJam, $old['selesai'])) {
        $errors[] = 'Format jam selesai tidak valid (contoh: 15:00).';
    }

    // 3) Jam mulai tidak boleh sama dengan jam selesai
    //    (selesai < mulai tetap BOLEH, untuk shift yang lewat tengah malam)
    if (
        $old['mulai'] !== '' && $old['selesai'] !== ''
        && $old['mulai'] === $old['selesai']
    ) {
        $errors[] = 'Jam mulai dan jam selesai tidak boleh sama.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("
            INSERT INTO shift (nama_shift, jam_mulai, jam_selesai)
            VALUES (?, ?, ?)
        ");

        $sukses = false;
        try {
            $stmt->execute([
                $old['nama'],
                $old['mulai'],
                $old['selesai']
            ]);
            $sukses = true;
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23505') {
                $errors[] = 'Nama shift "' . $old['nama'] . '" sudah dipakai, gunakan nama lain.';
            } else {
                $errors[] = 'Terjadi kesalahan saat menyimpan data.';
            }
        }

        if ($sukses) {
            set_flash('sukses', 'Shift "' . $old['nama'] . '" berhasil ditambahkan.');
            header('Location: /manager/jadwal/index.php');
            exit;
        }
    }
}


// Bagian backend AKHIR ======================
//============================================

// Bagian halaman frontend ==================================
include __DIR__ . '/../../includes/Header.php'; ?>

<div style="max-width: 760px">
    <form method="post" action="" class="card form" autocomplete="off">
        <?php if (!empty($errors)): ?>
            <ul class="form-errors">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <section class="fsec">
            <div class="fsec-head">
                <h2>Informasi Shift</h2>
                <p>Nama dan keterangan yang tampil di halaman jadwal.</p>
            </div>
            <div class="fsec-body">
                <div class="field">
                    <label for="nama">Nama Shift</label>
                    <input type="text" id="nama" name="nama" maxlength="20" placeholder="Cth: Sore"
                        value="<?= e($old['nama']) ?>" required autofocus>
                </div>
            </div>
        </section>

        <section class="fsec">
            <div class="fsec-head">
                <h2>Jam Kerja</h2>
                <p>Untuk shift yang melewati tengah malam, jam selesai boleh lebih kecil dari jam mulai (cth. 23:00 –
                    07:00).</p>
            </div>
            <div class="fsec-body">
                <div class="grid2">
                    <div class="field">
                        <label for="mulai">Jam Mulai</label>
                        <input type="time" id="mulai" name="mulai" value="<?= e($old['mulai']) ?>" required>
                    </div>
                    <div class="field">
                        <label for="selesai">Jam Selesai</label>
                        <input type="time" id="selesai" name="selesai" value="<?= e($old['selesai']) ?>" required>
                    </div>
                </div>
                <p class="hint" id="durasi" aria-live="polite"></p>
            </div>
        </section>

        <div class="form-actions">
            <a class="btn-ghost" href="index.php">Batal</a>
            <button type="submit" class="btn">Simpan Shift</button>
        </div>
    </form>
</div>

<script>
    (function () {
        var mulai = document.getElementById('mulai'), selesai = document.getElementById('selesai'), el = document.getElementById('durasi');
        function hitung() {
            var m = mulai.value, s = selesai.value;
            if (!m || !s || m === s) { el.textContent = ''; return; }
            var a = m.split(':'), b = s.split(':');
            var menit = (+b[0] * 60 + +b[1]) - (+a[0] * 60 + +a[1]);
            if (menit <= 0) menit += 1440;
            var j = Math.floor(menit / 60), r = menit % 60;
            el.textContent = 'Durasi: ' + j + ' jam' + (r ? ' ' + r + ' menit' : '') + (s < m ? ' (melewati tengah malam)' : '');
        }
        mulai.addEventListener('input', hitung);
        selesai.addEventListener('input', hitung);
        hitung();
    })();
</script>

<?php include __DIR__ . '/../../includes/Footer.php'; ?>