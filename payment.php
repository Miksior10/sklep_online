<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $card_number = encrypt($_POST['card_number']); // Funkcja szyfrująca
    $order_id = $_POST['order_id'];
    
    // Zapisanie zaszyfrowanych danych karty
    $sql = "INSERT INTO payments (order_id, card_number, status) VALUES (?, ?, 'completed')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id, $card_number]);
}

function encrypt($data) {
    $key = "twoj_tajny_klucz";
    return openssl_encrypt($data, "AES-256-CBC", $key, 0, substr($key, 0, 16));
}
?>

<form method="POST" action="payment.php">
    <input type="text" name="card_number" placeholder="Numer karty" required>
    <input type="text" name="expiry" placeholder="MM/RR" required>
    <input type="text" name="cvv" placeholder="CVV" required>
    <button type="submit">Zapłać</button>
</form> 