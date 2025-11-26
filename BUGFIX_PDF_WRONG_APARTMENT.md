# Bugfix: Błędne przypisanie PDF-ów do mieszkań

**Data:** 2025-11-26  
**Priorytet:** KRYTYCZNY 🔴

## Problem

Mieszkania miały przypisane PDF-y od **innych mieszkań**:
- M53 miał PDF od M5
- M6 miał PDF od M57
- Każde mieszkanie dostawało PDF z poprzedniego mieszkania w pętli

## Przyczyna

**Błąd kolejności zmiennych w pętli `foreach`** w pliku `templates/apartments-list.php`.

Zmienna `$projections` była **używana przed zdefiniowaniem**:

```php
// BŁĄD - Linia 354: używamy $projections
foreach ($projections as $proj) {
    if (isset($proj['type']) && $proj['type'] === 'Karta lokalu') {
        $pdf_link = $proj['pdf_url'];
        break;
    }
}

// Linia 377: dopiero tutaj definiujemy $projections (ZA PÓŹNO!)
$projections = isset($local['projections']) ? $local['projections'] : array();
```

### Co się działo:

1. **Pierwsza iteracja pętli (M5):** `$projections` nie istnieje → pusty PDF
2. **Druga iteracja (M53):** `$projections` ma wartość **z M5** → M53 dostaje PDF od M5!
3. **Trzecia iteracja (M57):** `$projections` ma wartość **z M53** → M57 dostaje PDF od M53!
4. I tak dalej...

## Rozwiązanie

Przeniesiono definicję `$projections` **przed** jej użyciem:

```php
<?php foreach ($locals as $local): 
    // POPRAWKA: Projections - MUST be loaded first before using
    $projections = isset($local['projections']) ? $local['projections'] : array();
    
    // Teraz można bezpiecznie używać $projections
    $pdf_link = '';
    foreach ($projections as $proj) {
        if (isset($proj['type']) && $proj['type'] === 'Karta lokalu' && !empty($proj['pdf_url'])) {
            $pdf_link = $proj['pdf_url'];
            break;
        }
    }
    // ... rest of the code
?>
```

## Zmienione pliki

### templates/apartments-list.php
- **Linia ~328:** Przeniesiono `$projections = isset($local['projections'])...` na początek pętli
- **Usunięto:** Duplikat definicji `$projections` z linii 377

## Impact

- ✅ Każde mieszkanie ma teraz **swój własny** PDF
- ✅ M53 ma PDF od M53
- ✅ M6 ma PDF od M6
- ✅ Wszyscy użytkownicy pobiorą poprawne karty mieszkań

## Testowanie

Po wdrożeniu należy sprawdzić:

1. ✓ Odśwież stronę z listą mieszkań
2. ✓ Kliknij "Pobierz kartę mieszkania" dla M53 → Powinien otworzyć PDF M53
3. ✓ Kliknij "Pobierz kartę mieszkania" dla M6 → Powinien otworzyć PDF M6
4. ✓ Sprawdź kilka innych mieszkań losowo
5. ✓ Sprawdź w konsoli przeglądarki czy nie ma błędów JavaScript

## Lekcja

**Zawsze definiuj zmienne PRZED ich użyciem!**

Ten typ błędu jest trudny do wychwycenia bo:
- Nie generuje błędu PHP (zmienna istnieje z poprzedniej iteracji)
- Działa "prawie dobrze" (każde mieszkanie ma *jakiś* PDF, tylko nie swój)
- Objawia się dopiero gdy użytkownik zauważy rozbieżność

## Podobne miejsca do sprawdzenia

Wyszukaj w kodzie podobne wzorce gdzie zmienna może być używana przed definicją w pętli:
```bash
grep -n "foreach.*as \$local" templates/*.php
```

## Changelog Entry

```
[Bugfix] Naprawiono krytyczny błąd przypisania PDF-ów - każde mieszkanie miało PDF z poprzedniego mieszkania w pętli. Przeniesiono definicję $projections przed jej użyciem w templates/apartments-list.php.
```

