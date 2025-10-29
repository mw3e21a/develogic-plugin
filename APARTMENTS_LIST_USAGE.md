# Apartments List Layout - Instrukcja użycia

## Opis

Szablon `apartments-list` to katalogowy widok listy mieszkań wzorowany na profesjonalnych serwisach deweloperskich. Zapewnia:

- **Shuffle.js** - do filtrowania i sortowania (opcjonalnie)
- **lightGallery** - galeria obrazów fullscreen z thumbnailami
- **Tippy.js** - tooltips dla przycisków akcji
- **localStorage** - zapisywanie ulubionych mieszkań
- **Responsywny design** - dopasowany do mobile/tablet/desktop

## Shortcode

```
[develogic_apartments_list]
```

## Dostępne parametry

| Parametr | Typ | Domyślna wartość | Opis |
|----------|-----|------------------|------|
| `investment_id` | int | - | ID inwestycji do filtrowania |
| `local_type_id` | int | - | ID typu lokalu (mieszkanie, lokal użytkowy) |
| `building_id` | int | - | ID budynku do filtrowania |
| `title` | string | "Lista mieszkań" | Tytuł nagłówka listy |
| `show_counters` | bool | true | Czy pokazywać liczniki statusów |
| `show_print` | bool | true | Czy pokazywać przycisk drukowania |
| `show_favorite` | bool | true | Czy pokazywać przyciski "obserwuj" |
| `sort_by` | string | priceGrossm2 | Domyślne sortowanie (floor, area, rooms, priceGross, priceGrossm2) |
| `sort_dir` | string | asc | Kierunek sortowania (asc, desc) |
| `gallery` | bool | true | Czy włączyć lightGallery |

## Przykłady użycia

### Podstawowe użycie
```
[develogic_apartments_list]
```

### Z filtrowaniem po inwestycji
```
[develogic_apartments_list investment_id="123"]
```

### Z własnym tytułem i sortowaniem
```
[develogic_apartments_list title="Oferta Mieszkań 30" sort_by="area" sort_dir="desc"]
```

### Bez liczników i ulubionych
```
[develogic_apartments_list show_counters="false" show_favorite="false"]
```

### Tylko konkretny budynek
```
[develogic_apartments_list building_id="5" title="Budynek A - Lista Mieszkań"]
```

## Struktura HTML

Każde mieszkanie renderowane jest jako:

```html
<div id="mieszkanie-{localId}" class="apartment-item shuffle-item" 
     data-rooms="03" 
     data-floor="00" 
     data-price="00506692" 
     data-price-m2="07600" 
     data-area="06667"
     data-status="available">
    
    <div class="row-md apartment apartment-html">
        <!-- Kolumna: Nazwa i status -->
        <div class="col c-name">...</div>
        
        <!-- Kolumna: Szczegóły -->
        <div class="col c-details">...</div>
        
        <!-- Kolumna: Obraz 3D -->
        <div class="col c-image c-image-1">...</div>
        
        <!-- Kolumna: Plan -->
        <div class="col c-image c-image-2">...</div>
        
        <!-- Kolumna: Cena -->
        <div class="col c-price c-price-yes c-bg">...</div>
        
        <!-- Kolumna: Akcje -->
        <div class="col c-action c-bg">...</div>
        
        <!-- Opcjonalnie: Download/Opis -->
        <div class="c-download">...</div>
    </div>
</div>
```

## Atrybuty data-* dla Shuffle.js

Każdy element `.apartment-item` posiada atrybuty data-* z wartościami wypełnionymi zerami wiodącymi (padding), co ułatwia sortowanie:

- `data-rooms="03"` - liczba pokoi (2 cyfry)
- `data-floor="00"` - piętro (2 cyfry)
- `data-price="00506692"` - cena (8 cyfr)
- `data-price-m2="07600"` - cena za m² (8 cyfr)
- `data-area="06667"` - powierzchnia w setnych (8 cyfr, np. 66.67 → 06667)
- `data-status="available"` - klasa statusu (available, reserved, sold)
- `data-groups='["rooms-3","status-available"]'` - grupy do filtrowania

## JavaScript API

### Sortowanie

Sortowanie działa automatycznie po zmianie selecta:

```javascript
$('.sort-select').val('priceGross').trigger('change');
```

### Ulubione (localStorage)

Plugin automatycznie zapisuje ulubione mieszkania w `localStorage`:

```javascript
// Pobranie ulubionych
var favorites = JSON.parse(localStorage.getItem('develogic_favorites') || '[]');

// Dodanie do ulubionych
favorites.push(localId);
localStorage.setItem('develogic_favorites', JSON.stringify(favorites));

// Usunięcie z ulubionych
var index = favorites.indexOf(localId);
if (index > -1) {
    favorites.splice(index, 1);
    localStorage.setItem('develogic_favorites', JSON.stringify(favorites));
}
```

### lightGallery

Galeria inicjalizowana jest automatycznie dla każdego mieszkania:

```javascript
lightGallery(element, {
    selector: '.link-img:not(.hidden)',
    plugins: [lgThumbnail, lgZoom, lgFullscreen, lgHash],
    hash: true,
    galleryId: 'mieszkanie-{localId}'
});
```

## Customizacja

### Filtr dla "Klatka"

Jeśli API nie dostarcza pola "Klatka", możesz je dodać poprzez filtr:

```php
add_filter('develogic_local_klatka', function($klatka, $local) {
    // Przykład: wyciągnij klatkę z numeru mieszkania
    if (preg_match('/^([A-Z])/', $local['number'], $matches)) {
        return $matches[1];
    }
    return '';
}, 10, 2);
```

### Filtr dla adresu budynku

```php
add_filter('develogic_building_address', function($address, $buildingId) {
    $addresses = array(
        1 => 'ul. Falewicza 6',
        2 => 'ul. Kowalska 10',
    );
    
    return isset($addresses[$buildingId]) ? $addresses[$buildingId] : '';
}, 10, 2);
```

### Whitelist tagów (atrybutów)

```php
add_filter('develogic_attribute_whitelist', function($whitelist) {
    return array(
        'aneks',
        'balkon',
        '2 balkony',
        'garderoba',
        'taras',
        'ogród',
        'pom. gospodarcze',
        'komórka lokatorska', // dodaj własny
    );
});
```

### Generowanie linków PDF

W ustawieniach pluginu ustaw wzorzec PDF:

```
https://twojastrona.pl/wp-content/uploads/karty/{number}.pdf
```

Lub użyj filtra:

```php
add_filter('develogic_pdf_link', function($pdf_link, $local) {
    return sprintf(
        'https://twojastrona.pl/karty/mieszkanie-%s.pdf',
        sanitize_title($local['number'])
    );
}, 10, 2);
```

## Style CSS

### Dostosowanie kolorów

```css
/* Status dostępny */
.status.status-available {
    background: #d4edda;
    color: #155724;
}

/* Status rezerwacja */
.status.status-reserved {
    background: #fff3cd;
    color: #856404;
}

/* Cena */
.c-price .h3 {
    color: #0066cc; /* Zmień kolor ceny */
}

/* Przyciski akcji hover */
.btn-border:hover {
    background: #0066cc; /* Zmień kolor hover */
    border-color: #0066cc;
}
```

### Własne tagi

```css
/* Stylizacja tagów */
.tag-aneks {
    background: #e3f2fd;
    color: #1565c0;
}

.tag-balkon,
.tag-2-balkony {
    background: #e8f5e9;
    color: #2e7d32;
}

.tag-taras {
    background: #fff3e0;
    color: #e65100;
}
```

## Responsywność

Layout automatycznie dostosowuje się do różnych rozdzielczości:

- **Desktop (>1200px)**: 6-kolumnowy grid
- **Tablet (1024-1200px)**: 6-kolumnowy grid (lekko zredukowany)
- **Mobile landscape (768-1024px)**: 2-kolumnowy grid z obrazami w drugim rzędzie
- **Mobile portrait (<768px)**: 1-kolumnowy stack

## Drukowanie

Style print automatycznie ukrywają:
- Nagłówek sortowania
- Przyciski akcji
- Przyciski download

I dostosowują layout do druku A4.

## Kompatybilność

- WordPress 5.0+
- PHP 7.4+
- jQuery (included with WP)
- Shuffle.js 6.1.0
- lightGallery 2.7.2
- Tippy.js 6.3.7

## Troubleshooting

### Galeria się nie otwiera

Sprawdź w konsoli czy biblioteki są załadowane:
```javascript
console.log(typeof lightGallery); // powinno być 'function'
```

### Sortowanie nie działa

Upewnij się, że atrybuty `data-*` są poprawnie wypełnione. Sprawdź w inspektorze:
```html
<div data-price="00506692" data-area="06667">
```

### Ulubione znikają po odświeżeniu

Sprawdź czy localStorage działa:
```javascript
localStorage.setItem('test', 'ok');
console.log(localStorage.getItem('test')); // powinno zwrócić 'ok'
```

## Licencje bibliotek

- **Shuffle.js**: MIT License
- **lightGallery**: GPLv3 (darmowa wersja) lub Commercial License
- **Tippy.js**: MIT License

