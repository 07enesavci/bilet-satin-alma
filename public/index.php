<?php
require __DIR__ . "/partials/header.php";
require __DIR__ . "/partials/navbar.php";
require __DIR__ . "/../db.php";

$girisYapildi = isset($_SESSION['id']);
$adSoyad = $_SESSION['full_name'] ?? '';
$rol = $_SESSION['role'] ?? '';
$bakiye = $_SESSION['balance'] ?? 0;

$sonuclar = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kalkis = trim($_POST['departure_city'] ?? '');
    $varis = trim($_POST['destination_city'] ?? '');
    $sql = "SELECT * FROM Trips WHERE 1=1";
    $params = [];

    if ($kalkis) { $sql .= " AND departure_city LIKE ?"; $params[] = "%$kalkis%"; }
    if ($varis)  { $sql .= " AND destination_city LIKE ?"; $params[] = "%$varis%"; }

    $sql .= " ORDER BY departure_time ASC";
    $sorgu = $pdo->prepare($sql);
    $sorgu->execute($params);
} else {
    $sorgu = $pdo->query("SELECT * FROM Trips ORDER BY departure_time ASC LIMIT 20");
}
$sonuclar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
  <h3 class="text-center mb-4">Sefer Ara</h3>

  <form method="POST" class="card p-4 shadow-sm mb-4 mx-auto" style="max-width:600px;">
    <div class="row g-3 align-items-end">
      <div class="col-md-5">
        <label class="form-label">Kalkış Şehri</label>
        <input type="text" name="departure_city" class="form-control" placeholder="Örn: Ankara"
               value="<?= htmlspecialchars($_POST['departure_city'] ?? '') ?>">
      </div>
      <div class="col-md-5">
        <label class="form-label">Varış Şehri</label>
        <input type="text" name="destination_city" class="form-control" placeholder="Örn: İstanbul"
               value="<?= htmlspecialchars($_POST['destination_city'] ?? '') ?>">
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-success">Ara</button>
      </div>
    </div>
  </form>

  <?php if ($sonuclar): ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped text-center align-middle">
        <thead class="table-dark"><tr><th>Kalkış</th><th>Varış</th><th>Fiyat (₺)</th><th>Firma</th><th>İşlem</th></tr></thead>
        <tbody>
          <?php foreach ($sonuclar as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s['departure_city']) ?></td>
              <td><?= htmlspecialchars($s['destination_city']) ?></td>
              <td><?= number_format($s['price'], 2, ',', '.') ?></td>
              <td>
                <?php
                  $firma = $pdo->prepare("SELECT name FROM Bus_Company WHERE id=?");
                  $firma->execute([$s['company_id']]);
                  echo htmlspecialchars($firma->fetchColumn() ?: "Bilinmiyor");
                ?>
              </td>
              <td><a href="trip_detail.php?trip_id=<?= $s['id'] ?>" class="btn btn-primary btn-sm">Detay</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="alert alert-warning text-center">❌ Eşleşen sefer bulunamadı.</div>
  <?php endif; ?>
</div>
</body>
</html>
