<?php
require_once 'config.php';

try {
    // Sprawdź czy kolumna stock istnieje
    $stmt = $pdo->query("SHOW COLUMNS FROM product_colors LIKE 'stock'");
    $columnExists = $stmt->rowCount() > 0;

    if (!$columnExists) {
        // Dodaj kolumnę stock jeśli nie istnieje
        $pdo->exec("ALTER TABLE product_colors ADD COLUMN stock INT DEFAULT 0");
        echo "Dodano kolumnę stock do tabeli product_colors\n";
    } else {
        echo "Kolumna stock już istnieje w tabeli product_colors\n";
    }
} catch (PDOException $e) {
    echo "Błąd: " . $e->getMessage() . "\n";
} 