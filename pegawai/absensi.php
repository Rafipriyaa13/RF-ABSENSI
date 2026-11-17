<?php
require_once __DIR__ . '/../includes/Functions.php';
require_once __DIR__ . '/../config/database.php';
// require_once __DIR__ . '/../includes/Auth.php';
// cek_role('pegawai');   // aktifkan setelah login selesai

date_default_timezone_set('Asia/Jakarta');

/* =====================================================================
 * DATA YANG DIPAKAI HALAMAN
 *  $jadwal       jadwal + shift hari ini (atau false)
 *  $absenHariIni absensi untuk jadwal hari ini (atau false)
 *  $absenAktif   absen masuk yang belum clock out (atau false)
 *  $rekap        ['persen'=>, 'hari_kerja'=>, 'tepat'=>, 'terlambat'=>]  (bulan berjalan)
 *  $riwayat      10 absensi terakhir
 *  Tombol: $masukAktif/$masukNote dan $keluarAktif/$keluarNote
 * ===================================================================== */

$BULAN = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

// Terima 'HH:MM:SS' (kolom TIME) atau 'YYYY-MM-DD HH:MM:SS' (kolom TIMESTAMP) -> 'HH:MM'
function jam($waktu)
{
    if (!$waktu) return '';
    return strlen($waktu) > 8 ? substr($waktu, 11, 5) : substr($waktu, 0, 5);
}
// '2026-11-11' -> '11 Nov 2026'
function tgl_singkat($tanggal, $bulan)
{
    $d = new DateTimeImmutable(substr($tanggal, 0, 10));
    return $d->format('j') . ' ' . $bulan[(int)$d->format('n')] . ' ' . $d->format('Y');
}

// PEGAWAI SEMENTARA: ganti dengan $_SESSION['user_id'] setelah login selesai
$idPegawai = (int)($_SESSION['user_id'] ?? 1);

$sekarang   = new DateTimeImmutable('now');
$tglHariIni = $sekarang->format('Y-m-d');
$awalBulan  = $sekarang->modify('first day of this month')->format('Y-m-d');
$akhirBulan = $sekarang->modify('last day of this month')->format('Y-m-d');

$flash = get_flash();

// 1) Data pegawai
$st = $pdo->prepare("SELECT nama FROM users WHERE id = ?");
$st->execute([$idPegawai]);
$namaPegawai = $st->fetchColumn() ?: 'Pegawai';

// 2) Jadwal hari ini
$st = $pdo->prepare("
    SELECT j.id, j.status, s.nama_shift, s.jam_mulai, s.jam_selesai
    FROM jadwal j
    LEFT JOIN shift s ON s.id = j.shift_id
    WHERE j.user_id = ? AND j.tanggal = ?
");
$st->execute([$idPegawai, $tglHariIni]);
$jadwal = $st->fetch();

// 3) Absensi hari ini
$absenHariIni = false;
if ($jadwal) {
    $st = $pdo->prepare("SELECT check_in, check_out, status_kehadiran FROM absensi WHERE jadwal_id = ?");
    $st->execute([$jadwal['id']]);
    $absenHariIni = $st->fetch();
}

// 4) Absen masuk yang belum ditutup (mulai kemarin, agar shift malam tetap terbaca)
$st = $pdo->prepare("
    SELECT a.check_in, j.tanggal, s.jam_mulai, s.jam_selesai
    FROM absensi a
    JOIN jadwal j ON j.id = a.jadwal_id
    JOIN shift  s ON s.id = j.shift_id
    WHERE a.user_id = ? AND a.check_in IS NOT NULL AND a.check_out IS NULL AND j.tanggal >= ?
    ORDER BY a.check_in DESC
    LIMIT 1
");
$st->execute([$idPegawai, $sekarang->modify('-1 day')->format('Y-m-d')]);
$absenAktif = $st->fetch();

// 5) Izin / sakit / cuti yang sudah disetujui dan mencakup hari ini
$st = $pdo->prepare("
    SELECT jenis FROM izin_cuti
    WHERE user_id = ? AND status_approval = 'disetujui'
      AND ? BETWEEN tanggal_mulai AND tanggal_selesai
    LIMIT 1
");
$st->execute([$idPegawai, $tglHariIni]);
$izinHariIni = $st->fetchColumn() ?: null;

// 6) Kehadiran bulan ini
//    hari_kerja = hari terjadwal yang sudah lewat (atau sudah absen), tidak termasuk hari izin/sakit/cuti disetujui
//    persen     = (tepat waktu + terlambat) / hari_kerja
$st = $pdo->prepare("
    SELECT
        COUNT(*) FILTER (WHERE a.status_kehadiran = 'tepat_waktu') AS tepat,
        COUNT(*) FILTER (WHERE a.status_kehadiran = 'terlambat')   AS terlambat,
        COUNT(*) FILTER (WHERE a.id IS NOT NULL OR ic.jenis IS NULL) AS hari_kerja
    FROM jadwal j
    LEFT JOIN absensi a ON a.jadwal_id = j.id
    LEFT JOIN LATERAL (
        SELECT i.jenis FROM izin_cuti i
        WHERE i.user_id = j.user_id AND i.status_approval = 'disetujui'
          AND j.tanggal BETWEEN i.tanggal_mulai AND i.tanggal_selesai
        LIMIT 1
    ) ic ON TRUE
    WHERE j.user_id = ? AND j.status = 'terjadwal' AND j.tanggal BETWEEN ? AND ?
      AND (j.tanggal < ? OR a.id IS NOT NULL)
");
$st->execute([$idPegawai, $awalBulan, $akhirBulan, $tglHariIni]);
$r = $st->fetch() ?: [];
$tepat     = (int)($r['tepat'] ?? 0);
$terlambat = (int)($r['terlambat'] ?? 0);
$hariKerja = (int)($r['hari_kerja'] ?? 0);
$rekap = [
    'tepat'      => $tepat,
    'terlambat'  => $terlambat,
    'hari_kerja' => $hariKerja,
    'persen'     => $hariKerja > 0 ? round(($tepat + $terlambat) / $hariKerja * 100, 1) : 0,
];

// 7) Riwayat kehadiran terakhir
$st = $pdo->prepare("
    SELECT j.tanggal, s.nama_shift, a.check_in, a.check_out, a.status_kehadiran
    FROM absensi a
    JOIN jadwal j ON j.id = a.jadwal_id
    LEFT JOIN shift s ON s.id = j.shift_id
    WHERE a.user_id = ?
    ORDER BY j.tanggal DESC, a.check_in DESC
    LIMIT 10
");
$st->execute([$idPegawai]);
$riwayat = $st->fetchAll();

/* ------------------ Status tombol ------------------ */

// Clock In
$masukAktif = false;
$adaShift   = $jadwal && $jadwal['status'] === 'terjadwal' && $jadwal['jam_mulai'];
if ($absenHariIni && $absenHariIni['check_in']) {
    $masukNote = 'Absen Masuk (' . jam($absenHariIni['check_in']) . ')';
} elseif ($izinHariIni) {
    $masukNote = 'Sedang ' . $izinHariIni . ' (disetujui)';
} elseif ($jadwal && $jadwal['status'] === 'libur') {
    $masukNote = 'Hari ini libur';
} elseif (!$adaShift) {
    $masukNote = 'Tidak ada jadwal hari ini';
} else {
    [, $selesaiShift] = jendela_shift($tglHariIni, $jadwal['jam_mulai'], $jadwal['jam_selesai']);
    if ($sekarang >= $selesaiShift) {
        $masukNote = 'Shift sudah berakhir';
    } else {
        $masukAktif = true;
        $masukNote  = 'Shift ' . jam($jadwal['jam_mulai']) . ' - ' . jam($jadwal['jam_selesai']);
    }
}

// Clock Out
$keluarAktif = false;
$menungguMs  = null;   // waktu (ms) shift selesai, dipakai JS untuk memuat ulang otomatis
if ($absenAktif) {
    [, $selesaiAktif] = jendela_shift($absenAktif['tanggal'], $absenAktif['jam_mulai'], $absenAktif['jam_selesai']);
    if ($sekarang >= $selesaiAktif) {
        $keluarAktif = true;
        $keluarNote  = 'Shift selesai ' . $selesaiAktif->format('H:i');
    } else {
        $keluarNote = 'Belum Waktunya Pulang';
        $menungguMs = $selesaiAktif->getTimestamp() * 1000;
    }
} elseif ($absenHariIni && $absenHariIni['check_out']) {
    $keluarNote = 'Absen Keluar (' . jam($absenHariIni['check_out']) . ')';
} else {
    $keluarNote = 'Belum absen masuk';
}

$nowMs = (int) round(microtime(true) * 1000);

$pageTitle    = 'Sesi Absensi';
$pageSubtitle = 'Lakukan pencatatan masuk dan keluar kerja';
$activeMenu   = 'absensi';
$sidebarRole  = 'pegawai';
$topDate      = 'Hari ini: ' . tgl_singkat($tglHariIni, $BULAN);
$namaLogin    = $namaPegawai;
// Bagian backend AKHIR ======================
//============================================

// Bagian halaman frontend ==================================
include __DIR__ . '/../includes/Header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['tipe']) ?>"><?= e($flash['pesan']) ?></div>
<?php endif; ?>

<section class="abs-grid">
    <!-- Waktu server & tombol absen -->
    <article class="card dcard">
        <h2>Waktu Server Aktif</h2>
        <div class="clock-box">
            <div class="clock-time" id="serverClock"><?= e($sekarang->format('H:i:s')) ?></div>
            <div class="clock-zone">Waktu Indonesia Barat (WIB)</div>
        </div>

        <div class="clock-actions">
            <form method="post" action="/pegawai/clock_in.php">
                <button type="submit" class="clock-btn" <?= $masukAktif ? '' : 'disabled' ?>>
                    <span class="ic">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                    </span>
                    <b>CLOCK IN</b>
                    <small><?= e($masukNote) ?></small>
                </button>
            </form>
            <form method="post" action="/pegawai/clock_out.php">
                <button type="submit" class="clock-btn" <?= $keluarAktif ? '' : 'disabled' ?>>
                    <span class="ic">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h5"/><path d="M15 8l4 4-4 4"/><path d="M19 12H9"/></svg>
                    </span>
                    <b>CLOCK OUT</b>
                    <small><?= e($keluarNote) ?></small>
                </button>
            </form>
        </div>
    </article>

    <!-- Kehadiran bulan ini -->
    <article class="card dcard">
        <h2>Kehadiran Bulan Ini</h2>
        <ul class="kv-list">
            <li><span>Persentase Kehadiran</span><b><?= e(number_format($rekap['persen'], 1, '.', '')) ?>%</b></li>
            <li><span>Jumlah Hari Kerja</span><b><?= $rekap['hari_kerja'] ?> Hari</b></li>
            <li><span>Kehadiran Tepat Waktu</span><b><?= $rekap['tepat'] ?> Hari</b></li>
            <li><span>Akumulasi Keterlambatan</span><b><?= $rekap['terlambat'] ?> Kali</b></li>
        </ul>
    </article>

    <!-- Riwayat kehadiran -->
    <section class="card panel abs-history">
        <h2>Riwayat Kehadiran Terakhir</h2>
        <div class="table-wrap">
            <table class="table cards">
                <thead>
                    <tr><th>Tanggal</th><th>Shift</th><th>Jam Masuk</th><th>Jam Keluar</th><th>Status Kehadiran</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($riwayat as $h):
                        $telat = $h['status_kehadiran'] === 'terlambat';
                    ?>
                        <tr>
                            <td data-label="Tanggal"><?= e(tgl_singkat($h['tanggal'], $BULAN)) ?></td>
                            <td data-label="Shift"><?= e($h['nama_shift'] ? ('Shift ' . preg_replace('/^shift\s+/i', '', $h['nama_shift'])) : '-') ?></td>
                            <td data-label="Jam Masuk"><?= e(jam($h['check_in']) ?: '--:--') ?></td>
                            <td data-label="Jam Keluar"><?= e(jam($h['check_out']) ?: '--:--') ?></td>
                            <td data-label="Status Kehadiran">
                                <span class="badge <?= $telat ? 'terlambat' : 'hadir' ?>"><?= $telat ? 'Terlambat' : 'Hadir' ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$riwayat): ?>
                        <tr><td colspan="5" class="empty">Belum ada riwayat kehadiran.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>

<script>
(function () {
    // Jam mengikuti waktu server (selisih dihitung sekali saat halaman dimuat)
    var offset = <?= $nowMs ?> - Date.now();
    var el = document.getElementById('serverClock');
    var fmt = new Intl.DateTimeFormat('en-GB', {
        timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23'
    });
    function tick() { el.textContent = fmt.format(new Date(Date.now() + offset)); }
    tick();
    setInterval(tick, 1000);

    // Saat jam pulang tiba, muat ulang agar tombol Clock Out aktif
    var selesai = <?= $menungguMs !== null ? $menungguMs : 'null' ?>;
    if (selesai) {
        var ms = selesai - (Date.now() + offset);
        if (ms > 0 && ms < 2147483647) setTimeout(function () { location.reload(); }, ms + 500);
    }
})();
</script>

<?php include __DIR__ . '/../includes/Footer.php'; ?>
