# Dokumentacja Systemu Sklepu Internetowego

## Spis treści
1. [Struktura systemu](#struktura-systemu)
2. [Baza danych](#baza-danych)
3. [Panel administratora](#panel-administratora)
4. [Panel użytkownika](#panel-użytkownika)
5. [Koszyk i zamówienia](#koszyk-i-zamówienia)
6. [Bezpieczeństwo](#bezpieczeństwo)
7. [Wymagania systemowe](#wymagania-systemowe)

## Struktura systemu

### Główne katalogi
```
sklep/
├── admin/              # Panel administratora
├── assets/            # Zasoby (obrazy, style, skrypty)
├── includes/          # Pliki pomocnicze
├── docs/              # Dokumentacja
└── public/            # Pliki dostępne publicznie
```

### Pliki główne
- `index.php` - Strona główna sklepu
- `config.php` - Konfiguracja bazy danych i ustawień
- `login.php` - Logowanie użytkowników
- `register.php` - Rejestracja nowych użytkowników
- `cart.php` - Koszyk zakupowy
- `checkout.php` - Proces składania zamówienia

## Baza danych

### Tabele
1. `users` - Użytkownicy systemu
   - id, username, email, password, role, created_at

2. `products` - Produkty
   - id, name, description, price, stock, image, category_id

3. `categories` - Kategorie produktów
   - id, name, description

4. `orders` - Zamówienia
   - id, user_id, total_amount, status, created_at

5. `order_items` - Pozycje zamówienia
   - id, order_id, product_id, quantity, price

6. `order_status_history` - Historia statusów zamówień
   - id, order_id, status, created_at, notes

## Panel administratora

### Funkcjonalności
1. Zarządzanie produktami
   - Dodawanie/edycja/usuwanie produktów
   - Zarządzanie kategoriami
   - Zarządzanie stanem magazynowym

2. Zarządzanie zamówieniami
   - Przeglądanie zamówień
   - Zmiana statusu zamówień
   - Szczegóły zamówień
   - Historia statusów

3. Zarządzanie użytkownikami
   - Lista użytkowników
   - Edycja uprawnień
   - Blokowanie kont

4. Statystyki i raporty
   - Sprzedaż
   - Popularne produkty
   - Aktywność użytkowników

### Pliki panelu administratora
- `admin_panel.php` - Panel główny
- `admin_products.php` - Zarządzanie produktami
- `admin_orders.php` - Zarządzanie zamówieniami
- `admin_users.php` - Zarządzanie użytkownikami
- `admin_order_details.php` - Szczegóły zamówienia

## Panel użytkownika

### Funkcjonalności
1. Konto użytkownika
   - Profil
   - Historia zamówień
   - Zmiana hasła

2. Koszyk zakupowy
   - Dodawanie/usuwanie produktów
   - Aktualizacja ilości
   - Podsumowanie

3. Proces zakupowy
   - Wybór metody płatności
   - Wybór dostawy
   - Potwierdzenie zamówienia

## Koszyk i zamówienia

### Proces składania zamówienia
1. Dodanie produktów do koszyka
2. Weryfikacja stanu magazynowego
3. Wybór metody płatności
4. Wybór metody dostawy
5. Potwierdzenie zamówienia
6. Generowanie faktury

### Statusy zamówień
- Nowe
- Oczekujące
- W trakcie realizacji
- Wysłane
- Dostarczone
- Anulowane
- Zakończone

## Bezpieczeństwo

### Implementowane zabezpieczenia
1. Walidacja danych wejściowych
2. Ochrona przed SQL Injection
3. Szyfrowanie haseł
4. System uprawnień
5. Ochrona przed XSS
6. CSRF Protection

### Wymagane uprawnienia
- Administrator: Pełny dostęp
- Pracownik: Zarządzanie zamówieniami
- Użytkownik: Podstawowe funkcje

## Wymagania systemowe

### Serwer
- PHP 7.4 lub nowszy
- MySQL 5.7 lub nowszy
- Apache/Nginx
- Moduł PDO dla PHP

### Przeglądarka
- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## Instalacja

1. Skopiuj pliki na serwer
2. Utwórz bazę danych
3. Zaimportuj strukturę bazy danych
4. Skonfiguruj plik config.php
5. Utwórz pierwszego administratora

## Konfiguracja

### Plik config.php
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sklep');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
```

### Ustawienia sesji
```php
session_start();
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
```

## Rozwój systemu

### Planowane funkcjonalności
1. System rabatów
2. Integracja z systemami płatności
3. System opinii i ocen
4. Newsletter
5. Panel sprzedawcy

### Wersjonowanie
- v1.0 - Podstawowa funkcjonalność
- v1.1 - Dodanie panelu administratora
- v1.2 - Rozszerzenie zarządzania produktami
- v1.3 - System zamówień i płatności

## Wsparcie

### Kontakt
- Email: support@sklep.pl
- Telefon: +48 123 456 789

### Dokumentacja techniczna
- API Documentation
- Database Schema
- Security Guidelines
- Performance Optimization 