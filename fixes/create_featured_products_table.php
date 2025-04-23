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
    <title>Tworzenie tabeli featured_products</title>
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
        <h1>Tworzenie tabeli featured_products</h1>";

try {
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
        echo "<div class='success'>Tabela featured_products została utworzona.</div>";
    } else {
        echo "<div class='info'>Tabela featured_products już istnieje.</div>";
        
        // Wyświetl aktualnie wyróżnione produkty
        echo "<h2>Aktualne produkty na stronie głównej:</h2>";
        $sql = "SELECT fp.*, p.name, p.price FROM featured_products fp 
                JOIN products p ON fp.product_id = p.id 
                ORDER BY fp.position";
        $featuredProducts = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($featuredProducts) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Nazwa produktu</th><th>Cena</th><th>Pozycja</th></tr>";
            foreach ($featuredProducts as $product) {
                echo "<tr>";
                echo "<td>" . $product['id'] . "</td>";
                echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                echo "<td>" . number_format($product['price'], 2) . " zł</td>";
                echo "<td>" . $product['position'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='info'>Brak produktów na stronie głównej.</div>";
        }
    }

    // Wyświetl strukturę tabeli
    $columns = $pdo->query("SHOW COLUMNS FROM featured_products")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Struktura tabeli featured_products:</h2>";
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