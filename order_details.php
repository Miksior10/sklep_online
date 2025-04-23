<?php
session_start();
require_once 'config.php';

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Sprawdź czy podano ID zamówienia
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$order_id = $_GET['id'];

// Pobierz dane zamówienia
$stmt = $pdo->prepare("
    SELECT o.*, sa.full_name, sa.street, sa.city, sa.postal_code, sa.shipping_point
    FROM orders o
    LEFT JOIN shipping_addresses sa ON o.id = sa.order_id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

// Jeśli zamówienie nie istnieje lub nie należy do zalogowanego użytkownika
if (!$order) {
    header('Location: orders.php');
    exit();
}

// Pobierz produkty z zamówienia
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.image_url
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Pobierz dane płatności
$stmt = $pdo->prepare("
    SELECT * FROM payments
    WHERE order_id = ?
");
$stmt->execute([$order_id]);
$payment = $stmt->fetch();

// Pobierz liczbę produktów w koszyku
$cart_count = $pdo->query("SELECT SUM(quantity) FROM cart_items WHERE user_id = " . $_SESSION['user_id'])->fetchColumn() ?: 0;
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szczegóły zamówienia #<?php echo $order_id; ?> - Sklep Online</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .order-details-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .order-title {
            font-size: 24px;
            font-weight: bold;
        }
        
        .order-date {
            color: #666;
        }
        
        .order-status {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .status-new {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .status-processing {
            background-color: #fff8e1;
            color: #ff8f00;
        }
        
        .status-shipped {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .status-delivered {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .status-cancelled {
            background-color: #ffebee;
            color: #d32f2f;
        }
        
        .order-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .order-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .address-details, .payment-details {
            line-height: 1.6;
        }
        
        .order-items {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
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
            flex: 1;
        }
        
        .item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .item-price {
            color: #666;
        }
        
        .item-quantity {
            margin-left: 20px;
            color: #666;
            font-weight: bold;
        }
        
        .order-summary {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .summary-total {
            font-size: 18px;
            font-weight: bold;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .back-button:hover {
            background: #45a049;
        }
        
        @media (max-width: 768px) {
            .order-sections {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Sklep Online</div>
            <ul>
                <li><a href="index.php">Strona główna</a></li>
                <li><a href="products.php">Produkty</a></li>
                <li>
                    <a href="cart.php" class="cart-link">
                        Koszyk
                        <span class="cart-count" <?php echo $cart_count == 0 ? 'style="display:none;"' : ''; ?>>
                            <?php echo $cart_count; ?>
                        </span>
                    </a>
                </li>
                <li><a href="orders.php" class="active">Historia zamówień</a></li>
                <li><a href="logout.php">Wyloguj</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="order-details-container">
            <div class="order-header">
                <div class="order-title">Zamówienie #<?php echo $order_id; ?></div>
                <div class="order-date">
                    <?php 
                    if (isset($order['created_at'])) {
                        echo date('d.m.Y H:i', strtotime($order['created_at']));
                    } else {
                        echo "Data nieznana";
                    }
                    ?>
                </div>
            </div>
            
            <div class="order-status status-<?php echo strtolower($order['status']); ?>">
                <?php 
                    switch($order['status']) {
                        case 'new': echo 'Nowe'; break;
                        case 'processing': echo 'W realizacji'; break;
                        case 'shipped': echo 'Wysłane'; break;
                        case 'delivered': echo 'Dostarczone'; break;
                        case 'cancelled': echo 'Anulowane'; break;
                        default: echo ucfirst($order['status']);
                    }
                ?>
            </div>
            
            <div class="order-sections">
                <div class="order-section">
                    <div class="section-title">Adres dostawy</div>
                    <div class="address-details">
                        <?php if (isset($order['full_name'])): ?>
                            <p><strong><?php echo htmlspecialchars($order['full_name']); ?></strong></p>
                            <p><?php echo htmlspecialchars($order['street']); ?></p>
                            <p><?php echo htmlspecialchars($order['postal_code']); ?> <?php echo htmlspecialchars($order['city']); ?></p>
                            <?php if ($order['shipping_point']): ?>
                                <p>Paczkomat: <?php echo htmlspecialchars($order['shipping_point']); ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>Brak danych adresowych</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="order-section">
                    <div class="section-title">Metoda dostawy i płatności</div>
                    <div class="payment-details">
                        <p>
                            <strong>Metoda dostawy:</strong> 
                            <?php 
                                switch($order['shipping_method']) {
                                    case 'courier': echo 'Kurier'; break;
                                    case 'parcel_locker': echo 'Paczkomat InPost'; break;
                                    case 'pickup': echo 'Odbiór osobisty'; break;
                                    default: echo ucfirst($order['shipping_method']);
                                }
                            ?>
                        </p>
                        <p><strong>Koszt dostawy:</strong> <?php echo number_format($order['shipping_cost'], 2); ?> PLN</p>
                        
                        <?php if ($payment): ?>
                            <p><strong>Status płatności:</strong> 
                                <?php 
                                    switch($payment['status']) {
                                        case 'completed': echo 'Opłacone'; break;
                                        case 'pending': echo 'Oczekujące'; break;
                                        case 'failed': echo 'Nieudane'; break;
                                        default: echo ucfirst($payment['status']);
                                    }
                                ?>
                            </p>
                            <p><strong>Data płatności:</strong> <?php echo date('d.m.Y H:i', strtotime($payment['payment_date'])); ?></p>
                            <p><strong>Karta:</strong> **** **** **** <?php echo substr($payment['card_number'], -4); ?></p>
                        <?php else: ?>
                            <p><strong>Status płatności:</strong> Brak informacji</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="section-title">Zamówione produkty</div>
            <div class="order-items">
                <?php if (count($order_items) > 0): ?>
                    <?php foreach ($order_items as $item): ?>
                        <div class="order-item">
                            <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'images/default.jpg'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-price"><?php echo number_format($item['price'], 2); ?> PLN</div>
                            </div>
                            <div class="item-quantity">x<?php echo $item['quantity']; ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="order-item">
                        <p>Brak danych o produktach</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="order-summary">
                <div class="section-title">Podsumowanie</div>
                <div class="summary-row">
                    <div>Wartość produktów:</div>
                    <div><?php echo number_format($order['total_amount'] - $order['shipping_cost'], 2); ?> PLN</div>
                </div>
                <div class="summary-row">
                    <div>Koszt dostawy:</div>
                    <div><?php echo number_format($order['shipping_cost'], 2); ?> PLN</div>
                </div>
                <div class="summary-row summary-total">
                    <div>Razem:</div>
                    <div><?php echo number_format($order['total_amount'], 2); ?> PLN</div>
                </div>
            </div>
            
            <a href="orders.php" class="back-button">← Powrót do historii zamówień</a>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Sklep Online</p>
    </footer>
</body>
</html> 