<?php
session_start();
require __DIR__ . "/../db.php"; // db.php kök dizinde ise bu yol doğru

$mesaj = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adsoyad = trim($_POST['full_name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $sifre   = $_POST['password'] ?? '';

    // Zorunlu alan kontrolleri
    if (!$adsoyad || !$email || !$sifre) {
        $mesaj = "⚠️ Lütfen ad-soyad, e-posta ve şifre alanlarını doldurun.";
    } else {
        try {
            // E-posta zaten kayıtlı mı?
            $sorgu = $pdo->prepare("SELECT 1 FROM User WHERE email = ?");
            $sorgu->execute([$email]);

            if ($sorgu->fetchColumn()) {
                $mesaj = "⚠️ Bu e-posta zaten kayıtlı!";
            } else {
                // Yeni kullanıcı ekle (şifreyi hashle)
                $id = uniqid();
                $hash = password_hash($sifre, PASSWORD_BCRYPT);

                $ekle = $pdo->prepare(
                    "INSERT INTO User (id, full_name, email, role, password, balance) 
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $ekle->execute([$id, $adsoyad, $email, 'user', $hash, 800]);

                // Başarılıysa login sayfasına yönlendir
                header("Location: /login.php");
                exit;
            }
        } catch (Throwable $e) {
            // Hata oluşursa kullanıcıya göster
            $mesaj = "🚨 Veritabanı hatası: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <title>Kayıt Ol</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width:420px">
  <div class="card p-4 shadow-sm">
    <h3 class="text-center mb-3">Kayıt Ol</h3>

    <?php if ($mesaj): ?>
      <div class="alert alert-warning text-center"><?= htmlspecialchars($mesaj) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Ad Soyad</label>
        <input type="text" name="full_name" class="form-control" required value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">E-posta</label>
        <input type="email" name="email" class="form-control" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Şifre</label>
        <input type="password" name="password" class="form-control" required>
      </div>

      <button type="submit" class="btn btn-success w-100">Kayıt Ol</button>
    </form>

    <div class="text-center mt-3">
      <small>Zaten hesabın var mı? <a href="/login.php">Giriş yap</a></small>
    </div>
  </div>
</div>
</body>
</html>
