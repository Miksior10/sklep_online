<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowa metoda żądania']);
    exit();
}

// Pobierz dane z żądania
$data = json_decode(file_get_contents('php://input'), true);
$voucher_code = $data['code'] ?? '';
$total_amount = $data['total_amount'] ?? 0;

// Logowanie otrzymanych danych
error_log("Otrzymany kod vouchera: " . $voucher_code);
error_log("Otrzymana kwota: " . $total_amount);

// Sprawdź długość kodu vouchera
if (strlen($voucher_code) !== 4) {
    echo json_encode(['success' => false, 'message' => 'Kod vouchera musi składać się z 4 cyfr']);
    exit();
}

// Sprawdź czy kod zawiera tylko cyfry
if (!ctype_digit($voucher_code)) {
    echo json_encode(['success' => false, 'message' => 'Kod vouchera może zawierać tylko cyfry']);
    exit();
}

try {
    // Sprawdź czy voucher istnieje i nie został użyty
    $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE code = ? AND is_used = 0");
    error_log("SQL Query: SELECT * FROM vouchers WHERE code = " . $voucher_code . " AND is_used = 0");

    // Logowanie przed wykonaniem zapytania
    error_log("Próba wykonania zapytania dla kodu: " . $voucher_code);
    $stmt->execute([$voucher_code]);
    error_log("Zapytanie wykonane");

    // Logowanie wyniku
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Wynik zapytania: " . print_r($voucher, true));

    // Sprawdź strukturę tabeli vouchers
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM vouchers")->fetchAll(PDO::FETCH_ASSOC);
        error_log("Struktura tabeli vouchers: " . print_r($columns, true));
    } catch (PDOException $e) {
        error_log("Błąd podczas sprawdzania struktury tabeli: " . $e->getMessage());
    }

    if (!$voucher) {
        // Sprawdź czy voucher istnieje ale został już użyty
        $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE code = ? AND is_used = 1");
        error_log("Sprawdzanie czy voucher został już użyty: " . $voucher_code);
        $stmt->execute([$voucher_code]);
        $used_voucher = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Wynik sprawdzania użytego vouchera: " . print_r($used_voucher, true));

        if ($used_voucher) {
            echo json_encode([
                'success' => false, 
                'message' => 'Ten voucher został już wykorzystany.'
            ]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Nieprawidłowy kod vouchera']);
            exit();
        }
    }

    // Sprawdź czy voucher ma wystarczającą wartość
    if ($voucher['amount'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Voucher ma zerową wartość']);
        exit();
    }

    // Oblicz wartość rabatu
    $discount = min($voucher['amount'], $total_amount);
    $new_total = $total_amount - $discount;

    // Logowanie obliczonego rabatu
    error_log("Obliczony rabat: " . $discount);
    error_log("Nowa suma: " . $new_total);

    echo json_encode([
        'success' => true,
        'message' => 'Voucher został pomyślnie dodany. Rabat: ' . number_format($discount, 2) . ' PLN',
        'discount' => $discount,
        'new_total' => $new_total,
        'voucher_id' => $voucher['id']
    ]);

} catch (PDOException $e) {
    error_log("Błąd PDO podczas weryfikacji vouchera: " . $e->getMessage());
    error_log("Kod błędu: " . $e->getCode());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Wystąpił błąd podczas weryfikacji vouchera: ' . $e->getMessage()]);
}
?> 