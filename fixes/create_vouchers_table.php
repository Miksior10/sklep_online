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
    <title>Tworzenie tabeli vouchers</title>
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
        <h1>Tworzenie tabeli vouchers</h1>";

try {
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
        echo "<div class='success'>Tabela vouchers została utworzona.</div>";

        // Dodaj przykładowy voucher do testów
        $stmt = $pdo->prepare("INSERT INTO vouchers (code, amount) VALUES (?, ?)");
        $stmt->execute(['1234', 50.00]);
        echo "<div class='success'>Dodano przykładowy voucher o kodzie: 1234 i wartości: 50.00 PLN</div>";
    } else {
        echo "<div class='info'>Tabela vouchers już istnieje.</div>";
        
        // Wyświetl aktualne vouchery
        echo "<h2>Aktualne vouchery:</h2>";
        $vouchers = $pdo->query("SELECT * FROM vouchers")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($vouchers) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Kod</th><th>Wartość</th><th>Wykorzystany</th><th>Data utworzenia</th></tr>";
            foreach ($vouchers as $voucher) {
                echo "<tr>";
                echo "<td>" . $voucher['id'] . "</td>";
                echo "<td>" . htmlspecialchars($voucher['code']) . "</td>";
                echo "<td>" . number_format($voucher['amount'], 2) . " zł</td>";
                echo "<td>" . ($voucher['is_used'] ? 'Tak' : 'Nie') . "</td>";
                echo "<td>" . htmlspecialchars($voucher['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='info'>Brak voucherów w bazie danych.</div>";
        }
    }

    // Wyświetl strukturę tabeli
    $columns = $pdo->query("SHOW COLUMNS FROM vouchers")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Struktura tabeli vouchers:</h2>";
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