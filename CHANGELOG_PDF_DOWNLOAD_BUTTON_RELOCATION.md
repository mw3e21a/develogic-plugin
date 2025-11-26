# Changelog: Relokacja przycisku pobierania karty mieszkania

**Data:** 2025-11-26

## Zmiany

### 1. Przeniesienie przycisku w modalu szczegółów mieszkania

Przycisk "Pobierz kartę mieszkania" został przeniesiony z dolnej części modalu (poniżej historii cen) na górę, bezpośrednio pod przyciskiem "Zobacz spacer 3D".

### 2. Nowy styl przycisku

Przycisk został przerobiony w stylu podobnym do przycisku "Zobacz spacer 3D":
- Pełna szerokość
- Duży, wyraźny design
- Zielony gradient (odróżniający od fioletowego przycisku 3D)
- Ikona pobierania po lewej stronie
- Strzałka po prawej stronie
- Efekty hover i animacje

## Zmienione pliki

### templates/apartments-list.php
- **Usunięto:** Stary element `.info-box` z przyciskiem `.download-link` (był na dole modalu)
- **Dodano:** Nowy przycisk `.download-card-link` zaraz pod przyciskiem `.tour-3d-link`
- Nowy przycisk używa klasy `.tour-3d-link` dla spójnego stylu + klasa `.download-card-link` dla własnych kolorów

### assets/js/apartments-list.js
- **Zaktualizowano logikę wyświetlania:**
  - Selektor dla przycisku 3D zmieniony na `.tour-3d-link:not(.download-card-link)` aby nie kolidował z nowym przyciskiem
  - Dodano osobną logikę dla `.download-card-link` 
  - Usunięto starą logikę dla `.download-link` i `.info-box`

### assets/css/apartments-list.css
- **Dodano style dla `.download-card-link`:**
  - Gradient zielony: `#28a745` → `#20833b` (vs fioletowy dla 3D)
  - Dostosowane efekty hover z zielonym cieniem
  - Dziedziczy wszystkie pozostałe style z `.tour-3d-link`

## Wygląd

**Przed:**
```
[Zobacz spacer 3D]    <- fioletowy przycisk
[Promocja]            <- banner (jeśli aktywny)
Cechy mieszkania
Cena
Historia cen
[Pobierz kartę mieszkania]  <- mały link na dole
```

**Po:**
```
[Zobacz spacer 3D]          <- fioletowy przycisk
[Pobierz kartę mieszkania]  <- zielony przycisk (NOWY)
[Promocja]                  <- banner (jeśli aktywny)
Cechy mieszkania
Cena
Historia cen
```

## Korzyści

1. **Lepsza widoczność** - przycisk jest teraz w widocznym miejscu, nie trzeba scrollować do końca modalu
2. **Spójny design** - oba główne przyciski akcji (3D i PDF) są teraz obok siebie w tym samym stylu
3. **Lepsza UX** - użytkownik od razu widzi obie główne opcje (spacer 3D i karta do pobrania)
4. **Jasne rozróżnienie** - różne kolory (fioletowy vs zielony) ułatwiają rozpoznanie funkcji

## Testowanie

Sprawdź:
1. ✓ Przycisk "Pobierz kartę mieszkania" pojawia się pod przyciskiem 3D
2. ✓ Przycisk ma zielony gradient (różny od fioletowego przycisku 3D)
3. ✓ Kliknięcie w przycisk otwiera PDF w nowej karcie
4. ✓ Przycisk nie wyświetla się, jeśli brak PDF dla mieszkania
5. ✓ Przycisk 3D działa normalnie
6. ✓ Efekty hover działają poprawnie na obu przyciskach
7. ✓ Na urządzeniach mobilnych oba przyciski są czytelne

