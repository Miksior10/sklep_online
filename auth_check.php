<?php
// Rozpocznij sesję, jeśli jeszcze nie została rozpoczęta
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Funkcja do sprawdzania, czy użytkownik jest zalogowany
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Funkcja do sprawdzania, czy użytkownik ma uprawnienia administratora
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

// Funkcja do sprawdzania, czy użytkownik ma uprawnienia menedżera lub administratora
function is_manager_or_admin() {
    return isset($_SESSION['role']) && ($_SESSION['role'] == 'manager' || $_SESSION['role'] == 'admin');
}

// Funkcja do przekierowania niezalogowanych użytkowników
function redirect_if_not_logged_in() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

// Funkcja do przekierowania użytkowników bez uprawnień administratora
function redirect_if_not_admin() {
    if (!is_admin()) {
        header('Location: index.php');
        exit;
    }
}

// Funkcja do przekierowania użytkowników bez uprawnień menedżera lub administratora
function redirect_if_not_manager_or_admin() {
    if (!is_manager_or_admin()) {
        header('Location: index.php');
        exit;
    }
}
?> 