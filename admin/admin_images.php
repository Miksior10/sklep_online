<?php
session_start();
require_once '../config.php';

// Sprawdzenie czy użytkownik jest zalogowany jako admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Obsługa usuwania zdjęcia
if (isset($_POST['delete_image'])) {
    $image_id = (int)$_POST['delete_image'];
    
    // Pobierz informacje o zdjęciu przed usunięciem
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE id = ?");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($image) {
        // Usuń plik fizycznie
        $file_path = '../' . $image['image_url'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Usuń rekord z bazy danych
        $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
        $stmt->execute([$image_id]);
        
        $success_message = "Zdjęcie zostało usunięte pomyślnie.";
    }
}

// Obsługa dodawania nowego zdjęcia
if (isset($_POST['product_id']) && isset($_FILES['new_image']) && $_FILES['new_image']['error'] === UPLOAD_ERR_OK) {
    $product_id = (int)$_POST['product_id'];
    
    // Sprawdź, czy produkt istnieje
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        // Obsługa przesyłania zdjęcia
        $upload_dir = '../uploads/products/';
        
        // Sprawdź czy katalog istnieje, jeśli nie, utwórz go
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Sprawdź typ pliku
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['new_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // Generuj unikalną nazwę pliku
            $file_extension = pathinfo($_FILES['new_image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('product_') . '.' . $file_extension;
            $target_file = $upload_dir . $new_filename;
            $db_image_path = 'uploads/products/' . $new_filename;
            
            // Przenieś plik i zapisz informacje w bazie danych
            if (move_uploaded_file($_FILES['new_image']['tmp_name'], $target_file)) {
                // Sprawdź czy tabela istnieje
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
                
                $success_message = "Zdjęcie zostało dodane pomyślnie.";
            } else {
                $error_message = "Wystąpił błąd podczas przesyłania pliku.";
            }
        } else {
            $error_message = "Niedozwolony typ pliku. Używaj tylko JPG, PNG lub GIF.";
        }
    } else {
        $error_message = "Produkt nie istnieje.";
    }
}

// Pobierz listę produktów
$stmt = $pdo->prepare("SELECT id, name FROM products ORDER BY name");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie Zdjęciami - Panel Administratora</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .admin-navbar {
            background-color: #343a40;
            padding: 15px;
            margin-bottom: 30px;
        }
        .admin-navbar .navbar-brand {
            color: white;
            font-size: 1.5rem;
        }
        .admin-navbar .nav-link {
            color: rgba(255,255,255,.75);
        }
        .admin-navbar .nav-link:hover {
            color: white;
        }
        .admin-container {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        .admin-title {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .image-card {
            margin-bottom: 20px;
            border-radius: 5px;
            overflow: hidden;
        }
        .image-preview {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px 5px 0 0;
        }
        .product-name {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .product-image-container {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin-top: 20px;
        }
        .product-images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <nav class="admin-navbar navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="admin_panel.php">Panel Administratora</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_panel.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_products.php">Produkty</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_images.php">Zdjęcia</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_orders.php">Zamówienia</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Powrót do sklepu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Wyloguj</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="admin-container">
            <h2 class="admin-title">Dodaj nowe zdjęcie produktu</h2>
            
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="product_id">Wybierz produkt:</label>
                    <select class="form-control" id="product_id" name="product_id" required>
                        <option value="">-- Wybierz produkt --</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="new_image">Wybierz zdjęcie:</label>
                    <input type="file" class="form-control-file" id="new_image" name="new_image" required accept="image/*">
                    <small class="form-text text-muted">Dozwolone formaty: JPG, PNG, GIF</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Dodaj zdjęcie</button>
            </form>
        </div>

        <div class="admin-container">
            <h2 class="admin-title">Zarządzaj zdjęciami produktów</h2>
            
            <form id="filter-form" class="mb-4">
                <div class="form-group">
                    <label for="filter_product">Filtruj według produktu:</label>
                    <select class="form-control" id="filter_product" name="filter_product" onchange="this.form.submit()">
                        <option value="">Wszystkie produkty</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>" <?php echo (isset($_GET['filter_product']) && $_GET['filter_product'] == $product['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($product['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            
            <?php
            // Pobierz zdjęcia dla wybranego produktu lub wszystkie
            $where_clause = '';
            $params = [];
            
            if (isset($_GET['filter_product']) && !empty($_GET['filter_product'])) {
                $where_clause = 'WHERE pi.product_id = ?';
                $params[] = (int)$_GET['filter_product'];
            }
            
            $query = "
                SELECT pi.*, p.name as product_name 
                FROM product_images pi
                JOIN products p ON pi.product_id = p.id
                $where_clause
                ORDER BY p.name, pi.created_at DESC
            ";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($images) > 0):
            ?>
                <div class="product-images-grid">
                    <?php foreach ($images as $image): ?>
                        <div class="card image-card">
                            <img src="../<?php echo htmlspecialchars($image['image_url']); ?>" class="image-preview" alt="<?php echo htmlspecialchars($image['product_name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title product-name"><?php echo htmlspecialchars($image['product_name']); ?></h5>
                                <p class="card-text">Dodano: <?php echo date('d.m.Y H:i', strtotime($image['created_at'])); ?></p>
                                <form action="" method="post" onsubmit="return confirm('Czy na pewno chcesz usunąć to zdjęcie?');">
                                    <input type="hidden" name="delete_image" value="<?php echo $image['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Usuń zdjęcie
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">Brak zdjęć do wyświetlenia.</div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 