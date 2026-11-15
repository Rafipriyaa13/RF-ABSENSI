<?php
// manager/absensi/riwayat.php  (FRONTEND SAJA, data masih dummy)
// CSS ada di assets/css/style.css, JS ada di assets/js/app.js
require_once __DIR__ . '/../../includes/Functions.php';
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Riwayat Absensi';
$pageSubtitle = 'Daftar pegawai yang sudah melakukan absensi';
$activeMenu = 'absensi';
$topAction = null;

$daftarJabatan = ['Store Manager', 'Cashier', 'Kitchen Staff', 'Crew'];

/*
 * Data dummy. Backend cukup mengisi array ini dengan field yang sama:
 *   tanggal (Y-m-d), kode, nama, jabatan, shift (teks), jam_masuk (HH:MM),
 *   status ('Hadir' atau 'Terlambat')
 * Filter pencarian, jabatan, dan tanggal dikerjakan di sisi browser (app.js),
 * jadi kirim saja data absensi untuk rentang yang masuk akal (misalnya 30 hari terakhir).
 */
$hariIni = date('Y-m-d');
$kemarin = date('Y-m-d', strtotime('-1 day'));
$lusa = date('Y-m-d', strtotime('-2 days'));


$absensi = $pdo->query("
    SELECT 
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
    ORDER BY j.tanggal DESC, a.id DESC
")->fetchAll();

include __DIR__ . '/../../includes/Header.php';
?>

<section class="card panel" id="absensi-panel" data-absensi="<?= e(json_encode($absensi, JSON_UNESCAPED_UNICODE)) ?>">
    <h2>Riwayat Absensi</h2>

    <div class="filter-bar">
        <input type="search" id="abs-q" placeholder="Cari nama atau ID pegawai..." autocomplete="off">

        <select id="abs-jabatan">
            <option value="">Semua Jabatan</option>
            <?php foreach ($daftarJabatan as $j): ?>
                <option value="<?= e($j) ?>"><?= e($j) ?></option>
            <?php endforeach; ?>
        </select>

        <input type="date" id="abs-tanggal" value="<?= $hariIni ?>">

        <button type="button" class="btn-reset" id="abs-reset" hidden>Reset</button>
    </div>

    <p class="filter-info" id="abs-info"></p>

    <div class="table-wrap">
        <table class="table cards">
            <thead>
                <tr>
                    <th>ID</th>
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
                        <td><?= e($row['kode']) ?></td>
                        <td><?= e($row['nama']) ?></td>
                        <td><?= e($row['jabatan']) ?></td>
                        <td><?= e($row['shift']) ?></td>
                        <td><?= e($row['jam_masuk']) ?></td>
                        
                        <td class="<?= $row['status'] == 'Hadir' ? 'status-hadir' : 'status-terlambat' ?>">
                            <?= e($row['status']) ?>
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
</section>

<?php include __DIR__ . '/../../includes/Footer.php'; ?>