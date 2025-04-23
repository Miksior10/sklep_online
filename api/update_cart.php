<?php
session_start();
require_once '../config.php';

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Musisz być zalogowany, aby zaktualizować koszyk']);
    exit;
}

// Pobierz dane z żądania
$data = json_decode(file_get_contents('php://input'), true);
$item_id = $data['item_id'] ?? null;
$quantity = $data['quantity'] ?? 1;

if (!$item_id) {
    echo json_encode(['success' => false, 'message' => 'Nie podano ID produktu']);
    exit;
}

try {
    // Pobierz informacje o produkcie i jego stanie magazynowym
    $stmt = $pdo->prepare("
        SELECT ci.*, p.stock 
        FROM cart_items ci 
        JOIN products p ON ci.product_id = p.id 
        WHERE ci.id = ? AND ci.user_id = ?
    ");
    $stmt->execute([$item_id, $_SESSION['user_id']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Produkt nie został znaleziony w koszyku']);
        exit;
    }

    // Sprawdź czy żądana ilość nie przekracza stanu magazynowego
    if ($quantity > $item['stock']) {
        echo json_encode(['success' => false, 'message' => 'Niewystarczająca ilość produktu w magazynie']);
        exit;
    }

    // Aktualizuj ilość w koszyku
    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$quantity, $item_id, $_SESSION['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Ilość produktu została zaktualizowana']);

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd podczas aktualizacji koszyka: ' . $e->getMessage()]);
}
?> 