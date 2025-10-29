# Changelog - Projekcje przechowywane w WordPress

## Wprowadzone zmiany

### Data: 2025-10-29

#### Synchronizacja projekcji do WordPress Media Library

**Problem:** 
Projekcje (wizualizacje, plany) były pobierane z linków zewnętrznych API podczas każdego wyświetlania strony, co powodowało wolniejsze ładowanie i zależność od dostępności API.

**Rozwiązanie:**
Podczas synchronizacji wtyczka pobiera wszystkie projekcje dla każdej oferty i przechowuje je jako attachmenty w WordPress Media Library.

---

## Zmodyfikowane pliki

### 1. `/includes/class-api-client.php`
- **Dodano metodę:** `download_projection_image($projection_id)`
  - Pobiera surowy obraz projekcji z API Develogic
  - Obsługuje błędy i loguje problemy z pobieraniem
  - Zwraca dane obrazu lub WP_Error

### 2. `/includes/class-sync.php`
- **Zmodyfikowano metodę:** `save_local_meta()`
  - Teraz wywołuje `process_projections()` przed zapisaniem danych projekcji
  - Projekcje są pobierane i uploadowane do WordPress przed zapisaniem metadanych

- **Dodano metodę:** `process_projections($post_id, $projections, $local_number)`
  - Iteruje przez wszystkie projekcje z API
  - Sprawdza, czy projekcja już istnieje w WordPress (unika duplikatów)
  - Pobiera obrazy z API przy użyciu `api_client->download_projection_image()`
  - Tworzy plik tymczasowy i używa `media_handle_sideload()` do utworzenia attachmentu
  - Zapisuje metadane projekcji: `develogic_projection_id`, `develogic_local_post_id`, `develogic_projection_type`
  - Dodaje WordPress URLs do danych projekcji: `wordpress_url`, `thumbnail_url`, `large_url`
  - Loguje błędy, jeśli projekcja nie może być pobrana

- **Dodano metodę:** `get_projection_attachment_id($post_id, $projection_id)`
  - Sprawdza, czy attachment dla danej projekcji już istnieje
  - Zapobiega duplikowaniu obrazów przy ponownej synchronizacji

### 3. Templates (wszystkie szablony używające projekcji)

Zaktualizowano następujące pliki, aby preferować obrazy z WordPress:

- `/templates/apartments-list-new.php`
- `/templates/apartments-list.php`
- `/templates/a1-card.php`
- `/templates/local-single.php`
- `/templates/offers-grid.php`

**Zmiany w templatech:**
- Dodano fallback logic: najpierw sprawdza `wordpress_url`, jeśli nie istnieje używa oryginalnego `uri` z API
- Dla miniatur używa `thumbnail_url` (WordPress automatycznie generuje miniatury)
- Dla dużych obrazów używa `large_url` lub `wordpress_url`

**Przykład kodu:**
```php
// Przed
$image_url = $projection['uri'];

// Po
$image_url = !empty($projection['wordpress_url']) ? $projection['wordpress_url'] : $projection['uri'];
$thumb_url = !empty($projection['thumbnail_url']) ? $projection['thumbnail_url'] : $image_url;
```

---

## Korzyści

1. **Szybkość**: Obrazy są serwowane bezpośrednio z WordPress, bez odwołań do zewnętrznego API
2. **Niezależność**: Strona działa nawet jeśli API Develogic jest tymczasowo niedostępne
3. **Optymalizacja**: WordPress automatycznie generuje miniatury w różnych rozmiarach
4. **Cache**: Obrazy mogą być cache'owane przez CDN i przeglądarki
5. **Backward compatibility**: Stare dane nadal działają (fallback do `uri` z API)

---

## Migracja istniejących danych

Aby pobrać projekcje dla istniejących ofert:
1. Przejdź do panelu administracyjnego WordPress
2. Develogic > Synchronizacja
3. Kliknij "Synchronizuj teraz"
4. Wtyczka automatycznie pobierze wszystkie projekcje i zapisze je w WordPress

**Uwaga**: Pierwsza synchronizacja po wdrożeniu może potrwać dłużej ze względu na pobieranie wszystkich obrazów.

---

## Struktura metadanych attachment

Każdy attachment (obraz projekcji) ma następujące metadane:

- `develogic_projection_id` - ID projekcji z API Develogic
- `develogic_local_post_id` - ID posta WordPress (lokalul)
- `develogic_projection_type` - Typ projekcji (np. "Karta lokalu", "Plan mieszkania")

Dane projekcji w post_meta `projections` zawierają:
```json
{
  "id": 123,
  "type": "Karta lokalu",
  "uri": "https://api.develogic.pl/...",
  "attachment_id": 456,
  "wordpress_url": "https://twoja-strona.pl/wp-content/uploads/...",
  "thumbnail_url": "https://twoja-strona.pl/wp-content/uploads/...-300x300.jpg",
  "large_url": "https://twoja-strona.pl/wp-content/uploads/...-1024x1024.jpg"
}
```

---

## Testowanie

Po wdrożeniu zmiany należy:

1. ✅ Uruchomić synchronizację
2. ✅ Sprawdzić, czy obrazy pojawiają się w Media Library
3. ✅ Zweryfikować, czy oferty wyświetlają obrazy z WordPress
4. ✅ Sprawdzić logi błędów w przypadku problemów z pobieraniem
5. ✅ Przetestować szybkość ładowania strony

---

## Obsługa błędów

- Jeśli projekcja nie może być pobrana, wtyczka loguje ostrzeżenie i kontynuuje z pozostałymi
- Oferta nadal będzie widoczna, ale bez tej konkretnej projekcji
- Przy następnej synchronizacji można spróbować ponownie
- Wszystkie błędy są logowane w `Develogic > Logi synchronizacji`

---

## Kompatybilność wsteczna

✅ **Tak** - Istniejące dane nadal działają:
- Stare projekcje bez `wordpress_url` używają oryginalnego `uri`
- Nowe projekcje preferują `wordpress_url`
- Nie wymaga żadnych zmian w bazie danych

