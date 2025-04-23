<?php
require_once 'config.php';

try {
    // Odczytaj plik SQL
    $sql = file_get_contents('database.sql');
    
    // Podziel skrypt na pojedyncze zapytania
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    // Wykonaj każde zapytanie
    foreach ($queries as $query) {
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }
    
    echo "Baza danych została pomyślnie utworzona!";
    
} catch(PDOException $e) {
    echo "Błąd podczas tworzenia bazy danych: " . $e->getMessage();
}
?> 