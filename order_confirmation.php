<?php
session_start();
require_once 'config.php';

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Sprawdź czy podano ID zamówienia
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header('Location: orders.php');
    exit();
}

$order_id = $_GET['order_id'];

// Pobierz dane zamówienia
$stmt = $pdo->prepare("
    SELECT o.*
    FROM orders o
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

// Jeśli zamówienie nie istnieje lub nie należy do zalogowanego użytkownika
if (!$order) {
    header('Location: orders.php');
    exit();
}

// Pobierz dane adresowe
$stmt = $pdo->prepare("
    SELECT *
    FROM shipping_addresses
    WHERE order_id = ?
");
$stmt->execute([$order_id]);
$shipping_address = $stmt->fetch();

// Pobierz produkty z zamówienia
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, pc.color_name AS color, pm.memory_size AS memory
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_colors pc ON oi.product_id = pc.product_id
    LEFT JOIN product_memories pm ON oi.product_id = pm.product_id
    WHERE oi.order_id = ?
    GROUP BY oi.id
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Formatowanie daty
$order['created_at'] = date('d.m.Y H:i', strtotime($order['created_at']));

// Tłumaczenie statusu
$status_translations = [
    'new' => 'Nowe',
    'processing' => 'W realizacji',
    'shipped' => 'Wysłane',
    'delivered' => 'Dostarczone',
    'cancelled' => 'Anulowane'
];
$order['status'] = $status_translations[$order['status']] ?? $order['status'];

// Tłumaczenie metody dostawy
$shipping_translations = [
    'courier' => 'Kurier',
    'parcel_locker' => 'Paczkomat',
    'pickup' => 'Odbiór osobisty'
];
$order['shipping_method'] = $shipping_translations[$order['shipping_method']] ?? $order['shipping_method'];
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potwierdzenie zamówienia - Sklep Online</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
        }

        body {
            background-color: #f8f9fa;
            opacity: 0;
            transform: translateX(-20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
            padding-top: 0;
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
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        nav .logo {
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
            text-decoration: none;
            margin-right: 20px;
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
            padding: 8px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
            font-size: 1rem;
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

        /* Karta zamówienia */
        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .order-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .order-items {
            margin-bottom: 20px;
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

        .order-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .order-item-price {
            color: #2196F3;
            font-weight: bold;
        }

        .order-summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .order-summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .order-summary-row.total {
            font-weight: bold;
            font-size: 1.1rem;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
        }

        /* Animacje */
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

        .order-card {
            animation: fadeInUp 0.5s ease forwards;
        }

        /* Responsywność */
        @media (max-width: 768px) {
            .main-container {
                padding: 0 15px;
            }

            .order-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-item img {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="container">
            <a href="index.php" class="logo">Sklep Online</a>
            <ul>
                <li><a href="index.php">Strona główna</a></li>
                <li><a href="products.php">Produkty</a></li>
                <li><a href="cart.php">Koszyk</a></li>
                <li><a href="orders.php">Historia zamówień</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="logout.php">Wyloguj</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="main-container">
        <div class="order-card">
            <div class="order-header">
                <h2>Potwierdzenie zamówienia</h2>
                <p>Dziękujemy za złożenie zamówienia!</p>
            </div>

            <div class="order-details">
                <h3>Szczegóły zamówienia</h3>
                <div class="order-summary">
                    <div class="order-summary-row">
                        <span>Numer zamówienia:</span>
                        <span>#<?php echo $order['id']; ?></span>
                    </div>
                    <div class="order-summary-row">
                        <span>Data zamówienia:</span>
                        <span><?php echo $order['created_at']; ?></span>
                    </div>
                    <div class="order-summary-row">
                        <span>Status:</span>
                        <span><?php echo $order['status']; ?></span>
                    </div>
                    <div class="order-summary-row">
                        <span>Metoda dostawy:</span>
                        <span><?php echo $order['shipping_method']; ?></span>
                    </div>
                </div>
            </div>

            <div class="order-items">
                <h3>Zamówione produkty</h3>
                <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <div class="order-item-details">
                            <div class="order-item-title"><?php echo $item['name']; ?></div>
                            <div>Kolor: <?php echo $item['color']; ?></div>
                            <div>Pamięć: <?php echo $item['memory']; ?></div>
                            <div class="order-item-price"><?php echo number_format($item['price'], 2); ?> PLN</div>
                            <div>Ilość: <?php echo $item['quantity']; ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="order-summary">
                <div class="order-summary-row total">
                    <span>Razem do zapłaty:</span>
                    <span><?php echo number_format($order['total_amount'], 2); ?> PLN</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dodaj klasę loaded do body po załadowaniu strony
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.add('loaded');
        });
    </script>
</body>
</html> 