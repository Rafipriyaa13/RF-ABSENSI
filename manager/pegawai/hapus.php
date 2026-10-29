<?php
require_once __DIR__ . '/../../includes/Functions.php';
require_once __DIR__ . '/../../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    set_flash('error', 'ID pegawai tidak valid.');
    header('Location: /manager/pegawai/index.php');
    exit;
}
try{
$stmtCheck = $pdo->prepare("SELECT nama, kode_pegawai FROM users WHERE id = ?");
$stmtCheck->execute([$id]);
$pegawai = $stmtCheck->fetch();
if (!$pegawai){
    set_flash('error','Data Pegawai tidak ditemukan atau sudah dihapus.');
    exit;
    }
    $stmtDelete = $pdo->prepare("DELETE FROM users WHERE id =?");
    $stmtDelete->execute([$id]);
    set_flash('sukses','Pegawai'.$pegawai['nama'].' (' . $pegawai['kode_pegawai'].') berhasil dihapus dari sistem');
}catch(PDOException $ex){
    if($ex-> getCode()=== '23503'){
        set_flash('error','Gagal menghapus data pegawai: '. $pegawai['nama'].' tidak dapat dihapus dikarenakan masih memiliki riwayat absensi/jadwal. Silahkan ubah statusnya menjadi "Nonaktif" terlebih dahulu melalui menu Edit');
    }else{
        set_flash('error','Gagal menghapus data pegawai:' .$ex->getMessage());
    }
}
header('Location:/manager/pegawai/index.php');
exit;
