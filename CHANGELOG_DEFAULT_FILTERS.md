# Changelog: Domyślne parametry filtrowania w shortcode

## Data: 2025-11-23

## Zmiana
Dodano możliwość ustawienia domyślnych wartości filtrów bezpośrednio w shortcode `[develogic_apartments_list]`.

## Nowe parametry shortcode

### Parametry filtrowania

| Parametr | Typ | Opis | Przykład |
|----------|-----|------|----------|
| `rooms` | string/int | Domyślna liczba pokoi (1-5) | `rooms="3"` |
| `floor` | string/int | Domyślne piętro (-1 dla piwnicy, 0 dla parteru, 1-4 dla pięter) | `floor="2"` |
| `building` | string | Nazwa budynku (np. "G", "H") - zalecane | `building="H"` |
| `building_id` | int | ID budynku (legacy, dla kompatybilności) | `building_id="123"` |
| `min_area` | float | Minimalny metraż w m² | `min_area="50"` |
| `max_area` | float | Maksymalny metraż w m² | `max_area="100"` |
| `min_price_gross` | float | Minimalna cena brutto | `min_price_gross="300000"` |
| `max_price_gross` | float | Maksymalna cena brutto | `max_price_gross="500000"` |
| `status` | string/array | Domyślny status lokali | `status="Wolny"` |

## Przykłady użycia

### Podstawowe filtrowanie

#### Pokaż tylko 3-pokojowe mieszkania
```
[develogic_apartments_list rooms="3"]
```

#### Pokaż mieszkania na 2 piętrze
```
[develogic_apartments_list floor="2"]
```

#### Pokaż mieszkania w konkretnym budynku
```
[develogic_apartments_list building="H"]
```

#### Pokaż mieszkania w kilku budynkach
```
[develogic_apartments_list building="G,H"]
```

### Zaawansowane filtrowanie

#### 3-pokojowe mieszkania o metrażu 60-80m²
```
[develogic_apartments_list rooms="3" min_area="60" max_area="80"]
```

#### Mieszkania na parterze, cena do 400 tys.
```
[develogic_apartments_list floor="0" max_price_gross="400000"]
```

#### Mieszkania w budynku H, piętro 2-3, do 500 tys.
```
[develogic_apartments_list building="H" floor="2" max_price_gross="500000"]
```

#### 2-pokojowe, 40-60m², budynek H
```
[develogic_apartments_list 
    building="H" 
    rooms="2" 
    min_area="40" 
    max_area="60"
]
```

### Kombinacja z innymi parametrami

#### Wyświetl tylko wolne mieszkania w konkretnym budynku
```
[develogic_apartments_list 
    investment_id="1" 
    building="H"
    status="Wolny"
    title="Dostępne mieszkania w budynku H"
]
```

#### Konkretna oferta dla inwestora
```
[develogic_apartments_list 
    investment_id="1"
    rooms="3"
    floor="2"
    min_area="60"
    max_area="80"
    min_price_gross="350000"
    max_price_gross="450000"
    sort_by="priceGross"
    sort_dir="asc"
    title="Mieszkania 3-pokojowe w promocji"
]
```

## Jak to działa

1. **Domyślne wartości w UI** - parametry `building`, `rooms`, `floor`, `min_area`, `max_area`, `min_price_gross`, `max_price_gross` ustawiają **tylko domyślne wartości** w interfejsie użytkownika (selecty, inputy, chipy). Użytkownik widzi od razu zastosowane filtry, ale może je swobodnie zmienić.

2. **Filtrowanie po stronie JavaScript** - po załadowaniu strony, JavaScript automatycznie filtruje mieszkania na podstawie ustawionych domyślnych wartości. Gdy użytkownik zmienia filtry, JavaScript natychmiast aktualizuje widoczne mieszkania.

3. **Wszystkie dane dostępne** - **wszystkie mieszkania są wysyłane do przeglądarki**, dzięki czemu użytkownik może zmienić filtr i zobaczyć inne mieszkania (np. zmienić piętro z 0 na 2, zmienić budynek z H na G, itp.).

4. **Współpraca z URL filters** - parametry z URL (`?pokoje=3&pietro=2`) mają priorytet nad parametrami shortcode.

5. **Wyjątek: parametr `status`** - tylko ten parametr filtruje dane po stronie serwera (zgodnie z ustawieniami widoczności statusów), ponieważ kontroluje on, które statusy mieszkań w ogóle powinny być dostępne.

## Uwagi techniczne

- **Wszystkie parametry są opcjonalne**
- **Parametry ustawiają tylko domyślne wartości UI** - nie filtrują danych po stronie serwera (oprócz `status`)
- **Użytkownik może swobodnie zmienić filtry** - wszystkie mieszkania są dostępne do przeglądania
- Filtrowanie działa po stronie JavaScript w przeglądarce
- Parametr `building` akceptuje nazwę budynku (np. "H", "G") lub wiele budynków oddzielonych przecinkiem (np. "G,H")
- Parametr `building_id` jest wspierany dla kompatybilności wstecznej, ale zalecane jest użycie `building`
- **Wyjątek:** Parametr `status` jest jedynym parametrem, który faktycznie filtruje dane po stronie serwera (może nadpisać domyślne ustawienie `visible_statuses` z panelu admina)

## Przykłady zastosowań biznesowych

### Landing page dla konkretnego budynku
```
[develogic_apartments_list 
    building="H" 
    title="Mieszkania w budynku H"
    show_counters="true"
]
```
**Uwaga:** Domyślnie będą widoczne mieszkania z budynku H, ale użytkownik może wybrać inny budynek z listy.

### Landing page dla kilku budynków
```
[develogic_apartments_list 
    building="G,H" 
    title="Mieszkania w budynkach G i H"
    show_counters="true"
]
```
**Uwaga:** Domyślnie będą widoczne mieszkania z budynków G i H, ale użytkownik może wybrać inny budynek lub wszystkie budynki.

### Strona z ofertami premium (większe mieszkania)
```
[develogic_apartments_list 
    min_area="80" 
    rooms="4"
    title="Apartamenty premium"
]
```
**Uwaga:** Domyślnie pokażą się tylko 4-pokojowe powyżej 80m², ale użytkownik może zmienić te filtry aby zobaczyć inne mieszkania.

### Strona dla młodych par (małe mieszkania w dobrej cenie)
```
[develogic_apartments_list 
    rooms="2" 
    max_area="50"
    max_price_gross="350000"
    title="Mieszkania dla młodych"
]
```

### Oferty na konkretne piętro (np. parter dla seniorów)
```
[develogic_apartments_list 
    floor="0"
    title="Mieszkania na parterze"
]
```

## Zgodność

- **Wersja wtyczki**: 2.0+
- **Shortcode**: `[develogic_apartments_list]`
- **Backward compatibility**: Tak - istniejące shortcode'y bez nowych parametrów działają jak wcześniej

