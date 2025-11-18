# 🔧 Troubleshooting - Lokal M63 nie aktualizuje koloru

## Problem

Masz mieszkanie **M63** w Develogic ze statusem **"Sprzedany"**, i w Image Map Pro masz kształt z **title="M63"**, ale po kliknięciu "Aktualizuj kolory teraz" kolor się nie zmienia.

## 🔍 Krok 1: Uruchom skrypt diagnostyczny

### Opcja A: Przez przeglądarkę

1. Otwórz w przeglądarce:
   ```
   http://twoja-domena.pl/wp-content/plugins/develogic-integration/debug-m63.php
   ```

2. Sprawdź wynik - pokaże wszystkie istotne informacje

### Opcja B: Przez WordPress admin

Dodaj ten kod do `functions.php` Twojego motywu:

```php
add_action('admin_init', function() {
    if (isset($_GET['debug_m63']) && current_user_can('manage_options')) {
        require_once(WP_PLUGIN_DIR . '/develogic-integration/debug-m63.php');
        exit;
    }
});
```

Potem otwórz:
```
http://twoja-domena.pl/wp-admin/?debug_m63=1
```

## ✅ Krok 2: Sprawdź najczęstsze przyczyny

### Przyczyna 1: Lokal nie istnieje w bazie

**Sprawdź:**
```
WordPress Admin → Develogic → Lokale
```

Poszukaj lokalu "M63" lub "63". Jeśli nie ma:

**Rozwiązanie:**
1. Przejdź do `Develogic → Synchronizacja`
2. Kliknij "Synchronizuj teraz"
3. Poczekaj na zakończenie
4. Sprawdź czy M63 się pojawił

### Przyczyna 2: Numer nie pasuje dokładnie

**Problem:** W Develogic jest `M63`, w Image Map Pro jest `63` (bez "M")

**Sprawdź w debug scripcie:**
- Jaką wartość ma pole `number` w Develogic?
- Jaką wartość ma `title` w Image Map Pro?

**Muszą być IDENTYCZNE:**
```
Develogic: number = "M63"  →  Image Map Pro: title = "M63"  ✅
Develogic: number = "M63"  →  Image Map Pro: title = "63"   ❌
Develogic: number = "63"   →  Image Map Pro: title = "M63"  ❌
```

**Rozwiązanie:**
- Albo zmień `title` w Image Map Pro na dokładnie to co jest w Develogic
- Albo użyj pola `externalNumber` w Develogic (jeśli dostępne)

### Przyczyna 3: Brak mapowania budynku

**Sprawdź:**
```
WordPress Admin → Develogic → Image Map Pro → Sekcja "Mapowanie budynków"
```

Czy budynek **H** jest zmapowany na odpowiedni projekt?

**Przykład poprawnego mapowania:**
```
Budynek: H (ID: 8) → Shortcode: Pietro_3H
```

**Rozwiązanie:**
1. W sekcji "Mapowanie budynków"
2. Dla budynku "H" wybierz projekt z M63
3. Kliknij "Zapisz mapowania"

### Przyczyna 4: Niepoprawny shortcode projektu

**Sprawdź:**
Czy M63 jest na właściwym piętrze/projekcie?

Z Twoich danych:
- Lokal M63: Piętro III, Budynek H
- Projekt powinien być: `Pietro_3H` (lub podobny)

**Rozwiązanie:**
Upewnij się, że mapujesz budynek H na projekt, który faktycznie zawiera M63.

### Przyczyna 5: buildingId vs building name

Czasem mapowanie używa **ID budynku** zamiast **nazwy**.

**Sprawdź w debug scripcie:**
- `buildingId`: może być np. `8`
- `building`: `"H"`

**W mapowaniu możesz użyć:**
- Albo ID: `8 → Pietro_3H`
- Albo nazwy: `H → Pietro_3H`

## 📊 Krok 3: Sprawdź logi

### Włącz WP_DEBUG

W `wp-config.php` dodaj:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Uruchom aktualizację

1. `Develogic → Image Map Pro`
2. Kliknij "Aktualizuj kolory teraz"
3. Sprawdź `wp-content/debug.log`

### Czego szukać w logach:

```
[ImageMapPro Debug] Comparing shape "M63" with local "M63" (external: "")
[ImageMapPro] Matched shape "M63" to local M63 (building: H)
[ImageMapPro] Updated project "Pietro 3 Budynek H" - 1 shapes updated
```

Jeśli widzisz:
```
[ImageMapPro] No local found for shape "M63" in project Pietro_3H
```

To znaczy że dopasowanie nie działa.

## 🎯 Krok 4: Rozwiązanie krok po kroku

### Scenariusz A: M63 NIE MA w bazie Develogic

```bash
1. Develogic → Synchronizacja
2. Kliknij "Synchronizuj teraz"  
3. Poczekaj 30-60 sekund
4. Sprawdź Develogic → Lokale → Szukaj "M63"
5. Jeśli dalej nie ma - sprawdź:
   - Czy API Key jest poprawny?
   - Czy M63 istnieje w systemie Develogic?
   - Czy jest w wybranej inwestycji (filtr synchronizacji)?
```

### Scenariusz B: M63 JEST, ale nie pasuje numer

```bash
1. Sprawdź dokładną wartość w Develogic:
   - Develogic → Lokale → Edytuj M63
   - Zobacz pole "number" i "externalNumber"

2. Sprawdź dokładną wartość w Image Map Pro:
   - Image Map Pro → Editor → Pietro_3H
   - Kliknij na kształt M63
   - Zobacz pole "Title"

3. Dostosuj:
   OPCJA A: Zmień title w Image Map Pro
   OPCJA B: Zmień number w Develogic (nie zalecane)
   OPCJA C: Ustaw externalNumber w Develogic = title z Image Map Pro
```

### Scenariusz C: Numery pasują, ale nie aktualizuje

```bash
1. Sprawdź mapowanie:
   Develogic → Image Map Pro → Mapowania
   
2. Upewnij się że:
   - Budynek H jest zmapowany
   - Projekt ma poprawny shortcode
   - Shortcode pasuje do tego z Image Map Pro

3. Jeśli używasz buildingId:
   - Sprawdź jakie ID ma budynek H (debug script)
   - Użyj tego ID w mapowaniu zamiast nazwy "H"
```

## 💡 Szybkie testy

### Test 1: Czy integracja w ogóle działa?

Stwórz testowy lokal:
1. Dodaj lokal "TEST1" w Develogic (status: Wolny)
2. Dodaj kształt "TEST1" w Image Map Pro
3. Zmapuj budynek
4. Aktualizuj kolory
5. Jeśli TEST1 działa, problem jest specyficzny dla M63

### Test 2: Czy mapowanie działa?

```php
// Dodaj do functions.php tymczasowo
add_action('init', function() {
    if (is_admin() && isset($_GET['test_mapping'])) {
        $mappings = get_option('develogic_imagemappro_building_map', array());
        echo '<pre>';
        print_r($mappings);
        echo '</pre>';
        exit;
    }
});

// Otwórz: /wp-admin/?test_mapping=1
```

## 📝 Checklist

Przed zgłoszeniem problemu, sprawdź:

- [ ] M63 istnieje w `Develogic → Lokale`
- [ ] M63 ma status "Sprzedany"
- [ ] M63 należy do budynku "H"
- [ ] Kształt w Image Map Pro ma `title = "M63"` (dokładnie!)
- [ ] Budynek H jest zmapowany w `Develogic → Image Map Pro`
- [ ] Kolory są skonfigurowane dla statusu "Sprzedany"
- [ ] Image Map Pro jest aktywne
- [ ] WP_DEBUG jest włączone i sprawdziłeś logi

## 🆘 Dalej nie działa?

### Wyślij debug info:

1. Uruchom `debug-m63.php`
2. Zrób screenshot całej strony
3. Sprawdź `wp-content/debug.log` (ostatnie 50 linii)
4. Sprawdź `Develogic → Synchronizacja → Logi`

### Informacje do przesłania:

```
1. Wartość "number" w Develogic dla M63: ___________
2. Wartość "title" w Image Map Pro: ___________
3. Shortcode projektu z M63: ___________
4. Mapowanie budynku H: ___________
5. Fragment debug.log (ImageMapPro): ___________
```

---

**Tip:** W 99% przypadków problem to niezgodność numeru lub brak mapowania budynku! 🎯

