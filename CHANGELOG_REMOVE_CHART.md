# Changelog - Usunięcie wykresu z historii cen + Nowy design tabelki

## Data: 2025-11-05

### Usunięto
- Wykres Chart.js z sekcji historii cen w modalu szczegółów mieszkania
- Rejestrację biblioteki Chart.js (CDN) z `class-assets.php`
- Kod renderujący wykres w `apartments-list.js`
- Zmienną `priceHistoryChart`
- Element `<canvas>` z szablonu `apartments-list.php`
- Parametr `chart` ze shortcode `[develogic_price_history]`

### Dodano
- **Nowoczesną tabelkę** z historią cen (zamiast prostej listy)
  - Nagłówki: "Data" i "Cena"
  - Zaokrąglone rogi (border-radius: 8px)
  - Delikatne obramowanie (#e5e5e5)
  - Hover effect na wierszach
  - Zebra striping (delikatne linie między wierszami)
- **Loader animowany** podczas ładowania historii cen
  - Małe kółko obracające się (24px × 24px)
  - Animacja `spin` CSS
  - Wyświetla się w miejscu tabelki
- **Nowe style CSS** w `apartments-list.css`:
  - `.detail-price-history` - kontener sekcji
  - `.price-history-table` - nowoczesna tabelka
  - `.price-history-loader` - animowany loader
  - `.price-history-empty` - komunikat o braku danych

### Zmieniono
- **HTML template** (`apartments-list.php`):
  - Lista `<div class="price-history-list">` → tabelka `<table class="price-history-table">`
  - Dodano `<thead>` z nagłówkami
  - `<tbody>` dla wierszy danych
  - Dodano element loadera
- **JavaScript** (`apartments-list.js`):
  - Funkcja `loadPriceHistory()` generuje teraz wiersze `<tr>` zamiast `<div>`
  - Obsługa pokazywania/ukrywania loadera
  - Obsługa pokazywania/ukrywania tabelki
  - Klasy CSS: `.date-cell` i `.value-cell` dla komórek

### Zachowano
- Listę historii cen (ostatnie 6 wpisów) w formacie data + wartość
- Endpoint REST API `/price-history/{id}`
- Shortcode `[develogic_price_history]`
- Funkcję `loadPriceHistory()` - teraz generuje tabelkę zamiast listy
- Funkcje pomocnicze: `isFiniteNumber()`, `pickNumber()`, `formatDateShort()`
- Funkcję `enqueue_price_history_assets()` - bez Chart.js

### Pliki zmodyfikowane
1. `templates/apartments-list.php` - tabelka HTML zamiast listy div-ów
2. `assets/js/apartments-list.js` - generowanie wierszy tabelki + loader
3. `assets/css/apartments-list.css` - **NOWE**: kompletne style dla tabelki i loadera
4. `public/class-assets.php` - usunięto rejestrację Chart.js
5. `public/class-shortcodes.php` - usunięto parametr `chart`

### Wygląd
- **Tabelka**: Czysta, nowoczesna, z delikatnymi liniami
- **Czcionki**: Data (13px, #666), Cena (14px bold, #1a1a1a)
- **Kolory**: Header (#f9f9f9), Hover (#fafafa), Border (#e5e5e5)
- **Loader**: Czarne kółko 24px z animacją obrotu

### Powód zmian
1. Wykres nie był potrzebny - wystarczy prosta lista historii cen
2. Poprzednia lista była nieczytelna - data i cena się zlewały
3. Nowa tabelka jest przejrzysta i profesjonalna
4. Loader informuje użytkownika o ładowaniu danych

