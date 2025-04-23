<?php
// Nadrzędny skrypt do uruchamiania wszystkich napraw i tabel
session_start();
require_once '../config.php';

// Funkcja do wyświetlania komunikatów
function showMessage($message, $type = 'info') {
    $color = '';
    switch ($type) {
        case 'success':
            $color = '#28a745';
            break;
        case 'error':
            $color = '#dc3545';
            break;
        case 'warning':
            $color = '#ffc107';
            break;
        default:
            $color = '#17a2b8';
    }
    
    echo "<div style='margin: 10px 0; padding: 10px; border-radius: 5px; background-color: {$color}; color: white;'>{$message}</div>";
}

// Nagłówek strony
echo "<!DOCTYPE html>
<html lang='pl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Naprawa systemu sklepu</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        h1, h2 {
            color: #333;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border-left: 5px solid #17a2b8;
        }
    </style>
</head>
<body>
    <h1>Naprawa systemu sklepu</h1>
    <div class='container'>
        <h2>Uruchom wszystkie naprawy</h2>
        <p>Ten skrypt tworzy i naprawia wszystkie potrzebne tabele w bazie danych.</p>
        <a href='?run=all' class='btn'>Uruchom wszystkie naprawy</a>
        <a href='../admin_panel.php' class='btn' style='background-color: #6c757d;'>Powrót do panelu admina</a>
    </div>";

// Jeśli wybrano uruchomienie napraw
if (isset($_GET['run']) && $_GET['run'] == 'all') {
    echo "<div class='result'>";
    echo "<h3>Wyniki napraw:</h3>";
    
    // 1. Tabela settings
    try {
        echo "<h4>1. Tworzenie tabeli settings</h4>";
        
        // Sprawdź czy tabela settings istnieje
        $tableExists = $pdo->query("SHOW TABLES LIKE 'settings'")->rowCount() > 0;

        if (!$tableExists) {
            // Utwórz tabelę settings
            $sql = "CREATE TABLE settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(50) NOT NULL UNIQUE,
                setting_value TEXT NOT NULL,
                shop_name VARCHAR(100) DEFAULT 'Sklep Online',
                shop_email VARCHAR(100) DEFAULT 'kontakt@example.com',
                contact_phone VARCHAR(20) DEFAULT '',
                contact_address TEXT DEFAULT '',
                footer_text TEXT DEFAULT '',
                maintenance_mode TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $pdo->exec($sql);
            showMessage("Tabela settings została utworzona.", 'success');

            // Dodaj podstawowe ustawienia
            $defaultSettings = [
                ['vat_rate', '23'],
                ['shipping_cost_courier', '15.00'],
                ['shipping_cost_pickup', '0.00'],
                ['free_shipping_threshold', '200.00'],
                ['currency', 'PLN'],
                ['enable_vouchers', '1']
            ];

            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            
            foreach ($defaultSettings as $setting) {
                $stmt->execute($setting);
            }
            
            // Dodaj rekord z id=1
            $stmt = $pdo->prepare("INSERT INTO settings 
                (id, setting_key, setting_value, shop_name, shop_email) 
                VALUES (1, 'general', 'general', 'Sklep Online', 'kontakt@example.com')
                ON DUPLICATE KEY UPDATE setting_key = setting_key");
            $stmt->execute();
            
            showMessage("Dodano podstawowe ustawienia.", 'success');
        } else {
            showMessage("Tabela settings już istnieje.", 'info');
            
            // Sprawdź czy są wszystkie kolumny
            $columns = $pdo->query("SHOW COLUMNS FROM settings")->fetchAll(PDO::FETCH_COLUMN);
            $requiredColumns = [
                'id', 'setting_key', 'setting_value', 'shop_name', 'shop_email', 
                'contact_phone', 'contact_address', 'footer_text', 'maintenance_mode'
            ];
            
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (!empty($missingColumns)) {
                // Dodaj brakujące kolumny
                foreach ($missingColumns as $column) {
                    switch ($column) {
                        case 'shop_name':
                            $pdo->exec("ALTER TABLE settings ADD COLUMN shop_name VARCHAR(100) DEFAULT 'Sklep Online'");
                            break;
                        case 'shop_email':
                            $pdo->exec("ALTER TABLE settings ADD COLUMN shop_email VARCHAR(100) DEFAULT 'kontakt@example.com'");
                            break;
                        case 'contact_phone':
                            $pdo->exec("ALTER TABLE settings ADD COLUMN contact_phone VARCHAR(20) DEFAULT ''");
                            break;
                        case 'contact_address':
                        case 'footer_text':
                            $pdo->exec("ALTER TABLE settings ADD COLUMN {$column} TEXT DEFAULT ''");
                            break;
                        case 'maintenance_mode':
                            $pdo->exec("ALTER TABLE settings ADD COLUMN maintenance_mode TINYINT(1) DEFAULT 0");
                            break;
                    }
                }
                showMessage("Dodano brakujące kolumny: " . implode(", ", $missingColumns), 'success');
            }
            
            // Sprawdź czy istnieje rekord z id=1
            $recordExists = $pdo->query("SELECT COUNT(*) FROM settings WHERE id = 1")->fetchColumn();
            
            if ($recordExists == 0) {
                $stmt = $pdo->prepare("INSERT INTO settings 
                    (id, setting_key, setting_value, shop_name, shop_email) 
                    VALUES (1, 'general', 'general', 'Sklep Online', 'kontakt@example.com')");
                $stmt->execute();
                showMessage("Dodano brakujący rekord ustawień z id=1", 'success');
            }
        }
    } catch (PDOException $e) {
        showMessage("Błąd podczas pracy z tabelą settings: " . $e->getMessage(), 'error');
    }
    
    // 2. Tabela featured_products
    try {
        echo "<h4>2. Tworzenie tabeli featured_products</h4>";
        
        // Sprawdź czy tabela featured_products istnieje
        $tableExists = $pdo->query("SHOW TABLES LIKE 'featured_products'")->rowCount() > 0;

        if (!$tableExists) {
            // Utwórz tabelę featured_products
            $sql = "CREATE TABLE featured_products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                position INT NOT NULL DEFAULT 0,
                added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )";
            $pdo->exec($sql);
            showMessage("Tabela featured_products została utworzona.", 'success');
        } else {
            showMessage("Tabela featured_products już istnieje.", 'info');
        }
    } catch (PDOException $e) {
        showMessage("Błąd podczas pracy z tabelą featured_products: " . $e->getMessage(), 'error');
    }
    
    // 3. Tabela vouchers
    try {
        echo "<h4>3. Tworzenie tabeli vouchers</h4>";
        
        // Sprawdź czy tabela vouchers istnieje
        $tableExists = $pdo->query("SHOW TABLES LIKE 'vouchers'")->rowCount() > 0;

        if (!$tableExists) {
            // Utwórz tabelę vouchers
            $sql = "CREATE TABLE vouchers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(4) NOT NULL UNIQUE,
                amount DECIMAL(10,2) NOT NULL,
                is_used TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $pdo->exec($sql);
            showMessage("Tabela vouchers została utworzona.", 'success');

            // Dodaj przykładowy voucher
            $stmt = $pdo->prepare("INSERT INTO vouchers (code, amount) VALUES (?, ?)");
            $stmt->execute(['1234', 50.00]);
            showMessage("Dodano przykładowy voucher o kodzie: 1234 i wartości: 50.00 PLN", 'success');
        } else {
            showMessage("Tabela vouchers już istnieje.", 'info');
        }
    } catch (PDOException $e) {
        showMessage("Błąd podczas pracy z tabelą vouchers: " . $e->getMessage(), 'error');
    }
    
    echo "</div>";
}

echo "</body></html>";
?> 