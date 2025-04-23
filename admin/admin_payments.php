<?php
session_start();
require_once '../config.php';

// Sprawdzenie czy użytkownik jest adminem
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: admin_login.php");
    exit();
}

// Funkcja do deszyfrowania danych
function decrypt($data) {
    $key = "twoj_tajny_klucz";
    $parts = explode('::', base64_decode($data));
    if (count($parts) === 2) {
        list($encrypted_data, $iv) = $parts;
        return openssl_decrypt($encrypted_data, "AES-256-CBC", $key, 0, $iv);
    }
    return "Błąd deszyfrowania";
}

// Pobierz wszystkie płatności
$stmt = $pdo->prepare("
    SELECT p.*, o.total_amount, u.username
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    JOIN users u ON o.user_id = u.id
    ORDER BY p.payment_date DESC
");
$stmt->execute();
$payments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie Płatnościami - Panel Administracyjny</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .admin-logout {
            padding: 8px 16px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .payments-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .payments-table th, .payments-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .payments-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .payments-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .payment-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h2>Zarządzanie Płatnościami</h2>
            <a href="logout.php" class="admin-logout">Wyloguj</a>
        </div>
        
        <h3>Lista płatności</h3>
        
        <?php if (count($payments) > 0): ?>
            <table class="payments-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Zamówienie</th>
                        <th>Użytkownik</th>
                        <th>Numer karty</th>
                        <th>Posiadacz karty</th>
                        <th>Data ważności</th>
                        <th>Kwota</th>
                        <th>Status</th>
                        <th>Data płatności</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo $payment['id']; ?></td>
                            <td><a href="admin_order_details.php?id=<?php echo $payment['order_id']; ?>">#<?php echo $payment['order_id']; ?></a></td>
                            <td><?php echo htmlspecialchars($payment['username']); ?></td>
                            <td>
                                <?php 
                                    $decrypted = decrypt($payment['card_number']);
                                    // Pokaż tylko ostatnie 4 cyfry
                                    echo '****-****-****-' . substr($decrypted, -4);
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($payment['cardholder_name']); ?></td>
                            <td><?php echo htmlspecialchars($payment['card_expiry']); ?></td>
                            <td><?php echo number_format($payment['total_amount'], 2); ?> PLN</td>
                            <td>
                                <span class="payment-status status-<?php echo $payment['status']; ?>">
                                    <?php 
                                        switch($payment['status']) {
                                            case 'completed': echo 'Opłacone'; break;
                                            case 'pending': echo 'Oczekujące'; break;
                                            case 'failed': echo 'Nieudane'; break;
                                            default: echo ucfirst($payment['status']);
                                        }
                                    ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($payment['payment_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Brak płatności w systemie.</p>
        <?php endif; ?>
        
        <a href="../admin_panel.php" class="back-button">← Powrót do panelu administracyjnego</a>
    </div>
</body>
</html> 