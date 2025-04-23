<?php
require_once 'config.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Naprawa problemu z kolumną 'user_id'</h2>";
    
    // Sprawdź wszystkie tabele w bazie danych
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Znaleziono " . count($tables) . " tabel w bazie danych.</p>";
    
    // Najpierw sprawdźmy tabelę 'orders', która najprawdopodobniej powinna mieć kolumnę user_id
    $found_user_id = false;
    $table_with_error = '';
    
    if (in_array('orders', $tables)) {
        $columns = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('user_id', $columns)) {
            echo "<p>Tabela 'orders' nie zawiera kolumny 'user_id'. To prawdopodobnie źródło problemu.</p>";
            $table_with_error = 'orders';
        } else {
            echo "<p>Tabela 'orders' zawiera kolumnę 'user_id'. Sprawdzam inne tabele...</p>";
            $found_user_id = true;
        }
    }
    
    // Jeśli nie znaleziono problemu w 'orders', sprawdź wszystkie inne tabele
    if (!$found_user_id) {
        foreach ($tables as $table) {
            if ($table === 'orders') continue; // już sprawdziliśmy
            
            $columns = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
            
            // Sprawdź, czy zapytania używają kolumny user_id dla tabeli, która jej nie posiada
            $table_references_user_id = false;
            
            // Szukaj referencji do user_id w kodzie PHP
            $php_files = glob('*.php');
            foreach ($php_files as $file) {
                if (strpos(file_get_contents($file), "$table WHERE user_id") !== false) {
                    $table_references_user_id = true;
                    echo "<p>Znaleziono referencję do 'user_id' dla tabeli '$table' w pliku '$file'.</p>";
                    break;
                }
            }
            
            if ($table_references_user_id && !in_array('user_id', $columns)) {
                echo "<p>Tabela '$table' nie zawiera kolumny 'user_id', ale kod odnosi się do niej.</p>";
                $table_with_error = $table;
                break;
            }
        }
    }
    
    // Jeśli znaleziono tabelę z błędem, napraw ją
    if (!empty($table_with_error)) {
        echo "<h3>Naprawa tabeli '$table_with_error'</h3>";
        
        // Sprawdź, czy tabela 'users' istnieje i ma kolumnę 'id'
        $users_exists = in_array('users', $tables);
        
        if ($users_exists) {
            echo "<p>Tabela 'users' istnieje. Dodaję kolumnę 'user_id' do tabeli '$table_with_error' jako klucz obcy.</p>";
            
            // Dodaj kolumnę user_id
            $pdo->exec("ALTER TABLE $table_with_error ADD COLUMN user_id INT");
            
            // Dodaj klucz obcy, jeśli to tabela orders
            if ($table_with_error === 'orders') {
                try {
                    $pdo->exec("ALTER TABLE $table_with_error ADD FOREIGN KEY (user_id) REFERENCES users(id)");
                    echo "<p>Dodano klucz obcy dla kolumny 'user_id' odnoszący się do 'users.id'.</p>";
                } catch (Exception $e) {
                    echo "<p>Nie można dodać klucza obcego: " . $e->getMessage() . "</p>";
                    echo "<p>Kolumna została dodana, ale bez ograniczenia klucza obcego.</p>";
                }
                
                // Jeśli to tabela orders, zaktualizuj istniejące zamówienia
                echo "<p>Aktualizuję istniejące zamówienia...</p>";
                
                // Sprawdź, czy istnieje kolumna customer_id w tabeli orders
                $order_columns = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
                
                if (in_array('customer_id', $order_columns)) {
                    echo "<p>Znaleziono kolumnę 'customer_id' w tabeli 'orders'. Przenoszę wartości do kolumny 'user_id'.</p>";
                    $pdo->exec("UPDATE orders SET user_id = customer_id WHERE customer_id IS NOT NULL");
                } else {
                    echo "<p>Nie znaleziono kolumny 'customer_id' w tabeli 'orders'. Ustawiam wartość domyślną.</p>";
                    
                    // Jeśli nie ma customer_id, ustaw administratora jako domyślnego użytkownika
                    $admin = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                    
                    if ($admin) {
                        $pdo->exec("UPDATE orders SET user_id = " . $admin['id'] . " WHERE user_id IS NULL");
                        echo "<p>Przypisano administratora (ID: " . $admin['id'] . ") do istniejących zamówień.</p>";
                    } else {
                        $first_user = $pdo->query("SELECT id FROM users LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                        
                        if ($first_user) {
                            $pdo->exec("UPDATE orders SET user_id = " . $first_user['id'] . " WHERE user_id IS NULL");
                            echo "<p>Przypisano pierwszego użytkownika (ID: " . $first_user['id'] . ") do istniejących zamówień.</p>";
                        } else {
                            echo "<p>Nie znaleziono żadnych użytkowników. Zamówienia pozostaną bez przypisanego użytkownika.</p>";
                        }
                    }
                }
            }
            
            echo "<p class='success'>Naprawa zakończona pomyślnie. Kolumna 'user_id' została dodana do tabeli '$table_with_error'.</p>";
        } else {
            echo "<p class='error'>Tabela 'users' nie istnieje. Nie można dodać kolumny 'user_id' jako klucza obcego.</p>";
            
            // Dodaj kolumnę user_id bez ograniczenia klucza obcego
            $pdo->exec("ALTER TABLE $table_with_error ADD COLUMN user_id INT");
            echo "<p>Dodano kolumnę 'user_id' do tabeli '$table_with_error' bez ograniczenia klucza obcego.</p>";
        }
    } else {
        // Nie znaleziono problemu z kolumną user_id
        echo "<p>Nie znaleziono tabeli, w której brakuje kolumny 'user_id', ale kod odnosi się do niej.</p>";
        echo "<p>Błąd może być spowodowany innym problemem. Sprawdźmy dokładniej kod źródłowy.</p>";
        
        // Znajdź wszystkie pliki PHP, które zawierają zapytania SQL z 'user_id'
        $php_files = glob('*.php');
        $files_with_user_id = [];
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            if (preg_match('/WHERE\s+user_id\s*=/', $content)) {
                $files_with_user_id[] = $file;
            }
        }
        
        if (!empty($files_with_user_id)) {
            echo "<p>Znaleziono " . count($files_with_user_id) . " plików, które zawierają zapytania WHERE user_id:</p>";
            echo "<ul>";
            foreach ($files_with_user_id as $file) {
                echo "<li>" . htmlspecialchars($file) . "</li>";
            }
            echo "</ul>";
            
            echo "<p>Prawdopodobnie problem występuje w jednym z tych plików. Sprawdź, czy używają poprawnych nazw tabel i kolumn.</p>";
        } else {
            echo "<p>Nie znaleziono plików z zapytaniami WHERE user_id.</p>";
        }
        
        // Wyświetl strukturę ważnych tabel
        echo "<h3>Struktura ważnych tabel</h3>";
        $important_tables = ['users', 'orders', 'order_items', 'payments', 'shipping_addresses'];
        
        foreach ($important_tables as $table) {
            if (in_array($table, $tables)) {
                $columns = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h4>Tabela '$table'</h4>";
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
            } else {
                echo "<p>Tabela '$table' nie istnieje.</p>";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>Błąd bazy danych: " . $e->getMessage() . "</p>";
}
?>

<p><a href="admin_panel.php" class="btn">Powrót do panelu administracyjnego</a></p>

<style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        margin: 20px;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    h2, h3, h4 {
        color: #333;
        margin-top: 20px;
    }
    p {
        margin: 10px 0;
    }
    table {
        border-collapse: collapse;
        width: 100%;
        margin: 15px 0;
    }
    th {
        background-color: #f2f2f2;
        text-align: left;
    }
    .success {
        color: green;
        font-weight: bold;
    }
    .error {
        color: red;
        font-weight: bold;
    }
    .btn {
        display: inline-block;
        padding: 8px 16px;
        background-color: #4CAF50;
        color: white;
        border: none;
        cursor: pointer;
        text-decoration: none;
        border-radius: 4px;
        margin-top: 20px;
    }
    .btn:hover {
        background-color: #45a049;
    }
</style> 