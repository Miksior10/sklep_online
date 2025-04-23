<?php
// Włącz wyświetlanie błędów
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Rozpocznij sesję
session_start();
require_once '../config.php';

// Sprawdź czy użytkownik jest zalogowany i ma uprawnienia administratora
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: admin_login.php');
    exit;
}

// Sprawdź czy podano ID zamówienia
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin_orders.php');
    exit;
}

$order_id = $_GET['id'];
$error = null;
$success = null;

// Statusy zamówień
$statuses = [
    'new' => 'Nowe',
    'pending' => 'Oczekujące',
    'processing' => 'W trakcie realizacji',
    'shipped' => 'Wysłane',
    'delivered' => 'Dostarczone',
    'cancelled' => 'Anulowane',
    'completed' => 'Zakończone'
];

// Obsługa akcji
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $new_status = $_POST['status'];
                $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
                
                try {
                    $pdo->beginTransaction();
                    
                    // Aktualizuj status zamówienia
                    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                    $stmt->execute([$new_status, $order_id]);
                    
                    // Sprawdź czy tabela order_status_history istnieje
                    $tableExists = false;
                    try {
                        $pdo->query("SELECT 1 FROM order_status_history LIMIT 1");
                        $tableExists = true;
                    } catch (PDOException $e) {
                        // Tabela nie istnieje
                    }
                    
                    if ($tableExists) {
                        // Sprawdź czy kolumna notes istnieje w tabeli order_status_history
                        $hasNotesColumn = false;
                        $columns = $pdo->query("SHOW COLUMNS FROM order_status_history")->fetchAll(PDO::FETCH_COLUMN);
                        if (in_array('notes', $columns)) {
                            $hasNotesColumn = true;
                        }
                        
                        // Zapisz historię zmiany statusu
                        if ($hasNotesColumn) {
                            $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, notes) VALUES (?, ?, ?)");
                            $stmt->execute([$order_id, $new_status, $notes]);
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status) VALUES (?, ?)");
                            $stmt->execute([$order_id, $new_status]);
                        }
                    }
                    
                    $pdo->commit();
                    $success = "Status zamówienia został zaktualizowany na: " . $statuses[$new_status];
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = "Błąd podczas aktualizacji statusu: " . $e->getMessage();
                }
                break;
                
            case 'delete_order':
                try {
                    $pdo->beginTransaction();
                    
                    // Przed usunięciem, zapisz ostatni status w historii
                    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
                    $stmt->execute([$order_id]);
                    $current_status = $stmt->fetchColumn();
                    
                    // Sprawdź czy tabela order_status_history istnieje
                    $tableExists = false;
                    try {
                        $pdo->query("SELECT 1 FROM order_status_history LIMIT 1");
                        $tableExists = true;
                    } catch (PDOException $e) {
                        // Tabela nie istnieje
                    }
                    
                    if ($tableExists) {
                        // Sprawdź czy kolumna notes istnieje w tabeli order_status_history
                        $hasNotesColumn = false;
                        $columns = $pdo->query("SHOW COLUMNS FROM order_status_history")->fetchAll(PDO::FETCH_COLUMN);
                        if (in_array('notes', $columns)) {
                            $hasNotesColumn = true;
                        }
                        
                        // Zapisz historię zmiany statusu
                        if ($hasNotesColumn) {
                            $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, notes) VALUES (?, ?, ?)");
                            $stmt->execute([$order_id, $current_status, 'Zamówienie usunięte']);
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status) VALUES (?, ?)");
                            $stmt->execute([$order_id, $current_status]);
                        }
                    }
                    
                    // Usuń powiązane rekordy
                    $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order_id]);
                    
                    // Sprawdź czy tabela shipping_addresses istnieje
                    try {
                        $pdo->query("SELECT 1 FROM shipping_addresses LIMIT 1");
                        $pdo->prepare("DELETE FROM shipping_addresses WHERE order_id = ?")->execute([$order_id]);
                    } catch (PDOException $e) {
                        // Tabela nie istnieje
                    }
                    
                    // Sprawdź czy tabela payments istnieje
                    try {
                        $pdo->query("SELECT 1 FROM payments LIMIT 1");
                        $pdo->prepare("DELETE FROM payments WHERE order_id = ?")->execute([$order_id]);
                    } catch (PDOException $e) {
                        // Tabela nie istnieje
                    }
                    
                    // Sprawdź czy tabela vouchers istnieje
                    try {
                        $pdo->query("SELECT 1 FROM vouchers LIMIT 1");
                        $pdo->prepare("DELETE FROM vouchers WHERE order_id = ?")->execute([$order_id]);
                    } catch (PDOException $e) {
                        // Tabela nie istnieje
                    }
                    
                    // Na końcu usuń samo zamówienie
                    $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$order_id]);
                    
                    $pdo->commit();
                    
                    // Przekieruj do listy zamówień
                    header('Location: admin_orders.php?success=Zamówienie zostało usunięte');
                    exit;
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = "Błąd podczas usuwania zamówienia: " . $e->getMessage();
                }
                break;
        }
    }
}

// Pobierz dane zamówienia
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.username, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        header('Location: admin_orders.php?error=Zamówienie nie istnieje');
        exit;
    }
    
    // Pobierz elementy zamówienia
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.price as unit_price 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();
    
    // Pobierz adres wysyłki
    $shipping_address = null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM shipping_addresses WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $shipping_address = $stmt->fetch();
    } catch (PDOException $e) {
        // Tabela shipping_addresses może nie istnieć
    }
    
    // Pobierz dane płatności
    $payment = null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $payment = $stmt->fetch();
    } catch (PDOException $e) {
        // Tabela payments może nie istnieć
    }
    
    // Pobierz dane vouchera
    $voucher = null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $voucher = $stmt->fetch();
    } catch (PDOException $e) {
        // Tabela vouchers może nie istnieć
    }
    
    // Pobierz historię statusów
    $status_history = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at DESC");
        $stmt->execute([$order_id]);
        $status_history = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Tabela order_status_history może nie istnieć
    }
    
} catch (PDOException $e) {
    $error = "Błąd podczas pobierania danych zamówienia: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szczegóły zamówienia #<?php echo $order_id; ?> - Panel Administratora</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .card h2 {
            color: #333;
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .info-item h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 0.9em;
        }
        .info-item p {
            margin: 0;
            font-size: 1.1em;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-processing { background: #17a2b8; color: #fff; }
        .status-shipped { background: #28a745; color: #fff; }
        .status-delivered { background: #6f42c1; color: #fff; }
        .status-cancelled { background: #dc3545; color: #fff; }
        .status-completed { background: #6c757d; color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Szczegóły zamówienia #<?php echo $order_id; ?></h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Informacje o zamówieniu</h2>
            <div class="info-grid">
                <div class="info-item">
                    <h3>Status</h3>
                    <p>
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo $statuses[$order['status']]; ?>
                        </span>
                    </p>
                </div>
                <div class="info-item">
                    <h3>Data zamówienia</h3>
                    <p><?php echo date('Y-m-d H:i:s', strtotime($order['created_at'])); ?></p>
                </div>
                <div class="info-item">
                    <h3>Kwota zamówienia</h3>
                    <p><?php echo number_format($order['total_amount'], 2); ?> zł</p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>Dane klienta</h2>
            <div class="info-grid">
                <div class="info-item">
                    <h3>Nazwa użytkownika</h3>
                    <p><?php echo htmlspecialchars($order['username']); ?></p>
                </div>
                <div class="info-item">
                    <h3>Email</h3>
                    <p><?php echo htmlspecialchars($order['email']); ?></p>
                </div>
            </div>
        </div>
        
        <?php if ($shipping_address): ?>
        <div class="card">
            <h2>Adres wysyłki</h2>
            <div class="info-grid">
                <div class="info-item">
                    <h3>Imię i nazwisko</h3>
                    <p><?php echo htmlspecialchars($shipping_address['full_name'] ?? ''); ?></p>
                </div>
                <div class="info-item">
                    <h3>Adres</h3>
                    <p>
                        <?php echo htmlspecialchars($shipping_address['address_line1'] ?? ''); ?><br>
                        <?php if (!empty($shipping_address['address_line2'])): ?>
                            <?php echo htmlspecialchars($shipping_address['address_line2']); ?><br>
                        <?php endif; ?>
                        <?php echo htmlspecialchars(($shipping_address['postal_code'] ?? '') . ' ' . ($shipping_address['city'] ?? '')); ?><br>
                        <?php echo htmlspecialchars($shipping_address['country'] ?? ''); ?>
                    </p>
                </div>
                <div class="info-item">
                    <h3>Telefon</h3>
                    <p><?php echo htmlspecialchars($shipping_address['phone'] ?? ''); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Produkty w zamówieniu</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Produkt</th>
                        <th>Cena jednostkowa</th>
                        <th>Ilość</th>
                        <th>Suma</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo number_format($item['unit_price'], 2); ?> zł</td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo number_format($item['price'], 2); ?> zł</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>Razem:</strong></td>
                        <td><strong><?php echo number_format($order['total_amount'], 2); ?> zł</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <?php if ($payment): ?>
        <div class="card">
            <h2>Informacje o płatności</h2>
            <div class="info-grid">
                <div class="info-item">
                    <h3>Metoda płatności</h3>
                    <p><?php echo htmlspecialchars($payment['payment_method'] ?? ''); ?></p>
                </div>
                <div class="info-item">
                    <h3>Status płatności</h3>
                    <p><?php echo htmlspecialchars($payment['status'] ?? ''); ?></p>
                </div>
                <div class="info-item">
                    <h3>Data płatności</h3>
                    <p><?php echo isset($payment['created_at']) ? date('Y-m-d H:i:s', strtotime($payment['created_at'])) : 'Brak daty'; ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Aktualizacja statusu</h2>
            <form method="post" action="">
                <input type="hidden" name="action" value="update_status">
                <div class="form-group">
                    <label for="status">Nowy status:</label>
                    <select name="status" id="status" class="form-control">
                        <?php foreach ($statuses as $key => $value): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($order['status'] == $key) ? 'selected' : ''; ?>>
                                <?php echo $value; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="notes">Notatki (opcjonalnie):</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn">Aktualizuj status</button>
            </form>
        </div>
        
        <?php if (!empty($status_history)): ?>
        <div class="card">
            <h2>Historia statusów</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Notatki</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($status_history as $history): ?>
                    <tr>
                        <td><?php echo isset($history['created_at']) ? date('Y-m-d H:i:s', strtotime($history['created_at'])) : 'Brak daty'; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $history['status']; ?>">
                                <?php echo $statuses[$history['status']]; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($history['notes'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Akcje</h2>
            <form method="post" action="" onsubmit="return confirm('Czy na pewno chcesz usunąć to zamówienie?');">
                <input type="hidden" name="action" value="delete_order">
                <button type="submit" class="btn btn-danger">Usuń zamówienie</button>
            </form>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="admin_orders.php" class="btn">Powrót do listy zamówień</a>
        </div>
    </div>
</body>
</html> 