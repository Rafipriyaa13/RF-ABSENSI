<?php
// Bagian backend AWAL =======================
require_once __DIR__ . '/../includes/Functions.php';
require_once __DIR__ . '/../config/database.php';
// require_once __DIR__ . '/../includes/Auth.php';
// cek_role('pegawai');   // aktifkan setelah login selesai

date_default_timezone_set('Asia/Jakarta');

/* =====================================================================
 * DATA YANG DIPAKAI HALAMAN
 *  $pegawai      ['nama'=>, 'jabatan'=>]
 *  $shiftHariIni ['nama_shift'=>, 'jam_mulai'=>, 'jam_selesai'=>, 'status'=>'terjadwal'|'libur'] atau null
 *  $absenHariIni ['check_in'=>, 'check_out'=>, 'status_kehadiran'=>'tepat_waktu'|'terlambat'] atau null
 *  $izinHariIni  'izin'|'sakit'|'cuti' atau null (pengajuan yang sudah disetujui)
 *  $rekap        ['hadir'=>, 'terlambat'=>, 'izin_sakit'=>, 'alpha'=>]  (bulan berjalan)
 *  $mingguan     7 hari (Senin-Minggu) beserta shift-nya
 * ===================================================================== */

$HARI  = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$BULAN = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

// PEGAWAI SEMENTARA: ganti dengan $_SESSION['user_id'] setelah login selesai
$idPegawai = (int)($_SESSION['user_id'] ?? 1);

$hariIni   = new DateTimeImmutable('today');
$tglHariIni = $hariIni->format('Y-m-d');
$awalBulan  = $hariIni->modify('first day of this month')->format('Y-m-d');
$akhirBulan = $hariIni->modify('last day of this month')->format('Y-m-d');
$senin      = $hariIni->modify('monday this week');
$minggu     = $senin->modify('+6 days');

// 1) Data pegawai
$st = $pdo->prepare("SELECT nama, jabatan FROM users WHERE id = ?");
$st->execute([$idPegawai]);
$pegawai = $st->fetch() ?: ['nama' => 'Pegawai', 'jabatan' => null];

// 2) Shift hari ini
$st = $pdo->prepare("
    SELECT j.id, j.status, s.nama_shift, s.jam_mulai, s.jam_selesai
    FROM jadwal j
    LEFT JOIN shift s ON s.id = j.shift_id
    WHERE j.user_id = ? AND j.tanggal = ?
");
$st->execute([$idPegawai, $tglHariIni]);
$shiftHariIni = $st->fetch() ?: null;

// 3) Absensi hari ini
$absenHariIni = null;
if ($shiftHariIni) {
    $st = $pdo->prepare("SELECT check_in, check_out, status_kehadiran FROM absensi WHERE jadwal_id = ?");
    $st->execute([$shiftHariIni['id']]);
    $absenHariIni = $st->fetch() ?: null;
}

// 4) Izin / sakit / cuti yang sudah disetujui dan mencakup hari ini
$st = $pdo->prepare("
    SELECT jenis FROM izin_cuti
    WHERE user_id = ? AND status_approval = 'disetujui'
      AND ? BETWEEN tanggal_mulai AND tanggal_selesai
    LIMIT 1
");
$st->execute([$idPegawai, $tglHariIni]);
$izinHariIni = $st->fetchColumn() ?: null;

// 5) Akumulasi bulan ini (hanya hari yang dijadwalkan masuk)
//    hadir      = absen tepat waktu
//    terlambat  = absen terlambat
//    izin_sakit = tidak absen, tapi ada izin/sakit yang disetujui
//    alpha      = hari sebelum hari ini, tidak absen, dan tidak ada izin/sakit/cuti yang disetujui
$st = $pdo->prepare("
    SELECT
        COUNT(*) FILTER (WHERE a.status_kehadiran = 'tepat_waktu') AS hadir,
        COUNT(*) FILTER (WHERE a.status_kehadiran = 'terlambat')   AS terlambat,
        COUNT(*) FILTER (WHERE a.id IS NULL AND ic.jenis IN ('izin', 'sakit')) AS izin_sakit,
        COUNT(*) FILTER (WHERE a.id IS NULL AND ic.jenis IS NULL AND j.tanggal < ?) AS alpha
    FROM jadwal j
    LEFT JOIN absensi a ON a.jadwal_id = j.id
    LEFT JOIN LATERAL (
        SELECT i.jenis FROM izin_cuti i
        WHERE i.user_id = j.user_id AND i.status_approval = 'disetujui'
          AND j.tanggal BETWEEN i.tanggal_mulai AND i.tanggal_selesai
        LIMIT 1
    ) ic ON TRUE
    WHERE j.user_id = ? AND j.status = 'terjadwal' AND j.tanggal BETWEEN ? AND ?
");
$st->execute([$tglHariIni, $idPegawai, $awalBulan, $akhirBulan]);
$rekap = $st->fetch() ?: [];
$rekap = [
    'hadir'      => (int)($rekap['hadir'] ?? 0),
    'terlambat'  => (int)($rekap['terlambat'] ?? 0),
    'izin_sakit' => (int)($rekap['izin_sakit'] ?? 0),
    'alpha'      => (int)($rekap['alpha'] ?? 0),
];

// 6) Jadwal minggu ini
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
$mingguan = [];
for ($i = 0; $i < 7; $i++) {
    $d = $senin->modify("+$i days");
    $k = $d->format('Y-m-d');
    $mingguan[] = ['hari' => $HARI[$i], 'tanggal' => $k, 'hariIni' => $k === $tglHariIni, 'jadwal' => $perTanggal[$k] ?? null];
}

/* ------------------ Turunan untuk tampilan ------------------ */
// Terima 'HH:MM:SS' (kolom TIME) atau 'YYYY-MM-DD HH:MM:SS' (kolom TIMESTAMP) -> 'HH:MM'
function jam($waktu)
{
    if (!$waktu) return '';
    return strlen($waktu) > 8 ? substr($waktu, 11, 5) : substr($waktu, 0, 5);
}
function nama_shift_lengkap($nama)
{
    if (!$nama) return '';
    return stripos($nama, 'shift') === 0 ? $nama : 'Shift ' . $nama;
}

$jamMulai   = $shiftHariIni['jam_mulai'] ?? null;
$jamSelesai = $shiftHariIni['jam_selesai'] ?? null;
$libur      = $shiftHariIni && $shiftHariIni['status'] === 'libur';

// Kartu "Shift Hari Ini"
if ($shiftHariIni && !$libur) {
    $shiftJudul = nama_shift_lengkap($shiftHariIni['nama_shift']);
    $shiftWaktu = 'Waktu: ' . jam($jamMulai) . ' - ' . jam($jamSelesai);
    if (!$absenHariIni) {
        $shiftNote = 'Status Absensi: Belum absen masuk';
    } elseif (empty($absenHariIni['check_out'])) {
        $shiftNote = 'Status Absensi: Belum absen keluar';
    } else {
        $shiftNote = 'Status Absensi: Sudah absen keluar ' . jam($absenHariIni['check_out']);
    }
} elseif ($libur) {
    $shiftJudul = 'Libur';
    $shiftWaktu = 'Tidak ada shift hari ini.';
    $shiftNote  = '';
} else {
    $shiftJudul = 'Belum dijadwalkan';
    $shiftWaktu = 'Hubungi manager untuk jadwal Anda.';
    $shiftNote  = '';
}

// Kartu "Status Absen Masuk"  ($statusKelas: st-ok | st-late | st-off)
if ($absenHariIni && !empty($absenHariIni['check_in'])) {
    $jamMasuk = jam($absenHariIni['check_in']);
    if ($absenHariIni['status_kehadiran'] === 'terlambat') {
        $menit = 0;
        if ($jamMulai) {
            $batas = new DateTimeImmutable($tglHariIni . ' ' . $jamMulai);
            $masuk = new DateTimeImmutable($absenHariIni['check_in']);
            $menit = max(0, intdiv($masuk->getTimestamp() - $batas->getTimestamp(), 60));
        }
        $statusJudul = $jamMasuk . ' (Terlambat)';
        $statusKelas = 'st-late';
        $statusNote  = $menit > 0 ? "Masuk $menit menit setelah jam mulai shift." : 'Masuk melewati jam mulai shift.';
    } else {
        $statusJudul = $jamMasuk . ' (Tepat Waktu)';
        $statusKelas = 'st-ok';
        $statusNote  = 'Masuk tepat waktu.';
    }
} elseif ($izinHariIni) {
    $statusJudul = ucfirst($izinHariIni);
    $statusKelas = 'st-off';
    $statusNote  = 'Pengajuan Anda untuk hari ini sudah disetujui.';
} elseif ($libur) {
    $statusJudul = 'Libur';
    $statusKelas = 'st-off';
    $statusNote  = 'Tidak ada kewajiban absen hari ini.';
} elseif ($shiftHariIni) {
    $statusJudul = 'Belum absen masuk';
    $statusKelas = 'st-off';
    $statusNote  = 'Silakan absen melalui menu Absensi Pegawai.';
} else {
    $statusJudul = 'Tidak ada jadwal';
    $statusKelas = 'st-off';
    $statusNote  = 'Belum ada shift untuk hari ini.';
}

$pageTitle    = 'Dashboard Karyawan';
$pageSubtitle = 'Selamat datang kembali, ' . $pegawai['nama'];
$activeMenu   = 'dashboard';
$sidebarRole  = 'pegawai';
$topDate      = 'Hari ini: ' . $hariIni->format('j') . ' ' . $BULAN[(int)$hariIni->format('n')] . ' ' . $hariIni->format('Y');
$namaLogin    = $pegawai['nama'];

$st = $pdo->prepare("
SELECT i.jenis, i.tanggal_mulai as mulai, i.tanggal_selesai as selesai, i.status_approval as status, i.alasan, i.alasan_penolakan as catatan
FROM izin_cuti i
WHERE i.user_id = ?
");
$st->execute([$idPegawai]);
$pengajuan = $st->fetchAll() ?: null;

// Bagian backend AKHIR ======================
//============================================

// Ikon kecil (isi tag <svg>)
$ikon = [
    'cal'  => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    'note' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>',
    'clip' => '<path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>',
    'warn' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/>',
    'ok'   => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/>',
];
$svg = fn($k) => '<svg class="pj-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $ikon[$k] . '</svg>';

include __DIR__ . '/../includes/Header.php';
?>

<section class="dash-grid">
    <!-- Shift hari ini -->
    <article class="card dcard">
        <div class="eyebrow">Shift hari ini</div>
        <div class="big"><?= e($shiftJudul) ?></div>
        <p class="sub"><?= e($shiftWaktu) ?></p>
        <?php if ($shiftNote !== ''): ?>
            <p class="sub faint"><?= e($shiftNote) ?></p>
        <?php endif; ?>
    </article>

    <!-- Status absen masuk -->
    <article class="card dcard">
        <div class="eyebrow">Status absen masuk</div>
        <div class="big status-line">
            <span class="status-dot <?= e($statusKelas) ?>"></span>
            <?= e($statusJudul) ?>
        </div>
        <p class="sub"><?= e($statusNote) ?></p>
    </article>

    <!-- Kolom kiri: Akumulasi + Status pengajuan -->
    <div class="dcol">
        <!-- Akumulasi bulan ini -->
        <article class="card dcard">
            <h2>Akumulasi Bulan Ini</h2>
            <div class="acc-grid">
                <div class="acc"><small>Hadir</small><b><?= $rekap['hadir'] ?> kali</b></div>
                <div class="acc"><small>Terlambat</small><b><?= $rekap['terlambat'] ?> kali</b></div>
                <div class="acc"><small>Izin / Sakit</small><b><?= $rekap['izin_sakit'] ?> kali</b></div>
                <div class="acc"><small>Alpha</small><b><?= $rekap['alpha'] ?> kali</b></div>
            </div>
        </article>

        <!-- Status pengajuan izin / cuti -->
        <article class="card dcard">
            <div class="pj-head">
                <h2>Status Pengajuan Izin / Cuti</h2>
                <a class="pj-ajukan" href="/pegawai/izin_cuti/ajukan.php">Ajukan</a>
            </div>

            <div class="pj-list">
                <?php foreach ($pengajuan as $p):
                    $a = new DateTimeImmutable($p['mulai']);
                    $b = new DateTimeImmutable($p['selesai']);
                    $durasi = $a->diff($b)->days + 1;
                    $fmt = fn($d) => $d->format('j') . ' ' . $BULAN[(int)$d->format('n')];
                    $periode = $durasi > 1
                        ? $fmt($a) . ' - ' . $fmt($b) . ' ' . $b->format('Y')
                        : $fmt($a) . ' ' . $a->format('Y');
                ?>
                    <section class="pj-item">
                        <div class="pj-top">
                            <b><?= e($p['jenis']) ?></b>
                            <span class="pj-badge pj-<?= e($p['status']) ?>"><?= e(strtoupper($p['status'])) ?></span>
                        </div>

                        <p class="pj-row"><?= $svg('cal') ?><span><?= e($periode) ?> (<?= $durasi ?> hari)</span></p>
                        <p class="pj-row"><?= $svg('note') ?><span>Alasan: <?= e($p['alasan']) ?></span></p>

                         <!-- lampiran masih belum terimplementasi -->
                        <!-- <?php if ($p['lampiran']): ?>
                            <?php /* TODO backend: ganti href dengan path file asli, mis. /uploads/izin_cuti/<nama file> */ ?>
                            <p class="pj-row"><?= $svg('clip') ?>
                                <a href="#" target="_blank" rel="noopener">Lihat Lampiran (<?= e($p['lampiran']) ?>)</a>
                            </p>
                        <?php endif; ?> -->

                        <?php if ($p['catatan']): ?>
                            <div class="pj-note pj-note-<?= e($p['status']) ?>">
                                <?= $svg($p['status'] === 'ditolak' ? 'warn' : 'ok') ?>
                                <div>
                                    <b><?= $p['status'] === 'ditolak' ? 'Alasan Penolakan:' : 'Catatan Manager:' ?></b>
                                    <p><?= e($p['catatan']) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>

                <?php if (!$pengajuan): ?>
                    <p class="pj-empty">Belum ada pengajuan izin atau cuti.</p>
                <?php endif; ?>
            </div>
        </article>
    </div>

    <!-- Jadwal shift mingguan -->
    <article class="card dcard dcard-fill">
        <h2>Jadwal Shift Mingguan</h2>
        <ul class="week-list">
            <?php foreach ($mingguan as $h): ?>
                <?php
                $j = $h['jadwal'];
                if ($j && $j['status'] === 'libur') {
                    $teks = 'Libur (Off)';
                } elseif ($j) {
                    $teks = $j['nama_shift'] . ' (' . jam($j['jam_mulai']) . ' - ' . jam($j['jam_selesai']) . ')';
                } else {
                    $teks = 'Belum dijadwalkan';
                }
                ?>
                <li class="week-row<?= $h['hariIni'] ? ' today' : '' ?>">
                    <b><?= e($h['hari']) ?></b>
                    <span><?= e($teks) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
</section>

<?php include __DIR__ . '/../includes/Footer.php'; ?>