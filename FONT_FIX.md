# Poprawka: Font Lato

## Problem
Po załadowaniu nowego layoutu, zamiast fontu Lato wyświetlała się czcionka systemowa DejaVu Serif.

## Przyczyna
Plik `new-layout.css` zawierał definicje `@font-face` dla fontu Lato z relatywnymi ścieżkami do plików `.woff` i `.woff2`:
```css
@font-face{
    font-family:Lato;
    src:local("Lato Light"),local("Lato-Light"),
    url(fonts/lato-v17-latin-ext_latin-300.woff2) format("woff2"),
    url(fonts/lato-v17-latin-ext_latin-300.woff) format("woff");
}
```

Te pliki fontów nie istniały w strukturze pluginu (`assets/css/fonts/`), więc przeglądarka nie mogła załadować czcionki i używała fontu zastępczego (DejaVu Serif).

## Rozwiązanie
Dodano Google Fonts jako zależność CSS. Font Lato jest teraz ładowany z CDN Google Fonts:

```php
// W pliku: public/class-assets.php

wp_register_style(
    'google-fonts-lato',
    'https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,300;0,400;0,700;0,900;1,400;1,700&display=swap',
    array(),
    null
);

wp_register_style(
    'develogic-new-layout',
    DEVELOGIC_PLUGIN_URL . 'assets/css/new-layout.css',
    array('google-fonts-lato', 'tippy', 'lightgallery', ...),
    DEVELOGIC_VERSION
);
```

## Korzyści tego rozwiązania

### 1. Nie wymaga lokalnych plików fontów
- Brak potrzeby dodawania plików `.woff`/`.woff2` do repozytorium
- Mniejszy rozmiar pluginu

### 2. Optymalizacja
- Google Fonts automatycznie serwuje najlepszy format dla przeglądarki
- Szybkie CDN Google
- Wsparcie dla `font-display: swap`

### 3. Zawsze aktualne
- Google zarządza plikami fontów
- Automatyczne wsparcie dla nowych przeglądarek

### 4. Cache przeglądarki
- Jeśli użytkownik odwiedził wcześniej strony używające Lato z Google Fonts, czcionka może być już w cache

## Wagi fontu Lato załadowane z Google Fonts

- **300** (Light)
- **400** (Regular)
- **400 italic**
- **700** (Bold)
- **700 italic**
- **900** (Black)

## Alternatywne rozwiązanie (pliki lokalne)

Jeśli preferujesz hosting lokalny fontów:

1. Pobierz pliki Lato z [Google Fonts](https://fonts.google.com/specimen/Lato)
2. Umieść je w: `assets/css/fonts/`
3. Usuń `google-fonts-lato` z zależności w `class-assets.php`
4. Pozostaw definicje `@font-face` w `new-layout.css` (są już tam)

Struktura:
```
assets/
└── css/
    ├── fonts/
    │   ├── lato-v17-latin-ext_latin-300.woff
    │   ├── lato-v17-latin-ext_latin-300.woff2
    │   ├── lato-v17-latin-ext_latin-regular.woff
    │   ├── lato-v17-latin-ext_latin-regular.woff2
    │   ├── lato-v17-latin-ext_latin-700.woff
    │   ├── lato-v17-latin-ext_latin-700.woff2
    │   └── ... (inne wagi)
    └── new-layout.css
```

## Weryfikacja

Po wdrożeniu poprawki:

1. Otwórz stronę z shortcode `[develogic_apartments_list_new]`
2. Otwórz DevTools (F12)
3. Przejdź do zakładki **Network** → **Font**
4. Odśwież stronę
5. Powinieneś zobaczyć żądania do `fonts.gstatic.com` z fontami Lato

Lub:

1. Sprawdź Computed styles w DevTools
2. Wybierz element z tekstem
3. W zakładce **Computed** sprawdź `font-family`
4. Powinno być: `Lato, -apple-system, BlinkMacSystemFont, ...`

## Data poprawki
2025-10-29

