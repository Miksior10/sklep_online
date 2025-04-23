<?php
require_once 'config.php';

// Utwórz katalog na zdjęcia, jeśli nie istnieje
$upload_dir = 'uploads/products/';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0777, true)) {
        echo "<p>Utworzono katalog '$upload_dir' na zdjęcia produktów.</p>";
    } else {
        echo "<p>Nie udało się utworzyć katalogu '$upload_dir'. Sprawdź uprawnienia serwera.</p>";
        die();
    }
} else {
    echo "<p>Katalog '$upload_dir' już istnieje.</p>";
}

// Sprawdź, czy kolumna image_url istnieje w tabeli products
try {
    $result = $pdo->query("SHOW COLUMNS FROM products LIKE 'image_url'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN image_url VARCHAR(255)");
        echo "<p>Dodano kolumnę 'image_url' do tabeli products.</p>";
    } else {
        echo "<p>Kolumna 'image_url' już istnieje w tabeli products.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Błąd bazy danych: " . $e->getMessage() . "</p>";
    die();
}

// Stwórz prosty plik tekstowy jako informację o domyślnym obrazie
$default_image_info = $upload_dir . 'README.txt';
file_put_contents($default_image_info, "W tym katalogu należy umieścić domyślne zdjęcie 'default.jpg' oraz zdjęcia produktów.\n");
echo "<p>Utworzono plik README w katalogu zdjęć.</p>";

echo "<h2>Instrukcja:</h2>";
echo "<ol>";
echo "<li>Ręcznie umieść plik <strong>default.jpg</strong> w katalogu <strong>$upload_dir</strong> - będzie to domyślne zdjęcie dla produktów bez zdjęcia.</li>";
echo "<li>Możesz teraz dodawać zdjęcia do produktów w panelu administracyjnym.</li>";
echo "</ol>";

echo "<p>Jeśli funkcja przesyłania zdjęć nie działa, upewnij się, że:</p>";
echo "<ul>";
echo "<li>Biblioteka GD dla PHP jest zainstalowana</li>";
echo "<li>Katalog <strong>$upload_dir</strong> ma odpowiednie uprawnienia (755 lub 777)</li>";
echo "<li>Limit rozmiaru przesyłanych plików w PHP jest wystarczająco duży (sprawdź upload_max_filesize w php.ini)</li>";
echo "</ul>";

// Napisz przykładowe ścieżki dla kilku produktów
echo "<h2>Przykładowe mapowanie ścieżek zdjęć do produktów:</h2>";

echo "<p>Możesz ręcznie zaktualizować bazę danych z następującymi ścieżkami zdjęć dla produktów:</p>";
echo "<pre>";
echo "UPDATE products SET image_url = 'uploads/products/iphone.jpg' WHERE name LIKE '%iPhone%';\n";
echo "UPDATE products SET image_url = 'uploads/products/samsung.jpg' WHERE name LIKE '%Samsung%';\n";
echo "UPDATE products SET image_url = 'uploads/products/laptop.jpg' WHERE name LIKE '%Laptop%' OR name LIKE '%MacBook%';\n";
echo "UPDATE products SET image_url = 'uploads/products/tablet.jpg' WHERE name LIKE '%Tablet%' OR name LIKE '%iPad%';\n";
echo "UPDATE products SET image_url = 'uploads/products/headphones.jpg' WHERE name LIKE '%Headphones%' OR name LIKE '%słuchawki%';\n";
echo "UPDATE products SET image_url = 'uploads/products/default.jpg' WHERE image_url IS NULL OR image_url = '';\n";
echo "</pre>";

echo "<h2>Konfiguracja zakończona pomyślnie!</h2>";
echo "<p>Możesz teraz dodawać zdjęcia do produktów w panelu administracyjnym.</p>";
echo "<p><a href='admin_products.php' style='display:inline-block; padding:10px 15px; background-color:#4CAF50; color:white; text-decoration:none; border-radius:4px;'>Przejdź do zarządzania produktami</a></p>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        line-height: 1.6;
    }
    h2 {
        color: #2c3e50;
        margin-top: 30px;
    }
    p {
        margin: 10px 0;
    }
    pre {
        background-color: #f5f5f5;
        padding: 10px;
        border-radius: 5px;
        overflow-x: auto;
    }
    ol, ul {
        margin-bottom: 20px;
    }
    li {
        margin-bottom: 8px;
    }
</style> 