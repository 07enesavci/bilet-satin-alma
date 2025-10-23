<?php
$pdo = new PDO("sqlite:" . __DIR__ . "/db/app.sqlite");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$schema = file_get_contents(__DIR__ . "/db/schema.sql");
$pdo->exec($schema);
echo "✅ Veritabanı oluşturuldu\n";
