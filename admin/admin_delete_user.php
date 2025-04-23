<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if (isset($_POST['user_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_POST['user_id']]);
        $_SESSION['success'] = "Użytkownik został pomyślnie usunięty.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Błąd podczas usuwania użytkownika: " . $e->getMessage();
    }
}

header('Location: admin_users.php');
exit(); 