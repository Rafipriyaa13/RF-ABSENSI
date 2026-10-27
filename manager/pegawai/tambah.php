<?php
// manager/pegawai/tambah.php
require_once __DIR__ . '/../../includes/Functions.php';
require_once __DIR__ . '/../../config/database.php';

$pageTitle    = 'Tambah Pegawai';
$pageSubtitle = 'Menambahkan data pegawai baru ke sistem';
$activeMenu   = 'kepegawaian';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama            = trim($_POST['nama']);
    $email           = trim($_POST['email']);
    $role            = $_POST['role'];
    $jabatan         = trim($_POST['jabatan']);
    $kontrak_mulai   = !empty($_POST['kontrak_mulai']) ? $_POST['kontrak_mulai'] : null;
    $kontrak_selesai = !empty($_POST['kontrak_selesai']) ? $_POST['kontrak_selesai'] : null;
    $rfid_uid        = !empty($_POST['rfid_uid']) ? trim($_POST['rfid_uid']) : null;
    $password_hash   = null;
    $status          = 'aktif';

    try {
        $stmt = $pdo->query("SELECT kode_pegawai FROM users WHERE kode_pegawai LIKE 'RF-EMP%' ORDER BY id DESC LIMIT 1");
        $lastKode = $stmt->fetchColumn();

        if ($lastKode) {
            $number = (int) substr($lastKode, 6);
            $newNumber = $number + 1;
        } else {
            $newNumber = 1;
        }

        $kode_pegawai = 'RF-EMP' . str_pad($newNumber, 2, '0', STR_PAD_LEFT);

        $sql = "INSERT INTO users (kode_pegawai, nama, email, password_hash, role, jabatan, kontrak_mulai, kontrak_selesai, rfid_uid, status) 
                VALUES (:kode_pegawai, :nama, :email, :password_hash, :role, :jabatan, :kontrak_mulai, :kontrak_selesai, :rfid_uid, :status)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':kode_pegawai'    => $kode_pegawai,
            ':nama'            => $nama,
            ':email'           => $email,
            ':password_hash'   => $password_hash,
            ':role'            => $role,
            ':jabatan'         => $jabatan,
            ':kontrak_mulai'   => $kontrak_mulai,
            ':kontrak_selesai' => $kontrak_selesai,
            ':rfid_uid'        => $rfid_uid,
            ':status'          => $status
        ]);

        set_flash('sukses', 'Data pegawai berhasil ditambahkan dengan ID ' . $kode_pegawai);
        header('Location: /manager/pegawai/index.php');
        exit;
    } catch (PDOException $e) {
        $error = "Gagal menyimpan data: " . $e->getMessage();
    }
}

include __DIR__ . '/../../includes/Header.php';
?>

<?php if (isset($error)): ?>
    <div class="alert alert-error" style="margin-bottom: 20px;"><?= e($error) ?></div>
<?php endif; ?>

<section class="card panel" style="max-width: 600px;">
    <h2>Form Tambah Pegawai</h2>
    <div style="padding: 24px;">
        <form action="" method="POST" style="display: grid; gap: 16px;">

            <div>
                <label style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px;">Nama Lengkap</label>
                <input type="text" name="nama" required style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px; outline: none; box-sizing: border-box;">
            </div>

            <div>
                <label style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px;">Email</label>
                <input type="email" name="email" required style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px; outline: none; box-sizing: border-box;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px;">Role Akses</label>
                    <select name="role" required style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px; outline: none; box-sizing: border-box; background: #fff;">
                        <option value="pegawai">Pegawai</option>
                        <option value="manager">Manager</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px;">Jabatan (ex: Cashier)</label>
                    <input type="text" name="jabatan" style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px; outline: none; box-sizing: border-box;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px;">Tanggal Mulai Kontrak</label>
                    <input type="date" name="kontrak_mulai" style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px; outline: none; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px;">Tanggal Selesai Kontrak</label>
                    <input type="date" name="kontrak_selesai" style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px; outline: none; box-sizing: border-box;">
                </div>
            </div>

            <div>
                <label style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px;">RFID UID (Opsional)</label>
                <input type="text" name="rfid_uid" placeholder="Tap kartu atau biarkan kosong" style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: 6px; outline: none; box-sizing: border-box;">
            </div>

            <p style="font-size: 12px; color: var(--muted); margin-top: -4px;">*Kode Pegawai (RF-EMP..) dibuat otomatis. Password bawaan akan dikosongkan.</p>

            <div style="display: flex; gap: 12px; justify-content: flex-end; border-top: 1px solid var(--line); padding-top: 16px; margin-top: 4px;">
                <a href="/manager/pegawai/index.php" style="padding: 10px 16px; border: 1px solid var(--line); border-radius: 8px; color: var(--ink); background: #fff;">Batal</a>
                <button type="submit" class="btn">Simpan Pegawai</button>
            </div>

        </form>
    </div>
</section>

<?php include __DIR__ . '/../../includes/Footer.php'; ?>