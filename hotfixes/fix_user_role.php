<?php
require_once 'config.php';

// Sprawdź czy kolumna role istnieje w tabeli users
try {
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('role', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'");
        echo "Dodano kolumnę 'role' do tabeli users<br>";
    }
    
    // Sprawdź czy istnieje użytkownik admin
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        // Aktualizuj rolę użytkownika admin
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE username = 'admin'");
        $stmt->execute();
        echo "Zaktualizowano rolę użytkownika 'admin' na 'admin'<br>";
    }
    
    // Wyświetl listę użytkowników i ich role
    $stmt = $pdo->query("SELECT id, username, role FROM users");
    $users = $stmt->fetchAll();
    
    echo "<h2>Lista użytkowników i ich role:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nazwa użytkownika</th><th>Rola</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "Błąd: " . $e->getMessage();
}

// Formularz do aktualizacji roli użytkownika
echo "<h2>Aktualizuj rolę użytkownika:</h2>";
echo "<form method='post' action=''>";
echo "<select name='user_id'>";

foreach ($users as $user) {
    echo "<option value='{$user['id']}'>{$user['username']} (obecnie: {$user['role']})</option>";
}

echo "</select>";
echo "<select name='role'>";
echo "<option value='user'>user</option>";
echo "<option value='manager'>manager</option>";
echo "<option value='admin'>admin</option>";
echo "</select>";
echo "<button type='submit'>Aktualizuj rolę</button>";
echo "</form>";

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id']) && isset($_POST['role'])) {
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $user_id]);
        
        echo "<p style='color: green;'>Zaktualizowano rolę użytkownika!</p>";
        echo "<meta http-equiv='refresh' content='1'>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Błąd: " . $e->getMessage() . "</p>";
    }
}
?> 