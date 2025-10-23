<?php
require __DIR__ . "/partials/header.php";
require __DIR__ . "/partials/navbar.php";
require __DIR__ . "/../db/db.php";

if (empty($_SESSION['id'])) {
    header("Location: /login.php");
    exit;
}

$id = $_SESSION['id'];
$mesaj = "";

// Kullanıcı bilgisi
$stmt = $pdo->prepare("SELECT full_name, balance FROM User WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: die("Kullanıcı bulunamadı.");

// Şifre güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = trim($_POST['new_password'] ?? '');
    if (!$pass) {
        $mesaj = "⚠️ Yeni şifre boş olamaz.";
    } else {
        $stmt = $pdo->prepare("UPDATE User SET password = ? WHERE id = ?");
        $stmt->execute([password_hash($pass, PASSWORD_BCRYPT), $id]);
        $mesaj = "✅ Şifre başarıyla değiştirildi!";
    }
}
?>
<div class="container py-5" style="max-width:500px">
  <div class="card shadow-sm p-4">
    <h4 class="text-center mb-3">Hesap Bilgilerim</h4>

    <?php if ($mesaj): ?>
      <div class="alert <?= str_contains($mesaj,'✅') ? 'alert-success' : 'alert-warning' ?> text-center p-2">
        <?= htmlspecialchars($mesaj) ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Ad Soyad</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" readonly>
      </div>
      <div class="mb-3">
        <label class="form-label">Bakiye (₺)</label>
        <input type="text" class="form-control" value="<?= number_format($user['balance'], 2, ',', '.') ?>" readonly>
      </div>
      <div class="mb-3">
        <label class="form-label">Yeni Şifre</label>
        <input type="password" name="new_password" class="form-control" placeholder="Yeni şifrenizi girin" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Şifreyi Değiştir</button>
    </form>
  </div>
</div>
<?php require __DIR__ . "/partials/footer.php"; ?>
