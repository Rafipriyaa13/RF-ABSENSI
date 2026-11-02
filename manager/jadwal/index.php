<?php
// Bagian backend AWAL =======================
require_once __DIR__ . '/../../includes/Functions.php';
require_once __DIR__ . '/../../config/database.php';
// require_once __DIR__ . '/../../includes/Auth.php';
// cek_role('manager');   // aktifkan setelah login selesai

date_default_timezone_set('Asia/Jakarta');

const HARI  = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
const BULAN = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

/* ------------------ Helper tampilan (tanggal) ------------------ */

function senin_dari(?string $param): DateTimeImmutable
{
    $d = false;
    if ($param && preg_match('/^\d{4}-\d{2}-\d{2}$/', $param)) {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $param);
    }
    if (!$d) {
        $d = new DateTimeImmutable('today');
    }
    return $d->modify('monday this week');
}

function rentang_minggu(DateTimeImmutable $a): string
{
    $b  = $a->modify('+6 days');
    $ta = (int)$a->format('j');
    $tb = (int)$b->format('j');
    $ba = BULAN[(int)$a->format('n')];
    $bb = BULAN[(int)$b->format('n')];
    $ya = $a->format('Y');
    $yb = $b->format('Y');
    if ($ya !== $yb) return "$ta $ba $ya – $tb $bb $yb";
    if ($ba !== $bb) return "$ta $ba – $tb $bb $ya";
    return "$ta – $tb $bb $ya";
}

/* =====================================================================
 * KONTRAK DATA (untuk backend)
 * Frontend hanya membaca variabel di bawah ini. Ganti nilai DUMMY dengan
 * hasil query database. Struktur/nama kunci jangan diubah.
 *
 *  $senin    DateTimeImmutable  Senin awal minggu yang ditampilkan (dari ?minggu=Y-m-d)
 *  $pegawai  [ ['id'=>int, 'nama'=>str, 'posisi'=>str], ... ]   urut per posisi
 *  $shift    [ ['kode'=>str, 'nama'=>str, 'mulai'=>'HH:MM', 'selesai'=>'HH:MM', 'ket'=>str], ... ]
 *  $cuti     [ id_pegawai => ['Y-m-d' => true, ...] ]           cuti disetujui (sel terkunci)
 *  $jadwal   [ id_pegawai => [0..6 => kode_shift | 'libur'] ]   0 = Senin ... 6 = Minggu
 *  $prev     sama seperti $jadwal, untuk minggu sebelumnya (tombol "Salin dari minggu lalu")
 *  $flash    ['tipe'=>'sukses'|'error', 'pesan'=>str] atau null
 *
 * FORM "Simpan" (POST ke halaman ini, aksi=simpan):
 *   jadwal[ID_PEGAWAI][HARI_0_6] = kode shift / 'libur' / '' (kosong = belum dijadwalkan)
 *   Sel cuti tidak dikirim (terkunci).
 * ===================================================================== */
$senin = senin_dari($_GET['minggu'] ?? null);

$pegawai = [   // DUMMY
    ['id' => 1, 'nama' => 'Siti Rahma',   'posisi' => 'Kasir'],
    ['id' => 2, 'nama' => 'Dewi Lestari', 'posisi' => 'Kasir'],
    ['id' => 3, 'nama' => 'Budi Santoso', 'posisi' => 'Dapur'],
    ['id' => 4, 'nama' => 'Rian Hidayat', 'posisi' => 'Crew'],
];

$shift = 
    $pdo->query("
    SELECT id as id, nama_shift as nama, jam_mulai as mulai, jam_selesai as selesai
    FROM shift;
")->fetchAll();

$KETERANGAN_SHIFT = [
    'pagi'  => 'Shift operasional pegawai outlet pagi',
    'sore' => 'Shift operasional pegawai outlet sore',
    'malam' => 'Shift operasional pegawai outlet malam',
];


$cuti = [      // DUMMY (Dewi cuti Jum-Sab minggu 26 Okt 2026)
    2 => ['2026-10-30' => true, '2026-10-31' => true],
];

$jadwal = [    // DUMMY (sama untuk semua minggu)
    1 => ['pagi', 'pagi', 'pagi', 'libur', 'pagi', 'pagi', 'pagi'],
    2 => ['malam', 'malam', 'malam', 'malam', 'libur', 'libur', 'pagi'],
    3 => ['pagi', 'pagi', 'siang', 'siang', 'libur', 'pagi', 'pagi'],
    4 => ['siang', 'siang', 'libur', 'siang', 'siang', 'malam', 'malam'],
];
$prev = $jadwal;   // DUMMY

$flash = get_flash();

/* ---- Turunan untuk tampilan (tidak perlu diubah) ---- */
$kodeShift = array_column($shift, 'kode');
$kodeValid = array_merge($kodeShift, ['libur']);
$hariIni   = date('Y-m-d');
$mingguIni = senin_dari(null)->format('Y-m-d');

// Ringkasan awal: jumlah orang per shift per hari
$ring = [];
foreach ($pegawai as $p) {
    for ($i = 0; $i < 7; $i++) {
        $v   = $jadwal[$p['id']][$i] ?? '';
        $tgl = $senin->modify("+$i days")->format('Y-m-d');
        if ($v !== '' && in_array($v, $kodeShift, true) && !isset($cuti[$p['id']][$tgl])) {
            $ring[$i][$v] = ($ring[$i][$v] ?? 0) + 1;
        }
    }
}

$pageTitle    = 'Jadwal Shift';
$pageSubtitle = 'Atur konfigurasi shift kerja dan tugaskan pegawai';
$activeMenu   = 'jadwal';

// Bagian backend AKHIR ======================
//============================================

// Bagian halaman frontend ==================================
include __DIR__ . '/../../includes/Header.php'; ?>

<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['tipe']) ?>"><?= e($flash['pesan']) ?></div>
<?php endif; ?>

<!-- ============ Konfigurasi Shift ============ -->
<section class="card">
    <div class="jd-head">
        <h2>Konfigurasi Shift</h2>
        <a class="btn btn-sm" href="tambah.php">+ Tambah</a>
    </div>
    <div class="shift-list">
        <?php foreach ($shift as $s): ?>
            <article class="shift-item s-<?= e($s['id']) ?>">
                <div class="top"><b><?= e('Shift ' . $s['nama']) ?></b><span><?= e($s['mulai']) ?> - <?= e($s['selesai']) ?></span></div>
                <p><?= $ket = $KETERANGAN_SHIFT[strtolower($s['nama'])] ?? ''; ?></p>
                <div class="act"><a class="btn-edit btn-sm" href="edit.php?id=<?= urlencode($s['id']) ?>">Edit</a>
                    <a class="btn-delete btn-sm" href="hapus.php?id=<?= urlencode($s['id']) ?>">Hapus</a></div>
            </article>
        <?php endforeach; ?>
    </div>
    <p class="jd-legend"><b>Libur</b> = hari tidak masuk. <b>Cuti</b> (berikon gembok) berasal dari izin/cuti yang sudah disetujui dan tidak bisa diubah di sini.</p>
</section>

<!-- ============ Penjadwalan Mingguan ============ -->
<section class="card">
    <form method="post" action="" id="jd-form">
        <input type="hidden" name="aksi" value="simpan">

        <div class="jd-head">
            <h2>Penjadwalan Mingguan</h2>
        </div>

        <div class="jd-toolbar">
            <div class="jd-week">
                <a class="icon-btn" href="index.php?minggu=<?= $senin->modify('-7 days')->format('Y-m-d') ?>" aria-label="Minggu sebelumnya">&#9664;&#xFE0E;</a>
                <strong>Minggu <?= e(rentang_minggu($senin)) ?></strong>
                <a class="icon-btn" href="index.php?minggu=<?= $senin->modify('+7 days')->format('Y-m-d') ?>" aria-label="Minggu berikutnya">&#9654;&#xFE0E;</a>
                <?php if ($senin->format('Y-m-d') !== $mingguIni): ?>
                    <a class="btn-ghost" href="index.php">Minggu ini</a>
                <?php endif; ?>
            </div>
            <div class="jd-actions">
                <span class="jd-dirty" id="jd-dirty" aria-live="polite"></span>
                <button type="button" class="btn-ghost" id="btnSalin">Salin dari minggu lalu</button>
                <button type="submit" class="btn">Simpan</button>
            </div>
        </div>
        <p class="jd-msg" id="jd-msg" aria-live="polite"></p>

        <div class="jd-wrap">
            <table class="jd-table" id="jd-table">
                <thead>
                    <tr>
                        <th class="left col-pos">Posisi</th>
                        <th class="left sticky">Pegawai</th>
                        <?php for ($i = 0; $i < 7; $i++):
                            $d = $senin->modify("+$i days"); ?>
                            <th class="<?= $d->format('Y-m-d') === $hariIni ? 'today' : '' ?>">
                                <?= HARI[$i] ?><small><?= $d->format('j') ?></small>
                            </th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $posisiSebelumnya = null;
                    foreach ($pegawai as $p):
                        $awalGrup = $p['posisi'] !== $posisiSebelumnya;
                        $posisiSebelumnya = $p['posisi']; ?>
                        <tr class="<?= $awalGrup ? 'grp' : '' ?>">
                            <td class="left col-pos"><?= $awalGrup ? e($p['posisi']) : '' ?></td>
                            <td class="left sticky">
                                <span class="nm"><?= e($p['nama']) ?></span>
                                <small class="pos-sub"><?= e($p['posisi']) ?></small>
                            </td>
                            <?php for ($i = 0; $i < 7; $i++):
                                $tgl = $senin->modify("+$i days")->format('Y-m-d');
                                if (isset($cuti[$p['id']][$tgl])): ?>
                                    <td>
                                        <span class="lock" title="Cuti disetujui, tidak bisa diubah">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                                            Cuti
                                        </span>
                                    </td>
                                <?php else:
                                    $val = $jadwal[$p['id']][$i] ?? '';
                                    if (!in_array($val, $kodeValid, true)) $val = ''; ?>
                                    <td>
                                        <select class="cell s-<?= e($val === '' ? 'kosong' : $val) ?>"
                                                name="jadwal[<?= (int)$p['id'] ?>][<?= $i ?>]"
                                                data-pid="<?= (int)$p['id'] ?>" data-hari="<?= $i ?>" data-orig="<?= e($val) ?>"
                                                aria-label="Shift <?= e($p['nama']) ?>, <?= HARI[$i] ?>">
                                            <option value="">—</option>
                                            <?php foreach ($shift as $s): ?>
                                                <option value="<?= e($s['kode']) ?>" <?= $val === $s['kode'] ? 'selected' : '' ?>><?= e($s['nama']) ?></option>
                                            <?php endforeach; ?>
                                            <option value="libur" <?= $val === 'libur' ? 'selected' : '' ?>>Libur</option>
                                        </select>
                                    </td>
                                <?php endif;
                            endfor; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="left col-pos"></td>
                        <td class="left sticky">Ringkasan</td>
                        
                        <!-- Masih belum bisa dipakai -->
                        <!-- <?php for ($i = 0; $i < 7; $i++): ?>
                            <td>
                                <div class="sum">
                                    <?php foreach ($shift as $s):
                                        $n = $ring[$i][$s['kode']] ?? 0; ?>
                                        <div class="s-<?= e($s['kode']) ?><?= $n === 0 ? ' zero' : '' ?>"><i></i><?= e($s['nama']) ?> <b data-sum="<?= $i ?>|<?= e($s['kode']) ?>"><?= $n ?></b></div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        <?php endfor; ?> -->
                    </tr>
                </tfoot>
            </table>
        </div>
    </form>
</section>

<script>
(function () {
    var form    = document.getElementById('jd-form');
    var selects = [].slice.call(form.querySelectorAll('select.cell'));
    var dirtyEl = document.getElementById('jd-dirty');
    var msgEl   = document.getElementById('jd-msg');
    var PREV    = <?= json_encode($prev, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var dirty = false, submitting = false;

    // Warna sel mengikuti shift terpilih; tandai sel yang berubah
    function cat(sel) {
        var berubah = sel.value !== sel.dataset.orig;
        sel.className = 'cell s-' + (sel.value || 'kosong') + (berubah ? ' changed' : '');
        return berubah;
    }

    // Hitung ulang ringkasan per hari
    function ringkas() {
        var total = {};
        selects.forEach(function (s) {
            if (!s.value) return;
            var k = s.dataset.hari + '|' + s.value;
            total[k] = (total[k] || 0) + 1;
        });
        [].forEach.call(form.querySelectorAll('[data-sum]'), function (b) {
            var n = total[b.dataset.sum] || 0;
            b.textContent = n;
            b.parentNode.classList.toggle('zero', n === 0);
        });
    }

    function segarkan() {
        var n = 0;
        selects.forEach(function (s) { if (cat(s)) n++; });
        ringkas();
        dirty = n > 0;
        dirtyEl.textContent = n ? n + ' perubahan belum disimpan' : '';
    }

    selects.forEach(function (s) {
        s.addEventListener('change', function () { msgEl.textContent = ''; segarkan(); });
    });

    // Salin jadwal minggu lalu ke form (belum tersimpan sampai klik Simpan)
    document.getElementById('btnSalin').addEventListener('click', function () {
        var terisi = 0;
        selects.forEach(function (s) {
            var v = PREV[s.dataset.pid] && PREV[s.dataset.pid][s.dataset.hari];
            if (!v) return;
            var ada = [].some.call(s.options, function (o) { return o.value === v; });
            if (ada) { s.value = v; terisi++; }
        });
        segarkan();
        msgEl.textContent = terisi
            ? 'Jadwal minggu lalu disalin (' + terisi + ' sel). Periksa, lalu klik Simpan.'
            : 'Minggu lalu belum ada jadwal yang bisa disalin.';
    });

    form.addEventListener('submit', function () { submitting = true; });
    window.addEventListener('beforeunload', function (e) {
        if (dirty && !submitting) { e.preventDefault(); e.returnValue = ''; }
    });

    segarkan();
})();
</script>

<?php include __DIR__ . '/../../includes/Footer.php'; ?>