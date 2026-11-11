<?php
// manager/izin_cuti/index.php  (FRONTEND SAJA, data masih dummy)
// CSS ada di assets/css/style.css, JS ada di assets/js/app.js
require_once __DIR__ . '/../../includes/Functions.php';

$pageTitle    = 'Izin / Cuti';
$pageSubtitle = 'Tinjau dan setujui pengajuan izin & cuti pegawai';
$activeMenu   = 'izin';
$topAction    = null;

// Data dummy. Nanti ganti dengan hasil query database (kolom yang sama).
$izin = [
    ['id' => 1, 'kode' => 'RF-EMP02', 'nama' => 'Siti Aminah',   'jabatan' => 'Cashier',       'jenis' => 'Cuti',  'mulai' => '2026-11-12', 'selesai' => '2026-11-14', 'alasan' => 'Menghadiri pernikahan saudara di luar kota.', 'status' => 'menunggu',  'surat' => true],
    ['id' => 2, 'kode' => 'RF-EMP03', 'nama' => 'Budi Santoso',  'jabatan' => 'Kitchen Staff', 'jenis' => 'Sakit', 'mulai' => '2026-11-10', 'selesai' => '2026-11-11', 'alasan' => 'Demam tinggi, sudah ke dokter.',              'status' => 'menunggu',  'surat' => true],
    ['id' => 3, 'kode' => 'RF-EMP04', 'nama' => 'Dewi Lestari',  'jabatan' => 'Crew',          'jenis' => 'Izin',  'mulai' => '2026-11-15', 'selesai' => '2026-11-15', 'alasan' => 'Mengurus dokumen keluarga.',                  'status' => 'menunggu',  'surat' => false],
    ['id' => 4, 'kode' => 'RF-EMP05', 'nama' => 'Rizky Pratama', 'jabatan' => 'Cashier',       'jenis' => 'Cuti',  'mulai' => '2026-11-03', 'selesai' => '2026-11-07', 'alasan' => 'Liburan keluarga.',                           'status' => 'disetujui', 'surat' => true],
    ['id' => 5, 'kode' => 'RF-EMP06', 'nama' => 'Ayu Wulandari', 'jabatan' => 'Crew',          'jenis' => 'Izin',  'mulai' => '2026-10-28', 'selesai' => '2026-10-29', 'alasan' => 'Keperluan pribadi mendadak.',                 'status' => 'ditolak',   'surat' => true],
];

include __DIR__ . '/../../includes/Header.php';
?>

<div id="toast" class="toast" role="status" aria-live="polite"></div>

<section class="stats" id="izin-stats"></section>

<section class="card panel izin" id="izin-panel" data-izin="<?= e(json_encode($izin, JSON_UNESCAPED_UNICODE)) ?>">
    <h2>Pengajuan Izin / Cuti</h2>

    <div class="izin-tabs" id="izin-tabs">
        <button type="button" data-status="semua" class="active">Semua</button>
        <button type="button" data-status="menunggu">Menunggu</button>
        <button type="button" data-status="disetujui">Disetujui</button>
        <button type="button" data-status="ditolak">Ditolak</button>
    </div>

    <div class="izin-search">
        <input type="search" id="izin-q" placeholder="Cari nama atau ID pegawai..." autocomplete="off">
    </div>

    <div class="table-wrap">
        <table class="table cards">
            <thead>
                <tr>
                    <th>Pegawai</th><th>Jenis</th><th>Periode</th><th>Durasi</th>
                    <th>Dokumen</th><th>Status</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody id="izin-rows"></tbody>
        </table>
    </div>
</section>

<!-- Pop-up lihat dokumen -->
<dialog id="izin-viewer" class="viewer">
    <div class="viewer-head">
        <div>
            <strong id="vNama"></strong>
            <div class="muted" id="vInfo"></div>
        </div>
        <button type="button" id="vClose" aria-label="Tutup">&times;</button>
    </div>
    <p class="viewer-alasan" id="vAlasan"></p>
    <div class="viewer-body" id="vBody"></div>
    <div class="viewer-foot">
        <span class="muted" id="vStatus"></span>
        <span class="spacer"></span>
        <button type="button" class="btn-aksi tolak" id="vTolak">Tolak</button>
        <button type="button" class="btn-aksi setuju" id="vSetuju">Setujui</button>
    </div>
</dialog>

<?php include __DIR__ . '/../../includes/Footer.php'; ?>