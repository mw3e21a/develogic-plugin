# Changelog: Integracja z Image Map Pro

## Data: 2025-11-18

### 🎨 Automatyczna aktualizacja kolorów w Image Map Pro

#### Opis funkcjonalności

Wtyczka Develogic teraz automatycznie aktualizuje kolory kształtów (polygonów) w Image Map Pro na podstawie statusu lokalu z systemu Develogic.

#### Jak to działa?

1. **Automatyczna aktualizacja po synchronizacji**
   - Po każdej synchronizacji lokali z API Develogic, system automatycznie aktualizuje kolory w Image Map Pro
   - Kształty na mapie zmieniają kolor w zależności od statusu: Wolny, Sprzedany, Rezerwacja, Niedostępny

2. **Mapowanie statusów na kolory**
   - Wolny → Zielony (#7ED322)
   - Sprzedany → Czerwony (#ee1c24)
   - Rezerwacja → Pomarańczowy (#FFA500)
   - Niedostępny → Szary (#cccccc)
   - Kolory można dostosować w panelu administracyjnym

3. **Mapowanie budynków na projekty**
   - Każdy budynek z Develogic można przypisać do konkretnego projektu Image Map Pro (shortcode)
   - System automatycznie znajdzie odpowiednie kształty na podstawie numeru lokalu

#### Konfiguracja

##### Krok 1: Przygotowanie Image Map Pro

1. Utwórz projekt w Image Map Pro dla każdego budynku/piętra
2. W każdym kształcie (polygon) ustaw w polu **"title"** numer lokalu z Develogic
   - Np. dla lokalu "43" ustaw title: `43`
   - System będzie dopasowywał kształty na podstawie pola `number` lub `externalNumber` z Develogic

##### Krok 2: Konfiguracja kolorów

1. Przejdź do **Develogic → Image Map Pro** w panelu WordPress
2. W sekcji **"Kolory statusów"** ustaw kolory dla każdego statusu:
   - Kliknij w pole koloru
   - Wybierz kolor z palety WordPress Color Picker
   - Zapisz zmiany

##### Krok 3: Mapowanie budynków

1. W sekcji **"Mapowanie budynków na projekty Image Map Pro"**:
2. Dla każdego budynku wybierz odpowiedni shortcode Image Map Pro
   - Np. Budynek "H" → Shortcode "Pietro_3H"
3. Zapisz mapowania

##### Krok 4: Test integracji

1. Kliknij przycisk **"Aktualizuj kolory teraz"** w sekcji "Manualna aktualizacja"
2. Sprawdź logi w **Develogic → Synchronizacja**
3. Otwórz projekt w Image Map Pro i sprawdź, czy kolory się zaktualizowały

#### Wymagania

1. **Wtyczka Image Map Pro** musi być zainstalowana i aktywna
2. **Lokale muszą być zsynchronizowane** z Develogic (wykonaj synchronizację)
3. **Kształty w Image Map Pro** muszą mieć w polu "title" numer lokalu

#### Struktura danych

##### Image Map Pro JSON

Integracja modyfikuje pole `default_style.background_color` w każdym kształcie:

```json
{
  "id": "poly-740",
  "title": "44",
  "type": "poly",
  "default_style": {
    "background_color": "7ED322",  // ← Ten kolor jest aktualizowany
    "background_opacity": 0.73,
    "border_radius": 50
  }
}
```

##### Mapowanie danych

System dopasowuje:
- **Shape title** (Image Map Pro) ↔ **number** lub **externalNumber** (Develogic)
- **Building ID** (Develogic) ↔ **Shortcode** (Image Map Pro)

#### Przykład użycia

```
Masz budynek "H" z lokalami:
- Lokal 44 - Status: Sprzedany
- Lokal 43 - Status: Rezerwacja  
- Lokal 42 - Status: Wolny

W Image Map Pro:
- Projekt: "Pietro 3 Budynek H" (shortcode: Pietro_3H)
- Kształty z title: "44", "43", "42"

Po synchronizacji:
- Shape "44" → Kolor czerwony (Sprzedany)
- Shape "43" → Kolor pomarańczowy (Rezerwacja)
- Shape "42" → Kolor zielony (Wolny)
```

#### Pliki dodane/zmodyfikowane

**Nowe pliki:**
- `includes/class-imagemappro-integration.php` - główna klasa integracji
- `admin/class-admin-imagemappro.php` - panel administracyjny
- `CHANGELOG_IMAGE_MAP_PRO_INTEGRATION.md` - ta dokumentacja

**Zmodyfikowane pliki:**
- `develogic-integration.php` - włączenie integracji
- `includes/class-sync.php` - dodano hook `do_action('develogic_sync_completed')`

#### Opcje w bazie danych

- `develogic_imagemappro_colors` - mapowanie statusów na kolory
- `develogic_imagemappro_building_map` - mapowanie budynków na shortcode'y

#### Logi

Wszystkie operacje są logowane w **Develogic → Synchronizacja**:
- Aktualizacje projektów
- Liczba zaktualizowanych kształtów
- Błędy i ostrzeżenia

#### Hook dla developerów

```php
// Hook wywoływany po zakończeniu synchronizacji
do_action('develogic_sync_completed', $stats);

// Przykład użycia w motywie/innym pluginie:
add_action('develogic_sync_completed', function($stats) {
    // Twój kod po synchronizacji
}, 10, 1);
```

#### API klasy integracji

```php
$integration = new Develogic_ImageMapPro_Integration();

// Pobranie aktualnych kolorów
$colors = $integration->get_status_colors();

// Ustawienie koloru dla statusu
$integration->set_status_color('Wolny', '00FF00');

// Pobranie mapowania budynków
$map = $integration->get_building_map();

// Ustawienie mapowania
$integration->set_building_map('H', 'Pietro_3H');

// Czyszczenie mapowań
$integration->clear_mappings();
```

#### Rozwiązywanie problemów

**Problem: Kolory się nie aktualizują**
- Sprawdź, czy Image Map Pro jest aktywne
- Sprawdź logi w Develogic → Synchronizacja
- Upewnij się, że kształty mają poprawne numery w polu "title"
- Sprawdź mapowanie budynków

**Problem: Nie znajduje odpowiednich kształtów**
- Upewnij się, że numery w "title" dokładnie odpowiadają numerom lokali
- Sprawdź czy jest ustawione mapowanie budynku na shortcode
- Sprawdź czy lokale są przypisane do właściwego budynku w Develogic

**Problem: Wtyczka Image Map Pro nie jest wykrywana**
- Upewnij się, że Image Map Pro jest zainstalowana i aktywowana
- Sprawdź czy istnieje klasa `ImageMapPro_v6` lub `ImageMapPro`

#### Bezpieczeństwo

- Wszystkie dane są sanitizowane przed zapisem
- Nonce verification dla formularzy administracyjnych
- Sprawdzanie uprawnień `manage_options`
- Tylko administratorzy mogą zmieniać ustawienia

#### Wydajność

- Aktualizacja działa tylko po synchronizacji (nie przy każdym ładowaniu strony)
- Można ręcznie uruchomić aktualizację bez pełnej synchronizacji
- Batch processing dla wszystkich projektów i kształtów
- Minimalne obciążenie bazy danych

---

**Autor:** Michal  
**Data:** 2025-11-18  
**Wersja Develogic:** 2.1.1+

