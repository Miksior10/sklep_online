<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_POST['cart_item_id'])) {
    $cart_item_id = $_POST['cart_item_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND id = ?");
        $stmt->execute([$_SESSION['user_id'], $cart_item_id]);
        
        // Przekieruj z powrotem do koszyka z komunikatem o sukcesie
        header('Location: cart.php?success=removed');
    } catch (PDOException $e) {
        // W przypadku błędu, przekieruj z komunikatem o błędzie
        header('Location: cart.php?error=remove_failed');
    }
} else {
    // Jeśli nie podano ID produktu, przekieruj z komunikatem o błędzie
    header('Location: cart.php?error=invalid_request');
}
exit(); 