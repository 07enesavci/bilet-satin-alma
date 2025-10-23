<?php
require __DIR__ . "/partials/header.php";
require __DIR__ . "/partials/navbar.php";
require __DIR__ . "/../db.php";

if (empty($_SESSION['id']) || $_SESSION['role'] !== 'company') {
  header("Location: /login.php");
  exit;
}

$user_id = $_SESSION['id'];
$stmt = $pdo->prepare("SELECT company_id FROM User WHERE id = ?");
$stmt->execute([$user_id]);
$company_id = $stmt->fetchColumn();

if (!$company_id) {
  echo "<div class='alert alert-danger text-center mt-4'>Firma bilgisi bulunamadı!</div>";
  require __DIR__ . "/partials/footer.php";
  exit;
}

$firmaAdi = $pdo->query("SELECT name FROM Bus_Company WHERE id = '$company_id'")->fetchColumn() ?: "Bilinmeyen Firma";
$mesaj = "";

// 🚌 SEFER İŞLEMLERİ
if (isset($_POST['add_trip'])) {
  $from = trim($_POST['departure_city']);
  $to = trim($_POST['destination_city']);
  $departure = $_POST['departure_time'];
  $arrival = $_POST['arrival_time'];
  $price = (float)$_POST['price'];
  $seats = (int)$_POST['seats_total'];
  if ($from && $to && $departure && $arrival && $price > 0 && $seats > 0) {
    $pdo->prepare("INSERT INTO Trips VALUES (hex(randomblob(8)), ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)")
        ->execute([$company_id, $from, $to, $departure, $arrival, $price, $seats]);
    $mesaj = "✅ Yeni sefer eklendi.";
  }
}

if (isset($_POST['delete_trip'])) {
  $pdo->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?")->execute([$_POST['trip_id'], $company_id]);
  $mesaj = "🗑️ Sefer silindi.";
}

if (isset($_POST['edit_trip'])) {
  $from = trim($_POST['departure_city']);
  $to = trim($_POST['destination_city']);
  $departure = $_POST['departure_time'];
  $arrival = $_POST['arrival_time'];
  $price = (float)$_POST['price'];
  $seats = (int)$_POST['seats_total'];
  if ($from && $to && $departure && $arrival && $price > 0 && $seats > 0) {
    $pdo->prepare("UPDATE Trips SET departure_city=?, destination_city=?, departure_time=?, arrival_time=?, price=?, capacity=? WHERE id=? AND company_id=?")
        ->execute([$from, $to, $departure, $arrival, $price, $seats, $_POST['trip_id'], $company_id]);
    $mesaj = "✏️ Sefer başarıyla güncellendi.";
  } else $mesaj = "⚠️ Lütfen tüm alanları doğru doldurun.";
}

// 🎟️ KUPON İŞLEMLERİ
if (isset($_POST['add_coupon'])) {
  $code = strtoupper(trim($_POST['code']));
  $discount = (float)$_POST['discount'];
  $limit = (int)$_POST['limit'];
  $expire = $_POST['expire_date'];
  if ($code && $discount > 0 && $expire) {
    $pdo->prepare("INSERT INTO Coupons VALUES (hex(randomblob(8)), ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)")
        ->execute([$company_id, $code, $discount, $limit, $expire]);
    $mesaj = "✅ Kupon eklendi: $code";
  }
}

if (isset($_POST['delete_coupon'])) {
  $pdo->prepare("DELETE FROM Coupons WHERE id = ? AND company_id = ?")->execute([$_POST['coupon_id'], $company_id]);
  $mesaj = "🗑️ Kupon silindi.";
}

// 🎫 BİLET İPTAL
if (isset($_POST['cancel_ticket'])) {
  $q = $pdo->prepare("SELECT t.id,t.total_price,t.user_id,tr.departure_time FROM Tickets t JOIN Trips tr ON t.trip_id=tr.id WHERE t.id=? AND tr.company_id=? AND t.status='active'");
  $q->execute([$_POST['ticket_id'], $company_id]);
  $b = $q->fetch(PDO::FETCH_ASSOC);
  if ($b) {
    if (strtotime($b['departure_time']) - time() > 3600) {
      $pdo->beginTransaction();
      $pdo->prepare("UPDATE Tickets SET status='canceled' WHERE id=?")->execute([$b['id']]);
      $pdo->prepare("UPDATE User SET balance=balance+? WHERE id=?")->execute([$b['total_price'], $b['user_id']]);
      $pdo->commit();
      $mesaj = "✅ Bilet iptal edildi ve ücret iade edildi.";
    } else $mesaj = "⚠️ Kalkışa 1 saatten az kaldı.";
  } else $mesaj = "❌ Bilet bulunamadı.";
}

// 🔍 VERİLER
$seferler = $pdo->query("SELECT * FROM Trips WHERE company_id='$company_id' ORDER BY departure_time DESC")->fetchAll(PDO::FETCH_ASSOC);
$kuponlar = $pdo->query("SELECT * FROM Coupons WHERE company_id='$company_id' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$biletler = $pdo->query("SELECT t.id ticket_id,u.full_name,u.email,tr.departure_city,tr.destination_city,tr.departure_time,t.total_price,t.status FROM Tickets t JOIN Trips tr ON t.trip_id=tr.id JOIN User u ON t.user_id=u.id WHERE tr.company_id='$company_id' ORDER BY tr.departure_time DESC")->fetchAll(PDO::FETCH_ASSOC);

$action = $_GET['action'] ?? null;
$sefer_to_edit = null;
if ($action === 'edit_trip' && isset($_GET['id'])) {
  $stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = ? AND company_id = ?");
  $stmt->execute([$_GET['id'], $company_id]);
  $sefer_to_edit = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
  if (!$sefer_to_edit) $mesaj = "❌ Düzenlenecek sefer bulunamadı.";
}
?>

<div class="container py-5">
  <h3 class="text-center mb-4">🚌 <?= htmlspecialchars($firmaAdi) ?> Admin Paneli</h3>
  <?php if ($mesaj): ?><div class="alert alert-info text-center"><?= htmlspecialchars($mesaj) ?></div><?php endif; ?>

  <?php if ($sefer_to_edit): ?>
  <div class="card mb-4 shadow-sm border-warning">
    <div class="card-header bg-warning text-dark"><h5 class="mb-0">✏️ Seferi Düzenle</h5></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="trip_id" value="<?= htmlspecialchars($sefer_to_edit['id']) ?>">
        <div class="row g-3">
          <?php
          $fields = [
            'Kalkış Şehri' => 'departure_city',
            'Varış Şehri' => 'destination_city',
            'Kalkış Zamanı' => 'departure_time',
            'Varış Zamanı' => 'arrival_time',
            'Fiyat (₺)' => 'price',
            'Koltuk Sayısı' => 'capacity'
          ];
          foreach ($fields as $label => $name): ?>
          <div class="col-md-6">
            <label class="form-label"><?= $label ?></label>
            <input type="<?= str_contains($name, 'time') ? 'datetime-local' : 'text' ?>" name="<?= $name ?>" class="form-control"
                   value="<?= htmlspecialchars($sefer_to_edit[$name]) ?>" required>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-3">
          <button type="submit" name="edit_trip" class="btn btn-warning text-dark">Güncelle</button>
          <a href="?" class="btn btn-secondary">İptal</a>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between"><span>🚌 Seferlerim</span><a href="?action=new_trip" class="btn btn-light btn-sm">Yeni Sefer</a></div>
    <div class="card-body">
      <table class="table table-bordered text-center align-middle">
        <thead class="table-light"><tr><th>Kalkış</th><th>Varış</th><th>Kalkış Zamanı</th><th>Varış Zamanı</th><th>Fiyat</th><th>Koltuk</th><th>İşlem</th></tr></thead>
        <tbody>
        <?php foreach ($seferler as $s): ?>
          <tr>
            <td><?= $s['departure_city'] ?></td><td><?= $s['destination_city'] ?></td>
            <td><?= $s['departure_time'] ?></td><td><?= $s['arrival_time'] ?></td>
            <td><?= number_format($s['price'], 2, ',', '.') ?></td><td><?= $s['capacity'] ?></td>
            <td>
              <a href="?action=edit_trip&id=<?= $s['id'] ?>" class="btn btn-sm btn-warning me-1">Düzenle</a>
              <form method="POST" class="d-inline"><input type="hidden" name="trip_id" value="<?= $s['id'] ?>">
                <button type="submit" name="delete_trip" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?')">Sil</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($seferler)): ?><tr><td colspan="7" class="text-muted">Henüz sefer yok.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-warning text-dark">🎫 Satılan Biletler</div>
    <div class="card-body">
      <table class="table table-bordered text-center align-middle">
        <thead class="table-light"><tr><th>Yolcu</th><th>E-posta</th><th>Kalkış</th><th>Varış</th><th>Zaman</th><th>Fiyat</th><th>Durum</th><th>İşlem</th></tr></thead>
        <tbody>
        <?php foreach ($biletler as $b):
          $iptal = $b['status'] === 'active' && (strtotime($b['departure_time']) - time() > 3600); ?>
          <tr>
            <td><?= $b['full_name'] ?></td><td><?= $b['email'] ?></td><td><?= $b['departure_city'] ?></td><td><?= $b['destination_city'] ?></td>
            <td><?= $b['departure_time'] ?></td><td><?= number_format($b['total_price'], 2, ',', '.') ?> ₺</td>
            <td><span class="badge bg-<?= $b['status'] === 'active' ? 'success' : 'danger' ?>"><?= ucfirst($b['status']) ?></span></td>
            <td>
              <?php if ($iptal): ?>
                <form method="POST" class="d-inline"><input type="hidden" name="ticket_id" value="<?= $b['ticket_id'] ?>">
                  <button type="submit" name="cancel_ticket" class="btn btn-sm btn-outline-danger">İptal Et</button>
                </form>
              <?php else: ?><button class="btn btn-sm btn-secondary" disabled>İptal Edilemez</button><?php endif; ?>
              <a href="/pdf_ticket.php?ticket_id=<?= $b['ticket_id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">📄 PDF</a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($biletler)): ?><tr><td colspan="8" class="text-muted">Henüz bilet yok.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-success text-white d-flex justify-content-between"><span>🎟️ Kuponlarım</span><a href="?action=new_coupon" class="btn btn-light btn-sm">Yeni Kupon</a></div>
    <div class="card-body">
      <table class="table table-bordered text-center align-middle">
        <thead class="table-light"><tr><th>Kod</th><th>İndirim (%)</th><th>Limit</th><th>Son Kullanma</th><th>İşlem</th></tr></thead>
        <tbody>
        <?php foreach ($kuponlar as $k): ?>
          <tr>
            <td><?= $k['code'] ?></td><td><?= $k['discount'] ?></td><td><?= $k['usage_limit'] ?: '-' ?></td><td><?= $k['expire_date'] ?></td>
            <td><form method="POST" class="d-inline"><input type="hidden" name="coupon_id" value="<?= $k['id'] ?>">
              <button type="submit" name="delete_coupon" class="btn btn-sm btn-danger">Sil</button></form></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($kuponlar)): ?><tr><td colspan="5" class="text-muted">Henüz kupon yok.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . "/partials/footer.php"; ?>
