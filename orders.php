<?php
// Zamiast prostego session_start(), użyjmy warunku
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Pobierz zamówienia użytkownika
$stmt = $pdo->prepare("
    SELECT o.*, 
           o.shipping_cost,
           o.discount_amount,
           oi.product_id,
           oi.quantity,
           oi.price as historical_price,
           p.price as current_price,
           p.name as product_name,
           p.image_url
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orderItems = $stmt->fetchAll();

// Grupuj zamówienia według ID zamówienia
$orders = [];
foreach ($orderItems as $item) {
    if (!isset($orders[$item['id']])) {
        $orders[$item['id']] = [
            'id' => $item['id'],
            'total_amount' => $item['total_amount'],
            'shipping_cost' => $item['shipping_cost'],
            'discount_amount' => $item['discount_amount'],
            'status' => $item['status'],
            'created_at' => $item['created_at'],
            'items' => []
        ];
    }
    $orders[$item['id']]['items'][] = [
        'product_name' => $item['product_name'],
        'quantity' => $item['quantity'],
        'historical_price' => $item['historical_price'],
        'current_price' => $item['current_price'],
        'image_url' => $item['image_url']
    ];
}

// Pobierz liczbę produktów w koszyku
$cart_count = $pdo->query("SELECT SUM(quantity) FROM cart_items WHERE user_id = " . $_SESSION['user_id'])->fetchColumn() ?: 0;
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia zamówień - Sklep Online</title>
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
        
        /* Dodatkowy padding dla głównej zawartości, aby kompensować stały pasek */
        main {
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
        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
            margin-top: 1rem;
        }

        /* Lista zamówień */
        .orders-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .orders-section h2 {
            margin: 0 0 20px 0;
            font-size: 1.5rem;
            color: #333;
        }

        .order-card {
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .order-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #333;
        }

        .order-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .order-items {
            padding: 15px;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }

        .item-details {
            flex-grow: 1;
        }

        .item-name {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }

        .item-price {
            color: #007bff;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .item-price div:first-child {
            color: #007bff;
            font-weight: 500;
        }
        
        .item-price div:last-child {
            color: #28a745;
            font-size: 0.9rem;
        }

        .item-quantity {
            color: #666;
            font-size: 0.9rem;
        }

        .order-total {
            padding: 15px;
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
            text-align: right;
            font-weight: 500;
        }
        
        .order-summary-details {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 10px;
            text-align: right;
        }
        
        .order-summary-details div {
            margin-bottom: 3px;
        }
        
        .order-final-total {
            color: #343a40;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .order-current-total {
            color: #28a745;
            font-size: 0.95rem;
        }

        .no-orders {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 0 15px;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-status {
                margin-top: 10px;
            }

            .order-item {
                flex-direction: column;
                text-align: center;
            }

            .item-image {
                margin: 0 0 10px 0;
            }
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
                    <li><a href="index.php">Strona Główna</a></li>
                    <li><a href="products.php">Produkty</a></li>
                    <li><a href="cart.php">Koszyk</a></li>
                    <li><a href="orders.php" class="active">Historia zamówień</a></li>
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
        <section class="orders-section">
            <h2>Historia zamówień</h2>
            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-bag fa-3x mb-3"></i>
                    <p>Nie masz jeszcze żadnych zamówień</p>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <h3>Zamówienie #<?php echo htmlspecialchars($order['id']); ?></h3>
                            <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                                <?php 
                                    $statusMap = [
                                        'pending' => 'Oczekujące',
                                        'completed' => 'Zrealizowane',
                                        'cancelled' => 'Anulowane'
                                    ];
                                    echo $statusMap[$order['status']] ?? $order['status'];
                                ?>
                            </span>
                        </div>
                        <div class="order-items">
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="order-item">
                                    <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'images/default-product.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                         class="item-image">
                                    <div class="item-details">
                                        <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <div class="item-price">
                                            <div>Cena zakupu: <?php echo number_format($item['historical_price'], 2); ?> zł</div>
                                            <div>Aktualna cena: <?php echo number_format($item['current_price'], 2); ?> zł</div>
                                        </div>
                                        <div class="item-quantity">Ilość: <?php echo $item['quantity']; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php
                        // Oblicz wartości zamówienia
                        $current_total = 0;
                        $historical_total = 0;
                        foreach ($order['items'] as $item) {
                            $current_total += $item['current_price'] * $item['quantity'];
                            $historical_total += $item['historical_price'] * $item['quantity'];
                        }
                        // Oblicz pierwotną kwotę przed zniżką i kosztami dostawy
                        $original_total = $order['total_amount'] + $order['discount_amount'] - $order['shipping_cost'];
                        ?>
                        <div class="order-total">
                            <div class="order-summary-details">
                                <div>Wartość produktów: <?php echo number_format($historical_total, 2); ?> zł</div>
                                <?php if ($order['shipping_cost'] > 0): ?>
                                <div>+ Koszt dostawy: <?php echo number_format($order['shipping_cost'], 2); ?> zł</div>
                                <?php endif; ?>
                                <?php if ($order['discount_amount'] > 0): ?>
                                <div>- Rabat: <?php echo number_format($order['discount_amount'], 2); ?> zł</div>
                                <?php endif; ?>
                            </div>
                            <div class="order-final-total">Zapłacono: <?php echo number_format($order['total_amount'], 2); ?> zł</div>
                            <div class="order-current-total">Aktualna wartość: <?php echo number_format($current_total + $order['shipping_cost'], 2); ?> zł</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
        });
    </script>
</body>
</html> 