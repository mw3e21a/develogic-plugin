# Przykłady użycia - Develogic Integration

## Scenariusze użycia

### 1. Strona z pełną ofertą mieszkań (A1 Layout)

Typowa strona dla JeziornaTowers lub OstojaOsiedle.

**Shortcode:**
```
[develogic_offers_a1 
    buildings_panel="true" 
    show_counters="true"
    show_print="true" 
    show_favorite="true" 
    ajax="true"
    sort_by="priceGrossm2" 
    sort_dir="asc"
    per_page="20"]
```

**Co zawiera:**
- Panel wyboru budynków z miniaturami
- Liczniki statusów (dostępne, rezerwacja)
- Sortowanie (piętro, metraż, pokoje, cena, cena m²)
- Galeria LightGallery (wizualizacje, rzuty)
- Funkcja "obserwuj" (localStorage)
- Przycisk druku
- Responsywny layout

---

### 2. Strona konkretnego budynku

Wyświetl tylko oferty z określonego budynku.

**Shortcode:**
```
[develogic_offers_a1 
    building_id="66"
    buildings_panel="false"
    show_counters="true"
    show_print="true"]
```

**Opis:**
- `building_id="66"` - filtruj tylko budynek o ID 66
- `buildings_panel="false"` - ukryj panel wyboru budynków

---

### 3. Strona z filtrem i listą ofert

Połączenie panelu filtrów z listą ofert.

**Kod:**
```html
<div class="offers-section">
    [develogic_filters 
        target="#my-offers" 
        fields="investment,localType,price,area,rooms,floor,search,sort"
        expanded="true"
        show_reset="true"]
    
    <div id="my-offers">
        [develogic_offers 
            view="grid" 
            per_page="12"
            ajax="true"]
    </div>
</div>
```

**Opis:**
- Filtry: inwestycja, typ lokalu, cena, powierzchnia, pokoje, piętro, szukaj, sortowanie
- Lista ładowana przez AJAX
- Widok grid (kafelki)

---

### 4. Strona pojedynczego lokalu z historią cen

Dedykowana strona dla konkretnego mieszkania.

**Shortcode:**
```
[develogic_local 
    id="193619" 
    template="single"
    show_price_history="true"]
```

**Zawiera:**
- Wszystkie szczegóły lokalu
- Galeria wizualizacji i rzutów
- Tabela pomieszczeń z powierzchniami
- Cechy (atrybuty)
- Lokale w pakiecie
- Historia cen z wykresem (Chart.js)
- Formularz kontaktowy (mailto)

---

### 5. Landing page z wyborem inwestycji

Lista wszystkich inwestycji z linkami do ofert.

**Shortcode:**
```
[develogic_investments 
    template="card" 
    link_to_offers="true"
    per_page="12"]
```

**Opis:**
- Kafelki z nazwami inwestycji
- Przyciski "Zobacz oferty" linkują do strony z ofertami (parametr `?investment_id=X`)

---

### 6. Strona z filtrami według typu lokalu

**Kod:**
```html
<h2>Wybierz typ lokalu</h2>
[develogic_local_types 
    template="chip" 
    link_to_offers="true"]

<div id="offers-container">
    [develogic_offers 
        view="grid" 
        ajax="true"]
</div>
```

**Opis:**
- Chipy (przyciski) z typami: Mieszkanie, Lokal użytkowy, Miejsce garażowe, itp.
- Kliknięcie w chip przekierowuje do ofert danego typu

---

### 7. Prosta tabela z ofertami (bez A1)

**Shortcode:**
```
[develogic_offers 
    view="table"
    investment_id="3118"
    status="Wolny,Rezerwacja"
    sort_by="area"
    sort_dir="asc"
    per_page="50"]
```

**Opis:**
- Widok tabelaryczny
- Tylko inwestycja o ID 3118
- Tylko statusy: Wolny i Rezerwacja
- Sortowanie po powierzchni rosnąco
- Do 50 ofert na stronę

---

### 8. Wykres historii cen (standalone)

**Shortcode:**
```
[develogic_price_history 
    local_id="193619" 
    chart="line"
    template="chart"]
```

**Opis:**
- Wykres liniowy z historią cen
- Pokazuje cenę całkowitą i cenę za m²
- Tabela pod wykresem

---

### 9. Responsywny widok mobilny

Layout A1 automatycznie dostosowuje się do urządzeń mobilnych:

- Desktop: 6 kolumn (meta, szczegóły, 2x obrazy, cena, akcje)
- Tablet: 3 kolumny + obrazy pełnej szerokości
- Mobile: 1 kolumna (wszystkie sekcje pod sobą)

Nie wymaga dodatkowej konfiguracji.

---

## Dostosowania w functions.php

### Dodanie miniatury budynku

```php
add_filter('develogic_building_thumbnail', function($url, $building_id) {
    $thumbnails = array(
        66 => get_stylesheet_directory_uri() . '/images/building-h.jpg',
        67 => get_stylesheet_directory_uri() . '/images/building-g.jpg',
    );
    
    return isset($thumbnails[$building_id]) ? $thumbnails[$building_id] : $url;
}, 10, 2);
```

### Dodanie adresu budynku

```php
add_filter('develogic_building_address', function($address, $building_id) {
    $addresses = array(
        66 => 'ul. Jeziorna Towers 1, 61-663 Poznań',
        67 => 'ul. Jeziorna Towers 2, 61-663 Poznań',
    );
    
    return isset($addresses[$building_id]) ? $addresses[$building_id] : $address;
}, 10, 2);
```

### Dodanie pola "klatka"

Jeśli "klatka" jest dostępna w atrybutach:

```php
add_filter('develogic_local_klatka', function($klatka, $local) {
    // Przykład: klatka jest w atrybutach jako "Klatka: A"
    foreach ($local['attributes'] as $attr) {
        if (stripos($attr['name'], 'klatka') !== false) {
            return trim(str_replace('Klatka:', '', $attr['name']));
        }
    }
    
    return $klatka;
}, 10, 2);
```

### Rozszerzenie whitelist tagów

```php
add_filter('develogic_attribute_whitelist', function($whitelist) {
    $whitelist[] = 'winda';
    $whitelist[] = 'klimatyzacja';
    $whitelist[] = 'monitoring';
    
    return $whitelist;
});
```

### Niestandardowy wzorzec PDF

```php
// W ustawieniach wtyczki ustaw:
// - Źródło PDF: "pattern"
// - Wzorzec URL PDF: https://jeziornatowers.pl/pdf/{number}.pdf

// Plik PDF będzie dostępny jako:
// https://jeziornatowers.pl/pdf/KM-13-10-2022-M.pdf
```

---

## Style CSS (nadpisanie)

### Zmiana kolorów statusów

```css
/* W pliku style.css motywu */
.local-status.status-available {
    background: #28a745;
    color: #fff;
}

.local-status.status-reserved {
    background: #ff9800;
    color: #fff;
}

.local-status.status-sold {
    background: #dc3545;
    color: #fff;
}
```

### Zmiana koloru głównego

```css
.price-total,
.action-btn:hover,
.develogic-print-btn {
    background: #your-brand-color;
    border-color: #your-brand-color;
}
```

### Responsywność - niestandardowe breakpointy

```css
@media (max-width: 1200px) {
    .develogic-a1-card {
        grid-template-columns: 1fr 2fr 1.5fr;
    }
}

@media (max-width: 768px) {
    .develogic-a1-card {
        grid-template-columns: 1fr;
        gap: 10px;
    }
}
```

---

## JavaScript (rozszerzenia)

### Event "dodano do ulubionych"

```javascript
jQuery(document).ready(function($) {
    $(document).on('develogic:favorite_added', function(e, localId) {
        console.log('Dodano do ulubionych:', localId);
        // Twój kod, np. Google Analytics event
    });
    
    $(document).on('develogic:favorite_removed', function(e, localId) {
        console.log('Usunięto z ulubionych:', localId);
    });
});
```

### Filtrowanie po kliknięciu w budynek

```javascript
jQuery(document).ready(function($) {
    $('.develogic-building-card').on('click', function() {
        var buildingId = $(this).data('building-id');
        
        // Przeładuj oferty przez AJAX
        $.ajax({
            url: develogicData.restUrl + '/offers',
            data: { building_id: buildingId },
            success: function(response) {
                // Aktualizuj listę
            }
        });
    });
});
```

---

## REST API (przykłady wywołań)

### Pobranie ofert z filtrem

```javascript
fetch('/wp-json/develogic/v1/offers?investment_id=3118&rooms=3&min_area=50&max_area=80')
    .then(response => response.json())
    .then(data => {
        console.log('Oferty:', data.locals);
        console.log('Liczniki statusów:', data.status_counts);
    });
```

### Pobranie pojedynczego lokalu

```javascript
fetch('/wp-json/develogic/v1/local/193619')
    .then(response => response.json())
    .then(local => {
        console.log('Lokal:', local);
    });
```

### Pobranie historii cen

```javascript
fetch('/wp-json/develogic/v1/price-history/193619')
    .then(response => response.json())
    .then(history => {
        console.log('Historia cen:', history.prices);
    });
```

---

## FAQ - Częste pytania

**Q: Czy mogę użyć własnej galerii zamiast LightGallery?**  
A: Tak, ustaw `gallery="false"` w shortcode i dodaj własny kod JS/CSS.

**Q: Jak zmienić domyślną liczbę ofert na stronę?**  
A: Użyj atrybutu `per_page="24"` w shortcode.

**Q: Czy mogę wyświetlić tylko 1 budynek bez panelu wyboru?**  
A: Tak: `[develogic_offers_a1 building_id="66" buildings_panel="false"]`

**Q: Jak dodać przycisk "Kontakt" zamiast mailto?**  
A: Nadpisz szablon `a1-card.php` i zmień sekcję akcji.

**Q: Czy wtyczka działa z Gutenberg?**  
A: Shortcody działają w blokach Gutenberg. Bloki Gutenberg będą dodane w następnej wersji.

---

**Wersja:** 1.0.0  
**Autor:** Develogic Integration Team

