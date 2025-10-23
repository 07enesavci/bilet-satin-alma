<?php
require __DIR__ . "/partials/header.php";
require __DIR__ . "/partials/navbar.php";
require __DIR__ . "/../db.php";

$trip_id = $_GET['trip_id'] ?? null;

if (!$trip_id) {
  echo "<div class='alert alert-warning text-center mt-4'>⚠️ Sefer ID belirtilmedi.</div>";
  require __DIR__ . "/partials/footer.php";
  exit;
}

// 🚌 Seferi getir
$stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
  echo "<div class='alert alert-danger text-center mt-4'>❌ Sefer bulunamadı!</div>";
  require __DIR__ . "/partials/footer.php";
  exit;
}

// 🏢 Firma adını al
$firmaSorgu = $pdo->prepare("SELECT name FROM Bus_Company WHERE id = ?");
$firmaSorgu->execute([$trip['company_id']]);
$firmaAdi = $firmaSorgu->fetchColumn() ?: "Bilinmeyen Firma";

// 💺 Dolu koltuklar
$seatQuery = $pdo->prepare("
  SELECT bs.seat_number 
  FROM Booked_Seats bs
  JOIN Tickets t ON bs.ticket_id = t.id
  WHERE t.trip_id = ?
");
$seatQuery->execute([$trip_id]);
$bookedSeats = $seatQuery->fetchAll(PDO::FETCH_COLUMN);
$bookedSeats = array_map('intval', $bookedSeats);
?>

<style>
.seat-map {
  display: grid;
  grid-template-columns: repeat(4, 38px);
  gap: 8px;
  justify-content: center;
  margin: 25px auto;
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
  <div class="card shadow-sm mx-auto" style="max-width:600px;">
    <div class="card-body">
      <h1 class="h5 text-center mb-3">🚌 Sefer Detayı</h1>

      <dl class="row mb-0">
        <dt class="col-sm-4">Kalkış</dt>
        <dd class="col-sm-8"><?= htmlspecialchars($trip['departure_city']) ?></dd>

        <dt class="col-sm-4">Varış</dt>
        <dd class="col-sm-8"><?= htmlspecialchars($trip['destination_city']) ?></dd>

        <dt class="col-sm-4">Kalkış Zamanı</dt>
        <dd class="col-sm-8"><?= htmlspecialchars($trip['departure_time']) ?></dd>

        <dt class="col-sm-4">Varış Zamanı</dt>
        <dd class="col-sm-8"><?= htmlspecialchars($trip['arrival_time']) ?></dd>

        <dt class="col-sm-4">Fiyat</dt>
        <dd class="col-sm-8">
          <span class="text-primary fw-semibold"><?= number_format($trip['price'], 2, ',', '.') ?> ₺</span>
        </dd>

        <dt class="col-sm-4">Firma</dt>
        <dd class="col-sm-8"><?= htmlspecialchars($firmaAdi) ?></dd>
      </dl>

      <!-- 🪑 1+2 Koltuk Düzeni (42 koltuk) -->
      <div class="mt-4">
        <h6 class="text-center mb-2">Koltuk Düzeni (1 + 2)</h6>
        <div class="seat-map">
          <?php
          $seatNum = 1;
          for ($row = 1; $row <= 14; $row++) {
            // Sol koltuk
            $class = in_array($seatNum, $bookedSeats) ? "seat booked" : "seat";
            echo "<div class='$class' title='Koltuk $seatNum'>$seatNum</div>";
            $seatNum++;

            // Koridor
            echo "<div class='seat aisle'></div>";

            // Sağ 2 koltuk
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
      </div>

      <div class="text-center mt-4">
        <a class="btn btn-primary" href="/purchase.php?trip_id=<?= urlencode($trip['id']) ?>">
          🎟️ Bilet Satın Al
        </a>
        <a class="btn btn-outline-secondary" href="/index.php">← Geri Dön</a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . "/partials/footer.php"; ?>
