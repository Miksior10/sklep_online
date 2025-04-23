<?php
// Pobierz liczbę produktów w koszyku jeśli użytkownik jest zalogowany
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $cart_count = $pdo->query("SELECT SUM(quantity) FROM cart_items WHERE user_id = " . $_SESSION['user_id'])->fetchColumn() ?: 0;
}
?>

<header>
    <nav>
        <div class="logo">Sklep Online</div>
        <ul>
            <li><a href="index.php">Strona główna</a></li>
            <li><a href="products.php">Produkty</a></li>
            <?php if(!isset($_SESSION['user_id'])): ?>
                <li><a href="login.php">Logowanie</a></li>
                <li><a href="register.php">Rejestracja</a></li>
            <?php else: ?>
                <li>
                    <a href="cart.php" class="cart-link">
                        Koszyk
                        <span class="cart-count" <?php echo $cart_count == 0 ? 'style="display:none;"' : ''; ?>>
                            <?php echo $cart_count; ?>
                        </span>
                    </a>
                </li>
                <li><a href="orders.php">Historia zamówień</a></li>
                <li><a href="logout.php">Wyloguj</a></li>
                <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <li><a href="admin_panel.php" class="admin-link">Panel Admina</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </nav>
</header> 