<?php
require_once 'config.php';

try {
    // Sprawdź czy kolumna już istnieje
    $columns = $pdo->query("DESCRIBE products")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('is_featured', $columns)) {
        // Dodaj kolumnę is_featured
        $pdo->exec("ALTER TABLE products ADD COLUMN is_featured TINYINT(1) DEFAULT 0");
        echo "Kolumna 'is_featured' została dodana do tabeli products.<br>";
    } else {
        echo "Kolumna 'is_featured' już istnieje w tabeli products.<br>";
    }
    
    // Wyświetl aktualną strukturę tabeli
    echo "<h3>Aktualna struktura tabeli products:</h3>";
    $columns = $pdo->query("DESCRIBE products")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Kolumna</th><th>Typ</th><th>Null</th><th>Klucz</th><th>Domyślne</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "Błąd: " . $e->getMessage();
}

echo "<p><a href='admin_featured_products.php'>Powrót do zarządzania wyróżnionymi produktami</a></p>";
?> 