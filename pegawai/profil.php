<?php
// Bagian backend AWAL =======================
require_once __DIR__ . '/../includes/Functions.php';
require_once __DIR__ . '/../config/database.php';
// require_once __DIR__ . '/../includes/Auth.php';
// cek_role('pegawai');   // aktifkan setelah login selesai

date_default_timezone_set('Asia/Jakarta');

/* =====================================================================
 * DATA YANG DIPAKAI HALAMAN
 *  $pegawai  data akun dari tabel users
 *  $old      isian form pengajuan sebelumnya (jika validasi gagal)
 *  Form pengajuan dikirim ke pegawai/izin_cuti/simpan.php
 * ===================================================================== */

// PEGAWAI SEMENTARA: ganti dengan $_SESSION['user_id'] setelah login selesai
$idPegawai = (int)($_SESSION['user_id'] ?? 1);

$st = $pdo->prepare("
    SELECT nama, kode_pegawai, email, jabatan, kontrak_mulai, kontrak_selesai
    FROM users WHERE id = ?
");
$st->execute([$idPegawai]);
$pegawai = $st->fetch() ?: [
    'nama' => 'Pegawai', 'kode_pegawai' => null, 'email' => null,
    'jabatan' => null, 'kontrak_mulai' => null, 'kontrak_selesai' => null,
];

$flash = get_flash();
$old   = $_SESSION['old_izin'] ?? null;
unset($_SESSION['old_izin']);

$hariIni   = new DateTimeImmutable('today');
$tglHariIni = $hariIni->format('Y-m-d');
$BULAN     = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

$jenisOpsi = ['izin' => 'Izin', 'sakit' => 'Sakit', 'cuti' => 'Cuti'];
$jenisPilih = $old['jenis'] ?? 'izin';
$mulaiIsi   = $old['tanggal_mulai'] ?? $tglHariIni;
$selesaiIsi = $old['tanggal_selesai'] ?? $tglHariIni;
$alasanIsi  = $old['alasan'] ?? '';

$baris = [
    'Jabatan'       => $pegawai['jabatan'] ?: '-',
    'Email Kerja'   => $pegawai['email'] ?: '-',
    'Kontrak Mulai' => tgl_indo($pegawai['kontrak_mulai']) ?: '-',
    'Kontrak Selesai' => tgl_indo($pegawai['kontrak_selesai']) ?: '-',
];

$pageTitle    = 'Profil Saya';
$pageSubtitle = 'Informasi akun dan pengajuan izin karyawan';
$activeMenu   = 'profil';
$sidebarRole  = 'pegawai';
$topDate      = 'Hari ini: ' . $hariIni->format('j') . ' ' . $BULAN[(int)$hariIni->format('n')] . ' ' . $hariIni->format('Y');
$namaLogin    = $pegawai['nama'];
// Bagian backend AKHIR ======================
//============================================

// Bagian halaman frontend ==================================
include __DIR__ . '/../includes/Header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['tipe']) ?>"><?= e($flash['pesan']) ?></div>
<?php endif; ?>

<section class="pf-grid">
    <!-- Data pribadi -->
    <article class="card dcard">
        <h2>Data Pribadi Pegawai</h2>
        <div class="pf-id">
            <div class="pf-avatar" aria-hidden="true">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-6 8-6s8 2 8 6"/></svg>
            </div>
            <div>
                <b><?= e($pegawai['nama']) ?></b>
                <small>NIP: <?= e($pegawai['kode_pegawai'] ?: '-') ?></small>
            </div>
        </div>
        <ul class="kv-list">
            <?php foreach ($baris as $label => $nilai): ?>
                <li><span><?= e($label) ?></span><b><?= e($nilai) ?></b></li>
            <?php endforeach; ?>
        </ul>
    </article>

    <!-- Formulir pengajuan izin / cuti -->
    <article class="card dcard">
        <h2>Formulir Pengajuan Izin / Cuti</h2>
        <form method="post" action="/pegawai/izin_cuti/simpan.php" class="pf-form">
            <div class="field">
                <label for="jenis">Tipe Pengajuan</label>
                <select id="jenis" name="jenis" required>
                    <?php foreach ($jenisOpsi as $nilai => $label): ?>
                        <option value="<?= e($nilai) ?>" <?= $jenisPilih === $nilai ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="tanggal_mulai">Tanggal Mulai s/d Selesai</label>
                <div class="pf-range">
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="<?= e($mulaiIsi) ?>" required>
                    <span>s/d</span>
                    <input type="date" id="tanggal_selesai" name="tanggal_selesai" value="<?= e($selesaiIsi) ?>" min="<?= e($mulaiIsi) ?>" required>
                </div>
            </div>

            <div class="field">
                <label for="alasan">Alasan Detail</label>
                <textarea id="alasan" name="alasan" rows="4" maxlength="500" required placeholder="Masukkan keterangan medis atau keperluan mendesak..."><?= e($alasanIsi) ?></textarea>
            </div>

            <button type="submit" class="btn pf-submit">Ajukan Permohonan</button>
        </form>
    </article>
</section>

<script>
(function () {
    var mulai = document.getElementById('tanggal_mulai');
    var selesai = document.getElementById('tanggal_selesai');
    // Tanggal selesai tidak boleh lebih awal dari tanggal mulai
    mulai.addEventListener('change', function () {
        selesai.min = mulai.value;
        if (selesai.value < mulai.value) selesai.value = mulai.value;
    });
})();
</script>

<?php include __DIR__ . '/../includes/Footer.php'; ?>
