<?php
$pdo = new PDO(
    'pgsql:host=db;port=5432;dbname=db',
    'db',
    'db',
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);