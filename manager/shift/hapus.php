<?php
// Bagian backend AWAL =======================
require_once __DIR__ . '/../../includes/Functions.php';
require_once __DIR__ . '/../../config/database.php';
// require_once __DIR__ . '/../../includes/Auth.php';
// cek_role('manager');   // aktifkan setelah login selesai

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    set_flash('error', 'ID shift tidak valid.');
    header('Location: ../jadwal/index.php');
    exit;
}

$errors = [];

$stmt = $pdo->prepare("
    SELECT id, nama_shift AS nama, jam_mulai AS mulai, jam_selesai AS selesai 
    FROM shift 
    WHERE id = ?
");
$stmt->execute([$id]);
$shift = $stmt->fetch();

if (!$shift) {
    set_flash('error', 'Shift tidak ditemukan.');
    header('Location: ../jadwal/index.php');
    exit;
}

$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM jadwal WHERE shift_id = ?");
$stmtCheck->execute([$id]);
$dipakai = (int)$stmtCheck->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmtDelete = $pdo->prepare("DELETE FROM shift WHERE id = ?");
        $stmtDelete->execute([$id]);

        set_flash('sukses', 'Shift "' . $shift['nama'] . '" berhasil dihapus.');
        header('Location: ../jadwal/index.php');
        exit;
    } catch (PDOException $ex) {
        if ($ex->getCode() === '23503') {
            $errors[] = 'Gagal menghapus shift: Shift ini masih digunakan pada data jadwal. Kosongkan atau ganti jadwal terkait terlebih dahulu.';
        } else {
            $errors[] = 'Gagal menghapus shift: ' . $ex->getMessage();
        }
    }
}

$pageTitle    = 'Hapus Shift';
$pageSubtitle = 'Konfirmasi penghapusan shift';
$activeMenu   = 'jadwal';
// Bagian backend AKHIR ======================
//============================================

// Bagian halaman frontend ==================================
include __DIR__ . '/../../includes/Header.php'; ?>

<div style="max-width: 760px">
    <form method="post" action="" class="card form">
        <input type="hidden" name="id" value="<?= e($shift['id']) ?>">

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
                <article class="shift-item s-<?= e($shift['id']) ?>">
                    <div class="top">
                        <b><?= e('Shift ' . $shift['nama']) ?></b>
                        <span><?= e($shift['mulai']) ?> - <?= e($shift['selesai']) ?></span>
                    </div>
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
            <a class="btn-ghost" href="../jadwal/index.php">Batal</a>
            <button type="submit" class="btn-delete">Ya, Hapus</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/Footer.php'; ?>