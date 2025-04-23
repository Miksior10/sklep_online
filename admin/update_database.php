<?php
// Plik do aktualizacji bazy danych
session_start();
require_once '../config.php';

// Sprawdź, czy użytkownik jest zalogowany jako admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

// Aktualizacja struktury bazy danych
try {
    // Utwórz tabelę product_memories, jeśli nie istnieje
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS product_memories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            memory_size INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )
    ");
    
    // Dodaj kolumnę memory do tabeli cart_items
    $column_exists = false;
    $columns = $pdo->query("SHOW COLUMNS FROM cart_items")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($columns as $column) {
        if ($column == 'memory') {
            $column_exists = true;
            break;
        }
    }
    
    if (!$column_exists) {
        $pdo->exec("ALTER TABLE cart_items ADD COLUMN memory INT NULL");
        echo '<div class="alert alert-success">Kolumna "memory" została dodana do tabeli cart_items.</div>';
    } else {
        echo '<div class="alert alert-info">Kolumna "memory" już istnieje w tabeli cart_items.</div>';
    }
    
    echo '<div class="alert alert-success">Struktura bazy danych została zaktualizowana pomyślnie.</div>';
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Błąd podczas aktualizacji bazy danych: ' . $e->getMessage() . '</div>';
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktualizacja bazy danych - Panel Administratora</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1>Aktualizacja bazy danych</h1>
        <p>Skrypt zakończył działanie.</p>
        <a href="../admin_panel.php" class="btn btn-primary">Wróć do panelu administracyjnego</a>
    </div>
</body>
</html> 