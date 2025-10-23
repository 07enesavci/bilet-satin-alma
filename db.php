<?php
// db.php — SQLite veritabanı bağlantısı
$DB_PATH = __DIR__ . "/db/app.sqlite";

try {
    $pdo = new PDO("sqlite:" . $DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA foreign_keys = ON;");
} catch (Throwable $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
