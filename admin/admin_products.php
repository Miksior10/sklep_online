<?php
// Rozpocznij sesję
session_start();
require_once '../config.php';

// Sprawdź, czy użytkownik jest zalogowany jako admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

// Obsługa usuwania zdjęcia
if (isset($_POST['delete_image'])) {
    $image_id = $_POST['delete_image'];
    
    // Pobierz informacje o zdjęciu
    $stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE id = ?");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($image) {
        // Usuń plik ze serwera
        $file_path = '../' . $image['image_url'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Usuń rekord z bazy danych
        $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
        $stmt->execute([$image_id]);
        
        header("Location: admin_products.php?edit=" . $_GET['edit']);
        exit();
    }
}

// Obsługa dodawania nowych zdjęć
if (isset($_FILES['additional_images'])) {
    // Określ product_id - albo z parametru GET albo z ukrytego pola
    $product_id = $_GET['edit'] ?? $_POST['product_id'] ?? null;
    
    // Sprawdź czy faktycznie wybrano jakiś plik (czy nie ma błędu UPLOAD_ERR_NO_FILE)
    $filesSelected = false;
    foreach ($_FILES['additional_images']['error'] as $error) {
        if ($error !== UPLOAD_ERR_NO_FILE) {
            $filesSelected = true;
            break;
        }
    }
    
    if ($product_id && $filesSelected) {
        $upload_dir = '../uploads/products/';
        
        // Sprawdź czy katalog istnieje, jeśli nie - utwórz go
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $uploadedFiles = false;
        $debug_info = '';
        
        // Debugowanie
        $debug_info .= "Liczba plików: " . count($_FILES['additional_images']['name']) . "<br>";
        
        foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
            $debug_info .= "Plik #" . $key . ": " . $_FILES['additional_images']['name'][$key] . " (Error: " . $_FILES['additional_images']['error'][$key] . ")<br>";
            
            if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK && !empty($tmp_name)) {
                $file_name = $_FILES['additional_images']['name'][$key];
                $file_tmp = $_FILES['additional_images']['tmp_name'][$key];
                $file_type = $_FILES['additional_images']['type'][$key];
                
                $debug_info .= "- Typ pliku: " . $file_type . "<br>";
                
                // Sprawdź typ pliku
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($file_type, $allowed_types)) {
                    $debug_info .= "- Niedozwolony typ pliku<br>";
                    continue;
                }
                
                // Generuj unikalną nazwę pliku
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_file_name = uniqid() . '.' . $file_extension;
                $target_path = $upload_dir . $new_file_name;
                
                $debug_info .= "- Cel: " . $target_path . "<br>";
                
                // Przenieś plik
                if (move_uploaded_file($file_tmp, $target_path)) {
                    // Zapisz informacje o zdjęciu w bazie danych
                    $image_url = 'uploads/products/' . $new_file_name;
                    $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
                    $result = $stmt->execute([$product_id, $image_url]);
                    $debug_info .= "- Zapisano w bazie: " . ($result ? "TAK" : "NIE") . "<br>";
                    $uploadedFiles = true;
                } else {
                    $debug_info .= "- Błąd przy przenoszeniu pliku: " . error_get_last()['message'] . "<br>";
                }
            } else {
                $debug_info .= "- Plik nie został poprawnie przesłany<br>";
            }
        }
        
        // Wyświetl informacje debugowania
        echo '<div class="alert alert-info mt-3">' . $debug_info . '</div>';
        
        if ($uploadedFiles) {
            echo '<div class="alert alert-success mt-3">Zdjęcia zostały dodane pomyślnie.</div>';
            // Nie przekierowuj, żeby zobaczyć informacje debugowania
            // header("Location: admin_products.php?edit=" . $product_id);
            // exit();
        } else {
            echo '<div class="alert alert-warning mt-3">Nie udało się dodać żadnych zdjęć.</div>';
        }
    } else if ($product_id && !$filesSelected) {
        echo '<div class="alert alert-warning mt-3">Nie wybrano żadnych plików do przesłania.</div>';
    } else {
        echo '<div class="alert alert-danger mt-3">Nie określono ID produktu.</div>';
    }
}

// Obsługa usuwania produktu
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Pobierz informacje o zdjęciu przed usunięciem
    $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Usuń produkt z bazy danych
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    
    // Usuń plik ze zdjęciem, jeśli istnieje i nie jest domyślnym zdjęciem
    if ($product && !empty($product['image_url']) && 
        @file_exists($product['image_url']) && 
        $product['image_url'] != 'uploads/products/default.jpg') {
        @unlink($product['image_url']);
    }
    
    header('Location: admin_products.php');
    exit;
}

// Pobierz dane produktu do edycji
$product = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $product_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo '<div class="alert alert-danger">Produkt nie został znaleziony.</div>';
    }
}

// Obsługa formularza dodawania/edycji
if (isset($_POST['submit'])) {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $category = $_POST['category'] ?? '';
    $memory = $_POST['memory'] ?? null;
    $memory_options = $_POST['memory_options'] ?? [];
    $memory_stocks = $_POST['memory_stocks'] ?? [];
    $colors = $_POST['colors'] ?? [];
    $color_names = $_POST['color_names'] ?? [];
    $color_stocks = $_POST['color_stocks'] ?? [];
    
    // Inicjalizacja zmiennej na ścieżkę do zdjęcia
    $image_url = '';
    
    // W przypadku edycji, pobierz aktualną ścieżkę zdjęcia
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $current_product = $stmt->fetch(PDO::FETCH_ASSOC);
        $image_url = $current_product ? ($current_product['image_url'] ?? '') : '';
    }
    
    // Obsługa przesyłania zdjęcia
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $upload_dir = '../uploads/products/';
        
        // Sprawdź czy katalog istnieje, jeśli nie, utwórz go
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Sprawdź typ pliku
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['product_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // Generuj unikalną nazwę pliku
            $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('product_') . '.' . $file_extension;
            $target_file = $upload_dir . $new_filename;
            $db_image_path = 'uploads/products/' . $new_filename; // ścieżka do zapisania w bazie danych
            
            // Ustaw maksymalne wymiary zdjęcia
            $max_width = 600;
            $max_height = 600;
            
            // Sprawdź czy biblioteka GD jest dostępna
            if (function_exists('imagecreatefromjpeg')) {
                // Pobierz informacje o zdjęciu
                list($width, $height, $type) = getimagesize($_FILES['product_image']['tmp_name']);
                
                // Stwórz źródłowy obraz na podstawie typu pliku
                switch ($type) {
                    case IMAGETYPE_JPEG:
                        $source_image = imagecreatefromjpeg($_FILES['product_image']['tmp_name']);
                        break;
                    case IMAGETYPE_PNG:
                        $source_image = imagecreatefrompng($_FILES['product_image']['tmp_name']);
                        break;
                    case IMAGETYPE_GIF:
                        $source_image = imagecreatefromgif($_FILES['product_image']['tmp_name']);
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
                    
                    $image_url = $db_image_path;
                    echo '<div class="alert alert-success">Zdjęcie zostało przeskalowane i zapisane.</div>';
                } else {
                    // Jeśli obraz jest już odpowiedniego rozmiaru lub nie udało się stworzyć źródłowego obrazu
                    move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file);
                    $image_url = $db_image_path;
                }
            } else {
                // Jeśli biblioteka GD nie jest dostępna, po prostu przenieś plik
                move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file);
                $image_url = $db_image_path;
                echo '<div class="alert alert-warning">Biblioteka GD nie jest dostępna. Zdjęcie zostało zapisane bez zmiany rozmiaru.</div>';
            }
        } else {
            echo '<div class="alert alert-danger">Niedozwolony typ pliku. Dozwolone są tylko JPG, PNG i GIF.</div>';
        }
    }
    
    // Zapisz lub zaktualizuj produkt w bazie danych
        if (isset($_POST['id']) && is_numeric($_POST['id'])) {
            // Aktualizacja istniejącego produktu
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category = ?, image_url = ? WHERE id = ?");
        $stmt->execute([$name, $description, $price, $stock, $category, $image_url, $_POST['id']]);
        $product_id = $_POST['id'];
    } else {
        // Dodawanie nowego produktu
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $stock, $category, $image_url]);
        $product_id = $pdo->lastInsertId();
    }

    // Aktualizuj kolory produktu
    if (!empty($colors)) {
            // Usuń stare kolory
            $stmt = $pdo->prepare("DELETE FROM product_colors WHERE product_id = ?");
        $stmt->execute([$product_id]);
        
        // Dodaj nowe kolory
        $stmt = $pdo->prepare("INSERT INTO product_colors (product_id, color, color_name, stock) VALUES (?, ?, ?, ?)");
        foreach ($colors as $index => $color) {
            if (!empty($color) && !empty($color_names[$index])) {
                        $stmt->execute([
                    $product_id,
                    $color,
                    $color_names[$index],
                    $color_stocks[$index] ?? 0
                        ]);
                    }
                }
            }
                
                // Usuń stare opcje pamięci
                $stmt = $pdo->prepare("DELETE FROM product_memories WHERE product_id = ?");
    $stmt->execute([$_POST['id']]);
                
                // Zapisz nowe opcje pamięci
    if (!empty($memory_options)) {
        $stmt = $pdo->prepare("INSERT INTO product_memories (product_id, memory_size, stock) VALUES (?, ?, ?)");
        $total_stock = 0; // Zmienna do przechowywania sumy stanów magazynowych
                
        foreach ($memory_options as $index => $memory_option) {
                    if (!empty($memory_option)) {
                // Konwertuj wartość stock na int
                $memory_stock = isset($memory_stocks[$index]) ? (int)$memory_stocks[$index] : 0;
                $total_stock += $memory_stock; // Dodaj do sumy
                
                try {
                                $stmt->execute([
                        $_POST['id'],
                        $memory_option,
                        $memory_stock
                    ]);
                    
                    echo '<div class="alert alert-success">';
                    echo 'Zapisano opcję pamięci: ' . $memory_option . 'GB, stan: ' . $memory_stock;
                    echo '</div>';
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">';
                    echo 'Błąd podczas zapisywania: ' . $e->getMessage();
                    echo '</div>';
                }
            }
        }
        
        // Aktualizuj ogólny stan magazynowy produktu
        $update_stock_stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $update_stock_stmt->execute([$total_stock, $_POST['id']]);
        
        echo '<div class="alert alert-info">';
        echo 'Zaktualizowano ogólny stan magazynowy produktu: ' . $total_stock . ' szt.';
        echo '</div>';
    }
    
    echo '<div class="alert alert-success">Produkt został zaktualizowany pomyślnie.</div>';
    
    // Przekierowanie po zapisie
    echo '<script>
        setTimeout(function() {
            window.location.href = "admin_products.php?edit=' . $_POST['id'] . '";
        }, 1000);
    </script>';
}

// Pobierz listę produktów
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Błąd podczas pobierania listy produktów: " . $e->getMessage();
}

// Pobierz kategorie produktów
$categories = ['Elektronika', 'Odzież', 'Książki', 'Sport', 'Dom i ogród', 'Inne'];
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie Produktami - Panel Administratora</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .product-image-preview {
            max-width: 150px;
            max-height: 150px;
            margin-top: 10px;
        }
        .product-image-thumbnail {
            max-width: 50px;
            max-height: 50px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Panel Administratora - Zarządzanie Produktami</h1>
        <a href="../admin/admin_panel.php" class="btn btn-secondary mb-3">Powrót do Panelu Głównego</a>
        
        <!-- Formularz dodawania/edycji produktu -->
        <div class="card mb-4">
            <div class="card-header">
                <?php echo isset($_GET['edit']) ? 'Edytuj Produkt' : 'Dodaj Nowy Produkt'; ?>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <?php if(isset($_GET['edit'])): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($product_id); ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="name">Nazwa Produktu</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($product) ? htmlspecialchars($product['name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Opis Produktu</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required><?php echo isset($product) ? htmlspecialchars($product['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Cena</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo isset($product) ? htmlspecialchars($product['price']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock">Stan Magazynowy</label>
                        <input type="number" class="form-control" id="stock" name="stock" value="<?php echo isset($product) ? htmlspecialchars($product['stock']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Opcje pamięci (GB)</label>
                        <div class="row">
                            <?php
                            $memory_options = [8, 16, 32, 64, 128, 256, 512, 1024, 2048];
                            $selected_memories = [];
                            
                            // Pobierz opcje pamięci dla edytowanego produktu
                            if (isset($product['id'])) {
                                // Sprawdź czy tabela istnieje
                                $table_exists = $pdo->query("SHOW TABLES LIKE 'product_memories'")->rowCount() > 0;
                                
                                if ($table_exists) {
                                    $stmt = $pdo->prepare("SELECT memory_size, stock FROM product_memories WHERE product_id = ? ORDER BY memory_size ASC");
                                    $stmt->execute([$product['id']]);
                                    $selected_memories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                }
                            }
                            
                            // Generuj pola dla 5 opcji pamięci
                            for ($i = 0; $i < 5; $i++): 
                                $memory_value = isset($selected_memories[$i]) ? $selected_memories[$i]['memory_size'] : '';
                                $memory_stock = isset($selected_memories[$i]) ? $selected_memories[$i]['stock'] : 0;
                            ?>
                                <div class="col-md-4 mb-3">
                                    <div class="input-group">
                                        <select class="form-control" name="memory_options[]">
                                            <option value="">Wybierz pamięć</option>
                                            <?php foreach ($memory_options as $option): ?>
                                                <option value="<?php echo $option; ?>" <?php echo ($memory_value == $option) ? 'selected' : ''; ?>>
                                                    <?php 
                                                    $display = ($option >= 1024) ? (($option/1024) . ' TB') : ($option . ' GB');
                                                    echo $display;
                                                    ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" class="form-control" name="memory_stocks[]" 
                                               value="<?php echo $memory_stock; ?>" 
                                               placeholder="Stan magazynowy">
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                        <small class="form-text text-muted">Wybierz do 5 opcji pamięci. Puste opcje będą ignorowane.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="memory">Domyślna pamięć (GB)</label>
                        <select class="form-control" id="memory" name="memory">
                            <option value="">Wybierz pamięć</option>
                            <option value="8" <?php echo (isset($product) && $product['memory'] == 8) ? 'selected' : ''; ?>>8 GB</option>
                            <option value="16" <?php echo (isset($product) && $product['memory'] == 16) ? 'selected' : ''; ?>>16 GB</option>
                            <option value="32" <?php echo (isset($product) && $product['memory'] == 32) ? 'selected' : ''; ?>>32 GB</option>
                            <option value="64" <?php echo (isset($product) && $product['memory'] == 64) ? 'selected' : ''; ?>>64 GB</option>
                            <option value="128" <?php echo (isset($product) && $product['memory'] == 128) ? 'selected' : ''; ?>>128 GB</option>
                            <option value="256" <?php echo (isset($product) && $product['memory'] == 256) ? 'selected' : ''; ?>>256 GB</option>
                            <option value="512" <?php echo (isset($product) && $product['memory'] == 512) ? 'selected' : ''; ?>>512 GB</option>
                            <option value="1024" <?php echo (isset($product) && $product['memory'] == 1024) ? 'selected' : ''; ?>>1 TB</option>
                            <option value="2048" <?php echo (isset($product) && $product['memory'] == 2048) ? 'selected' : ''; ?>>2 TB</option>
                        </select>
                        <small class="form-text text-muted">Wybierz domyślną ilość pamięci (będzie wykorzystana przy dodawaniu do koszyka)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Kategoria</label>
                        <select class="form-control" id="category" name="category" required>
                            <option value="">Wybierz kategorię</option>
                            <?php
                            $categories = ['Smartphone', 'Laptop', 'Tablet', 'Audio', 'Gaming'];
                            $selected_category = isset($product) && isset($product['category']) ? $product['category'] : '';
                            
                            foreach ($categories as $cat) {
                                $selected = ($selected_category == $cat) ? 'selected' : '';
                                echo "<option value=\"$cat\" $selected>$cat</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Kolory:</label>
                        <div id="color-options">
                            <?php
                                $stmt = $pdo->prepare("SELECT * FROM product_colors WHERE product_id = ?");
                                $stmt->execute([$product['id']]);
                                $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                            foreach ($colors as $color): ?>
                                <div class="color-option">
                                    <input type="text" name="color_names[]" class="form-control" placeholder="Nazwa koloru" value="<?php echo htmlspecialchars($color['color_name']); ?>" required>
                                    <input type="color" name="colors[]" class="form-control" value="<?php echo htmlspecialchars($color['color']); ?>" required>
                                    <input type="number" name="color_stocks[]" class="form-control" placeholder="Stan magazynowy" value="<?php echo htmlspecialchars($color['stock'] ?? 0); ?>" min="0" required>
                                    <button type="button" class="btn btn-danger remove-color">Usuń</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-secondary mt-2" id="add-color">Dodaj kolor</button>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_image">Zdjęcie główne produktu:</label>
                        <input type="file" class="form-control-file" id="product_image" name="product_image">
                        <?php if (!empty($product['image_url'])): ?>
                            <div class="mt-2">
                                <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" alt="Aktualne zdjęcie" style="max-width: 200px;">
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sekcja zarządzania zdjęciami produktu -->
                    <div class="form-group">
                        <label>Dodatkowe zdjęcia produktu:</label>
                        <?php
                        // Pobierz dodatkowe zdjęcia produktu
                        if (isset($product['id'])) {
                            $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY created_at DESC");
                            $stmt->execute([$product['id']]);
                            $additional_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (!empty($additional_images)) {
                                echo '<div class="row mt-2">';
                                foreach ($additional_images as $image) {
                                    echo '<div class="col-md-3 mb-3">';
                                    echo '<div class="card h-100">';
                                    echo '<img src="../' . htmlspecialchars($image['image_url']) . '" class="card-img-top" alt="Dodatkowe zdjęcie" style="height: 150px; object-fit: contain;">';
                                    echo '<div class="card-body p-2 text-center">';
                                    echo '<form method="post" class="d-inline" onsubmit="return confirm(\'Czy na pewno chcesz usunąć to zdjęcie?\');">';
                                    echo '<input type="hidden" name="delete_image" value="' . $image['id'] . '">';
                                    echo '<button type="submit" class="btn btn-danger btn-sm">Usuń</button>';
                                    echo '</form>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                                echo '</div>';
                            } else {
                                echo '<p class="text-muted">Brak dodatkowych zdjęć.</p>';
                            }
                        }
                        ?>
                        
                        <div class="mt-2">
                            <input type="file" class="form-control-file" name="additional_images[]" multiple accept="image/jpeg,image/png,image/gif">
                            <small class="form-text text-muted">Możesz wybrać wiele zdjęć naraz. Dozwolone formaty: JPG, PNG, GIF</small>
                        </div>
                    </div>
                    
                    <button type="submit" name="submit" class="btn btn-primary"><?php echo isset($_GET['edit']) ? 'Zapisz Zmiany' : 'Dodaj Produkt'; ?></button>
                </form>
                
                <!-- Formularz tylko dla dodatkowych zdjęć -->
                <?php if (isset($_GET['edit'])): ?>
                <div class="card mt-3">
                    <div class="card-header">Dodaj dodatkowe zdjęcia</div>
                    <div class="card-body">
                        <form action="admin_products.php?edit=<?php echo htmlspecialchars($_GET['edit']); ?>" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($_GET['edit']); ?>">
                            <div class="form-group">
                                <label>Wybierz dodatkowe zdjęcia:</label>
                                <input type="file" class="form-control-file" name="additional_images[]" multiple accept="image/jpeg,image/png,image/gif" required>
                                <small class="form-text text-muted">Możesz wybrać wiele zdjęć naraz. Dozwolone formaty: JPG, PNG, GIF</small>
                            </div>
                            <button type="submit" name="upload_images" class="btn btn-success">Dodaj zdjęcia</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Lista produktów -->
        <div class="card">
            <div class="card-header">
                Lista Produktów
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Zdjęcie</th>
                                <th>Nazwa</th>
                                <th>Cena</th>
                                <th>Stan Magazynowy</th>
                                <th>Pamięć (GB)</th>
                                <th>Kategoria</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td>
                                    <?php 
                                    if (!empty($row['image_url'])) {
                                        echo '<img src="../' . htmlspecialchars($row['image_url']) . '" alt="' . htmlspecialchars($row['name']) . '" class="product-image-thumbnail">';
                                    } else {
                                        echo '<img src="../uploads/products/default.jpg" alt="Default" class="product-image-thumbnail">';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['price']); ?> zł</td>
                                <td><?php echo htmlspecialchars($row['stock']); ?></td>
                                <td>
                                    <?php
                                    // Pobierz opcje pamięci dla produktu
                                    $stmt_memories = $pdo->prepare("SELECT memory_size, stock FROM product_memories WHERE product_id = ? ORDER BY memory_size ASC");
                                    $stmt_memories->execute([$row['id']]);
                                    $memories = $stmt_memories->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if (!empty($memories)) {
                                        echo '<select class="form-control form-control-sm">';
                                        foreach ($memories as $memory) {
                                            $memory_display = ($memory['memory_size'] >= 1024) ? 
                                                (($memory['memory_size']/1024) . ' TB') : 
                                                ($memory['memory_size'] . ' GB');
                                            echo '<option>' . $memory_display . ' (Dostępny: ' . $memory['stock'] . ' szt.)</option>';
                                        }
                                        echo '</select>';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['category'] ?? 'Brak kategorii'); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edytuj</a>
                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Czy na pewno chcesz usunąć ten produkt?')">Usuń</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if ($stmt->rowCount() == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center">Brak produktów</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Dodawanie nowego koloru
            $('#add-color').click(function() {
                const colorOption = document.createElement('div');
                colorOption.className = 'color-option';
                colorOption.innerHTML = `
                    <input type="text" name="color_names[]" class="form-control" placeholder="Nazwa koloru" required>
                    <input type="color" name="colors[]" class="form-control" required>
                    <input type="number" name="color_stocks[]" class="form-control" placeholder="Stan magazynowy" min="0" required>
                            <button type="button" class="btn btn-danger remove-color">Usuń</button>
                `;
                document.getElementById('color-options').appendChild(colorOption);
            });

            // Obsługa przycisku "Usuń" dla kolorów
            $(document).on('click', '.remove-color', function() {
                $(this).closest('.color-option').remove();
            });

            // Podgląd zdjęcia produktu
            $('#product_image').change(function() {
                readURL(this, 'product-image-preview');
            });

            // Podgląd zdjęć dla kolorów
            $(document).on('change', 'input[name="color_images[]"]', function() {
                // Znajdź lub utwórz element obrazu dla podglądu
                let previewContainer = $(this).closest('.mt-2');
                let previewId = 'color-image-preview-' + Math.random().toString(36).substring(7);
                
                if (previewContainer.find('img').length === 0) {
                    previewContainer.append('<div class="mt-2"><img id="' + previewId + '" class="product-image-preview"></div>');
                } else {
                    previewId = previewContainer.find('img').attr('id');
                }
                
                readURL(this, previewId);
            });

            function readURL(input, previewId) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#' + previewId).attr('src', e.target.result);
                        $('#' + previewId).show();
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }

            // Obsługa formularza edycji produktu
            document.getElementById('edit-product-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const productId = formData.get('product_id');
                
                // Pobierz wszystkie wartości z formularza
                const name = formData.get('name');
                const description = formData.get('description');
                const price = formData.get('price');
                const stock = formData.get('stock');
                const category = formData.get('category');
                const colors = formData.getAll('colors[]');
                const colorNames = formData.getAll('color_names[]');
                const colorStocks = formData.getAll('color_stocks[]');
                const memoryOptions = formData.getAll('memory_options[]');
                const memoryStocks = formData.getAll('memory_stocks[]');
                
                // Sprawdź czy suma stanów magazynowych kolorów nie przekracza ogólnego stanu
                const totalColorStock = colorStocks.reduce((sum, stock) => sum + parseInt(stock), 0);
                if (totalColorStock > parseInt(stock)) {
                    alert(`Suma stanów magazynowych kolorów (${totalColorStock}) przekracza ogólny stan magazynowy produktu (${stock})`);
                    return;
                }
                
                // Sprawdź czy suma stanów magazynowych pamięci nie przekracza ogólnego stanu
                const totalMemoryStock = memoryStocks.reduce((sum, stock) => sum + parseInt(stock), 0);
                if (totalMemoryStock > parseInt(stock)) {
                    alert(`Suma stanów magazynowych pamięci (${totalMemoryStock}) przekracza ogólny stan magazynowy produktu (${stock})`);
                    return;
                }
                
                // Wyślij dane do serwera
                fetch('api/update_product.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Produkt został zaktualizowany pomyślnie!');
                        window.location.reload();
                    } else {
                        alert('Wystąpił błąd podczas aktualizacji produktu: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Wystąpił błąd podczas aktualizacji produktu');
                });
            });
        });
    </script>
</body>
</html> 