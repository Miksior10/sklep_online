# Przewodnik Administratora - Panel Administracyjny

## Spis treści
1. [Logowanie do panelu](#logowanie-do-panelu)
2. [Zarządzanie produktami](#zarządzanie-produktami)
3. [Zarządzanie zamówieniami](#zarządzanie-zamówieniami)
4. [Zarządzanie użytkownikami](#zarządzanie-użytkownikami)
5. [Statystyki i raporty](#statystyki-i-raporty)
6. [Ustawienia systemu](#ustawienia-systemu)
7. [Bezpieczeństwo](#bezpieczeństwo)

## Logowanie do panelu

### Dostęp do panelu
1. Wejdź na stronę: `sklep/admin/admin_login.php`
2. Wprowadź dane logowania:
   - Login administratora
   - Hasło
3. Kliknij "Zaloguj"

### Resetowanie hasła
1. Skontaktuj się z głównym administratorem
2. Wykonaj procedurę resetowania hasła
3. Ustaw nowe, silne hasło

## Zarządzanie produktami

### Dodawanie produktu
1. Wejdź w "Produkty" > "Dodaj nowy"
2. Wypełnij formularz:
   - Nazwa produktu
   - Opis
   - Cena
   - Ilość w magazynie
   - Kategoria
   - Zdjęcia
3. Kliknij "Zapisz"

### Edycja produktu
1. Znajdź produkt w liście
2. Kliknij "Edytuj"
3. Zmień potrzebne dane
4. Kliknij "Zapisz zmiany"

### Usuwanie produktu
1. Znajdź produkt w liście
2. Kliknij "Usuń"
3. Potwierdź usunięcie

### Zarządzanie kategoriami
1. Wejdź w "Kategorie"
2. Dodaj/edytuj/usuń kategorie
3. Przypisz produkty do kategorii

## Zarządzanie zamówieniami

### Przegląd zamówień
- Lista wszystkich zamówień
- Filtrowanie według:
  - Statusu
  - Daty
  - Klienta
  - Kwoty

### Szczegóły zamówienia
1. Kliknij numer zamówienia
2. Sprawdź:
   - Dane klienta
   - Listę produktów
   - Adres dostawy
   - Metodę płatności
   - Historię statusów

### Zmiana statusu zamówienia
1. Otwórz szczegóły zamówienia
2. Wybierz nowy status:
   - Nowe
   - Oczekujące
   - W trakcie realizacji
   - Wysłane
   - Dostarczone
   - Anulowane
3. Dodaj notatkę (opcjonalnie)
4. Kliknij "Zapisz"

### Anulowanie zamówienia
1. Otwórz szczegóły zamówienia
2. Kliknij "Anuluj zamówienie"
3. Podaj powód anulowania
4. Potwierdź anulowanie

## Zarządzanie użytkownikami

### Lista użytkowników
- Przegląd wszystkich kont
- Filtrowanie według:
  - Roli
  - Statusu
  - Data rejestracji

### Edycja użytkownika
1. Znajdź użytkownika
2. Kliknij "Edytuj"
3. Zmień dane:
   - Dane osobowe
   - Uprawnienia
   - Status konta
4. Kliknij "Zapisz"

### Blokowanie konta
1. Otwórz profil użytkownika
2. Kliknij "Zablokuj konto"
3. Podaj powód
4. Potwierdź blokadę

## Statystyki i raporty

### Sprzedaż
- Wykresy sprzedaży
- Raporty dzienne/miesięczne
- Analiza trendów

### Produkty
- Najpopularniejsze produkty
- Stan magazynowy
- Produkty wyprzedane

### Użytkownicy
- Aktywność użytkowników
- Nowe rejestracje
- Konwersja zakupowa

### Generowanie raportów
1. Wybierz typ raportu
2. Określ zakres czasowy
3. Wybierz format (PDF/Excel)
4. Kliknij "Generuj"

## Ustawienia systemu

### Podstawowe ustawienia
- Nazwa sklepu
- Adres email
- Waluta
- Strefa czasowa

### Metody płatności
1. Wejdź w "Ustawienia" > "Płatności"
2. Aktywuj/dezaktywuj metody
3. Skonfiguruj parametry

### Metody dostawy
1. Wejdź w "Ustawienia" > "Dostawa"
2. Dodaj nowe metody
3. Ustaw stawki

### Szablony email
- Potwierdzenie zamówienia
- Zmiana statusu
- Newsletter
- Powiadomienia

## Bezpieczeństwo

### Zarządzanie dostępem
- Tworzenie kont administratorów
- Przydzielanie uprawnień
- Logi dostępu

### Kopie zapasowe
1. Wejdź w "Narzędzia" > "Backup"
2. Wybierz zakres:
   - Baza danych
   - Pliki
   - Konfiguracja
3. Kliknij "Utwórz backup"

### Monitorowanie
- Logi systemowe
- Próby włamań
- Nieudane logowania
- Zmiany w systemie

## Rozwiązywanie problemów

### Błędy systemowe
1. Sprawdź logi błędów
2. Zidentyfikuj problem
3. Wykonaj odpowiednie działania

## Aktualizacje systemu

### Sprawdzanie aktualizacji
1. Wejdź w "Narzędzia" > "Aktualizacje"
2. Sprawdź dostępne wersje
3. Przeczytaj changelog

### Proces aktualizacji
1. Utwórz backup
2. Pobierz nową wersję
3. Wykonaj aktualizację
4. Sprawdź poprawność działania 