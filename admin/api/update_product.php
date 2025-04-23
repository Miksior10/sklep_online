<?php
require_once '../config.php';

// Sprawdź czy użytkownik jest zalogowany jako admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Pobierz ID produktu
$product_id = $_POST['product_id'] ?? null;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Brak ID produktu']);
    exit;
}

try {
    // Aktualizuj podstawowe informacje o produkcie
    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category = ? WHERE id = ?");
    $stmt->execute([
        $_POST['name'],
        $_POST['description'],
        $_POST['price'],
        $_POST['stock'],
        $_POST['category'],
        $product_id
    ]);

    // Aktualizuj kolory produktu
    if (isset($_POST['colors']) && isset($_POST['color_names']) && isset($_POST['color_stocks'])) {
        // Usuń stare kolory
        $stmt = $pdo->prepare("DELETE FROM product_colors WHERE product_id = ?");
        $stmt->execute([$product_id]);
        
        // Dodaj nowe kolory
        $stmt = $pdo->prepare("INSERT INTO product_colors (product_id, color, color_name, stock) VALUES (?, ?, ?, ?)");
        foreach ($_POST['colors'] as $index => $color) {
            if (!empty($color) && !empty($_POST['color_names'][$index])) {
                $stock = intval($_POST['color_stocks'][$index]);
                $stmt->execute([
                    $product_id,
                    $color,
                    $_POST['color_names'][$index],
                    $stock
                ]);
            }
        }
    }

    // Aktualizuj opcje pamięci
    if (isset($_POST['memory_options']) && isset($_POST['memory_stocks'])) {
        // Usuń stare opcje pamięci
        $stmt = $pdo->prepare("DELETE FROM product_memories WHERE product_id = ?");
        $stmt->execute([$product_id]);
        
        // Dodaj nowe opcje pamięci
        $stmt = $pdo->prepare("INSERT INTO product_memories (product_id, memory_size, stock) VALUES (?, ?, ?)");
        foreach ($_POST['memory_options'] as $index => $memory) {
            if (!empty($memory)) {
                $stock = intval($_POST['memory_stocks'][$index]);
                $stmt->execute([
                    $product_id,
                    $memory,
                    $stock
                ]);
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Produkt został zaktualizowany pomyślnie']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd podczas aktualizacji produktu: ' . $e->getMessage()]);
} 