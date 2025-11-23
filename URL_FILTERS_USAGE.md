# Filtrowanie mieszkań przez URL

## Opis

Plugin umożliwia predefiniowanie filtrów mieszkań poprzez parametry URL. Dzięki temu można tworzyć bezpośrednie linki do konkretnych zestawów mieszkań (np. tylko mieszkania na 3 piętrze, tylko 2-pokojowe, itp.).

## Dostępne parametry URL

### Podstawowe filtry

| Parametr | Opis | Przykładowe wartości |
|----------|------|---------------------|
| `pokoje` | Ilość pokoi | `1`, `2`, `3`, `4`, `5` (5+), `all` |
| `pietro` | Piętro | `-1` (piwnica), `0` (parter), `1`, `2`, `3`, `4`, `all` |
| `budynek` | Nazwa budynku | Zgodna z nazwą budynku w systemie, `all` |
| `typ_lokalu` | Typ lokalu | `Lokal mieszkalny`, `Lokal użytkowy`, itp., `all` |

### Zakresy wartości

| Parametr | Opis | Przykładowe wartości |
|----------|------|---------------------|
| `metraz_od` | Minimalna powierzchnia (m²) | `30`, `45.5`, `60` |
| `metraz_do` | Maksymalna powierzchnia (m²) | `80`, `100`, `120.5` |
| `cena_od` | Minimalna cena (zł) | `200000`, `350000`, `500000` |
| `cena_do` | Maksymalna cena (zł) | `400000`, `600000`, `800000` |

### Opcje dodatkowe

| Parametr | Opis | Wartości |
|----------|------|---------|
| `promocja` | Tylko mieszkania w promocji | `1` lub `true` |
| `2_lazienki` | Tylko mieszkania z 2 łazienkami | `1` lub `true` |
| `garderoba` | Tylko mieszkania z garderobą | `1` lub `true` |

### Scroll do konkretnego mieszkania

| Parametr | Opis | Przykładowe wartości |
|----------|------|---------------------|
| `mieszkanie` lub `m` | Numer mieszkania do którego ma przescrollować | `M18`, `A12`, `B5` |

**Jak to działa:**
- Strona automatycznie przescrolluje do danego mieszkania
- Mieszkanie zostanie podświetlone na żółto przez 3 sekundy
- Jeśli mieszkanie jest ukryte przez filtry, nic się nie stanie

## Przykłady użycia

### Przykład 1: Mieszkania na 3 piętrze
```
https://twoja-domena.pl/mieszkania/?pietro=3
```

### Przykład 2: 2-pokojowe mieszkania w promocji
```
https://twoja-domena.pl/mieszkania/?pokoje=2&promocja=1
```

### Przykład 3: Mieszkania w zakresie 40-60 m²
```
https://twoja-domena.pl/mieszkania/?metraz_od=40&metraz_do=60
```

### Przykład 4: Budynek A, piętro 3, z garderobą
```
https://twoja-domena.pl/mieszkania/?budynek=A&pietro=3&garderoba=1
```

### Przykład 5: Mieszkania 3-pokojowe, cena do 500000 zł
```
https://twoja-domena.pl/mieszkania/?pokoje=3&cena_do=500000
```

### Przykład 6: Kompleksowe filtrowanie
```
https://twoja-domena.pl/mieszkania/?pokoje=2&pietro=2&metraz_od=45&metraz_do=65&cena_do=450000&promocja=1
```

### Przykład 7: Scroll do konkretnego mieszkania
```
https://twoja-domena.pl/mieszkania/?mieszkanie=M18
```
lub krócej:
```
https://twoja-domena.pl/mieszkania/?m=M18
```

### Przykład 8: Mieszkanie M18 na 3 piętrze
```
https://twoja-domena.pl/mieszkania/?pietro=3&mieszkanie=M18
```

## Zastosowania praktyczne

### 1. Marketing i reklama
Tworzenie dedykowanych linków dla różnych kampanii reklamowych:
- Facebook: Mieszkania 2-pokojowe w promocji
- Google Ads: Mieszkania na parterze
- Newsletter: Nowości poniżej 400 000 zł

### 2. Agenci sprzedaży
Szybki dostęp do konkretnych mieszkań podczas rozmów z klientami:
```
https://twoja-domena.pl/mieszkania/?pokoje=3&pietro=3
```

Link do konkretnego mieszkania:
```
https://twoja-domena.pl/mieszkania/?mieszkanie=M18
```
Strona automatycznie przescrolluje do mieszkania i podświetli je.

### 3. Emaile do klientów
Wysyłanie spersonalizowanych linków z mieszkaniami spełniającymi kryteria klienta:
```
Witam,

Zgodnie z Pana preferencjami przygotowałem listę mieszkań:
https://twoja-domena.pl/mieszkania/?pokoje=2&metraz_od=45&metraz_do=60&pietro=1

Szczególnie polecam mieszkanie M18:
https://twoja-domena.pl/mieszkania/?mieszkanie=M18

Pozdrawiam
```

### 4. Kody QR
Tworzenie kodów QR z konkretnymi filtrami do materiałów drukowanych.

### 5. Landing pages
Dedykowane strony docelowe z predefiniowanymi kryteriami wyszukiwania.

## Jak to działa technicznie

1. Po załadowaniu strony JavaScript odczytuje parametry z URL
2. Automatycznie ustawia odpowiednie wartości w filtrach
3. Aplikuje filtry i wyświetla pasujące mieszkania
4. Użytkownik może dalej modyfikować filtry ręcznie
5. Nasłuchiwanie na zmiany w historii przeglądarki (back/forward)
6. Automatyczna reakcja na zmiany URL bez przeładowania strony

## Dynamiczne zmiany filtrów (bez przeładowania)

Wtyczka **automatycznie wykrywa** zmiany w URL i aplikuje filtry. Działa to z:
- `history.pushState()`
- `history.replaceState()`
- Przyciskami "wstecz" i "dalej" w przeglądarce

### Metoda 1: Używając `history.pushState()` (ZALECANE)

```javascript
function setFloor(floor) {
    // Zmień URL - filtry zostaną automatycznie zastosowane!
    const newUrl = window.location.pathname + '?pietro=' + floor;
    history.pushState({}, '', newUrl);
}
```

### Metoda 2: Używając funkcji pomocniczej `develogicUpdateFilters()`

```javascript
// Zmień filtry bez przeładowania strony
window.develogicUpdateFilters({
    pietro: 3,
    pokoje: 2,
    promocja: 1
});
```

### Przykłady użycia w kodzie

#### Przykład 1: Własna funkcja zmiany piętra
```javascript
function setFloor(floor) {
    const newUrl = window.location.pathname + '?pietro=' + floor;
    history.pushState({}, '', newUrl);
    // Filtry automatycznie się zastosują!
}
```

```html
<button onclick="setFloor(0)">Parter</button>
<button onclick="setFloor(1)">Piętro 1</button>
<button onclick="setFloor(2)">Piętro 2</button>
<button onclick="setFloor(3)">Piętro 3</button>
```

#### Przykład 2: Przyciski szybkiego filtrowania
```html
<button onclick="develogicUpdateFilters({ pietro: 3 })">
    Pokaż piętro 3
</button>
<button onclick="develogicUpdateFilters({ pokoje: 2, promocja: 1 })">
    2-pokojowe w promocji
</button>
```

#### Przykład 3: Łączenie parametrów
```javascript
function showPromotions(floor) {
    const newUrl = window.location.pathname + '?pietro=' + floor + '&promocja=1';
    history.pushState({}, '', newUrl);
}
```

#### Przykład 4: Reset konkretnych filtrów
```javascript
// Usuń filtr piętra (ustaw na null lub 'all')
window.develogicUpdateFilters({
    pietro: null
});
```

#### Przykład 5: Kompletne filtrowanie (z develogicUpdateFilters)
```javascript
// Kompleksowe filtrowanie
window.develogicUpdateFilters({
    pokoje: 3,
    pietro: 2,
    metraz_od: 50,
    metraz_do: 70,
    cena_do: 500000,
    promocja: 1
});
```

#### Przykład 6: Integracja z własnym formularzem
```javascript
document.getElementById('myCustomForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    window.develogicUpdateFilters({
        pokoje: document.getElementById('myRooms').value,
        pietro: document.getElementById('myFloor').value,
        budynek: document.getElementById('myBuilding').value
    });
});
```

#### Przykład 7: Scroll do konkretnego mieszkania
```javascript
// Prosty scroll do mieszkania M18
window.develogicScrollToApartment('M18');

// Lub z filtrowaniem piętra
window.develogicUpdateFilters({ pietro: 3 });
window.develogicScrollToApartment('M18');
```

#### Przykład 8: Link do konkretnego mieszkania
```html
<a href="?mieszkanie=M18">Zobacz mieszkanie M18</a>
<a href="?pietro=3&mieszkanie=M18">M18 na 3 piętrze</a>
```

## Notatki

- Parametry URL można łączyć dowolnie
- Jeśli parametr nie jest rozpoznany, zostanie zignorowany
- Wartości parametrów muszą być zgodne z wartościami w systemie (np. dokładna nazwa budynku)
- Użytkownik może zawsze zresetować filtry przyciskiem "Resetuj filtry"
- Parametry są case-sensitive (rozróżniają wielkie i małe litery)

## Changelog

### Wersja 1.0 (2024)
- Dodano obsługę filtrowania przez URL
- Obsługa wszystkich podstawowych filtrów
- Obsługa zakresów (metraż, cena)
- Obsługa opcji dodatkowych (promocja, łazienki, garderoba)

