<?php
require __DIR__ . "/partials/header.php";
require __DIR__ . "/partials/navbar.php";
require __DIR__ . "/../db.php";

if (!isset($_SESSION['id'])) {
  header("Location: /login.php");
  exit;
}

$user_id = $_SESSION['id'];
$mesaj = "";

// 🔹 Bilet iptal işlemi
if (isset($_GET['cancel_id'])) {
  $bilet_id = $_GET['cancel_id'];

  $biletSorgu = $pdo->prepare("
    SELECT t.id, t.total_price, tr.departure_time 
    FROM Tickets t
    JOIN Trips tr ON t.trip_id = tr.id
    WHERE t.id = ? AND t.user_id = ? AND t.status = 'active'
  ");
  $biletSorgu->execute([$bilet_id, $user_id]);
  $bilet = $biletSorgu->fetch(PDO::FETCH_ASSOC);

  if ($bilet) {
    $fark = strtotime($bilet['departure_time']) - time();

    if ($fark > 3600) {
      $pdo->beginTransaction();
      $pdo->prepare("UPDATE Tickets SET status='canceled' WHERE id=?")->execute([$bilet_id]);
      $pdo->prepare("UPDATE User SET balance=balance+? WHERE id=?")->execute([$bilet['total_price'], $user_id]);
      $pdo->prepare("DELETE FROM Booked_Seats WHERE ticket_id=?")->execute([$bilet_id]);
      $pdo->commit();

      $_SESSION['balance'] += $bilet['total_price'];
      $mesaj = "✅ Bilet iptal edildi ve " . number_format($bilet['total_price'], 2) . " ₺ iade edildi.";
    } else {
      $mesaj = "⚠️ Kalkış saatine 1 saatten az kaldığı için iptal edilemez.";
    }
  } else {
    $mesaj = "❌ Bilet bulunamadı veya zaten iptal edilmiş.";
  }
}

// 🔹 Kullanıcının biletlerini çek
$sorgu = $pdo->prepare("
  SELECT t.id AS ticket_id, tr.departure_city, tr.destination_city, tr.departure_time, tr.arrival_time,
         t.total_price, t.status, tr.company_id
  FROM Tickets t
  JOIN Trips tr ON t.trip_id = tr.id
  WHERE t.user_id = ?
  ORDER BY tr.departure_time DESC
");
$sorgu->execute([$user_id]);
$biletler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-5">
  <h3 class="text-center mb-4">Biletlerim</h3>

  <?php if ($mesaj): ?>
    <div class="alert <?= str_contains($mesaj, '✅') ? 'alert-success' : 'alert-warning' ?> text-center">
      <?= htmlspecialchars($mesaj) ?>
    </div>
  <?php endif; ?>

  <?php if ($biletler): ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped text-center align-middle">
        <thead class="table-dark">
          <tr>
            <th>Kalkış</th><th>Varış</th><th>Kalkış Zamanı</th><th>Varış Zamanı</th>
            <th>Fiyat (₺)</th><th>Durum</th><th>İşlem</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($biletler as $b): 
            $fark = strtotime($b['departure_time']) - time();
            $iptalMumkun = $b['status'] === 'active' && $fark > 3600;
            $firmaSorgu = $pdo->prepare("SELECT name FROM Bus_Company WHERE id=?");
            $firmaSorgu->execute([$b['company_id']]);
            $firmaAdi = $firmaSorgu->fetchColumn() ?: 'Bilinmiyor';
          ?>
          <tr>
            <td><?= htmlspecialchars($b['departure_city']) ?></td>
            <td><?= htmlspecialchars($b['destination_city']) ?></td>
            <td><?= htmlspecialchars($b['departure_time']) ?></td>
            <td><?= htmlspecialchars($b['arrival_time']) ?></td>
            <td><?= number_format($b['total_price'], 2, ',', '.') ?></td>
            <td>
              <span class="badge 
                <?= $b['status'] === 'active' ? 'bg-success' : ($b['status'] === 'canceled' ? 'bg-danger' : 'bg-secondary') ?>">
                <?= $b['status'] === 'active' ? 'Aktif' : ($b['status'] === 'canceled' ? 'İptal' : 'Geçersiz') ?>
              </span>
            </td>
            <td>
              <a href="/pdf_ticket.php?ticket_id=<?= $b['ticket_id'] ?>" class="btn btn-sm btn-outline-primary">PDF</a>
              <?php if ($iptalMumkun): ?>
                <a href="?cancel_id=<?= $b['ticket_id'] ?>" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Bu bileti iptal etmek istediğine emin misin?')">İptal</a>
              <?php else: ?>
                <button class="btn btn-sm btn-outline-secondary" disabled>İptal Edilemez</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="alert alert-info text-center">Henüz satın aldığın bir bilet bulunmuyor.</div>
  <?php endif; ?>
</div>

<?php require __DIR__ . "/partials/footer.php"; ?>
