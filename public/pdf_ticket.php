<?php
ob_start(); 

session_start();
require __DIR__ . "/../db/db.php";
require_once __DIR__ . "/../vendor/autoload.php"; 
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * YENİ FONKSİYON:
 * Türkçe karakterleri İngilizce (ASCII) benzerlerine çevirir.
 * @param string $text Çevrilecek metin
 * @return string Çevrilmiş metin
 */
function turkceKarakterCevir($text) {
    if ($text === null) {
        return '';
    }
    $search  = ['ı', 'İ', 'ş', 'Ş', 'ğ', 'Ğ', 'ü', 'Ü', 'ö', 'Ö', 'ç', 'Ç'];
    $replace = ['i', 'I', 's', 'S', 'g', 'G', 'u', 'U', 'o', 'O', 'c', 'C'];
    return str_replace($search, $replace, $text);
}


if (!isset($_SESSION['id'])) {
    header("Location: /login.php");
    exit;
}

$user_id = $_SESSION['id'];
$role = $_SESSION['role'] ?? '';
$ticket_id = $_GET['ticket_id'] ?? null;

if (!$ticket_id) {
    die("❌ Bilet ID belirtilmemiş.");
}

// ... (VERİTABANI KODU - Değişiklik yok) ...
$stmt = $pdo->prepare("
    SELECT 
        t.id AS ticket_id, t.total_price, t.status, t.created_at,
        tr.departure_city, tr.destination_city, tr.departure_time, tr.arrival_time,
        tr.company_id,
        bc.name AS company_name,
        u.full_name, u.email, u.id AS user_owner_id
    FROM Tickets t
    JOIN Trips tr ON tr.id = t.trip_id
    JOIN User u ON u.id = t.user_id
    JOIN Bus_Company bc ON bc.id = tr.company_id
    WHERE t.id = ?
");
$stmt->execute([$ticket_id]);
$bilet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bilet) {
    die("❌ Bilet bulunamadı.");
}
if (
    !(
        ($role === 'user' && $bilet['user_owner_id'] == $user_id) ||
        ($role === 'company' && isset($_SESSION['company_id']) && $bilet['company_id'] == $_SESSION['company_id'])
    )
) {
    die("⛔ Bu bileti indirme yetkiniz yok.");
}
// ... (VERİTABANI KODU BİTİŞİ) ...


// --- YENİ DEĞİŞKENLER: PDF'e basılacak tüm verileri çeviriyoruz ---
$pdf_ticket_id = turkceKarakterCevir($bilet['ticket_id']);
$pdf_full_name = turkceKarakterCevir($bilet['full_name']);
$pdf_email = $bilet['email']; // Email'e dokunmuyoruz
$pdf_company_name = turkceKarakterCevir($bilet['company_name']);
$pdf_departure_city = turkceKarakterCevir($bilet['departure_city']);
$pdf_departure_time = $bilet['departure_time'];
$pdf_destination_city = turkceKarakterCevir($bilet['destination_city']);
$pdf_arrival_time = $bilet['arrival_time'];
// Fiyat: ₺ simgesi '?' oluyordu, 'TL' olarak değiştiriyoruz
$pdf_fiyat = number_format($bilet['total_price'], 2, ',', '.') . " TL"; 
$pdf_status = turkceKarakterCevir($bilet['status']);
$pdf_created_at = $bilet['created_at'];


//  PDF oluşturma (HIZLI AYARLAR KORUNDU)
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$options->set('isFontSubsettingEnabled', false); 
$options->set('defaultFont', 'Helvetica'); 

$dompdf = new Dompdf($options);

// --- HTML İÇERİĞİ GÜNCELLENDİ (Yeni $pdf_... değişkenlerini kullan) ---
$html = "
<!DOCTYPE html>
<html lang='tr'>
<head>
<meta charset='UTF-8'>
<style>
body { font-family: sans-serif; } 
h1 { text-align: center; color: #2c3e50; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #555; padding: 8px; text-align: left; }
th { background: #f1f1f1; }
footer { margin-top: 40px; text-align: center; color: #777; font-size: 12px; }
</style>
</head>
<body>
<h1>" . turkceKarakterCevir(" Bilet Platformu - Bilet Bilgileri") . "</h1>
<table>
<tr><th>Bilet No</th><td>{$pdf_ticket_id}</td></tr>
<tr><th>Yolcu</th><td>{$pdf_full_name} ({$pdf_email})</td></tr>
<tr><th>Firma</th><td>{$pdf_company_name}</td></tr>
<tr><th>Kalkis</th><td>{$pdf_departure_city} ({$pdf_departure_time})</td></tr>
<tr><th>Varis</th><td>{$pdf_destination_city} ({$pdf_arrival_time})</td></tr>
<tr><th>Fiyat</th><td>{$pdf_fiyat}</td></tr>
<tr><th>Durum</th><td>{$pdf_status}</td></tr>
<tr><th>Olusturulma</th><td>{$pdf_created_at}</td></tr>
</table>
<footer>" . turkceKarakterCevir("İyi yolculuklar dileriz <br>BiletPlatformu © ") . date('Y') . "</footer>
</body>
</html>
";

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

ob_end_clean(); 

$dompdf->stream("bilet-{$pdf_ticket_id}.pdf", ["Attachment" => true]);
exit;
?>
