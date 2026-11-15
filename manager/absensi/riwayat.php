<?php
// manager/absensi/riwayat.php  
// CSS ada di assets/css/style.css, JS ada di assets/js/app.js
require_once __DIR__ . '/../../includes/Functions.php';
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Riwayat Absensi';
$pageSubtitle = 'Daftar pegawai yang sudah melakukan absensi';
$activeMenu = 'absensi';
$topAction = null;

$daftarJabatan = $pdo->query("
    SELECT DISTINCT jabatan 
    FROM users 
    WHERE jabatan IS NOT NULL AND jabatan != '' 
    ORDER BY jabatan ASC
")->fetchAll(PDO::FETCH_COLUMN);

// Menangkap input dari URL
$q = $_GET['q'] ?? '';
$jabatan = $_GET['jabatan'] ?? '';
$tanggalRaw = $_GET['tanggal'] ?? ''; // Default KOSONG (Semua Tanggal)

// Validasi format tanggal
$tanggal = '';
if ($tanggalRaw !== '') {
    $dt = DateTime::createFromFormat('Y-m-d', $tanggalRaw);
    if ($dt && $dt->format('Y-m-d') === $tanggalRaw) {
        $tanggal = $tanggalRaw;
    }
}

$limit = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Query ambil data bsensi
$sql = "
    SELECT 
        COUNT(*) OVER() AS total_rows,
        u.kode_pegawai AS kode, 
        u.nama, 
        u.jabatan, 
        TO_CHAR(j.tanggal, 'YYYY-MM-DD') AS tanggal,
        TO_CHAR(s.jam_mulai, 'HH24:MI') || ' - ' || TO_CHAR(s.jam_selesai, 'HH24:MI') AS shift, 
        COALESCE(TO_CHAR(a.check_in, 'HH24:MI'), '-') AS jam_masuk, 
        CASE 
            WHEN a.status_kehadiran = 'tepat_waktu' THEN 'Hadir'
            WHEN a.status_kehadiran = 'terlambat' THEN 'Terlambat'
            ELSE 'Belum Absen' 
        END AS status
    FROM jadwal j
    INNER JOIN users u ON j.user_id = u.id
    INNER JOIN shift s ON j.shift_id = s.id
    LEFT JOIN absensi a ON a.jadwal_id = j.id
";

$where = ["j.tanggal <= CURRENT_DATE"]; // Array untuk menampung kondisi
$params = [];

// Filter Tanggal
if (!empty($tanggal)) {
    $where[] = "j.tanggal = :tanggal";
    $params[':tanggal'] = $tanggal;
}

// Filter Pencarian (Nama atau Kode)
if (!empty($q)) {
    $where[] = "(u.nama ILIKE :q OR u.kode_pegawai ILIKE :q)";
    $params[':q'] = "%$q%";
}

// Filter Jabatan
if (!empty($jabatan)) {
    $where[] = "u.jabatan = :jabatan";
    $params[':jabatan'] = $jabatan;
}

// Gabungkan kondisi WHERE jika ada
if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY j.tanggal DESC, a.check_in DESC NULLS LAST, u.nama ASC LIMIT $limit OFFSET $offset";

// EKSEKUSI QUERY
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$absensi = $stmt->fetchAll();

$totalData = $absensi[0]['total_rows'] ?? 0;
$totalPages = max(1, (int)ceil($totalData / $limit));

include __DIR__ . '/../../includes/Header.php';
?>

<section class="card panel" id="absensi-panel">
    <h2>Riwayat Absensi</h2>

    <form method="GET" action="" class="filter-bar" id="filter-form">

        <input type="search" name="q" id="abs-q" placeholder="Cari nama atau ID pegawai..." value="<?= e($q) ?>"
            autocomplete="off">

        <select name="jabatan" id="abs-jabatan">
            <option value="">Semua Jabatan</option>
            <?php foreach ($daftarJabatan as $j): ?>
                <option value="<?= e($j) ?>" <?= $jabatan === $j ? 'selected' : '' ?>>
                    <?= e($j) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="date" name="tanggal" id="abs-tanggal" value="<?= e($tanggal) ?>">

        <!-- Tombol reset muncul jika ada filter aktif -->
        <?php if (!empty($q) || !empty($jabatan) || !empty($tanggal)): ?>
            <a href="riwayat.php" class="btn-reset"
                style="display:inline-flex; align-items:center; text-decoration:none;">Reset</a>
        <?php endif; ?>
    </form>

    <p class="filter-info">
        Menampilkan <strong><?= count($absensi) ?></strong> dari <strong><?= $totalData ?></strong> data absensi
        (Halaman <?= $page ?> dari <?= $totalPages ?>).
    </p>

    <div class="table-wrap">
        <table class="table cards">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kode Pegawai</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th>Shift</th>
                    <th>Jam Masuk</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="abs-rows">
                <?php if (count($absensi) > 0): ?>

                    <?php foreach ($absensi as $row): ?>
                        <tr>
                            <td>
                                <?= date('d/m/y', strtotime($row['tanggal'])) ?>
                            </td>
                            <td><?= e($row['kode']) ?></td>
                            <td><?= e($row['nama']) ?></td>
                            <td><?= e($row['jabatan']) ?></td>
                            <td><?= e($row['shift']) ?></td>
                            <td><?= e($row['jam_masuk']) ?></td>

                            <td>
                                <span class="badge <?= strtolower(str_replace(' ', '-', $row['status'])) ?>">
                                    <?= e($row['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">Belum ada data absensi.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
        <?php if ($totalPages > 1): ?>
        <!-- Tambahkan padding dan border-top -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-top: 1px solid var(--line);">
            
            <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn-reset"
                    style="text-decoration:none;">&laquo; Sebelumnya</a>
            <?php else: ?>
                <span style="width: 100px;"></span> <!-- Placeholder agar text tengah tetap di tengah -->
            <?php endif; ?>

            <span style="color: var(--muted); font-size: 13px; font-weight: 500;">
                Halaman <?= $page ?> dari <?= $totalPages ?>
            </span>

            <?php if ($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn-reset"
                    style="text-decoration:none;">Berikutnya &raquo;</a>
            <?php else: ?>
                <span style="width: 100px;"></span> <!-- Placeholder agar text tengah tetap di tengah -->
            <?php endif; ?>
            
        </div>
    <?php endif; ?>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('filter-form');
        const searchInput = document.getElementById('abs-q');

        // Kembalikan fokus & posisi kursor ke akhir teks setelah page reload
        if (searchInput && searchInput.value.trim() !== '') {
            searchInput.focus();
            const len = searchInput.value.length;
            searchInput.setSelectionRange(len, len);
        }

        document.getElementById('abs-jabatan').addEventListener('change', () => form.submit());
        document.getElementById('abs-tanggal').addEventListener('change', () => form.submit());

        let timer;
        searchInput.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(() => form.submit(), 600);
        });
    });
</script>

<?php include __DIR__ . '/../../includes/Footer.php'; ?>