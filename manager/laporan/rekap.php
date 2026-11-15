<?php
// manager/laporan/rekap.php  
// CSS ada di assets/css/style.css, JS ada di assets/js/app.js
require_once __DIR__ . '/../../includes/Functions.php';
require_once __DIR__ . '/../../config/database.php';

$pageTitle    = 'Laporan';
$pageSubtitle = 'Rekapitulasi absensi bulanan';
$activeMenu   = 'laporan';
$topAction    = null;

$daftarJabatan = $pdo->query("
    SELECT DISTINCT jabatan 
    FROM users 
    WHERE jabatan IS NOT NULL AND jabatan != '' 
    ORDER BY jabatan ASC
")->fetchAll(PDO::FETCH_COLUMN);

// Menangkap input dari URL
$q       = trim($_GET['q'] ?? '');
$jabatan = $_GET['jabatan'] ?? '';
$bulanRaw   = $_GET['bulan'] ?? date('Y-m'); // Default bulan ini (YYYY-MM)

$bulan = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulanRaw) ? $bulanRaw : date('Y-m');

// QUERY SQL (Agregasi per Pegawai)
$sql = "
WITH input_bulan AS (
        SELECT TO_DATE(:bulan || '-01', 'YYYY-MM-DD') AS awal_bulan
    ),
    periode AS (
        SELECT 
            awal_bulan,
            (awal_bulan + INTERVAL '1 month - 1 day')::date AS akhir_bulan,
            LEAST(CURRENT_DATE, (awal_bulan + INTERVAL '1 month - 1 day')::date) AS batas_hitung
        FROM input_bulan
    ),
    rekap_absensi AS (
        SELECT 
            j.user_id,
            COUNT(j.id) FILTER (WHERE j.status = 'terjadwal' AND j.tanggal <= p.batas_hitung) AS hari_kerja,
            COUNT(a.id) FILTER (WHERE a.status_kehadiran IN ('tepat_waktu', 'terlambat')) AS hadir,
            COUNT(a.id) FILTER (WHERE a.status_kehadiran = 'terlambat') AS terlambat
        FROM jadwal j
        CROSS JOIN periode p
        LEFT JOIN absensi a ON a.jadwal_id = j.id
        WHERE j.tanggal BETWEEN p.awal_bulan AND p.akhir_bulan
        GROUP BY j.user_id
    ),
    rekap_izin AS (
        SELECT 
            ic.user_id,
            SUM(
                LEAST(ic.tanggal_selesai, p.batas_hitung) - GREATEST(ic.tanggal_mulai, p.awal_bulan) + 1
            ) AS total_izin
        FROM izin_cuti ic
        CROSS JOIN periode p
        WHERE p.batas_hitung >= p.awal_bulan
          AND ic.status_approval = 'disetujui'
          AND ic.tanggal_mulai <= p.batas_hitung
          AND ic.tanggal_selesai >= p.awal_bulan
        GROUP BY ic.user_id
    )
    SELECT 
        u.kode_pegawai AS kode, 
        u.nama, 
        u.jabatan,
        COALESCE(ra.hari_kerja, 0) AS hari_kerja,
        COALESCE(ra.hadir, 0) AS hadir,
        COALESCE(ra.terlambat, 0) AS terlambat,
        COALESCE(ri.total_izin, 0) AS izin
    FROM users u
    LEFT JOIN rekap_absensi ra ON ra.user_id = u.id
    LEFT JOIN rekap_izin ri ON ri.user_id = u.id
    WHERE u.status = 'aktif'
";

$where = [];
$params = [':bulan' => $bulan];

// Filter Pencarian
if (!empty($q)) {
    $where[] = "(u.nama ILIKE :q OR u.kode_pegawai ILIKE :q)";
    $params[':q'] = "%$q%";
}

// Filter Jabatan
if (!empty($jabatan)) {
    $where[] = "u.jabatan = :jabatan";
    $params[':jabatan'] = $jabatan;
}

if (count($where) > 0) {
    $sql .= " AND " . implode(" AND ", $where);
}

$sql .= " ORDER BY u.kode_pegawai ASC";

// EKSEKUSI QUERY
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$raw_data = $stmt->fetchAll();

// Hitung Alfa dan Persentase
$rekap = [];
$total_terlambat = 0;
$total_izin = 0;
$total_alfa = 0;
$total_persen = 0;
$pegawai_terjadwal = 0;

foreach ($raw_data as $row) {
    $hadir     = (int)$row['hadir'];
    $izin      = (int)$row['izin'];
    $hariKerja = (int)$row['hari_kerja'];
    
    // Hitung Alfa
    $alfa = max(0, $hariKerja - ($hadir + $izin));
    
    // Hitung Persentase Kehadiran
    $persen = $hariKerja > 0 ? round(($hadir / $hariKerja) * 100) : 0;
    
    // Level warna progress bar
    $level = $persen >= 90 ? 'tinggi' : ($persen >= 75 ? 'sedang' : 'rendah');

    $rekap[] = [
        'kode' => $row['kode'],
        'nama' => $row['nama'],
        'jabatan' => $row['jabatan'],
        'hari_kerja' => $hariKerja,
        'hadir' => $hadir,
        'terlambat' => (int)$row['terlambat'],
        'izin' => $izin,
        'alfa' => $alfa,
        'persen' => $persen,
        'level' => $level
    ];

    // Akumulasi untuk kartu statistik di atas
    $total_terlambat += (int)$row['terlambat'];
    $total_izin += $izin;
    $total_alfa += $alfa;

    if ($hariKerja > 0) {
        $total_persen += $persen;
        $pegawai_terjadwal++;
    }
}

$rata_rata = $pegawai_terjadwal > 0 ? round($total_persen / $pegawai_terjadwal) : 0;
$labelBulan = date('F Y', strtotime($bulan . '-01'));

include __DIR__ . '/../../includes/Header.php';
?>

<!-- KARTU STATISTIK -->
<section class="stats">
    <article class="card stat">
        <span class="stat-label">Rata-rata Kehadiran</span>
        <p class="stat-value"><?= $rata_rata ?>%</p>
    </article>
    <article class="card stat">
        <span class="stat-label">Total Terlambat</span>
        <p class="stat-value"><?= $total_terlambat ?> <small>Kali</small></p>
    </article>
    <article class="card stat">
        <span class="stat-label">Total Izin / Cuti</span>
        <p class="stat-value"><?= $total_izin ?> <small>Hari</small></p>
    </article>
    <article class="card stat">
        <span class="stat-label">Total Alfa</span>
        <p class="stat-value"><?= $total_alfa ?> <small>Hari</small></p>
    </article>
</section>

<section class="card panel" id="rekap-panel">
    <h2>Rekapitulasi Absensi</h2>

    <form method="GET" action="" class="filter-bar" id="rekap-form">
        <input type="search" name="q" id="rekap-q" placeholder="Cari nama atau ID pegawai..." 
               value="<?= e($q) ?>" autocomplete="off">

        <select name="jabatan" id="rekap-jabatan">
            <option value="">Semua Jabatan</option>
            <?php foreach ($daftarJabatan as $j): ?>
                <option value="<?= e($j) ?>" <?= $jabatan === $j ? 'selected' : '' ?>>
                    <?= e($j) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="month" name="bulan" id="rekap-bulan" value="<?= e($bulan) ?>">

        <?php if (!empty($q) || !empty($jabatan) || $bulan !== date('Y-m')): ?>
            <a href="rekap.php" class="btn-reset" style="display:inline-flex; align-items:center; text-decoration:none;">Reset</a>
        <?php endif; ?>
    </form>

    <!-- Info text tanpa tombol cetak -->
    <p class="filter-info" style="padding-bottom: 14px;">
        Rekap <strong><?= e($labelBulan) ?></strong> &middot; <strong><?= count($rekap) ?></strong> pegawai
    </p>

    <div class="table-wrap">
        <table class="table cards rekap-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th class="num">Hadir</th>
                    <th class="num">Terlambat</th>
                    <th class="num">Izin/Cuti</th>
                    <th class="num">Alfa</th>
                    <th>Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rekap) > 0): ?>
                    <?php foreach ($rekap as $r): ?>
                        <tr>
                            <td data-label="Kode" class="strong"><?= e($r['kode']) ?></td>
                            <td data-label="Nama"><?= e($r['nama']) ?></td>
                            <td data-label="Jabatan"><?= e($r['jabatan']) ?></td>
                            <td data-label="Hadir" class="num"><?= $r['hadir'] ?></td>
                            <td data-label="Terlambat" class="num"><?= $r['terlambat'] ?></td>
                            <td data-label="Izin/Cuti" class="num"><?= $r['izin'] ?></td>
                            <td data-label="Alfa" class="num <?= $r['alfa'] > 0 ? 'warn' : '' ?>"><?= $r['alfa'] ?></td>
                            <td data-label="Kehadiran">
                                <div class="bar-wrap">
                                    <div class="bar">
                                        <span class="bar-<?= $r['level'] ?>" style="width:<?= $r['persen'] ?>%"></span>
                                    </div>
                                    <b><?= $r['persen'] ?>%</b>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="empty" style="text-align: center;">Tidak ada data rekap yang sesuai.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('rekap-form');
        const searchInput = document.getElementById('rekap-q');

        // Kembalikan fokus & posisi kursor setelah page reload
        if (searchInput && searchInput.value.trim() !== '') {
            searchInput.focus();
            const len = searchInput.value.length;
            searchInput.setSelectionRange(len, len);
        }
        
        document.getElementById('rekap-jabatan').addEventListener('change', () => form.submit());
        document.getElementById('rekap-bulan').addEventListener('change', () => form.submit());
        
        let timer;
        searchInput.addEventListener('input', function() {
            clearTimeout(timer);
            timer = setTimeout(() => { form.submit(); }, 600); 
        });
    });
</script>


<?php include __DIR__ . '/../../includes/Footer.php'; ?>