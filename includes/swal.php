<?php
// swal.php

/**
 * Fungsi untuk menampilkan alert SweetAlert2
 * 
 * @param string $type    'success', 'error', 'warning', 'info'
 * @param string $title   Judul alert
 * @param mixed  $message Pesan (bisa berupa string atau array)
 */
function render_swal($type, $title, $message) {
    // Jika pesan berupa array (seperti $errors), gabungkan dengan <br>
    if (is_array($message)) {
        $message = implode('<br>', array_map('e', $message));
    } else {
        $message = e($message);
    }

    $title = e($title);

    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        Swal.fire({
            icon: '{$type}',
            title: '{$title}',
            html: '{$message}',
            confirmButtonText: 'OK'
        });
        
    </script>
    ";
}
function show_flash_swal() {
    // Pastikan session sudah aktif
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Cek apakah ada data flash session
    if (isset($_SESSION['flash'])) {
        $tipe  = $_SESSION['flash']['tipe'];
        $pesan = $_SESSION['flash']['pesan'];

        // Sesuaikan tipe untuk SweetAlert (misal: 'sukses' jadi 'success')
        $icon = ($tipe === 'sukses') ? 'success' : $tipe;

        // Cetak skrip SweetAlert
        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: '{$icon}',
                    title: '" . ucfirst($tipe) . "',
                    text: '{$pesan}',
                    confirmButtonText: 'OK'
                });
            });
        </script>
        ";

        // Hapus session setelah ditampilkan agar tidak muncul lagi saat refresh
        unset($_SESSION['flash']);
    }
}
?>