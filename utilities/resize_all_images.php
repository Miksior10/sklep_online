<?php
require_once 'config.php';

try {
    // Ustaw maksymalne wymiary zdjęcia
    $max_width = 1200;
    $max_height = 1200;
    
    // Pobierz wszystkie produkty z ścieżkami zdjęć
    $stmt = $pdo->query("SELECT id, name, image_url FROM products WHERE image_url IS NOT NULL AND image_url != ''");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Przeskalowywanie zdjęć produktów</h2>";
    
    if (empty($products)) {
        echo "<p>Nie znaleziono produktów ze zdjęciami.</p>";
    } else {
        echo "<p>Znaleziono " . count($products) . " produktów ze zdjęciami:</p>";
        echo "<ul>";
        
        foreach ($products as $product) {
            $image_url = $product['image_url'];
            
            // Pomijaj domyślne zdjęcia lub nieistniejące pliki
            if (empty($image_url) || !file_exists($image_url) || $image_url == 'uploads/products/default.jpg') {
                echo "<li>Produkt ID " . $product['id'] . " (" . htmlspecialchars($product['name']) . ") - pominięto (domyślne lub brak zdjęcia)</li>";
                continue;
            }
            
            // Sprawdź, czy biblioteka GD jest dostępna
            if (!function_exists('imagecreatefromjpeg')) {
                echo "<li>Produkt ID " . $product['id'] . " (" . htmlspecialchars($product['name']) . ") - pominięto (biblioteka GD nie jest dostępna)</li>";
                continue;
            }
            
            // Pobierz informacje o zdjęciu
            list($width, $height, $type) = @getimagesize($image_url);
            
            if (!$width || !$height) {
                echo "<li>Produkt ID " . $product['id'] . " (" . htmlspecialchars($product['name']) . ") - błąd odczytu wymiarów zdjęcia</li>";
                continue;
            }
            
            // Jeśli zdjęcie jest już odpowiedniego rozmiaru, pomiń
            if ($width <= $max_width && $height <= $max_height) {
                echo "<li>Produkt ID " . $product['id'] . " (" . htmlspecialchars($product['name']) . ") - zdjęcie ma już odpowiedni rozmiar</li>";
                continue;
            }
            
            // Stwórz źródłowy obraz na podstawie typu pliku
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $source_image = @imagecreatefromjpeg($image_url);
                    break;
                case IMAGETYPE_PNG:
                    $source_image = @imagecreatefrompng($image_url);
                    break;
                case IMAGETYPE_GIF:
                    $source_image = @imagecreatefromgif($image_url);
                    break;
                default:
                    $source_image = false;
            }
            
            if (!$source_image) {
                echo "<li>Produkt ID " . $product['id'] . " (" . htmlspecialchars($product['name']) . ") - nie udało się utworzyć obrazu źródłowego</li>";
                continue;
            }
            
            // Oblicz nowe wymiary zachowując proporcje
            if ($width > $height) {
                $new_width = $max_width;
                $new_height = intval($height * $max_width / $width);
            } else {
                $new_height = $max_height;
                $new_width = intval($width * $max_height / $height);
            }
            
            // Stwórz nowy obraz o odpowiednim rozmiarze
            $new_image = imagecreatetruecolor($new_width, $new_height);
            
            // Zachowaj przezroczystość dla PNG
            if ($type == IMAGETYPE_PNG) {
                imagealphablending($new_image, false);
                imagesavealpha($new_image, true);
                $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
                imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
            }
            
            // Przeskaluj obraz
            imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            
            // Utwórz kopię zapasową oryginalnego pliku
            $backup_file = $image_url . '.bak';
            @copy($image_url, $backup_file);
            
            // Zapisz przeskalowany obraz
            $success = false;
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $success = imagejpeg($new_image, $image_url, 85);
                    break;
                case IMAGETYPE_PNG:
                    $success = imagepng($new_image, $image_url, 8);
                    break;
                case IMAGETYPE_GIF:
                    $success = imagegif($new_image, $image_url);
                    break;
            }
            
            // Zwolnij pamięć
            imagedestroy($new_image);
            imagedestroy($source_image);
            
            if ($success) {
                echo "<li>Produkt ID " . $product['id'] . " (" . htmlspecialchars($product['name']) . ") - zdjęcie zostało przeskalowane z {$width}x{$height} do {$new_width}x{$new_height}</li>";
            } else {
                echo "<li>Produkt ID " . $product['id'] . " (" . htmlspecialchars($product['name']) . ") - błąd podczas zapisywania przeskalowanego zdjęcia</li>";
                // Przywróć kopię zapasową, jeśli dostępna
                if (file_exists($backup_file)) {
                    @copy($backup_file, $image_url);
                }
            }
        }
        
        echo "</ul>";
    }
    
    echo "<h2>Operacja zakończona</h2>";
    echo "<p><a href='admin_products.php' class='btn'>Powrót do zarządzania produktami</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Błąd bazy danych: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Błąd: " . $e->getMessage() . "</p>";
}
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
    ul {
        margin: 10px 0;
        padding-left: 20px;
    }
    li {
        margin-bottom: 8px;
    }
    .btn {
        display: inline-block;
        padding: 10px 15px;
        background-color: #4CAF50;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        margin-top: 20px;
    }
    .btn:hover {
        background-color: #45a049;
    }
</style> 