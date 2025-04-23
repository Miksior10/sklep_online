<?php
// Rozpocznij sesję
session_start();
require_once '../config.php';

// Połącz z bazą danych
$host = 'localhost';
$db   = 'sklep_online';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Błąd połączenia z bazą danych: " . $e->getMessage());
}

// Sprawdź czy użytkownik jest zalogowany i ma uprawnienia administratora
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

$error = null;
$success = null;

// Statusy zamówień
$statuses = [
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
                $order_id = $_POST['order_id'];
                $new_status = $_POST['status'];
                
                try {
                    $pdo->beginTransaction();
                    
                    // Aktualizuj status zamówienia
                    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                    $stmt->execute([$new_status, $order_id]);
                    
                    // Zapisz historię zmiany statusu (tylko z podstawowymi kolumnami)
                    $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status) VALUES (?, ?)");
                    $stmt->execute([$order_id, $new_status]);
                    
                    $pdo->commit();
                    $success = "Status zamówienia #$order_id został zaktualizowany na: $new_status";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = "Błąd podczas aktualizacji statusu: " . $e->getMessage();
                }
                break;
                
            case 'delete_order':
                $order_id = $_POST['order_id'];
                
                try {
                    $pdo->beginTransaction();
                    
                    // Przed usunięciem, zapisz ostatni status w historii
                    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
                    $stmt->execute([$order_id]);
                    $current_status = $stmt->fetchColumn();
                    
                    // Zapisz historię zmiany statusu (tylko z podstawowymi kolumnami)
                    $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status) VALUES (?, ?)");
                    $stmt->execute([$order_id, $current_status]);
                    
                    // Usuń powiązane rekordy
                    $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order_id]);
                    $pdo->prepare("DELETE FROM shipping_addresses WHERE order_id = ?")->execute([$order_id]);
                    $pdo->prepare("DELETE FROM payments WHERE order_id = ?")->execute([$order_id]);
                    
                    // Sprawdź czy tabela vouchers istnieje przed próbą usunięcia danych
                    try {
                        $pdo->query("SELECT 1 FROM vouchers LIMIT 1");
                        $pdo->prepare("DELETE FROM vouchers WHERE order_id = ?")->execute([$order_id]);
                    } catch (PDOException $e) {
                        // Tabela vouchers nie istnieje, pomijamy usuwanie
                    }
                    
                    // Na końcu usuń samo zamówienie
                    $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$order_id]);
                    
                    $pdo->commit();
                    $success = "Zamówienie #$order_id zostało usunięte. Historia statusów została zachowana.";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = "Błąd podczas usuwania zamówienia: " . $e->getMessage();
                }
                break;
        }
    }
}

// Pobierz listę zamówień
try {
    $stmt = $pdo->query("
        SELECT o.*, u.username, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.id DESC
    ");
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Błąd podczas pobierania listy zamówień: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zamówienia - Panel Administratora</title>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --success: #10b981;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-700: #374151;
            --gray-900: #111827;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f9fafb;
            color: var(--gray-900);
            line-height: 1.5;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--gray-900);
        }

        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        .table-container {
            overflow-x: auto;
            border-radius: 0.75rem;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background: var(--gray-100);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--gray-700);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover td {
            background: var(--gray-100);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-new { background: #e0e7ff; color: #4f46e5; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-shipped { background: #dcfce7; color: #166534; }
        .status-delivered { background: #ede9fe; color: #5b21b6; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-completed { background: #e2e8f0; color: #1e293b; }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-700);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s;
            background: white;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray-700);
        }

        .empty-state p {
            margin-top: 1rem;
            color: var(--gray-500);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .table th, .table td {
                padding: 0.75rem;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Zarządzanie zamówieniami</h1>
            <a href="admin_panel.php" class="btn">Powrót do panelu</a>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="orders-list">
            <h2>Lista zamówień</h2>
            
            <?php if (empty($orders)): ?>
                <p>Brak zamówień w systemie.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Klient</th>
                            <th>Data</th>
                            <th>Kwota</th>
                            <th>Status</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo $order['username']; ?> (<?php echo $order['email']; ?>)</td>
                                <td><?php echo isset($order['created_at']) ? $order['created_at'] : 'Brak daty'; ?></td>
                                <td><?php echo number_format($order['total_amount'], 2); ?> zł</td>
                                <td>
                                    <form method="post" action="" class="inline-form">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <?php foreach ($statuses as $key => $value): ?>
                                                <option value="<?php echo $key; ?>" <?php echo ($order['status'] == $key) ? 'selected' : ''; ?>>
                                                    <?php echo $value; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <a href="admin_order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-small">Szczegóły</a>
                                    
                                    <form method="post" action="" class="inline-form" onsubmit="return confirm('Czy na pewno chcesz usunąć to zamówienie? Ta operacja jest nieodwracalna.');">
                                        <input type="hidden" name="action" value="delete_order">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" class="btn btn-small btn-danger">Usuń</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>