<?php
require __DIR__ . "/partials/header.php";
require __DIR__ . "/partials/navbar.php";
require __DIR__ . "/../db.php";

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
  header("Location: /login.php");
  exit;
}

$mesaj = "";

// ============================
// 🏢 FİRMA İŞLEMLERİ
// ============================
if (isset($_POST['add_company'])) {
  $name = trim($_POST['name']);
  if ($name !== '') {
    $stmt = $pdo->prepare("INSERT INTO Bus_Company (id, name, created_at) VALUES (hex(randomblob(8)), ?, CURRENT_TIMESTAMP)");
    $stmt->execute([$name]);
    $mesaj = "✅ Yeni firma eklendi: $name";
  }
} elseif (isset($_POST['delete_company'])) {
  $id = $_POST['company_id'];
  $pdo->prepare("DELETE FROM Bus_Company WHERE id = ?")->execute([$id]);
  $mesaj = "🗑️ Firma silindi.";
}

// ============================
// 👤 FİRMA ADMIN OLUŞTURMA
// ============================
if (isset($_POST['add_company_admin'])) {
  $full_name = trim($_POST['full_name']);
  $email = trim($_POST['email']);
  $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
  $company_id = $_POST['company_id'];

  if ($full_name && $email && $company_id) {
    $stmt = $pdo->prepare("INSERT INTO User (id, full_name, email, password, role, company_id, created_at) 
                           VALUES (hex(randomblob(8)), ?, ?, ?, 'company', ?, CURRENT_TIMESTAMP)");
    $stmt->execute([$full_name, $email, $pass, $company_id]);
    $mesaj = "✅ Firma admini oluşturuldu.";
  }
}

// ============================
// 🎟️ KUPON İŞLEMLERİ
// ============================
if (isset($_POST['add_coupon'])) {
  $code = strtoupper(trim($_POST['code']));
  $discount = (float)$_POST['discount'];
  $limit = (int)$_POST['limit'];
  $expire = $_POST['expire_date'];

  if ($code && $discount > 0 && $expire) {
    $stmt = $pdo->prepare("INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, created_at) 
                           VALUES (hex(randomblob(8)), ?, ?, ?, ?, CURRENT_TIMESTAMP)");
    $stmt->execute([$code, $discount, $limit, $expire]);
    $mesaj = "✅ Kupon eklendi ($code)";
  }
} elseif (isset($_POST['delete_coupon'])) {
  $id = $_POST['coupon_id'];
  $pdo->prepare("DELETE FROM Coupons WHERE id = ?")->execute([$id]);
  $mesaj = "🗑️ Kupon silindi.";
}

// ============================
// VERİLERİ GETİR
// ============================
$firmalar = $pdo->query("SELECT * FROM Bus_Company ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$kuponlar = $pdo->query("SELECT * FROM Coupons ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-5">
  <h3 class="text-center mb-4">👑 Admin Paneli</h3>

  <?php if ($mesaj): ?>
    <div class="alert alert-info text-center"><?= htmlspecialchars($mesaj) ?></div>
  <?php endif; ?>

  <!-- 🏢 FİRMA YÖNETİMİ -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white">🏢 Otobüs Firmaları</div>
    <div class="card-body">
      <form method="POST" class="row g-2 mb-3">
        <div class="col-md-6">
          <input type="text" name="name" class="form-control" placeholder="Firma Adı" required>
        </div>
        <div class="col-md-3 d-grid">
          <button type="submit" name="add_company" class="btn btn-success">Ekle</button>
        </div>
      </form>

      <table class="table table-bordered text-center align-middle">
        <thead class="table-light">
          <tr><th>Firma Adı</th><th>İşlem</th></tr>
        </thead>
        <tbody>
          <?php foreach ($firmalar as $f): ?>
            <tr>
              <td><?= htmlspecialchars($f['name']) ?></td>
              <td>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="company_id" value="<?= $f['id'] ?>">
                  <button type="submit" name="delete_company" class="btn btn-danger btn-sm">Sil</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- 👤 FİRMA ADMIN EKLE -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-warning">👤 Firma Admin Oluştur</div>
    <div class="card-body">
      <form method="POST" class="row g-3">
        <div class="col-md-3">
          <input type="text" name="full_name" class="form-control" placeholder="Ad Soyad" required>
        </div>
        <div class="col-md-3">
          <input type="email" name="email" class="form-control" placeholder="E-posta" required>
        </div>
        <div class="col-md-2">
          <input type="password" name="password" class="form-control" placeholder="Şifre" required>
        </div>
        <div class="col-md-2">
          <select name="company_id" class="form-select" required>
            <option value="">Firma Seç</option>
            <?php foreach ($firmalar as $f): ?>
              <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 d-grid">
          <button type="submit" name="add_company_admin" class="btn btn-primary">Kaydet</button>
        </div>
      </form>
    </div>
  </div>
  <!-- 👥 FİRMA ADMİNLERİNİ GÖRÜNTÜLE VE SİL -->
  <div class="card shadow-sm mt-4">
    <div class="card-header bg-danger text-white">👥 Firma Adminleri</div>
    <div class="card-body">
      <?php
      $admins = $pdo->query("
        SELECT u.id, u.full_name, u.email, b.name AS company_name, u.created_at 
        FROM User u 
        LEFT JOIN Bus_Company b ON u.company_id = b.id 
        WHERE u.role = 'company'
        ORDER BY u.created_at DESC
      ")->fetchAll(PDO::FETCH_ASSOC);
      ?>

      <?php if (count($admins) > 0): ?>
        <table class="table table-bordered text-center align-middle">
          <thead class="table-light">
            <tr>
              <th>Ad Soyad</th>
              <th>E-posta</th>
              <th>Bağlı Olduğu Firma</th>
              <th>Oluşturulma Tarihi</th>
              <th>İşlem</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($admins as $a): ?>
              <tr>
                <td><?= htmlspecialchars($a['full_name']) ?></td>
                <td><?= htmlspecialchars($a['email']) ?></td>
                <td><?= htmlspecialchars($a['company_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars($a['created_at']) ?></td>
                <td>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="delete_admin_id" value="<?= $a['id'] ?>">
                    <button type="submit" name="delete_admin" class="btn btn-danger btn-sm"
                            onclick="return confirm('Bu firma adminini silmek istediğine emin misin?')">
                      Sil
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="alert alert-info text-center">Henüz firma admini bulunmuyor.</div>
      <?php endif; ?>
    </div>
  </div>

<?php
// 👇 Firma admini silme işlemi
if (isset($_POST['delete_admin'])) {
  $admin_id = $_POST['delete_admin_id'];
  $pdo->prepare("DELETE FROM User WHERE id = ? AND role = 'company'")->execute([$admin_id]);
  echo "<meta http-equiv='refresh' content='0'>"; // sayfayı yenile
}
?>

  <!-- 🎟️ KUPON YÖNETİMİ -->
  <div class="card shadow-sm">
    <div class="card-header bg-success text-white">🎟️ İndirim Kuponları</div>
    <div class="card-body">
      <form method="POST" class="row g-3 mb-3">
        <div class="col-md-3"><input type="text" name="code" class="form-control" placeholder="Kod (Örn: IND10)" required></div>
        <div class="col-md-2"><input type="number" name="discount" step="0.1" class="form-control" placeholder="% Oran" required></div>
        <div class="col-md-2"><input type="number" name="limit" class="form-control" placeholder="Kullanım Limiti"></div>
        <div class="col-md-3"><input type="date" name="expire_date" class="form-control" required></div>
        <div class="col-md-2 d-grid"><button type="submit" name="add_coupon" class="btn btn-success">Ekle</button></div>
      </form>

      <table class="table table-bordered text-center align-middle">
        <thead class="table-light">
          <tr><th>Kod</th><th>İndirim</th><th>Limit</th><th>Son Kullanma</th><th>İşlem</th></tr>
        </thead>
        <tbody>
          <?php foreach ($kuponlar as $k): ?>
            <tr>
              <td><?= htmlspecialchars($k['code']) ?></td>
              <td><?= htmlspecialchars($k['discount']) ?>%</td>
              <td><?= htmlspecialchars($k['usage_limit']) ?></td>
              <td><?= htmlspecialchars($k['expire_date']) ?></td>
              <td>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="coupon_id" value="<?= $k['id'] ?>">
                  <button type="submit" name="delete_coupon" class="btn btn-danger btn-sm">Sil</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . "/partials/footer.php"; ?>
