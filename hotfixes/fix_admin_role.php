<?php
require_once 'config.php';

// Sprawdź czy kolumna role istnieje w tabeli users
try {
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('role', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'");
        echo "Dodano kolumnę 'role' do tabeli users<br>";
    }
    
    // Pobierz listę użytkowników
    $stmt = $pdo->query("SELECT id, username, role FROM users");
    $users = $stmt->fetchAll();
    
    echo "<h2>Lista użytkowników:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nazwa użytkownika</th><th>Rola</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['username'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Formularz do ustawienia roli administratora
    echo "<h2>Ustaw rolę administratora:</h2>";
    echo "<form method='post' action=''>";
    echo "<select name='user_id'>";
    
    foreach ($users as $user) {
        echo "<option value='" . $user['id'] . "'>" . $user['username'] . " (obecnie: " . $user['role'] . ")</option>";
    }
    
    echo "</select>";
    echo "<button type='submit' name='set_admin'>Ustaw jako administratora</button>";
    echo "</form>";
    
    // Obsługa formularza
    if (isset($_POST['set_admin']) && isset($_POST['user_id'])) {
        $user_id = $_POST['user_id'];
        
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
        $stmt->execute([$user_id]);
        
        echo "<p style='color: green;'>Ustawiono rolę administratora dla użytkownika o ID " . $user_id . "</p>";
        echo "<meta http-equiv='refresh' content='1'>";
    }
    
    // Formularz do logowania jako administrator
    echo "<h2>Zaloguj się jako administrator:</h2>";
    echo "<form method='post' action=''>";
    echo "<select name='admin_id'>";
    
    foreach ($users as $user) {
        if ($user['role'] == 'admin') {
            echo "<option value='" . $user['id'] . "'>" . $user['username'] . "</option>";
        }
    }
    
    echo "</select>";
    echo "<button type='submit' name='login_as_admin'>Zaloguj się</button>";
    echo "</form>";
    
    // Obsługa formularza logowania
    if (isset($_POST['login_as_admin']) && isset($_POST['admin_id'])) {
        $admin_id = $_POST['admin_id'];
        
        $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch();
        
        if ($admin) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['role'] = $admin['role'];
            
            echo "<p style='color: green;'>Zalogowano jako administrator: " . $admin['username'] . "</p>";
            echo "<p><a href='admin_panel.php'>Przejdź do panelu administratora</a></p>";
        } else {
            echo "<p style='color: red;'>Nie znaleziono administratora o podanym ID</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "Błąd: " . $e->getMessage();
}
?> 