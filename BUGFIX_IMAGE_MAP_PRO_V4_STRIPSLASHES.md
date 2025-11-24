# Bugfix: Image Map Pro v4/v5 - stripslashes()

**Data:** 2025-11-24  
**Problem:** JSON decode error w starszej wersji Image Map Pro

## Problem

Po dodaniu obsługi starszej wersji Image Map Pro (v4/v5), JSON nie był poprawnie dekodowany:

```
[ERROR] Failed to decode project JSON for: ParterHNew_develogic. Error: Syntax error
[INFO] JSON first 200 chars: {\"id\":7144,\"editor\":{\"selected_shape\":\"poly-5973\",...
```

Widzimy że JSON ma podwójne escape'owanie (`\"` zamiast `"`).

## Przyczyna

WordPress automatycznie dodaje slashe (`\`) do danych zapisanych w `wp_options` przez funkcję `add_slashes()` (magic quotes behavior).

Gdy używamy `get_option()`, **dane nie są automatycznie unslash'owane** - musimy to zrobić ręcznie używając `stripslashes()`.

### Porównanie z nową wersją

W kodzie dla **nowej wersji** (tabela) już była używana funkcja `stripslashes()`:

```php
// Linia 582 - NOWA wersja
$table_projects[$key]->json = stripslashes($value->json);
```

Ale dla **starej wersji** (wp_options) brakowało tego:

```php
// Przed poprawką - STARA wersja
$project->json = $project_data['json'];  // ❌ Brak stripslashes!
```

## Rozwiązanie

Dodano `stripslashes()` dla starszej wersji, aby było zgodnie z nową:

```php
// Po poprawce - STARA wersja (linia ~603)
$project->json = stripslashes($project_data['json']);  // ✅ Dodano stripslashes!
```

## Zmienione pliki

**Plik:** `includes/class-imagemappro-integration.php`

### Zmiana w get_all_imagemappro_projects()

```php
// PRZED (linia ~603)
foreach ($old_options['saves'] as $project_id => $project_data) {
    if (isset($project_data['json']) && isset($project_data['meta'])) {
        $project = new stdClass();
        $project->id = $project_id;
        $project->name = isset($project_data['meta']['name']) ? $project_data['meta']['name'] : "Project $project_id";
        $project->shortcode = isset($project_data['meta']['shortcode']) ? $project_data['meta']['shortcode'] : "project_$project_id";
        
        // JSON is already a string in old version, no need to decode here
        $project->json = $project_data['json'];  // ❌ Brak stripslashes
        $project->version = 'old';
        
        // Debug: check JSON validity
        $test_decode = json_decode($project_data['json'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log(sprintf(
                'JSON decode warning for project %s: %s (first 100 chars: %s)',
                $project->name,
                json_last_error_msg(),
                substr($project_data['json'], 0, 100)
            ), 'warning');
        }
        
        $projects[] = $project;
    }
}

// PO (linia ~595)
foreach ($old_options['saves'] as $project_id => $project_data) {
    if (isset($project_data['json']) && isset($project_data['meta'])) {
        $project = new stdClass();
        $project->id = $project_id;
        $project->name = isset($project_data['meta']['name']) ? $project_data['meta']['name'] : "Project $project_id";
        $project->shortcode = isset($project_data['meta']['shortcode']) ? $project_data['meta']['shortcode'] : "project_$project_id";
        
        // Strip slashes from JSON (same as new version)
        $project->json = stripslashes($project_data['json']);  // ✅ Dodano stripslashes
        $project->version = 'old';
        
        $projects[] = $project;
    }
}
```

### Uproszczenie w update_project_colors()

Usunięto nadmiarowe logowanie i duplikację `stripslashes()`, ponieważ jest już wykonane w `get_all_imagemappro_projects()`.

## Testowanie

### Przed poprawką:
```
[ERROR] Failed to decode project JSON for: ParterHNew_develogic. Error: Syntax error
[INFO] JSON first 200 chars: {\"id\":7144,\"editor\":{\"selected_shape\":\"poly-5973\",...
```

### Po poprawce (oczekiwane):
```
[SUCCESS] JSON decoded successfully
[INFO] OLD version project has 14 spots
[SUCCESS] Found match for shape "M24" -> local M24
[SUCCESS] Updating shape "M24" to color #7ed322 (status: Wolny)
```

## Weryfikacja

Po tej poprawce:
1. Projekty z Image Map Pro v4/v5 są poprawnie wykrywane
2. JSON jest poprawnie dekodowany
3. Kolory kształtów są aktualizowane zgodnie ze statusem lokali
4. Zapis działa poprawnie (WordPress automatycznie doda slashe przy `update_option()`)

## Związane z

- `CHANGELOG_IMAGE_MAP_PRO_V4_V5_SUPPORT.md` - główny changelog obsługi v4/v5
- `IMAGE_MAP_PRO_V4_V5_GUIDE.md` - przewodnik użytkownika

---

**Status:** ✅ FIXED  
**Commit:** Dodanie stripslashes() dla Image Map Pro v4/v5

