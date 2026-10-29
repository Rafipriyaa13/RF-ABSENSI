<?php
$host = 'localhost';
$port = '5432';
$dbname = 'pbl'; 
$user = 'postgres'; 
$password = '1234'; 

try {
    $pdo = new PDO(
        'pgsql:host=localhost;port=5432;dbname=pbl',
        'postgres',
        '123',
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
