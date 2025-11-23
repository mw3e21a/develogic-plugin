# ✅ Podsumowanie: Scroll do mieszkania z Image Map Pro

## Problem
Po kliknięciu w polygon (mieszkanie) na mapie Image Map Pro, chcesz aby:
1. Strona zjechała w dół do tabeli z listą mieszkań
2. Wybrane mieszkanie było podświetlone (żółte tło + animacja)

## Rozwiązanie
Funkcjonalność **już istnieje** w wtyczce Develogic! Wystarczy ją skonfigurować.

## Co musisz zrobić? (3 kroki)

### 1. W Image Map Pro Editor - ustaw "Title"

W polu **"Title"** polygonu wpisz dokładny numer mieszkania z Develogic.

**Przykład:** `M18`, `44`, `A2-15`

### 2. Dodaj akcję "Run Script"

W sekcji **"Actions"** → **"+ Add Action"** → **"Run Script"**

### 3. Wklej kod

```javascript
develogicScrollToApartment('{{title}}');
```

**Gotowe!** 🎉

## Funkcjonalność

### Co jest już zaimplementowane:

✅ **JavaScript (`apartments-list.js`):**
- Funkcja `window.develogicScrollToApartment(apartmentNumber)`
- Wyszukuje mieszkanie po numerze
- Scrolluje do niego z płynną animacją
- Dodaje klasę `.apartment-highlight`
- Usuwa podświetlenie po 3 sekundach

✅ **CSS (`apartments-list.css`):**
- Klasa `.apartment-highlight` z żółtym tłem (#fff3cd)
- Żółte obramowanie (box-shadow: #ffc107)
- Animacja `highlightPulse` przez 3 sekundy

✅ **Obsługa URL:**
- Można użyć `?mieszkanie=M18` w URL
- Automatyczne scroll przy wejściu na stronę

## Dodatkowe opcje

### Opcja 1: Scroll + czyszczenie filtrów

Jeśli mieszkanie może być ukryte przez filtry:

```javascript
develogicUpdateFilters({ pokoje: 'all', pietro: 'all', budynek: 'all' });
setTimeout(function() { develogicScrollToApartment('{{title}}'); }, 300);
```

### Opcja 2: Scroll + ustawienie konkretnego budynku

```javascript
develogicUpdateFilters({ budynek: 'H' });
setTimeout(function() { develogicScrollToApartment('{{title}}'); }, 300);
```

### Opcja 3: Użycie URL (bez JavaScript)

W Image Map Pro zamiast "Run Script" użyj "Open Link":

**URL:** `?mieszkanie={{title}}#apartments-list`

## Dokumentacja

- **[IMAGE_MAP_PRO_SCROLL_TO_APARTMENT.md](IMAGE_MAP_PRO_SCROLL_TO_APARTMENT.md)** - Pełna dokumentacja z troubleshooting
- **[IMAGE_MAP_PRO_QUICK_GUIDE.md](IMAGE_MAP_PRO_QUICK_GUIDE.md)** - Szybki przewodnik wizualny

## Przykład użycia

### Mieszkanie M18 w budynku H

**W Image Map Pro:**

**Title:** `M18`

**Action - Run Script:**
```javascript
develogicScrollToApartment('{{title}}');
```

**Efekt po kliknięciu:**
1. Strona zjeżdża do sekcji z listą mieszkań
2. Mieszkanie M18 jest wycentrowane na ekranie
3. Mieszkanie świeci na żółto przez 3 sekundy
4. Animacja płynnie zanika

## Bulk Configuration

Dla wielu mieszkań używaj funkcji **"Duplicate Shape"**:

1. Skonfiguruj pierwszy polygon z akcją "Run Script"
2. Duplikuj shape (Duplicate Shape)
3. Przesuń na nowe mieszkanie
4. Zmień tylko pole "Title"
5. Powtórz

**Czas:** ~30 sekund na mieszkanie

## Testowanie

### Quick test:
1. Kliknij na polygon
2. Sprawdź czy:
   - ✅ Scroll działa
   - ✅ Mieszkanie jest podświetlone żółtym tłem
   - ✅ Podświetlenie znika po 3s

### Jeśli nie działa:
- Otwórz konsolę (F12) → szukaj błędów
- Sprawdź czy shortcode `[develogic_apartments_list]` jest na stronie
- Sprawdź czy numer w "Title" jest identyczny jak w liście mieszkań

## Kod źródłowy

### JavaScript (apartments-list.js, linia ~204)
```javascript
window.develogicScrollToApartment = function(apartmentNumber) {
    const url = new URL(window.location.href);
    url.searchParams.set('mieszkanie', apartmentNumber);
    window.history.pushState({}, '', url.toString());
    scrollToApartmentFromUrl();
};
```

### CSS (apartments-list.css, linia ~363)
```css
.apartment-item.apartment-highlight {
    animation: highlightPulse 3s ease-in-out;
    background: #fff3cd;
    box-shadow: 0 0 0 3px #ffc107;
}
```

## Dostępne funkcje globalne

Wtyczka udostępnia następujące funkcje JavaScript:

### `develogicScrollToApartment(apartmentNumber)`
```javascript
develogicScrollToApartment('M18');
```

### `develogicUpdateFilters(params)`
```javascript
develogicUpdateFilters({ 
    pokoje: '3', 
    pietro: '2', 
    budynek: 'H' 
});
```

## Podsumowanie

✅ **Wszystko jest już gotowe** - wystarczy skonfigurować akcję w Image Map Pro
✅ **Prosty kod** - jedna linijka JavaScript
✅ **Działa od razu** - żadnych modyfikacji kodu nie trzeba
✅ **Responsywne** - działa na desktop i mobile
✅ **Ładna animacja** - płynny scroll + żółte podświetlenie

**Gotowe do użycia!** 🚀

