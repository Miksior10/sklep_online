<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

try {
    // Pobieranie wszystkich produktów wraz z pierwszym kolorem
    $stmt = $pdo->query("
        SELECT p.*, pc.color, pc.color_name 
        FROM products p 
        LEFT JOIN product_colors pc ON p.id = pc.product_id 
        GROUP BY p.id 
        ORDER BY p.name
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Popraw ścieżki do obrazów
    foreach ($products as &$product) {
        if (!empty($product['image_url'])) {
            // Jeśli ścieżka zaczyna się od 'admin/', usuń ten przedrostek
            if (strpos($product['image_url'], 'admin/') === 0) {
                $product['image_url'] = substr($product['image_url'], 6); // usuń 'admin/'
            }
        }
    }
    unset($product); // usuń referencję

} catch(PDOException $e) {
    $error_message = "Błąd podczas pobierania produktów: " . $e->getMessage();
}

// Pobierz liczbę produktów w koszyku jeśli użytkownik jest zalogowany
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $cart_count = $pdo->query("SELECT SUM(quantity) FROM cart_items WHERE user_id = " . $_SESSION['user_id'])->fetchColumn() ?: 0;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produkty - Sklep Online</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            opacity: 0;
            transform: translateX(-20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
            padding-top: 60px;
        }

        body.loaded {
            opacity: 1;
            transform: translateX(0);
        }

        body.fade-out {
            opacity: 0;
            transform: translateX(20px);
        }

        #page-transition-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #007bff;
            transform: translateX(-100%);
            z-index: 9999;
            pointer-events: none;
        }

        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }

        @keyframes slideOut {
            from { transform: translateX(0); }
            to { transform: translateX(100%); }
        }

        /* Nawigacja */
        nav {
            background-color: #007bff;
            padding: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav .logo {
            color: white;
            font-size: 1.2rem;
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
            margin-left: 15px;
        }

        nav ul li a {
            position: relative;
            color: white;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        nav ul li a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            background-color: white;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        nav ul li a:hover::after {
            width: 100%;
        }

        nav ul li a.active::after {
            width: 100%;
        }

        /* Główny kontener */
        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
            position: relative;
        }

        /* Filtry */
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: fixed;
            left: 0;
            top: 20%;
            transform: translateX(-220px) translateY(-50%);
            transition: transform 0.5s ease;
            width: 250px;
            z-index: 100;
            opacity: 1;
        }

        .filters:hover {
            transform: translateX(0) translateY(-50%);
        }

        .filters::after {
            content: 'Filtry';
            position: absolute;
            right: -40px;
            top: 50%;
            transform: translateY(-50%) rotate(-90deg);
            background: white;
            padding: 10px 20px;
            border-radius: 0 0 8px 8px;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.1);
            color: #333;
            font-weight: 500;
            opacity: 1;
            transition: opacity 0.3s ease;
            white-space: nowrap;
        }

        .filters:hover::after {
            opacity: 0;
        }

        .filters h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.2rem;
            color: #333;
        }

        .filters input[type="text"],
        .filters input[type="number"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: border-color 0.3s ease;
        }

        .filters input[type="text"]:focus,
        .filters input[type="number"]:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        .filters button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filters button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        /* Produkty */
        .products-section {
            width: 100%;
        }

        .products-section h2 {
            margin-top: 20px;
            margin-bottom: 20px;
            font-size: 1.5rem;
            color: #333;
            padding-left: 20px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        /* Animacje dla produktów */
        .product-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            padding: 15px;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s ease forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .product-card:nth-child(1) { animation-delay: 0.1s; }
        .product-card:nth-child(2) { animation-delay: 0.2s; }
        .product-card:nth-child(3) { animation-delay: 0.3s; }
        .product-card:nth-child(4) { animation-delay: 0.4s; }
        .product-card:nth-child(5) { animation-delay: 0.5s; }
        .product-card:nth-child(6) { animation-delay: 0.6s; }

        .product-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }

        .product-card:hover img {
            transform: scale(1.05);
        }

        .product-info {
            padding: 10px 0;
            transition: transform 0.3s ease;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin: 0 0 10px 0;
            transition: color 0.3s ease;
        }

        .product-card:hover .product-title {
            color: #007bff;
        }

        .product-description {
            font-size: 0.9rem;
            color: #666;
            margin: 0 0 10px 0;
            line-height: 1.4;
        }

        .product-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2196F3;
            margin: 0 0 15px 0;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-price {
            transform: scale(1.1);
        }

        .product-options-container {
            margin-top: auto;
        }

        .product-options {
            margin-bottom: 10px;
        }

        .product-options select {
            width: 100%;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 7px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.85rem;
        }

        .add-to-cart-btn:hover {
            background-color: #0056b3;
        }

        /* Style dla opcji produktu */
        .product-options label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: #666;
        }

        .product-options select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        /* Notification styles */
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

        .cart-count.updated {
            animation: pulse 0.3s ease-in-out;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            padding: 20px;
            background-color: white;
            box-shadow: 0 -1px 3px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .main-container {
                grid-template-columns: 1fr;
                padding: 0 15px;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            }
        }

        /* Animacja dla górnego paska */
        .top-bar {
            background-color: #007bff;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .top-bar.hidden {
            transform: translateY(-100%);
        }
    </style>
</head>
<body class="bg-light">
    <div id="page-transition-overlay"></div>
    <header>
        <nav class="top-bar">
            <div class="container">
                <div class="logo">Sklep z Elektronika</div>
                <ul>
                    <li><a href="index.php">Strona Główna</a></li>
                    <li><a href="products.php" class="active">Produkty</a></li>
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

    <div class="main-container">
        <!-- Panel wyszukiwania -->
        <div class="filters">
            <h3>Filtry</h3>
            <form method="GET" action="">
                <input type="text" name="search" 
                       placeholder="Wyszukaj..." 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                
                <input type="number" name="min_price" 
                       placeholder="Cena od"
                       value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>">

                <input type="number" name="max_price" 
                       placeholder="Cena do"
                       value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>">

                <button type="submit">Wyszukaj</button>
            </form>
        </div>

        <!-- Lista produktów -->
        <div class="products-section">
            <h2>Wszystkie produkty</h2>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                            <div class="product-image-container">
                                <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'images/default-product.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="product-image">
                            </div>
                            
                            <div class="product-info">
                                <h3 class="product-name">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </h3>
                                <p class="product-description">
                                    <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?>
                                </p>
                                <p class="product-price"><?php echo number_format($product['price'], 2); ?> zł</p>
                            </div>
                        </a>
                        
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <div class="product-options-container" data-product-id="<?php echo $product['id']; ?>">
                                <?php
                                // Pobierz dostępne kolory dla produktu
                                $stmt = $pdo->prepare("SELECT color, color_name FROM product_colors WHERE product_id = ?");
                                $stmt->execute([$product['id']]);
                                $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                // Pobierz dostępne opcje pamięci dla produktu
                                $stmt = $pdo->prepare("SELECT memory_size FROM product_memories WHERE product_id = ?");
                                $stmt->execute([$product['id']]);
                                $memories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>

                                <?php if (!empty($colors)): ?>
                                    <div class="product-options">
                                        <label for="color-<?php echo $product['id']; ?>">Kolor:</label>
                                        <select id="color-<?php echo $product['id']; ?>" class="form-control mb-2 product-color">
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
                                    <div class="product-options">
                                        <label for="memory-<?php echo $product['id']; ?>">Pamięć:</label>
                                        <select id="memory-<?php echo $product['id']; ?>" class="form-control mb-2 product-memory">
                                            <option value="">Wybierz pamięć</option>
                                            <?php foreach ($memories as $memory): ?>
                                                <option value="<?php echo htmlspecialchars($memory['memory_size']); ?>">
                                                    <?php echo htmlspecialchars($memory['memory_size']); ?> GB
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <button type="button" class="btn btn-primary add-to-cart-btn" 
                                        data-product-id="<?php echo $product['id']; ?>">
                                    Dodaj do koszyka
                                </button>
                            </div>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary">Zaloguj się, aby dodać do koszyka</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; 2025 Sklep z Elektronika</p>
        </div>
    </footer>

    <script>
    let isProcessing = false;
    let lastClickTime = 0;
    let lastProductId = null;

    document.addEventListener('DOMContentLoaded', function() {
        const topBar = document.querySelector('.top-bar');
        let lastScrollY = window.scrollY;
        let ticking = false;

        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    const currentScrollY = window.scrollY;
                    
                    if (currentScrollY > lastScrollY && currentScrollY > 60) {
                        // Przewijanie w dół i nie jesteśmy na samej górze
                        topBar.classList.add('hidden');
                    } else {
                        // Przewijanie w górę lub jesteśmy blisko góry
                        topBar.classList.remove('hidden');
                    }
                    
                    lastScrollY = currentScrollY;
                    ticking = false;
                });

                ticking = true;
            }
        });

        // Dodaj klasę loaded do body
        document.body.classList.add('loaded');
        
        // Dodaj klasę loaded do filtrów
        const filters = document.querySelector('.filters');
        if (filters) {
            filters.classList.add('loaded');
        }

        // Obsługa przejść między stronami
        document.querySelectorAll('nav a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                const overlay = document.getElementById('page-transition-overlay');
                
                overlay.style.animation = 'slideIn 0.5s ease forwards';
                document.body.classList.add('fade-out');
                
                setTimeout(() => {
                    window.location.href = href;
                }, 500);
            });
        });

        // Dodaj event listener do przycisków "Dodaj do koszyka"
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const now = Date.now();
                const container = this.closest('.product-options-container');
                const productId = container.getAttribute('data-product-id');

                // Sprawdź, czy to ten sam produkt i minęło mniej niż 2 sekundy
                if (productId === lastProductId && now - lastClickTime < 2000) {
                    return;
                }

                lastClickTime = now;
                lastProductId = productId;

                if (isProcessing) {
                    return;
                }

                if (!container || !productId) {
                    return;
                }

                const colorSelect = container.querySelector('.product-color');
                const memorySelect = container.querySelector('.product-memory');

                const color = colorSelect?.value;
                const memory = memorySelect?.value;

                // Sprawdź, czy wymagane pola są wypełnione
                if (colorSelect && !color) {
                    showNotification('Wybierz kolor', 'error');
                    return;
                }
                if (memorySelect && !memory) {
                    showNotification('Wybierz pamięć', 'error');
                    return;
                }

                // Zablokuj możliwość dodawania do koszyka
                isProcessing = true;
                button.disabled = true;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dodawanie...';

                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('color', color || '');
                formData.append('memory', memory || '');
                formData.append('quantity', '1');

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
                            element.classList.add('updated');
                            setTimeout(() => element.classList.remove('updated'), 300);
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
                        isProcessing = false;
                        button.disabled = false;
                        button.innerHTML = originalText;
                    }, 2000); // Zwiększamy opóźnienie przed odblokowaniem przycisku
                });
            });
        });
    });

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
    </script>
</body>
</html> 