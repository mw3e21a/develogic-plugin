# Tryb SYNC - Dokumentacja

## Przegląd

Wtyczka Develogic Integration została zaktualizowana do pracy w trybie **SYNC** (synchronizacja).

Zamiast pobierania danych na żywo z API Develogic (co powodowało problemy z wydajnością - timeout 60s), 
dane są teraz przechowywane **lokalnie w bazie WordPress** jako Custom Post Type i synchronizowane 
co 1 minutę przez zewnętrzny CRON.

## Zmiany w architekturze

### ❌ Co zostało **USUNIĘTE**:
- `class-cache-manager.php` - mechanizm cache (WordPress Transients)
- WP-Cron dla prefetch (`develogic_prefetch_cache`)
- Tryb "live + cache"
- Submenu "Cache" w panelu admin

### ✅ Co zostało **DODANE**:
- `class-post-type.php` - Custom Post Type `develogic_local`
- `class-sync.php` - mechanizm synchronizacji z API do CPT
- `class-sync-endpoint.php` - REST endpoint dla zewnętrznego crona
- `class-local-query.php` - helper do pobierania danych z CPT
- `class-admin-sync.php` - panel administracyjny synchronizacji
- Custom Taxonomies:
  - `develogic_investment` - inwestycje
  - `develogic_local_type` - typy lokali
  - `develogic_building` - budynki
  - `develogic_status` - statusy

## Jak to działa?

```
┌─────────────────┐      Każda minuta       ┌─────────────────┐
│  Zewnętrzny     │ ──────────────────────> │  WordPress      │
│  CRON           │  POST /wp-json/         │  REST Endpoint  │
│  (cron-job.org) │  develogic/v1/sync      │                 │
└─────────────────┘  Bearer: secret_key     └─────────────────┘
                                                      │
                                                      ▼
                                            ┌─────────────────┐
                                            │  Develogic API  │
                                            │  (live)         │
                                            └─────────────────┘
                                                      │
                                                      ▼
                                            ┌─────────────────┐
                                            │  WordPress DB   │
                                            │  CPT: locals    │
                                            │  Taxonomies     │
                                            └─────────────────┘
                                                      │
                                                      ▼
                                            ┌─────────────────┐
                                            │  Shortcody      │
                                            │  (szybkie!)     │
                                            └─────────────────┘
```

## Konfiguracja zewnętrznego CRON

### 1. Pobierz Secret Key

Przejdź do **WordPress Admin** → **Develogic** → **Synchronizacja**

Znajdziesz tam:
- **Endpoint URL**: `https://twoja-domena.pl/wp-json/develogic/v1/sync`
- **Secret Key**: (wygenerowany automatycznie przy aktywacji wtyczki)

### 2. Skonfiguruj CRON na cron-job.org

1. Zarejestruj się na https://cron-job.org (darmowe)
2. Kliknij **Create cronjob**
3. Wypełnij formularz:
   - **Title**: Develogic Sync
   - **URL**: `https://twoja-domena.pl/wp-json/develogic/v1/sync`
   - **Request method**: `POST`
   - **Headers** (kliknij "Add header"):
     - **Name**: `Authorization`
     - **Value**: `Bearer {twój_secret_key}`
   - **Schedule**: `* * * * *` (co minutę)
   - **Enabled**: ✓

4. **Save**

### 3. Alternatywnie: Własny serwer CRON

Jeśli masz dostęp do serwera, możesz użyć własnego crona:

```bash
# Dodaj do crontab -e
* * * * * curl -X POST "https://twoja-domena.pl/wp-json/develogic/v1/sync" \
  -H "Authorization: Bearer {secret_key}" \
  >> /var/log/develogic-sync.log 2>&1
```

## REST API Endpoints

### POST `/wp-json/develogic/v1/sync`
**Opis**: Uruchamia synchronizację danych z Develogic API do bazy WordPress.

**Autoryzacja**: Bearer token (secret_key)

**Odpowiedź**:
```json
{
  "success": true,
  "added": 15,
  "updated": 120,
  "errors": 0,
  "total": 135,
  "time": 12.34,
  "message": "Synchronizacja zakończona: 15 dodanych, 120 zaktualizowanych, 0 błędów w 12.34 sek"
}
```

### GET `/wp-json/develogic/v1/sync/status`
**Opis**: Sprawdza status ostatniej synchronizacji.

**Autoryzacja**: Bearer token (secret_key)

**Odpowiedź**:
```json
{
  "last_sync": {
    "time": "2025-10-27 14:30:00",
    "stats": {
      "success": true,
      "added": 15,
      "updated": 120,
      "errors": 0,
      "total": 135,
      "time": 12.34
    }
  },
  "locals_count": 135,
  "recent_log": [...],
  "is_running": false
}
```

## Panel Administracyjny

### Develogic → Synchronizacja

Panel pozwala na:
- ✅ Podgląd statusu synchronizacji
- ✅ Ręczne uruchomienie synchronizacji
- ✅ Wyświetlenie ostatnich logów
- ✅ Instrukcje konfiguracji CRON
- ✅ Przykłady CURL
- ✅ Wyświetlenie secret key
- ✅ Czyszczenie wszystkich lokali (kasowanie bazy)

## Shortcody - bez zmian API

Wszystkie shortcody działają **tak samo jak wcześniej**, ale:
- ✅ **Szybko** - dane z lokalnej bazy (nie API)
- ✅ **Niezawodnie** - bez timeoutów
- ✅ **Aktualnie** - sync co 1 minutę

Lista shortcodów:
- `[develogic_offers_a1]` - główny layout A1
- `[develogic_offers]` - lista ofert
- `[develogic_filters]` - filtry
- `[develogic_local id="123"]` - pojedynczy lokal
- `[develogic_price_history local_id="123"]` - historia cen (live API)
- `[develogic_investments]` - lista inwestycji
- `[develogic_local_types]` - typy lokali

**Uwaga**: Historia cen (`price_history`) jest nadal pobierana **live z API** (nie jest cachowana w bazie).

## Deduplikacja i aktualizacje

- Każdy lokal jest identyfikowany po `localId` z API Develogic
- Przy synchronizacji:
  - Jeśli lokal **nie istnieje** → **dodawany** (INSERT)
  - Jeśli lokal **istnieje** → **aktualizowany** (UPDATE)
- Meta `localId` jest używane jako unikalny identyfikator

## Logowanie

Wszystkie synchronizacje są logowane:
- WordPress option `develogic_last_sync` - ostatnia synchronizacja
- WordPress option `develogic_sync_log` - ostatnie 50 wpisów

Poziomy logów:
- **success** - synchronizacja OK
- **error** - błąd synchronizacji
- **warning** - ostrzeżenie (np. brak danych)

## Zabezpieczenia

### Lock mechanism
Aby zapobiec równoczesnym synchronizacjom:
- Transient `develogic_sync_lock` (TTL: 5 minut)
- Jeśli sync jest w trakcie, kolejne próby zwracają HTTP 409

### Secret Key
- Generowany automatycznie (32 znaki, alfanumeryczne)
- Można zmienić w: **Develogic** → **Ustawienia** → **Secret Key**
- Musi być przekazany jako `Authorization: Bearer {key}`

## Testowanie

### 1. Ręczna synchronizacja
W panelu **Develogic → Synchronizacja** kliknij **"Synchronizuj teraz (ręcznie)"**.

### 2. CURL test
```bash
curl -X POST "https://twoja-domena.pl/wp-json/develogic/v1/sync" \
  -H "Authorization: Bearer {secret_key}" \
  -v
```

### 3. Sprawdź status
```bash
curl "https://twoja-domena.pl/wp-json/develogic/v1/sync/status" \
  -H "Authorization: Bearer {secret_key}"
```

## Migracja z wersji poprzedniej

1. **Zaktualizuj wtyczkę** (upload nowej wersji)
2. **Reaktywuj wtyczkę** (deaktywuj → aktywuj) - wygeneruje secret key
3. **Skonfiguruj API** w **Develogic → Ustawienia** (jeśli jeszcze nie było)
4. **Uruchom pierwszą synchronizację** ręcznie w **Develogic → Synchronizacja**
5. **Skonfiguruj zewnętrzny CRON** (cron-job.org)
6. **Gotowe!**

## Troubleshooting

### Błąd: "Secret key nie został skonfigurowany"
- Reaktywuj wtyczkę lub ustaw ręcznie w **Develogic → Ustawienia**

### Błąd: "Synchronizacja jest już w trakcie"
- Poczekaj 5 minut lub usuń transient `develogic_sync_lock` z bazy

### Brak danych po synchronizacji
- Sprawdź logi w **Develogic → Synchronizacja**
- Sprawdź ustawienia API (URL, klucz)
- Sprawdź błędy w **Develogic → Ustawienia** (red banner)

### CRON nie działa
- Sprawdź logi w **Develogic → Synchronizacja**
- Testuj ręcznie przez CURL
- Sprawdź czy secret key jest poprawny
- Sprawdź czy cron-job.org ma status "Enabled"

## Performance

**Przed (Live + Cache)**:
- Pierwsze wywołanie: 60s timeout ❌
- Kolejne: 30s (cache) ⚠️

**Teraz (Sync)**:
- Wywołanie shortcode: <0.1s ✅
- Synchronizacja (background): 10-20s co minutę ✅

## Autor

Develogic Integration Plugin - wersja SYNC  
Data: 2025-10-27

