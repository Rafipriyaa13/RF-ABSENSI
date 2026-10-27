<?php
// manager/pegawai/index.php
require_once __DIR__ . '/../../includes/Functions.php';
require_once __DIR__ . '/../../config/database.php';
// require_once __DIR__ . '/../../includes/Auth.php';
// cek_role('manager');   // aktifkan setelah login selesai

$pageTitle    = 'Kepegawaian';
$pageSubtitle = 'Kehadiran, kontrak, dan jadwal shift';
$activeMenu   = 'kepegawaian';
$topAction    = ['Tambah Data', '/manager/pegawai/tambah.php'];

// ---------- 1. Statistik ----------
$total = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'aktif'")->fetchColumn();

$hadir = $pdo->query("
    SELECT COUNT(*) FROM absensi
    WHERE check_in >= CURRENT_DATE AND check_in < CURRENT_DATE + 1
")->fetchColumn();

$izin = $pdo->query("
    SELECT COUNT(DISTINCT user_id) FROM izin_cuti
    WHERE status_approval = 'disetujui'
      AND CURRENT_DATE BETWEEN tanggal_mulai AND tanggal_selesai
")->fetchColumn();

// Kontrak berakhir = sudah lewat atau habis dalam 30 hari ke depan
$kontrak = $pdo->query("
    SELECT COUNT(*) FROM users
    WHERE status = 'aktif' AND kontrak_selesai IS NOT NULL
      AND kontrak_selesai <= CURRENT_DATE + 30
")->fetchColumn();

$stats = [
    ['Total Karyawan',   $total],
    ['Hadir Hari Ini',   $hadir],
    ['Izin/Cuti',        $izin],
    ['Kontrak Berakhir', $kontrak],
];

// ---------- 2. Daftar karyawan + status hari ini ----------
$karyawan = $pdo->query("
    SELECT u.id,u.kode_pegawai, u.nama, u.jabatan, u.kontrak_selesai,
        CASE
            WHEN a.status_kehadiran = 'terlambat'   THEN 'Terlambat'
            WHEN a.status_kehadiran = 'tepat_waktu' THEN 'Hadir'
            WHEN EXISTS (
                SELECT 1 FROM izin_cuti i
                WHERE i.user_id = u.id
                  AND i.status_approval = 'disetujui'
                  AND CURRENT_DATE BETWEEN i.tanggal_mulai AND i.tanggal_selesai
            )                                       THEN 'Izin'
            WHEN j.id IS NULL OR j.status = 'libur' THEN 'Libur'
            ELSE 'Belum Absen'
        END AS kehadiran
    FROM users u
    LEFT JOIN jadwal  j ON j.user_id = u.id AND j.tanggal = CURRENT_DATE
    LEFT JOIN absensi a ON a.jadwal_id = j.id
    WHERE u.status = 'aktif'
    ORDER BY u.kode_pegawai
")->fetchAll();

// ---------- 3. Jadwal shift minggu ini (Senin-Jumat) ----------
$hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

$daftarShift = $pdo->query("SELECT id, jam_mulai, jam_selesai FROM shift ORDER BY jam_mulai")->fetchAll();

$jadwal = $pdo->query("
    SELECT j.shift_id,
           EXTRACT(ISODOW FROM j.tanggal)::int AS hari,
           STRING_AGG(split_part(u.nama, ' ', 1), ', ' ORDER BY u.nama) AS nama
    FROM jadwal j
    JOIN users u ON u.id = j.user_id
    WHERE j.status = 'terjadwal'
      AND j.tanggal BETWEEN date_trunc('week', CURRENT_DATE)::date
                        AND date_trunc('week', CURRENT_DATE)::date + 4
    GROUP BY j.shift_id, hari
")->fetchAll();

$peta = [];
foreach ($jadwal as $r) {
    $peta[$r['shift_id']][$r['hari']] = $r['nama'];
}

$shift = [];
foreach ($daftarShift as $s) {
    $baris = [substr($s['jam_mulai'], 0, 5) . ' - ' . substr($s['jam_selesai'], 0, 5)];
    for ($h = 1; $h <= 5; $h++) {
        $baris[] = $peta[$s['id']][$h] ?? '-';
    }
    $shift[] = $baris;
}

include __DIR__ . '/../../includes/Header.php';
?>

<?php if ($flash = get_flash()): ?>
    <div class="alert alert-<?= e($flash['tipe']) ?>"><?= e($flash['pesan']) ?></div>
<?php endif; ?>

<section class="stats">
    <?php foreach ($stats as [$label, $nilai]): ?>
        <article class="card stat">
            <span class="stat-label"><?= e($label) ?></span>
            <p class="stat-value"><?= number_format((int) $nilai) ?> <small>Orang</small></p>
        </article>
    <?php endforeach; ?>
</section>

<section class="card panel">
    <h2>Daftar Karyawan</h2>
    <div class="table-wrap">
        <table class="table cards">
            <thead>
                <tr><th>ID</th><th>Nama</th><th>Jabatan</th><th>Kehadiran</th><th>Kontrak</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($karyawan as $k):
                    $hampirHabis = $k['kontrak_selesai'] && strtotime($k['kontrak_selesai']) <= strtotime('+30 days');
                ?>
                    <tr>
                        <td data-label="ID" class="strong"><?= e($k['kode_pegawai'] ?? '-') ?></td>
                        <td data-label="Nama"><?= e($k['nama']) ?></td>
                        <td data-label="Jabatan"><?= e($k['jabatan'] ?? '-') ?></td>
                        <td data-label="Kehadiran">
                            <span class="badge <?= str_replace(' ', '-', strtolower($k['kehadiran'])) ?>"><?= e($k['kehadiran']) ?></span>
                        </td>
                        <td data-label="Kontrak" class="<?= $hampirHabis ? 'warn' : '' ?>"><?= e(tgl_indo($k['kontrak_selesai'])) ?></td>
                        <td>
                            <a href="/manager/pegawai/edit.php?id=<?= e($k['id']) ?>" class="btn-edit">Edit</a>
                            <a href="/manager/pegawai/hapus.php?id=<?= e($k['id']) ?>" class="btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus pegawai ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$karyawan): ?>
                    <tr><td colspan="5" class="empty">Belum ada pegawai. Klik Tambah Data untuk mulai.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card panel">
    <h2>Jadwal Shift</h2>
    <div class="table-wrap">
        <table class="table shift">
            <thead>
                <tr>
                    <th>Jam Shift</th>
                    <?php foreach ($hari as $h): ?><th><?= $h ?></th><?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shift as $row): ?>
                    <tr>
                        <?php foreach ($row as $i => $cell): ?>
                            <td class="<?= $i === 0 ? 'strong' : '' ?>"><?= e($cell) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$shift): ?>
                    <tr><td colspan="6" class="empty">Belum ada data shift.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include __DIR__ . '/../../includes/Footer.php'; ?>