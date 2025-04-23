<?php
session_start();
require_once '../config.php';

// Sprawdzenie czy użytkownik jest adminem
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: admin_login.php");
    exit();
}

// Sprawdź czy podano ID zamówienia
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_orders.php");
    exit();
}

$order_id = $_GET['id'];

// Pobierz dane zamówienia
$stmt = $pdo->prepare("
    SELECT o.*, u.username
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: admin_orders.php");
    exit();
}

// Pobierz historię statusów
$stmt = $pdo->prepare("
    SELECT h.*, u.username as admin_name
    FROM order_status_history h
    LEFT JOIN users u ON h.admin_id = u.id
    WHERE h.order_id = ?
    ORDER BY h.created_at DESC
");
$stmt->execute([$order_id]);
$history = $stmt->fetchAll();

// Pobierz dostępne statusy zamówień
$statuses = ['new' => 'Nowe', 'processing' => 'W realizacji', 'shipped' => 'Wysłane', 'delivered' => 'Dostarczone', 'completed' => 'Zrealizowane', 'cancelled' => 'Anulowane'];
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Zamówienia #<?php echo $order_id; ?> - Panel Administracyjny</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .order-info {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .order-info-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .order-title {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }
        
        .order-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .status-new {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .status-processing {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-shipped {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background-color: #c3e6cb;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .detail-item {
            margin-bottom: 10px;
        }
        
        .detail-label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #666;
        }
        
        .detail-value {
            font-size: 16px;
        }
        
        .history-timeline {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 30px;
            padding-bottom: 20px;
            border-left: 2px solid #ddd;
            margin-left: 15px;
        }
        
        .timeline-item:last-child {
            border-left: 2px solid transparent;
        }
        
        .timeline-dot {
            position: absolute;
            left: -10px;
            top: 0;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #4CAF50;
            border: 2px solid white;
        }
        
        .timeline-content {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .timeline-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .timeline-status {
            font-weight: bold;
        }
        
        .timeline-date {
            color: #666;
            font-size: 14px;
        }
        
        .timeline-admin {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .timeline-notes {
            color: #333;
            font-size: 15px;
        }
        
        .back-button {
            display: inline-block;
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
        
        .no-history {
            text-align: center;
            padding: 30px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h2>Historia Zamówienia #<?php echo $order_id; ?></h2>
            <a href="admin_orders.php" class="back-button">← Powrót do zamówień</a>
        </div>
        
        <div class="order-info">
            <div class="order-info-header">
                <h3 class="order-title">Zamówienie #<?php echo $order_id; ?></h3>
                <span class="order-status status-<?php echo $order['status']; ?>">
                    <?php echo $statuses[$order['status']] ?? ucfirst($order['status']); ?>
                </span>
            </div>
            
            <div class="order-details">
                <div class="detail-item">
                    <div class="detail-label">Klient:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($order['username']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Kwota:</div>
                    <div class="detail-value"><?php echo number_format($order['total_amount'], 2); ?> PLN</div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Data zamówienia:</div>
                    <div class="detail-value">
                        <?php 
                        if (isset($order['created_at'])) {
                            echo date('d.m.Y H:i', strtotime($order['created_at']));
                        } else {
                            echo "Data nieznana";
                        }
                        ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Metoda dostawy:</div>
                    <div class="detail-value">
                        <?php 
                        switch($order['shipping_method']) {
                            case 'courier': echo 'Kurier'; break;
                            case 'parcel_locker': echo 'Paczkomat InPost'; break;
                            case 'pickup': echo 'Odbiór osobisty'; break;
                            default: echo ucfirst($order['shipping_method']);
                        }
                        ?>
                    </div>
                </div>
                
                <?php if (isset($order['payment_method'])): ?>
                <div class="detail-item">
                    <div class="detail-label">Metoda płatności:</div>
                    <div class="detail-value">
                        <?php 
                        switch($order['payment_method']) {
                            case 'card': echo 'Karta płatnicza'; break;
                            case 'voucher': echo 'Voucher / Kod rabatowy'; break;
                            default: echo ucfirst($order['payment_method']);
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (count($history) > 0): ?>
            <div class="history-timeline">
                <h3>Historia statusów</h3>
                <ul>
                    <?php foreach ($history as $item): ?>
                        <li class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-status status-<?php echo $item['status']; ?>">
                                        <?php echo $statuses[$item['status']] ?? ucfirst($item['status']); ?>
                                    </span>
                                    <span class="timeline-date"><?php echo date('d.m.Y H:i', strtotime($item['created_at'])); ?></span>
                                </div>
                                <div class="timeline-admin">
                                    <?php echo htmlspecialchars($item['admin_name']); ?>
                                </div>
                                <div class="timeline-notes">
                                    <?php echo htmlspecialchars($item['notes']); ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <p class="no-history">Brak historii statusów dla tego zamówienia.</p>
        <?php endif; ?>
    </div>
</body>
</html> 