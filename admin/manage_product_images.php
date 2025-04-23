<?php
session_start();
require_once '../config.php';

// Sprawdzenie czy użytkownik jest zalogowany jako admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Pobierz informacje o produkcie
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: admin_panel.php');
    exit();
}

// Pobierz zdjęcia produktu
$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY created_at DESC");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obsługa dodawania nowych zdjęć
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $upload_dir = '../uploads/products/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['images']['name'][$key];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $new_file_name = uniqid() . '.' . $file_ext;
            $target_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($tmp_name, $target_path)) {
                $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
                $stmt->execute([$product_id, 'uploads/products/' . $new_file_name]);
            }
        }
    }
    header("Location: manage_product_images.php?id=" . $product_id);
    exit();
}

// Obsługa usuwania zdjęć
if (isset($_POST['delete_image'])) {
    $image_id = (int)$_POST['delete_image'];
    $stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE id = ? AND product_id = ?");
    $stmt->execute([$image_id, $product_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($image) {
        $file_path = '../' . $image['image_url'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
        $stmt->execute([$image_id]);
        header("Location: manage_product_images.php?id=" . $product_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie zdjęciami produktu - Panel Administratora</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .image-card {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .image-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .image-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
        }

        .delete-btn {
            background: rgba(255,0,0,0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.3s;
        }

        .delete-btn:hover {
            background: rgba(255,0,0,1);
        }

        .upload-area {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
            transition: border-color 0.3s;
        }

        .upload-area:hover {
            border-color: #007bff;
        }

        .upload-area i {
            font-size: 3rem;
            color: #007bff;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Zdjęcia produktu: <?php echo htmlspecialchars($product['name']); ?></h1>
            <a href="admin_panel.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Powrót
            </a>
        </div>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="upload-area" onclick="document.getElementById('images').click()">
                <i class="fas fa-cloud-upload-alt"></i>
                <h4>Przeciągnij zdjęcia tutaj lub kliknij, aby wybrać</h4>
                <p class="text-muted">Możesz wybrać wiele zdjęć naraz</p>
                <input type="file" id="images" name="images[]" multiple accept="image/*" style="display: none">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload"></i> Prześlij zdjęcia
            </button>
        </form>

        <div class="image-grid">
            <?php foreach ($images as $image): ?>
                <div class="image-card">
                    <img src="../<?php echo htmlspecialchars($image['image_url']); ?>" 
                         alt="Zdjęcie produktu">
                    <div class="image-actions">
                        <form action="" method="post" style="display: inline;">
                            <button type="submit" name="delete_image" value="<?php echo $image['id']; ?>" 
                                    class="delete-btn" onclick="return confirm('Czy na pewno chcesz usunąć to zdjęcie?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Obsługa przeciągania i upuszczania plików
        const uploadArea = document.querySelector('.upload-area');
        const fileInput = document.getElementById('images');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            uploadArea.classList.add('border-primary');
        }

        function unhighlight(e) {
            uploadArea.classList.remove('border-primary');
        }

        uploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
        }
    </script>
</body>
</html> 