<?php
// manager/pegawai/index.php
$pageTitle    = 'Kepegawaian';
$pageSubtitle = 'Kehadiran, kontrak, dan jadwal shift';
$activeMenu   = 'kepegawaian';

// Data contoh. Ganti dengan query dari database (config/database.php).
$stats = [
    ['Total Karyawan',   '1,842'],
    ['Hadir Hari Ini',   '1,750'],
    ['Izin/Cuti',        '42'],
    ['Kontrak Berakhir', '15'],
];

$karyawan = [
    ['RF-EMP01', 'Andi Saputra', 'Store Manager', 'BIP Bandung',     'Hadir', '31 Des 2026'],
    ['RF-EMP02', 'Siti Rahma',   'Cashier',       'Jakarta Blok M',  'Izin',  '15 Jun 2025'],
    ['RF-EMP03', 'Budi Pratama', 'Kitchen Staff', 'Surabaya Gubeng', 'Hadir', '10 Feb 2025'],
];

$hari  = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
$shift = [
    ['06:00 - 14:00', 'Andi, Siti', 'Andi, Budi', 'Siti, Dewi', 'Andi, Dewi', 'Siti, Budi'],
    ['14:00 - 22:00', 'Budi, Dewi', 'Siti, Dewi', 'Andi, Budi', 'Siti, Budi', 'Andi, Dewi'],
];

include __DIR__ . '/../../includes/Header.php';
?>

<section class="stats">
    <?php foreach ($stats as [$label, $value]): ?>
        <article class="card stat">
            <span class="stat-label"><?= $label ?></span>
            <p class="stat-value"><?= $value ?> <small>Orang</small></p>
        </article>
    <?php endforeach; ?>
</section>

<section class="card panel">
    <h2>Daftar Karyawan</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>ID</th><th>Nama</th><th>Jabatan</th><th>Outlet</th><th>Kehadiran</th><th>Kontrak</th></tr>
            </thead>
            <tbody>
                <?php foreach ($karyawan as [$id, $nama, $jabatan, $outlet, $hadir, $kontrak]): ?>
                    <tr>
                        <td class="strong"><?= htmlspecialchars($id) ?></td>
                        <td><?= htmlspecialchars($nama) ?></td>
                        <td><?= htmlspecialchars($jabatan) ?></td>
                        <td><?= htmlspecialchars($outlet) ?></td>
                        <td><span class="badge <?= strtolower($hadir) ?>"><?= htmlspecialchars($hadir) ?></span></td>
                        <td><?= htmlspecialchars($kontrak) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card panel">
    <h2>Jadwal Shift</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Jam Shift</th><?php foreach ($hari as $h): ?><th><?= $h ?></th><?php endforeach; ?></tr>
            </thead>
            <tbody>
                <?php foreach ($shift as $row): ?>
                    <tr>
                        <?php foreach ($row as $i => $cell): ?>
                            <td class="<?= $i === 0 ? 'strong' : '' ?>"><?= htmlspecialchars($cell) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include __DIR__ . '/../../includes/Footer.php'; ?>