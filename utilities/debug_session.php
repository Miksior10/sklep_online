<?php
// Rozpocznij sesję
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Wyświetl wszystkie informacje o sesji
echo "<h1>Informacje o sesji</h1>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n\n";
echo "SESSION variables:\n";
print_r($_SESSION);
echo "\n\nCOOKIE variables:\n";
print_r($_COOKIE);
echo "\n\nPHP Session Configuration:\n";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "session.name: " . ini_get('session.name') . "\n";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "\n";
echo "session.cookie_path: " . ini_get('session.cookie_path') . "\n";
echo "session.cookie_domain: " . ini_get('session.cookie_domain') . "\n";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
echo "session.use_cookies: " . ini_get('session.use_cookies') . "\n";
echo "session.use_only_cookies: " . ini_get('session.use_only_cookies') . "\n";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "\n";
echo "</pre>";

// Dodaj formularz do ustawienia roli
echo "<h2>Ustaw rolę w sesji</h2>";
echo "<form method='post' action=''>";
echo "<select name='role'>";
echo "<option value='user'>user</option>";
echo "<option value='manager'>manager</option>";
echo "<option value='admin'>admin</option>";
echo "</select>";
echo "<button type='submit'>Ustaw rolę</button>";
echo "</form>";

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['role'])) {
    $_SESSION['role'] = $_POST['role'];
    echo "<p style='color: green;'>Ustawiono rolę: " . $_POST['role'] . "</p>";
    echo "<meta http-equiv='refresh' content='1'>";
}

// Dodaj link do panelu administratora
echo "<p><a href='admin_panel.php'>Przejdź do panelu administratora</a></p>";
echo "<p><a href='admin_orders.php'>Przejdź do zarządzania zamówieniami</a></p>";
echo "<p><a href='login.php'>Przejdź do strony logowania</a></p>";
echo "<p><a href='index.php'>Przejdź do strony głównej</a></p>";
?> 