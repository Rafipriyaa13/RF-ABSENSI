<?php

$pageTitle    = 'Kepegawaian';
$pageSubtitle = 'Kehadiran, kontrak, dan jadwal shift';
$activeMenu   = 'kepegawaian';
$addButton    = 'Tambah Data';
$addModal     = 'modalTambah';


$idBaru = 'KPG-' . date('Y') . '-001';

$jabatanList = ['Store Manager', 'Cashier', 'Kitchen Staff', 'Crew'];
$outletList  = ['Richeese Factory Ijen',
    'Richeese Factory Dinoyo',
    'Richeese Factory Transmart MX',
    'Richeese Factory Borobudur',
    'Richeese Factory Sunandar Priyo',];


$stats = [
    ['Total Karyawan',   '1,842'],
    ['Hadir Hari Ini',   '1,750'],
    ['Izin/Cuti',        '42'],
    ['Kontrak Berakhir', '15'],
];

$karyawan = [
    ['RF-EMP01', 'Andi Saputra', 'Store Manager', 'Richeese Factory Ijen',    'Hadir', '31 Des 2026'],
    ['RF-EMP02', 'Siti Rahma',   'Cashier',       'Richeese Factory Dinoyo',  'Izin',  '15 Jun 2025'],
    ['RF-EMP03', 'Budi Pratama', 'Kitchen Staff', 'Richeese Factory Borobudur', 'Hadir', '10 Feb 2025'],
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

<!-- Modal Tambah Data -->
<div class="modal" id="modalTambah" role="dialog" aria-modal="true" aria-labelledby="modalTambahTitle" hidden>
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-dialog">
        <form action="tambah.php" method="post" class="modal-form">
            <header class="modal-header">
                <h2 id="modalTambahTitle">Tambah Data Kepegawaian</h2>
                <button type="button" class="icon-btn borderless" data-modal-close aria-label="Tutup">&times;</button>
            </header>

            <div class="modal-body">
                <fieldset>
                    <legend>Identitas Kepegawaian</legend>
                    <p class="hint">Informasi dasar untuk registrasi kepegawaian.</p>
                    <div class="form-grid">
                        <div class="field">
                            <label for="id_karyawan">ID Karyawan</label>
                            <input type="text" id="id_karyawan" name="id_karyawan" value="<?= htmlspecialchars($idBaru) ?>" readonly>
                        </div>
                        <div class="field">
                            <label for="nip">NIP</label>
                            <input type="text" id="nip" name="nip" placeholder="Masukkan NIP" required>
                        </div>
                        <div class="field full">
                            <label for="nama">Nama Lengkap</label>
                            <input type="text" id="nama" name="nama" placeholder="Cth: Ahmad Rizky" required>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Penugasan &amp; Kontrak</legend>
                    <p class="hint">Detail jabatan, outlet, dan masa kontrak.</p>
                    <div class="form-grid">
                        <div class="field">
                            <label for="jabatan">Jabatan</label>
                            <select id="jabatan" name="jabatan" required>
                                <option value="">Pilih Jabatan...</option>
                                <?php foreach ($jabatanList as $j): ?>
                                    <option value="<?= htmlspecialchars($j) ?>"><?= htmlspecialchars($j) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="outlet">Outlet Penugasan</label>
                            <select id="outlet" name="outlet" required>
                                <option value="">Pilih Outlet...</option>
                                <?php foreach ($outletList as $o): ?>
                                    <option value="<?= htmlspecialchars($o) ?>"><?= htmlspecialchars($o) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field full">
                            <label for="kontrak_berakhir">Tanggal Berakhir Kontrak</label>
                            <input type="date" id="kontrak_berakhir" name="kontrak_berakhir" required>
                        </div>
                    </div>
                </fieldset>
            </div>

            <footer class="modal-footer">
                <button type="button" class="btn btn-light" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-red">Simpan Data</button>
            </footer>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/Footer.php'; ?>