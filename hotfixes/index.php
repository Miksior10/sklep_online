<?php
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
    <title>Szybkie naprawy (hotfixes)</title>
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
            border-left: 4px solid #dc3545;
        }
        .script-list a {
            text-decoration: none;
            color: #dc3545;
            font-weight: bold;
        }
        .script-list p {
            margin: 5px 0 0 0;
            color: #666;
        }
        .info {
            color: #17a2b8;
            padding: 10px;
            background-color: #d1ecf1;
            border-radius: 5px;
            margin: 10px 0;
        }
        .warning {
            color: #856404;
            padding: 10px;
            background-color: #fff3cd;
            border-radius: 5px;
            margin: 10px 0;
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
        <h1>Szybkie naprawy (hotfixes)</h1>
        
        <div class="warning">
            <strong>Uwaga!</strong> Te skrypty są przeznaczone do rozwiązywania konkretnych problemów. 
            Uruchamiaj je tylko wtedy, gdy masz pewność, że są potrzebne lub gdy zostałeś o to poproszony.
        </div>
        
        <p>Dostępne szybkie naprawy:</p>
        
        <ul class="script-list">
            <?php
            $hotfixes = glob('fix_*.php');
            
            if (empty($hotfixes)) {
                echo '<li><div class="info" style="margin-left: 0;">Brak dostępnych szybkich napraw</div></li>';
            } else {
                foreach ($hotfixes as $hotfix) {
                    $filename = basename($hotfix);
                    $name = str_replace(['fix_', '.php'], ['', ''], $filename);
                    $name = ucwords(str_replace('_', ' ', $name));
                    
                    echo '<li>';
                    echo '<a href="' . $filename . '">' . $name . '</a>';
                    echo '<p>Szybka naprawa: ' . $name . '</p>';
                    echo '</li>';
                }
            }
            ?>
        </ul>
        
        <div style="margin-top: 20px;">
            <a href="../fixes/index.php" class="btn">Powrót do głównych narzędzi</a>
            <a href="../admin_panel.php" class="btn" style="background-color: #6c757d;">Powrót do panelu admina</a>
        </div>
    </div>
</body>
</html> 