<?php
session_start();
require_once '../config.php';

// Sprawdź czy istnieje już admin
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admin_count = $stmt->fetchColumn();

    // Jeśli istnieje admin, przekieruj do strony logowania
    if ($admin_count > 0) {
        $_SESSION['error'] = "Administrator już istnieje w systemie.";
        header('Location: login.php');
        exit();
    }
} catch(PDOException $e) {
    die("Błąd połączenia z bazą danych: " . $e->getMessage());
}

// Obsługa formularza rejestracji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $admin_code = trim($_POST['admin_code']);

    $errors = [];

    // Walidacja
    if (empty($username)) {
        $errors[] = "Nazwa użytkownika jest wymagana.";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Podaj prawidłowy adres email.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Hasło musi mieć co najmniej 8 znaków.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Hasła nie są identyczne.";
    }

    // Sprawdź kod administratora (możesz zmienić ten kod na własny)
    if ($admin_code !== "Admin123!@#") {
        $errors[] = "Nieprawidłowy kod administratora.";
    }

    // Jeśli nie ma błędów, dodaj administratora
    if (empty($errors)) {
        try {
            // Sprawdź czy użytkownik lub email już istnieje
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Użytkownik o podanej nazwie lub emailu już istnieje.";
            } else {
                // Dodaj administratora
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
                $stmt->execute([$username, $email, $hashed_password]);

                $_SESSION['success'] = "Administrator został pomyślnie zarejestrowany. Możesz się teraz zalogować.";
                header('Location: login.php');
                exit();
            }
        } catch(PDOException $e) {
            $errors[] = "Błąd podczas rejestracji: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja Administratora</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .error {
            color: #dc3545;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 4px;
        }
        .info {
            color: #0c5460;
            background-color: #d1ecf1;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Rejestracja Administratora</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="info">
            Ta strona jest dostępna tylko przy pierwszej konfiguracji systemu.
            Po utworzeniu konta administratora, strona będzie niedostępna.
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Nazwa użytkownika:</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Hasło:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Potwierdź hasło:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <div class="form-group">
                <label for="admin_code">Kod administratora:</label>
                <input type="password" id="admin_code" name="admin_code" required>
            </div>

            <button type="submit">Zarejestruj</button>
        </form>
        
        <p style="text-align: center; margin-top: 15px;">
            <a href="login.php" style="color: #007bff; text-decoration: none;">Powrót do logowania</a>
        </p>
    </div>
</body>
</html> 