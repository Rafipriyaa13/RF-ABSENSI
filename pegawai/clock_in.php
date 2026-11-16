<?php
// Proses Clock In pegawai (dipanggil dari form di pegawai/absensi.php)
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

$sekarang   = new DateTimeImmutable('now');
$tglHariIni = $sekarang->format('Y-m-d');

function selesai($tipe, $pesan)
{
    set_flash($tipe, $pesan);
    header('Location: /pegawai/absensi.php');
    exit;
}

// Jadwal hari ini
$st = $pdo->prepare("
    SELECT j.id, j.status, s.jam_mulai, s.jam_selesai
    FROM jadwal j
    LEFT JOIN shift s ON s.id = j.shift_id
    WHERE j.user_id = ? AND j.tanggal = ?
");
$st->execute([$idPegawai, $tglHariIni]);
$jadwal = $st->fetch();

if (!$jadwal) {
    selesai('error', 'Anda tidak memiliki jadwal shift hari ini.');
}
if ($jadwal['status'] === 'libur' || !$jadwal['jam_mulai']) {
    selesai('error', 'Hari ini adalah hari libur Anda.');
}

// Izin / sakit / cuti yang sudah disetujui
$st = $pdo->prepare("
    SELECT 1 FROM izin_cuti
    WHERE user_id = ? AND status_approval = 'disetujui'
      AND ? BETWEEN tanggal_mulai AND tanggal_selesai
    LIMIT 1
");
$st->execute([$idPegawai, $tglHariIni]);
if ($st->fetchColumn()) {
    selesai('error', 'Anda sedang izin atau cuti hari ini, tidak perlu absen.');
}

// Sudah absen?
$st = $pdo->prepare("SELECT 1 FROM absensi WHERE jadwal_id = ?");
$st->execute([$jadwal['id']]);
if ($st->fetchColumn()) {
    selesai('error', 'Anda sudah melakukan absen masuk hari ini.');
}

[$mulai, $selesaiShift] = jendela_shift($tglHariIni, $jadwal['jam_mulai'], $jadwal['jam_selesai']);
if ($sekarang >= $selesaiShift) {
    selesai('error', 'Shift Anda hari ini sudah berakhir.');
}

// Terlambat bila masuk setelah jam mulai shift (dibandingkan per menit)
$menitSekarang = $sekarang->setTime((int)$sekarang->format('H'), (int)$sekarang->format('i'), 0);
$status = $menitSekarang > $mulai ? 'terlambat' : 'tepat_waktu';

try {
    $st = $pdo->prepare("
        INSERT INTO absensi (jadwal_id, user_id, check_in, status_kehadiran)
        VALUES (?, ?, ?, ?)
    ");
    $st->execute([$jadwal['id'], $idPegawai, $sekarang->format('Y-m-d H:i:s'), $status]);
} catch (PDOException $ex) {
    // UNIQUE (jadwal_id): klik ganda / dua tab bersamaan
    selesai('error', 'Anda sudah melakukan absen masuk hari ini.');
}

selesai('sukses', $status === 'terlambat'
    ? 'Absen masuk tercatat pukul ' . $sekarang->format('H:i') . ' (terlambat).'
    : 'Absen masuk tercatat pukul ' . $sekarang->format('H:i') . ' (tepat waktu).');
