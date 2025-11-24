# Changelog: Obsługa starszych wersji Image Map Pro (v4/v5)

**Data:** 2025-11-24  
**Wersja:** 2.x  
**Autor:** Michał

## Problem

Plugin nie wykrywał projektów Image Map Pro w starszej wersji (v4/v5), która przechowuje dane w innej lokalizacji niż nowsza wersja (v6+).

### Różnice między wersjami:

**Image Map Pro v6+ (nowsza wersja):**
- Dane przechowywane w tabeli `wp_image_map_pro_projects`
- Struktura JSON: `artboards` → `children` (kształty)

**Image Map Pro v4/v5 (starsza wersja):**
- Dane przechowywane w `wp_options` z kluczem `image-map-pro-wordpress-admin-options`
- Struktura JSON: `spots` (kształty bezpośrednio)
- Przykładowa struktura:
```php
array(
    'purchase_code' => '',
    'saves' => array(
        2926 => array(
            'json' => '{...}',
            'meta' => array(
                'name' => 'ParterHNew_develogic',
                'shortcode' => 'ParterHNew_develogic'
            )
        )
    )
)
```

## Rozwiązanie

### 1. Wykrywanie obu wersji

**Plik:** `includes/class-imagemappro-integration.php`

Metoda `get_all_imagemappro_projects()` została rozszerzona o:
- Wykrywanie projektów z tabeli (v6+)
- Wykrywanie projektów z wp_options (v4/v5)
- Automatyczne oznaczanie wersji w obiekcie projektu (`version = 'old'` lub `'new'`)

```php
private function get_all_imagemappro_projects() {
    $projects = array();
    
    // Nowa wersja (tabela)
    $table_name = $wpdb->prefix . 'image_map_pro_projects';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        // ... pobierz projekty z tabeli
    }
    
    // Stara wersja (wp_options)
    $old_options = get_option('image-map-pro-wordpress-admin-options', false);
    if ($old_options && isset($old_options['saves'])) {
        // ... pobierz projekty z options
    }
    
    return $projects;
}
```

### 2. Przetwarzanie różnych struktur JSON

**Plik:** `includes/class-imagemappro-integration.php`

Metoda `update_project_colors()` została przebudowana:
- Rozpoznawanie wersji projektu
- Dla v4/v5: przetwarzanie `spots` array bezpośrednio
- Dla v6+: przetwarzanie `artboards` → `children`
- Wydzielenie logiki przetwarzania pojedynczego kształtu do `process_single_shape()`

```php
private function update_project_colors($project, $locals) {
    $version = isset($project->version) ? $project->version : 'new';
    
    if ($version === 'old') {
        // Przetwarzaj spots[]
        foreach ($project_data['spots'] as &$shape) {
            $this->process_single_shape($shape, $locals, $project);
        }
    } else {
        // Przetwarzaj artboards[].children[]
        foreach ($project_data['artboards'] as &$artboard) {
            foreach ($artboard['children'] as &$shape) {
                $this->process_single_shape($shape, $locals, $project);
            }
        }
    }
}
```

### 3. Zapisywanie do odpowiedniej lokalizacji

**Plik:** `includes/class-imagemappro-integration.php`

Metoda `save_project_json()` została rozszerzona:
- Parameter `$version` określa gdzie zapisać
- Dla v4/v5: aktualizacja w `wp_options`
- Dla v6+: aktualizacja w tabeli

```php
private function save_project_json($project_id, $json, $version = 'new') {
    if ($version === 'old') {
        // Zapisz do wp_options
        $old_options = get_option('image-map-pro-wordpress-admin-options');
        $old_options['saves'][$project_id]['json'] = $json;
        update_option('image-map-pro-wordpress-admin-options', $old_options);
    } else {
        // Zapisz do tabeli
        $wpdb->update($table_name, array('json' => $json), ...);
    }
}
```

### 4. Interfejs administratora

**Plik:** `admin/class-admin-imagemappro.php`

Zmiany:
- Metoda `get_imagemappro_projects()` wykrywa obu wersji
- W liście projektów wyświetla oznaczenie `[v4/v5]` dla starszych wersji
- W sekcji "Informacje techniczne" pokazuje rozkład wersji projektów

Przykład:
```
Liczba projektów Image Map Pro: 3 (2 v6+, 1 v4/v5)
```

## Testowanie

### 1. Sprawdź wykrywanie projektów:
- Przejdź do: **Develogic → Image Map Pro**
- Powinieneś zobaczyć wszystkie projekty (z obu wersji)
- Projekty ze starszej wersji mają oznaczenie `[v4/v5]`

### 2. Zmapuj budynki:
- Przypisz budynek do projektu ze starszej wersji
- Zapisz mapowania

### 3. Testuj aktualizację kolorów:
- Kliknij "Aktualizuj kolory teraz" w sekcji "Manualna aktualizacja"
- Sprawdź logi w `wp-content/debug.log`:
```
[Develogic ImageMapPro] Detected OLD Image Map Pro version (wp_options based)
[Develogic ImageMapPro] Processing project: ParterHNew_develogic (shortcode: ParterHNew_develogic, version: old)
[Develogic ImageMapPro] OLD version project has X spots
```

### 4. Weryfikacja w bazie danych:
- Przejdź do phpMyAdmin
- Sprawdź tabelę `wp_options`
- Znajdź wiersz z `option_name = 'image-map-pro-wordpress-admin-options'`
- Zweryfikuj, że kolory kształtów zostały zaktualizowane

## Kompatybilność

✅ **Image Map Pro v4/v5** (starsza wersja, wp_options)  
✅ **Image Map Pro v6+** (nowsza wersja, tabela)  
✅ **Obsługa obu wersji jednocześnie**

## Dodatkowe informacje

### Struktura kształtu w obu wersjach:

Obie wersje używają podobnej struktury dla pojedynczego kształtu:

```json
{
  "id": "poly-343",
  "title": "M24",
  "type": "poly",
  "default_style": {
    "background_color": "7ed322",
    "background_opacity": 0.75
  },
  "mouseover_style": {
    "background_color": "7ed322"
  }
}
```

Logika aktualizacji kolorów jest identyczna dla obu wersji - zmienia tylko sposób dostępu do kształtów i metoda zapisu.

## Podsumowanie

Plugin Develogic teraz w pełni obsługuje:
- ✅ Automatyczne wykrywanie wersji Image Map Pro
- ✅ Przetwarzanie projektów z obu wersji
- ✅ Zapisywanie zmian do odpowiednich lokalizacji
- ✅ Wyświetlanie informacji o wersji w panelu administracyjnym
- ✅ Szczegółowe logowanie dla debugowania

Integracja działa bez zmian w konfiguracji użytkownika - wystarczy zmapować budynki na projekty, niezależnie od wersji Image Map Pro.

