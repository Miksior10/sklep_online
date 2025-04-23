<?php
session_start();
require_once '../config.php';

// Sprawdzenie czy użytkownik jest zalogowany jako admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Obsługa dodawania/edycji vouchera
if (isset($_POST['submit_voucher'])) {
    try {
        $code = strtoupper($_POST['code']);
        $amount = $_POST['amount'];
        
        if (isset($_POST['voucher_id'])) {
            // Aktualizacja istniejącego vouchera
            $stmt = $pdo->prepare("UPDATE vouchers SET code = ?, amount = ? WHERE id = ?");
            $stmt->execute([$code, $amount, $_POST['voucher_id']]);
            $_SESSION['success'] = "Voucher został zaktualizowany.";
        } else {
            // Dodawanie nowego vouchera
            $stmt = $pdo->prepare("INSERT INTO vouchers (code, amount, is_used) VALUES (?, ?, 0)");
            $stmt->execute([$code, $amount]);
            $_SESSION['success'] = "Nowy voucher został dodany.";
        }
        header('Location: admin_vouchers.php');
        exit();
    } catch(PDOException $e) {
        $_SESSION['error'] = "Błąd: " . $e->getMessage();
    }
}

// Obsługa usuwania vouchera
if (isset($_POST['delete_voucher'])) {
    $voucher_id = $_POST['voucher_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM vouchers WHERE id = ? AND is_used = 0");
        $stmt->execute([$voucher_id]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Voucher został usunięty.";
        } else {
            $_SESSION['error'] = "Nie można usunąć wykorzystanego vouchera.";
        }
        header('Location: admin_vouchers.php');
        exit();
    } catch(PDOException $e) {
        $_SESSION['error'] = "Błąd podczas usuwania vouchera: " . $e->getMessage();
    }
}

// Pobieranie vouchera do edycji
$voucher_to_edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $voucher_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Pobieranie wszystkich voucherów
$stmt = $pdo->query("SELECT * FROM vouchers ORDER BY created_at DESC");
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel Administracyjny - Vouchery</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
        }
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Zarządzanie Voucherami</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php if ($voucher_to_edit): ?>
                <input type="hidden" name="voucher_id" value="<?php echo $voucher_to_edit['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="code">Kod vouchera:</label>
                <input type="text" id="code" name="code" required 
                       value="<?php echo $voucher_to_edit ? $voucher_to_edit['code'] : ''; ?>">
            </div>

            <div class="form-group">
                <label for="amount">Wartość zniżki (PLN):</label>
                <input type="number" step="0.01" id="amount" name="amount" required 
                       value="<?php echo $voucher_to_edit ? $voucher_to_edit['amount'] : ''; ?>">
            </div>

            <button type="submit" name="submit_voucher" class="btn btn-primary">
                <?php echo $voucher_to_edit ? 'Zaktualizuj' : 'Dodaj'; ?> Voucher
            </button>
            
            <?php if ($voucher_to_edit): ?>
                <a href="admin_vouchers.php" class="btn btn-danger">Anuluj</a>
            <?php endif; ?>
        </form>

        <h2>Lista Voucherów</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kod</th>
                    <th>Wartość (PLN)</th>
                    <th>Status</th>
                    <th>Data utworzenia</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vouchers as $voucher): ?>
                <tr>
                    <td><?php echo htmlspecialchars($voucher['id']); ?></td>
                    <td><?php echo htmlspecialchars($voucher['code']); ?></td>
                    <td><?php echo number_format($voucher['amount'], 2); ?> zł</td>
                    <td>
                        <span class="badge <?php echo $voucher['is_used'] ? 'badge-danger' : 'badge-success'; ?>">
                            <?php echo $voucher['is_used'] ? 'Wykorzystany' : 'Dostępny'; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($voucher['created_at']); ?></td>
                    <td>
                        <?php if (!$voucher['is_used']): ?>
                            <a href="?edit=<?php echo $voucher['id']; ?>" class="btn btn-primary">Edytuj</a>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="voucher_id" value="<?php echo $voucher['id']; ?>">
                                <button type="submit" name="delete_voucher" class="btn btn-danger" 
                                        onclick="return confirm('Czy na pewno chcesz usunąć ten voucher?')">
                                    Usuń
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p style="margin-top: 20px;">
            <a href="../admin/admin_panel.php" class="btn btn-primary">Powrót do panelu admina</a>
        </p>
    </div>
</body>
</html> 