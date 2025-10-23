<?php
$girisYapildi = isset($_SESSION['id']);
$adSoyad = $_SESSION['full_name'] ?? '';
$rol = $_SESSION['role'] ?? '';
$bakiye = $_SESSION['balance'] ?? 0; // 💰 Bakiyeyi session’dan çekiyoruz

$rolEtiket = [
    'user' => 'Yolcu',
    'company' => 'Firma Yetkilisi',
    'admin' => 'Yönetici'
];
$rolGoster = $rolEtiket[$rol] ?? 'Ziyaretçi';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="/index.php">🚌 Bilet Platformu</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="/index.php">Ana Sayfa</a></li>

        <?php if ($girisYapildi && $rol === 'user'): ?>
          <li class="nav-item"><a class="nav-link" href="/my_tickets.php">Biletlerim</a></li>
        <?php elseif ($girisYapildi && $rol === 'company'): ?>
          <li class="nav-item"><a class="nav-link" href="/company_panel.php">Firma Paneli</a></li>
        <?php elseif ($girisYapildi && $rol === 'admin'): ?>
          <li class="nav-item"><a class="nav-link" href="/admin_panel.php">Admin Paneli</a></li>
        <?php endif; ?>
      </ul>

      <div class="d-flex align-items-center">
        <?php if ($girisYapildi): ?>
          <span class="text-white me-3">
            <strong><?= htmlspecialchars($adSoyad) ?></strong> 
            <span class="badge bg-light text-dark"><?= htmlspecialchars($rolGoster) ?></span>
            <span class="ms-2 badge bg-warning text-dark">💰 <?= number_format($bakiye, 2) ?> ₺</span>
          </span>
          <a href="/account.php" class="btn btn-outline-light btn-sm me-2">Hesabım</a>
          <a href="/logout.php" class="btn btn-danger btn-sm">Çıkış</a>
        <?php else: ?>
          <a href="/login.php" class="btn btn-light btn-sm me-2">Giriş Yap</a>
          <a href="/register.php" class="btn btn-warning btn-sm">Kayıt Ol</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
