<?php
require __DIR__ . "/partials/header.php";
require __DIR__ . "/partials/navbar.php";
require __DIR__ . "/../db.php";

$girisYapildi = isset($_SESSION['id']);
$rol = $_SESSION['role'] ?? '';

if (!$girisYapildi) {
  $rol = 'guest';
}

$user_id = $_SESSION['id'] ?? null;
$trip_id = $_GET['trip_id'] ?? null;
$mesaj = "";
$kuponBilgisi = "";

// 🚌 Sefer bilgilerini getir
$sorgu = $pdo->prepare("SELECT * FROM Trips WHERE id = ?");
$sorgu->execute([$trip_id]);
$trip = $sorgu->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
  echo "<div class='alert alert-danger text-center mt-4'>Sefer bulunamadı!</div>";
  require __DIR__ . "/partials/footer.php";
  exit;
}

// 💺 Dolu koltuklar
$sorgu = $pdo->prepare("
  SELECT bs.seat_number 
  FROM Booked_Seats bs
  JOIN Tickets t ON bs.ticket_id = t.id
  WHERE t.trip_id = ?
");
$sorgu->execute([$trip_id]);
$bookedSeats = $sorgu->fetchAll(PDO::FETCH_COLUMN);
$bookedSeats = array_map('intval', $bookedSeats);

// 💰 Kullanıcı bakiyesi
$user_balance = 0;
if ($rol === 'user' && $girisYapildi) {
  $userSorgu = $pdo->prepare("SELECT balance FROM User WHERE id = ?");
  $userSorgu->execute([$user_id]);
  $user_balance = (float)$userSorgu->fetchColumn();
}

// 🎫 Bilet satın alma işlemi (sadece user ise)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rol === 'user') {
  $seatInput = trim($_POST['seat_numbers'] ?? '');
  $seatNumbers = array_filter(array_map('intval', explode(',', $seatInput)));
  $couponCode = strtoupper(trim($_POST['coupon_code'] ?? ''));
  $discountRate = 0;
  $kupon = null;

  // 💸 Kupon kontrolü
  if ($couponCode !== '') {
    $kuponSorgu = $pdo->prepare("
      SELECT * FROM Coupons 
      WHERE upper(code) = ? 
      AND (company_id IS NULL OR company_id = ?) 
      AND date(expire_date) >= date('now')
      AND (usage_limit IS NULL OR usage_limit > 0)
    ");
    $kuponSorgu->execute([$couponCode, $trip['company_id']]);
    $kupon = $kuponSorgu->fetch(PDO::FETCH_ASSOC);

    if ($kupon) {
      $discountRate = (float)$kupon['discount'];
      $kuponBilgisi = "💰 Kupon uygulandı: %" . $discountRate . " indirim!";
    } else {
      $kuponBilgisi = "⚠️ Kupon geçersiz veya süresi dolmuş.";
    }
  }

  if (empty($seatNumbers)) {
    $mesaj = "⚠️ Lütfen almak istediğiniz koltuk numarasını yazın (örnek: 5 veya 3,7,9)";
  } else {
    $pricePerSeat = (float)$trip['price'];
    $totalPrice = $pricePerSeat * count($seatNumbers);

    // 🧮 İndirim uygula
    if ($discountRate > 0) {
      $totalPrice = $totalPrice * (1 - ($discountRate / 100));
    }

    if ($user_balance < $totalPrice) {
      $mesaj = "❌ Yetersiz bakiye. Lütfen hesabınıza bakiye yükleyin.";
    } else {
      // Dolu koltuk kontrolü
      $check = $pdo->prepare("
        SELECT bs.seat_number 
        FROM Booked_Seats bs
        JOIN Tickets t ON bs.ticket_id = t.id
        WHERE t.trip_id = ? 
        AND bs.seat_number IN (" . str_repeat('?,', count($seatNumbers) - 1) . "?)
      ");
      $check->execute(array_merge([$trip_id], $seatNumbers));
      $alreadyBooked = $check->fetchAll(PDO::FETCH_COLUMN);

      if (count($alreadyBooked) > 0) {
        $mesaj = "⚠️ Seçilen koltuklardan bazıları dolu: " . implode(', ', $alreadyBooked);
      } else {
        $pdo->beginTransaction();

        $ticket_id = uniqid();
        $insertTicket = $pdo->prepare("
          INSERT INTO Tickets (id, user_id, trip_id, total_price, status, created_at)
          VALUES (?, ?, ?, ?, 'active', datetime('now'))
        ");
        $insertTicket->execute([$ticket_id, $user_id, $trip_id, $totalPrice]);

        $insertSeat = $pdo->prepare("
          INSERT INTO Booked_Seats (id, ticket_id, seat_number, created_at)
          VALUES (?, ?, ?, datetime('now'))
        ");
        foreach ($seatNumbers as $seat) {
          $insertSeat->execute([uniqid(), $ticket_id, $seat]);
        }

        // 🧾 Kupon kullanıldıysa azalt
        if ($kupon && !empty($kupon['usage_limit'])) {
          $updateKupon = $pdo->prepare("UPDATE Coupons SET usage_limit = usage_limit - 1 WHERE id = ?");
          $updateKupon->execute([$kupon['id']]);
        }

        // 💸 Kullanıcı bakiyesini düş
        $updateBalance = $pdo->prepare("UPDATE User SET balance = balance - ? WHERE id = ?");
        $updateBalance->execute([$totalPrice, $user_id]);
        $_SESSION['balance'] -= $totalPrice;

        $pdo->commit();

        $mesaj = "✅ Bilet satın alındı. Koltuk(lar): " . implode(', ', $seatNumbers) .
                 " | Toplam: " . number_format($totalPrice, 2) . " ₺";

        if ($kuponBilgisi) {
          $mesaj = $kuponBilgisi . "<br>" . $mesaj;
        }

        echo "<meta http-equiv='refresh' content='3;url=/my_tickets.php'>";
      }
    }
  }
}
?>

<style>
.seat-map {
  display: grid;
  grid-template-columns: repeat(4, 38px);
  gap: 8px;
  justify-content: center;
  margin: 20px auto;
  width: fit-content;
}
.seat {
  width: 36px;
  height: 36px;
  border-radius: 6px;
  background: #28a745;
  color: white;
  font-size: 13px;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
}
.seat.booked {
  background: #dc3545;
}
.seat.aisle {
  visibility: hidden;
}
.seat-map-label {
  text-align: center;
  font-size: 0.9em;
  margin-top: 8px;
  color: #555;
}
</style>

<div class="container py-5">
  <h3 class="text-center mb-4">Bilet Satın Al</h3>

  <?php if ($mesaj): ?>
    <div class="alert <?= str_contains($mesaj, '✅') ? 'alert-success' : 'alert-warning' ?> text-center">
      <?= $mesaj ?>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm mx-auto" style="max-width:600px">
    <div class="card-body">
      <h5 class="mb-3 text-center">🚌 Sefer Bilgileri</h5>
      <p class="text-center">
        <strong><?= htmlspecialchars($trip['departure_city']) ?></strong> → 
        <strong><?= htmlspecialchars($trip['destination_city']) ?></strong><br>
        <?= htmlspecialchars($trip['departure_time']) ?><br>
        Fiyat: <?= number_format($trip['price'], 2) ?> ₺ / koltuk
      </p>

      <!-- 🪑 1+2 Koltuk Düzeni -->
      <div class="seat-map">
        <?php
        $seatNum = 1;
        for ($row = 1; $row <= 14; $row++) {
          // Sol taraf: 1 koltuk
          $class = in_array($seatNum, $bookedSeats) ? "seat booked" : "seat";
          echo "<div class='$class' title='Koltuk $seatNum'>$seatNum</div>";
          $seatNum++;

          // Koridor boşluğu
          echo "<div class='seat aisle'></div>";

          // Sağ taraf: 2 koltuk
          for ($i = 1; $i <= 2; $i++) {
            $class = in_array($seatNum, $bookedSeats) ? "seat booked" : "seat";
            echo "<div class='$class' title='Koltuk $seatNum'>$seatNum</div>";
            $seatNum++;
          }
        }
        ?>
      </div>
      <div class="text-center seat-map-label">
        <span class="badge bg-success">Boş</span>
        <span class="badge bg-danger">Dolu</span>
      </div>

      <?php if ($rol === 'user'): ?>
      <form method="POST" class="mt-4">
        <div class="mb-3">
          <label class="form-label">Koltuk Numarası(ları)</label>
          <input type="text" name="seat_numbers" class="form-control" placeholder="örnek: 5 veya 3,7,9">
          <div class="form-text text-muted">
            Dolu koltuklar: <?= implode(', ', $bookedSeats) ?: 'Henüz dolu koltuk yok.' ?>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Kupon Kodu (Varsa)</label>
          <input type="text" name="coupon_code" class="form-control" placeholder="Örn: IND10">
          <div class="form-text text-muted">Kupon kodu girersen indirim uygulanır.</div>
        </div>

        <div class="mb-3">
          <p><strong>Bakiyeniz:</strong> <?= number_format($user_balance, 2) ?> ₺</p>
        </div>

        <button type="submit" class="btn btn-primary w-100">Satın Al</button>
      </form>

      <?php elseif ($rol === 'guest'): ?>
        <div class="alert alert-info text-center mt-3">
          🔒 Bilet almak için giriş yapmalısınız.
        </div>
        <a href="/login.php" class="btn btn-secondary w-100 mt-2">Oturum Aç</a>

      <?php else: ?>
        <div class="alert alert-warning text-center mt-3">
          ⛔ Bu hesap türü ile bilet satın alınamaz.
        </div>
        <button class="btn btn-danger w-100" disabled>Satın Alım Yapamazsınız</button>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . "/partials/footer.php"; ?>
