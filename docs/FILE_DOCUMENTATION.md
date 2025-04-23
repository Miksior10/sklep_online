# Dokumentacja Plików Systemu Sklepu Internetowego

## Struktura Katalogów

```
sklep/
├── admin/                    # Panel administratora
├── assets/                  # Zasoby statyczne
├── includes/               # Pliki pomocnicze
├── docs/                  # Dokumentacja
└── public/               # Pliki dostępne publicznie
```

## Panel Administratora (`admin/`)

### `admin_login.php`
- **Cel**: Logowanie administratorów do panelu
- **Funkcje**:
  - Walidacja danych logowania
  - Ustawianie sesji administratora
  - Przekierowanie do panelu głównego
- **Zależności**: `includes/config.php`, `includes/functions.php`

### `admin_panel.php`
- **Cel**: Główny panel administratora
- **Funkcje**:
  - Wyświetlanie statystyk
  - Szybki dostęp do funkcji
  - Podgląd ostatnich zamówień
- **Zależności**: `includes/config.php`, `includes/db.php`

### `admin_products.php`
- **Cel**: Zarządzanie produktami
- **Funkcje**:
  - Lista produktów
  - Dodawanie/edycja/usuwanie
  - Zarządzanie kategoriami
- **Zależności**: `includes/config.php`, `includes/db.php`

### `admin_orders.php`
- **Cel**: Zarządzanie zamówieniami
- **Funkcje**:
  - Lista zamówień
  - Filtrowanie i wyszukiwanie
  - Zmiana statusów
- **Zależności**: `includes/config.php`, `includes/db.php`

### `admin_users.php`
- **Cel**: Zarządzanie użytkownikami
- **Funkcje**:
  - Lista użytkowników
  - Edycja uprawnień
  - Blokowanie kont
- **Zależności**: `includes/config.php`, `includes/db.php`

### `admin_order_details.php`
- **Cel**: Szczegóły zamówienia
- **Funkcje**:
  - Wyświetlanie danych zamówienia
  - Historia statusów
  - Dane klienta
- **Zależności**: `includes/config.php`, `includes/db.php`

## Zasoby Statyczne (`assets/`)

### `css/`
- **style.css**: Główne style systemu
- **admin.css**: Styles panelu administratora
- **responsive.css**: Styles responsywne

### `js/`
- **main.js**: Główne skrypty systemu
- **admin.js**: Skrypty panelu administratora
- **cart.js**: Obsługa koszyka

### `images/`
- **products/**: Zdjęcia produktów
- **icons/**: Ikony systemu
- **logos/**: Logotypy

## Pliki Pomocnicze (`includes/`)

### `config.php`
- **Cel**: Konfiguracja systemu
- **Zawartość**:
  - Dane do bazy danych
  - Ustawienia sesji
  - Stałe systemowe
  - Konfiguracja maila

### `functions.php`
- **Cel**: Funkcje pomocnicze
- **Funkcje**:
  - Walidacja danych
  - Formatowanie
  - Obsługa sesji
  - Funkcje bezpieczeństwa

### `db.php`
- **Cel**: Operacje na bazie danych
- **Funkcje**:
  - Połączenie z bazą
  - Zapytania SQL
  - Transakcje
  - Obsługa błędów

## Pliki Publiczne (`public/`)

### `index.php`
- **Cel**: Strona główna sklepu
- **Funkcje**:
  - Wyświetlanie produktów
  - Wyszukiwarka
  - Kategorie
  - Promocje

### `product.php`
- **Cel**: Szczegóły produktu
- **Funkcje**:
  - Wyświetlanie informacji
  - Galeria zdjęć
  - Dodawanie do koszyka
  - Opinie

### `cart.php`
- **Cel**: Koszyk zakupowy
- **Funkcje**:
  - Lista produktów
  - Aktualizacja ilości
  - Podsumowanie
  - Kody rabatowe

### `checkout.php`
- **Cel**: Proces zamówienia
- **Funkcje**:
  - Dane osobowe
  - Adres dostawy
  - Metody płatności
  - Potwierdzenie

### `login.php`
- **Cel**: Logowanie użytkowników
- **Funkcje**:
  - Formularz logowania
  - Reset hasła
  - Rejestracja

## Dokumentacja (`docs/`)

### `README.md`
- **Cel**: Ogólna dokumentacja systemu
- **Zawartość**:
  - Opis systemu
  - Instalacja
  - Konfiguracja
  - Wymagania

### `TECHNICAL.md`
- **Cel**: Dokumentacja techniczna
- **Zawartość**:
  - Architektura
  - API
  - Bezpieczeństwo
  - Optymalizacja

### `USER_GUIDE.md`
- **Cel**: Przewodnik użytkownika
- **Zawartość**:
  - Instrukcje użytkowania
  - FAQ
  - Kontakt

### `ADMIN_GUIDE.md`
- **Cel**: Przewodnik administratora
- **Zawartość**:
  - Zarządzanie systemem
  - Funkcje panelu
  - Rozwiązywanie problemów

## Pliki Systemowe

### `.htaccess`
- **Cel**: Konfiguracja serwera Apache
- **Funkcje**:
  - Przekierowania
  - Zabezpieczenia
  - Cache
  - SEO

### `composer.json`
- **Cel**: Zarządzanie zależnościami PHP
- **Zawartość**:
  - Wymagane pakiety
  - Wersje
  - Autoloader

### `package.json`
- **Cel**: Zarządzanie zależnościami JavaScript
- **Zawartość**:
  - Skrypty
  - Zależności
  - Konfiguracja

## Pliki Bazy Danych

### `database.sql`
- **Cel**: Struktura bazy danych
- **Zawartość**:
  - Tabele
  - Relacje
  - Indeksy
  - Dane początkowe

## Pliki Konfiguracyjne

### `config.ini`
- **Cel**: Konfiguracja środowiska
- **Zawartość**:
  - Ustawienia serwera
  - Ścieżki
  - Limity
  - Cache

### `mail.config.php`
- **Cel**: Konfiguracja poczty
- **Zawartość**:
  - SMTP
  - Szablony
  - Nadawca
  - Kopie 