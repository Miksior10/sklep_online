<?php
session_start();
require_once 'config.php';

// Sprawdź czy podano ID produktu
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$product_id = $_GET['id'];

// Pobierz szczegóły produktu
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('Location: index.php');
        exit;
    }

    // Pobierz kolory produktu
    $stmt = $pdo->prepare("SELECT * FROM product_colors WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pobierz opcje pamięci dla produktu
    $stmt = $pdo->prepare("SELECT memory_size as memory FROM product_memories WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $memories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pobierz zdjęcia dla kolorów
    $color_images = [];
    $stmt = $pdo->prepare("SELECT * FROM product_color_images WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $color_images_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($color_images_result as $image) {
        $color_images[$image['color']] = $image['image_url'];
    }
    
    // Pobierz główne zdjęcie produktu
    $default_image = $product['image_url'] ?: 'images/default-product.jpg';
    
    // Przywracam pobieranie dodatkowych zdjęć
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Jeśli nie ma dodatkowych zdjęć, użyj głównego zdjęcia produktu
    if (empty($product_images)) {
        $product_images = [['image_url' => $product['image_url'] ?: 'images/default-product.jpg']];
    }
} catch(PDOException $e) {
    $error_message = "Błąd podczas pobierania produktu: " . $e->getMessage();
}

// Pobierz liczbę produktów w koszyku jeśli użytkownik jest zalogowany
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $cart_count = $pdo->query("SELECT SUM(quantity) FROM cart_items WHERE user_id = " . $_SESSION['user_id'])->fetchColumn() ?: 0;
}

// Pobierz opcje pamięci produktu
$memory_options = [];
$product_memories = [];
try {
    // Sprawdź czy tabela istnieje
    $table_exists = $pdo->query("SHOW TABLES LIKE 'product_memories'")->rowCount() > 0;
    
    if ($table_exists) {
        $stmt = $pdo->prepare("SELECT memory_size, stock FROM product_memories WHERE product_id = ? ORDER BY memory_size ASC");
        $stmt->execute([$product_id]);
        $memory_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($memory_data as $memory) {
            $memory_size = $memory['memory_size'];
            $product_memories[$memory_size] = [
                'stock' => $memory['stock']
            ];
            $memory_options[] = $memory_size;
        }
    }
} catch (PDOException $e) {
    // Ignoruj błędy - po prostu nie pokaże opcji pamięci
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> -Sklep z Elektronika</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }

        nav {
            background-color: #007bff;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav .logo {
            color: white;
            font-size: 1.4rem;
            font-weight: bold;
            text-decoration: none;
        }

        nav ul {
            list-style: none;
            display: flex;
            align-items: center;
            margin: 0;
            padding: 0;
        }

        nav ul li {
            margin-left: 20px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        nav ul li a:hover {
            background-color: rgba(255,255,255,0.1);
        }

        .product-details {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        .product-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .product-gallery {
            position: relative;
            width: 100%;
        }

        .main-image {
            width: 100%;
            max-height: 500px;
            object-fit: contain;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .thumbnail-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        .thumbnail {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .thumbnail:hover {
            transform: scale(1.05);
            border-color: #007bff;
        }

        .thumbnail.active {
            border-color: #007bff;
        }

        .product-info h1 {
            margin-bottom: 1rem;
            color: #333;
        }

        .product-price {
            font-size: 1.5rem;
            color: #007bff;
            font-weight: bold;
            margin: 1rem 0;
        }

        .product-stock {
            margin-bottom: 1rem;
        }

        .product-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .product-color {
            margin: 1rem 0;
        }

        .color-circle {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
            border: 2px solid #ddd;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 1rem;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .add-to-cart-btn:hover {
            background-color: #0056b3;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            z-index: 1000;
            transform: translateX(120%);
            transition: transform 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background-color: #28a745;
        }

        .notification.error {
            background-color: #dc3545;
        }

        @media (max-width: 768px) {
            .product-container {
                grid-template-columns: 1fr;
            }
        }

        .product-colors {
            margin: 1.5rem 0;
        }

        .colors-list {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .color-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .color-radio {
            display: none;
        }

        .color-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border: 2px solid transparent;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .color-radio:checked + .color-label {
            border-color: #007bff;
            background-color: rgba(0, 123, 255, 0.1);
        }

        .color-circle {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            border: 2px solid #ddd;
        }
        
        .add-image-thumbnail {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border: 2px dashed #007bff;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 100px;
            width: 100%;
        }
        
        .add-image-thumbnail:hover {
            background-color: #e9ecef;
        }
        
        .add-image-icon {
            font-size: 24px;
            color: #007bff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .add-image-icon i {
            font-size: 30px;
        }

        .memory-radio {
            display: none;
        }

        .memories-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .memory-item {
            display: inline-block;
        }

        .memory-label {
            padding: 6px 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .memory-radio:checked + .memory-label {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        .select-memory {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .select-memory:hover {
            background-color: #45a049;
        }

        .select-memory.selected {
            background-color: #2E7D32;
            transform: scale(1.05);
            transition: all 0.3s;
        }

        .memory-option {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
            padding: 5px;
            border-radius: 4px;
            background-color: #f8f9fa;
        }

        .memory-option:hover {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <?php
    // Obsługa komunikatów o sukcesie/błędzie
    if (isset($_GET['success']) && $_GET['success'] === 'image_added'): ?>
    <div id="success-notification" class="notification success show">Zdjęcie zostało dodane pomyślnie!</div>
    <script>
        setTimeout(function() {
            document.getElementById('success-notification').classList.remove('show');
            setTimeout(function() {
                document.getElementById('success-notification').remove();
            }, 300);
        }, 3000);
    </script>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): 
        $error_message = "Wystąpił błąd podczas dodawania zdjęcia";
        if ($_GET['error'] === 'missing_data') {
            $error_message = "Brak wymaganego pliku lub danych";
        } else if ($_GET['error'] === 'invalid_file_type') {
            $error_message = "Niedozwolony typ pliku. Używaj tylko JPG, PNG lub GIF";
        }
    ?>
    <div id="error-notification" class="notification error show"><?php echo htmlspecialchars($error_message); ?></div>
    <script>
        setTimeout(function() {
            document.getElementById('error-notification').classList.remove('show');
            setTimeout(function() {
                document.getElementById('error-notification').remove();
            }, 300);
        }, 3000);
    </script>
    <?php endif; ?>
    
    <header>
        <nav>
            <div class="container">
                <div class="logo">Sklep Online</div>
                <ul>
                    <li><a href="index.php">Strona Główna</a></li>
                    <li><a href="products.php">Produkty</a></li>
                    <li><a href="cart.php">Koszyk</a></li>
                    <li><a href="orders.php">Historia zamówień</a></li>
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <li><a href="login.php">Zaloguj się</a></li>
                    <?php else: ?>
                        <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                            <li><a href="admin_panel.php">Panel Admina</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php">Wyloguj się</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>

    <div class="product-details">
        <div class="product-container">
            <div class="product-gallery">
                <img src="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'images/default-product.jpg'; ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     class="main-image" 
                     id="mainImage">
                
                <?php if (count($product_images) > 1): ?>
                <div class="additional-images mt-3">
                    <h5>Dodatkowe zdjęcia:</h5>
                    <div class="row">
                        <?php foreach ($product_images as $index => $image): ?>
                            <?php if ($image['image_url'] != $product['image_url']): ?>
                            <div class="col-4 col-md-3 mb-3">
                                <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?> - zdjęcie <?php echo $index + 1; ?>" 
                                     class="img-fluid" 
                                     style="cursor: pointer; height: 150px; object-fit: contain; border: 1px solid #ddd; padding: 5px; border-radius: 5px;"
                                     onclick="changeMainImage('<?php echo htmlspecialchars($image['image_url']); ?>')">
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="product-price"><?php echo number_format($product['price'], 2); ?> zł</p>
                <div class="product-stock">
                    <?php if ($product['stock'] > 0): ?>
                        <p class="text-success"><i class="fas fa-check-circle"></i> Dostępny (<?php echo $product['stock']; ?> szt.)</p>
                    <?php else: ?>
                        <p class="text-danger"><i class="fas fa-times-circle"></i> Niedostępny</p>
                    <?php endif; ?>
                </div>
                <p class="product-description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                
                <?php if(!empty($colors)): ?>
                <div class="product-colors">
                    <strong>Dostępne kolory:</strong>
                    <div class="colors-list">
                        <?php foreach($colors as $index => $color): ?>
                            <div class="color-item">
                                <input type="radio" name="selected_color" id="color_<?php echo $color['id']; ?>" 
                                       value="<?php echo htmlspecialchars($color['color']); ?>" 
                                       class="color-radio" <?php echo $index === 0 ? 'checked' : ''; ?>
                                       data-image-url="<?php echo htmlspecialchars($color_images[$color['color']] ?? $default_image); ?>"
                                       <?php echo ($color['stock'] ?? 0) <= 0 ? 'disabled' : ''; ?>>
                                <label for="color_<?php echo $color['id']; ?>" class="color-label">
                                    <div class="color-circle" style="background-color: <?php echo htmlspecialchars($color['color']); ?>"></div>
                                    <span><?php echo htmlspecialchars($color['color_name']); ?></span>
                                    <span class="stock-status <?php echo ($color['stock'] ?? 0) > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                        <?php echo ($color['stock'] ?? 0) > 0 ? 'Dostępny: ' . ($color['stock'] ?? 0) . ' szt.' : 'Niedostępny'; ?>
                                    </span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                // Pobierz opcje pamięci produktu
                $memory_options = [];
                $product_memories = [];
                try {
                    // Sprawdź czy tabela istnieje
                    $table_exists = $pdo->query("SHOW TABLES LIKE 'product_memories'")->rowCount() > 0;
                    
                    if ($table_exists) {
                        $stmt = $pdo->prepare("SELECT memory_size, stock FROM product_memories WHERE product_id = ? ORDER BY memory_size ASC");
                        $stmt->execute([$product_id]);
                        $memory_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($memory_data as $memory) {
                            $memory_size = $memory['memory_size'];
                            $product_memories[$memory_size] = [
                                'stock' => $memory['stock']
                            ];
                            $memory_options[] = $memory_size;
                        }
                    }
                } catch (PDOException $e) {
                    // Ignoruj błędy - po prostu nie pokaże opcji pamięci
                }
                
                if (!empty($memory_options)): 
                ?>
                <div class="product-memories mt-3">
                    <strong>Dostępne opcje pamięci:</strong>
                    <div class="memory-options">
                        <?php foreach ($memory_options as $memory): ?>
                            <div class="memory-option">
                                <span class="memory-size"><?php echo htmlspecialchars($memory); ?>GB</span>
                                <span class="stock-status <?php echo $product_memories[$memory]['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                    <?php echo $product_memories[$memory]['stock'] > 0 ? 'Dostępny: ' . $product_memories[$memory]['stock'] . ' szt.' : 'Niedostępny'; ?>
                                </span>
                                <?php if ($product_memories[$memory]['stock'] > 0): ?>
                                    <button type="button" class="select-memory" 
                                            data-memory="<?php echo htmlspecialchars($memory); ?>"
                                            onclick="selectMemory(this)">
                                        Wybierz
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <form id="addToCartForm" onsubmit="return handleAddToCart(event)">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="quantity" value="1">
                        
                        <?php if (!empty($colors)): ?>
                            <div class="form-group">
                                <label for="color">Kolor:</label>
                                <select name="color" id="color" class="form-control" required>
                                    <option value="">Wybierz kolor</option>
                                    <?php foreach ($colors as $color): ?>
                                        <option value="<?php echo htmlspecialchars($color['color']); ?>">
                                            <?php echo htmlspecialchars($color['color_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($memories)): ?>
                            <div class="form-group">
                                <label for="memory">Pamięć:</label>
                                <select name="memory" id="memory" class="form-control" required>
                                    <option value="">Wybierz pamięć</option>
                                    <?php foreach ($memories as $memory): ?>
                                        <?php 
                                        $memory_size = $memory['memory'];
                                        $memory_display = ($memory_size >= 1024) ? (($memory_size/1024) . ' TB') : ($memory_size . ' GB');
                                        
                                        // Pobierz stan magazynowy dla danej pamięci
                                        $stmt = $pdo->prepare("SELECT stock FROM product_memories WHERE product_id = ? AND memory_size = ?");
                                        $stmt->execute([$product['id'], $memory_size]);
                                        $memory_stock = $stmt->fetchColumn();
                                        
                                        if ($memory_stock > 0) {
                                            echo '<option value="' . htmlspecialchars($memory_size) . '">' . 
                                                 htmlspecialchars($memory_display) . ' (Dostępny: ' . $memory_stock . ' szt.)</option>';
                                        } else {
                                            echo '<option value="' . htmlspecialchars($memory_size) . '" disabled>' . 
                                                 htmlspecialchars($memory_display) . ' (Niedostępny)</option>';
                                        }
                                        ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary" id="addToCartBtn">
                            <i class="fas fa-shopping-cart"></i> Dodaj do koszyka
                        </button>
                    </form>
                <?php else: ?>
                    <p class="text-center">Zaloguj się, aby dodać do koszyka</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
    <!-- Usuwam modal dodawania zdjęcia - nie jest już potrzebny -->
    <?php endif; ?>

    <script>
    let isSubmitting = false;

    document.addEventListener('DOMContentLoaded', function() {
        // Obsługa zmiany koloru i zdjęcia
        const colorRadios = document.querySelectorAll('input[name="selected_color"]');
        const memoryRadios = document.querySelectorAll('input[name="selected_memory"]');
        const mainImage = document.getElementById('mainImage');
        
        colorRadios.forEach(radio => {
            radio.addEventListener('change', function(e) {
                e.stopPropagation();
                
                const imageUrl = this.getAttribute('data-image-url');
                if (imageUrl) {
                    mainImage.src = imageUrl;
                } else {
                    mainImage.src = '<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'images/default-product.jpg'; ?>';
                }
            });
        });

        memoryRadios.forEach(radio => {
            radio.addEventListener('change', function(e) {
                e.stopPropagation();
            });
        });
        
        // Ustaw początkowy kolor i zdjęcie
        if (colorRadios.length > 0) {
            const checkedRadio = document.querySelector('input[name="selected_color"]:checked');
            if (checkedRadio) {
                const imageUrl = checkedRadio.getAttribute('data-image-url');
                if (imageUrl) {
                    mainImage.src = imageUrl;
                } else {
                    mainImage.src = '<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'images/default-product.jpg'; ?>';
                }
            }
        }

        // Ustaw początkową pamięć
        if (memoryRadios.length > 0) {
            const checkedRadio = document.querySelector('input[name="selected_memory"]:checked');
        }

        // Nie dodajemy tutaj event listenera, ponieważ formularz używa onsubmit
    });

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => notification.classList.add('show'), 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    function handleAddToCart(event) {
        event.preventDefault();
        event.stopPropagation();

        if (isSubmitting) {
            return false;
        }

        const form = document.getElementById('addToCartForm');
        const formData = new FormData(form);
        
        // Sprawdź, czy wybrano kolor (jeśli jest wymagany)
        const colorSelect = document.getElementById('color');
        if (colorSelect && !colorSelect.value) {
            showNotification('Wybierz kolor produktu', 'error');
            return false;
        }

        // Sprawdź, czy wybrano pamięć (jeśli jest wymagana)
        const memorySelect = document.getElementById('memory');
        if (memorySelect && !memorySelect.value) {
            showNotification('Wybierz pamięć produktu', 'error');
            return false;
        }

        isSubmitting = true;
        const button = document.getElementById('addToCartBtn');
        if (!button || button.disabled) {
            isSubmitting = false;
            return false;
        }

        // Zablokuj możliwość dodawania do koszyka
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dodawanie...';

        fetch('api/add_to_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                // Aktualizuj licznik koszyka
                document.querySelectorAll('.cart-count').forEach(element => {
                    element.textContent = data.cart_count;
                });
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Wystąpił błąd podczas dodawania do koszyka', 'error');
        })
        .finally(() => {
            // Odblokuj przycisk i przywróć oryginalny tekst
            setTimeout(() => {
                isSubmitting = false;
                button.disabled = false;
                button.innerHTML = originalText;
            }, 500);
        });
        
        return false;
    }
    
    // Funkcja do zmiany głównego zdjęcia po kliknięciu w dodatkowe zdjęcie
    function changeMainImage(imageUrl) {
        document.getElementById('mainImage').src = imageUrl;
    }

    function selectMemory(button) {
        const memoryValue = button.getAttribute('data-memory');
        const memorySelect = document.getElementById('memory');
        
        // Znajdź opcję z odpowiednią wartością pamięci
        for (let i = 0; i < memorySelect.options.length; i++) {
            if (memorySelect.options[i].value === memoryValue) {
                memorySelect.selectedIndex = i;
                break;
            }
        }
        
        // Dodaj efekt wizualny
        button.classList.add('selected');
        setTimeout(() => {
            button.classList.remove('selected');
        }, 500);
    }
    </script>
</body>
</html> 