<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Klucz szyfrowania (w prawdziwej aplikacji powinien być przechowywany bezpiecznie)
define('ENCRYPTION_KEY', 'twoj_tajny_klucz_szyfrowania_123');

// Funkcja do szyfrowania danych
function encrypt($data) {
    $key = "twoj_tajny_klucz";
    return openssl_encrypt($data, "AES-256-CBC", $key, 0, substr($key, 0, 16));
}

// Funkcja do szyfrowania danych
function encryptData($data) {
    $key = "your-secret-key-here"; // Klucz szyfrowania - zmień na bezpieczny klucz
    $method = "AES-256-CBC";
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
    $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

// Funkcja do deszyfrowania danych
function decryptData($data) {
    $key = "your-secret-key-here"; // Ten sam klucz co w szyfrowaniu
    $method = "AES-256-CBC";
    list($encrypted_data, $iv) = array_pad(explode('::', base64_decode($data), 2), 2, null);
    return openssl_decrypt($encrypted_data, $method, $key, 0, $iv);
}

// Pobieranie produktów z koszyka
$stmt = $pdo->prepare("
    SELECT ci.*, p.name, p.price, p.image_url, p.stock, pc.color, pc.color_name, ci.memory 
    FROM cart_items ci 
    JOIN products p ON ci.product_id = p.id 
    LEFT JOIN product_colors pc ON ci.color = pc.color AND ci.product_id = pc.product_id
    WHERE ci.user_id = ?
    ORDER BY ci.id DESC
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

// Sprawdź czy koszyk nie jest pusty
if (empty($cart_items)) {
    header('Location: cart.php?error=empty_cart');
    exit();
}

// Oblicz sumę
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Koszty dostawy
$shipping_costs = [
    'courier' => 14.99,
    'parcel_locker' => 9.99,
    'pickup' => 0
];

// Metody płatności
$payment_methods = [
    'card' => 'Karta płatnicza',
    'voucher' => 'Voucher/Kod rabatowy'
];

// Obsługa formularza zamówienia
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Sprawdź czy wszystkie wymagane pola są wypełnione
        $required_fields = ['full_name', 'street', 'city', 'postal_code', 'shipping_method', 'payment_method'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Pole " . $field . " jest wymagane");
            }
        }

        // Rozpocznij transakcję
        $pdo->beginTransaction();

        // Dodaj koszt dostawy do sumy
        $shipping_method = $_POST['shipping_method'];
        if (!array_key_exists($shipping_method, $shipping_costs)) {
            throw new Exception("Nieprawidłowa metoda dostawy");
        }
        $shipping_cost = $shipping_costs[$shipping_method];
        $total_with_shipping = $total + $shipping_cost;
        
        // Obsługa vouchera
        $discount = 0;
        $voucher = null;
        if (isset($_POST['voucher_code']) && !empty($_POST['voucher_code'])) {
            $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE code = ? AND is_used = 0 LIMIT 1");
            $stmt->execute([$_POST['voucher_code']]);
            $voucher = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($voucher) {
                $discount = min($voucher['amount'], $total_with_shipping);
                $total_with_shipping -= $discount;
            }
        }

        // Utwórz zamówienie
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, shipping_method, shipping_cost, discount_amount, status, created_at) 
                              VALUES (?, ?, ?, ?, ?, 'new', NOW())");
        if (!$stmt->execute([
            $_SESSION['user_id'],
            $total_with_shipping,
            $shipping_method,
            $shipping_cost,
            $discount
        ])) {
            throw new Exception("Błąd podczas tworzenia zamówienia");
        }
        
        $order_id = $pdo->lastInsertId();

        // Zapisz produkty z zamówienia
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                              VALUES (?, ?, ?, ?)");
        foreach ($cart_items as $item) {
            if (!$stmt->execute([
                $order_id,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            ])) {
                throw new Exception("Błąd podczas zapisywania produktów zamówienia");
            }

            // Aktualizuj stan magazynowy
            $stmt2 = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            if (!$stmt2->execute([$item['quantity'], $item['product_id']])) {
                throw new Exception("Błąd podczas aktualizacji stanu magazynowego");
            }
        }

        // Zapisz adres dostawy
        $stmt = $pdo->prepare("INSERT INTO shipping_addresses 
                              (order_id, full_name, street, city, postal_code, shipping_point, parcel_locker_street, parcel_locker_number) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt->execute([
            $order_id,
            $_POST['full_name'],
            $_POST['street'],
            $_POST['city'],
            $_POST['postal_code'],
            $shipping_method == 'parcel_locker' ? $_POST['parcel_locker_id'] : null,
            $shipping_method == 'parcel_locker' ? $_POST['parcel_locker_street'] : null,
            $shipping_method == 'parcel_locker' ? $_POST['parcel_locker_number'] : null
        ])) {
            throw new Exception("Błąd podczas zapisywania adresu dostawy");
        }

        // Zapisz dane płatności
        if ($_POST['payment_method'] === 'card') {
            // Walidacja danych karty
            if (empty($_POST['card_number']) || empty($_POST['card_expiry']) || 
                empty($_POST['card_cvv']) || empty($_POST['cardholder_name'])) {
                throw new Exception("Wszystkie pola karty płatniczej są wymagane");
            }

            // Podstawowa walidacja numeru karty
            $card_number = preg_replace('/\D/', '', $_POST['card_number']);
            if (strlen($card_number) !== 16) {
                throw new Exception("Nieprawidłowy numer karty");
            }

            // Szyfruj numer karty przed zapisem
            $encrypted_card_number = encryptData($card_number);
            
            // Zapisz zaszyfrowane dane płatności
            $stmt = $pdo->prepare("INSERT INTO payments 
                                  (order_id, payment_method, card_number, card_expiry, cardholder_name, payment_date, amount) 
                                  VALUES (?, 'card', ?, ?, ?, NOW(), ?)");
            if (!$stmt->execute([
                $order_id,
                $encrypted_card_number,
                $_POST['card_expiry'],
                $_POST['cardholder_name'],
                $total_with_shipping
            ])) {
                throw new Exception("Błąd podczas zapisywania danych płatności");
            }
        } elseif ($_POST['payment_method'] === 'voucher' && $voucher) {
            $stmt = $pdo->prepare("INSERT INTO payments 
                                  (order_id, payment_method, payment_date, amount) 
                                  VALUES (?, 'voucher', NOW(), ?)");
            if (!$stmt->execute([$order_id, $total_with_shipping])) {
                throw new Exception("Błąd podczas zapisywania płatności voucherem");
            }

            // Oznacz voucher jako użyty
            $stmt = $pdo->prepare("UPDATE vouchers SET is_used = 1 WHERE id = ?");
            if (!$stmt->execute([$voucher['id']])) {
                throw new Exception("Błąd podczas aktualizacji statusu vouchera");
            }
        } else {
            throw new Exception("Nieprawidłowa metoda płatności");
        }

        // Wyczyść koszyk
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
        if (!$stmt->execute([$_SESSION['user_id']])) {
            throw new Exception("Błąd podczas czyszczenia koszyka");
        }

        // Zatwierdź transakcję
        $pdo->commit();
        
        // Zapisz ID zamówienia w sesji
        $_SESSION['last_order_id'] = $order_id;
        $_SESSION['order_success'] = true;
        
        // Przekieruj do potwierdzenia zamówienia
        header('Location: order_confirmation.php?order_id=' . $order_id);
        exit();

    } catch (Exception $e) {
        // Cofnij transakcję w przypadku błędu
        $pdo->rollBack();
        $error = $e->getMessage();
        error_log("Błąd zamówienia: " . $e->getMessage());
    }
}

// Dodaj wyświetlanie błędów na stronie
if (isset($error)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif;
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zamówienie - Sklep Online</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }

        /* Nawigacja */
        nav {
            background-color: #007bff;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav .logo {
            color: white;
            font-size: 1.4rem;
            font-weight: bold;
            text-decoration: none;
        }

        nav ul {
            list-style: none;
            display: flex;
            align-items: center;
            margin: 0;
            padding: 0;
        }

        nav ul li {
            margin-left: 20px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        nav ul li a:hover {
            background-color: rgba(255,255,255,0.1);
        }

        nav ul li a.active {
            background-color: rgba(255,255,255,0.2);
        }

        /* Główny kontener */
        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }

        /* Sekcje zamówienia */
        .checkout-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .checkout-section h2 {
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        /* Podsumowanie zamówienia */
        .order-summary {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .order-summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-total {
            font-size: 1.2rem;
            font-weight: bold;
            color: #007bff;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #f0f0f0;
        }

        /* Formularze */
        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            width: 100%;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }

        /* Przyciski */
        .btn-primary {
            background-color: #007bff;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        /* Metody dostawy i płatności */
        .delivery-method,
        .payment-method {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .delivery-method:hover,
        .payment-method:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }

        .delivery-method.selected,
        .payment-method.selected {
            border-color: #007bff;
            background-color: #e7f1ff;
        }

        .delivery-method i,
        .payment-method i {
            font-size: 1.5rem;
            margin-right: 15px;
            color: #007bff;
        }

        /* Powiadomienia */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            z-index: 1000;
            transform: translateX(120%);
            transition: transform 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background-color: #28a745;
        }

        .notification.error {
            background-color: #dc3545;
        }

        /* Stopka */
        footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            padding: 20px;
            background-color: white;
            box-shadow: 0 -1px 3px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 0 15px;
            }

            .delivery-method,
            .payment-method {
                flex-direction: column;
                text-align: center;
            }

            .delivery-method i,
            .payment-method i {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }

        .order-item-color {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
            color: #666;
        }

        .order-item-memory {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
            color: #666;
        }

        .order-item-memory i {
            color: #007bff;
        }

        .color-circle {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 1px solid #ddd;
        }

        .order-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .order-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }

        .order-item-details {
            flex-grow: 1;
        }

        .order-item-details h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }

        .order-item-details p {
            margin: 0.25rem 0;
            color: #666;
        }

        .order-total {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #eee;
            text-align: right;
        }

        .order-total h4 {
            margin: 0;
            color: #007bff;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="container">
                <a href="index.php" class="logo">Sklep Online</a>
                <ul>
                    <li><a href="index.php">Strona Główna</a></li>
                    <li><a href="products.php">Produkty</a></li>
                    <li><a href="cart.php">Koszyk</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="orders.php">Historia zamówień</a></li>
                        <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                            <li><a href="admin_panel.php">Panel Admina</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php">Wyloguj się</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Zaloguj się</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>

    <div class="main-container">
        <form method="POST" action="" id="checkout-form">
            <!-- Podsumowanie koszyka -->
            <div class="checkout-section">
                <h2>Podsumowanie zamówienia</h2>
                <div class="order-summary">
                    <h3>Podsumowanie zamówienia</h3>
                    <div class="order-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="order-item">
                                <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'images/default-product.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="order-item-details">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <?php if (!empty($item['color'])): ?>
                                        <div class="order-item-color">
                                            <div class="color-circle" style="background-color: <?php echo htmlspecialchars($item['color']); ?>"></div>
                                            <span>Kolor: <?php echo htmlspecialchars($item['color_name'] ?? 'Nazwa koloru'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($item['memory'])): ?>
                                        <div class="order-item-memory">
                                            <i class="fas fa-memory"></i>
                                            <span>Pamięć: <?php echo ($item['memory'] >= 1024) ? (($item['memory']/1024) . ' TB') : ($item['memory'] . ' GB'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <p>Ilość: <?php echo $item['quantity']; ?></p>
                                    <p>Cena: <?php echo number_format($item['price'], 2); ?> zł</p>
                                    <p>Suma: <?php echo number_format($item['price'] * $item['quantity'], 2); ?> zł</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="order-total">
                        <h4>Suma całkowita: <?php echo number_format($total, 2); ?> zł</h4>
                    </div>
                </div>
            </div>

            <!-- Dane dostawy -->
            <div class="checkout-section">
                <h2>Dane dostawy</h2>
                <div class="form-group">
                    <label for="full_name">Imię i nazwisko</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="street">Ulica i numer</label>
                    <input type="text" id="street" name="street" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="city">Miejscowość</label>
                    <input type="text" id="city" name="city" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="postal_code">Kod pocztowy</label>
                    <input type="text" id="postal_code" name="postal_code" class="form-control" required>
                </div>
            </div>

            <!-- Metoda dostawy -->
            <div class="checkout-section">
                <h2>Metoda dostawy</h2>
                <div class="delivery-methods">
                    <label class="delivery-method">
                        <input type="radio" name="shipping_method" value="courier" required>
                        <i class="fas fa-truck"></i>
                        <div>
                            <strong>Kurier</strong>
                            <p>5-7 dni robocze</p>
                            <span><?php echo number_format($shipping_costs['courier'], 2); ?> zł</span>
                        </div>
                    </label>
                    <label class="delivery-method">
                        <input type="radio" name="shipping_method" value="parcel_locker" required>
                        <i class="fas fa-box"></i>
                        <div>
                            <strong>Paczkomat InPost</strong>
                            <p>1-2 dni robocze</p>
                            <span><?php echo number_format($shipping_costs['parcel_locker'], 2); ?> zł</span>
                        </div>
                    </label>
                    <label class="delivery-method">
                        <input type="radio" name="shipping_method" value="pickup" required>
                        <i class="fas fa-store"></i>
                        <div>
                            <strong>Odbiór osobisty</strong>
                            <p>od ręki</p>
                            <span><?php echo number_format($shipping_costs['pickup'], 2); ?> zł</span>
                        </div>
                    </label>
                </div>

                <!-- Pola dla paczkomatu (widoczne tylko gdy wybrana jest dostawa do paczkomatu) -->
                <div id="parcel-locker-fields" style="display: none;">
                    <div class="form-group">
                        <label for="parcel_locker_street">Ulica paczkomatu</label>
                        <input type="text" id="parcel_locker_street" name="parcel_locker_street" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="parcel_locker_number">Numer paczkomatu</label>
                        <input type="text" id="parcel_locker_number" name="parcel_locker_number" class="form-control">
                    </div>
                </div>
            </div>

            <!-- Metoda płatności -->
            <div class="checkout-section">
                <h2>Metoda płatności</h2>
                <div class="payment-methods">
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="card" required>
                        <i class="fas fa-credit-card"></i>
                        <div>
                            <strong>Karta płatnicza</strong>
                            <p>Visa, Mastercard, American Express</p>
                        </div>
                    </label>
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="voucher" required>
                        <i class="fas fa-ticket-alt"></i>
                        <div>
                            <strong>Voucher / Kod rabatowy</strong>
                            <p>Wykorzystaj posiadany kod rabatowy</p>
                        </div>
                    </label>
                </div>

                <!-- Dane karty (widoczne tylko gdy wybrana jest płatność kartą) -->
                <div id="card-details" style="display: none;">
                    <div class="form-group">
                        <label for="card_number">Numer karty</label>
                        <input type="text" id="card_number" name="card_number" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>
                    <div class="form-group">
                        <label for="card_expiry">Data ważności</label>
                        <input type="text" id="card_expiry" name="card_expiry" class="form-control" pattern="\d{2}/\d{2}" placeholder="MM/RR">
                    </div>
                    <div class="form-group">
                        <label for="card_cvv">CVV</label>
                        <input type="text" id="card_cvv" name="card_cvv" class="form-control" pattern="\d{3}" placeholder="123">
                    </div>
                    <div class="form-group">
                        <label for="cardholder_name">Imię i nazwisko na karcie</label>
                        <input type="text" id="cardholder_name" name="cardholder_name" class="form-control">
                    </div>
                </div>

                <!-- Pole na kod vouchera (widoczne tylko gdy wybrana jest płatność voucherem) -->
                <div class="form-group">
                    <label for="voucher_code">Kod vouchera (4 cyfry)</label>
                    <div class="input-group">
                        <input type="text" id="voucher_code" name="voucher_code" class="form-control" maxlength="4" pattern="\d{4}" placeholder="Wpisz kod vouchera">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary" onclick="verifyVoucher()">Zastosuj voucher</button>
                        </div>
                    </div>
                    <div id="voucher-message"></div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Złóż zamówienie</button>
        </form>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; 2024 Sklep Online</p>
        </div>
    </footer>

    <script>
        // Obsługa wyświetlania/ukrywania pól w zależności od wybranej metody płatności
        document.querySelectorAll('input[name="payment_method"]').forEach(input => {
            input.addEventListener('change', function() {
                document.getElementById('card-details').style.display = 
                    this.value === 'card' ? 'block' : 'none';
                document.getElementById('voucher-details').style.display = 
                    this.value === 'voucher' ? 'block' : 'none';
            });
        });

        // Obsługa wyświetlania/ukrywania pól paczkomatu
        document.querySelectorAll('input[name="shipping_method"]').forEach(input => {
            input.addEventListener('change', function() {
                const parcelLockerFields = document.getElementById('parcel-locker-fields');
                parcelLockerFields.style.display = this.value === 'parcel_locker' ? 'block' : 'none';
                
                const parent = this.closest('.delivery-method');
                document.querySelectorAll('.delivery-method').forEach(method => {
                    method.classList.remove('selected');
                });
                if (this.checked) {
                    parent.classList.add('selected');
                }
            });
        });

        // Obsługa wyboru metody płatności
        document.querySelectorAll('input[name="payment_method"]').forEach(input => {
            input.addEventListener('change', function() {
                const parent = this.closest('.payment-method');
                document.querySelectorAll('.payment-method').forEach(method => {
                    method.classList.remove('selected');
                });
                if (this.checked) {
                    parent.classList.add('selected');
                }
            });
        });

        // Walidacja formularza przed wysłaniem
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            
            if (!paymentMethod) {
                e.preventDefault();
                showNotification('Wybierz metodę płatności', false);
                return;
            }

            if (paymentMethod.value === 'card') {
                const cardNumber = document.getElementById('card_number').value.replace(/\D/g, '');
                const cardExpiry = document.getElementById('card_expiry').value;
                const cardCvv = document.getElementById('card_cvv').value;
                const cardholderName = document.getElementById('cardholder_name').value;

                // Sprawdź czy wszystkie pola są wypełnione
                if (!cardNumber || !cardExpiry || !cardCvv || !cardholderName) {
                    e.preventDefault();
                    showNotification('Wypełnij wszystkie pola karty płatniczej', false);
                    return;
                }

                // Sprawdź numer karty (dokładniejsza walidacja)
                if (!/^\d{16}$/.test(cardNumber)) {
                    e.preventDefault();
                    showNotification('Numer karty musi składać się z 16 cyfr', false);
                    return;
                }

                // Sprawdź datę ważności
                if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(cardExpiry)) {
                    e.preventDefault();
                    showNotification('Nieprawidłowy format daty ważności (MM/RR)', false);
                    return;
                }

                // Sprawdź czy karta nie jest przeterminowana
                const [month, year] = cardExpiry.split('/');
                const expiry = new Date(2000 + parseInt(year), parseInt(month) - 1);
                const now = new Date();
                if (expiry < now) {
                    e.preventDefault();
                    showNotification('Karta płatnicza jest przeterminowana', false);
                    return;
                }

                // Sprawdź CVV
                if (!/^\d{3,4}$/.test(cardCvv)) {
                    e.preventDefault();
                    showNotification('Nieprawidłowy kod CVV', false);
                    return;
                }
            }

            const shippingMethod = document.querySelector('input[name="shipping_method"]:checked');
            
            if (!shippingMethod) {
                e.preventDefault();
                showNotification('Wybierz metodę dostawy', false);
                return;
            }

            if (shippingMethod.value === 'parcel_locker') {
                const parcelLockerStreet = document.getElementById('parcel_locker_street').value.trim();
                const parcelLockerNumber = document.getElementById('parcel_locker_number').value.trim();

                if (!parcelLockerStreet || !parcelLockerNumber) {
                    e.preventDefault();
                    showNotification('Wypełnij wszystkie pola paczkomatu', false);
                    return;
                }
            }
        });

        // Formatowanie numeru karty (dodawanie spacji co 4 cyfry)
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, ''); // Usuń wszystkie nie-cyfry
            if (value.length > 16) {
                value = value.slice(0, 16);
            }
            // Dodaj spacje co 4 cyfry
            const parts = [];
            for (let i = 0; i < value.length; i += 4) {
                parts.push(value.slice(i, i + 4));
            }
            this.value = parts.join(' ');
        });

        // Formatowanie daty ważności
        document.getElementById('card_expiry').addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 4) value = value.slice(0, 4);
            if (value.length > 2) {
                value = value.slice(0, 2) + '/' + value.slice(2);
            }
            this.value = value;
        });

        // Formatowanie CVV
        document.getElementById('card_cvv').addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 4) value = value.slice(0, 4);
            this.value = value;
        });

        function showNotification(message, isSuccess) {
            const messageDiv = document.getElementById('voucher-message');
            messageDiv.innerHTML = `
                <div class="alert alert-${isSuccess ? 'success' : 'danger'} mt-2">
                    ${message}
                </div>
            `;
        }

        function verifyVoucher() {
            const voucherCode = document.getElementById('voucher_code').value.trim();
            const totalAmount = <?php echo isset($total) ? $total : 0; ?>;
            
            if (!voucherCode) {
                showNotification('Wprowadź kod vouchera', false);
                return;
            }

            if (!/^\d{4}$/.test(voucherCode)) {
                showNotification('Kod vouchera musi składać się z 4 cyfr', false);
                return;
            }

            // Wyświetl komunikat o ładowaniu
            showNotification('Sprawdzanie vouchera...', true);

            fetch('verify_voucher.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    code: voucherCode,
                    total_amount: totalAmount
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Aktualizuj wyświetlane ceny tylko jeśli voucher jest prawidłowy
                    const discountElement = document.createElement('div');
                    discountElement.className = 'order-summary-item discount';
                    discountElement.innerHTML = `
                        <span>Rabat (${voucherCode})</span>
                        <span>-${data.discount.toFixed(2)} zł</span>
                    `;

                    // Dodaj informację o rabacie przed sumą
                    const orderSummary = document.querySelector('.order-summary');
                    const totalElement = orderSummary.querySelector('.order-total');
                    
                    // Usuń poprzedni rabat jeśli istnieje
                    const oldDiscount = orderSummary.querySelector('.discount');
                    if (oldDiscount) {
                        oldDiscount.remove();
                    }

                    // Dodaj nowy rabat
                    orderSummary.insertBefore(discountElement, totalElement);

                    // Aktualizuj sumę
                    const shippingCost = <?php echo isset($shipping_costs[$_POST['shipping_method'] ?? 'courier']) ? $shipping_costs[$_POST['shipping_method'] ?? 'courier'] : 0; ?>;
                    const newTotal = data.new_total + shippingCost;
                    totalElement.querySelector('h4').textContent = `Suma całkowita: ${newTotal.toFixed(2)} zł`;

                    // Dodaj ID vouchera do ukrytego pola
                    let voucherIdInput = document.getElementById('voucher_id');
                    if (!voucherIdInput) {
                        voucherIdInput = document.createElement('input');
                        voucherIdInput.type = 'hidden';
                        voucherIdInput.id = 'voucher_id';
                        voucherIdInput.name = 'voucher_id';
                        document.getElementById('checkout-form').appendChild(voucherIdInput);
                    }
                    voucherIdInput.value = data.voucher_id;

                    // Zablokuj pole vouchera i przycisk
                    document.getElementById('voucher_code').disabled = true;
                    document.querySelector('.btn-primary').disabled = true;
                } else {
                    // Jeśli voucher jest nieprawidłowy, usuń rabat jeśli istnieje
                    const oldDiscount = document.querySelector('.discount');
                    if (oldDiscount) {
                        oldDiscount.remove();
                    }

                    // Usuń ukryte pole z ID vouchera jeśli istnieje
                    const voucherIdInput = document.getElementById('voucher_id');
                    if (voucherIdInput) {
                        voucherIdInput.remove();
                    }

                    // Przywróć oryginalną sumę
                    const totalElement = document.querySelector('.order-total h4');
                    const shippingCost = <?php echo isset($shipping_costs[$_POST['shipping_method'] ?? 'courier']) ? $shipping_costs[$_POST['shipping_method'] ?? 'courier'] : 0; ?>;
                    const originalTotal = totalAmount + shippingCost;
                    totalElement.textContent = `Suma całkowita: ${originalTotal.toFixed(2)} zł`;

                    // Odblokuj pole vouchera i przycisk
                    document.getElementById('voucher_code').disabled = false;
                    document.querySelector('.btn-primary').disabled = false;
                }
                
                showNotification(data.message, data.success);
            })
            .catch(error => {
                console.error('Błąd:', error);
                showNotification('Wystąpił błąd podczas weryfikacji vouchera. Spróbuj ponownie.', false);
            });
        }

        // Dodaj obsługę Enter w polu vouchera
        document.getElementById('voucher_code').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                verifyVoucher();
            }
        });
    </script>
</body>
</html> 