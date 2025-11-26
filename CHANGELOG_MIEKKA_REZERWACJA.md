# Changelog: Obsługa statusu "Miękka rezerwacja"

## Data: 2025-11-25

### Problem
Status "Miękka rezerwacja" z API Develogic nie był obsługiwany przez wtyczkę - aplikacja się "gubiła" i nie traktowała go jako rezerwacji. Powodowało to:
- Nieprawidłowe kolorowanie na listach i mapach (brak pomarańczowego koloru)
- Nieprawidłowe zliczanie w statystykach (nie uwzględniało w liczbie rezerwacji)
- Możliwe problemy z filtrowaniem

### Rozwiązanie
Dodano pełną obsługę statusu "Miękka rezerwacja", traktując go identycznie jak standardowy status "Rezerwacja":
- **Kolor**: Pomarańczowy (#FFA500) - taki sam jak "Rezerwacja"
- **Wyświetlanie**: Pokazuje się jako "Rezerwacja" (znormalizowane)
- **Zliczanie**: Sumuje się z "Rezerwacja" w statystykach

### Zmiany w plikach

#### 1. `includes/class-data-formatter.php`
Dodano mapowanie statusu "Miękka rezerwacja" na klasę CSS "reserved":

```php
public static function get_status_class($status) {
    $status_map = array(
        'Wolny' => 'available',
        'Rezerwacja' => 'reserved',
        'Miękka rezerwacja' => 'reserved',  // ← DODANE
        'Sprzedany' => 'sold',
        'Sprzedane' => 'sold',
    );
    
    return isset($status_map[$status]) ? $status_map[$status] : sanitize_title($status);
}
```

#### 2. `includes/class-imagemappro-integration.php`
Dodano kolor dla statusu "Miękka rezerwacja" (taki sam jak "Rezerwacja"):

```php
private $status_colors = array(
    'Wolny' => '7ED322',        // Green - available
    'Sprzedany' => 'ee1c24',    // Red - sold
    'Rezerwacja' => 'FFA500',   // Orange - reserved
    'Miękka rezerwacja' => 'FFA500',  // ← DODANE - Orange - soft reservation
    'Niedostępny' => 'cccccc',  // Gray - unavailable
);
```

#### 3. `includes/class-filter-sort.php`
Dodano normalizację statusu podczas zliczania:

```php
public static function count_by_status($locals) {
    // ...
    foreach ($locals as $local) {
        $status = $local['status'];
        
        // Normalize "Miękka rezerwacja" to "Rezerwacja"
        if ($status === 'Miękka rezerwacja') {  // ← DODANE
            $status = 'Rezerwacja';
        }
        
        if (!isset($counts[$status])) {
            $counts[$status] = 0;
        }
        
        $counts[$status]++;
    }
    // ...
}
```

#### 4. `templates/apartments-list.php`
Dodano normalizację statusu do wyświetlania:

**Przed listą mieszkań:**
```php
// Normalize status display
$display_status = $local['status'];
if ($display_status === 'Miękka rezerwacja') {
    $display_status = 'Rezerwacja';
}
```

**W badge'u statusu:**
```php
<?php 
if ($status_class === 'available') {
    echo 'Dostępne';
} else {
    echo esc_html($display_status);  // ← Zmieniono z $local['status']
}
?>
```

**W danych modala:**
```php
'status' => $display_status,  // ← Zmieniono z $local['status']
```

#### 5. `admin/class-admin-settings.php`
Dodano "Miękka rezerwacja" do listy dostępnych statusów w panelu admina:

```php
public function render_status_checkboxes($args) {
    // ...
    $statuses = array('Wolny', 'Rezerwacja', 'Miękka rezerwacja', 'Sprzedany', 'Sprzedane');
    // ...
}
```

#### 6. `admin/class-admin-imagemappro.php`
Dodano domyślny kolor dla "Miękka rezerwacja":

```php
$default_statuses = array(
    'Wolny' => '7ED322',
    'Sprzedany' => 'ee1c24',
    'Rezerwacja' => 'FFA500',
    'Miękka rezerwacja' => 'FFA500',  // ← DODANE
    'Niedostępny' => 'cccccc',
);
```

#### 7. `includes/class-debug-helper.php`
Zaktualizowano narzędzie debugowania o nowy status (2 miejsca):

```php
$colors = array(
    'Wolny' => '7ED322',
    'Sprzedany' => 'ee1c24',
    'Rezerwacja' => 'FFA500',
    'Miękka rezerwacja' => 'FFA500',  // ← DODANE
    'Niedostępny' => 'cccccc',
);
```

### Działanie po zmianach

#### Wyświetlanie na liście
- Lokale z statusem "Miękka rezerwacja" wyświetlają się jako **"Rezerwacja"**
- Mają pomarańczowy kolor badge'a (class="reserved")

#### Statystyki w nagłówku
```
15 dostępnych | 8 rezerwacje
```
↑ Licznik "rezerwacje" sumuje zarówno "Rezerwacja" jak i "Miękka rezerwacja"

#### Modal szczegółów
- Status wyświetla się jako: 🟠 **Rezerwacja** (pomarańczowy)

#### Image Map Pro
- Kształty z lokalami o statusie "Miękka rezerwacja" są kolorowane na pomarańczowy (#FFA500)
- Działa identycznie jak dla statusu "Rezerwacja"

#### Panel administratora
- W sekcji "Widoczne statusy" można teraz zaznaczyć "Miękka rezerwacja"
- W konfiguracji Image Map Pro można ustawić własny kolor dla tego statusu

### Kompatybilność wsteczna
✅ **Zachowana w 100%**
- Standardowy status "Rezerwacja" działa bez zmian
- Użytkownicy nie muszą zmieniać swojej konfiguracji
- Jeśli w API nie ma "Miękka rezerwacja", nic się nie zmienia

### Testowanie

1. **Synchronizuj dane z Develogic** (powinny pojawić się lokale ze statusem "Miękka rezerwacja")
2. **Sprawdź listę mieszkań:**
   - Lokale z "Miękka rezerwacja" powinny mieć pomarańczowy badge
   - Badge powinien wyświetlać "Rezerwacja"
3. **Sprawdź liczniki w nagłówku:**
   - Licznik "rezerwacje" powinien sumować oba typy rezerwacji
4. **Otwórz modal szczegółów:**
   - Status powinien wyświetlać się jako "🟠 Rezerwacja"
5. **Sprawdź Image Map Pro** (jeśli używane):
   - Kształty z "Miękka rezerwacja" powinny być pomarańczowe
6. **Panel admina → Ustawienia:**
   - Checkbox "Miękka rezerwacja" powinien być widoczny w "Widoczne statusy"

### Pliki zmienione
- `includes/class-data-formatter.php` (+1 linia)
- `includes/class-imagemappro-integration.php` (+1 linia)
- `includes/class-filter-sort.php` (+5 linii)
- `templates/apartments-list.php` (+8 linii, -2 linie)
- `admin/class-admin-settings.php` (+1 linia, -1 linia)
- `admin/class-admin-imagemappro.php` (+1 linia)
- `includes/class-debug-helper.php` (+2 linie)

**Razem:** 7 plików, +19 linii, -3 linie

### Notatki techniczne
- Status "Miękka rezerwacja" jest **normalizowany** do "Rezerwacja" tylko na poziomie wyświetlania
- W bazie danych (custom post type) pozostaje oryginalny status z API
- Dzięki temu w przyszłości można łatwo rozróżnić oba typy rezerwacji, jeśli będzie taka potrzeba



