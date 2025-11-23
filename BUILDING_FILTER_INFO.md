# Filtrowanie po budynkach - szczegóły implementacji

## Parametr `building` vs `building_id`

### `building` (zalecane)
Filtruje po **nazwie budynku** z API Develogic (np. "G", "H", "Budynek A").

**Zalety:**
- Prostsze w użyciu - nie trzeba szukać ID
- Bardziej czytelne w kodzie shortcode
- Naturalniejsze dla użytkownika końcowego
- Wspiera wiele budynków: `building="G,H"`

**Przykłady:**
```
# Jeden budynek
[develogic_apartments_list building="H"]

# Wiele budynków
[develogic_apartments_list building="G,H,I"]
```

### `building_id` (legacy)
Filtruje po **ID budynku** z API Develogic.

**Kiedy używać:**
- Gdy nazwy budynków mogą się zmieniać
- Gdy chcesz mieć pewność unikalności
- Legacy code / backward compatibility

**Przykład:**
```
[develogic_apartments_list building_id="123"]
```

## Jak działa filtrowanie

### WAŻNE: Parametry shortcode NIE filtrują danych po stronie serwera!

Parametry `building`, `rooms`, `floor`, itp. **tylko ustawiają domyślne wartości w interfejsie użytkownika**. Wszystkie mieszkania są wysyłane do przeglądarki, a filtrowanie odbywa się po stronie JavaScript. Dzięki temu użytkownik może swobodnie zmieniać filtry.

### 1. Ustawienie domyślnej wartości w UI (`apartments-list.php`)

```php
$default_building = '';
if (!empty($atts['building'])) {
    // Użyj nazwy budynku bezpośrednio (tylko pierwszy jeśli wiele)
    $default_building = trim(explode(',', $atts['building'])[0]);
} elseif (!empty($atts['building_id'])) {
    // Legacy support - znajdź nazwę po ID
    foreach ($buildings as $building) {
        if ($building['id'] == $atts['building_id']) {
            $default_building = $building['name'];
            break;
        }
    }
}
```

**Uwaga:** W przypadku wielu budynków (`building="G,H"`), w selectcie pozostaje zaznaczona opcja "Wszystkie budynki", ale **JavaScript automatycznie filtruje mieszkania tylko z budynków G i H**. Jest to celowe zachowanie, bo użytkownik nie wybrał jednego konkretnego budynku, tylko podzbiór budynków.

### 2. Filtrowanie po stronie JavaScript (`apartments-list.js`)

JavaScript czyta wartości z filtrów (selecty, inputy, chipy) i automatycznie ukrywa/pokazuje mieszkania na podstawie tych wartości. Gdy użytkownik zmienia filtr, JavaScript natychmiast aktualizuje widoczne mieszkania.

**Kluczowa różnica:** Wszystkie mieszkania są załadowane w DOM, tylko niektóre są ukryte za pomocą CSS (`display: none`). Dzięki temu użytkownik może swobodnie zmieniać filtry bez przeładowywania strony.

## Przykłady użycia biznesowego

### Landing page dla budynku H
```
[develogic_apartments_list 
    building="H" 
    title="Mieszkania w budynku H"
    show_counters="true"]
```

### Porównanie dwóch budynków
```
[develogic_apartments_list 
    building="G,H" 
    title="Porównaj mieszkania w budynkach G i H"]
```

### Budynek + pokoje + piętro
```
[develogic_apartments_list 
    building="H" 
    rooms="3" 
    floor="2"
    title="Mieszkania 3-pokojowe na II piętrze w budynku H"]
```

### Oferta premium (duże mieszkania w wybranych budynkach)
```
[develogic_apartments_list 
    building="H,I" 
    min_area="80"
    rooms="4"
    title="Apartamenty premium"]
```

## Kompatybilność

- ✅ Backward compatible - `building_id` nadal działa
- ✅ `building` i `building_id` mogą być używane razem (priorytet ma `building`)
- ✅ Jeśli nie podano żadnego parametru, pokazywane są wszystkie budynki
- ✅ Użytkownik może swobodnie zmienić filtr budynku w interfejsie i zobaczyć inne budynki
- ✅ Wszystkie mieszkania są dostępne - parametry shortcode tylko ustawiają domyślne wartości filtrów

## Dane z API

Nazwy budynków pochodzą z pola `building` w danych lokalu z API Develogic:

```json
{
  "localId": 12345,
  "building": "H",
  "buildingId": 67,
  // ...
}
```

Upewnij się, że Twoje dane z API zawierają właściwe nazwy budynków.

