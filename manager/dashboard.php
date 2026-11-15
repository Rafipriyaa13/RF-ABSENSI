<?php
// manager/dashboard.php
require_once __DIR__ . '/../includes/Functions.php';
require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Jakarta');

$pageTitle    = 'Dashboard';
$pageSubtitle = 'Ringkasan kondisi hari ini';
$activeMenu   = 'dashboard';
$topAction    = null;

// =====================================================================
// 1. SHIFT HARI INI & STATUS PEGAWAI (Sekaligus sumber data Kartu Ringkasan)
// =====================================================================
$sql_shift = "
    SELECT 
        s.id AS shift_id,
        s.nama_shift, 
        TO_CHAR(s.jam_mulai, 'HH24:MI') || ' - ' || TO_CHAR(s.jam_selesai, 'HH24:MI') AS jam,
        u.nama, 
        u.jabatan,
        CASE 
            WHEN a.status_kehadiran = 'tepat_waktu' THEN 'Hadir'
            WHEN a.status_kehadiran = 'terlambat' THEN 'Terlambat'
            WHEN EXISTS (
                SELECT 1 FROM izin_cuti ic 
                WHERE ic.user_id = u.id 
                  AND ic.status_approval = 'disetujui' 
                  AND CURRENT_DATE BETWEEN ic.tanggal_mulai AND ic.tanggal_selesai
            ) THEN 'Izin'
            ELSE 'Belum Absen'
        END AS status,
        TO_CHAR(a.check_in, 'HH24:MI') AS jam_absen
    FROM jadwal j
    INNER JOIN shift s ON j.shift_id = s.id
    INNER JOIN users u ON j.user_id = u.id
    LEFT JOIN absensi a ON a.jadwal_id = j.id
    WHERE j.tanggal = CURRENT_DATE 
      AND j.status = 'terjadwal'
      AND u.status = 'aktif'
    ORDER BY s.jam_mulai ASC, u.nama ASC
";
$raw_shift = $pdo->query($sql_shift)->fetchAll();

// Hitung Kartu Ringkasan & Kelompokkan Shift dalam satu kali loop
$hitung      = ['Hadir' => 0, 'Terlambat' => 0, 'Belum Absen' => 0, 'Izin' => 0];
$dijadwalkan = count($raw_shift);
$shiftHariIni = [];

foreach ($raw_shift as $row) {
    // Akumulasi statistik kartu atas
    if ($row['status'] === 'Hadir') {
        $hitung['Hadir']++;
    } elseif ($row['status'] === 'Terlambat') {
        $hitung['Terlambat']++;
        $hitung['Hadir']++;
    } elseif ($row['status'] === 'Izin') {
        $hitung['Izin']++;
    } else {
        $hitung['Belum Absen']++;
    }

    // Kelompokkan berdasarkan ID Shift
    $sid = $row['shift_id'];
    if (!isset($shiftHariIni[$sid])) {
        $shiftHariIni[$sid] = [
            'judul'   => 'Shift ' . $row['nama_shift'] . ' (' . $row['jam'] . ')',
            'pegawai' => []
        ];
    }
    $shiftHariIni[$sid]['pegawai'][] = [
        'nama'    => $row['nama'],
        'jabatan' => $row['jabatan'],
        'status'  => $row['status'],
        'jam'     => $row['jam_absen']
    ];
}
$totalHadir   = $hitung['Hadir'];
$shiftHariIni = array_values($shiftHariIni);

// =====================================================================
// 2. AKTIVITAS ABSENSI TERBARU
// =====================================================================
$sql_aktivitas = "
    SELECT 
        u.nama, 
        u.jabatan, 
        CASE 
            WHEN a.status_kehadiran = 'tepat_waktu' THEN 'Hadir'
            WHEN a.status_kehadiran = 'terlambat' THEN 'Terlambat'
            ELSE a.status_kehadiran
        END AS status, 
        TO_CHAR(a.check_in, 'HH24:MI') AS jam
    FROM absensi a
    INNER JOIN jadwal j ON a.jadwal_id = j.id
    INNER JOIN users u ON a.user_id = u.id
    WHERE j.tanggal = CURRENT_DATE
    ORDER BY a.check_in DESC
    LIMIT 5
";
$aktivitas = $pdo->query($sql_aktivitas)->fetchAll();

// =====================================================================
// 3. PERLU TINDAKAN
// =====================================================================
$perluTindakan = [];

// A. Izin/Cuti Menunggu
$izin_pending = $pdo->query("
    SELECT COUNT(*) AS jml, STRING_AGG(u.nama, ', ' ORDER BY i.tanggal_mulai ASC) AS detail
    FROM izin_cuti i 
    JOIN users u ON i.user_id = u.id 
    WHERE i.status_approval = 'menunggu'
")->fetch();
if ($izin_pending['jml'] > 0) {
    $perluTindakan[] = [
        'warna'  => 'kuning',
        'jumlah' => $izin_pending['jml'],
        'judul'  => 'Izin / cuti menunggu persetujuan',
        'detail' => $izin_pending['detail'] ?? '-',
        'link'   => 'izin_cuti/index.php?tab=menunggu',
        'tombol' => 'Tinjau'
    ];
}

// B. Kontrak Berakhir <= 30 Hari
$kontrak_habis = $pdo->query("
    SELECT COUNT(*) AS jml, STRING_AGG(nama || ' (' || TO_CHAR(kontrak_selesai, 'DD Mon YYYY') || ')', ', ' ORDER BY kontrak_selesai ASC) AS detail
    FROM users 
    WHERE status = 'aktif' 
      AND kontrak_selesai IS NOT NULL 
      AND kontrak_selesai <= CURRENT_DATE + 30
")->fetch();
if ($kontrak_habis['jml'] > 0) {
    $perluTindakan[] = [
        'warna'  => 'merah',
        'jumlah' => $kontrak_habis['jml'],
        'judul'  => 'Kontrak berakhir dalam 30 hari',
        'detail' => $kontrak_habis['detail'] ?? '-',
        'link'   => 'pegawai/index.php',
        'tombol' => 'Lihat'
    ];
}

// C. Belum Punya RFID (Menangani NULL maupun string kosong '')
$rfid_kosong = $pdo->query("
    SELECT COUNT(*) AS jml, STRING_AGG(nama, ', ' ORDER BY nama ASC) AS detail
    FROM users 
    WHERE status = 'aktif' 
      AND NULLIF(TRIM(rfid_uid), '') IS NULL
")->fetch();
if ($rfid_kosong['jml'] > 0) {
    $perluTindakan[] = [
        'warna'  => 'biru',
        'jumlah' => $rfid_kosong['jml'],
        'judul'  => 'Pegawai belum punya kartu RFID',
        'detail' => $rfid_kosong['detail'] ?? '-',
        'link'   => 'pegawai/index.php',
        'tombol' => 'Daftarkan'
    ];
}

$hariNama = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$tanggalHariIni = $hariNama[(int) date('w')] . ', ' . tgl_indo(date('Y-m-d'));

include __DIR__ . '/../includes/Header.php';
?>

<!-- 1. Ringkasan hari ini -->
<section class="stats">
    <article class="card stat">
        <span class="stat-label">Hadir</span>
        <p class="stat-value"><?= $totalHadir ?> <small>dari <?= $dijadwalkan ?> dijadwalkan</small></p>
    </article>
    <article class="card stat">
        <span class="stat-label">Terlambat</span>
        <p class="stat-value"><?= $hitung['Terlambat'] ?> <small>Orang</small></p>
    </article>
    <article class="card stat">
        <span class="stat-label">Belum Absen</span>
        <p class="stat-value"><?= $hitung['Belum Absen'] ?> <small>Orang</small></p>
    </article>
    <article class="card stat">
        <span class="stat-label">Izin / Cuti</span>
        <p class="stat-value"><?= $hitung['Izin'] ?> <small>Orang</small></p>
    </article>
</section>

<div class="dash-grid">
    <!-- 2. Perlu tindakan -->
    <section class="card panel">
        <h2>Perlu Tindakan</h2>
        <ul class="todo">
            <?php foreach ($perluTindakan as $t): ?>
                <li class="todo-item">
                    <span class="todo-count todo-<?= e($t['warna']) ?>"><?= (int) $t['jumlah'] ?></span>
                    <div class="todo-text">
                        <strong><?= e($t['judul']) ?></strong>
                        <small><?= e($t['detail']) ?></small>
                    </div>
                    <a href="<?= e($t['link']) ?>" class="todo-link"><?= e($t['tombol']) ?> &rarr;</a>
                </li>
            <?php endforeach; ?>
            <?php if (!$perluTindakan): ?>
                <li class="empty">Tidak ada yang perlu ditindaklanjuti. Semua beres.</li>
            <?php endif; ?>
        </ul>
    </section>

    <!-- 3. Aktivitas absensi terbaru -->
    <section class="card panel">
        <h2>Aktivitas Absensi Terbaru</h2>
        <div class="table-wrap">
            <table class="table cards">
                <thead>
                    <tr><th>Nama</th><th>Jam</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($aktivitas as $a): ?>
                        <tr>
                            <td data-label="Nama">
                                <span class="strong"><?= e($a['nama']) ?></span><br>
                                <small class="muted-text"><?= e($a['jabatan']) ?></small>
                            </td>
                            <td data-label="Jam"><?= e($a['jam']) ?></td>
                            <td data-label="Status">
                                <span class="badge <?= str_replace(' ', '-', strtolower($a['status'])) ?>"><?= e($a['status']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$aktivitas): ?>
                        <tr><td colspan="3" class="empty">Belum ada yang absen hari ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- 5. Shift hari ini -->
<section class="card panel">
    <h2>Shift Hari Ini <span class="panel-date"><?= e($tanggalHariIni) ?></span></h2>
    <div class="shift-grid">
        <?php foreach ($shiftHariIni as $s): ?>
            <article class="shift-box">
                <header>
                    <strong><?= e($s['judul']) ?></strong>
                    <small><?= count($s['pegawai']) ?> pegawai</small>
                </header>
                <ul>
                    <?php foreach ($s['pegawai'] as $p): ?>
                        <li>
                            <span>
                                <?= e($p['nama']) ?>
                                <small class="muted-text">
                                    <?= e($p['jabatan']) ?><?= !empty($p['jam']) ? ' &middot; ' . e($p['jam']) : '' ?>
                                </small>
                            </span>
                            <span class="badge <?= str_replace(' ', '-', strtolower($p['status'])) ?>"><?= e($p['status']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </article>
        <?php endforeach; ?>
        <?php if (!$shiftHariIni): ?>
            <p class="empty">Tidak ada shift terjadwal hari ini.</p>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/Footer.php'; ?>