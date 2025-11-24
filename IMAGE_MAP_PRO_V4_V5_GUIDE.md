# Przewodnik: Image Map Pro v4/v5 - Integracja z Develogic

## Dla kogo jest ten przewodnik?

Jeśli używasz **starszej wersji Image Map Pro (v4 lub v5)** i projekty nie były wykrywane przez plugin Develogic, ten przewodnik jest dla Ciebie.

## Jak sprawdzić wersję Image Map Pro?

### Metoda 1: Sprawdź w bazie danych

1. Otwórz **phpMyAdmin**
2. Wyszukaj tabelę `wp_image_map_pro_projects`
   - **Jeśli tabela istnieje** → masz Image Map Pro v6+
   - **Jeśli tabeli NIE MA** → masz Image Map Pro v4/v5

### Metoda 2: Sprawdź w panelu Develogic

1. Przejdź do: **WordPress Admin → Develogic → Image Map Pro**
2. W sekcji "Informacje techniczne" znajdziesz:
   ```
   Liczba projektów Image Map Pro: X (Y v6+, Z v4/v5)
   ```

## Konfiguracja dla Image Map Pro v4/v5

### Krok 1: Przygotuj projekty w Image Map Pro

1. Otwórz edytor Image Map Pro
2. Dla każdego kształtu (polygon/spot) ustaw w polu **"title"** numer lokalu z Develogic:
   - Przykład: `M24`, `M50`, `M1`, itp.
   - ⚠️ **Ważne**: Numer musi dokładnie odpowiadać numerowi z Develogic!

3. Zanotuj **shortcode** projektu (np. `ParterHNew_develogic`)

### Krok 2: Ustaw kolory statusów

1. Przejdź do: **Develogic → Image Map Pro**
2. W sekcji "Kolory statusów" ustaw kolory:
   - **Wolny**: `#7ED322` (zielony)
   - **Sprzedany**: `#ee1c24` (czerwony)
   - **Rezerwacja**: `#FFA500` (pomarańczowy)
   - **Niedostępny**: `#cccccc` (szary)
3. Kliknij **"Zapisz kolory"**

### Krok 3: Zmapuj budynki na projekty

1. W sekcji "Mapowanie budynków na projekty Image Map Pro"
2. Dla każdego budynku wybierz odpowiedni projekt
   - Projekty z v4/v5 są oznaczone `[v4/v5]`
   - Możesz wybrać wiele projektów (Ctrl+Click)
3. Kliknij **"Zapisz mapowania"**

### Krok 4: Testuj aktualizację

1. W sekcji "Manualna aktualizacja" kliknij **"Aktualizuj kolory teraz"**
2. Sprawdź komunikat o sukcesie
3. Otwórz stronę z Image Map Pro i zweryfikuj kolory

## Przykład struktury projektu v4/v5

W bazie danych (`wp_options`, klucz: `image-map-pro-wordpress-admin-options`):

```php
array(
    'saves' => array(
        2926 => array(
            'json' => '{
                "general": {
                    "name": "ParterHNew_develogic",
                    "shortcode": "ParterHNew_develogic"
                },
                "spots": [
                    {
                        "id": "poly-343",
                        "title": "M24",
                        "default_style": {
                            "background_color": "7ed322"
                        }
                    },
                    {
                        "id": "poly-5121",
                        "title": "M25",
                        "default_style": {
                            "background_color": "ee1c24"
                        }
                    }
                ]
            }',
            'meta' => array(
                'name' => 'ParterHNew_develogic',
                'shortcode' => 'ParterHNew_develogic'
            )
        )
    )
)
```

## Weryfikacja działania

### 1. Sprawdź logi

Włącz WordPress debug (`wp-config.php`):
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Sprawdź `wp-content/debug.log`:
```
[Develogic ImageMapPro] Detected OLD Image Map Pro version (wp_options based)
[Develogic ImageMapPro] Found 1 Image Map Pro projects
[Develogic ImageMapPro] Processing project: ParterHNew_develogic (shortcode: ParterHNew_develogic, version: old)
[Develogic ImageMapPro] OLD version project has 14 spots
[Develogic ImageMapPro] Found match for shape "M24" -> local M24
[Develogic ImageMapPro] Updating shape "M24" to color #7ed322 (status: Wolny)
[Develogic ImageMapPro] Saving to OLD version (wp_options)
```

### 2. Sprawdź bazę danych

1. Otwórz phpMyAdmin
2. Przejdź do tabeli `wp_options`
3. Znajdź wiersz z `option_name = 'image-map-pro-wordpress-admin-options'`
4. Sprawdź kolumną `option_value` - powinieneś zobaczyć zaktualizowane kolory w JSON

### 3. Sprawdź frontend

1. Otwórz stronę z mapą Image Map Pro
2. Zweryfikuj, czy kolory kształtów odpowiadają statusom lokali:
   - Zielony (#7ed322) → Wolny
   - Czerwony (#ee1c24) → Sprzedany
   - Pomarańczowy (#FFA500) → Rezerwacja
   - Szary (#cccccc) → Niedostępny

## Automatyczna synchronizacja

Po wykonaniu konfiguracji, kolory będą automatycznie aktualizowane:
- **Po każdej synchronizacji** z Develogic (WP-Cron)
- **Po manualnej synchronizacji** w panelu administracyjnym

## Troubleshooting

### Problem: Projekty nie są wykrywane

**Rozwiązanie:**
1. Sprawdź czy opcja `image-map-pro-wordpress-admin-options` istnieje w `wp_options`
2. Zweryfikuj, czy struktura zawiera klucz `saves`

### Problem: Kolory nie są aktualizowane

**Możliwe przyczyny:**
1. **Nieprawidłowe numery lokali** - sprawdź czy `title` kształtu dokładnie odpowiada numerowi lokalu
2. **Brak mapowania budynku** - sprawdź sekcję "Mapowanie budynków"
3. **Błędny status** - sprawdź w Develogic czy lokal ma przypisany status

**Debugowanie:**
```php
// Włącz szczegółowe logowanie
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Sprawdź logi
tail -f wp-content/debug.log
```

### Problem: Kształt M63 nie jest wykrywany

**Rozwiązanie:**
1. Sprawdź w Image Map Pro czy kształt ma `title = "M63"` (bez spacji)
2. Sprawdź w Develogic czy lokal ma numer `M63` lub `externalNumber = "M63"`
3. Zweryfikuj mapowanie budynku - czy projekt jest przypisany do właściwego budynku?

## Migracja z v4/v5 do v6+

Jeśli planujesz upgrade Image Map Pro:
1. **Przed migracją** - zrób backup bazy danych (tabela `wp_options`)
2. Po migracji plugin automatycznie wykryje nową wersję
3. Mapowania budynków pozostaną bez zmian
4. Kolory będą kontynuowane aktualizowanie

## Wsparcie

W razie problemów:
1. Sprawdź `wp-content/debug.log`
2. Sprawdź sekcję "Informacje techniczne" w panelu Develogic
3. Zweryfikuj strukturę JSON w bazie danych

---

**Plugin Develogic** - Wersja 2.x  
**Obsługuje:** Image Map Pro v4, v5, v6+

