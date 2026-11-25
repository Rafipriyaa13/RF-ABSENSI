<?php
// Bagian backend AWAL =======================
require_once __DIR__ . '/../../includes/Functions.php';
require_once __DIR__ . '/../../config/database.php';
// require_once __DIR__ . '/../includes/Auth.php';
// cek_role('pegawai');   // aktifkan setelah login selesai

date_default_timezone_set('Asia/Jakarta');

// PEGAWAI SEMENTARA: ganti dengan $_SESSION['user_id'] setelah login selesai
$idPegawai = (int)($_SESSION['user_id'] ?? 1);

$st = $pdo->prepare("SELECT nama FROM users WHERE id = ?");
$st->execute([$idPegawai]);
$pegawai = $st->fetch() ?: ['nama' => 'Pegawai'];

$flash = get_flash();
$old   = $_SESSION['old_izin'] ?? null;
unset($_SESSION['old_izin']);

$hariIni    = new DateTimeImmutable('today');
$tglHariIni = $hariIni->format('Y-m-d');
$BULAN      = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

$jenisOpsi  = ['izin' => 'Izin', 'sakit' => 'Sakit', 'cuti' => 'Cuti'];
$jenisPilih = $old['jenis'] ?? 'izin';
$mulaiIsi   = $old['tanggal_mulai'] ?? $tglHariIni;
$selesaiIsi = $old['tanggal_selesai'] ?? $tglHariIni;
$alasanIsi  = $old['alasan'] ?? '';

$pageTitle    = 'Pengajuan Izin & Cuti';
$pageSubtitle = 'Formulir permohonan izin, sakit, atau cuti karyawan';
$activeMenu   = 'ajukan';
$sidebarRole  = 'pegawai';
$topDate      = 'Hari ini: ' . $hariIni->format('j') . ' ' . $BULAN[(int)$hariIni->format('n')] . ' ' . $hariIni->format('Y');
$namaLogin    = $pegawai['nama'];
// Bagian backend AKHIR ======================

// Bagian halaman frontend ==================================
include __DIR__ . '/../../includes/Header.php';
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['tipe']) ?>"><?= e($flash['pesan']) ?></div>
<?php endif; ?>

<section class="pf-grid">
    <!-- Formulir pengajuan izin / cuti -->
    <article class="card dcard">
        <h2>Formulir Pengajuan Izin / Cuti</h2>
        <form method="post" action="/pegawai/izin_cuti/simpan.php" class="pf-form" id="pf-form" enctype="multipart/form-data">
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

            <div class="field">
                <label for="lampiran">
                    Surat / Dokumen Pendukung
                    <span class="up-tag up-tag-opt" id="up-opsional">Opsional</span>
                    <span class="up-tag" id="up-wajib" hidden>Wajib untuk Sakit</span>
                </label>

                <div class="up-drop" id="up-drop" role="button" tabindex="0" aria-label="Pilih file surat atau dokumen">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5M12 3v12"/></svg>
                    <span><b>Pilih file</b> atau tarik ke sini</span>
                    <small>Surat dokter / surat izin &middot; JPG, PNG, atau PDF &middot; maks. 2 MB</small>
                </div>
                <input type="file" id="lampiran" name="lampiran" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" hidden>

                <div class="up-file" id="up-file" hidden>
                    <img id="up-thumb" alt="" hidden>
                    <span class="up-pdf" id="up-pdf" hidden>PDF</span>
                    <div class="up-info">
                        <b id="up-nama"></b>
                        <small id="up-ukuran"></small>
                    </div>
                    <button type="button" class="up-remove" id="up-remove" aria-label="Hapus file">&times;</button>
                </div>
                <p class="up-error" id="up-error" role="alert" hidden></p>
            </div>

            <button type="submit" class="btn pf-submit">Ajukan Permohonan</button>
        </form>
    </article>
</section>

<script>
(function () {
    var mulai = document.getElementById('tanggal_mulai');
    var selesai = document.getElementById('tanggal_selesai');
    mulai.addEventListener('change', function () {
        selesai.min = mulai.value;
        if (selesai.value < mulai.value) selesai.value = mulai.value;
    });
})();
</script>

<script>
// Unggah surat / dokumen pendukung
(function () {
    var form    = document.getElementById('pf-form');
    var jenis   = document.getElementById('jenis');
    var file    = document.getElementById('lampiran');
    var drop    = document.getElementById('up-drop');
    var box     = document.getElementById('up-file');
    var thumb   = document.getElementById('up-thumb');
    var pdf     = document.getElementById('up-pdf');
    var nama    = document.getElementById('up-nama');
    var ukuran  = document.getElementById('up-ukuran');
    var wajib   = document.getElementById('up-wajib');
    var opsi    = document.getElementById('up-opsional');
    var galat   = document.getElementById('up-error');
    var MAKS    = 2 * 1024 * 1024;
    var TIPE    = ['image/jpeg', 'image/png', 'application/pdf'];

    function ukur(b) {
        return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
    }
    function tampilGalat(teks) {
        galat.textContent = teks;
        galat.hidden = !teks;
    }
    function kosongkan() {
        file.value = '';
        box.hidden = true;
        thumb.hidden = pdf.hidden = true;
        if (thumb.src && thumb.src.indexOf('blob:') === 0) URL.revokeObjectURL(thumb.src);
        thumb.removeAttribute('src');
    }
    function pilih(f) {
        if (!f) { kosongkan(); return; }
        if (TIPE.indexOf(f.type) === -1) { kosongkan(); tampilGalat('Format file harus JPG, PNG, atau PDF.'); return; }
        if (f.size > MAKS)               { kosongkan(); tampilGalat('Ukuran file maksimal 2 MB.'); return; }

        tampilGalat('');
        nama.textContent = f.name;
        ukuran.textContent = ukur(f.size);
        box.hidden = false;
        if (f.type === 'application/pdf') {
            thumb.hidden = true;
            pdf.hidden = false;
        } else {
            pdf.hidden = true;
            thumb.src = URL.createObjectURL(f);
            thumb.hidden = false;
        }
    }

    function perbaruiTipe() {
        var sakit = jenis.value === 'sakit';
        wajib.hidden = !sakit;
        opsi.hidden = sakit;
        if (!sakit) tampilGalat('');
    }
    jenis.addEventListener('change', perbaruiTipe);

    drop.addEventListener('click', function () { file.click(); });
    drop.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); file.click(); }
    });
    file.addEventListener('change', function () { pilih(file.files[0]); });
    document.getElementById('up-remove').addEventListener('click', kosongkan);

    ['dragenter', 'dragover'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('over'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('over'); });
    });
    drop.addEventListener('drop', function (e) {
        if (!e.dataTransfer.files.length) return;
        file.files = e.dataTransfer.files;
        pilih(file.files[0]);
    });

    form.addEventListener('submit', function (e) {
        if (jenis.value === 'sakit' && !file.files.length) {
            e.preventDefault();
            tampilGalat('Pengajuan sakit wajib melampirkan surat dokter.');
            drop.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    perbaruiTipe();
})();
</script>

<?php include __DIR__ . '/../../includes/Footer.php'; ?>