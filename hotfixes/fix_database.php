<?php
require_once 'config.php';

// Sprawdź strukturę tabeli orders
try {
    $table_info = $pdo->query("DESCRIBE orders")->fetchAll(PDO::FETCH_COLUMN);
    
    // Dodaj kolumnę status jeśli nie istnieje
    if (!in_array('status', $table_info)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN status VARCHAR(20) DEFAULT 'new'");
        echo "Dodano kolumnę 'status' do tabeli orders<br>";
    }
    
    // Dodaj kolumnę payment_method jeśli nie istnieje
    if (!in_array('payment_method', $table_info)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(20) DEFAULT 'card'");
        echo "Dodano kolumnę 'payment_method' do tabeli orders<br>";
    }
    
    // Dodaj kolumnę discount jeśli nie istnieje
    if (!in_array('discount', $table_info)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN discount DECIMAL(10, 2) DEFAULT 0");
        echo "Dodano kolumnę 'discount' do tabeli orders<br>";
    }
} catch (PDOException $e) {
    echo "Błąd podczas sprawdzania tabeli orders: " . $e->getMessage() . "<br>";
}

// Sprawdź strukturę tabeli payments
try {
    $table_info = $pdo->query("DESCRIBE payments")->fetchAll(PDO::FETCH_COLUMN);
    
    // Dodaj kolumnę status jeśli nie istnieje
    if (!in_array('status', $table_info)) {
        $pdo->exec("ALTER TABLE payments ADD COLUMN status VARCHAR(20) DEFAULT 'completed'");
        echo "Dodano kolumnę 'status' do tabeli payments<br>";
    }
    
    // Sprawdź czy istnieje kolumna payment_date
    if (!in_array('payment_date', $table_info)) {
        $pdo->exec("ALTER TABLE payments ADD COLUMN payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "Dodano kolumnę 'payment_date' do tabeli payments<br>";
    }
} catch (PDOException $e) {
    echo "Błąd podczas sprawdzania tabeli payments: " . $e->getMessage() . "<br>";
}

// Sprawdź czy istnieje tabela vouchers
try {
    $pdo->query("SELECT 1 FROM vouchers LIMIT 1");
    echo "Tabela vouchers już istnieje<br>";
} catch (PDOException $e) {
    // Tabela nie istnieje, utwórz ją
    try {
        $pdo->exec("CREATE TABLE vouchers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            code VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            is_used TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "Utworzono tabelę 'vouchers'<br>";
    } catch (PDOException $e2) {
        echo "Błąd podczas tworzenia tabeli vouchers: " . $e2->getMessage() . "<br>";
    }
}

// Sprawdź czy istnieje tabela order_status_history
try {
    $pdo->query("SELECT 1 FROM order_status_history LIMIT 1");
    echo "Tabela order_status_history już istnieje<br>";
    
    // Sprawdź czy kolumny notes i admin_id istnieją
    $columns = $pdo->query("DESCRIBE order_status_history")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('notes', $columns)) {
        $pdo->exec("ALTER TABLE order_status_history ADD COLUMN notes TEXT");
        echo "Dodano kolumnę 'notes' do tabeli order_status_history<br>";
    }
    
    if (!in_array('admin_id', $columns)) {
        $pdo->exec("ALTER TABLE order_status_history ADD COLUMN admin_id INT");
        echo "Dodano kolumnę 'admin_id' do tabeli order_status_history<br>";
    }
} catch (PDOException $e) {
    // Tabela nie istnieje, utwórz ją
    try {
        $pdo->exec("CREATE TABLE order_status_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            status VARCHAR(20) NOT NULL,
            notes TEXT,
            admin_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "Utworzono tabelę 'order_status_history'<br>";
    } catch (PDOException $e2) {
        echo "Błąd podczas tworzenia tabeli order_status_history: " . $e2->getMessage() . "<br>";
    }
}

// Sprawdź, czy kolumna 'category' istnieje w tabeli 'products'
$checkColumn = $pdo->query("SHOW COLUMNS FROM products LIKE 'category'");

if ($checkColumn->rowCount() == 0) {
    // Kolumna nie istnieje, dodaj ją
    $pdo->exec("ALTER TABLE products ADD COLUMN category VARCHAR(50) DEFAULT NULL");
    echo "<p>Dodano kolumnę 'category' do tabeli 'products'.</p>";
} else {
    echo "<p>Kolumna 'category' już istnieje w tabeli 'products'.</p>";
}

// Sprawdź, czy kolumna 'image_url' istnieje
$checkImageColumn = $pdo->query("SHOW COLUMNS FROM products LIKE 'image_url'");

if ($checkImageColumn->rowCount() == 0) {
    // Kolumna nie istnieje, dodaj ją
    $pdo->exec("ALTER TABLE products ADD COLUMN image_url VARCHAR(255) DEFAULT NULL");
    echo "<p>Dodano kolumnę 'image_url' do tabeli 'products'.</p>";
} else {
    echo "<p>Kolumna 'image_url' już istnieje w tabeli 'products'.</p>";
}

// Utwórz katalog na zdjęcia, jeśli nie istnieje
$upload_dir = 'uploads/products/';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0777, true)) {
        echo "<p>Utworzono katalog '$upload_dir' dla zdjęć produktów.</p>";
    } else {
        echo "<p>Nie udało się utworzyć katalogu '$upload_dir'. Sprawdź uprawnienia.</p>";
    }
} else {
    echo "<p>Katalog '$upload_dir' już istnieje.</p>";
}

// Utwórz plik tekstowy z informacją o domyślnym zdjęciu, jeśli nie ma default.jpg
if (!file_exists($upload_dir . 'default.jpg')) {
    $info_file = $upload_dir . 'README.txt';
    file_put_contents($info_file, "Umieść plik 'default.jpg' w tym katalogu jako domyślne zdjęcie dla produktów.\n");
    echo "<p>Utwórz plik 'default.jpg' w katalogu '$upload_dir' jako domyślne zdjęcie.</p>";
}

// Aktualizuj kategorie dla istniejących produktów (opcjonalnie)
$products = $pdo->query("SELECT id, name FROM products WHERE category IS NULL")->fetchAll(PDO::FETCH_ASSOC);

if (!empty($products)) {
    echo "<p>Automatycznie przypisywanie kategorii do " . count($products) . " produktów bez kategorii:</p>";
    echo "<ul>";
    
    foreach ($products as $product) {
        $name = strtolower($product['name']);
        $category = 'Inne';
        
        // Proste przypisanie kategorii na podstawie nazwy
        if (strpos($name, 'iphone') !== false || strpos($name, 'samsung') !== false || 
            strpos($name, 'xiaomi') !== false || strpos($name, 'smart') !== false) {
            $category = 'Smartphone';
        } elseif (strpos($name, 'laptop') !== false || strpos($name, 'macbook') !== false || 
                 strpos($name, 'notebook') !== false || strpos($name, 'dell') !== false) {
            $category = 'Laptop';
        } elseif (strpos($name, 'tablet') !== false || strpos($name, 'ipad') !== false) {
            $category = 'Tablet';
        } elseif (strpos($name, 'headphone') !== false || strpos($name, 'słuchawki') !== false || 
                 strpos($name, 'speaker') !== false || strpos($name, 'głośnik') !== false) {
            $category = 'Audio';
        } elseif (strpos($name, 'playstation') !== false || strpos($name, 'xbox') !== false || 
                 strpos($name, 'nintendo') !== false || strpos($name, 'gaming') !== false) {
            $category = 'Gaming';
        }
        
        $stmt = $pdo->prepare("UPDATE products SET category = ? WHERE id = ?");
        $stmt->execute([$category, $product['id']]);
        
        echo "<li>Produkt ID " . $product['id'] . " (" . htmlspecialchars($product['name']) . ") - przypisano kategorię: $category</li>";
    }
    
    echo "</ul>";
}

echo "<h2>Aktualizacja zakończona pomyślnie!</h2>";
echo "<p><a href='admin_products.php' class='btn'>Powrót do zarządzania produktami</a></p>";

echo "Zakończono sprawdzanie i naprawianie struktury bazy danych.";
?> 