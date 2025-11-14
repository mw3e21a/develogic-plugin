# Develogic Integration - WordPress Plugin

Wtyczka WordPress do integracji z API Develogic. Umożliwia wyświetlanie ofert mieszkań, filtrowanie, sortowanie, galerie zdjęć i więcej.

## Wymagania

- WordPress 5.0 lub nowszy
- PHP 7.4 lub nowszy
- Dostęp do API Develogic (URL + API Key)

## Instalacja

1. Skopiuj folder `develogic-wp-plugin` do katalogu `wp-content/plugins/`
2. Zmień nazwę folderu na `develogic-integration`
3. Aktywuj wtyczkę w panelu WordPress (Wtyczki → Zainstalowane wtyczki)
4. Przejdź do Develogic → Ustawienia i skonfiguruj:
   - URL bazowy API
   - Klucz API
   - Inne ustawienia według potrzeb

## Konfiguracja

curl -X GET "https://domelcki.ondevelogic.com/api/fis/v1/feed/locals" \-H "ApiKey: tRx6d7vh5othPXdtfxu9" \-H "Content-Type: application/json" \-o locals.json

### Ustawienia API
- **URL bazowy API**: np. `https://api.develogic.pl`
- **Klucz API**: otrzymany od konsultanta Develogic
- **Timeout**: czas oczekiwania na odpowiedź API (domyślnie 30 sekund)

### Ustawienia Cache
- **TTL lokali**: czas przechowywania listy lokali (domyślnie 1800 sek = 30 min)
- **TTL inwestycji**: czas przechowywania listy inwestycji (domyślnie 86400 sek = 24h)
- **TTL typów lokali**: czas przechowywania listy typów (domyślnie 86400 sek = 24h)
- **TTL historii cen**: czas przechowywania historii cen (domyślnie 3600 sek = 60 min)

### Ustawienia A1 (JeziornaTowers, OstojaOsiedle)
- **Nazwa dewelopera**: używana w temacie e-maila
- **Domyślne sortowanie**: pole i kierunek sortowania
- **Źródło ceny m²**: `priceGrossm2` lub `omnibusPriceGrossm2`
- **Widoczne statusy**: które statusy pokazywać (Wolny, Rezerwacja, Sprzedany)
- **Funkcja druku**: włącz/wyłącz przycisk "Lista do wydruku"
- **Funkcja "obserwuj"**: włącz/wyłącz możliwość dodawania do ulubionych
- **Źródło PDF**: off/pattern/field - skąd brać link do PDF

## Shortcody

### [develogic_apartments_list_new]
Nowy layout listy mieszkań wykorzystujący stylowanie z `new-layout.css`.

**Atrybuty:**
- `investment_id` - ID inwestycji do filtrowania
- `local_type_id` - ID typu lokalu do filtrowania
- `building_id` - ID budynku do filtrowania
- `title` - Tytuł sekcji (domyślnie: "Lista mieszkań")
- `show_counters` - Pokazuj liczniki statusów (domyślnie: `true`)
- `show_print` - Pokazuj przycisk drukowania (domyślnie: `true`)
- `show_favorite` - Pokazuj przycisk obserwowania (domyślnie: `true`)
- `sort_by` - Domyślne sortowanie (domyślnie: `priceGrossm2`)
- `sort_dir` - Kierunek sortowania: `asc` lub `desc` (domyślnie: `asc`)
- `gallery` - Włącz galerię lightGallery (domyślnie: `true`)

**Przykład:**
```
[develogic_apartments_list_new investment_id="123" title="Dostępne Mieszkania"]
```

**Więcej informacji:** Zobacz [NEW_LAYOUT_USAGE.md](NEW_LAYOUT_USAGE.md)

### [develogic_offers_a1]
Główny layout A1 dla JeziornaTowers i OstojaOsiedle.

**Atrybuty:**
- `investment_id` - ID inwestycji do filtrowania
- `local_type_id` - ID typu lokalu do filtrowania
- `buildings_panel` - pokazuj panel wyboru budynków (true/false)
- `building_id` - startowy ID budynku
- `ajax` - włącz AJAX (true/false)
- `show_counters` - pokazuj liczniki statusów (true/false)
- `show_print` - pokazuj przycisk druku (true/false)
- `show_favorite` - pokazuj funkcję obserwuj (true/false)
- `sort_by` - domyślne sortowanie (floor/area/rooms/priceGross/priceGrossm2)
- `sort_dir` - kierunek sortowania (asc/desc)
- `per_page` - ilość na stronę
- `gallery` - włącz LightGallery (true/false)

**Przykład:**
```
[develogic_offers_a1 buildings_panel="true" show_print="true" show_favorite="true" ajax="true"]
```

### [develogic_apartments_list]
Katalogowy widok listy mieszkań z Shuffle.js, lightGallery i Tippy.js. **Nowy layout!**

**Atrybuty:**
- `investment_id` - ID inwestycji do filtrowania
- `local_type_id` - ID typu lokalu
- `building_id` - ID budynku do filtrowania
- `title` - tytuł nagłówka listy (domyślnie "Lista mieszkań")
- `show_counters` - pokazuj liczniki statusów (true/false)
- `show_print` - pokazuj przycisk druku (true/false)
- `show_favorite` - pokazuj funkcję obserwuj (true/false)
- `sort_by` - domyślne sortowanie (floor/area/rooms/priceGross/priceGrossm2)
- `sort_dir` - kierunek sortowania (asc/desc)
- `gallery` - włącz lightGallery (true/false)

**Przykład:**
```
[develogic_apartments_list title="Lista mieszkań 30" sort_by="priceGrossm2" investment_id="123"]
```

**Pełna dokumentacja:** Zobacz [APARTMENTS_LIST_USAGE.md](APARTMENTS_LIST_USAGE.md)

### [develogic_offers]
Generyczny listing ofert.

**Atrybuty:**
- `investment_id`, `local_type_id`, `building_id` - filtry
- `status`, `rooms`, `floor` - filtry
- `min_area`, `max_area` - zakres powierzchni
- `min_price_gross`, `max_price_gross` - zakres ceny
- `sort_by`, `sort_dir` - sortowanie
- `per_page` - ilość na stronę
- `view` - widok (grid/list/table)
- `ajax` - AJAX (true/false)

**Przykład:**
```
[develogic_offers view="grid" per_page="12" sort_by="priceGrossm2" sort_dir="asc"]
```

### [develogic_filters]
Panel filtrów.

**Atrybuty:**
- `target` - selektor/ID kontenera z ofertami
- `fields` - pola do pokazania (investment,localType,price,area,rooms,floor,worldDir,status,search,sort)
- `expanded` - rozwinięte od razu (true/false)
- `show_reset` - przycisk resetowania (true/false)
- `investment_id` - początkowe ID inwestycji

**Przykład:**
```
[develogic_filters target="#offersA" fields="investment,localType,price,area,rooms,sort" expanded="true"]
```

### [develogic_local]
Pojedynczy lokal.

**Atrybuty:**
- `id` - ID lokalu (wymagane)
- `template` - szablon (single/custom)
- `show_price_history` - pokaż historię cen (true/false)

**Przykład:**
```
[develogic_local id="193619" show_price_history="true"]
```

### [develogic_price_history]
Historia cen lokalu.

**Atrybuty:**
- `local_id` - ID lokalu (wymagane)
- `chart` - typ wykresu (line/bar/none)
- `template` - szablon (chart/table)

**Przykład:**
```
[develogic_price_history local_id="193619" chart="line"]
```

### [develogic_investments]
Lista inwestycji.

**Atrybuty:**
- `template` - szablon (card/row/list)
- `link_to_offers` - linkuj do ofert (true/false)
- `per_page` - ilość na stronę

**Przykład:**
```
[develogic_investments template="card" link_to_offers="true"]
```

### [develogic_local_types]
Lista typów lokali.

**Atrybuty:**
- `template` - szablon (chip/row/list)
- `link_to_offers` - linkuj do ofert (true/false)

**Przykład:**
```
[develogic_local_types template="chip" link_to_offers="true"]
```

## REST API

Wtyczka udostępnia REST API pod `/wp-json/develogic/v1/`:

- `GET /offers` - lista ofert z filtrami i sortowaniem
- `GET /local/{id}` - pojedynczy lokal
- `GET /price-history/{id}` - historia cen lokalu
- `GET /investments` - lista inwestycji
- `GET /local-types` - lista typów lokali
- `GET /buildings?investment_id={id}` - lista budynków

## Szablony (Theme Override)

Możesz nadpisać szablony wtyczki tworząc folder `develogic` w swoim motywie:

```
your-theme/
  develogic/
    a1-layout.php
    a1-card.php
    filters.php
    local-single.php
    price-history-chart.php
    ...
```

## Hooki i filtry

### Filtry

**`develogic_building_thumbnail`**
Zwraca URL miniatury budynku.
```php
add_filter('develogic_building_thumbnail', function($url, $building_id) {
    // Zwróć URL miniatury dla danego budynku
    return 'https://example.com/building-' . $building_id . '.jpg';
}, 10, 2);
```

**`develogic_building_address`**
Zwraca adres budynku.
```php
add_filter('develogic_building_address', function($address, $building_id) {
    $addresses = array(
        66 => 'ul. Przykładowa 1, Poznań',
        67 => 'ul. Testowa 2, Poznań',
    );
    return isset($addresses[$building_id]) ? $addresses[$building_id] : '';
}, 10, 2);
```

**`develogic_local_klatka`**
Zwraca klatkę lokalu (jeśli dostępna).
```php
add_filter('develogic_local_klatka', function($klatka, $local) {
    // Pobierz z atrybutów lub innego źródła
    foreach ($local['attributes'] as $attr) {
        if (strtolower($attr['name']) === 'klatka') {
            return $attr['value'];
        }
    }
    return '';
}, 10, 2);
```

**`develogic_attribute_whitelist`**
Lista dozwolonych tagów z atrybutów.
```php
add_filter('develogic_attribute_whitelist', function($whitelist) {
    $whitelist[] = 'winda';
    $whitelist[] = 'klimatyzacja';
    return $whitelist;
});
```

### Akcje

**`develogic_before_init`**
Wykonywane przed inicjalizacją wtyczki.

**`develogic_init`**
Wykonywane po inicjalizacji wtyczki.

## Cache

Wtyczka automatycznie cache'uje dane z API według ustawionych TTL. Możesz ręcznie wyczyścić cache w:
- Develogic → Cache → Wyczyść cały cache

Lub programowo:
```php
develogic()->cache_manager->clear_all_cache();
```

## Funkcja "Obserwuj" (Favorites)

Wtyczka przechowuje ulubione oferty w `localStorage` przeglądarki. Nie wymaga logowania użytkownika.

Dane są zapisywane jako JSON w kluczu `develogic_favorites`.

## Wydruk

Przycisk "Lista do wydruku" uruchamia `window.print()`. CSS zawiera specjalne style `@media print` dla czystego wydruku.

## Wsparcie

W razie problemów:
1. Sprawdź WP_DEBUG w `wp-config.php`
2. Zobacz logi w `wp-content/debug.log`
3. Sprawdź panel Develogic → Cache dla statusu cache
4. Sprawdź czy API URL i klucz są poprawne

## Changelog

### 1.1.0 (2025-10-29)
- **Nowy layout**: `apartments-list` - katalogowy widok listy mieszkań
- Integracja z Shuffle.js do filtrowania i sortowania
- Integracja z Tippy.js dla tooltipów
- Nowy shortcode `[develogic_apartments_list]`
- Pełna dokumentacja w APARTMENTS_LIST_USAGE.md
- Responsywny design dopasowany do mobile/tablet/desktop
- Atrybuty data-* z paddingiem dla Shuffle.js
- Wsparcie dla tagów z atrybutów API
- Automatyczna inicjalizacja lightGallery dla każdego mieszkania
- localStorage dla funkcji "obserwuj"

### 1.0.0 (2025-10-27)
- Pierwsza wersja
- Integracja z API Develogic
- Layout A1 dla JeziornaTowers i OstojaOsiedle
- Shortcody dla różnych widoków
- REST API
- System cache
- LightGallery dla galerii zdjęć
- Historia cen z wykresami (Chart.js)
- Funkcja "obserwuj" (localStorage)
- Funkcja druku

