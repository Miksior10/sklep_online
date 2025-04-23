<?php
session_start();
require_once '../config.php';

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Musisz być zalogowany, aby usunąć produkt z koszyka']);
    exit;
}

// Pobierz dane z żądania
$data = json_decode(file_get_contents('php://input'), true);
$item_id = $data['item_id'] ?? null;

if (!$item_id) {
    echo json_encode(['success' => false, 'message' => 'Nie podano ID produktu']);
    exit;
}

try {
    // Sprawdź czy produkt należy do zalogowanego użytkownika
    $stmt = $pdo->prepare("SELECT * FROM cart_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$item_id, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Produkt nie został znaleziony w koszyku']);
        exit;
    }
    
    // Usuń produkt z koszyka
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$item_id, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Produkt został usunięty z koszyka']);

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd podczas usuwania produktu z koszyka: ' . $e->getMessage()]);
} 