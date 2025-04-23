<?php
// Skrypt indeksowy dla folderu z naprawami
session_start();
require_once '../config.php';

// Sprawdzenie uprawnień
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<div style='color: red; text-align: center; margin: 50px;'>
        <h2>Brak uprawnień</h2>
        <p>Musisz być zalogowany jako administrator, aby zobaczyć tę stronę.</p>
        <a href='../login.php'>Zaloguj się</a>
    </div>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skrypty naprawcze</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        h1, h2 {
            color: #333;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .script-list {
            list-style-type: none;
            padding: 0;
        }
        .script-list li {
            margin-bottom: 10px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #17a2b8;
        }
        .script-list a {
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }
        .script-list p {
            margin: 5px 0 0 0;
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Skrypty naprawcze i konserwacyjne</h1>
        <p>Wybierz skrypt, który chcesz uruchomić:</p>
        
        <ul class="script-list">
            <li>
                <a href="run_all_fixes.php">Uruchom wszystkie naprawy</a>
                <p>Tworzy i naprawia wszystkie wymagane tabele w jednym kroku</p>
            </li>
            <li>
                <a href="create_settings_table.php">Tabela ustawień</a>
                <p>Tworzy tabelę settings i dodaje podstawowe ustawienia</p>
            </li>
            <li>
                <a href="create_featured_products_table.php">Tabela produktów wyróżnionych</a>
                <p>Tworzy tabelę featured_products do zarządzania produktami na stronie głównej</p>
            </li>
            <li>
                <a href="create_vouchers_table.php">Tabela voucherów</a>
                <p>Tworzy tabelę vouchers do obsługi kodów rabatowych</p>
            </li>
        </ul>
        
        <h2>Szybkie naprawy (hotfixes)</h2>
        <p>Skrypty do naprawy konkretnych problemów:</p>
        
        <ul class="script-list">
            <?php
            $hotfixesDir = '../hotfixes/';
            $hotfixes = glob($hotfixesDir . 'fix_*.php');
            $hotfixesDb = glob($hotfixesDir . 'add_*.php');
            $allHotfixes = array_merge($hotfixes, $hotfixesDb);
            
            if (empty($allHotfixes)) {
                echo '<li><div class="info" style="margin-left: 0;">Brak dostępnych szybkich napraw</div></li>';
            } else {
                foreach ($allHotfixes as $hotfix) {
                    $filename = basename($hotfix);
                    $name = str_replace(['fix_', 'add_', '.php'], ['', '', ''], $filename);
                    $name = ucwords(str_replace('_', ' ', $name));
                    
                    echo '<li>';
                    echo '<a href="../hotfixes/' . $filename . '">' . $name . '</a>';
                    echo '<p>Szybka naprawa: ' . $name . '</p>';
                    echo '</li>';
                }
            }
            ?>
        </ul>
        
        <h2>Narzędzia (utilities)</h2>
        <p>Pomocnicze narzędzia administracyjne:</p>
        
        <ul class="script-list">
            <?php
            $utilitiesDir = '../utilities/';
            $utilities = glob($utilitiesDir . '*.php');
            
            if (empty($utilities)) {
                echo '<li><div class="info" style="margin-left: 0;">Brak dostępnych narzędzi</div></li>';
            } else {
                foreach ($utilities as $utility) {
                    $filename = basename($utility);
                    $name = str_replace('.php', '', $filename);
                    $name = ucwords(str_replace('_', ' ', $name));
                    
                    echo '<li>';
                    echo '<a href="../utilities/' . $filename . '">' . $name . '</a>';
                    echo '<p>Narzędzie: ' . $name . '</p>';
                    echo '</li>';
                }
            }
            ?>
        </ul>
        
        <a href="../admin/admin_panel.php" class="btn" style="background-color: #6c757d;">Powrót do panelu admina</a>
    </div>
</body>
</html> 