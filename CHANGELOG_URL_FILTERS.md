# Changelog: Filtrowanie przez URL

## Data: 2024-11-19

### Dodane
- Możliwość predefiniowania filtrów mieszkań przez parametry URL
- Funkcja `applyUrlFilters()` w `apartments-list.js`
- Automatyczne wykrywanie zmian URL (history.pushState/replaceState)
- **Scroll do konkretnego mieszkania** z parametrem `?mieszkanie=M18` lub `?m=M18`
- **Podświetlanie mieszkania** żółtą ramką przez 3 sekundy
- Funkcja `scrollToApartmentFromUrl()` z automatyczną detekcją numeru mieszkania
- Globalna funkcja `window.develogicScrollToApartment(number)`
- Dokumentacja użycia w `URL_FILTERS_USAGE.md`
- Przykład testowy w `examples/test-url-filters.html`

### Zmodyfikowane pliki
- `assets/js/apartments-list.js`
  - Dodano funkcję `applyUrlFilters()`
  - Dodano funkcję `scrollToApartmentFromUrl()`
  - Wywołanie `applyUrlFilters()` w funkcji `init()`
  - Monkey-patching `history.pushState()` i `history.replaceState()`
  - Automatyczne wykrywanie zmian URL bez przeładowania strony
  - Event listener dla `popstate` (back/forward w przeglądarce)
  - Event listener dla custom event `urlchange`
  - Globalna funkcja pomocnicza `window.develogicUpdateFilters()`
  - Globalna funkcja pomocnicza `window.develogicScrollToApartment()`
- `assets/css/apartments-list.css`
  - Dodano klasę `.apartment-highlight` z animacją podświetlenia
  - Dodano keyframes `highlightPulse` dla efektu żółtego świecenia

### Dostępne parametry URL
- `pokoje` - ilość pokoi (1, 2, 3, 4, 5, all)
- `pietro` - piętro (-1, 0, 1, 2, 3, 4, all)
- `budynek` - nazwa budynku
- `typ_lokalu` - typ lokalu
- `metraz_od` - minimalna powierzchnia
- `metraz_do` - maksymalna powierzchnia
- `cena_od` - minimalna cena
- `cena_do` - maksymalna cena
- `promocja` - tylko promocje (1/true)
- `2_lazienki` - tylko z 2 łazienkami (1/true)
- `garderoba` - tylko z garderobą (1/true)
- `mieszkanie` lub `m` - numer mieszkania do scroll + highlight (np. M18)

### Przykłady użycia

#### Mieszkania na 3 piętrze
```
?pietro=3
```

#### 2-pokojowe w promocji
```
?pokoje=2&promocja=1
```

#### Kompleksowe filtrowanie
```
?pokoje=3&pietro=2&metraz_od=50&metraz_do=70&cena_do=500000
```

#### Scroll do konkretnego mieszkania
```
?mieszkanie=M18
?m=M18
```

#### Mieszkanie z filtrem
```
?pietro=3&mieszkanie=M18
```

### Zastosowania
1. Kampanie marketingowe z dedykowanymi linkami
2. Emaile do klientów z predefiniowanymi kryteriami
3. Kody QR z konkretnymi filtrami
4. Landing pages z automatycznym filtrowaniem
5. Współpraca z agentami - szybki dostęp do konkretnych mieszkań

### Kompatybilność
- Działa z istniejącym systemem filtrowania
- Nie wymaga zmian w PHP/backend
- Użytkownik może dalej modyfikować filtry ręcznie
- Przycisk "Resetuj filtry" czyści wszystkie filtry (URL i manualne)
- **Automatycznie wykrywa zmiany URL** przez `history.pushState()` i `history.replaceState()`
- Obsługuje przyciski wstecz/dalej w przeglądarce (`popstate`)
- Kompatybilne z własnymi funkcjami JavaScript zmieniającymi URL

### Bezpieczeństwo
- Wszystkie wartości są walidowane po stronie JS
- Niewłaściwe wartości są ignorowane
- Brak wpływu na backend/bazę danych
- Tylko odczyt parametrów, bez zapisu do URL (poza istniejącą funkcją favorites)

