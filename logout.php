<?php
// Rozpocznij sesję
session_start();

// Usuń wszystkie zmienne sesyjne
$_SESSION = array();

// Usuń ciasteczko sesji
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Zniszcz sesję
session_destroy();

// Przekieruj do strony logowania
header('Location: login.php');
exit;
?> 