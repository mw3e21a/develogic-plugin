# Nowy Layout - Dokumentacja

## Opis

Nowy layout dla listy mieszkań (`apartments-list-new.php`) to alternatywna wersja wyświetlania ofert mieszkań z API Develogic. Wykorzystuje stylowanie z pliku `new-layout.css` (minifikowany CSS z zewnętrznego projektu).

## Użycie

### Shortcode

```
[develogic_apartments_list_new]
```

### Parametry shortcode

Shortcode akceptuje następujące parametry:

- `investment_id` - ID inwestycji (opcjonalnie)
- `local_type_id` - ID typu lokalu (opcjonalnie)  
- `building_id` - ID budynku (opcjonalnie)
- `title` - Tytuł sekcji (domyślnie: "Lista mieszkań")
- `show_counters` - Pokazuj liczniki statusów (domyślnie: `true`)
- `show_print` - Pokazuj przycisk drukowania (domyślnie: `true`)
- `show_favorite` - Pokazuj przycisk obserwowania (domyślnie: `true`)
- `sort_by` - Domyślne sortowanie (domyślnie: `priceGrossm2`)
- `sort_dir` - Kierunek sortowania: `asc` lub `desc` (domyślnie: `asc`)
- `gallery` - Włącz galerię lightGallery (domyślnie: `true`)

### Przykłady użycia

#### Podstawowe użycie
```
[develogic_apartments_list_new]
```

#### Z filtrem inwestycji
```
[develogic_apartments_list_new investment_id="123"]
```

#### Z własnym tytułem
```
[develogic_apartments_list_new title="Dostępne Mieszkania" investment_id="123"]
```

#### Bez liczników i drukowania
```
[develogic_apartments_list_new show_counters="false" show_print="false"]
```

#### Sortowanie po cenie malejąco
```
[develogic_apartments_list_new sort_by="priceGross" sort_dir="desc"]
```

## Struktura HTML

Layout wykorzystuje następującą strukturę:

```html
<section class="a-fadeInUp">
    <div class="container apartments-sort shuffle-sort">
        <div class="txt">
            <!-- Nagłówek z licznikami -->
            <div class="row-auto apartments-header">
                <div class="col apartments-title">
                    <h3 class="apartments-count">...</h3>
                </div>
                <div class="col text-right l apartments-sort">
                    <!-- Dropdown sortowania -->
                </div>
            </div>
        </div>
        
        <div class="apartmnets-list-container">
            <!-- Lista mieszkań -->
            <div class="apartments-list shuffle">
                <!-- Pojedyncze mieszkanie -->
                <div class="aprtment-item shuffle-item">
                    <div class="row-md apartment aprtment-html">
                        <div class="col c-name">...</div>
                        <div class="col c-details">...</div>
                        <div class="col c-image c-image-1">...</div>
                        <div class="col c-image c-image-2">...</div>
                        <div class="col c-price">...</div>
                        <div class="col c-action">...</div>
                        <div class="c-download">...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
```

## Funkcje

### Sortowanie

Layout obsługuje sortowanie po następujących kryteriach:
- Piętro (`data-floor`)
- Metraż (`data-area`)
- Liczba pokoi (`data-rooms`)
- Cena (`data-price`)
- Cena za m² (`data-price-m2`)

Sortowanie działa poprzez Shuffle.js i jest dostępne w formie:
- **Mobile**: dropdown menu (klasa `dropdown-xs`)
- **Desktop**: przyciski sortowania (klasa `btn-sort`)

### Galeria

Każde mieszkanie może mieć wiele zdjęć, które są wyświetlane w lightGallery:
1. **Obraz główny** (displayUrl) - widoczny jako pierwszy thumbnail
2. **Plan mieszkania** - widoczny jako drugi thumbnail
3. **Dodatkowe obrazy** - ukryte, dostępne po otwarciu galerii

### Statusy mieszkań

Layout wyświetla różne statusy mieszkań z odpowiednim kolorowaniem:
- `status-available` - Dostępne (zielony)
- `status-reservation` - Rezerwacja (pomarańczowy)
- `status-sold` - Sprzedane (czerwony)

Klasy CSS do statusów:
- `.status-available` - kolor tekstu: `#6bdd4c`
- `.status-reservation` - kolor tekstu: `#f7a800`
- `.status-sold` - kolor tekstu: `red`

### Tagi/Atrybuty

Layout automatycznie wyświetla atrybuty mieszkania jako tagi (np. "aneks", "balkon", "taras").

Lista domyślnych atrybutów do wyświetlenia (filtr `develogic_attribute_whitelist`):
- aneks
- balkon
- 2 balkony
- garderoba
- taras
- ogród
- pom. gospodarcze

## Customizacja

### Filtr dla adresu budynku

```php
add_filter('develogic_building_address', function($address, $building_id) {
    // Zwróć adres dla budynku
    return 'ul. Przykładowa 1';
}, 10, 2);
```

### Filtr dla klatki

```php
add_filter('develogic_local_klatka', function($klatka, $local) {
    // Zwróć klatkę dla lokalu
    return 'A';
}, 10, 2);
```

### Filtr dla listy atrybutów

```php
add_filter('develogic_attribute_whitelist', function($whitelist) {
    $whitelist[] = 'komórka lokatorska';
    return $whitelist;
});
```

## Wymagane zasoby

Layout automatycznie ładuje następujące zasoby:

### CSS
- **Google Fonts - Lato** - czcionka używana w całym layoutcie (wagi: 300, 400, 700, 900)
- `tippy.css` - style tooltipów
- `lightgallery.css` - style galerii
- `lightgallery-thumbnail.css`
- `lightgallery-zoom.css`
- `lightgallery-fullscreen.css`
- `new-layout.css` - główne style layoutu

### JavaScript
- jQuery
- Shuffle.js - sortowanie i filtrowanie
- Tippy.js - tooltipy
- lightGallery - galeria zdjęć
- `apartments-list.js` - logika aplikacji

## Różnice względem standardowego layoutu

1. **CSS**: Wykorzystuje minifikowany `new-layout.css` zamiast `apartments-list.css`
2. **Struktura HTML**: Zgodna z dostarczonym przykładem (bardziej kompaktowa)
3. **Shortcode**: `[develogic_apartments_list_new]` zamiast `[develogic_apartments_list]`
4. **Instance ID**: Prefix `develogic-apartments-list-new-` zamiast `develogic-apartments-list-`

## Changelog

### v1.0.0 - 2025-10-29
- Utworzenie nowego layoutu `apartments-list-new.php`
- Dodanie shortcode `develogic_apartments_list_new`
- Rejestracja stylu `develogic-new-layout`
- Pełna integracja z systemem Develogic

