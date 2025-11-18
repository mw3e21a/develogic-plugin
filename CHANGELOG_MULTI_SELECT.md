# Changelog: Multi-select dla mapowania budynków

## Data: 2025-11-18

### 🎯 Nowa funkcjonalność: Jeden budynek → Wiele projektów

#### Problem

Wcześniej można było przypisać jeden budynek tylko do jednego projektu Image Map Pro. To było problematyczne gdy:
- Budynek ma wiele pięter, każde w osobnym projekcie
- Budynek ma różne perspektywy (np. rzut, widok 3D, plan zagospodarowania)

#### Rozwiązanie

Zmieniono UI z pojedynczego `<select>` na **multi-select**, który pozwala wybrać wiele projektów dla jednego budynku.

### Przykład użycia

**Budynek H ma 3 piętra:**
```
Budynek H → [Pietro_1H, Pietro_2H, Pietro_3H]
```

Teraz gdy synchronizujesz lokale z budynku H:
- Lokal na 1 piętrze aktualizuje projekt `Pietro_1H`
- Lokal na 2 piętrze aktualizuje projekt `Pietro_2H`
- Lokal na 3 piętrze aktualizuje projekt `Pietro_3H`

### Jak używać

1. Przejdź do **Develogic → Image Map Pro**
2. W sekcji "Mapowanie budynków":
   - Zobaczysz multi-select (większe pole z listą)
   - **Przytrzymaj Ctrl** (lub Cmd na Mac)
   - Klikaj na projekty które chcesz przypisać
3. Kliknij **"Zapisz mapowania"**

### Backwards compatibility

Stare mapowania (jeden budynek → jeden projekt) są automatycznie konwertowane na nowy format (tablice).

**Format w bazie:**

**Stary:**
```php
array(
    'H' => 'Pietro_3H',  // string
)
```

**Nowy:**
```php
array(
    'H' => array('Pietro_1H', 'Pietro_2H', 'Pietro_3H'),  // array
)
```

Kod automatycznie obsługuje oba formaty.

### Zmiany techniczne

#### Pliki zmodyfikowane

**admin/class-admin-imagemappro.php:**
- Zmieniono `<select>` na `<select multiple>`
- Zaktualizowano funkcję `save_mappings()` aby obsługiwała tablice
- Dodano backwards compatibility dla starych mapowań
- Dodano licznik przypisań w "Informacje techniczne"

**includes/class-imagemappro-integration.php:**
- Zmieniono logikę dopasowania z pojedynczego shortcode na tablicę
- Dodano `in_array()` check zamiast `===`
- Zachowano backwards compatibility

#### API

Format w bazie danych:
```php
get_option('develogic_imagemappro_building_map')
// Returns:
array(
    'H' => array('Pietro_1H', 'Pietro_2H', 'Pietro_3H'),
    'G' => array('Pietro_3G'),
)
```

### Testowanie

1. Wybierz wiele projektów dla budynku
2. Zapisz
3. Kliknij "Aktualizuj kolory teraz"
4. Sprawdź logi - powinny pokazywać:
```
Matched project Pietro_1H to building NAME: H
Matched project Pietro_2H to building NAME: H
Matched project Pietro_3H to building NAME: H
```

### Korzyści

- ✅ Elastyczność - jeden budynek może mieć dowolnie wiele projektów
- ✅ Organizacja - łatwe zarządzanie różnymi piętrami/perspektywami
- ✅ Backwards compatibility - stare konfiguracje działają
- ✅ Intuicyjny UI - standardowy multi-select z tooltipem

---

**Wersja:** 2.2.0+  
**Autor:** Michal  
**Data:** 2025-11-18

