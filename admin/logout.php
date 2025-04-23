<?php
// Rozpocznij sesję
session_start();

// Zniszcz wszystkie zmienne sesji
$_SESSION = array();

// Jeśli używane są ciasteczka sesji, usuń je
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Zniszcz sesję
session_destroy();

// Przekieruj do strony głównej
header('Location: ../index.php');
exit;
?> 