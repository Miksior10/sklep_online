<?php
session_start();
require_once '../config.php';

// Sprawdzenie czy użytkownik jest zalogowany jako admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Dodawanie produktu do strony głównej
if (isset($_POST['add_featured'])) {
    try {
        $product_id = $_POST['product_id'];
        
        // Sprawdź czy produkt już jest na stronie głównej
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM featured_products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            $_SESSION['error'] = "Ten produkt jest już na stronie głównej.";
        } else {
            // Znajdź najwyższą pozycję
            $max_position = $pdo->query("SELECT MAX(position) FROM featured_products")->fetchColumn() ?: 0;
            
            // Dodaj produkt na koniec listy
            $stmt = $pdo->prepare("INSERT INTO featured_products (product_id, position) VALUES (?, ?)");
            $stmt->execute([$product_id, $max_position + 1]);
            
            $_SESSION['success'] = "Produkt został dodany do strony głównej.";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Błąd: " . $e->getMessage();
    }
    header('Location: admin_featured_products.php');
    exit();
}

// Usuwanie produktu ze strony głównej
if (isset($_POST['remove_featured'])) {
    try {
        $featured_id = $_POST['featured_id'];
        
        $stmt = $pdo->prepare("DELETE FROM featured_products WHERE id = ?");
        $stmt->execute([$featured_id]);
        
        // Przenumeruj pozostałe pozycje
        $stmt = $pdo->query("SELECT id FROM featured_products ORDER BY position");
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($ids as $i => $id) {
            $stmt = $pdo->prepare("UPDATE featured_products SET position = ? WHERE id = ?");
            $stmt->execute([$i + 1, $id]);
        }
        
        $_SESSION['success'] = "Produkt został usunięty ze strony głównej.";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Błąd podczas usuwania: " . $e->getMessage();
    }
    header('Location: admin_featured_products.php');
    exit();
}

// Zmiana pozycji produktu (w górę lub w dół)
if (isset($_POST['move_up']) || isset($_POST['move_down'])) {
    try {
        $featured_id = isset($_POST['move_up']) ? $_POST['move_up'] : $_POST['move_down'];
        $direction = isset($_POST['move_up']) ? -1 : 1;
        
        // Pobierz aktualną pozycję
        $stmt = $pdo->prepare("SELECT position FROM featured_products WHERE id = ?");
        $stmt->execute([$featured_id]);
        $current_position = $stmt->fetchColumn();
        
        // Nowa pozycja po przesunięciu
        $new_position = $current_position + $direction;
        
        // Sprawdź czy nowa pozycja jest prawidłowa
        $count = $pdo->query("SELECT COUNT(*) FROM featured_products")->fetchColumn();
        
        if ($new_position > 0 && $new_position <= $count) {
            // Znajdź produkt na pozycji, z którą zamieniamy
            $stmt = $pdo->prepare("SELECT id FROM featured_products WHERE position = ?");
            $stmt->execute([$new_position]);
            $swap_id = $stmt->fetchColumn();
            
            // Aktualizuj pozycję wybranego produktu
            $stmt = $pdo->prepare("UPDATE featured_products SET position = ? WHERE id = ?");
            $stmt->execute([$new_position, $featured_id]);
            
            // Aktualizuj pozycję produktu, z którym zamieniamy
            $stmt = $pdo->prepare("UPDATE featured_products SET position = ? WHERE id = ?");
            $stmt->execute([$current_position, $swap_id]);
            
            $_SESSION['success'] = "Pozycja produktu została zmieniona.";
        } else {
            $_SESSION['error'] = "Nie można zmienić pozycji (produkt jest już na " . 
                                ($direction < 0 ? "początku" : "końcu") . " listy).";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Błąd podczas zmiany pozycji: " . $e->getMessage();
    }
    header('Location: admin_featured_products.php');
    exit();
}

// Pobranie produktów na stronie głównej
$featured_products = [];
try {
    $stmt = $pdo->query("
        SELECT fp.*, p.name, p.price, p.image_url 
        FROM featured_products fp
        JOIN products p ON fp.product_id = p.id
        ORDER BY fp.position
    ");
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Błąd podczas pobierania produktów: " . $e->getMessage();
}

// Pobranie wszystkich dostępnych produktów (do wyboru)
$all_products = [];
try {
    $stmt = $pdo->query("
        SELECT p.* 
        FROM products p
        LEFT JOIN featured_products fp ON p.id = fp.product_id
        WHERE fp.id IS NULL
        ORDER BY p.name
    ");
    $all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Błąd podczas pobierania produktów: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie Produktami na Stronie Głównej</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 2rem auto;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        .table img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .btn-group-sm > .btn {
            margin-right: 5px;
        }
        .alert {
            margin-bottom: 20px;
        }
        .product-select {
            max-width: 400px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Zarządzanie Produktami na Stronie Głównej</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                Produkty na Stronie Głównej
            </div>
            <div class="card-body">
                <?php if (count($featured_products) > 0): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Pozycja</th>
                                <th>Zdjęcie</th>
                                <th>Nazwa</th>
                                <th>Cena</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($featured_products as $product): ?>
                                <tr>
                                    <td><?php echo $product['position']; ?></td>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'images/default-product.jpg'); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo number_format($product['price'], 2); ?> zł</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="move_up" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="btn btn-secondary" <?php echo $product['position'] == 1 ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-arrow-up"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="move_down" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="btn btn-secondary" <?php echo $product['position'] == count($featured_products) ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-arrow-down"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Czy na pewno chcesz usunąć ten produkt ze strony głównej?');">
                                                <input type="hidden" name="featured_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" name="remove_featured" class="btn btn-danger">
                                                    <i class="fas fa-times"></i> Usuń
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Brak produktów na stronie głównej.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                Dodaj Produkt do Strony Głównej
            </div>
            <div class="card-body">
                <?php if (count($all_products) > 0): ?>
                    <form method="POST" class="form-inline">
                        <div class="form-group mr-2 product-select">
                            <select name="product_id" class="form-control form-control-lg" required>
                                <option value="">-- Wybierz produkt --</option>
                                <?php foreach ($all_products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?> 
                                        (<?php echo number_format($product['price'], 2); ?> zł)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_featured" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Dodaj do Strony Głównej
                        </button>
                    </form>
                <?php else: ?>
                    <p>Brak produktów do dodania (wszystkie produkty są już na stronie głównej).</p>
                <?php endif; ?>
            </div>
        </div>
        
        <a href="../admin/admin_panel.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Powrót do Panelu Admina
        </a>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 