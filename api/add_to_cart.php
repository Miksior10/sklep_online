<?php
session_start();
require_once '../config.php';

// Włącz wyświetlanie błędów
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Sprawdź, czy użytkownik jest zalogowany
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Musisz być zalogowany']);
    exit;
}

// Sprawdź, czy otrzymano wymagane dane
if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Brak wymaganych danych']);
    exit;
}

try {
    // Pobierz dane z POST
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $color = isset($_POST['color']) ? $_POST['color'] : null;
    $memory = isset($_POST['memory']) ? (int)$_POST['memory'] : null;
    $user_id = $_SESSION['user_id'];

    // Logowanie otrzymanych danych
    error_log('Received data: ' . print_r($_POST, true));

    // Sprawdź, czy produkt istnieje
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Produkt nie istnieje']);
        exit;
    }

    // Sprawdź, czy produkt jest już w koszyku
    $sql = "SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?";
    $params = [$user_id, $product_id];
    
    if ($color !== null) {
        $sql .= " AND color = ?";
        $params[] = $color;
    } else {
        $sql .= " AND color IS NULL";
    }
    
    if ($memory !== null) {
        $sql .= " AND memory = ?";
        $params[] = $memory;
    } else {
        $sql .= " AND memory IS NULL";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $existing_item = $stmt->fetch();

    if ($existing_item) {
        // Aktualizuj ilość
        $new_quantity = $existing_item['quantity'] + $quantity;
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->execute([$new_quantity, $existing_item['id']]);
    } else {
        // Dodaj nowy produkt do koszyka
        $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity, color, memory) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $quantity, $color, $memory]);
    }

    // Pobierz aktualną liczbę produktów w koszyku
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart_count = $stmt->fetch()['total'] ?? 0;

    echo json_encode([
        'success' => true,
        'message' => 'Produkt dodany do koszyka',
        'cart_count' => $cart_count
    ]);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Wystąpił błąd podczas dodawania produktu do koszyka: ' . $e->getMessage()]);
}
?> 