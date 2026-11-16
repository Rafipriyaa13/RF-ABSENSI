<?php
// Menyimpan pengajuan izin / sakit / cuti (dari form di pegawai/profil.php)
require_once __DIR__ . '/../../includes/Functions.php';
require_once __DIR__ . '/../../config/database.php';
// require_once __DIR__ . '/../../includes/Auth.php';
// cek_role('pegawai');   // aktifkan setelah login selesai

date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pegawai/profil.php');
    exit;
}

// PEGAWAI SEMENTARA: ganti dengan $_SESSION['user_id'] setelah login selesai
$idPegawai = (int)($_SESSION['user_id'] ?? 1);

function kembali($tipe, $pesan, $old = null)
{
    set_flash($tipe, $pesan);
    if ($old !== null) {
        $_SESSION['old_izin'] = $old;   // isian dikembalikan ke form
    }
    header('Location: /pegawai/profil.php');
    exit;
}

function tanggal_valid($t)
{
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $t);
    return $d && $d->format('Y-m-d') === $t;
}

$jenis   = $_POST['jenis'] ?? '';
$mulai   = $_POST['tanggal_mulai'] ?? '';
$selesai = $_POST['tanggal_selesai'] ?? '';
$alasan  = trim($_POST['alasan'] ?? '');
$old     = ['jenis' => $jenis, 'tanggal_mulai' => $mulai, 'tanggal_selesai' => $selesai, 'alasan' => $alasan];

// ---- Validasi ----
$errors = [];
if (!in_array($jenis, ['izin', 'sakit', 'cuti'], true)) {
    $errors[] = 'Tipe pengajuan tidak valid.';
}
if (!tanggal_valid($mulai) || !tanggal_valid($selesai)) {
    $errors[] = 'Tanggal mulai dan selesai wajib diisi dengan benar.';
} elseif ($selesai < $mulai) {
    $errors[] = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
}
if ($alasan === '') {
    $errors[] = 'Alasan wajib diisi.';
} elseif (mb_strlen($alasan) > 500) {
    $errors[] = 'Alasan maksimal 500 karakter.';
}
if ($errors) {
    kembali('error', implode(' ', $errors), $old);
}

// Tidak boleh bertabrakan dengan pengajuan lain yang masih menunggu / sudah disetujui
$st = $pdo->prepare("
    SELECT 1 FROM izin_cuti
    WHERE user_id = ? AND status_approval IN ('menunggu', 'disetujui')
      AND tanggal_mulai <= ? AND tanggal_selesai >= ?
    LIMIT 1
");
$st->execute([$idPegawai, $selesai, $mulai]);
if ($st->fetchColumn()) {
    kembali('error', 'Sudah ada pengajuan lain yang menunggu atau disetujui pada rentang tanggal tersebut.', $old);
}

// ---- Simpan (status awal: menunggu) ----
try {
    $st = $pdo->prepare("
        INSERT INTO izin_cuti (user_id, jenis, tanggal_mulai, tanggal_selesai, alasan)
        VALUES (?, ?, ?, ?, ?)
    ");
    $st->execute([$idPegawai, $jenis, $mulai, $selesai, $alasan]);
} catch (PDOException $ex) {
    if ($ex->getCode() === '42703') {   // kolom tidak ada
        kembali('error', 'Kolom alasan belum ada di database. Jalankan database/migrasi_alasan_izin_cuti.sql terlebih dahulu.', $old);
    }
    error_log('simpan izin_cuti gagal [' . $ex->getCode() . ']: ' . $ex->getMessage());
    kembali('error', 'Terjadi kesalahan saat menyimpan pengajuan.', $old);
}

kembali('sukses', 'Pengajuan ' . $jenis . ' berhasil dikirim dan menunggu persetujuan manager.');
