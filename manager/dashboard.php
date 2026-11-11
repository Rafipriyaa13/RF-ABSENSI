<?php
// manager/dashboard.php  (FRONTEND SAJA, data masih dummy)
// CSS ada di assets/css/style.css
require_once __DIR__ . '/../includes/Functions.php';

$pageTitle    = 'Dashboard';
$pageSubtitle = 'Ringkasan kondisi hari ini';
$activeMenu   = 'dashboard';
$topAction    = null;

/*
 * ===== DATA DUMMY (backend cukup mengisi dua array ini) =====
 *
 * $shiftHariIni : daftar shift hari ini beserta pegawai yang dijadwalkan.
 *   pegawai.status : 'Hadir' | 'Terlambat' | 'Belum Absen' | 'Izin'
 *   pegawai.jam    : jam check-in (HH:MM) atau null kalau belum absen
 *
 * $perluTindakan : daftar hal yang perlu diurus manager.
 *   warna : 'merah' | 'kuning' | 'biru'
 *
 * Kartu ringkasan dan "Aktivitas Terbaru" dihitung otomatis dari $shiftHariIni.
 */
$shiftHariIni = [
    ['jam' => '07:30 - 12:30', 'pegawai' => [
        ['nama' => 'Siti Aminah',   'jabatan' => 'Cashier',       'status' => 'Hadir',       'jam' => '07:25'],
        ['nama' => 'Budi Santoso',  'jabatan' => 'Kitchen Staff', 'status' => 'Terlambat',   'jam' => '07:41'],
        ['nama' => 'Dewi Lestari',  'jabatan' => 'Crew',          'status' => 'Hadir',       'jam' => '07:28'],
        ['nama' => 'Andi Wijaya',   'jabatan' => 'Store Manager', 'status' => 'Hadir',       'jam' => '07:20'],
    ]],
    ['jam' => '15:30 - 21:30', 'pegawai' => [
        ['nama' => 'Rizky Pratama', 'jabatan' => 'Cashier',       'status' => 'Belum Absen', 'jam' => null],
        ['nama' => 'Ayu Wulandari', 'jabatan' => 'Crew',          'status' => 'Belum Absen', 'jam' => null],
        ['nama' => 'Rina Marlina',  'jabatan' => 'Kitchen Staff', 'status' => 'Belum Absen', 'jam' => null],
        ['nama' => 'Eko Prasetyo',  'jabatan' => 'Crew',          'status' => 'Izin',        'jam' => null],
    ]],
];

$perluTindakan = [
    ['warna' => 'kuning', 'jumlah' => 3, 'judul' => 'Izin / cuti menunggu persetujuan',
     'detail' => 'Siti Aminah, Budi Santoso, Dewi Lestari',
     'link' => '/manager/izin_cuti/index.php', 'tombol' => 'Tinjau'],
    ['warna' => 'merah',  'jumlah' => 2, 'judul' => 'Kontrak berakhir dalam 30 hari',
     'detail' => 'Rizky Pratama (12 Des 2026), Ayu Wulandari (28 Nov 2026)',
     'link' => '/manager/pegawai/index.php', 'tombol' => 'Lihat'],
    ['warna' => 'biru',   'jumlah' => 1, 'judul' => 'Pegawai belum punya kartu RFID',
     'detail' => 'Rina Marlina',
     'link' => '/manager/pegawai/index.php', 'tombol' => 'Daftarkan'],
];

// ---------- Hitung ringkasan dari data shift ----------
$hitung = ['Hadir' => 0, 'Terlambat' => 0, 'Belum Absen' => 0, 'Izin' => 0];
$aktivitas = [];
$dijadwalkan = 0;
foreach ($shiftHariIni as $s) {
    foreach ($s['pegawai'] as $p) {
        $dijadwalkan++;
        $hitung[$p['status']]++;
        if ($p['jam']) {
            $aktivitas[] = $p;
        }
    }
}
usort($aktivitas, fn($a, $b) => strcmp($b['jam'], $a['jam']));   // terbaru dulu
$aktivitas = array_slice($aktivitas, 0, 5);
$totalHadir = $hitung['Hadir'] + $hitung['Terlambat'];

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
                    <strong><?= e($s['jam']) ?></strong>
                    <small><?= count($s['pegawai']) ?> pegawai</small>
                </header>
                <ul>
                    <?php foreach ($s['pegawai'] as $p): ?>
                        <li>
                            <span>
                                <?= e($p['nama']) ?>
                                <small class="muted-text"><?= e($p['jabatan']) ?></small>
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