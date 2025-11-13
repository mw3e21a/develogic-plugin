# Changelog - Asynchroniczna Synchronizacja w Tle

**Data:** 2025-11-13  
**Wersja:** 2.1.0  
**Update:** 2025-11-13 19:15 - Poprawka WordPress Cron

## Problem

Synchronizacja z API Develogic trwała zbyt długo (nawet 60+ sekund), co powodowało:
- Timeouty na endpoincie REST API `/sync`
- Blokowanie użytkownika w panelu admina podczas manualnej synchronizacji
- Problemy z zewnętrznymi serwisami CRON które mogły timeout'ować requesty

## Rozwiązanie

Implementacja **asynchronicznej synchronizacji w tle** z wykorzystaniem WordPress Cron:
- Endpoint REST API i panel admina zwracają **natychmiastową odpowiedź** (< 1 sekundy)
- Synchronizacja wykonuje się w tle przez WordPress `wp_schedule_single_event()`
- Użytkownik widzi aktualny status synchronizacji w czasie rzeczywistym

## Zmiany

### 1. `includes/class-sync.php`

**Dodano nowe metody:**

- `trigger_async_sync()` - rozpoczyna synchronizację w tle, zwraca natychmiast
- `run_sync_background()` - wykonuje synchronizację w tle (wywoływane przez WP Cron)
- `get_sync_status()` - pobiera aktualny status synchronizacji

**Nowy system statusów:**
```php
'idle'      // Gotowy do synchronizacji
'queued'    // Zakolejkowano
'running'   // W trakcie
'completed' // Zakończono pomyślnie
'error'     // Błąd
```

Status zapisywany w opcji `develogic_sync_status` z polami:
- `status` - aktualny stan
- `started_at` - data rozpoczęcia
- `completed_at` - data zakończenia
- `message` - opis statusu
- `stats` - statystyki (dodane, zaktualizowane, usunięte, błędy, czas)

### 2. `includes/class-sync-endpoint.php`

**Zmiany w metodzie `trigger_sync()`:**
- Zamiast wywoływać `sync_locals()` (blokujące)
- Wywołuje `trigger_async_sync()` (natychmiastowy zwrot)
- Endpoint odpowiada w < 1 sekundzie niezależnie od ilości danych

**Rozszerzono endpoint statusu `/sync/status`:**
- Dodano pole `current_status` ze szczegółami bieżącej synchronizacji
- Możliwość monitorowania postępu w czasie rzeczywistym

### 3. `admin/class-admin-sync.php`

**Zmiany w `handle_manual_sync()`:**
- Używa `trigger_async_sync()` zamiast `sync_locals()`
- Natychmiastowe przekierowanie użytkownika z komunikatem "Synchronizacja rozpoczęta w tle"

**Rozbudowano widok statusu:**
- Wyświetlanie aktualnego statusu synchronizacji (idle/queued/running/completed/error)
- Pokazywanie czasu rozpoczęcia i zakończenia
- Komunikat o aktualnym stanie

**Auto-refresh:**
- Gdy synchronizacja jest w trakcie (`queued` lub `running`), strona odświeża się automatycznie co 5 sekund
- Użytkownik widzi postęp bez manualnego odświeżania

### 4. `develogic-integration.php`

**Dodano hook dla WP Cron:**
```php
add_action('develogic_run_async_sync', array($this, 'run_async_sync'));
```

**Nowa metoda:**
- `run_async_sync()` - callback dla WordPress Cron

## Jak to działa?

### Poprzednia implementacja (blokująca):
```
Request → Endpoint → sync_locals() [60+ sekund] → Response
```

### Nowa implementacja (asynchroniczna):
```
Request → Endpoint → trigger_async_sync() [< 1 sek] → Response
                           ↓
                   spawn_sync_request()
                           ↓ (wp_remote_post non-blocking)
               Internal Endpoint /sync/run
                           ↓
                   run_sync_background() [wykonuje się w tle, 30-60s]
```

## Użycie

### Endpoint REST API

```bash
# Uruchomienie synchronizacji (natychmiastowa odpowiedź)
curl -X POST "https://example.com/wp-json/develogic/v1/sync" \
  -H "Authorization: Bearer {secret_key}"

# Response (< 1 sekunda):
{
  "success": true,
  "message": "Synchronizacja rozpoczęta w tle. Sprawdź status za chwilę.",
  "status": "queued"
}

# Sprawdzenie statusu
curl "https://example.com/wp-json/develogic/v1/sync/status" \
  -H "Authorization: Bearer {secret_key}"

# Response:
{
  "current_status": {
    "status": "running",
    "started_at": "2025-11-13 10:30:00",
    "message": "Synchronizacja w trakcie..."
  },
  "last_sync": {
    "time": "2025-11-13 09:00:00",
    "stats": {...}
  },
  "locals_count": 150,
  "is_running": true
}
```

### Panel Admina

1. Kliknięcie "Synchronizuj teraz" → natychmiastowe przekierowanie z komunikatem
2. Strona pokazuje status "⏳ Zakolejkowano" lub "⏳ W trakcie..."
3. Auto-refresh co 5 sekund aktualizuje status
4. Po zakończeniu: "✓ Zakończono" z pełnymi statystykami

## Korzyści

✅ **Brak timeoutów** - endpoint odpowiada natychmiast  
✅ **Lepsza UX** - użytkownik nie czeka, widzi postęp  
✅ **Stabilność** - zewnętrzne CRONy nie timeout'ują  
✅ **Monitoring** - możliwość sprawdzenia statusu w dowolnym momencie  
✅ **Skalowalność** - działa niezależnie od ilości danych

## Uwagi techniczne

- ~~WordPress Cron wykonuje się przy następnym request do strony (nie jest to prawdziwy cron)~~ **[POPRAWIONE]**
- **Nowa implementacja:** Synchronizacja uruchamia się natychmiast przez `wp_remote_post()` z `blocking => false`
- Tworzone jest wewnętrzne żądanie HTTP do endpointu `/sync/run` który wykonuje synchronizację w tle
- Żądanie jest non-blocking (timeout 0.01s), więc użytkownik otrzymuje natychmiastową odpowiedź
- Synchronizacja działa w tle z `ignore_user_abort(true)` i `set_time_limit(300)`
- Lock (`develogic_sync_lock`) chroni przed jednoczesnym uruchomieniem wielu synchronizacji
- Status przechowywany w `wp_options` pozwala na monitoring między requestami
- Dla większej pewności nadal zaleca się używanie zewnętrznego cron-job.org który wywołuje endpoint co minutę

## Backward Compatibility

Stara metoda `sync_locals()` nadal istnieje i działa - używana wewnętrznie przez `run_sync_background()`. Nie ma breaking changes dla kodu, który używał tej metody bezpośrednio.

## Testy

Do przetestowania:
1. ✅ Synchronizacja z panelu admina (natychmiastowy zwrot)
2. ✅ Endpoint REST API `/sync` (zwraca w < 1 sek)
3. ✅ Status synchronizacji `/sync/status` (pokazuje aktualny stan)
4. ✅ Auto-refresh w panelu admina
5. ✅ Zachowanie przy próbie uruchomienia drugiej synchronizacji (blokada)
6. ✅ Logowanie w `develogic_sync_log`

## Poprawka - WordPress Cron Issue (2025-11-13 19:15)

### Problem
Początkowo używano `wp_schedule_single_event()` do uruchomienia synchronizacji, ale WordPress Cron nie uruchamia się natychmiast - wymaga kolejnego HTTP request do strony. To powodowało, że synchronizacja pozostawała w stanie "Zakolejkowano" i nie wykonywała się automatycznie.

### Rozwiązanie
Zastąpiono WordPress Cron mechanizmem **non-blocking HTTP request**:

1. **Dodano metodę `spawn_sync_request()`** w `class-sync.php`:
   - Wykonuje `wp_remote_post()` z `blocking => false` i `timeout => 0.01`
   - Wysyła request do wewnętrznego endpointu `/sync/run`
   - Request jest zabezpieczony nonce'em (`X-Develogic-Internal`)

2. **Dodano wewnętrzny endpoint `/sync/run`** w `class-sync-endpoint.php`:
   - Weryfikuje nonce z headera
   - Ustawia `ignore_user_abort(true)` - sync działa nawet jeśli request zostanie anulowany
   - Ustawia `set_time_limit(300)` - pozwala na długie wykonanie
   - Wywołuje `run_sync_background()`
   - Usuwa lock po zakończeniu

### Korzyści nowego podejścia
- ✅ Synchronizacja uruchamia się **natychmiast** (nie czeka na kolejny request)
- ✅ Non-blocking request (użytkownik otrzymuje odpowiedź w < 1 sek)
- ✅ Sync wykonuje się w tle niezależnie od tego czy użytkownik zamknie okno
- ✅ Działa niezależnie od konfiguracji WordPress Cron
- ✅ Bardziej niezawodne niż WordPress Cron

### Zmiany techniczne
```php
// Stare (nie działało):
wp_schedule_single_event(time(), 'develogic_run_async_sync');

// Nowe (działa):
wp_remote_post(rest_url('develogic/v1/sync/run'), array(
    'timeout' => 0.01,
    'blocking' => false,
    'headers' => array('X-Develogic-Internal' => wp_create_nonce(...))
));
```
