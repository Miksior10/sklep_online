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
    
    echo "<h1>Naprawa funkcji usuwania zamówień</h1>";
    
    // Sprawdź czy plik admin_order_details.php istnieje
    if (file_exists('admin_order_details.php')) {
        // Odczytaj zawartość pliku
        $file_content = file_get_contents('admin_order_details.php');
        
        // Znajdź fragment kodu odpowiedzialny za usuwanie zamówień
        if (strpos($file_content, 'case \'delete_order\'') !== false) {
            // Zastąp kod usuwania zamówień nowym kodem, który najpierw usuwa historię statusów
            $old_code = "case 'delete_order':
                try {
                    // Dzięki kaskadowemu usuwaniu, możemy po prostu usunąć zamówienie
                    // a wszystkie powiązane rekordy zostaną usunięte automatycznie
                    \$stmt = \$pdo->prepare(\"DELETE FROM orders WHERE id = ?\");
                    \$stmt->execute([\$order_id]);";
            
            $new_code = "case 'delete_order':
                try {
                    // Najpierw usuń rekordy z tabeli order_status_history
                    \$stmt = \$pdo->prepare(\"DELETE FROM order_status_history WHERE order_id = ?\");
                    \$stmt->execute([\$order_id]);
                    
                    // Następnie usuń zamówienie
                    \$stmt = \$pdo->prepare(\"DELETE FROM orders WHERE id = ?\");
                    \$stmt->execute([\$order_id]);";
            
            // Zastąp stary kod nowym
            $new_file_content = str_replace($old_code, $new_code, $file_content);
            
            // Zapisz zmodyfikowany plik
            if ($new_file_content != $file_content) {
                // Utwórz kopię zapasową oryginalnego pliku
                file_put_contents('admin_order_details.php.bak', $file_content);
                echo "<p>Utworzono kopię zapasową oryginalnego pliku admin_order_details.php.</p>";
                
                // Zapisz zmodyfikowany plik
                file_put_contents('admin_order_details.php', $new_file_content);
                echo "<p class='success'>Zmodyfikowano plik admin_order_details.php. Teraz funkcja usuwania zamówień najpierw usuwa historię statusów.</p>";
            } else {
                echo "<p>Nie znaleziono fragmentu kodu do zastąpienia. Plik nie został zmodyfikowany.</p>";
            }
        } else {
            echo "<p>Nie znaleziono fragmentu kodu do zastąpienia. Plik nie został zmodyfikowany.</p>";
        }
    } else {
        echo "<p>Nie znaleziono pliku admin_order_details.php do zmodyfikowania.</p>";
    }
} catch (PDOException $e) {
    echo "<h2>Wystąpił błąd:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
} 