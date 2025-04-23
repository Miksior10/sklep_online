<?php
session_start();
require_once '../config.php';

// Sprawdzenie uprawnień
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<div style='color: red; text-align: center; margin: 50px;'>
        <h2>Brak uprawnień</h2>
        <p>Musisz być zalogowany jako administrator, aby zobaczyć tę stronę.</p>
        <a href='../login.php'>Zaloguj się</a>
    </div>";
    exit();
}

// Nagłówek strony
echo "<!DOCTYPE html>
<html lang='pl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Tworzenie tabeli settings</title>
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
        .success {
            color: #28a745;
            padding: 10px;
            background-color: #d4edda;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            color: #17a2b8;
            padding: 10px;
            background-color: #d1ecf1;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: #dc3545;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 5px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
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
    </style>
</head>
<body>
    <div class='container'>
        <h1>Tworzenie tabeli settings</h1>";

try {
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
        echo "<div class='success'>Tabela settings została utworzona.</div>";

        // Dodaj podstawowe ustawienia
        $defaultSettings = [
            ['vat_rate', '23'],
            ['shipping_cost_courier', '15.00'],
            ['shipping_cost_pickup', '0.00'],
            ['free_shipping_threshold', '200.00'],
            ['currency', 'PLN'],
            ['shop_name', 'Sklep Online'],
            ['shop_email', 'kontakt@example.com'],
            ['enable_vouchers', '1']
        ];

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        
        foreach ($defaultSettings as $setting) {
            $stmt->execute($setting);
            echo "<div class='success'>Dodano ustawienie: {$setting[0]} = {$setting[1]}</div>";
        }
        
        // Dodaj rekord z id=1
        $stmt = $pdo->prepare("INSERT INTO settings 
            (id, setting_key, setting_value, shop_name, shop_email) 
            VALUES (1, 'general', 'general', 'Sklep Online', 'kontakt@example.com')
            ON DUPLICATE KEY UPDATE setting_key = setting_key");
        $stmt->execute();
        echo "<div class='success'>Dodano główny rekord ustawień.</div>";
    } else {
        echo "<div class='info'>Tabela settings już istnieje.</div>";
        
        // Sprawdź strukturę tabeli
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
                echo "<div class='success'>Dodano brakującą kolumnę: {$column}</div>";
            }
        }
        
        // Sprawdź czy istnieje rekord z id=1
        $recordExists = $pdo->query("SELECT COUNT(*) FROM settings WHERE id = 1")->fetchColumn();
        
        if ($recordExists == 0) {
            $stmt = $pdo->prepare("INSERT INTO settings 
                (id, setting_key, setting_value, shop_name, shop_email) 
                VALUES (1, 'general', 'general', 'Sklep Online', 'kontakt@example.com')");
            $stmt->execute();
            echo "<div class='success'>Dodano brakujący rekord ustawień z id=1</div>";
        }
        
        // Wyświetl aktualne ustawienia
        echo "<h2>Aktualne ustawienia:</h2>";
        $settings = $pdo->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($settings) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Klucz</th><th>Wartość</th></tr>";
            foreach ($settings as $setting) {
                echo "<tr>";
                echo "<td>" . $setting['id'] . "</td>";
                echo "<td>" . htmlspecialchars($setting['setting_key']) . "</td>";
                echo "<td>" . htmlspecialchars($setting['setting_value']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='info'>Brak ustawień w bazie danych.</div>";
        }
    }

    // Wyświetl strukturę tabeli
    $columns = $pdo->query("SHOW COLUMNS FROM settings")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Struktura tabeli settings:</h2>";
    echo "<table>";
    echo "<tr><th>Pole</th><th>Typ</th><th>Null</th><th>Klucz</th><th>Domyślnie</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        foreach ($column as $key => $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "<div class='error'>Wystąpił błąd: " . $e->getMessage() . "</div>";
}

echo "
        <div style='margin-top: 20px;'>
            <a href='index.php' class='btn' style='background-color: #6c757d;'>Powrót do listy napraw</a>
            <a href='../admin_panel.php' class='btn' style='background-color: #17a2b8;'>Powrót do panelu admina</a>
        </div>
    </div>
</body>
</html>";
?> 