<?php
// Lista plików, które zostały stworzone do naprawy problemów i mogą być teraz usunięte
$files_to_remove = [
    'fix_orders_table.php',
    'fix_order_queries.php',
    'fix_foreign_key.php',
    'fix_constraint_issue.php',
    'fix_database_direct.php',
    'delete_order_safely.php',
    'check_database.php',
    'fix_foreign_key_problem.php',
    'delete_order_direct.php',
    'fix_constraint_problem.php',
    'delete_order_mysqli.php',
    'clean_database.php',
    'delete_specific_order.php',
    'delete_all_orders.php',
    'manual_delete_order.php',
    'delete_order_function.php',
    'debug_admin.php',
    'fix_admin_user.php'
];

echo "<h1>Czyszczenie projektu</h1>";
echo "<p>Usuwanie niepotrzebnych plików...</p>";

$removed_files = [];
$not_found_files = [];

foreach ($files_to_remove as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            $removed_files[] = $file;
        } else {
            echo "<p>Nie udało się usunąć pliku: $file. Sprawdź uprawnienia.</p>";
        }
    } else {
        $not_found_files[] = $file;
    }
}

echo "<h2>Wynik operacji</h2>";

if (!empty($removed_files)) {
    echo "<p>Usunięto następujące pliki:</p>";
    echo "<ul>";
    foreach ($removed_files as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Nie usunięto żadnych plików.</p>";
}

if (!empty($not_found_files)) {
    echo "<p>Nie znaleziono następujących plików:</p>";
    echo "<ul>";
    foreach ($not_found_files as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
}

// Sprawdź pliki kopii zapasowych
$backup_files = glob("*.bak");
if (!empty($backup_files)) {
    echo "<h2>Pliki kopii zapasowych</h2>";
    echo "<p>Znaleziono następujące pliki kopii zapasowych:</p>";
    echo "<ul>";
    foreach ($backup_files as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
    
    echo "<form method='post'>";
    echo "<input type='hidden' name='remove_backups' value='1'>";
    echo "<button type='submit'>Usuń pliki kopii zapasowych</button>";
    echo "</form>";
}

// Usuń pliki kopii zapasowych, jeśli formularz został przesłany
if (isset($_POST['remove_backups'])) {
    $removed_backups = [];
    
    foreach ($backup_files as $file) {
        if (unlink($file)) {
            $removed_backups[] = $file;
        } else {
            echo "<p>Nie udało się usunąć pliku: $file. Sprawdź uprawnienia.</p>";
        }
    }
    
    if (!empty($removed_backups)) {
        echo "<p>Usunięto następujące pliki kopii zapasowych:</p>";
        echo "<ul>";
        foreach ($removed_backups as $file) {
            echo "<li>$file</li>";
        }
        echo "</ul>";
    }
}

echo "<p>Operacja zakończona. Niepotrzebne pliki zostały usunięte.</p>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        margin: 20px;
    }
    h1, h2 {
        color: #333;
    }
    p {
        margin: 10px 0;
    }
    ul {
        margin: 10px 0;
        padding-left: 20px;
    }
    button {
        padding: 8px 16px;
        background-color: #4CAF50;
        color: white;
        border: none;
        cursor: pointer;
        margin: 10px 0;
    }
    button:hover {
        background-color: #45a049;
    }
</style>

<p>
    <a href="admin_panel.php">Powrót do panelu administracyjnego</a>
</p> 