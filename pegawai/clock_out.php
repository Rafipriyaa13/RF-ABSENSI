<?php
// Proses Clock Out pegawai (dipanggil dari form di pegawai/absensi.php)
require_once __DIR__ . '/../includes/Functions.php';
require_once __DIR__ . '/../config/database.php';
// require_once __DIR__ . '/../includes/Auth.php';
// cek_role('pegawai');   // aktifkan setelah login selesai

date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pegawai/absensi.php');
    exit;
}

// PEGAWAI SEMENTARA: ganti dengan $_SESSION['user_id'] setelah login selesai
$idPegawai = (int)($_SESSION['user_id'] ?? 1);

$sekarang = new DateTimeImmutable('now');

function selesai($tipe, $pesan)
{
    set_flash($tipe, $pesan);
    header('Location: /pegawai/absensi.php');
    exit;
}

// Absen masuk yang belum ditutup (maks. mulai kemarin, agar shift malam yang lewat tengah malam tetap terbaca)
$st = $pdo->prepare("
    SELECT a.id, j.tanggal, s.jam_mulai, s.jam_selesai
    FROM absensi a
    JOIN jadwal j ON j.id = a.jadwal_id
    JOIN shift  s ON s.id = j.shift_id
    WHERE a.user_id = ? AND a.check_in IS NOT NULL AND a.check_out IS NULL AND j.tanggal >= ?
    ORDER BY a.check_in DESC
    LIMIT 1
");
$st->execute([$idPegawai, $sekarang->modify('-1 day')->format('Y-m-d')]);
$absen = $st->fetch();

if (!$absen) {
    selesai('error', 'Belum ada absen masuk yang bisa diselesaikan.');
}

[, $selesaiShift] = jendela_shift($absen['tanggal'], $absen['jam_mulai'], $absen['jam_selesai']);
if ($sekarang < $selesaiShift) {
    selesai('error', 'Belum waktunya pulang. Shift Anda berakhir pukul ' . $selesaiShift->format('H:i') . '.');
}

$st = $pdo->prepare("UPDATE absensi SET check_out = ? WHERE id = ? AND check_out IS NULL");
$st->execute([$sekarang->format('Y-m-d H:i:s'), $absen['id']]);

selesai('sukses', 'Absen keluar tercatat pukul ' . $sekarang->format('H:i') . '. Terima kasih atas kerja Anda hari ini.');
