# Changelog - Develogic Integration

Wszystkie istotne zmiany w projekcie będą dokumentowane w tym pliku.

Format oparty na [Keep a Changelog](https://keepachangelog.com/pl/1.0.0/),
a wersjonowanie zgodne z [Semantic Versioning](https://semver.org/lang/pl/).

## [2.2.0] - 2025-11-18

### Dodano
- **🎨 Integracja z Image Map Pro**: Automatyczna aktualizacja kolorów kształtów na mapach w zależności od statusu lokalu
- **Panel administracyjny Image Map Pro**: Nowa zakładka "Image Map Pro" w menu Develogic do konfiguracji integracji
- **Mapowanie budynków na projekty**: Możliwość przypisania każdego budynku do konkretnego shortcode'u Image Map Pro
- **Konfiguracja kolorów statusów**: Edytor kolorów dla każdego statusu (Wolny, Sprzedany, Rezerwacja, Niedostępny)
- **Automatyczna synchronizacja kolorów**: Po każdej synchronizacji lokali kolory na mapach są automatycznie aktualizowane
- **Manualna aktualizacja**: Możliwość ręcznego uruchomienia aktualizacji kolorów bez pełnej synchronizacji
- **Hook `develogic_sync_completed`**: Nowy action hook wywoływany po zakończeniu synchronizacji

### Nowe pliki
- `includes/class-imagemappro-integration.php` - główna klasa integracji z Image Map Pro
- `admin/class-admin-imagemappro.php` - panel administracyjny konfiguracji integracji
- `examples/image-map-pro-config-example.php` - przykłady programatycznej konfiguracji
- `CHANGELOG_IMAGE_MAP_PRO_INTEGRATION.md` - szczegółowa dokumentacja integracji
- `IMAGE_MAP_PRO_INTEGRATION.md` - przewodnik użytkownika

### Zmieniono
- `includes/class-sync.php`: Dodano wywołanie `do_action('develogic_sync_completed', $stats)` po synchronizacji
- `develogic-integration.php`: Włączono integrację z Image Map Pro

### Funkcje integracji
- Automatyczne dopasowanie kształtów po numerze lokalu (pole "title" w Image Map Pro)
- Wsparcie dla wielu budynków i projektów
- Konfigurowalne kolory dla każdego statusu z WordPress Color Picker
- Logi wszystkich operacji w panelu synchronizacji
- Bezpieczne API z walidacją danych i sprawdzaniem uprawnień

### Wymagania
- Image Map Pro v6+ (wtyczka musi być aktywna)
- Kształty w Image Map Pro muszą mieć w polu "title" numer lokalu z Develogic

### Korzyści
- ✅ Wizualna synchronizacja statusów lokali w czasie rzeczywistym
- ✅ Automatyzacja - zero ręcznej pracy po konfiguracji
- ✅ Elastyczna konfiguracja kolorów i mapowań
- ✅ Łatwa obsługa wielu budynków
- ✅ Pełne logowanie dla łatwego debugowania

**Zobacz szczegóły**:
- `IMAGE_MAP_PRO_INTEGRATION.md` - szybki start i FAQ
- `CHANGELOG_IMAGE_MAP_PRO_INTEGRATION.md` - pełna dokumentacja techniczna
- `examples/image-map-pro-config-example.php` - przykłady programistyczne

## [2.1.1] - 2025-11-14

### Dodano
- **Indykatory kierunku sortowania**: Wizualne strzałki ↑↓ przy opcjach sortowania pokazujące kierunek (rosnąco/malejąco)

### Zmieniono
- **uninstall.php**: Rozszerzono proces deinstalacji o kompletne czyszczenie wszystkich danych wtyczki
- **apartments-list.php**: Dodano strukturę SVG ze strzałkami do każdej opcji sortowania
- **apartments-list.js**: Zaktualizowano logikę sortowania aby przełączać atrybut `data-direction`
- **apartments-list.css**: Dodano style dla strzałek sortowania z animacjami opacity

### Ulepszone czyszczenie przy usuwaniu wtyczki
- Usuwanie zaplanowanych zadań cron (`develogic_sync_cron`)
- Usuwanie wszystkich transients (`develogic_sync_lock`, `develogic_last_api_error`)
- Usuwanie wszystkich meta pól lokali (ponad 40 różnych pól z API)
- Usuwanie wszystkich opcji wtyczki (`develogic_settings`, `develogic_last_sync`, `develogic_sync_log`)
- Kompletne czyszczenie taxonomii i term meta
- Usuwanie wszystkich postów typu `develogic_local` i powiązanych załączników

### Indykatory sortowania - funkcje
- Strzałka ↑ (w górę) = sortowanie rosnące (asc)
- Strzałka ↓ (w dół) = sortowanie malejące (desc)
- Kliknięcie tej samej opcji przełącza kierunek
- Kliknięcie innej opcji resetuje do sortowania rosnącego
- Płynne animacje opacity dla lepszego UX

### Korzyści
- ✅ Całkowite czyszczenie bazy danych przy usuwaniu wtyczki
- ✅ GDPR compliance - brak pozostałych danych użytkownika
- ✅ Świeży start przy ponownej instalacji
- ✅ Brak śmieci w bazie danych
- ✅ Lepszy UX - natychmiastowa wizualna informacja o kierunku sortowania
- ✅ Intuicyjne strzałki jako uniwersalny symbol sortowania

**Zobacz szczegóły**:
- `CHANGELOG_COMPLETE_UNINSTALL.md` - czyszczenie przy deinstalacji
- `CHANGELOG_SORT_INDICATORS.md` - indykatory sortowania

## [2.1.0] - 2025-10-29

### Dodano
- **Nowy layout `apartments-list`**: Katalogowy widok listy mieszkań wzorowany na profesjonalnych serwisach deweloperskich
- **Shortcode `[develogic_apartments_list]`**: Pełnofunkcjonalny katalog mieszkań z filtrami i galeriami
- **Integracja z Shuffle.js 6.1.0**: Filtrowanie i sortowanie kart mieszkań z płynnymi animacjami
- **Integracja z Tippy.js 6.3.7**: Profesjonalne tooltips dla przycisków akcji
- **Szablon `/templates/apartments-list.php`**: Nowa struktura HTML dla listy mieszkań
- **Stylesheet `/assets/css/apartments-list.css`**: Dedykowane style dla nowego layoutu
- **Script `/assets/js/apartments-list.js`**: JavaScript obsługujący sortowanie, galerie i ulubione
- **Dokumentacja `APARTMENTS_LIST_USAGE.md`**: Pełna instrukcja użycia z przykładami

### Funkcje apartments-list
- 6-kolumnowy responsywny layout (nazwa, szczegóły, 2 obrazy, cena, akcje)
- Atrybuty `data-*` z paddingiem (leading zeros) dla łatwego sortowania przez Shuffle.js
- lightGallery z automatyczną inicjalizacją dla każdego mieszkania
- Tippy.js tooltips na przyciskach "zapytaj" i "obserwuj"
- localStorage do zapisywania ulubionych mieszkań
- Automatyczne rozpoznawanie typu projekcji (3D, plan, elewacja)
- Wyświetlanie tagów z atrybutów API (aneks, balkon, taras, itd.)
- Responsywność: 1 do 6 kolumn w zależności od rozdzielczości
- Dedykowane style print dla czystego wydruku
- Wsparcie dla filtrów: `investment_id`, `local_type_id`, `building_id`
- Liczniki statusów (dostępne, rezerwacje)
- Sortowanie po: piętro, metraż, pokoje, cena, cena m²

### Zmieniono
- **class-assets.php**: Zarejestrowano Shuffle.js, Tippy.js, nowe style i skrypty
- **class-shortcodes.php**: Dodano metodę `render_apartments_list()`
- **README.md**: Zaktualizowano o nowy shortcode i link do dokumentacji

### Techniczne
- Wszystkie biblioteki ładowane z CDN (jsdelivr)
- Kompatybilność z istniejącymi shortcodami
- Możliwość override szablonu w motywie (`theme/develogic/apartments-list.php`)
- Filtry WordPress dla customizacji (klatka, adres budynku, whitelist tagów)

### Dokumentacja
- Pełne przykłady użycia w `APARTMENTS_LIST_USAGE.md`
- Przykłady customizacji CSS
- Instrukcje integracji z własnym kodem
- Troubleshooting i FAQ

---

## [2.0.0] - 2025-10-27

### 🚀 MAJOR UPDATE: Tryb SYNC

**BREAKING CHANGE**: Wtyczka została przeprojektowana z trybu "Live + Cache" na pełny tryb "SYNC".

### Dodano
- **Custom Post Type** `develogic_local` do przechowywania lokali w bazie WordPress
- **Custom Taxonomies**:
  - `develogic_investment` - inwestycje
  - `develogic_local_type` - typy lokali
  - `develogic_building` - budynki
  - `develogic_status` - statusy
- **REST API Endpoint** `/wp-json/develogic/v1/sync` dla zewnętrznego crona
- **REST API Endpoint** `/wp-json/develogic/v1/sync/status` do sprawdzania statusu
- **Panel Synchronizacji** w WordPress Admin (`Develogic → Synchronizacja`):
  - Status synchronizacji (liczba lokali, ostatnia sync)
  - Ręczne uruchomienie synchronizacji
  - Log synchronizacji (ostatnie 50 wpisów)
  - Instrukcje konfiguracji zewnętrznego crona (cron-job.org)
  - Przykłady CURL
  - Wyświetlenie secret key
  - Możliwość wyczyszczenia wszystkich lokali
- **Zabezpieczenie endpointu**: Secret key (Bearer token) generowany automatycznie
- **Deduplikacja**: Aktualizacja istniejących lokali po `localId`
- **Logowanie**: Pełne logi sync w WordPress options
- **Lock mechanism**: Zapobieganie równoczesnym synchronizacjom (transient lock)
- **Klasa `Develogic_Sync`**: Synchronizacja z API do CPT
- **Klasa `Develogic_Sync_Endpoint`**: REST endpoint dla crona
- **Klasa `Develogic_Post_Type`**: Rejestracja CPT i taxonomies
- **Klasa `Develogic_Local_Query`**: Helper do pobierania danych z CPT
- **Klasa `Develogic_Admin_Sync`**: Panel admin synchronizacji
- **Dokumentacja** `SYNC_MODE.md` z pełną instrukcją wdrożenia

### Zmieniono
- **Shortcody** - teraz pobierają dane z lokalnej bazy (CPT) zamiast API/cache
- **REST API** - endpoints pobierają dane z CPT zamiast cache
- **Historia cen** - nadal pobierana live z API (nie cachowana)
- **Ustawienia** - usunięto pola TTL cache, dodano pole Secret Key
- **Activation hook** - dodano generowanie secret key

### Usunięto
- ❌ **`class-cache-manager.php`** - cały mechanizm cache (WordPress Transients)
- ❌ **WP-Cron prefetch** - nie ma już `develogic_prefetch_cache`
- ❌ **Panel Cache** w admin - submenu "Cache"
- ❌ **Tryb "live + cache"** - całkowicie zastąpiony przez SYNC
- ❌ **Wszystkie metody cache** z kodu (get/set transients)

### Naprawiono
- ✅ **Timeout API** - dane są teraz lokalne, nie ma timeoutów (60s → <0.1s)
- ✅ **Wydajność** - shortcody ładują się natychmiastowo z bazy WordPress
- ✅ **Niezawodność** - brak zależności od dostępności zewnętrznego API w momencie wyświetlania

### Migracja
1. Zaktualizuj wtyczkę
2. Reaktywuj wtyczkę (wygeneruje secret key)
3. Skonfiguruj API w **Develogic → Ustawienia**
4. Uruchom pierwszą synchronizację w **Develogic → Synchronizacja**
5. Skonfiguruj zewnętrzny CRON (cron-job.org) - instrukcje w panelu
6. Gotowe!

### Architektura
```
Zewnętrzny CRON (co 1min) → WordPress REST → Develogic API → WordPress DB (CPT) → Shortcody (szybko!)
```

### Performance
- **Przed**: 60s timeout przy pierwszym wywołaniu ❌
- **Teraz**: <0.1s przy każdym wywołaniu ✅

### Więcej informacji
Zobacz `SYNC_MODE.md` dla pełnej dokumentacji.

---

## [1.0.1] - 2025-10-27

### Naprawiono
- **CRITICAL FIX:** Fatal error "Allowed memory size exhausted" podczas aktywacji wtyczki
- Przeniesiono inicjalizację komponentów do hooka `plugins_loaded` (wcześniej w konstruktorze)
- Dodano static guard w `init_components()` aby zapobiec wielokrotnej inicjalizacji
- Dodano lazy loading dla API Client i Cache Manager przez magic getter `__get()`
- Zabezpieczono metodę `deactivate()` przed undefined property error
- Poprawiono kolejność ładowania klas aby uniknąć cyklicznych wywołań

### Zmieniono
- Zmieniono metodę `init_components()` z `private` na `public` (wymagane dla hooka)
- API Client i Cache Manager są teraz tworzone dopiero przy pierwszym użyciu (lazy loading)

## [1.0.0] - 2025-10-27

### Dodano
- Integrację z API Develogic (GET locals, investments, localTypes, price history)
- Klient API z obsługą błędów i logowaniem
- System cache oparty na WordPress Transients z konfigurowalnymi TTL
- Panel administracyjny w WP-Admin:
  - Ustawienia API (URL, klucz, timeout)
  - Ustawienia cache (TTL dla różnych typów danych)
  - Ustawienia layoutu A1 (sortowanie, ceny, statusy, PDF)
  - Strona zarządzania cache ze statystykami
- Shortcode `[develogic_offers_a1]` - główny layout dla JeziornaTowers i OstojaOsiedle:
  - Panel wyboru budynków z miniaturami
  - Liczniki statusów (Wolne, Rezerwacja)
  - Sortowanie wielopolowe (piętro, metraż, pokoje, cena, cena m²)
  - Karty ofert w układzie 6-kolumnowym
  - Integracja z LightGallery (wizualizacje, rzuty)
  - Funkcja "obserwuj" (localStorage)
  - Przycisk "Lista do wydruku"
  - Responsywność (mobile, tablet, desktop)
- Shortcode `[develogic_offers]` - generyczny listing ofert:
  - Widoki: grid, list, table
  - Filtry: investment, localType, building, status, rooms, floor, area, price
  - AJAX loading
  - Paginacja
- Shortcode `[develogic_filters]` - panel filtrów:
  - Pola konfigurowalne (investment, localType, price, area, rooms, floor, search, sort)
  - Komunikacja z listą ofert przez AJAX
  - Przycisk resetowania filtrów
- Shortcode `[develogic_local]` - pojedynczy lokal:
  - Pełne szczegóły lokalu
  - Galeria wizualizacji
  - Tabela pomieszczeń
  - Atrybuty (cechy)
  - Lokale w pakiecie
  - Opcjonalnie historia cen
- Shortcode `[develogic_price_history]` - historia cen z wykresem:
  - Wykres Chart.js (line/bar)
  - Tabela z datami i cenami
  - Obsługa cen brutto/netto i za m²
- Shortcode `[develogic_investments]` - lista inwestycji
- Shortcode `[develogic_local_types]` - lista typów lokali
- REST API pod `/wp-json/develogic/v1/`:
  - GET `/offers` - oferty z filtrami i sortowaniem
  - GET `/local/{id}` - pojedynczy lokal
  - GET `/price-history/{id}` - historia cen
  - GET `/investments` - inwestycje
  - GET `/local-types` - typy lokali
  - GET `/buildings` - budynki
- Klasy formatujące:
  - Formatowanie cen (PLN, format polski)
  - Formatowanie powierzchni (m²)
  - Formatowanie pięter (Piwnica, Parter, 1, 2, ...)
  - Formatowanie dat (polskie nazwy miesięcy)
  - Parsowanie kierunków świata
- Klasy filtrowania i sortowania:
  - Filtrowanie wielopolowe
  - Sortowanie po dowolnym polu
  - Liczniki statusów
  - Lista budynków
- Integracja z LightGallery 2.7.2:
  - Pluginy: thumbnail, zoom, fullscreen, hash
  - Panel informacji w galerii
  - Akcje: mailto, obserwuj, PDF
- Integracja z Chart.js 4.4.0:
  - Wykres historii cen
  - Dwie osie Y (cena całkowita i za m²)
  - Tooltip z formatowaniem PLN
- Funkcja "obserwuj":
  - Przechowywanie w localStorage
  - Toggle aktywny/nieaktywny
  - Synchronizacja między kartami a galerią
  - Notyfikacje (toast)
- Funkcja druku:
  - Dedykowane style @media print
  - Ukrywanie niepotrzebnych elementów
  - Formatowanie dla czytelności
- System szablonów:
  - 8 wbudowanych szablonów
  - Możliwość override w motywie (`theme/develogic/*.php`)
  - Przekazywanie zmiennych do szablonów
- Hooki i filtry:
  - `develogic_building_thumbnail` - miniatury budynków
  - `develogic_building_address` - adresy budynków
  - `develogic_local_klatka` - klatka lokalu
  - `develogic_attribute_whitelist` - lista dozwolonych tagów
  - `develogic_pdf_link` - link do PDF
  - `develogic_contact_email` - email kontaktowy
  - `develogic_email_subject` - temat emaila
  - `develogic_local_data` - modyfikacja danych lokalu
  - `develogic_sort_locals` - niestandardowe sortowanie
  - Akcje: `develogic_before_init`, `develogic_init`, `develogic_after_card_render`
- Internacjonalizacja (i18n):
  - Text domain: `develogic`
  - Wszystkie stringi gotowe do tłumaczenia
  - Polskie tłumaczenia jako domyślne
- Dokumentacja:
  - README.md - dokumentacja główna
  - INSTALL.md - instrukcja instalacji krok po kroku
  - EXAMPLE_USAGE.md - przykłady użycia shortcodów
  - STRUCTURE.md - opis struktury projektu
  - CHANGELOG.md - ten plik
  - Snippety w `examples/functions-snippets.php`
- Style CSS:
  - Responsywność (breakpointy: 768px, 1024px)
  - Print styles
  - Flexbox/Grid layouts
  - Animacje i transitions
- JavaScript:
  - Funkcja "obserwuj" (localStorage)
  - AJAX loading ofert
  - Notification system
  - Helper functions
  - Inicjalizacja LightGallery
  - Sortowanie i filtrowanie

### Zabezpieczenia
- Sanityzacja wszystkich danych z API
- Escaping wszystkich danych w szablonach
- Nonce dla REST API requests
- Permissions check (`manage_options`) dla panelu admin
- Bezpieczne przechowywanie API Key w `wp_options`
- Walidacja parametrów shortcodów i REST API

### Wydajność
- Cache oparty na WordPress Transients
- Konfigurowalne TTL (30 min dla locals, 24h dla investments/types)
- Lazy loading assetów (tylko gdy shortcode użyty)
- CDN dla bibliotek zewnętrznych (LightGallery, Chart.js)
- Filtrowanie i sortowanie na cache'owanych danych
- Paginacja REST API (max 100/strona)

### Kompatybilność
- WordPress 5.0+
- PHP 7.4+
- Testowane na PHP 7.4, 8.0, 8.1, 8.2
- Kompatybilne z popularnymi motywami (Astra, GeneratePress, itp.)
- Responsywność: desktop, tablet, mobile

### Znane ograniczenia
- LightGallery wymaga licencji dla użytku komercyjnego (obecnie używamy wersji CDN)
- Favorites (obserwuj) działają tylko w ramach jednej przeglądarki (localStorage)
- Brak dedykowanych bloków Gutenberg (tylko shortcody - bloki w przyszłej wersji)
- Brak integracji z page builderami (Elementor, Divi - w przyszłej wersji)

## [Planowane] - Przyszłe wersje

### [1.1.0] - Q1 2026 (planowane)
- Bloki Gutenberg dla wszystkich shortcodów
- Live preview w Gutenberg
- Widget dla Elementor
- Widget dla Beaver Builder
- Eksport ofert do CSV/Excel
- Porównywarka ofert (do 3 lokali)
- Zapisywanie filtrów jako "ulubione wyszukiwania"
- Integracja z Google Maps (lokalizacja inwestycji)

### [1.2.0] - Q2 2026 (planowane)
- Tryb Sync (import do CPT `develogic_local`)
- Dedykowane URL-e dla ofert
- Breadcrumbs i SEO
- Schema.org markup dla ofert
- Sitemap XML dla ofert
- Moduł rezerwacji online
- Integracja z kalendarzem wizyt

### [2.0.0] - Q3 2026 (planowane)
- Dashboard analityczny (statystyki wyświetleń, kliknięć)
- Lead management (zapytania z formularzy)
- Email marketing (powiadomienia o nowych ofertach)
- Multi-język (WPML, Polylang)
- White label mode
- API własne (dla zewnętrznych integracji)

## Wsparcie

Wersja 1.0.0 będzie wspierana do Q4 2026 z poprawkami bezpieczeństwa i bugfixami.

---

**Legenda:**
- `Dodano` - nowe funkcje
- `Zmieniono` - zmiany w istniejących funkcjach
- `Przestarzałe` - funkcje wycofywane w przyszłości
- `Usunięto` - usunięte funkcje
- `Naprawiono` - poprawki błędów
- `Bezpieczeństwo` - poprawki bezpieczeństwa

