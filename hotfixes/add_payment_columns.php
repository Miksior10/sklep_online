<?php
require_once 'config.php';

try {
    // Sprawdź czy tabela payments istnieje
    $stmt = $pdo->query("SHOW TABLES LIKE 'payments'");
    $table_exists = $stmt->fetch();

    if (!$table_exists) {
        // Utwórz tabelę payments
        $sql = "CREATE TABLE payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            card_number VARCHAR(16),
            card_expiry VARCHAR(5),
            cardholder_name VARCHAR(100),
            payment_date DATETIME NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id)
        )";
        $pdo->exec($sql);
        echo "Tabela payments została utworzona.<br>";
    } else {
        // Sprawdź i dodaj brakujące kolumny
        $columns = [
            'payment_method' => "ALTER TABLE payments ADD COLUMN payment_method VARCHAR(50) NOT NULL AFTER order_id",
            'card_number' => "ALTER TABLE payments ADD COLUMN card_number VARCHAR(16) AFTER payment_method",
            'card_expiry' => "ALTER TABLE payments ADD COLUMN card_expiry VARCHAR(5) AFTER card_number",
            'cardholder_name' => "ALTER TABLE payments ADD COLUMN cardholder_name VARCHAR(100) AFTER card_expiry",
            'amount' => "ALTER TABLE payments ADD COLUMN amount DECIMAL(10,2) NOT NULL AFTER payment_date"
        ];

        foreach ($columns as $column => $sql) {
            $stmt = $pdo->query("SHOW COLUMNS FROM payments LIKE '$column'");
            if (!$stmt->fetch()) {
                try {
                    $pdo->exec($sql);
                    echo "Kolumna $column została dodana do tabeli payments.<br>";
                } catch (PDOException $e) {
                    echo "Błąd podczas dodawania kolumny $column: " . $e->getMessage() . "<br>";
                }
            }
        }
    }
    echo "Struktura tabeli payments została zaktualizowana.";
} catch (PDOException $e) {
    echo "Wystąpił błąd: " . $e->getMessage();
}
?>