# Changelog - WordPress Cron Automatyczna Synchronizacja

## Wprowadzone zmiany

### 1. Dodano WP-Cron dla automatycznej synchronizacji (co 5 minut)

#### Pliki zmienione:
- `develogic-integration.php`
- `admin/class-admin-settings.php`

### Szczegóły implementacji:

#### A) develogic-integration.php

**Dodano hooki:**
- `add_filter('cron_schedules')` - dodaje niestandardowy interwał "co 5 minut"
- `add_action('develogic_sync_cron')` - obsługuje wywołanie synchronizacji przez cron

**Dodano metodę `add_cron_schedules()`:**
- Rejestruje niestandardowy harmonogram `every_5_minutes` (300 sekund)
- Wyświetlana nazwa: "Co 5 minut"

**Dodano metodę `run_cron_sync()`:**
- Sprawdza czy automatyczna synchronizacja jest włączona w ustawieniach
- Zabezpieczenie przed jednoczesnym uruchamianiem synchronizacji (lock)
- Wykonuje synchronizację za pomocą `Develogic_Sync::sync_locals()`
- Loguje wyniki do error_log

**Zaktualizowano metodę `activate()`:**
- Dodano domyślną opcję `enable_cron_sync` (false - wyłączone)
- Automatyczne zaplanowanie crona przy aktywacji wtyczki

**Zaktualizowano metodę `deactivate()`:**
- Automatyczne usunięcie zaplanowanego crona przy deaktywacji wtyczki

#### B) admin/class-admin-settings.php

**Dodano pole w ustawieniach:**
- Checkbox "Automatyczna synchronizacja (WP-Cron)"
- Dodany w sekcji "Ustawienia synchronizacji"
- Opis: wymaga prawidłowo skonfigurowanego WP-Cron

**Zaktualizowano metodę `sanitize_settings()`:**
- Obsługa zapisu ustawienia `enable_cron_sync`
- Automatyczne (od)planowanie crona przy zmianie ustawienia
- Jeśli włączone: `wp_schedule_event()` z interwałem `every_5_minutes`
- Jeśli wyłączone: `wp_unschedule_event()` usuwa zaplanowane zadanie

**Zaktualizowano metodę `render_checkbox_field()`:**
- Dodano obsługę parametru `description` do wyświetlania opisu pod checkboxem

### Jak to działa:

1. **Aktywacja wtyczki:**
   - Cron jest automatycznie zaplanowany
   - Domyślnie wyłączony (wymaga włączenia w ustawieniach)

2. **Włączenie w ustawieniach:**
   - Administrator zaznacza checkbox "Automatyczna synchronizacja (WP-Cron)"
   - Przy zapisie ustawień cron jest aktywowany
   - Synchronizacja uruchamia się co 5 minut

3. **Wykonanie synchronizacji:**
   - WP-Cron wywołuje action `develogic_sync_cron`
   - Metoda `run_cron_sync()` sprawdza czy funkcja jest włączona
   - Jeśli tak - wykonuje pełną synchronizację
   - Wyniki są logowane do `error_log`

4. **Wyłączenie:**
   - Odznaczenie checkboxa usuwa zaplanowane zadanie
   - Synchronizacja przestaje się wykonywać

5. **Deaktywacja wtyczki:**
   - Cron jest automatycznie usuwany

### Zabezpieczenia:

1. **Lock mechanizm:**
   - Transient `develogic_sync_lock` (5 minut)
   - Zapobiega równoczesnym synchronizacjom

2. **Sprawdzanie ustawień:**
   - Cron wykonuje się tylko gdy `enable_cron_sync` = true
   - Dodatkowe zabezpieczenie przed niepożądanym uruchomieniem

3. **Logowanie:**
   - Wszystkie operacje są logowane do `error_log`
   - Ułatwia debugowanie i monitoring

### Wymagania:

- Prawidłowo skonfigurowany WordPress Cron
- Dla produkcji zaleca się:
  - Wyłączenie WP-Cron w `wp-config.php`: `define('DISABLE_WP_CRON', true);`
  - Skonfigurowanie prawdziwego system cron: `*/5 * * * * wget -q -O - https://twoja-domena.pl/wp-cron.php?doing_wp_cron >/dev/null 2>&1`

### Kompatybilność:

- Działa równolegle z istniejącym REST API endpoint `/wp-json/develogic/v1/sync`
- Działa równolegle z GitHub Actions workflow
- Działa równolegle z ręczną synchronizacją w panelu administracyjnym

### Testowanie:

Aby przetestować czy cron działa:

1. Włącz `WP_DEBUG` w `wp-config.php`
2. Włącz automatyczną synchronizację w ustawieniach Develogic
3. Sprawdź `wp-content/debug.log` po 5 minutach
4. Powinieneś zobaczyć logi: `[Develogic Cron] Rozpoczynam automatyczną synchronizację`

Alternatywnie użyj WP-CLI:
```bash
wp cron event list
wp cron event run develogic_sync_cron
```

### Uwagi:

- Interwał 5 minut to kompromis między aktualnością danych a obciążeniem serwera
- Można zmienić interwał modyfikując wartość w metodzie `add_cron_schedules()`
- WP-Cron nie jest "prawdziwym" cronem - uruchamia się przy odwiedzinach strony
- Dla krytycznych zastosowań zaleca się prawdziwy system cron + REST API endpoint

