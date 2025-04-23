# Dokumentacja Techniczna Systemu Sklepu Internetowego

## Architektura Systemu

### Struktura Katalogów
```
sklep/
├── admin/                    # Panel administratora
│   ├── admin_panel.php      # Panel główny
│   ├── admin_products.php   # Zarządzanie produktami
│   ├── admin_orders.php     # Zarządzanie zamówieniami
│   ├── admin_users.php      # Zarządzanie użytkownikami
│   └── admin_login.php      # Logowanie administratora
├── assets/                  # Zasoby statyczne
│   ├── css/                # Style CSS
│   ├── js/                 # Skrypty JavaScript
│   └── images/             # Obrazy
├── includes/               # Pliki pomocnicze
│   ├── config.php         # Konfiguracja
│   ├── functions.php      # Funkcje pomocnicze
│   └── db.php            # Operacje na bazie danych
└── public/                # Pliki publiczne
    ├── index.php         # Strona główna
    ├── product.php       # Szczegóły produktu
    ├── cart.php         # Koszyk
    └── checkout.php     # Zamówienie
```

## Baza Danych

### Struktura Tabel

#### users
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### products
```sql
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image VARCHAR(255),
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

#### orders
```sql
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## API Endpoints

### Produkty
- GET `/api/products` - Lista produktów
- GET `/api/products/{id}` - Szczegóły produktu
- POST `/api/products` - Dodanie produktu (admin)
- PUT `/api/products/{id}` - Aktualizacja produktu (admin)
- DELETE `/api/products/{id}` - Usunięcie produktu (admin)

### Zamówienia
- GET `/api/orders` - Lista zamówień
- GET `/api/orders/{id}` - Szczegóły zamówienia
- POST `/api/orders` - Utworzenie zamówienia
- PUT `/api/orders/{id}/status` - Aktualizacja statusu (admin)

### Użytkownicy
- POST `/api/auth/register` - Rejestracja
- POST `/api/auth/login` - Logowanie
- GET `/api/users/profile` - Profil użytkownika
- PUT `/api/users/profile` - Aktualizacja profilu

## Bezpieczeństwo

### Walidacja Danych
```php
function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
```

### Ochrona przed SQL Injection
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
```

### Szyfrowanie Haseł
```php
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
```

## Obsługa Błędów

### Logowanie Błędów
```php
function logError($message) {
    $log_file = 'logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message\n", 3, $log_file);
}
```

### Wyjątki
```php
try {
    // Kod
} catch (PDOException $e) {
    logError($e->getMessage());
    // Obsługa błędu
}
```

## Optymalizacja

### Cache
```php
function getCachedData($key, $ttl = 3600) {
    $cache_file = "cache/$key.cache";
    if (file_exists($cache_file) && time() - filemtime($cache_file) < $ttl) {
        return unserialize(file_get_contents($cache_file));
    }
    return false;
}
```

### Zapytania SQL
```php
// Indeksy
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_orders_user ON orders(user_id);
```

## Testy

### Jednostkowe
```php
class ProductTest extends PHPUnit\Framework\TestCase {
    public function testProductCreation() {
        $product = new Product();
        $product->setName("Test Product");
        $this->assertEquals("Test Product", $product->getName());
    }
}
```

### Integracyjne
```php
class OrderProcessTest extends PHPUnit\Framework\TestCase {
    public function testOrderCreation() {
        $order = new Order();
        $order->addProduct(1, 2);
        $this->assertEquals(2, $order->getTotalItems());
    }
}
```

## Deployment

### Wymagania
- PHP 7.4+
- MySQL 5.7+
- Composer
- Node.js (dla assetów)

### Proces
1. Clone repository
2. Install dependencies
3. Configure environment
4. Run migrations
5. Build assets
6. Set permissions

## Monitoring

### Logi
- error.log - Błędy aplikacji
- access.log - Dostępy
- security.log - Zdarzenia bezpieczeństwa

### Metryki
- Czas odpowiedzi
- Użycie CPU
- Użycie pamięci
- Liczba zapytań SQL

## Backup

### Baza danych
```bash
mysqldump -u user -p database > backup.sql
```

### Pliki
```bash
tar -czf backup.tar.gz /path/to/shop
```

## Rozszerzenia

### Pluginy
1. System płatności
2. System dostaw
3. System rabatów
4. Integracja z CRM

### API
- RESTful API
- Webhooks
- SDK dla klientów 