<?php
require_once 'config.php';

try {
    // Sprawdź czy kolumna już istnieje
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'discount_amount'");
    $column_exists = $stmt->fetch();

    if (!$column_exists) {
        // Dodaj kolumnę discount_amount
        $sql = "ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00 AFTER shipping_cost";
        $pdo->exec($sql);
        echo "Kolumna discount_amount została dodana do tabeli orders.";
    } else {
        echo "Kolumna discount_amount już istnieje w tabeli orders.";
    }
} catch (PDOException $e) {
    echo "Wystąpił błąd: " . $e->getMessage();
}
?> 