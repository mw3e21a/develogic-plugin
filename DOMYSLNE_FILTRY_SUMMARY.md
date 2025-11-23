# Podsumowanie: Domyślne parametry filtrowania w shortcode

## Co zostało dodane?

Do shortcode'a `[develogic_apartments_list]` dodano możliwość ustawienia domyślnych wartości filtrów.

## Nowe parametry

| Parametr | Przykład | Opis |
|----------|----------|------|
| `building` | `building="H"` | Nazwa budynku (zalecane) |
| `building` | `building="G,H"` | Wiele budynków oddzielonych przecinkiem |
| `rooms` | `rooms="3"` | Domyślna liczba pokoi (1-5) |
| `floor` | `floor="2"` | Domyślne piętro (-1 do 4) |
| `min_area` | `min_area="50"` | Minimalny metraż |
| `max_area` | `max_area="100"` | Maksymalny metraż |
| `min_price_gross` | `min_price_gross="300000"` | Minimalna cena |
| `max_price_gross` | `max_price_gross="500000"` | Maksymalna cena |
| `status` | `status="Wolny"` | Domyślny status |

## Przykłady użycia

### Prosty przykład - budynek H
```
[develogic_apartments_list building="H"]
```
Pokaże tylko mieszkania w budynku H, z filtrem budynku już zaznaczonym.

### Prosty przykład - pokoje i piętro
```
[develogic_apartments_list rooms="3" floor="2"]
```
Pokaże mieszkania 3-pokojowe na 2 piętrze, z filtrem już zaznaczonym.

### Zaawansowany przykład
```
[develogic_apartments_list 
    building="H" 
    rooms="3" 
    min_area="60" 
    max_area="80"
    min_price_gross="350000"
    max_price_gross="450000"
    title="Mieszkania 3-pokojowe w budynku H"]
```

### Wiele budynków
```
[develogic_apartments_list 
    building="G,H" 
    title="Mieszkania w budynkach G i H"]
```

## Jak to działa?

1. **Domyślne wartości w UI** - parametry ustawiają wartości początkowe w filtrach (selecty, inputy, chipy)
2. **Filtrowanie po stronie JavaScript** - filtry działają w przeglądarce, użytkownik widzi od razu przefiltrowane mieszkania
3. **Wszystkie dane dostępne** - wszystkie mieszkania są załadowane, użytkownik może swobodnie zmienić filtry i zobaczyć inne mieszkania
4. **URL ma priorytet** - parametry z URL (`?pokoje=3`) nadpisują wartości z shortcode
5. **Wyjątek:** tylko parametr `status` faktycznie filtruje dane po stronie serwera (kontroluje widoczność statusów)

## Zmienione pliki

1. `public/class-shortcodes.php` - dodano parametry, **usunięto twarde filtrowanie po stronie serwera** (oprócz status)
2. `templates/apartments-list.php` - dodano ustawianie domyślnych wartości w UI
3. `includes/class-filter-sort.php` - dodano obsługę filtra `building` (nazwa budynku)

## Ważna uwaga o architekturze

**Parametry z shortcode NIE filtrują danych po stronie serwera** (oprócz `status`). Wszystkie mieszkania są wysyłane do przeglądarki, a JavaScript filtruje je dynamicznie na podstawie ustawień UI. Dzięki temu użytkownik może swobodnie zmieniać filtry i przeglądać różne mieszkania.

## Dokumentacja

- Szczegółowa dokumentacja: `CHANGELOG_DEFAULT_FILTERS.md`
- Przykłady użycia: `EXAMPLE_USAGE.md` (sekcja 2a)

## Testowanie

### Test 1: Podstawowe filtrowanie
```
[develogic_apartments_list rooms="3"]
```
Sprawdź czy:
- Po załadowaniu strony widoczne są tylko mieszkania 3-pokojowe
- Chip "3" jest zaznaczony jako aktywny
- Po zmianie na "2 pokoje" lista się aktualizuje i pokazuje 2-pokojowe

### Test 2: Zakres metrażu
```
[develogic_apartments_list min_area="60" max_area="80"]
```
Sprawdź czy:
- Po załadowaniu strony widoczne są tylko mieszkania 60-80m²
- Pola "od" i "do" mają wartości 60 i 80
- Po zmianie zakresu (np. 40-100) lista pokazuje wszystkie mieszkania w nowym zakresie

### Test 3: Kombinacja filtrów
```
[develogic_apartments_list building="H" floor="2" rooms="3"]
```
Sprawdź czy:
- Po załadowaniu strony widoczne są tylko mieszkania z budynku H, piętro 2, 3 pokoje
- Wszystkie filtry są odpowiednio zaznaczone
- Po zmianie budynku na "G" lista pokazuje mieszkania z budynku G (piętro 2, 3 pokoje)
- Po zmianie piętra na "0" lista pokazuje mieszkania z parteru (budynek G, 3 pokoje)

### Test 4: Wiele budynków
```
[develogic_apartments_list building="G,H"]
```
Sprawdź czy:
- Po załadowaniu strony widoczne są mieszkania zarówno z budynku G jak i H
- Select budynku pokazuje "Wszystkie budynki" (bo nie jest to jeden konkretny budynek)
- Po wybraniu konkretnego budynku (np. "I") lista pokazuje mieszkania tylko z tego budynku

### Test 5: Zmiana przez użytkownika
```
[develogic_apartments_list building="H" floor="0"]
```
**To jest KLUCZOWY test dla Twojego use case!**

Po załadowaniu strony:
1. Sprawdź czy widoczne są mieszkania z budynku H, piętro 0 (parter)
2. Zmień piętro na "2" (Piętro II)
3. **Sprawdź czy lista pokazuje teraz mieszkania z piętra 2** (z budynku H)
4. Zmień budynek na "G"
5. **Sprawdź czy lista pokazuje mieszkania z budynku G, piętro 2**
6. Kliknij "Wszystkie piętra"
7. **Sprawdź czy lista pokazuje wszystkie mieszkania z budynku G** (wszystkie piętra)

Jeśli wszystkie powyższe kroki działają prawidłowo, funkcjonalność działa jak powinna!

## Backward Compatibility

✅ Istniejące shortcode'y bez nowych parametrów działają identycznie jak wcześniej.

## Uwagi dla developera

- Wszystkie parametry są opcjonalne
- Walidacja i sanityzacja po stronie serwera
- Kod jest zgodny z zasadami DRY i KISS
- Żadne debugowe logi nie zostały pozostawione

