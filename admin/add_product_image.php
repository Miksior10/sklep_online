<?php
session_start();
require_once '../config.php';

// Sprawdź, czy użytkownik jest zalogowany jako admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

// Sprawdź, czy przesłano odpowiednie dane
if (!isset($_POST['product_id']) || !isset($_FILES['new_product_image']) || $_FILES['new_product_image']['error'] !== UPLOAD_ERR_OK) {
    header('Location: ../product_details.php?id=' . $_POST['product_id'] . '&error=missing_data');
    exit;
}

$product_id = (int) $_POST['product_id'];

// Sprawdź, czy produkt istnieje
$stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
$stmt->execute([$product_id]);
if (!$stmt->fetch()) {
    header('Location: ../index.php');
    exit;
}

// Obsługa przesyłania zdjęcia
$upload_dir = '../uploads/products/';

// Sprawdź czy katalog istnieje, jeśli nie, utwórz go
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Sprawdź typ pliku
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$file_type = $_FILES['new_product_image']['type'];

if (!in_array($file_type, $allowed_types)) {
    header('Location: ../product_details.php?id=' . $product_id . '&error=invalid_file_type');
    exit;
}

// Generuj unikalną nazwę pliku
$file_extension = pathinfo($_FILES['new_product_image']['name'], PATHINFO_EXTENSION);
$new_filename = uniqid('product_') . '.' . $file_extension;
$target_file = $upload_dir . $new_filename;
$db_image_path = 'uploads/products/' . $new_filename;

// Sprawdź czy biblioteka GD jest dostępna - dla zmniejszenia rozmiaru zdjęcia
if (function_exists('imagecreatefromjpeg')) {
    // Pobierz informacje o zdjęciu
    list($width, $height, $type) = getimagesize($_FILES['new_product_image']['tmp_name']);
    
    // Ustaw maksymalne wymiary zdjęcia
    $max_width = 800;
    $max_height = 800;
    
    // Stwórz źródłowy obraz na podstawie typu pliku
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source_image = imagecreatefromjpeg($_FILES['new_product_image']['tmp_name']);
            break;
        case IMAGETYPE_PNG:
            $source_image = imagecreatefrompng($_FILES['new_product_image']['tmp_name']);
            break;
        case IMAGETYPE_GIF:
            $source_image = imagecreatefromgif($_FILES['new_product_image']['tmp_name']);
            break;
        default:
            $source_image = false;
    }
    
    if ($source_image && ($width > $max_width || $height > $max_height)) {
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
        
        // Zapisz przeskalowany obraz
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($new_image, $target_file, 85); // 85 = jakość kompresji
                break;
            case IMAGETYPE_PNG:
                imagepng($new_image, $target_file, 8); // 8 = poziom kompresji (0-9)
                break;
            case IMAGETYPE_GIF:
                imagegif($new_image, $target_file);
                break;
        }
        
        // Zwolnij pamięć
        imagedestroy($new_image);
        imagedestroy($source_image);
    } else {
        // Jeśli obraz jest już odpowiedniego rozmiaru lub nie udało się stworzyć źródłowego obrazu
        move_uploaded_file($_FILES['new_product_image']['tmp_name'], $target_file);
    }
} else {
    // Jeśli biblioteka GD nie jest dostępna, po prostu przenieś plik
    move_uploaded_file($_FILES['new_product_image']['tmp_name'], $target_file);
}

// Zapisz informacje o zdjęciu w bazie danych
try {
    // Sprawdź czy tabela istnieje, jeśli nie - utwórz ją
    $pdo->query("
        CREATE TABLE IF NOT EXISTS product_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            image_url VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )
    ");
    
    $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
    $stmt->execute([$product_id, $db_image_path]);
    
    // Przekieruj z powrotem do strony produktu
    header('Location: ../product_details.php?id=' . $product_id . '&success=image_added');
    exit;
} catch (PDOException $e) {
    // W przypadku błędu przekieruj z komunikatem o błędzie
    header('Location: ../product_details.php?id=' . $product_id . '&error=' . urlencode($e->getMessage()));
    exit;
} 