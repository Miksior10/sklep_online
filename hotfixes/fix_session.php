<?php
// Usuń wszystkie pliki sesji
$session_path = session_save_path();
if (is_dir($session_path)) {
    $files = glob($session_path . '/sess_*');
    foreach ($files as $file) {
        unlink($file);
    }
    echo "Usunięto wszystkie pliki sesji.<br>";
}

// Usuń ciasteczka sesji
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
    echo "Usunięto ciasteczko sesji.<br>";
}

// Rozpocznij nową sesję
session_start();
session_regenerate_id(true);
echo "Rozpoczęto nową sesję z ID: " . session_id() . "<br>";

// Ustaw dłuższy czas życia sesji
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);
echo "Ustawiono czas życia sesji na 1 dzień.<br>";

// Ustaw rolę administratora w sesji
$_SESSION['user_id'] = 1; // Zakładamy, że ID administratora to 1
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
echo "Ustawiono rolę administratora w sesji.<br>";

// Wyświetl informacje o sesji
echo "<h2>Informacje o sesji:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Dodaj link do panelu administratora
echo "<p><a href='admin_panel.php'>Przejdź do panelu administratora</a></p>";
echo "<p><a href='admin_orders.php'>Przejdź do zarządzania zamówieniami</a></p>";
echo "<p><a href='debug_session.php'>Przejdź do strony diagnostycznej</a></p>";
?> 