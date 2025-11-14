# Changelog - Kompletne czyszczenie przy usuwaniu wtyczki

## Data: 2025-11-14

## Zmiany

### Ulepszona funkcja uninstall.php

Rozszerzono proces deinstalacji wtyczki aby zapewnić **całkowite usunięcie wszystkich danych** związanych z wtyczką Develogic Integration.

### Co zostało dodane do czyszczenia:

#### 1. **Zaplanowane zadania Cron**
- Usuwanie zaplanowanych zadań `develogic_sync_cron`
- Czyszczenie wszystkich instancji crona za pomocą `wp_clear_scheduled_hook()`
- Gwarantuje że nie pozostaną żadne zaplanowane synchronizacje

#### 2. **Wszystkie transients**
- `develogic_sync_lock` - blokada synchronizacji
- `develogic_last_api_error` - ostatni błąd API
- Wildcard cleanup dla wszystkich transients z prefiksem `develogic_`

#### 3. **Wszystkie meta pola lokali**
Rozszerzono czyszczenie o wszystkie pola meta przechowujące dane z API:
- Podstawowe: `localId`, `number`, `status`, `floor`, `rooms`, `area`
- Ceny: `priceGross`, `priceNet`, `priceGrossm2`, `priceNetm2`
- Ceny Omnibus: `omnibusPriceGross`, `omnibusPackagePriceGross`, itp.
- Powierzchnie: `areaBalcony`, `areaTerrace`, `areaGarden`, `areaUsable`, itp.
- Powierzchnie finalne: `areaGroundFinal`, `areaProductFinal`, itp.
- Ceny pakietowe: `packagePriceGross`, `packagePriceNet`, itp.
- Ceny promocyjne: `promoPriceGross`, `promoPriceNet`, itp.
- Inne: `worldDirections`, `maxDiscountGross`, `plannedDateOfFinishing`, itp.

#### 4. **Wszystkie opcje wtyczki**
- `develogic_settings` - wszystkie ustawienia wtyczki
- `develogic_last_sync` - informacje o ostatniej synchronizacji
- `develogic_sync_log` - logi synchronizacji

#### 5. **Istniejące czyszczenie (bez zmian)**
- Custom Post Type `develogic_local` i wszystkie posty
- Załączniki (rzuty i plany mieszkań)
- Wszystkie taxonomie: `develogic_investment`, `develogic_local_type`, `develogic_building`, `develogic_status`
- Term relationships i term meta
- Orphaned terms

### Plik zmieniony
- `uninstall.php`

### Korzyści

✅ **Całkowite czyszczenie** - Nie pozostają żadne dane w bazie  
✅ **Nie ma śmieci** - Usuwane są wszystkie opcje, transients, meta, taxonomie  
✅ **Czyszczenie cron** - Usuwane są wszystkie zaplanowane zadania  
✅ **GDPR compliance** - Dane nie pozostają w systemie po odinstalowaniu  
✅ **Świeży start** - Ponowna instalacja zaczyna od czystego stanu  

### Kiedy jest wykonywane

Czyszczenie jest wykonywane **tylko gdy wtyczka jest całkowicie usuwana** (nie dezaktywowana) przez WordPress:

1. WordPress Admin → Wtyczki
2. Dezaktywuj wtyczkę
3. Usuń wtyczkę (Delete)
4. Podczas usuwania WordPress uruchamia `uninstall.php`

⚠️ **UWAGA**: Deaktywacja wtyczki **NIE USUWA** danych. Tylko całkowite usunięcie wtyczki uruchamia proces czyszczenia.

### Zgodność z WordPress best practices

✅ Używa `WP_UNINSTALL_PLUGIN` do weryfikacji  
✅ Bezpieczne zapytania SQL z `$wpdb->prepare()`  
✅ Używa WordPress API gdzie to możliwe  
✅ Flush rewrite rules na koniec  

### Testowanie

Aby przetestować kompletne czyszczenie:

```bash
# 1. Sprawdź co jest w bazie przed usunięciem
wp db query "SELECT * FROM wp_options WHERE option_name LIKE '%develogic%'"
wp db query "SELECT COUNT(*) FROM wp_posts WHERE post_type = 'develogic_local'"

# 2. Usuń wtyczkę przez WordPress Admin
# Dashboard → Wtyczki → Develogic Integration → Usuń

# 3. Zweryfikuj że wszystko zostało usunięte
wp db query "SELECT * FROM wp_options WHERE option_name LIKE '%develogic%'"
wp db query "SELECT COUNT(*) FROM wp_posts WHERE post_type = 'develogic_local'"
wp db query "SELECT * FROM wp_term_taxonomy WHERE taxonomy LIKE 'develogic_%'"
```

Wynik powinien być **pusty** dla wszystkich zapytań.

## Wpływ na użytkownika

- ✅ Brak wpływu na działanie wtyczki
- ✅ Dane są usuwane tylko przy całkowitym usunięciu wtyczki
- ✅ Dezaktywacja zachowuje wszystkie dane (można bezpiecznie ponownie aktywować)
- ✅ Administrator ma kontrolę - czyszczenie następuje tylko na żądanie

## Bezpieczeństwo

✅ Weryfikacja `WP_UNINSTALL_PLUGIN` - zapobiega przypadkowemu uruchomieniu  
✅ Używa WordPress API dla większości operacji  
✅ Bezpieczne zapytania SQL z przygotowanymi statements  
✅ Nie usuwa danych innych wtyczek ani WordPress core  

