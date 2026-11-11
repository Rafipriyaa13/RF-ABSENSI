<?php
// manager/laporan/rekap.php  (FRONTEND SAJA, data masih dummy)
// CSS ada di assets/css/style.css, JS ada di assets/js/app.js
require_once __DIR__ . '/../../includes/Functions.php';

$pageTitle    = 'Laporan';
$pageSubtitle = 'Rekapitulasi absensi bulanan';
$activeMenu   = 'laporan';
$topAction    = null;

$daftarJabatan = ['Store Manager', 'Cashier', 'Kitchen Staff', 'Crew'];

/*
 * ===== DATA DUMMY (backend cukup mengisi array $rekap) =====
 * Satu baris = rekap satu pegawai untuk satu bulan:
 *   bulan      : 'YYYY-MM'
 *   kode, nama, jabatan
 *   hari_kerja : jumlah hari kerja pegawai itu pada bulan tersebut
 *   hadir      : total hari hadir (SUDAH termasuk yang terlambat)
 *   terlambat  : berapa kali di antaranya terlambat
 *   izin       : hari izin/cuti yang disetujui
 *   alfa       : hari tanpa keterangan
 * Persentase kehadiran dihitung di browser: hadir / hari_kerja.
 * Filter bulan, jabatan, dan pencarian dikerjakan di browser (app.js).
 */
$pegawai = [
    ['RF-EMP01', 'Andi Wijaya',   'Store Manager'],
    ['RF-EMP02', 'Siti Aminah',   'Cashier'],
    ['RF-EMP03', 'Budi Santoso',  'Kitchen Staff'],
    ['RF-EMP04', 'Dewi Lestari',  'Crew'],
    ['RF-EMP05', 'Rizky Pratama', 'Cashier'],
    ['RF-EMP06', 'Ayu Wulandari', 'Crew'],
    ['RF-EMP07', 'Eko Prasetyo',  'Crew'],
    ['RF-EMP08', 'Rina Marlina',  'Kitchen Staff'],
];

// Hari kerja Senin-Jumat dalam satu bulan (bulan berjalan dihitung sampai hari ini)
function hari_kerja(string $bulan): int
{
    $awal  = strtotime($bulan . '-01');
    $akhir = min(strtotime(date('Y-m-t', $awal)), strtotime(date('Y-m-d')));
    $n = 0;
    for ($t = $awal; $t <= $akhir; $t += 86400) {
        if ((int) date('N', $t) <= 5) {
            $n++;
        }
    }
    return $n;
}

$rekap = [];
foreach ([0, 1, 2] as $mundur) {
    $bulan = date('Y-m', strtotime("first day of -$mundur month"));
    $hk    = hari_kerja($bulan);

    foreach ($pegawai as [$kode, $nama, $jabatan]) {
        mt_srand(crc32($kode . $bulan));          // angka acak tetap per pegawai & bulan
        $alfa  = min($hk, mt_rand(0, 9) > 6 ? mt_rand(1, 3) : 0);
        $izin  = min($hk - $alfa, mt_rand(0, 3));
        $hadir = $hk - $alfa - $izin;
        $rekap[] = [
            'bulan' => $bulan, 'kode' => $kode, 'nama' => $nama, 'jabatan' => $jabatan,
            'hari_kerja' => $hk, 'hadir' => $hadir,
            'terlambat' => min($hadir, mt_rand(0, 5)), 'izin' => $izin, 'alfa' => $alfa,
        ];
    }
}

include __DIR__ . '/../../includes/Header.php';
?>

<section class="stats" id="rekap-stats"></section>

<section class="card panel" id="rekap-panel" data-rekap="<?= e(json_encode($rekap, JSON_UNESCAPED_UNICODE)) ?>">
    <h2>Rekapitulasi Absensi</h2>

    <div class="filter-bar">
        <input type="search" id="rekap-q" placeholder="Cari nama atau ID pegawai..." autocomplete="off">

        <select id="rekap-jabatan">
            <option value="">Semua Jabatan</option>
            <?php foreach ($daftarJabatan as $j): ?>
                <option value="<?= e($j) ?>"><?= e($j) ?></option>
            <?php endforeach; ?>
        </select>

        <input type="month" id="rekap-bulan" value="<?= date('Y-m') ?>">

        <button type="button" class="btn-reset" id="rekap-reset" hidden>Reset</button>
    </div>

    <div class="rekap-bar">
        <p class="filter-info" id="rekap-info"></p>
        <div class="rekap-actions">
            <button type="button" class="btn-outline" id="rekap-csv">Export CSV</button>
            <button type="button" class="btn-outline" id="rekap-print">Cetak</button>
        </div>
    </div>

    <div class="table-wrap">
        <table class="table cards rekap-table">
            <thead>
                <tr>
                    <th>ID</th><th>Nama</th><th>Jabatan</th>
                    <th class="num">Hadir</th><th class="num">Terlambat</th>
                    <th class="num">Izin/Cuti</th><th class="num">Alfa</th>
                    <th>Kehadiran</th>
                </tr>
            </thead>
            <tbody id="rekap-rows"></tbody>
        </table>
    </div>
</section>

<?php include __DIR__ . '/../../includes/Footer.php'; ?>