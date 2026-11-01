<?php
// Bagian backend AWAL =======================
require_once __DIR__ . '/../../includes/Functions.php';
require_once __DIR__ . '/../../config/database.php';
// require_once __DIR__ . '/../../includes/Auth.php';
// cek_role('manager');   // aktifkan setelah login selesai

/* =====================================================================
 * KONTRAK DATA (untuk backend)
 * URL: edit.php?kode=pagi   (kode shift, tidak boleh berubah saat edit)
 * FORM (POST ke halaman ini): nama, ket, mulai (HH:MM), selesai (HH:MM)
 *
 *  $kode    kode shift yang sedang diedit.
 *  $old     ['nama'=>, 'ket'=>, 'mulai'=>, 'selesai'=>]  data shift dari database
 *           (atau isian terakhir saat validasi gagal).
 *  $errors  array of string. Isi jika validasi gagal.
 *
 * Jika kode tidak ditemukan: set_flash('error', 'Shift tidak ditemukan.'); header('Location: index.php'); exit;
 * Setelah sukses simpan:     set_flash('sukses', '...'); header('Location: index.php'); exit;
 * ===================================================================== */
$kode   = (string)($_GET['kode'] ?? '');
$errors = [];
$old    = [   // DUMMY
    'nama' => 'Pagi', 'ket' => 'Shift operasional dapur & kasir pagi',
    'mulai' => '07:00', 'selesai' => '15:00',
];

$pageTitle    = 'Edit Shift';
$pageSubtitle = 'Ubah konfigurasi shift ' . $old['nama'];
$activeMenu   = 'jadwal';
// Bagian backend AKHIR ======================
//============================================

// Bagian halaman frontend ==================================
include __DIR__ . '/../../includes/Header.php'; ?>

<div style="max-width: 760px">
    <form method="post" action="" class="card form" autocomplete="off">
        <?php if (!empty($errors)): ?>
            <ul class="form-errors">
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <section class="fsec">
            <div class="fsec-head">
                <h2>Informasi Shift</h2>
                <p>Kode internal: <b><?= e($kode) ?></b>. Kode tidak berubah karena dipakai oleh data jadwal.</p>
            </div>
            <div class="fsec-body">
                <div class="field">
                    <label for="nama">Nama Shift</label>
                    <input type="text" id="nama" name="nama" maxlength="20" value="<?= e($old['nama']) ?>" required autofocus>
                </div>
                <div class="field">
                    <label for="ket">Keterangan <span>(opsional)</span></label>
                    <input type="text" id="ket" name="ket" maxlength="80" value="<?= e($old['ket']) ?>">
                </div>
            </div>
        </section>

        <section class="fsec">
            <div class="fsec-head">
                <h2>Jam Kerja</h2>
                <p>Untuk shift yang melewati tengah malam, jam selesai boleh lebih kecil dari jam mulai (cth. 23:00 – 07:00).</p>
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
            <button type="submit" class="btn">Simpan Perubahan</button>
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