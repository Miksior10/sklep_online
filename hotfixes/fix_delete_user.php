<?php
require_once 'config.php';

try {
    echo "<h2>Naprawa problemu z usuwaniem użytkowników</h2>";
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Funkcja do bezpiecznego usuwania użytkownika z kaskadowym usunięciem powiązanych danych
    function deleteUser($pdo, $user_id) {
        // Rozpocznij transakcję
        $pdo->beginTransaction();
        
        try {
            // 1. Pobierz wszystkie ID zamówień użytkownika
            $orderIdsStmt = $pdo->prepare("SELECT id FROM orders WHERE user_id = ?");
            $orderIdsStmt->execute([$user_id]);
            $orderIds = $orderIdsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($orderIds)) {
                $orderIdsString = implode(',', $orderIds);
                echo "<p>Znaleziono " . count($orderIds) . " zamówień dla użytkownika ID $user_id.</p>";
                
                // 2. Usuń wpisy z order_status_history
                $stmt = $pdo->prepare("DELETE FROM order_status_history WHERE order_id IN ($orderIdsString)");
                $stmt->execute();
                echo "<p>Usunięto powiązane wpisy z tabeli order_status_history.</p>";
                
                // 3. Usuń wpisy z order_items
                $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id IN ($orderIdsString)");
                $stmt->execute();
                echo "<p>Usunięto powiązane wpisy z tabeli order_items.</p>";
                
                // 4. Usuń wpisy z shipping_addresses
                $stmt = $pdo->prepare("DELETE FROM shipping_addresses WHERE order_id IN ($orderIdsString)");
                $stmt->execute();
                echo "<p>Usunięto powiązane wpisy z tabeli shipping_addresses.</p>";
                
                // 5. Usuń wpisy z payments
                $stmt = $pdo->prepare("DELETE FROM payments WHERE order_id IN ($orderIdsString)");
                $stmt->execute();
                echo "<p>Usunięto powiązane wpisy z tabeli payments.</p>";
                
                // 6. Usuń wpisy z vouchers powiązane z zamówieniami
                $stmt = $pdo->prepare("DELETE FROM vouchers WHERE order_id IN ($orderIdsString)");
                $stmt->execute();
                echo "<p>Usunięto powiązane wpisy z tabeli vouchers.</p>";
            }
            
            // 7. Usuń zamówienia użytkownika
            $stmt = $pdo->prepare("DELETE FROM orders WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $ordersCount = $stmt->rowCount();
            echo "<p>Usunięto $ordersCount zamówień użytkownika.</p>";
            
            // 8. Usuń wouchery przypisane bezpośrednio do użytkownika (jeśli istnieją)
            $stmt = $pdo->prepare("DELETE FROM vouchers WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $vouchersCount = $stmt->rowCount();
            if ($vouchersCount > 0) {
                echo "<p>Usunięto $vouchersCount voucherów użytkownika.</p>";
            }
            
            // 9. Na koniec usuń samego użytkownika
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            // Zatwierdź transakcję
            $pdo->commit();
            
            echo "<p class='success'>Użytkownik ID $user_id został pomyślnie usunięty wraz ze wszystkimi powiązanymi danymi.</p>";
            return true;
            
        } catch (Exception $e) {
            // Wycofaj transakcję w przypadku błędu
            $pdo->rollBack();
            echo "<p class='error'>Błąd podczas usuwania: " . $e->getMessage() . "</p>";
            return false;
        }
    }

    // Tworzenie funkcji w bazie danych (opcjonalnie)
    // Ten krok automatyzuje usuwanie użytkowników w przyszłości
    echo "<h3>Tworzenie procedury do usuwania użytkowników w bazie danych</h3>";
    
    $createProcedure = "
    CREATE PROCEDURE IF NOT EXISTS delete_user(IN user_id INT)
    BEGIN
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            ROLLBACK;
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Wystąpił błąd podczas usuwania użytkownika';
        END;
        
        START TRANSACTION;
        
        -- Pobierz ID zamówień użytkownika
        SET @order_ids = NULL;
        SELECT GROUP_CONCAT(id) INTO @order_ids FROM orders WHERE user_id = user_id;
        
        -- Usuń powiązane wpisy, jeśli istnieją zamówienia
        IF @order_ids IS NOT NULL THEN
            -- Usuń wpisy z order_status_history
            SET @sql = CONCAT('DELETE FROM order_status_history WHERE order_id IN (', @order_ids, ')');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            
            -- Usuń wpisy z order_items
            SET @sql = CONCAT('DELETE FROM order_items WHERE order_id IN (', @order_ids, ')');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            
            -- Usuń wpisy z shipping_addresses
            SET @sql = CONCAT('DELETE FROM shipping_addresses WHERE order_id IN (', @order_ids, ')');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            
            -- Usuń wpisy z payments
            SET @sql = CONCAT('DELETE FROM payments WHERE order_id IN (', @order_ids, ')');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            
            -- Usuń wpisy z vouchers powiązane z zamówieniami
            SET @sql = CONCAT('DELETE FROM vouchers WHERE order_id IN (', @order_ids, ')');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
        
        -- Usuń zamówienia użytkownika
        DELETE FROM orders WHERE user_id = user_id;
        
        -- Usuń vouchery przypisane bezpośrednio do użytkownika
        DELETE FROM vouchers WHERE user_id = user_id;
        
        -- Na koniec usuń samego użytkownika
        DELETE FROM users WHERE id = user_id;
        
        COMMIT;
    END;
    ";
    
    try {
        $pdo->exec($createProcedure);
        echo "<p>Procedura składowana 'delete_user' została pomyślnie utworzona w bazie danych.</p>";
    } catch (PDOException $e) {
        echo "<p class='warning'>Nie można utworzyć procedury: " . $e->getMessage() . "</p>";
        echo "<p>Prawdopodobnie Twój hosting nie pozwala na tworzenie procedur składowanych. To nie jest problem, skrypt PHP będzie działał niezależnie.</p>";
    }
    
    // Napraw istniejącą funkcję usuwania użytkownika w admin_users.php
    echo "<h3>Aktualizacja funkcji usuwania w admin_users.php</h3>";
    
    $admin_users_file = 'admin_users.php';
    if (file_exists($admin_users_file)) {
        $file_content = file_get_contents($admin_users_file);
        
        // Szukaj wzorca istniejącego kodu do usuwania użytkowników
        $pattern = '/if\s*\(\s*isset\s*\(\s*\$_GET\s*\[\s*[\'"]delete[\'"]\s*\]\s*\)\s*\)\s*{.*?}/s';
        
        $replacement = '
        if (isset($_GET[\'delete\'])) {
            $user_id = $_GET[\'delete\'];
            
            // Rozpocznij transakcję
            $pdo->beginTransaction();
            
            try {
                // 1. Pobierz wszystkie ID zamówień użytkownika
                $orderIdsStmt = $pdo->prepare("SELECT id FROM orders WHERE user_id = ?");
                $orderIdsStmt->execute([$user_id]);
                $orderIds = $orderIdsStmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($orderIds)) {
                    $orderIdsString = implode(\',\', $orderIds);
                    
                    // 2. Usuń wpisy z order_status_history
                    $stmt = $pdo->prepare("DELETE FROM order_status_history WHERE order_id IN ($orderIdsString)");
                    $stmt->execute();
                    
                    // 3. Usuń wpisy z order_items
                    $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id IN ($orderIdsString)");
                    $stmt->execute();
                    
                    // 4. Usuń wpisy z shipping_addresses
                    $stmt = $pdo->prepare("DELETE FROM shipping_addresses WHERE order_id IN ($orderIdsString)");
                    $stmt->execute();
                    
                    // 5. Usuń wpisy z payments
                    $stmt = $pdo->prepare("DELETE FROM payments WHERE order_id IN ($orderIdsString)");
                    $stmt->execute();
                    
                    // 6. Usuń wpisy z vouchers powiązane z zamówieniami
                    $stmt = $pdo->prepare("DELETE FROM vouchers WHERE order_id IN ($orderIdsString)");
                    $stmt->execute();
                }
                
                // 7. Usuń zamówienia użytkownika
                $stmt = $pdo->prepare("DELETE FROM orders WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // 8. Usuń vouchery przypisane bezpośrednio do użytkownika (jeśli istnieją)
                $stmt = $pdo->prepare("DELETE FROM vouchers WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // 9. Na koniec usuń samego użytkownika
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                // Zatwierdź transakcję
                $pdo->commit();
                
                header(\'Location: admin_users.php\');
                exit;
                
            } catch (Exception $e) {
                // Wycofaj transakcję w przypadku błędu
                $pdo->rollBack();
                $error = "Błąd podczas usuwania użytkownika: " . $e->getMessage();
            }
        }';
        
        // Spróbuj zastąpić kod
        $new_content = preg_replace($pattern, $replacement, $file_content);
        
        // Sprawdź czy zamiana się powiodła
        if ($new_content != $file_content) {
            // Utwórz kopię zapasową
            file_put_contents($admin_users_file . '.bak', $file_content);
            
            // Zapisz nową zawartość
            file_put_contents($admin_users_file, $new_content);
            echo "<p class='success'>Plik admin_users.php został pomyślnie zaktualizowany.</p>";
        } else {
            echo "<p class='warning'>Nie można automatycznie zaktualizować pliku admin_users.php. Możesz ręcznie zastąpić kod usuwania użytkownika poniższym kodem:</p>";
            echo "<pre>" . htmlspecialchars($replacement) . "</pre>";
        }
    } else {
        echo "<p class='error'>Plik admin_users.php nie istnieje. Upewnij się, że jesteś w głównym katalogu sklepu.</p>";
    }
    
    // Formularz do ręcznego usuwania użytkownika
    echo "<h3>Formularz do ręcznego usunięcia użytkownika</h3>";
    echo "<form method='post' action=''>";
    echo "<div>";
    echo "<label for='user_id'>ID użytkownika do usunięcia:</label>";
    echo "<input type='number' name='user_id' id='user_id' required>";
    echo "</div>";
    echo "<button type='submit' name='delete_user' class='btn'>Usuń użytkownika</button>";
    echo "</form>";
    
    // Lista użytkowników
    echo "<h3>Lista użytkowników:</h3>";
    $users = $pdo->query("SELECT id, username, email, role FROM users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($users)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nazwa użytkownika</th><th>Email</th><th>Rola</th><th>Akcja</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role'] ?? 'user') . "</td>";
            echo "<td><a href='?delete_user=1&user_id=" . $user['id'] . "' class='btn-small' onclick='return confirm(\"Czy na pewno chcesz usunąć tego użytkownika?\")'>Usuń</a></td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Brak użytkowników w bazie danych.</p>";
    }
    
    // Obsługa formularza
    if (isset($_POST['delete_user']) || (isset($_GET['delete_user']) && isset($_GET['user_id']))) {
        $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : $_GET['user_id'];
        
        if (!empty($user_id) && is_numeric($user_id)) {
            // Sprawdź, czy użytkownik istnieje
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo "<h3>Usuwanie użytkownika ID " . htmlspecialchars($user_id) . " (" . htmlspecialchars($user['username']) . ")</h3>";
                deleteUser($pdo, $user_id);
            } else {
                echo "<p class='error'>Nie znaleziono użytkownika o ID " . htmlspecialchars($user_id) . ".</p>";
            }
        } else {
            echo "<p class='error'>Nieprawidłowe ID użytkownika.</p>";
        }
    }
    
    echo "<p><a href='admin_users.php' class='btn'>Powrót do zarządzania użytkownikami</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Błąd bazy danych: " . $e->getMessage() . "</p>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        margin: 20px;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    h2, h3 {
        color: #333;
        margin-top: 20px;
    }
    p {
        margin: 10px 0;
    }
    pre {
        background-color: #f4f4f4;
        padding: 10px;
        border-radius: 5px;
        overflow-x: auto;
    }
    .success {
        color: green;
        font-weight: bold;
    }
    .error {
        color: red;
        font-weight: bold;
    }
    .warning {
        color: orange;
        font-weight: bold;
    }
    table {
        border-collapse: collapse;
        width: 100%;
        margin: 15px 0;
    }
    th {
        background-color: #f2f2f2;
        text-align: left;
    }
    form {
        margin: 20px 0;
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 5px;
    }
    input[type="number"] {
        padding: 8px;
        margin: 5px 0 15px;
        width: 100%;
        max-width: 300px;
    }
    label {
        font-weight: bold;
    }
    .btn {
        display: inline-block;
        padding: 8px 16px;
        background-color: #4CAF50;
        color: white;
        border: none;
        cursor: pointer;
        text-decoration: none;
        border-radius: 4px;
        margin-top: 10px;
    }
    .btn:hover {
        background-color: #45a049;
    }
    .btn-small {
        padding: 4px 8px;
        background-color: #f44336;
        color: white;
        text-decoration: none;
        border-radius: 3px;
        font-size: 0.9em;
    }
    .btn-small:hover {
        background-color: #d32f2f;
    }
</style> 