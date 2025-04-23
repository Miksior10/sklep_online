<?php
require_once 'config.php';

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    echo "Status: Niezalogowany<br>";
    echo "Akcja: Przekierowanie do login.php<br>";
    exit;
}

// Sprawdź czy użytkownik ma uprawnienia administratora
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    echo "Status: Brak uprawnień administratora<br>";
    echo "Rola: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'brak') . "<br>";
    echo "Akcja: Przekierowanie do index.php<br>";
    exit;
}

echo "Status: Zalogowany jako administrator<br>";
echo "ID użytkownika: " . $_SESSION['user_id'] . "<br>";
echo "Nazwa użytkownika: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'brak') . "<br>";
echo "Rola: " . $_SESSION['role'] . "<br>";

// Sprawdź w bazie danych
try {
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<br>Dane z bazy danych:<br>";
        echo "ID: " . $user['id'] . "<br>";
        echo "Nazwa użytkownika: " . $user['username'] . "<br>";
        echo "Rola: " . $user['role'] . "<br>";
        
        // Sprawdź czy rola w sesji zgadza się z rolą w bazie danych
        if ($user['role'] != $_SESSION['role']) {
            echo "<br>UWAGA: Rola w sesji (" . $_SESSION['role'] . ") nie zgadza się z rolą w bazie danych (" . $user['role'] . ")!<br>";
            echo "Aktualizuję rolę w sesji...<br>";
            $_SESSION['role'] = $user['role'];
        }
    } else {
        echo "<br>UWAGA: Użytkownik o ID " . $_SESSION['user_id'] . " nie istnieje w bazie danych!<br>";
    }
} catch (PDOException $e) {
    echo "<br>Błąd podczas pobierania danych z bazy: " . $e->getMessage() . "<br>";
}

// Dodaj link do panelu administratora
echo "<br><a href='admin_panel.php'>Przejdź do panelu administratora</a><br>";
echo "<a href='admin_orders.php'>Przejdź do zarządzania zamówieniami</a><br>";
?> 