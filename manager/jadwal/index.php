<?php
// Bagian backend AWAL =======================
require_once __DIR__ . '/../../includes/Functions.php';
require_once __DIR__ . '/../../config/database.php';
// require_once __DIR__ . '/../../includes/Auth.php';
// cek_role('manager');   // aktifkan setelah login selesai

date_default_timezone_set('Asia/Jakarta');

const HARI  = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
const BULAN = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

/* ------------------ Helper tanggal ------------------ */

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

/* ------------------ Helper data ------------------ */

// Jadwal satu minggu → [id_pegawai => [0..6 => 'id_shift' | 'libur']]
// (id shift sengaja berupa string, agar cocok dengan value <option> dan JS)
function ambil_jadwal(PDO $pdo, DateTimeImmutable $senin): array
{
    $st = $pdo->prepare("
        SELECT user_id, shift_id, tanggal, status
        FROM jadwal
        WHERE tanggal BETWEEN ? AND ?
    ");
    $st->execute([$senin->format('Y-m-d'), $senin->modify('+6 days')->format('Y-m-d')]);

    $hasil = [];
    foreach ($st->fetchAll() as $r) {
        $hari = (int)$senin->diff(new DateTimeImmutable(substr($r['tanggal'], 0, 10)))->days;   // 0 = Senin
        if ($r['status'] === 'libur')    $nilai = 'libur';
        elseif ($r['shift_id'] !== null) $nilai = (string)$r['shift_id'];
        else                             continue;
        $hasil[(int)$r['user_id']][$hari] = $nilai;
    }
    return $hasil;
}

/* ===================== PROSES SIMPAN (POST) =====================
 * Form: aksi=simpan, minggu=Y-m-d, jadwal[ID_PEGAWAI][HARI_0_6] = id_shift | 'libur' | ''
 * ('' = belum dijadwalkan → baris jadwal dihapus)
 * ================================================================ */
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'simpan') {
    $mingguPost = senin_dari($_POST['minggu'] ?? null);
    $sen = $mingguPost->format('Y-m-d');

    // 1) Tolak minggu yang sudah lewat
    if ($sen < senin_dari(null)->format('Y-m-d')) {
        $errors[] = 'Jadwal minggu yang sudah lewat tidak bisa diubah.';
    }

    // 2) Data pembanding dari database (jangan percaya kiriman form)
    $idPegawai = array_map('intval', $pdo->query("SELECT id FROM users WHERE status = 'aktif'")->fetchAll(PDO::FETCH_COLUMN));
    $idShift   = array_map('intval', $pdo->query("SELECT id FROM shift")->fetchAll(PDO::FETCH_COLUMN));
    $cutiPost  = [];   // TODO: isi dari izin_cuti setelah modul cuti jadi

    // 3) Periksa tiap sel
    $simpan = [];   // [user_id, shift_id|null, tanggal, status]
    $hapus  = [];   // [user_id, tanggal]

    if (!$errors) {
        $kiriman = $_POST['jadwal'] ?? null;
        if (!is_array($kiriman)) {
            $errors[] = 'Data jadwal tidak valid.';
        } else {
            foreach ($kiriman as $pid => $perHari) {
                $pid = (int)$pid;
                if (!in_array($pid, $idPegawai, true) || !is_array($perHari)) continue;

                foreach ($perHari as $h => $nilai) {
                    if (!ctype_digit((string)$h) || (int)$h > 6 || !is_string($nilai)) {
                        $errors[] = 'Data jadwal tidak valid.';
                        break 2;
                    }
                    $tgl = $mingguPost->modify('+' . (int)$h . ' days')->format('Y-m-d');
                    if (isset($cutiPost[$pid][$tgl])) continue;   // sel cuti tidak disentuh

                    $nilai = trim($nilai);
                    if ($nilai === '') {
                        $hapus[] = [$pid, $tgl];
                    } elseif ($nilai === 'libur') {
                        $simpan[] = [$pid, null, $tgl, 'libur'];
                    } elseif (ctype_digit($nilai) && in_array((int)$nilai, $idShift, true)) {
                        $simpan[] = [$pid, (int)$nilai, $tgl, 'terjadwal'];
                    } else {
                        $errors[] = 'Data jadwal tidak valid.';
                        break 2;
                    }
                }
            }
        }
    }

    // 4) Simpan dalam satu transaksi
    if (!$errors) {
        $idManager = 5; // MANAGER SEMENTARA   
        try {
            $pdo->beginTransaction();

            $up = $pdo->prepare("
                INSERT INTO jadwal (user_id, shift_id, tanggal, status, created_by)
                VALUES (?, ?, ?, ?, ?)
                ON CONFLICT (user_id, tanggal) DO UPDATE
                SET shift_id = EXCLUDED.shift_id, status = EXCLUDED.status, created_by = EXCLUDED.created_by
            ");
            foreach ($simpan as [$pid, $sid, $tgl, $status]) {
                $up->execute([$pid, $sid, $tgl, $status, $idManager]);
            }

            $del = $pdo->prepare("DELETE FROM jadwal WHERE user_id = ? AND tanggal = ?");
            foreach ($hapus as [$pid, $tgl]) {
                $del->execute([$pid, $tgl]);
            }

            $pdo->commit();

            set_flash('sukses', 'Jadwal minggu ' . rentang_minggu($mingguPost) . ' berhasil disimpan.');
            header('Location: index.php?minggu=' . $sen);
            exit;
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('simpan jadwal gagal [' . $ex->getCode() . ']: ' . $ex->getMessage());
            $errors[] = 'Terjadi kesalahan saat menyimpan jadwal.';
        }
    }
}

/* ===================== DATA UNTUK TAMPILAN (GET) ===================== */
$senin = senin_dari($_GET['minggu'] ?? null);

$karyawan = $pdo->query("
    SELECT u.id, u.kode_pegawai, u.nama, u.jabatan,
           COALESCE(NULLIF(TRIM(u.jabatan), ''), 'Belum Ditentukan') AS jabatan_grup
    FROM users u
    WHERE u.status = 'aktif'
    ORDER BY jabatan_grup, u.nama
")->fetchAll();

$shift = $pdo->query("
    SELECT id, nama_shift AS nama,
           to_char(jam_mulai, 'HH24:MI')   AS mulai,
           to_char(jam_selesai, 'HH24:MI') AS selesai
    FROM shift
    ORDER BY id
")->fetchAll();

$KETERANGAN_SHIFT = [
    'pagi'  => 'Shift operasional pegawai outlet pagi',
    'sore'  => 'Shift operasional pegawai outlet sore',
    'malam' => 'Shift operasional pegawai outlet malam',
];

$jadwal = ambil_jadwal($pdo, $senin);
$prev   = ambil_jadwal($pdo, $senin->modify('-7 days'));
$cuti   = [];  

$flash = get_flash();

/* ---- Turunan untuk tampilan ---- */
$kodeShift = array_map('strval', array_column($shift, 'id'));
$kodeValid = array_merge($kodeShift, ['libur']);
$hariIni   = date('Y-m-d');
$mingguIni = senin_dari(null)->format('Y-m-d');

// Ringkasan awal: jumlah orang per shift per hari
$ring = [];
foreach ($karyawan as $p) {
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

<?php if (!empty($errors)): ?>
    <ul class="form-errors">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
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
                <p><?= e($KETERANGAN_SHIFT[strtolower($s['nama'])] ?? '') ?></p>
                <div class="act"><a class="btn-edit btn-sm" href="edit.php?id=<?= (int)$s['id'] ?>">Edit</a>
                    <a class="btn-delete btn-sm" href="hapus.php?id=<?= (int)$s['id'] ?>">Hapus</a></div>
            </article>
        <?php endforeach; ?>
    </div>
    <p class="jd-legend"><b>Libur</b> = hari tidak masuk. <b>Cuti</b> (berikon gembok) berasal dari izin/cuti yang sudah disetujui dan tidak bisa diubah di sini.</p>
</section>

<!-- ============ Penjadwalan Mingguan ============ -->
<section class="card">
    <form method="post" action="" id="jd-form">
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="minggu" value="<?= e($senin->format('Y-m-d')) ?>">

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
                    foreach ($karyawan as $p):
                        $awalGrup = $p['jabatan_grup'] !== $posisiSebelumnya;
                        $posisiSebelumnya = $p['jabatan_grup']; ?>
                        <tr class="<?= $awalGrup ? 'grp' : '' ?>">
                            <td class="left col-pos"><?= $awalGrup ? e($p['jabatan_grup']) : '' ?></td>
                            <td class="left sticky">
                                <span class="nm"><?= e($p['nama']) ?></span>
                                <small class="pos-sub"><?= e($p['jabatan_grup']) ?></small>
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
                                                <option value="<?= e($s['id']) ?>" <?= $val === (string)$s['id'] ? 'selected' : '' ?>><?= e($s['nama']) ?></option>
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

                        <?php for ($i = 0; $i < 7; $i++): ?>
                            <td>
                                <div class="sum">
                                    <?php foreach ($shift as $s):
                                        $n = $ring[$i][(string)$s['id']] ?? 0; ?>
                                        <div class="s-<?= e($s['id']) ?><?= $n === 0 ? ' zero' : '' ?>"><i></i><?= e($s['nama']) ?> <b data-sum="<?= $i ?>|<?= e($s['id']) ?>"><?= $n ?></b></div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        <?php endfor; ?>
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