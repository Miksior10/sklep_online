<?php
require_once 'config.php';

try {
    // Sprawdź czy tabela settings istnieje
    $tableExists = $pdo->query("SHOW TABLES LIKE 'settings'")->rowCount() > 0;

    if (!$tableExists) {
        echo "Tabela settings nie istnieje. Najpierw uruchom create_settings_table.php<br>";
        exit();
    }

    // Pobierz strukturę tabeli
    $columns = $pdo->query("SHOW COLUMNS FROM settings")->fetchAll(PDO::FETCH_COLUMN);
    echo "Aktualna struktura tabeli settings:<br>";
    foreach ($columns as $column) {
        echo "- " . $column . "<br>";
    }
    echo "<br>";

    // Lista kolumn do dodania
    $columnsToAdd = [
        'shop_name' => "ALTER TABLE settings ADD COLUMN shop_name VARCHAR(100) DEFAULT 'Sklep Online'",
        'shop_email' => "ALTER TABLE settings ADD COLUMN shop_email VARCHAR(100) DEFAULT 'kontakt@example.com'",
        'contact_phone' => "ALTER TABLE settings ADD COLUMN contact_phone VARCHAR(20) DEFAULT ''",
        'contact_address' => "ALTER TABLE settings ADD COLUMN contact_address TEXT DEFAULT ''",
        'footer_text' => "ALTER TABLE settings ADD COLUMN footer_text TEXT DEFAULT ''",
        'maintenance_mode' => "ALTER TABLE settings ADD COLUMN maintenance_mode TINYINT(1) DEFAULT 0"
    ];

    $addedColumns = 0;

    // Dodaj brakujące kolumny
    foreach ($columnsToAdd as $columnName => $sql) {
        if (!in_array($columnName, $columns)) {
            $pdo->exec($sql);
            echo "Dodano kolumnę: " . $columnName . "<br>";
            $addedColumns++;
        }
    }

    if ($addedColumns === 0) {
        echo "Wszystkie wymagane kolumny już istnieją.<br>";
    } else {
        echo "<br>Dodano " . $addedColumns . " brakujących kolumn.<br>";
    }

    // Sprawdź czy istnieje rekord z id=1
    $recordExists = $pdo->query("SELECT COUNT(*) FROM settings WHERE id = 1")->fetchColumn();

    if ($recordExists == 0) {
        // Dodaj pierwszy rekord, jeśli nie istnieje
        $stmt = $pdo->prepare("INSERT INTO settings 
            (id, setting_key, setting_value, shop_name, shop_email) 
            VALUES (1, 'general', 'general', 'Sklep Online', 'kontakt@example.com')");
        
        $stmt->execute();
        echo "Dodano podstawowy rekord ustawień z id=1<br>";
    }

    echo "<br>Aktualizacja tabeli settings zakończona pomyślnie!<br>";

} catch (PDOException $e) {
    echo "Wystąpił błąd: " . $e->getMessage();
}
?> 