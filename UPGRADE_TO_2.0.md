# 🚀 Upgrade do wersji 2.0.0 - Tryb SYNC

## ⚠️ WAŻNE - BREAKING CHANGE

Wersja 2.0.0 wprowadza **radykalną zmianę architektury** z trybu "Live + Cache" na tryb "SYNC".

## Co się zmieniło?

### ❌ Przed (v1.x - Live + Cache):
```
Shortcode → Cache (transient) → Develogic API (60s timeout ❌)
```

### ✅ Teraz (v2.0 - SYNC):
```
Zewnętrzny CRON (co 1min) → WordPress REST → Develogic API → WordPress DB (CPT)
                                                                      ↓
                                                              Shortcode (0.1s ✅)
```

## Dlaczego zmiana?

1. **Timeout API** - Develogic API odpowiadało nawet 60 sekund, powodując problemy
2. **Wydajność** - Shortcody teraz ładują się <0.1s zamiast 30-60s
3. **Niezawodność** - Brak zależności od dostępności API w momencie wyświetlania
4. **Skalowalność** - Dane w lokalnej bazie, nie ma limitu requestów

## Instrukcja upgrade (5 minut)

### Krok 1: Backup

```bash
# Backup bazy
wp db export backup-before-2.0.sql

# Backup plików wtyczki
cp -r wp-content/plugins/develogic-wp-plugin wp-content/plugins/develogic-wp-plugin-backup
```

### Krok 2: Aktualizacja wtyczki

1. **Wyłącz** wtyczkę w WordPress Admin
2. **Usuń** starą wersję z `wp-content/plugins/develogic-wp-plugin/`
3. **Upload** nową wersję 2.0.0
4. **Włącz** wtyczkę ponownie

Podczas aktywacji:
- ✅ Zostanie utworzony Custom Post Type `develogic_local`
- ✅ Zostaną utworzone taxonomie
- ✅ Zostanie wygenerowany **secret key** (zapisz go!)

### Krok 3: Skonfiguruj API (jeśli jeszcze nie było)

Przejdź do: **WordPress Admin** → **Develogic** → **Ustawienia**

Wypełnij:
- **URL bazowy API**: `https://twoja-instalacja.ondevelogic.com/api/fis/v1/feed`
- **Klucz API**: Twój ApiKey z Develogic
- **Timeout**: 30 sekund (domyślnie)

**NOWE POLE**:
- **Secret Key**: (wygenerowany automatycznie - ZAPISZ GO!)

### Krok 4: Pierwsza synchronizacja (ręczna)

Przejdź do: **WordPress Admin** → **Develogic** → **Synchronizacja**

Kliknij **"Synchronizuj teraz (ręcznie)"**

Poczekaj na zakończenie (10-30 sekund w zależności od liczby lokali).

Sprawdź:
- ✅ Liczba lokali w bazie (powinna być > 0)
- ✅ Status: "Gotowy" (zielony)
- ✅ Log: "Synchronizacja zakończona: X dodanych..."

### Krok 5: Skonfiguruj zewnętrzny CRON

#### Opcja A: cron-job.org (ZALECANE - darmowe)

1. Zarejestruj się na https://cron-job.org
2. Kliknij **"Create cronjob"**
3. Wypełnij:
   - **Title**: `Develogic Sync - TwojaDomena`
   - **URL**: `https://twoja-domena.pl/wp-json/develogic/v1/sync`
   - **Request method**: `POST`
   - **Headers** → **Add header**:
     - Name: `Authorization`
     - Value: `Bearer {twój_secret_key}` (bez nawiasów!)
   - **Schedule**: `* * * * *` (co minutę)
   - **Enabled**: ✓
4. **Save**

#### Opcja B: Własny serwer (zaawansowane)

Dodaj do `crontab -e`:

```bash
* * * * * curl -X POST "https://twoja-domena.pl/wp-json/develogic/v1/sync" \
  -H "Authorization: Bearer {secret_key}" \
  >> /var/log/develogic-sync.log 2>&1
```

### Krok 6: Weryfikacja

Po 5 minutach:

1. Odśwież stronę **Develogic → Synchronizacja**
2. Sprawdź **"Ostatnia synchronizacja"** - powinna być aktualna (< 2 min temu)
3. Sprawdź **Log** - powinny być wpisy co minutę

Odśwież stronę z shortcodem `[develogic_offers_a1]`:
- ✅ Powinna ładować się **natychmiastowo** (<0.5s)
- ✅ Dane powinny być widoczne

## Co NIE wymaga zmian?

### ✅ Shortcody - działają bez zmian
```php
[develogic_offers_a1] // Bez zmian!
[develogic_offers]
[develogic_filters]
[develogic_local id="123"]
// ... wszystkie inne
```

### ✅ Template overrides - działają bez zmian
```
your-theme/develogic/a1-layout.php // Nadal działa!
your-theme/develogic/a1-card.php
```

### ✅ Hooki i filtry - działają bez zmian
```php
add_filter('develogic_building_thumbnail', ...); // Nadal działa!
add_filter('develogic_pdf_link', ...);
```

## Co PRZESTAŁO działać?

### ❌ WP-Cron prefetch
```php
// To NIE DZIAŁA - zostało usunięte
do_action('develogic_prefetch_cache'); // ❌
```

### ❌ Cache Manager
```php
// To NIE DZIAŁA - klasa została usunięta
develogic()->cache_manager->get_locals(); // ❌
develogic()->cache_manager->clear_all_cache(); // ❌
```

### ❌ Panel "Cache" w admin
- Submenu **"Develogic → Cache"** zostało usunięte

## Troubleshooting

### Problem: Brak danych po upgrade

**Rozwiązanie**:
1. Sprawdź **Develogic → Ustawienia** - czy API Key i URL są poprawne
2. Uruchom ręczną synchronizację w **Develogic → Synchronizacja**
3. Sprawdź logi - czerwone wpisy oznaczają błędy

### Problem: "Secret key nie został skonfigurowany"

**Rozwiązanie**:
1. Reaktywuj wtyczkę (deaktywuj → aktywuj)
2. LUB ustaw ręcznie w **Develogic → Ustawienia → Secret Key**

### Problem: CRON nie synchronizuje

**Rozwiązanie**:
1. Testuj ręcznie przez CURL:
   ```bash
   curl -X POST "https://twoja-domena.pl/wp-json/develogic/v1/sync" \
     -H "Authorization: Bearer {secret_key}" -v
   ```
2. Sprawdź czy secret key jest poprawny
3. Sprawdź logi w **Develogic → Synchronizacja**

### Problem: Shortcode ładuje się wolno

**Rozwiązanie**:
- Sprawdź czy synchronizacja działa (**Develogic → Synchronizacja**)
- Jeśli synchronizacja działa, a shortcode wolno się ładuje, to problem z motywem/innymi wtyczkami (nie z Develogic)

## Rollback (jeśli coś poszło nie tak)

Jeśli chcesz wrócić do v1.x:

```bash
# 1. Wyłącz wtyczkę 2.0
# 2. Przywróć backup
cp -r wp-content/plugins/develogic-wp-plugin-backup wp-content/plugins/develogic-wp-plugin
# 3. Włącz wtyczkę 1.x
# 4. (Opcjonalnie) Przywróć bazę
wp db import backup-before-2.0.sql
```

**UWAGA**: CPT `develogic_local` i taxonomie pozostaną w bazie, ale nie będą używane przez v1.x.

## Dokumentacja

- **Pełna dokumentacja SYNC**: `SYNC_MODE.md`
- **Changelog**: `CHANGELOG.md`
- **Główna dokumentacja**: `README.md`

## Wsparcie

W razie problemów:
1. Sprawdź **SYNC_MODE.md** - pełna dokumentacja
2. Sprawdź logi w **WordPress Admin → Develogic → Synchronizacja**
3. Włącz WP_DEBUG i sprawdź `/wp-content/debug.log`
4. Skontaktuj się z supportem

---

**Wersja**: 2.0.0  
**Data**: 2025-10-27  
**Szacowany czas upgrade**: 5-10 minut

