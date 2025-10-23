<?php
// db/db.php — SQLite veritabanı bağlantısı

// Hata raporlamayı aç
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Bu dosya /var/www/db/db.php içinde olduğu için,
// __DIR__ zaten /var/www/db demektir.
// app.sqlite dosyasını da aynı klasörde arar.
$DB_PATH = __DIR__ . "/app.sqlite";

try {
    // $pdo değişkeni burada oluşturuluyor
    $pdo = new PDO("sqlite:" . $DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA foreign_keys = ON;");
} catch (Throwable $e) {
    die("Veritabanı bağlantı hatası (db.php): " . $e->getMessage());
}

 ?>
