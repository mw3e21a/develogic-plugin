# Changelog - Nowy Layout

## 2025-10-29

### Dodane
- **Nowy szablon**: `templates/apartments-list-new.php`
  - Alternatywna wersja wyświetlania listy mieszkań
  - Wykorzystuje minifikowany CSS z `new-layout.css`
  - Pełna struktura HTML zgodna z dostarczonym wzorcem
  - Font Lato ładowany z Google Fonts dla zapewnienia poprawnego wyświetlania
  
- **Nowy shortcode**: `[develogic_apartments_list_new]`
  - Metoda w `class-shortcodes.php`: `render_apartments_list_new()`
  - Wszystkie parametry zgodne ze standardowym layoutem
  - Automatyczne ładowanie odpowiednich zasobów CSS/JS
  
- **Nowy styl CSS**: `assets/css/new-layout.css`
  - Zarejestrowany jako `develogic-new-layout`
  - Minifikowany kod CSS z zewnętrznego projektu
  - Zależności: Tippy.js, lightGallery i pluginy
  
- **Dokumentacja**:
  - `NEW_LAYOUT_USAGE.md` - szczegółowa dokumentacja użycia
  - Zaktualizowano główny `README.md` o informacje o nowym layoutcie

### Funkcjonalność

#### Sortowanie
- Piętro (`data-floor`)
- Metraż (`data-area`)
- Liczba pokoi (`data-rooms`)
- Cena (`data-price`)
- Cena za m² (`data-price-m2`)
- Dropdown mobilny + przyciski desktop

#### Galeria
- Obraz główny (displayUrl)
- Plan mieszkania (type zawiera "plan")
- Dodatkowe obrazy (ukryte, w galerii)
- Integracja z lightGallery
- Thumbnails i zoom

#### Statusy
- `status-available` - Dostępne (zielony)
- `status-reservation` - Rezerwacja (pomarańczowy)  
- `status-sold` - Sprzedane (czerwony)

#### Dane wyświetlane
- Nazwa budynku + adres
- Numer mieszkania
- Status
- Klatka (opcjonalnie, przez filtr)
- Kondygnacja
- Powierzchnia
- Liczba pokoi
- Tagi/atrybuty (aneks, balkon, taras, itp.)
- Cena + cena za m²
- Planowana data oddania
- Link do PDF (jeśli skonfigurowany)

#### Akcje
- Email z zapytaniem
- Obserwowanie/ulubione (jeśli włączone)
- Drukowanie listy (jeśli włączone)

### Customizacja

#### Dostępne filtry
```php
// Adres budynku
apply_filters('develogic_building_address', $address, $building_id);

// Klatka schodowa
apply_filters('develogic_local_klatka', $klatka, $local);

// Whitelist atrybutów do wyświetlenia
apply_filters('develogic_attribute_whitelist', $whitelist);
```

### Struktura plików

```
develogic-wp-plugin/
├── assets/
│   └── css/
│       └── new-layout.css          [NOWY]
├── templates/
│   └── apartments-list-new.php     [NOWY]
├── public/
│   ├── class-shortcodes.php        [ZMODYFIKOWANY]
│   └── class-assets.php            [ZMODYFIKOWANY]
├── NEW_LAYOUT_USAGE.md             [NOWY]
├── CHANGELOG_NEW_LAYOUT.md         [NOWY]
└── README.md                       [ZMODYFIKOWANY]
```

### Kompatybilność
- WordPress 5.0+
- PHP 7.4+
- Wszystkie istniejące funkcjonalności wtyczki zachowane
- Nowy layout działa równolegle ze standardowym
- Brak zmian breaking changes

### Testowanie
Aby przetestować nowy layout:

1. Dodaj shortcode na stronie:
   ```
   [develogic_apartments_list_new investment_id="123"]
   ```

2. Sprawdź czy CSS się ładuje:
   - Otwórz DevTools → Network → CSS
   - Szukaj `new-layout.css`

3. Sprawdź interakcje:
   - Sortowanie (kliknij przyciski/dropdown)
   - Galeria (kliknij na obrazek)
   - Tooltips (najedź na ikony akcji)

### Uwagi techniczne
- Plik `new-layout.css` zawiera wszystkie style w jednym minifikowanym pliku
- **Font Lato** ładowany z Google Fonts (wagi: 300, 400, 700, 900, italic 400, 700)
- JavaScript używa istniejącego `apartments-list.js`
- Shuffle.js obsługuje sortowanie i filtrowanie
- lightGallery v2.7.2 obsługuje galerię zdjęć
- Tippy.js v6.3.7 obsługuje tooltips

### Planowane ulepszenia
- [ ] Filtrowanie po statusie
- [ ] Filtrowanie po liczbie pokoi
- [ ] Paginacja wyników
- [ ] Zapisywanie preferencji sortowania
- [ ] Export do PDF całej listy

