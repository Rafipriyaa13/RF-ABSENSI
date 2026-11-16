<?php
// Bagian backend AWAL =======================
require_once __DIR__ . '/../includes/Functions.php';
require_once __DIR__ . '/../config/database.php';
// require_once __DIR__ . '/../includes/Auth.php';
// cek_role('pegawai');   // aktifkan setelah login selesai

date_default_timezone_set('Asia/Jakarta');

/* =====================================================================
 * DATA YANG DIPAKAI HALAMAN
 *  $hari   7 hari (Senin-Minggu): nama hari, tanggal, status, shift, jam
 *  Halaman ini hanya menampilkan jadwal minggu berjalan (view only).
 * ===================================================================== */

$NAMA_HARI   = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$BULAN_PENDEK = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$BULAN_PENUH  = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// Terima 'HH:MM:SS' -> 'HH:MM'
function jam($waktu)
{
    return $waktu ? substr($waktu, 0, 5) : '';
}

// PEGAWAI SEMENTARA: ganti dengan $_SESSION['user_id'] setelah login selesai
$idPegawai = (int)($_SESSION['user_id'] ?? 1);

$hariIni = new DateTimeImmutable('today');
$tglHariIni = $hariIni->format('Y-m-d');
$senin  = $hariIni->modify('monday this week');
$minggu = $senin->modify('+6 days');

// Nama pegawai (untuk sidebar)
$st = $pdo->prepare("SELECT nama FROM users WHERE id = ?");
$st->execute([$idPegawai]);
$namaPegawai = $st->fetchColumn() ?: 'Pegawai';

// Jadwal minggu ini
$st = $pdo->prepare("
    SELECT j.tanggal, j.status, s.nama_shift, s.jam_mulai, s.jam_selesai
    FROM jadwal j
    LEFT JOIN shift s ON s.id = j.shift_id
    WHERE j.user_id = ? AND j.tanggal BETWEEN ? AND ?
");
$st->execute([$idPegawai, $senin->format('Y-m-d'), $minggu->format('Y-m-d')]);
$perTanggal = [];
foreach ($st->fetchAll() as $r) {
    $perTanggal[substr($r['tanggal'], 0, 10)] = $r;
}

$hari = [];
for ($i = 0; $i < 7; $i++) {
    $d = $senin->modify("+$i days");
    $k = $d->format('Y-m-d');
    $j = $perTanggal[$k] ?? null;

    if ($j && $j['status'] === 'libur') {
        $jenis = 'libur';
    } elseif ($j && $j['jam_mulai']) {
        $jenis = 'kerja';
    } else {
        $jenis = 'kosong';
    }

    $hari[] = [
        'nama'    => $NAMA_HARI[$i],
        'tgl'     => $d->format('j') . ' ' . $BULAN_PENDEK[(int)$d->format('n')],
        'hariIni' => $k === $tglHariIni,
        'jenis'   => $jenis,
        'shift'   => $jenis === 'kerja' ? 'Shift ' . preg_replace('/^shift\s+/i', '', $j['nama_shift']) : '',
        'waktu'   => $jenis === 'kerja' ? jam($j['jam_mulai']) . ' - ' . jam($j['jam_selesai']) : '-',
    ];
}

// "23 Oktober - 29 Oktober 2023"
$rentang = $senin->format('j') . ' ' . $BULAN_PENUH[(int)$senin->format('n')]
    . ' - ' . $minggu->format('j') . ' ' . $BULAN_PENUH[(int)$minggu->format('n')] . ' ' . $minggu->format('Y');
if ($senin->format('Y') !== $minggu->format('Y')) {
    $rentang = $senin->format('j') . ' ' . $BULAN_PENUH[(int)$senin->format('n')] . ' ' . $senin->format('Y')
        . ' - ' . $minggu->format('j') . ' ' . $BULAN_PENUH[(int)$minggu->format('n')] . ' ' . $minggu->format('Y');
}

$pageTitle    = 'Jadwal Kerja Anda';
$pageSubtitle = 'Kalender shift penugasan mingguan (View Only)';
$activeMenu   = 'jadwal';
$sidebarRole  = 'pegawai';
$topDate      = 'Hari ini: ' . $hariIni->format('j') . ' ' . $BULAN_PENDEK[(int)$hariIni->format('n')] . ' ' . $hariIni->format('Y');
$namaLogin    = $namaPegawai;
// Bagian backend AKHIR ======================
//============================================

// Bagian halaman frontend ==================================
include __DIR__ . '/../includes/Header.php';
?>

<section class="card panel">
    <h2>Jadwal Minggu Ini: <?= e($rentang) ?></h2>
    <div class="jw-grid">
        <?php foreach ($hari as $h): ?>
            <article class="jw-day<?= $h['jenis'] !== 'kerja' ? ' off' : '' ?><?= $h['hariIni'] ? ' today' : '' ?>">
                <b><?= e($h['nama']) ?></b>
                <span class="tgl"><?= e($h['tgl']) ?><?php if ($h['hariIni']): ?> <em>&middot; Hari ini</em><?php endif; ?></span>

                <?php if ($h['jenis'] === 'kerja'): ?>
                    <span class="shift"><?= e($h['shift']) ?></span>
                    <span class="waktu"><?= e($h['waktu']) ?></span>
                <?php elseif ($h['jenis'] === 'libur'): ?>
                    <span class="shift">LIBUR (Off)</span>
                    <span class="waktu">-</span>
                    <span class="tag">Istirahat Mingguan</span>
                <?php else: ?>
                    <span class="shift">Belum dijadwalkan</span>
                    <span class="waktu">-</span>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/Footer.php'; ?>
