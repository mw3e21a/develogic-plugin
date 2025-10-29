# Instrukcja instalacji - Develogic Integration

## Szybki start

### 1. Przygotowanie plików

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone [repository-url] develogic-integration
# lub skopiuj folder develogic-wp-plugin i zmień nazwę na develogic-integration
```

### 2. Aktywacja wtyczki

1. Zaloguj się do panelu WordPress
2. Przejdź do **Wtyczki → Zainstalowane wtyczki**
3. Znajdź **Develogic Integration**
4. Kliknij **Aktywuj**

### 3. Konfiguracja

1. Przejdź do **Develogic → Ustawienia**
2. W sekcji **Ustawienia API**:
   - **URL bazowy API**: wpisz `https://twoje-api.develogic.pl` (otrzymany od Develogic)
   - **Klucz API**: wpisz klucz otrzymany od konsultanta Develogic
   - **Timeout**: pozostaw domyślne 30 sekund
3. W sekcji **Ustawienia Cache**:
   - Domyślne wartości są optymalne, możesz je dostosować według potrzeb
4. W sekcji **Ustawienia layoutu A1**:
   - **Nazwa dewelopera**: wpisz nazwę swojej firmy (używane w e-mailach)
   - Ustaw preferencje sortowania, ceny m², widoczne statusy itd.
5. Kliknij **Zapisz ustawienia**

### 4. Testowanie

Utwórz nową stronę WordPress i dodaj shortcode:

```
[develogic_offers_a1 buildings_panel="true" show_print="true" show_favorite="true"]
```

Zapisz i podejrzyj stronę.

## Wdrożenie dla JeziornaTowers

### URL i API Key
Otrzymasz od Develogic:
- URL API: `https://ib25.wfdev.exant.local` (przykładowy, zastąp prawdziwym)
- API Key: `twoj-klucz-api`

### Dodanie miniatury i adresu budynku

W pliku `functions.php` swojego motywu dodaj:

```php
// Miniatury budynków
add_filter('develogic_building_thumbnail', function($url, $building_id) {
    $thumbnails = array(
        66 => get_template_directory_uri() . '/images/budynek-h.jpg',
        // Dodaj kolejne budynki
    );
    return isset($thumbnails[$building_id]) ? $thumbnails[$building_id] : $url;
}, 10, 2);

// Adresy budynków
add_filter('develogic_building_address', function($address, $building_id) {
    $addresses = array(
        66 => 'ul. Jeziorna 1, Poznań',
        // Dodaj kolejne budynki
    );
    return isset($addresses[$building_id]) ? $addresses[$building_id] : $address;
}, 10, 2);
```

### Dodanie shortcode do strony

Edytuj stronę `jeziornatowers.pl/mieszkania-budynek-h` i zastąp istniejącą tabelę:

```
[develogic_offers_a1 
    buildings_panel="true" 
    building_id="66" 
    show_print="true" 
    show_favorite="true" 
    ajax="true" 
    sort_by="floor" 
    sort_dir="asc"]
```

## Wdrożenie dla OstojaOsiedle

Analogicznie jak dla JeziornaTowers:

1. Ustaw API URL i Key w ustawieniach
2. Dodaj miniatury i adresy budynków w `functions.php`
3. Edytuj stronę `ostojaosiedle.pl/oferta` i dodaj shortcode:

```
[develogic_offers_a1 
    buildings_panel="true" 
    show_print="true" 
    show_favorite="true" 
    ajax="true"]
```

## Dostosowanie wyglądu

### Nadpisanie stylów

W pliku `style.css` swojego motywu:

```css
/* Nadpisz kolory */
.develogic-a1-card:hover {
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}

.local-status.status-available {
    background: #your-color;
}

/* Dostosuj rozmiary */
.develogic-building-card {
    min-height: 250px;
}
```

### Nadpisanie szablonów

Skopiuj szablon z `wp-content/plugins/develogic-integration/templates/` do `wp-content/themes/twoj-motyw/develogic/`:

```
your-theme/
  develogic/
    a1-layout.php       <- nadpisz layout A1
    a1-card.php         <- nadpisz kartę oferty
```

Edytuj skopiowany plik według potrzeb.

## Rozwiązywanie problemów

### Brak ofert / błędy API

1. Sprawdź czy URL API i klucz są poprawne
2. Włącz debugowanie w `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
3. Sprawdź logi w `wp-content/debug.log`
4. Sprawdź czy IP serwera jest na białej liście Develogic

### Cache nie odświeża się

1. Przejdź do **Develogic → Cache**
2. Kliknij **Wyczyść cały cache**
3. Zmniejsz TTL w ustawieniach jeśli potrzeba częstszych aktualizacji

### Galeria nie działa

1. Sprawdź czy LightGallery się ładuje (DevTools → Network)
2. Sprawdź konsolę przeglądarki (F12) czy są błędy JavaScript
3. Upewnij się że `gallery="true"` w shortcode

### Problem z drukiem

1. Sprawdź czy przycisk jest widoczny (`show_print="true"`)
2. Testuj druk w różnych przeglądarkach
3. Dostosuj style `@media print` w CSS jeśli potrzeba

## Aktualizacja wtyczki

```bash
cd wp-content/plugins/develogic-integration
git pull origin main
# lub zastąp pliki ręcznie
```

Po aktualizacji:
1. Wyczyść cache WordPress
2. Wyczyść cache Develogic (Develogic → Cache)
3. Sprawdź czy wszystko działa

## Wsparcie

W razie problemów:
1. Sprawdź dokumentację w `README.md`
2. Sprawdź logi debugowania
3. Skontaktuj się z zespołem developerskim

---

**Wersja:** 1.0.0  
**Data:** 2025-10-27

