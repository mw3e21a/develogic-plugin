# Changelog: Naprawa wyświetlania filtrów i sortowania na mobile

## Data: 2025-11-14

### Problemy
1. Filtry metrażu (m²) i ceny (zł) na urządzeniach mobilnych były zbyt szerokie i wychodziły poza ekran z boku
2. Licznik obserwowanych ("1 obserwowane mieszkanie") nie był widoczny na mobile
3. Sekcja "Sortuj po" była zbyt szeroka i nie pokazywała wszystkich opcji sortowania

### Rozwiązania
Dodano responsywne style CSS dla urządzeń mobilnych, które zapewniają:
- Poprawne dopasowanie szerokości inputów filtrów do ekranu
- Widoczność licznika obserwowanych mieszkań
- Zawijanie opcji sortowania z lepszą czytelnością
- Zachowanie czytelności z mniejszymi czcionkami
- Prawidłowe działanie layoutu na wszystkich rozmiarach ekranów

### Zmiany w plikach

#### `assets/css/apartments-list.css`

**1. Media query dla ekranów ≤768px:**

a) Filtry metrażu i ceny:
```css
.filter-range {
    flex-wrap: nowrap;
    width: 100%;
}

.filter-input {
    min-width: 0;
    width: 100%;
    font-size: 13px;
    padding: 10px 8px;
}

.filter-separator {
    flex-shrink: 0;
    font-size: 13px;
}
```

b) Licznik obserwowanych i przyciski:
```css
.toggle-buttons-wrapper {
    width: 100%;
    flex-wrap: wrap;
    gap: 10px;
}

.favorites-count {
    width: 100%;
    display: block;
    font-size: 13px;
    margin-top: 8px;
    padding-left: 4px;
}
```

c) Opcje sortowania:
```css
.sort-bar-right {
    width: 100%;
    margin-left: 0;
    justify-content: flex-start;
    border-top: 1px solid #e5e5e5;
    padding-top: 15px;
    margin-top: 15px;
    flex-wrap: wrap;
    gap: 12px 16px;
}

.sort-label {
    width: 100%;
    font-size: 13px;
    margin-bottom: 4px;
    font-weight: 600;
}

.sort-option {
    font-size: 13px;
    padding: 6px 12px;
    border: 1px solid #e5e5e5;
    border-radius: 6px;
    background: white;
}
```

**2. Media query dla ekranów ≤480px (małe smartfony):**
```css
/* Filtry */
.filter-input {
    font-size: 12px;
    padding: 8px 6px;
}

.filter-input::placeholder {
    font-size: 11px;
}

.filter-separator {
    font-size: 12px;
    padding: 0 2px;
}

.filter-label {
    font-size: 12px;
}

.filter-select {
    font-size: 13px;
    padding: 9px 12px;
}

.filter-chip {
    font-size: 13px;
    padding: 7px 14px;
}

/* Przyciski i sortowanie */
.favorites-toggle-btn {
    font-size: 13px;
    padding: 8px 16px;
}

.favorites-toggle-btn svg {
    width: 16px;
    height: 16px;
}

.sort-option {
    font-size: 12px;
    padding: 5px 10px;
}

.favorites-count {
    font-size: 12px;
}

.sort-label {
    font-size: 12px;
}
```

### Kluczowe zmiany

**Filtry:**
1. **`min-width: 0`** - Pozwala inputom się kurczyć poniżej domyślnego minimum
2. **`width: 100%`** - Inputy zajmują dostępną przestrzeń w kontenerze flex
3. **`flex-shrink: 0` na separator** - Separator "-" nie będzie się kurczył

**Licznik obserwowanych:**
4. **`width: 100%`** - Licznik zajmuje pełną szerokość, więc jest zawsze widoczny
5. **`display: block`** - Wyświetlany w osobnej linii pod przyciskami
6. **`flex-wrap: wrap`** na wrapper - Pozwala na zawijanie elementów

**Sortowanie:**
7. **`flex-wrap: wrap`** - Opcje sortowania zawijają się do nowych linii
8. **`width: 100%` na label** - Label "Sortuj po:" w osobnej linii
9. **Border i background na opcjach** - Lepiej widoczne przyciski sortowania
10. **Mniejsze czcionki i paddingi** - Dopasowane do rozmiaru ekranu

### Testowanie

1. Otwórz stronę z listą mieszkań na urządzeniu mobilnym (lub w trybie mobilnym w DevTools)
2. Sprawdź filtry "Metraż (m²)" i "Cena (zł)":
   - Inputy mieszczą się w szerokości ekranu
   - Separator "-" jest widoczny między inputami
   - Można wygodnie wpisywać wartości
3. Sprawdź sekcję z przyciskami "Wszystkie" / "Obserwowane":
   - Licznik "X obserwowanych" jest widoczny pod przyciskami
4. Sprawdź sekcję "Sortuj po":
   - Wszystkie opcje sortowania są widoczne (Piętro, Metraż, Pokoje, Cena, Cena m²)
   - Opcje zawijają się do nowych linii
   - Aktywna opcja jest wyraźnie zaznaczona
5. Upewnij się, że layout nie psuje się na różnych rozmiarach ekranu (320px - 768px)

### Breakpointy

- **768px i mniej**: Standardowe urządzenia mobilne (tablety w pionie, smartfony)
- **480px i mniej**: Małe smartfony (dodatkowa optymalizacja czcionek i paddingów)

### Kompatybilność

- Zmiana jest w pełni wstecznie kompatybilna
- Nie wpływa na wyświetlanie na desktopie
- Działa we wszystkich nowoczesnych przeglądarkach

