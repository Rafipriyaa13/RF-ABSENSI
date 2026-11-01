<?php
// Bagian backend AWAL =======================
require_once __DIR__ . '/../../includes/Functions.php';
require_once __DIR__ . '/../../config/database.php';
// require_once __DIR__ . '/../../includes/Auth.php';
// cek_role('manager');   // aktifkan setelah login selesai

/* =====================================================================
 * KONTRAK DATA (untuk backend)
 * URL: hapus.php?kode=pagi
 * FORM (POST ke halaman ini): kode
 *
 *  $kode     kode shift yang akan dihapus.
 *  $shift    ['kode'=>, 'nama'=>, 'mulai'=>'HH:MM', 'selesai'=>'HH:MM', 'ket'=>]  data shift.
 *  $dipakai  int, jumlah sel jadwal yang masih memakai shift ini (0 = tidak dipakai).
 *            Aturan jika > 0 (ditolak, atau jadwal dikosongkan) ditentukan backend.
 *  $errors   array of string. Isi jika penghapusan ditolak.
 *
 * Jika kode tidak ditemukan: set_flash('error', 'Shift tidak ditemukan.'); header('Location: index.php'); exit;
 * Setelah sukses hapus:      set_flash('sukses', '...'); header('Location: index.php'); exit;
 * ===================================================================== */
$kode   = (string)($_GET['kode'] ?? '');
$errors = [];
$dipakai = 3;   // DUMMY
$shift  = [     // DUMMY
    'kode' => $kode ?: 'pagi', 'nama' => 'Pagi', 'mulai' => '07:00', 'selesai' => '15:00',
    'ket' => 'Shift operasional dapur & kasir pagi',
];

$pageTitle    = 'Hapus Shift';
$pageSubtitle = 'Konfirmasi penghapusan shift';
$activeMenu   = 'jadwal';
// Bagian backend AKHIR ======================
//============================================

// Bagian halaman frontend ==================================
include __DIR__ . '/../../includes/Header.php'; ?>

<div style="max-width: 760px">
    <form method="post" action="" class="card form">
        <input type="hidden" name="kode" value="<?= e($shift['kode']) ?>">

        <?php if (!empty($errors)): ?>
            <ul class="form-errors">
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <section class="fsec">
            <div class="fsec-head">
                <h2>Hapus Shift</h2>
                <p>Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="fsec-body">
                <article class="shift-item s-<?= e($shift['kode']) ?>">
                    <div class="top"><b><?= e('Shift ' . $shift['nama']) ?></b><span><?= e($shift['mulai']) ?> - <?= e($shift['selesai']) ?></span></div>
                    <?php if ($shift['ket'] !== ''): ?><p><?= e($shift['ket']) ?></p><?php endif; ?>
                </article>

                <?php if ($dipakai > 0): ?>
                    <div class="alert alert-error">
                        Shift ini masih dipakai di <b><?= (int)$dipakai ?></b> sel jadwal. Menghapusnya dapat memengaruhi jadwal tersebut.
                    </div>
                <?php endif; ?>

                <p>Yakin ingin menghapus <b>Shift <?= e($shift['nama']) ?></b>?</p>
            </div>
        </section>

        <div class="form-actions">
            <a class="btn-ghost" href="index.php">Batal</a>
            <button type="submit" class="btn-delete">Ya, Hapus</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/Footer.php'; ?>