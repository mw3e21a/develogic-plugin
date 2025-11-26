# Changelog: Przycisk "Wymuś re-synchronizację projekcji"

**Data**: 2025-11-26  
**Wersja**: 2.3.2

---

## 🎯 Cel

Dodanie przycisku w panelu administracyjnym do wymuszenia ponownego pobrania wszystkich projekcji (zdjęć i PDF).

---

## ✅ Zmiany

### 1. **Nowy przycisk w panelu synchronizacji**

W WordPress Admin → Develogic → Synchronizacja, w sekcji "Operacje zaawansowane":

```php
<form method="post" action="<?php echo admin_url('admin-post.php'); ?>" 
      onsubmit="return confirm('To usunie wszystkie zdjęcia i PDF projekcji...');">
    <input type="hidden" name="action" value="develogic_force_resync_projections">
    <?php wp_nonce_field('develogic_force_resync_projections', 'develogic_resync_nonce'); ?>
    <?php submit_button(__('🔄 Wymuś re-synchronizację projekcji', 'develogic'), 'secondary'); ?>
    <p class="description">
        Usuwa wszystkie załączniki projekcji (zdjęcia i PDF) i wymusza 
        ponowne pobranie podczas następnej synchronizacji.
    </p>
</form>
```

**Opis działania:**
- Znajduje wszystkie mieszkania w bazie
- Usuwa wszystkie attachmenty projekcji (JPG i PDF)
- Wyświetla komunikat o sukcesie z licznikami
- Loguje operację do sync log

---

### 2. **Nowy handler: `handle_force_resync_projections()`**

```php
public function handle_force_resync_projections() {
    // 1. Sprawdzenie uprawnień i nonce
    if (!current_user_can('manage_options')) {
        wp_die(__('Brak uprawnień', 'develogic'));
    }
    check_admin_referer('develogic_force_resync_projections', 'develogic_resync_nonce');
    
    // 2. Pobranie wszystkich mieszkań
    $query = new WP_Query([
        'post_type' => 'develogic_local',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ]);
    
    // 3. Dla każdego mieszkania - usuń attachmenty projekcji
    foreach ($query->posts as $post_id) {
        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_parent' => $post_id,
            'meta_query' => [[
                'key' => 'develogic_projection_id',
                'compare' => 'EXISTS',
            ]],
        ]);
        
        foreach ($attachments as $attachment_id) {
            wp_delete_attachment($attachment_id, true); // true = force delete files
        }
    }
    
    // 4. Log operacji
    $log = get_option('develogic_sync_log', []);
    $log[] = [
        'time' => current_time('mysql'),
        'level' => 'info',
        'message' => sprintf(
            'Wymuszono re-synchronizację projekcji: usunięto %d attachmentów z %d mieszkań',
            $deleted_count,
            $local_count
        ),
    ];
    update_option('develogic_sync_log', $log);
    
    // 5. Redirect z komunikatem
    wp_redirect(add_query_arg([
        'page' => 'develogic-sync',
        'resync_projections' => 'success',
        'deleted_count' => $deleted_count,
        'local_count' => $local_count,
    ], admin_url('admin.php')));
}
```

---

### 3. **Komunikat sukcesu**

Po wykonaniu operacji wyświetlany jest komunikat:

```
✅ Usunięto 768 załączników projekcji z 384 mieszkań. 
   Uruchom teraz synchronizację aby pobrać pliki ponownie.
```

---

## 🔧 Jak to działa?

### Przepływ operacji

1. **Użytkownik klika przycisk** "Wymuś re-synchronizację projekcji"
2. **Potwierdzenie** - wyświetlany jest dialog JavaScript
3. **Usuwanie attachmentów** - wszystkie projekcje (JPG i PDF) są usuwane
4. **Komunikat sukcesu** - informacja o liczbie usuniętych plików
5. **Synchronizacja** - użytkownik uruchamia normalną synchronizację
6. **Pobieranie** - plugin wykrywa brak attachmentów i pobiera je ponownie

### Co jest usuwane?

Wszystkie attachmenty które mają metadata:
- `develogic_projection_id` - ID projekcji z API
- `develogic_is_pdf_original` - flaga PDF (opcjonalnie)

Obejmuje to:
- **JPG** - skonwertowane obrazy do wyświetlania
- **PDF** - oryginalne pliki do pobrania
- Wszystkie rozmiary miniatur WordPress

---

## 📝 Zmiany w plikach

### `admin/class-admin-sync.php`

**Dodane:**
- Handler `handle_force_resync_projections()` (linie 532-605)
- Przycisk w sekcji "Operacje zaawansowane" (linie 249-257)
- Komunikat sukcesu (linie 127-134)
- Hook `admin_post_develogic_force_resync_projections` (linia 28)

---

## 🎨 UI/UX

### Lokalizacja przycisku

**WordPress Admin → Develogic → Synchronizacja**
- Sekcja: "Operacje zaawansowane"
- Pod przyciskiem "Synchronizuj teraz"
- Nad przyciskiem "Wyczyść wszystkie lokale"

### Bezpieczeństwo

1. **Potwierdzenie JavaScript** - dialog przed wykonaniem
2. **WordPress nonce** - zabezpieczenie przed CSRF
3. **Uprawnienia** - tylko dla `manage_options`
4. **Logowanie** - operacja zapisywana w sync log

---

## 🧪 Kiedy używać?

### Użyj tego przycisku gdy:

✅ Chcesz dodać PDF do istniejących mieszkań (po aktualizacji pluginu)
✅ Pliki projekcji są uszkodzone lub nieprawidłowe
✅ API Develogic zaktualizowało pliki projekcji
✅ Chcesz wymusić aktualizację wszystkich zdjęć

### Nie używaj jeśli:

❌ Dopiero uruchamiasz plugin (użyj normalnej synchronizacji)
❌ Chcesz tylko zsynchronizować dane mieszkań (bez projekcji)
❌ Masz problem z pojedynczym mieszkaniem (skontaktuj się z supportem)

---

## 📌 Uwagi

- **Operacja jest bezpieczna** - usuwa tylko attachmenty, nie dane mieszkań
- **Wymaga ponownej synchronizacji** - pliki nie są pobierane natychmiast
- **Może zająć czas** - dla dużej liczby mieszkań operacja trwa kilka sekund
- **Zużywa API quota** - ponowne pobranie wszystkich projekcji

---

## 🔗 Powiązane zmiany

Ten przycisk współpracuje z:
- [Przycisk "Pobierz kartę lokalu"](CHANGELOG_PDF_DOWNLOAD_BUTTON.md)
- [Synchronizacja PDF i JPG](BUGFIX_PDF_SAVE.md)
- [Wykrywanie istniejących projekcji](includes/class-sync.php)

