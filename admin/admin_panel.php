<?php
// Rozpocznij sesję
session_start();
require_once '../config.php';

// Sprawdzenie czy użytkownik jest zalogowany jako admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administratora</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: #1a1a1a;
        }

        .admin-header {
            background-color: #ffffff;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .admin-title {
            font-size: 24px;
            color: #1a1a1a;
            margin-bottom: 10px;
        }

        .admin-info {
            color: #666;
            font-size: 14px;
        }

        .dashboard {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .card {
            background: #ffffff;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: #1a1a1a;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .card i {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: #3498db;
        }

        .card h3 {
            font-size: 1.2em;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .card p {
            color: #666;
            font-size: 0.9em;
            line-height: 1.4;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: #ffffff;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-card h4 {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .stat-card .number {
            font-size: 1.8em;
            font-weight: bold;
            color: #3498db;
        }

        .logout-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #e74c3c;
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: background-color 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #c0392b;
        }

        @media (max-width: 768px) {
            .cards-container {
                grid-template-columns: 1fr;
            }
            
            .quick-stats {
                grid-template-columns: 1fr 1fr;
            }
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 20px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #2196F3;
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 2rem;
        }

        .dashboard-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            height: 100%;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .dashboard-card .card-body {
            padding: 1.5rem;
        }

        .dashboard-card i {
            font-size: 2.5rem;
            color: #2196F3;
            margin-bottom: 1rem;
        }

        .dashboard-card .card-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .dashboard-card .card-text {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .dashboard-card .btn {
            background-color: #4CAF50;
            border-color: #4CAF50;
            padding: 8px 20px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .dashboard-card .btn:hover {
            background-color: #45a049;
            border-color: #45a049;
            transform: translateY(-2px);
        }

        .dashboard-card .fa-star {
            color: #2196F3 !important;
        }

        /* Responsywność */
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .cards-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat-value {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            .cards-container {
                grid-template-columns: 1fr;
            }

            .dashboard-container {
                padding: 10px;
            }

            .dashboard-card {
                margin-bottom: 15px;
            }
        }

        /* Animacje */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dashboard-card {
            animation: fadeIn 0.5s ease-out;
        }

        /* Dodatkowe style dla karty produktów na stronie głównej */
        .dashboard-card.featured-products {
            border-left: 4px solid #2196F3;
        }

        /* Style dla linków w kartach */
        .dashboard-card .card-body {
            color: inherit;
        }

        .dashboard-card:hover .card-title {
            color: #2196F3;
        }
    </style>
</head>
<body>
            <div class="admin-header">
        <h1 class="admin-title">Panel Administratora</h1>
        <p class="admin-info">Zalogowany jako: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            </div>
            
    <div class="dashboard">
        <div class="quick-stats">
            <?php
            // Pobierz statystyki
            $stats = [
                'products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
                'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                'orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
                'total_sales' => $pdo->query("SELECT SUM(total_amount) FROM orders")->fetchColumn()
            ];
            ?>
            <div class="stat-card">
                <h4>Produkty</h4>
                <div class="number"><?php echo $stats['products']; ?></div>
                    </div>
            <div class="stat-card">
                <h4>Użytkownicy</h4>
                <div class="number"><?php echo $stats['users']; ?></div>
                    </div>
            <div class="stat-card">
                <h4>Zamówienia</h4>
                <div class="number"><?php echo $stats['orders']; ?></div>
                    </div>
            <div class="stat-card">
                <h4>Sprzedaż</h4>
                <div class="number"><?php echo number_format($stats['total_sales'], 2); ?> zł</div>
                </div>
            </div>

        <div class="cards-container">
            <a href="admin_products.php" class="card">
                <i class="fas fa-box"></i>
                <h3>Zarządzaj Produktami</h3>
                <p>Dodawaj, edytuj i usuwaj produkty w sklepie</p>
            </a>

            <a href="admin_users.php" class="card">
                <i class="fas fa-users"></i>
                <h3>Zarządzaj Użytkownikami</h3>
                <p>Zarządzaj kontami użytkowników i ich uprawnieniami</p>
            </a>

            <a href="admin_orders.php" class="card">
                <i class="fas fa-shopping-cart"></i>
                <h3>Zarządzaj Zamówieniami</h3>
                <p>Przeglądaj i zarządzaj zamówieniami klientów</p>
            </a>

            <a href="admin_vouchers.php" class="card">
                <i class="fas fa-ticket-alt"></i>
                <h3>Zarządzaj Voucherami</h3>
                <p>Twórz i zarządzaj kodami rabatowymi</p>
            </a>

            <a href="../index.php" class="card">
                <i class="fas fa-store"></i>
                <h3>Przejdź do Sklepu</h3>
                <p>Zobacz sklep z perspektywy klienta</p>
            </a>

            <a href="admin_settings.php" class="card">
                <i class="fas fa-cog"></i>
                <h3>Ustawienia</h3>
                <p>Konfiguruj ustawienia sklepu</p>
            </a>

            <a href="admin_featured_products.php" class="card">
                <i class="fas fa-star"></i>
                <h3>Produkty na Stronie Głównej</h3>
                <p>Wybierz produkty wyświetlane na stronie głównej</p>
            </a>
            
            <a href="../fixes/index.php" class="card">
                <i class="fas fa-tools"></i>
                <h3>Narzędzia naprawcze</h3>
                <p>Naprawy i skrypty konserwacyjne bazy danych</p>
            </a>
        </div>
    </div>

    <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Wyloguj
    </a>
</body>
</html> 