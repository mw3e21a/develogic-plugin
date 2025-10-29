# Struktura projektu - Develogic Integration

## Przegląd katalogów i plików

```
develogic-wp-plugin/
│
├── develogic-integration.php          # Główny plik wtyczki
├── README.md                          # Dokumentacja główna
├── INSTALL.md                         # Instrukcja instalacji
├── EXAMPLE_USAGE.md                   # Przykłady użycia
├── STRUCTURE.md                       # Ten plik
├── LICENSE                            # Licencja MIT
├── .gitignore                         # Ignorowane pliki dla Git
│
├── admin/                             # Panel administracyjny
│   └── class-admin-settings.php       # Strona ustawień w WP-Admin
│
├── includes/                          # Klasy core
│   ├── class-api-client.php           # Klient API Develogic
│   ├── class-cache-manager.php        # Zarządzanie cache (transients)
│   ├── class-data-formatter.php       # Formatowanie danych (ceny, daty, itp.)
│   └── class-filter-sort.php          # Filtrowanie i sortowanie ofert
│
├── public/                            # Część publiczna (frontend)
│   ├── class-shortcodes.php           # Rejestracja i renderowanie shortcodów
│   ├── class-rest-api.php             # REST API endpoints
│   └── class-assets.php               # Zarządzanie JS/CSS
│
├── templates/                         # Szablony PHP
│   ├── a1-layout.php                  # Główny layout A1
│   ├── a1-card.php                    # Karta oferty A1
│   ├── filters.php                    # Panel filtrów
│   ├── local-single.php               # Pojedynczy lokal
│   ├── price-history-chart.php        # Wykres historii cen
│   ├── offers-grid.php                # Widok grid ofert
│   ├── investments-card.php           # Widok inwestycji
│   └── local-types-chip.php           # Widok typów lokali
│
├── assets/                            # Assety (CSS, JS)
│   ├── css/
│   │   └── main.css                   # Style główne
│   └── js/
│       └── main.js                    # JavaScript główny
│
├── languages/                         # Tłumaczenia (i18n)
│   └── (pliki .po/.mo - generowane później)
│
└── examples/                          # Przykłady i snippety
    └── functions-snippets.php         # Snippety dla functions.php motywu
```

## Opis klas i plików

### Główny plik wtyczki

**`develogic-integration.php`**
- Rejestruje wtyczkę w WordPress
- Definiuje stałe (DEVELOGIC_VERSION, DEVELOGIC_PLUGIN_DIR, itp.)
- Inicjalizuje klasy core
- Hooki aktywacji/deaktywacji
- Singleton głównej klasy `Develogic_Integration`

### Admin (Panel administracyjny)

**`admin/class-admin-settings.php`**
- Dodaje menu w WP-Admin: "Develogic"
- Strony: Ustawienia, Cache
- Settings API: rejestracja pól, sekcji, sanityzacja
- Akcja czyszczenia cache
- Wyświetlanie błędów API w notices

### Includes (Klasy core)

**`includes/class-api-client.php`**
- Komunikacja z API Develogic (wp_remote_request)
- Metody:
  - `get_locals($filters)` - lista lokali
  - `get_investments()` - lista inwestycji
  - `get_local_types()` - lista typów lokali
  - `get_price_history($local_id)` - historia cen
  - `get_projection_url($projection_id)` - URL obrazu
- Obsługa błędów i logowanie

**`includes/class-cache-manager.php`**
- Zarządzanie cache przez WordPress Transients API
- Metody get/set dla różnych typów danych
- TTL konfigurowalne per typ
- Czyszczenie cache
- Statystyki cache

**`includes/class-data-formatter.php`**
- Formatowanie cen (PLN, format polski)
- Formatowanie powierzchni (m²)
- Formatowanie pięter (Piwnica, Parter, 1, 2, ...)
- Formatowanie dat
- Parsowanie kierunków świata
- Sanityzacja danych z API

**`includes/class-filter-sort.php`**
- Filtrowanie lokali po kryteriach (building, status, rooms, area, price, itp.)
- Sortowanie lokali (po cenie, powierzchni, piętrze, itp.)
- Pomocnicze metody:
  - `get_unique_values()` - unikalne wartości pola
  - `get_buildings()` - lista budynków
  - `count_by_status()` - liczniki statusów

### Public (Frontend)

**`public/class-shortcodes.php`**
- Rejestracja shortcodów:
  - `[develogic_offers_a1]` - layout A1
  - `[develogic_offers]` - generyczny listing
  - `[develogic_filters]` - panel filtrów
  - `[develogic_local]` - pojedynczy lokal
  - `[develogic_price_history]` - historia cen
  - `[develogic_investments]` - lista inwestycji
  - `[develogic_local_types]` - lista typów lokali
- Pobieranie danych (cache → API)
- Ładowanie szablonów (plugin → theme override)

**`public/class-rest-api.php`**
- REST API namespace: `develogic/v1`
- Endpointy:
  - GET `/offers` - oferty z filtrami
  - GET `/local/{id}` - pojedynczy lokal
  - GET `/price-history/{id}` - historia cen
  - GET `/investments` - inwestycje
  - GET `/local-types` - typy lokali
  - GET `/buildings` - budynki
- Obsługa AJAX requests

**`public/class-assets.php`**
- Rejestracja skryptów i stylów:
  - LightGallery (CDN)
  - Chart.js (CDN)
  - main.css, main.js (lokalne)
- Lokalizacja zmiennych JS (`develogicData`)
- Enqueue według potrzeb (per shortcode)

### Templates (Szablony)

**`templates/a1-layout.php`**
- Główny kontener A1
- Panel wyboru budynków
- Header z licznikami i sortowaniem
- Lista kart ofert
- Przycisk druku
- JavaScript inicjalizujący LightGallery

**`templates/a1-card.php`**
- Pojedyncza karta oferty (6 kolumn)
- Kolumny: meta, szczegóły, obrazy (2x), cena, akcje
- Galeria LightGallery
- Panel informacji w lightbox
- Akcje: mailto, obserwuj

**`templates/filters.php`**
- Formularz filtrów
- Pola: investment, localType, rooms, area, price, floor, search, sort
- JavaScript obsługi filtrowania
- Event `develogic:filter`

**`templates/local-single.php`**
- Pełny widok pojedynczego lokalu
- Galeria, szczegóły, tabela pomieszczeń, atrybuty, pakiety
- Sidebar z ceną i kontaktem
- Opcjonalnie historia cen

**`templates/price-history-chart.php`**
- Wykres Chart.js (line/bar)
- Tabela z historią cen
- Obsługa brakujących danych

**`templates/offers-grid.php`**
- Prosty widok grid (kafelki)
- Mniejsze karty ofert
- Bez A1 complexity

**`templates/investments-card.php`**
- Kafelki inwestycji
- Opcjonalnie linki do ofert

**`templates/local-types-chip.php`**
- Chipy (przyciski) z typami lokali
- Opcjonalnie linki do filtrowania

### Assets

**`assets/css/main.css`**
- Style dla layoutu A1
- Responsywność (desktop, tablet, mobile)
- Style dla buildingów, kart, akcji
- Print styles (@media print)

**`assets/js/main.js`**
- Funkcja "obserwuj" (localStorage)
- AJAX loading ofert
- Notification system
- Helper functions (formatPrice)

### Examples

**`examples/functions-snippets.php`**
- Gotowe snippety do skopiowania do `functions.php` motywu
- Konfiguracja budynków (miniatury, adresy)
- Rozszerzenia (klatka, tagi, PDF, email)
- Tracking (Google Analytics)
- Performance (prefetch, cache)
- Integracje (CF7, WooCommerce)

## Flow danych

### Pobieranie ofert (shortcode → wyświetlenie)

1. Użytkownik dodaje shortcode `[develogic_offers_a1]` na stronie
2. WordPress wywołuje `Develogic_Shortcodes::render_offers_a1()`
3. Shortcode parsuje atrybuty (investment_id, sort_by, itp.)
4. Próba pobrania z cache: `cache_manager->get_locals()`
5. Jeśli brak w cache:
   - Wywołanie API: `api_client->get_locals()`
   - Zapis do cache: `cache_manager->set_locals()`
6. Filtrowanie: `Develogic_Filter_Sort::filter_locals()`
7. Sortowanie: `Develogic_Filter_Sort::sort_locals()`
8. Ładowanie szablonu: `templates/a1-layout.php`
9. W pętli: `templates/a1-card.php` dla każdego lokalu
10. Enqueue assetów: CSS + JS + LightGallery
11. Wyświetlenie HTML

### AJAX filtrowanie (frontend)

1. Użytkownik zmienia filtr lub sortowanie
2. JavaScript wywołuje REST API: `/wp-json/develogic/v1/offers?...`
3. `Develogic_REST_API::get_offers()`
4. Parsowanie parametrów
5. Pobieranie danych (cache → API)
6. Filtrowanie i sortowanie
7. Paginacja
8. Zwrot JSON: `{locals: [...], pagination: {...}, status_counts: {...}}`
9. JavaScript renderuje nowe oferty w DOM

### Cache flow

1. Request danych (locals, investments, itp.)
2. `cache_manager->get('key')`
3. Jeśli istnieje i nie wygasł → return
4. Jeśli brak lub wygasły:
   - `api_client->request()`
   - `cache_manager->set('key', data, TTL)`
5. TTL konfigurowalny w ustawieniach
6. Ręczne czyszczenie: Develogic → Cache → Wyczyść

## Hooki i filtry

### Główne filtry

- `develogic_building_thumbnail` - URL miniatury budynku
- `develogic_building_address` - adres budynku
- `develogic_local_klatka` - klatka lokalu
- `develogic_attribute_whitelist` - lista dozwolonych tagów
- `develogic_pdf_link` - link do PDF
- `develogic_contact_email` - email kontaktowy
- `develogic_email_subject` - temat emaila
- `develogic_local_data` - modyfikacja danych lokalu
- `develogic_sort_locals` - niestandardowe sortowanie

### Główne akcje

- `develogic_before_init` - przed inicjalizacją
- `develogic_init` - po inicjalizacji
- `develogic_after_card_render` - po renderze karty
- `develogic_api_request` - przed requestem API (debug)
- `develogic_api_response` - po response API (debug)
- `wp_cache_flush` - czyszczenie cache WordPress (czyści też Develogic)

## Bezpieczeństwo

- **Sanityzacja**: wszystkie dane z API są sanityzowane (`Develogic_Data_Formatter::sanitize_local()`)
- **Escaping**: wszystkie dane w szablonach są escapowane (`esc_html()`, `esc_url()`, `esc_attr()`)
- **Nonce**: REST API używa WP nonce dla AJAX requests
- **Permissions**: panel admin wymaga `manage_options`
- **API Key**: przechowywany w `wp_options`, nie eksponowany w frontend

## Wydajność

- **Cache**: Transients API z konfigurowalnymi TTL
- **Lazy loading**: assety (CSS/JS) ładowane tylko gdy shortcode użyty
- **CDN**: LightGallery i Chart.js z CDN (nie obciążają serwera)
- **Paginacja**: REST API wspiera paginację (limit 100/strona)
- **Optymalizacja**: filtrowanie i sortowanie na cache'owanych danych (nie przy każdym request do API)

## Responsywność

- **Mobile First**: CSS z breakpointami (768px, 1024px)
- **Flexbox/Grid**: nowoczesne layouty
- **Touch-friendly**: akcje (obserwuj, galeria) optymalizowane dla touch
- **Print styles**: dedykowane style dla wydruku

## Internacjonalizacja (i18n)

- Text domain: `develogic`
- Load path: `languages/`
- Wszystkie stringi wrapped w `__()`, `_e()`, `_n()`
- Gotowe do tłumaczenia na inne języki (plik .pot do wygenerowania)

## Rozszerzalność

- **Theme override**: szablony mogą być nadpisane w motywie (`theme/develogic/*.php`)
- **Hooki**: mnóstwo filtrów i akcji
- **REST API**: dostęp programowy do danych
- **CSS Classes**: semantyczne klasy dla łatwego stylowania
- **Data attributes**: `data-*` dla JavaScript integrations

---

**Aktualizacja:** 2025-10-27  
**Wersja wtyczki:** 1.0.0

