<?php
// manager/izin_cuti/index.php
require_once __DIR__ . '/../../includes/Functions.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/Auth.php';
cek_role('manager');   // aktifkan setelah login selesai

$pageTitle = 'Izin / Cuti';
$pageSubtitle = 'Tinjau dan setujui pengajuan izin & cuti pegawai';
$activeMenu = 'izin';
$topAction = null;

// 1. TANGKAP & VALIDASI INPUT FILTER
$tabValid = ['semua', 'menunggu', 'disetujui', 'ditolak'];
$tab = in_array($_GET['tab'] ?? '', $tabValid, true) ? $_GET['tab'] : 'semua';
$q = trim($_GET['q'] ?? '');

$limit = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// 2. PROSES PERSETUJUAN / PENOLAKAN (METHOD POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $izin_id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';
    $alasan = trim($_POST['alasan_penolakan'] ?? '');

    $backTab = in_array($_POST['tab'] ?? '', $tabValid, true) ? $_POST['tab'] : 'semua';
    $backQ = trim($_POST['q'] ?? '');
    $backPage = max(1, (int) ($_POST['page'] ?? 1));

    if (!$izin_id || !in_array($status, ['disetujui', 'ditolak'], true)) {
        set_flash('error', 'Permintaan aksi tidak valid.');
    } elseif ($status === 'ditolak' && $alasan === '') {
        set_flash('error', 'Alasan penolakan wajib diisi.');
    } else {
        $approver_id = $_SESSION['user_id'] ?? 5; // Sesuaikan dengan session manager aktif
        try {
            $stmt = $pdo->prepare("
                UPDATE izin_cuti 
                SET status_approval = :status, 
                    approved_by = :approver,
                    alasan_penolakan = :alasan
                WHERE id = :id AND status_approval = 'menunggu'
            ");
            $stmt->execute([
                ':status' => $status,
                ':approver' => $approver_id,
                ':alasan' => $alasan,
                ':id' => $izin_id
            ]);

            if ($stmt->rowCount() > 0) {
                $pesan = $status === 'ditolak'
                    ? 'Pengajuan berhasil ditolak. (Alasan: ' . $alasan . ')'
                    : 'Pengajuan berhasil disetujui.';
                set_flash('sukses', $pesan);
            } else {
                set_flash('error', 'Pengajuan tidak ditemukan atau sudah diproses sebelumnya.');
            }
        } catch (PDOException $e) {
            error_log('Approval izin gagal: ' . $e->getMessage());
            set_flash('error', 'Gagal memproses data pengajuan.');
        }
    }

    $redirectQuery = http_build_query(array_filter([
        'tab' => $backTab !== 'semua' ? $backTab : null,
        'q' => $backQ !== '' ? $backQ : null,
        'page' => $backPage > 1 ? $backPage : null,
    ]));
    header('Location: index.php' . ($redirectQuery ? '?' . $redirectQuery : ''));
    exit;
}

// 3. SUSUN QUERY SQL (Filter Tab, Pencarian, & Pagination)
$sql = "
    SELECT 
        COUNT(*) OVER() AS total_rows,
        i.id,
        u.kode_pegawai AS kode,
        u.nama,
        u.jabatan,
        i.jenis,
        i.alasan,
        TO_CHAR(i.tanggal_mulai, 'YYYY-MM-DD') AS mulai,
        TO_CHAR(i.tanggal_selesai, 'YYYY-MM-DD') AS selesai,
        i.status_approval AS status
    FROM izin_cuti i
    INNER JOIN users u ON i.user_id = u.id
";

$where = [];
$params = [];

if ($tab !== 'semua') {
    $where[] = "i.status_approval = :status";
    $params[':status'] = $tab;
}

if ($q !== '') {
    $where[] = "(u.nama ILIKE :q OR u.kode_pegawai ILIKE :q)";
    $params[':q'] = "%$q%";
}

if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY CASE WHEN i.status_approval = 'menunggu' THEN 1 ELSE 2 END, i.tanggal_mulai DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$izin = $stmt->fetchAll();

$totalData = $izin[0]['total_rows'] ?? 0;
$totalPages = max(1, (int) ceil($totalData / $limit));

// 4. HITUNG STATISTIK
$st_stats = $pdo->query("SELECT status_approval, COUNT(*) as jumlah FROM izin_cuti GROUP BY status_approval");
$stats_raw = $st_stats->fetchAll(PDO::FETCH_KEY_PAIR);
$stat_menunggu = $stats_raw['menunggu'] ?? 0;
$stat_disetujui = $stats_raw['disetujui'] ?? 0;
$stat_ditolak = $stats_raw['ditolak'] ?? 0;

include __DIR__ . '/../../includes/Header.php';
?>

<?php if ($flash = get_flash()): ?>
    <div class="alert alert-<?= e($flash['tipe']) ?>"><?= e($flash['pesan']) ?></div>
<?php endif; ?>

<!-- KARTU STATISTIK -->
<section class="stats">
    <article class="card stat">
        <span class="stat-label">Menunggu</span>
        <p class="stat-value"><?= $stat_menunggu ?> <small>Pengajuan</small></p>
    </article>
    <article class="card stat">
        <span class="stat-label">Disetujui</span>
        <p class="stat-value"><?= $stat_disetujui ?> <small>Pengajuan</small></p>
    </article>
    <article class="card stat">
        <span class="stat-label">Ditolak</span>
        <p class="stat-value"><?= $stat_ditolak ?> <small>Pengajuan</small></p>
    </article>
</section>

<section class="card panel izin">
    <h2>Pengajuan Izin / Cuti</h2>

    <!-- TAB MENU -->
    <div class="izin-tabs" id="izin-tabs">
        <?php foreach (['semua' => 'Semua', 'menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'] as $key => $label): ?>
            <button type="button" onclick="window.location.href='?tab=<?= $key ?>&q=<?= urlencode($q) ?>'"
                class="<?= $tab === $key ? 'active' : '' ?>">
                <?= $label ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- FORM PENCARIAN (Auto-submit sama seperti riwayat.php) -->
    <form method="GET" action="" class="izin-search" id="izin-filter-form" style="display:flex; gap:10px;">
        <input type="hidden" name="tab" value="<?= e($tab) ?>">
        <input type="search" name="q" id="izin-q" placeholder="Cari nama atau ID pegawai..." value="<?= e($q) ?>"
            autocomplete="off" style="flex:1;">
        <?php if ($q !== ''): ?>
            <a href="?tab=<?= e($tab) ?>" class="btn-reset"
                style="display:inline-flex; align-items:center; text-decoration:none;">Reset</a>
        <?php endif; ?>
    </form>

    <p class="filter-info">
        Menampilkan <strong><?= count($izin) ?></strong> dari <strong><?= $totalData ?></strong> pengajuan
        (Halaman <?= $page ?> dari <?= $totalPages ?>).
    </p>

    <div class="table-wrap">
        <table class="table cards">
            <thead>
                <tr>
                    <th>Pegawai</th>
                    <th>Jenis</th>
                    <th>Periode</th>
                    <th>Alasan</th>
                    <th>Durasi</th>
                    <th style="width: 140px;">Dokumen</th>
                    <th style="width: 130px;">Status</th>
                    <th style="width: 165px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($izin) > 0): ?>
                    <?php foreach ($izin as $row):
                        $tgl_mulai = new DateTime($row['mulai']);
                        $tgl_selesai = new DateTime($row['selesai']);
                        $durasi = $tgl_mulai->diff($tgl_selesai)->days + 1;
                        ?>
                        <tr>
                            <td data-label="Pegawai">
                                <span class="strong"><?= e($row['nama']) ?></span><br>
                                <small class="muted"><?= e($row['kode']) ?> &middot; <?= e($row['jabatan']) ?></small>
                            </td>
                            <td data-label="Jenis"><?= ucfirst(e($row['jenis'])) ?></td>
                            <td data-label="Periode">
                                <?php if ($row['mulai'] === $row['selesai']): ?>
                                    <?= date('d M Y', strtotime($row['mulai'])) ?>
                                <?php else: ?>
                                    <?= date('d M', strtotime($row['mulai'])) ?> - <?= date('d M Y', strtotime($row['selesai'])) ?>
                                <?php endif; ?>
                            </td>
                            <td data-label="Alasan"><?= e($row['alasan']) ?></td>
                            <td data-label="Durasi"><?= $durasi ?> hari</td>

                            <!-- Kolom Dokumen (Placeholder) -->
                            <td data-label="Dokumen">
                                <button type="button" class="btn-doc"
                                    onclick="alert('Fitur lihat dokumen akan dibuat di versi selanjutnya.');">Lihat
                                    Surat</button>
                            </td>

                            <td data-label="Status">
                                <span class="st st-<?= e($row['status']) ?>"><?= ucfirst(e($row['status'])) ?></span>
                                <!-- TODO: Tampilkan catatan alasan_penolakan di sini setelah kolom DB dibuat -->
                            </td>

                            <td data-label="Aksi">
                                <?php if ($row['status'] === 'menunggu'): ?>
                                    <div class="aksi" style="display:flex; align-items:center; gap:8px;">
                                        <!-- Form POST Setujui -->
                                        <form method="POST" action="" style="margin:0;" data-nama="<?= e($row['nama']) ?>"
                                            onsubmit="return confirm('Setujui pengajuan izin/cuti ' + this.dataset.nama + '?')">
                                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                            <input type="hidden" name="status" value="disetujui">
                                            <input type="hidden" name="tab" value="<?= e($tab) ?>">
                                            <input type="hidden" name="q" value="<?= e($q) ?>">
                                            <input type="hidden" name="page" value="<?= $page ?>">
                                            <button type="submit" class="btn-aksi setuju"
                                                style="border:none; cursor:pointer; padding:6px 12px; border-radius:6px; font-weight:600; font-size:13px;">Setujui</button>
                                        </form>

                                        <!-- Tombol Buka Modal Tolak -->
                                        <button type="button" class="btn-aksi tolak"
                                            style="background:#dc2626; color:#fff; border:none; cursor:pointer; padding:6px 12px; border-radius:6px; font-weight:600; font-size:13px;"
                                            data-id="<?= (int) $row['id'] ?>" data-nama="<?= e($row['nama']) ?>"
                                            onclick="bukaModalTolak(this)">
                                            Tolak
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty" style="text-align:center;">Tidak ada pengajuan yang sesuai dengan
                            filter.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
            <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn-reset"
                    style="text-decoration:none;">&laquo; Sebelumnya</a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>

            <span>Halaman <?= $page ?> / <?= $totalPages ?></span>

            <?php if ($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn-reset"
                    style="text-decoration:none;">Berikutnya &raquo;</a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<!-- MODAL ALASAN PENOLAKAN -->
<dialog id="modal-tolak" class="dlg">
    <form method="POST" action="">
        <h2>Tolak Pengajuan</h2>

        <div class="dlg-body">
            <p class="hint" style="margin: 0; margin-bottom: 8px;">
                Masukkan alasan penolakan untuk <strong id="tolak-nama"></strong>:
            </p>

            <!-- Input Hidden -->
            <input type="hidden" name="id" id="tolak-id">
            <input type="hidden" name="status" value="ditolak">
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            <input type="hidden" name="q" value="<?= e($q) ?>">
            <input type="hidden" name="page" value="<?= $page ?>">

            <!-- Textarea dengan styling yang rapi -->
            <textarea name="alasan_penolakan" id="tolak-alasan" rows="3" required
                placeholder="Contoh: Jadwal outlet sedang padat, silakan ajukan di minggu berikutnya..."
                style="width:100%; padding:12px; border:1px solid #d1d5db; border-radius:8px; font-family:inherit; font-size:14px; box-sizing:border-box; resize:vertical; outline:none; color:var(--ink);"></textarea>
        </div>

        <div class="form-actions">
            <button type="button" class="btn-ghost"
                onclick="document.getElementById('modal-tolak').close()">Batal</button>
            <button type="submit" class="btn-delete">Kirim Penolakan</button>
        </div>
    </form>
</dialog>

<script>
    function bukaModalTolak(btn) {
        document.getElementById('tolak-id').value = btn.dataset.id;
        document.getElementById('tolak-nama').textContent = btn.dataset.nama;

        const alasanInput = document.getElementById('tolak-alasan');
        alasanInput.value = '';

        document.getElementById('modal-tolak').showModal();
        alasanInput.focus();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('izin-filter-form');
        const searchInput = document.getElementById('izin-q');

        if (searchInput && searchInput.value.trim() !== '') {
            searchInput.focus();
            const len = searchInput.value.length;
            searchInput.setSelectionRange(len, len);
        }

        let timer;
        searchInput.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(() => form.submit(), 600);
        });
    });
</script>

<?php include __DIR__ . '/../../includes/Footer.php'; ?>