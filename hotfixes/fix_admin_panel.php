<?php
// Rozpocznij sesję
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Usuń wszystkie zmienne sesyjne
$_SESSION = array();

// Usuń ciasteczko sesji
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Zniszcz sesję
session_destroy();

// Rozpocznij nową sesję
session_start();

// Połącz z bazą danych
require_once 'config.php';

echo "<h1>Naprawa panelu administratora</h1>";

// Sprawdź strukturę tabeli users
echo "<h2>Sprawdzanie struktury tabeli users...</h2>";
try {
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    
    // Sprawdź czy kolumna role istnieje
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
echo "<h2>Sprawdzanie użytkownika admin...</h2>";
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
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
        $stmt->execute(['admin', $hashed_password]);
        echo "<p style='color: green;'>Użytkownik 'admin' został utworzony.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Błąd podczas sprawdzania użytkownika admin: " . $e->getMessage() . "</p>";
}

// Dodaj linki do innych stron
echo "<h2>Linki:</h2>";
echo "<ul>";
echo "<li><a href='admin_panel.php'>Panel administratora</a></li>";
echo "<li><a href='login.php'>Strona logowania</a></li>";
echo "<li><a href='index.php'>Strona główna</a></li>";
echo "</ul>";
?> 