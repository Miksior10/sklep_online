<?php
session_start();
require_once '../config.php';

// Sprawdzenie czy użytkownik jest zalogowany i czy jest administratorem
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Inicjalizacja zmiennych na wypadek błędów
$success_message = '';
$error_message = '';

// Pobieranie aktualnych ustawień
try {
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Błąd podczas pobierania ustawień: " . $e->getMessage();
}

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE settings SET 
            shop_name = ?,
            shop_email = ?,
            contact_phone = ?,
            contact_address = ?,
            footer_text = ?,
            maintenance_mode = ?,
            updated_at = NOW()
            WHERE id = 1
        ");

        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;

        $stmt->execute([
            $_POST['shop_name'],
            $_POST['shop_email'],
            $_POST['contact_phone'],
            $_POST['contact_address'],
            $_POST['footer_text'],
            $maintenance_mode
        ]);

        $success_message = "Ustawienia zostały zaktualizowane pomyślnie!";
        
        // Odświeżenie ustawień po zapisie
        $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        $error_message = "Błąd podczas aktualizacji ustawień: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ustawienia Administratora</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .settings-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .form-group label {
            font-weight: bold;
            color: #333;
        }

        .settings-header {
            border-bottom: 2px solid #4CAF50;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
        }

        .btn-save {
            background-color: #4CAF50;
            border-color: #4CAF50;
            padding: 10px 30px;
        }

        .btn-save:hover {
            background-color: #45a049;
            border-color: #45a049;
        }

        .alert {
            margin-top: 1rem;
        }

        .form-control:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }

        .custom-switch .custom-control-label::before {
            border-color: #4CAF50;
        }

        .custom-switch .custom-control-input:checked ~ .custom-control-label::before {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }
    </style>
</head>
<body class="bg-light">
    <div class="settings-container">
        <div class="settings-header">
            <h2 class="text-center">Ustawienia Sklepu</h2>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="shop_name">Nazwa Sklepu</label>
                <input type="text" class="form-control" id="shop_name" name="shop_name" 
                       value="<?php echo htmlspecialchars($settings['shop_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="shop_email">Email Sklepu</label>
                <input type="email" class="form-control" id="shop_email" name="shop_email" 
                       value="<?php echo htmlspecialchars($settings['shop_email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="contact_phone">Telefon Kontaktowy</label>
                <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                       value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="contact_address">Adres Sklepu</label>
                <textarea class="form-control" id="contact_address" name="contact_address" rows="3"
                          ><?php echo htmlspecialchars($settings['contact_address'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="footer_text">Tekst w Stopce</label>
                <textarea class="form-control" id="footer_text" name="footer_text" rows="2"
                          ><?php echo htmlspecialchars($settings['footer_text'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="maintenance_mode" 
                           name="maintenance_mode" <?php echo ($settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="maintenance_mode">Tryb Konserwacji</label>
                </div>
            </div>

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-save text-white">Zapisz Ustawienia</button>
                <a href="../admin/admin_panel.php" class="btn btn-secondary ml-2">Powrót do Panelu</a>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 