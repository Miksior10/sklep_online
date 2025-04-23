<?php
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

echo "<h1>Tworzenie użytkownika administratora</h1>";

// Sprawdź czy kolumna role istnieje w tabeli users
try {
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('role', $columns)) {
        echo "<p>Dodawanie kolumny 'role' do tabeli users...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'");
        echo "<p style='color: green;'>Kolumna 'role' została dodana.</p>";
    } else {
        echo "<p style='color: green;'>Kolumna 'role' już istnieje.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Błąd podczas sprawdzania struktury tabeli users: " . $e->getMessage() . "</p>";
}

// Sprawdź czy istnieje użytkownik admin
try {
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p>Użytkownik 'admin' już istnieje (ID: {$admin['id']}).</p>";
        
        // Aktualizuj rolę użytkownika admin
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE username = 'admin'");
        $stmt->execute();
        echo "<p style='color: green;'>Rola użytkownika 'admin' została zaktualizowana na 'admin'.</p>";
    } else {
        // Utwórz użytkownika admin
        echo "<p>Tworzenie użytkownika 'admin'...</p>";
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@example.com', $hashed_password, 'admin']);
        echo "<p style='color: green;'>Użytkownik 'admin' został utworzony z hasłem 'admin123'.</p>";
    }
    
    // Wyświetl dane użytkownika admin
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    echo "<h2>Dane użytkownika admin:</h2>";
    echo "<pre>";
    print_r($admin);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Błąd podczas sprawdzania użytkownika admin: " . $e->getMessage() . "</p>";
}

// Wyświetl link do logowania
echo "<p><a href='login.php'>Przejdź do strony logowania</a></p>";
?> 