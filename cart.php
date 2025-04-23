<?php
session_start();
require_once 'config.php';

// Sprawdzenie czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Obsługa komunikatów
$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'removed':
            $success_message = 'Produkt został usunięty z koszyka.';
            break;
        case 'updated':
            $success_message = 'Ilość produktu została zaktualizowana.';
            break;
        default:
            $success_message = 'Operacja zakończona sukcesem.';
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'remove_failed':
            $error_message = 'Nie udało się usunąć produktu z koszyka.';
            break;
        case 'invalid_request':
            $error_message = 'Nieprawidłowe żądanie.';
            break;
        default:
            $error_message = 'Wystąpił błąd podczas operacji.';
    }
}

try {
    // Pobieranie produktów z koszyka
    $stmt = $pdo->prepare("
        SELECT ci.*, p.name, p.price, p.image_url, p.stock, pc.color, pc.color_name, ci.memory 
        FROM cart_items ci 
        JOIN products p ON ci.product_id = p.id 
        LEFT JOIN product_colors pc ON ci.color = pc.color AND ci.product_id = pc.product_id
        WHERE ci.user_id = ?
        ORDER BY ci.id DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Popraw ścieżki do obrazów
    foreach ($cart_items as &$item) {
        if (!empty($item['image_url'])) {
            // Jeśli ścieżka zaczyna się od 'admin/', usuń ten przedrostek
            if (strpos($item['image_url'], 'admin/') === 0) {
                $item['image_url'] = substr($item['image_url'], 6); // usuń 'admin/'
            }
        }
    }
    unset($item); // usuń referencję

    // Obliczanie sumy koszyka
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }

} catch(PDOException $e) {
    $error_message = "Błąd podczas pobierania koszyka: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koszyk - Sklep Online</title>
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
        
        .top-bar.visible {
            transform: translateY(0);
        }
        
        /* Dodatkowy padding dla głównej zawartości */
        body {
            padding-top: 60px;
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
        .cart-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
            margin-top: 1rem;
        }

        /* Elementy koszyka */
        .cart-item {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 1rem;
        }

        .cart-item h5 {
            color: #333;
            margin: 0 0 10px 0;
            font-size: 1.1rem;
        }

        .product-price {
            color: #007bff;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .form-control {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px 12px;
        }

        .btn-danger {
            background-color: #dc3545;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .cart-item-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: flex-end;
        }

        .total-price {
            color: #007bff;
            font-size: 1.4rem;
            font-weight: bold;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            transition: background-color 0.3s;
            font-size: 1.1rem;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .alert {
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .alert-info {
            background-color: #cce5ff;
            border-color: #b8daff;
            color: #004085;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
        }

        @media (max-width: 768px) {
            .cart-container {
                padding: 0 15px;
            }

            .cart-item {
                padding: 15px;
            }

            .cart-item img {
                width: 80px;
                height: 80px;
            }

            .cart-item-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
                margin-top: 10px;
            }
        }

        .cart-item-details {
            flex-grow: 1;
        }

        .cart-item-name {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .cart-item-color {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #666;
        }

        .cart-item-memory {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #666;
        }

        .color-circle {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 1px solid #ddd;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-btn {
            padding: 0.25rem 0.5rem;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 4px;
        }

        .quantity-input {
            width: 50px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 0.25rem;
        }

        .remove-item {
            color: #dc3545;
            cursor: pointer;
            padding: 0.5rem;
        }

        .cart-summary {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .empty-cart {
            text-align: center;
            padding: 2rem;
        }

        .empty-cart i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
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
                    <li><a href="products.php">Produkty</a></li>
                    <li><a href="cart.php" class="active">Koszyk</a></li>
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

    <div class="cart-container">
        <h2 class="mb-4">Twój Koszyk</h2>

        <?php if ($success_message): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Twój koszyk jest pusty</h3>
                <p>Dodaj produkty do koszyka, aby zobaczyć je tutaj.</p>
                <a href="products.php" class="btn btn-primary">Przejdź do produktów</a>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item" data-item-id="<?php echo $item['id']; ?>">
                        <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'images/default-product.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                             class="cart-item-image">
                        
                        <div class="cart-item-details">
                            <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            
                            <?php if (!empty($item['color_name'])): ?>
                                <div class="cart-item-color">
                                    <strong>Kolor:</strong> <span style="display: inline-block; width: 15px; height: 15px; background-color: <?php echo htmlspecialchars($item['color']); ?>; border: 1px solid #ddd; margin-right: 5px; vertical-align: middle;"></span> <?php echo htmlspecialchars($item['color_name']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($item['memory'])): ?>
                                <div class="cart-item-memory">
                                    <strong>Pamięć:</strong> 
                                    <?php echo ($item['memory'] >= 1024) ? (($item['memory']/1024) . ' TB') : ($item['memory'] . ' GB'); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="cart-item-price">
                                <span class="price"><?php echo number_format($item['price'], 2); ?> zł</span>
                            </div>
                            
                            <div class="quantity-controls">
                                <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 'decrease')">-</button>
                                <input type="number" id="quantity-<?php echo $item['id']; ?>" class="quantity-input" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" max="<?php echo $item['stock']; ?>" 
                                       onchange="updateQuantity(<?php echo $item['id']; ?>, 'set', this.value)">
                                <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 'increase')">+</button>
                            </div>
                        </div>
                        
                        <div class="remove-item" onclick="removeItem(<?php echo $item['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <h3>Podsumowanie</h3>
                <p>Suma: <strong><?php echo number_format($total, 2); ?> zł</strong></p>
                <a href="checkout.php" class="btn btn-primary">Przejdź do kasy</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    function updateQuantity(itemId, action, value = null) {
        let quantity;
        if (action === 'set') {
            quantity = parseInt(value);
        } else if (action === 'increase') {
            quantity = parseInt(document.getElementById('quantity-' + itemId).value) + 1;
        } else if (action === 'decrease') {
            quantity = parseInt(document.getElementById('quantity-' + itemId).value) - 1;
        }

        if (quantity < 1) {
            removeItem(itemId);
            return;
        }

        fetch('api/update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                item_id: itemId,
                quantity: quantity
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
                // Przywróć poprzednią wartość w przypadku błędu
                if (action === 'set') {
                    document.getElementById('quantity-' + itemId).value = document.getElementById('quantity-' + itemId).getAttribute('data-original-value');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Wystąpił błąd podczas aktualizacji koszyka');
        });
    }

    function removeItem(itemId) {
        if (confirm('Czy na pewno chcesz usunąć ten produkt z koszyka?')) {
            fetch('api/remove_from_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    item_id: itemId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Znajdź i usuń element z DOM
                    const itemElement = document.querySelector(`.cart-item[data-item-id="${itemId}"]`);
                    if (itemElement) {
                        itemElement.remove();
                    }
                    
                    // Sprawdź czy koszyk jest pusty
                    const cartItems = document.querySelector('.cart-items');
                    if (cartItems && cartItems.children.length === 0) {
                        location.reload(); // Przeładuj stronę, aby pokazać pusty koszyk
                    }
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas usuwania produktu z koszyka');
            });
        }
    }

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

        // Animacja fade-in przy załadowaniu strony
        document.body.style.opacity = '1';

        // Zapisz oryginalną wartość ilości przy załadowaniu strony
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.setAttribute('data-original-value', input.value);
        });
    });
    </script>
</body>
</html> 