<?php
require_once 'config.php';

try {
    // Dodaj kolumnę memory do tabeli cart_items
    $sql = "ALTER TABLE cart_items ADD COLUMN memory INT DEFAULT NULL AFTER color";
    $pdo->exec($sql);
    
    echo "Kolumna memory została dodana do tabeli cart_items.";
} catch (PDOException $e) {
    echo "Błąd: " . $e->getMessage();
}
?> 