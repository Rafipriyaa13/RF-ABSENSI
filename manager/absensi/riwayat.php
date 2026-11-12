<?php
// manager/absensi/riwayat.php  (FRONTEND SAJA, data masih dummy)
// CSS ada di assets/css/style.css, JS ada di assets/js/app.js
require_once __DIR__ . '/../../includes/Functions.php';

$pageTitle    = 'Riwayat Absensi';
$pageSubtitle = 'Daftar pegawai yang sudah melakukan absensi';
$activeMenu   = 'absensi';
$topAction    = null;

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
$lusa    = date('Y-m-d', strtotime('-2 days'));

$absensi = [
    ['tanggal' => $hariIni, 'kode' => 'RF-EMP01', 'nama' => 'Andi Wijaya',   'jabatan' => 'Store Manager', 'shift' => '08:00 - 16:00', 'jam_masuk' => '07:50', 'status' => 'Hadir'],
    ['tanggal' => $hariIni, 'kode' => 'RF-EMP02', 'nama' => 'Siti Aminah',   'jabatan' => 'Cashier',       'shift' => '07:00 - 15:00', 'jam_masuk' => '06:58', 'status' => 'Hadir'],
    ['tanggal' => $hariIni, 'kode' => 'RF-EMP03', 'nama' => 'Budi Santoso',  'jabatan' => 'Kitchen Staff', 'shift' => '07:00 - 15:00', 'jam_masuk' => '07:12', 'status' => 'Terlambat'],
    ['tanggal' => $hariIni, 'kode' => 'RF-EMP04', 'nama' => 'Dewi Lestari',  'jabatan' => 'Crew',          'shift' => '11:00 - 19:00', 'jam_masuk' => '10:55', 'status' => 'Hadir'],
    ['tanggal' => $kemarin, 'kode' => 'RF-EMP02', 'nama' => 'Siti Aminah',   'jabatan' => 'Cashier',       'shift' => '07:00 - 15:00', 'jam_masuk' => '07:01', 'status' => 'Hadir'],
    ['tanggal' => $kemarin, 'kode' => 'RF-EMP05', 'nama' => 'Rizky Pratama', 'jabatan' => 'Cashier',       'shift' => '11:00 - 19:00', 'jam_masuk' => '11:20', 'status' => 'Terlambat'],
    ['tanggal' => $kemarin, 'kode' => 'RF-EMP06', 'nama' => 'Ayu Wulandari', 'jabatan' => 'Crew',          'shift' => '07:00 - 15:00', 'jam_masuk' => '07:00', 'status' => 'Hadir'],
    ['tanggal' => $lusa,    'kode' => 'RF-EMP03', 'nama' => 'Budi Santoso',  'jabatan' => 'Kitchen Staff', 'shift' => '07:00 - 15:00', 'jam_masuk' => '06:55', 'status' => 'Hadir'],
    ['tanggal' => $lusa,    'kode' => 'RF-EMP04', 'nama' => 'Dewi Lestari',  'jabatan' => 'Crew',          'shift' => '11:00 - 19:00', 'jam_masuk' => '11:35', 'status' => 'Terlambat'],
];

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
                    <th>ID</th><th>Nama</th><th>Jabatan</th>
                    <th>Shift</th><th>Jam Masuk</th><th>Status</th>
                </tr>
            </thead>
            <tbody id="abs-rows"></tbody>
        </table>
    </div>
</section>

<?php include __DIR__ . '/../../includes/Footer.php'; ?>