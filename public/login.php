<?php
session_start();
require __DIR__ . "/../db.php";

$mesaj = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $sifre = $_POST['password'] ?? '';

    if (!$email || !$sifre) {
        $mesaj = "⚠️ E-posta ve şifre girin.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM User WHERE email = ?");
        $stmt->execute([$email]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$u || !password_verify($sifre, $u['password'])) {
            $mesaj = "❌ E-posta veya şifre hatalı!";
        } else {
            $_SESSION = [
                'id' => $u['id'],
                'full_name' => $u['full_name'],
                'email' => $u['email'],
                'role' => $u['role'],
                'balance' => $u['balance'],
                'company_id' => $u['company_id'] ?? null
            ];

            $sayfa = match ($u['role']) {
                'admin' => '/admin_panel.php',
                'company' => '/company_panel.php',
                default => '/index.php'
            };
            header("Location: $sayfa");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Giriş Yap</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f8fafc}
.card{border:none;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,.1)}
</style>
</head>
<body>
<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card p-4" style="width:360px">
    <h4 class="text-center mb-3">Giriş Yap</h4>

    <?php if ($mesaj): ?>
      <div class="alert alert-warning text-center p-2"><?= htmlspecialchars($mesaj) ?></div>
    <?php endif; ?>

    <form method="POST" class="needs-validation" novalidate>
      <div class="mb-3">
        <label class="form-label">E-posta</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Şifre</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
    </form>

    <div class="text-center mt-3">
      <small>Hesabın yok mu? <a href="/register.php">Kayıt ol</a></small>
    </div>
  </div>
</div>
</body>
</html>
