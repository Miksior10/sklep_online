<?php
// Połączenie z bazą danych
$host = 'localhost';
$db   = 'sklep_online';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    echo "<h1>Naprawa ograniczeń kluczy obcych</h1>";
    
    // Sprawdź czy tabela order_status_history istnieje
    try {
        $pdo->query("SELECT 1 FROM order_status_history LIMIT 1");
        
        // Usuń istniejące ograniczenie klucza obcego
        echo "Usuwanie istniejącego ograniczenia klucza obcego...<br>";
        
        // Pobierz nazwę ograniczenia
        $stmt = $pdo->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'order_status_history'
            AND REFERENCED_TABLE_NAME = 'orders'
            AND REFERENCED_COLUMN_NAME = 'id'
        ");
        
        $constraint = $stmt->fetchColumn();
        
        if ($constraint) {
            $pdo->exec("ALTER TABLE order_status_history DROP FOREIGN KEY `$constraint`");
            echo "Usunięto ograniczenie klucza obcego: $constraint<br>";
        } else {
            echo "Nie znaleziono ograniczenia klucza obcego do usunięcia.<br>";
        }
        
        // Dodaj nowe ograniczenie klucza obcego z CASCADE
        echo "Dodawanie nowego ograniczenia klucza obcego z CASCADE...<br>";
        $pdo->exec("
            ALTER TABLE order_status_history
            ADD CONSTRAINT order_status_history_ibfk_1
            FOREIGN KEY (order_id) REFERENCES orders(id)
            ON DELETE CASCADE
        ");
        echo "Dodano nowe ograniczenie klucza obcego z CASCADE.<br>";
        
    } catch (PDOException $e) {
        echo "Tabela order_status_history nie istnieje. Tworzenie tabeli...<br>";
        $pdo->exec("
            CREATE TABLE order_status_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                status VARCHAR(50) NOT NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            )
        ");
        echo "Utworzono tabelę order_status_history z odpowiednim ograniczeniem klucza obcego.<br>";
    }
    
    // Sprawdź inne tabele, które mogą mieć podobne problemy
    $tables_to_check = ['order_items', 'shipping_addresses', 'payments', 'vouchers'];
    
    foreach ($tables_to_check as $table) {
        try {
            $pdo->query("SELECT 1 FROM $table LIMIT 1");
            echo "<h2>Sprawdzanie tabeli $table</h2>";
            
            // Pobierz nazwę ograniczenia
            $stmt = $pdo->query("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '$table'
                AND REFERENCED_TABLE_NAME = 'orders'
                AND REFERENCED_COLUMN_NAME = 'id'
            ");
            
            $constraint = $stmt->fetchColumn();
            
            if ($constraint) {
                $pdo->exec("ALTER TABLE $table DROP FOREIGN KEY `$constraint`");
                echo "Usunięto ograniczenie klucza obcego: $constraint<br>";
                
                $pdo->exec("
                    ALTER TABLE $table
                    ADD CONSTRAINT {$table}_ibfk_1
                    FOREIGN KEY (order_id) REFERENCES orders(id)
                    ON DELETE CASCADE
                ");
                echo "Dodano nowe ograniczenie klucza obcego z CASCADE dla tabeli $table.<br>";
            } else {
                echo "Nie znaleziono ograniczenia klucza obcego dla tabeli $table. Dodawanie nowego...<br>";
                
                try {
                    $pdo->exec("
                        ALTER TABLE $table
                        ADD CONSTRAINT {$table}_ibfk_1
                        FOREIGN KEY (order_id) REFERENCES orders(id)
                        ON DELETE CASCADE
                    ");
                    echo "Dodano nowe ograniczenie klucza obcego z CASCADE dla tabeli $table.<br>";
                } catch (PDOException $e) {
                    echo "Błąd podczas dodawania ograniczenia dla tabeli $table: " . $e->getMessage() . "<br>";
                }
            }
            
        } catch (PDOException $e) {
            echo "Tabela $table nie istnieje. Pomijanie.<br>";
        }
    }
    
    echo "<h2>Operacja zakończona</h2>";
    echo "Wszystkie ograniczenia kluczy obcych zostały zaktualizowane, aby umożliwić kaskadowe usuwanie.<br>";
    echo "Teraz powinno być możliwe usuwanie zamówień bez naruszania ograniczeń integralności.<br>";
    
} catch (PDOException $e) {
    echo "Błąd: " . $e->getMessage();
}
?>

<p>
    <a href="admin_orders.php">Powrót do listy zamówień</a>
</p> 