<?php
session_start();
require_once 'config.php';

// Utwórz tabelę product_images jeśli nie istnieje
$createTableSql = "CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)";
$pdo->exec($createTableSql);

// Pobierz liczbę produktów w koszyku jeśli użytkownik jest zalogowany
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $cart_count = $pdo->query("SELECT SUM(quantity) FROM cart_items WHERE user_id = " . $_SESSION['user_id'])->fetchColumn() ?: 0;
}

// Pobieranie produktów z bazy danych
$sql = "SELECT p.* 
         FROM products p
         JOIN featured_products fp ON p.id = fp.product_id
         ORDER BY fp.position
         LIMIT 6";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll();

// Pobierz produkty z normalnej tabeli tylko jeśli nie ma wyróżnionych
if (count($products) === 0) {
    $sql = "SELECT * FROM products LIMIT 6";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll();
}

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
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sklep Online</title>
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

        /* Nawigacja */
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
            top: 50%;
            transform: translateX(-220px) translateY(-50%);
            transition: transform 0.3s ease;
            width: 250px;
            z-index: 100;
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

        .product-options {
            margin-top: auto;
            padding: 10px 0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 12px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .add-to-cart-btn:hover {
            background: #1976D2;
            transform: translateY(-2px);
        }

        .add-to-cart-btn:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .add-to-cart-btn:hover:before {
            width: 300px;
            height: 300px;
        }

        .login-prompt {
            text-align: center;
            color: #666;
            margin: 10px 0;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .main-container {
                grid-template-columns: 1fr;
            }

            .filters {
                order: 1;
            }

            .products-section {
                order: 2;
            }

            .product-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .product-card img {
                width: 100%;
                height: 150px;
                margin-bottom: 10px;
            }
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 5px;
            color: white;
            font-weight: 500;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.3s, transform 0.3s;
            z-index: 1000;
        }

        .notification.success {
            background: #4CAF50;
        }

        .notification.error {
            background: #f44336;
        }

        .notification.show {
            opacity: 1;
            transform: translateY(0);
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
    </style>
</head>
<body>
    <div id="page-transition-overlay"></div>
    <header>
        <nav class="top-bar">
            <div class="container">
                <div class="logo">Sklep z Elektronika</div>
                <ul>
                    <li><a href="index.php" class="active">Strona Główna</a></li>
                    <li><a href="products.php">Produkty</a></li>
                    <li><a href="cart.php">Koszyk</a></li>
                    <li><a href="orders.php">Historia zamówień</a></li>
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <li><a href="login.php">Zaloguj się</a></li>
                    <?php else: ?>
                        <?php
                        // Sprawdź czy użytkownik jest administratorem
                        $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $user = $stmt->fetch();
                        if ($user && $user['is_admin'] == 1): ?>
                            <li><a href="admin/admin_panel.php">Panel Admina</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php">Wyloguj się</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>

    <div class="main-container">
        <!-- Filtry -->
        <aside class="filters">
            <h3>Filtry</h3>
            <form action="" method="GET">
                <input type="text" name="search" placeholder="Wyszukaj...">
                <input type="number" name="price_min" placeholder="Cena od">
                <input type="number" name="price_max" placeholder="Cena do">
                <button type="submit">Wyszukaj</button>
            </form>
        </aside>

        <!-- Lista produktów -->
        <section class="products-section">
            <h2>Wszystkie produkty</h2>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                            <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'images/default-product.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <div class="product-info">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                <p class="product-price"><?php echo number_format($product['price'], 2); ?> zł</p>
                            </div>
                        </a>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <div class="product-options">
                                <?php
                                $stmt = $pdo->prepare("SELECT color, color_name FROM product_colors WHERE product_id = ?");
                                $stmt->execute([$product['id']]);
                                $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <?php if (!empty($colors)): ?>
                                    <div class="form-group">
                                        <label for="color_<?php echo $product['id']; ?>">Kolor:</label>
                                        <select name="color" id="color_<?php echo $product['id']; ?>" class="form-control color-select">
                                            <option value="">Wybierz kolor</option>
                                            <?php foreach ($colors as $color): ?>
                                                <option value="<?php echo htmlspecialchars($color['color']); ?>">
                                                    <?php echo htmlspecialchars($color['color_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <?php
                                $stmt = $pdo->prepare("SELECT memory_size as memory FROM product_memories WHERE product_id = ?");
                                $stmt->execute([$product['id']]);
                                $memories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <?php if (!empty($memories)): ?>
                                    <div class="form-group">
                                        <label for="memory_<?php echo $product['id']; ?>">Pamięć:</label>
                                        <select name="memory" id="memory_<?php echo $product['id']; ?>" class="form-control memory-select">
                                            <option value="">Wybierz pamięć</option>
                                            <?php foreach ($memories as $memory): ?>
                                                <option value="<?php echo htmlspecialchars($memory['memory']); ?>">
                                                    <?php echo ($memory['memory'] >= 1024) ? (($memory['memory']/1024) . ' TB') : ($memory['memory'] . ' GB'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                <button class="add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-shopping-cart"></i>
                                    Dodaj do koszyka
                                </button>
                            </div>
                        <?php else: ?>
                            <p class="login-prompt">Zaloguj się, aby dodać do koszyka</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; 2025 Sklep z Elektronika</p>
        </div>
    </footer>

    <script>
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

            // Dodaj klasę loaded po załadowaniu strony
            setTimeout(() => {
                document.body.classList.add('loaded');
            }, 100);

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

            const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
            let isProcessing = false;

            addToCartButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (isProcessing) {
                        return;
                    }

                    const productId = this.getAttribute('data-product-id');
                    const colorSelect = this.closest('.product-card').querySelector('.color-select');
                    const memorySelect = this.closest('.product-card').querySelector('.memory-select');
                    
                    if (!colorSelect || !memorySelect) {
                        alert('Błąd: Brak wymaganych pól wyboru');
                        return;
                    }

                    const color = colorSelect.value;
                    const memory = memorySelect.value;

                    if (!color || !memory) {
                        alert('Proszę wybrać kolor i pamięć');
                        return;
                    }

                    isProcessing = true;
                    const originalText = this.innerHTML;
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dodawanie...';

                    fetch('api/add_to_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `product_id=${productId}&color=${color}&memory=${memory}`
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
                        setTimeout(() => {
                            isProcessing = false;
                            this.disabled = false;
                            this.innerHTML = originalText;
                        }, 500);
                    });
                });
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
        });
    </script>
</body>
</html>