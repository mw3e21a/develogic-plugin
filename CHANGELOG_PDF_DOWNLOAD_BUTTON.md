# Changelog: Przycisk "Pobierz kartę lokalu" (PDF)

**Data**: 2025-11-26  
**Wersja**: 2.3.1

---

## 🎯 Cel

Dodanie przycisku "Pobierz kartę lokalu" w widoku listy mieszkań, który otwiera oryginalny PDF z projekcji "Karta lokalu" w nowym oknie.

---

## ✅ Zmiany

### 1. **Synchronizacja zapisuje PDF i JPG osobno**

**Poprzednio:** PDF był konwertowany na JPG i tylko JPG był zapisywany  
**Teraz:** Zapisywane są **oba pliki** - oryginalny PDF i skonwertowany JPG

#### Zmieniona logika w `includes/class-sync.php`:

```php
// Convert PDF to JPEG if needed (but keep both PDF and JPEG)
$pdf_attachment_id = null;
if ($is_pdf) {
    // WAŻNE: Create a copy of PDF for conversion 
    // (media_handle_sideload deletes the temp file after saving)
    $pdf_copy_file = $temp_file . '.copy';
    if (!copy($temp_file, $pdf_copy_file)) {
        // Handle error
        continue;
    }
    
    // First, save the original PDF
    $pdf_file_array = array(
        'name' => $filename,
        'tmp_name' => $temp_file,  // This will be consumed by media_handle_sideload
    );
    
    $pdf_attachment_id = media_handle_sideload(
        $pdf_file_array,
        $post_id,
        sprintf(
            'Lokal %s - %s (PDF)',
            $local_number,
            $projection['type']
        )
    );
    // Note: $temp_file is now deleted by media_handle_sideload
    
    if (!is_wp_error($pdf_attachment_id)) {
        // Save PDF attachment metadata
        update_post_meta($pdf_attachment_id, 'develogic_projection_id', $projection_id);
        update_post_meta($pdf_attachment_id, 'develogic_local_post_id', $post_id);
        update_post_meta($pdf_attachment_id, 'develogic_projection_type', $projection['type']);
        update_post_meta($pdf_attachment_id, 'develogic_is_pdf_original', true);
    }
    
    // Now convert PDF COPY to JPEG for display
    $jpeg_file = $this->convert_pdf_to_jpeg($pdf_copy_file, ...);
    
    if ($jpeg_file && file_exists($jpeg_file)) {
        $temp_file = $jpeg_file;  // Use JPEG for next step
        $filename = basename($jpeg_file);
        @unlink($pdf_copy_file);  // Clean up copy
    }
}

// Add PDF URL to projection data
if ($pdf_attachment_id && !is_wp_error($pdf_attachment_id)) {
    $projection['pdf_attachment_id'] = $pdf_attachment_id;
    $projection['pdf_url'] = wp_get_attachment_url($pdf_attachment_id);
}
```

**Ważny szczegół techniczny:**  
`media_handle_sideload()` **usuwa plik tymczasowy** po zapisaniu go jako attachment. Dlatego tworzymy kopię PDF przed zapisaniem - oryginał jest zapisywany, a kopia jest konwertowana na JPG.

**Korzyści:**
- ✅ PDF jest dostępny bezpośrednio przez WordPress (nie wymaga API key)
- ✅ JPG jest używany do wyświetlania w galerii
- ✅ PDF jest dostępny do pobrania przez użytkowników
- ✅ Oba pliki są prawidłowo powiązane z postem

---

### 2. **Nowa logika pobierania linku PDF w template**

Template używa teraz `pdf_url` z danych projekcji (link do PDF zapisanego w WordPress):

```php
// PDF link - get WordPress PDF URL from "Karta lokalu" projection
$pdf_link = '';
foreach ($projections as $proj) {
    if (isset($proj['type']) && $proj['type'] === 'Karta lokalu' && !empty($proj['pdf_url'])) {
        $pdf_link = $proj['pdf_url'];
        break;
    }
}

// Fallback to pattern-based PDF link if projection not found
if (empty($pdf_link)) {
    $pdf_source = develogic()->get_setting('pdf_source', 'off');
    if ($pdf_source === 'pattern') {
        $pdf_pattern = develogic()->get_setting('pdf_pattern', '');
        if (!empty($pdf_pattern)) {
            $pdf_link = str_replace(
                array('{localId}', '{number}'),
                array($local['localId'], $local['number']),
                $pdf_pattern
            );
        }
    }
}
```

**Korzyści:**
- ✅ Używa WordPress URL (np. `/wp-content/uploads/2025/11/lokal-M1-projekcja-56-karta-lokalu.pdf`)
- ✅ Nie wymaga klucza API - PDF jest publicznie dostępny
- ✅ Automatycznie pobiera prawidłowy link bez dodatkowej konfiguracji
- ✅ Zachowana kompatybilność wsteczna z wzorcem PDF (fallback)

---

### 2. **Nowy przycisk w akcjach mieszkania**

Przycisk został dodany w sekcji `apartment-actions`, między przyciskiem "Spacer 3D" a przyciskiem email:

```php
<?php if (!empty($pdf_link)): ?>
<a href="<?php echo esc_url($pdf_link); ?>" 
   class="icon-btn icon-btn-pdf" 
   target="_blank" 
   rel="noopener noreferrer" 
   aria-label="<?php esc_attr_e('Pobierz kartę lokalu', 'develogic'); ?>" 
   title="Pobierz kartę lokalu (otwiera PDF w nowej karcie)" 
   onclick="event.stopPropagation();">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="16" y1="13" x2="8" y2="13"/>
        <line x1="16" y1="17" x2="8" y2="17"/>
        <polyline points="10 9 9 9 8 9"/>
    </svg>
</a>
<?php endif; ?>
```

**Właściwości przycisku:**
- ✅ Wyświetla się tylko gdy `$pdf_link` jest dostępny
- ✅ Otwiera PDF w nowym oknie (`target="_blank"`)
- ✅ Ikona dokumentu PDF
- ✅ Tooltip informujący użytkownika
- ✅ `onclick="event.stopPropagation()"` - kliknięcie nie otwiera modala

---

## 🎨 Kolejność przycisków

W sekcji `apartment-actions`:
1. **Spacer 3D 360°** (jeśli dostępny)
2. **Pobierz kartę lokalu** (PDF) - **NOWY** (jeśli dostępny)
3. **Email** (zawsze widoczny)
4. **Ulubione** (jeśli włączone w shortcode)

---

## 📝 Zmiany w plikach

### `includes/class-sync.php`

**Linie 520-600** - Zmieniona logika przetwarzania PDF:
- Najpierw zapisuje oryginalny PDF jako attachment
- Dodaje metadata: `develogic_is_pdf_original = true`
- Następnie konwertuje PDF na JPG
- Zapisuje również JPG jako osobny attachment
- Dodaje `pdf_url` i `pdf_attachment_id` do danych projekcji

**Nowe metadata dla PDF:**
- `develogic_projection_id` - ID projekcji z API
- `develogic_local_post_id` - ID posta WordPress
- `develogic_projection_type` - Typ projekcji (np. "Karta lokalu")
- `develogic_is_pdf_original` - Flaga oznaczająca oryginalny PDF

---

### `templates/apartments-list.php`

**Linie 352-373** - Zmieniona logika pobierania PDF:
- Szuka projekcji typu "Karta lokalu"
- Używa `pdf_url` (WordPress URL do zapisanego PDF)
- Fallback do wzorca PDF z ustawień

**Linie 636-647** - Dodany nowy przycisk:
- Ikona dokumentu PDF
- Link otwarty w nowej karcie
- Warunek wyświetlania: `!empty($pdf_link)`
- Publicznie dostępny URL bez potrzeby API key

---

## 🔧 Jak to działa?

### Przepływ danych

1. **Synchronizacja** (`includes/class-sync.php`):
   - Pobiera projekcje z API Develogic (z użyciem API key)
   - Wykrywa PDF po magic bytes (`%PDF`)
   - **Zapisuje oryginalny PDF** jako attachment WordPress
   - Konwertuje PDF → JPG dla wyświetlania na stronie
   - **Zapisuje również JPG** jako osobny attachment
   - Dodaje `pdf_url` i `pdf_attachment_id` do danych projekcji

2. **Template** (`templates/apartments-list.php`):
   - Szuka projekcji typu "Karta lokalu"
   - Pobiera jej `pdf_url` (WordPress URL do zapisanego PDF)
   - Wyświetla przycisk, który linkuje do tego pliku
   - Przeglądarka pobiera/otwiera PDF bez potrzeby API key

### Różnica między JPG a PDF

- **JPG** (`wordpress_url`): Skonwertowany obraz dla galerii i wyświetlania
  - Przykład: `/wp-content/uploads/2025/11/lokal-M1-projekcja-56-karta-lokalu.jpg`
- **PDF** (`pdf_url`): Oryginalny plik do pobrania/wydruku
  - Przykład: `/wp-content/uploads/2025/11/lokal-M1-projekcja-56-karta-lokalu.pdf`

### Dlaczego oba pliki?

**Problem:** URI z API (`https://domelcki.ondevelogic.com/api/fis/v1/feed/projection/56`) wymaga API key w nagłówku - nie można go użyć bezpośrednio jako linku publicznego.

**Rozwiązanie:** Podczas synchronizacji pobieramy PDF przez API i zapisujemy go w WordPress, dzięki czemu staje się publicznie dostępny pod normalnym URL.

---

## 🧪 Testowanie

1. **Uruchom synchronizację** - nowe mieszkania będą miały zapisane PDF
2. Sprawdź bibliotekę mediów WordPress - powinny być tam pliki PDF i JPG
3. Upewnij się, że mieszkania mają projekcję typu "Karta lokalu"
4. Sprawdź, czy przycisk PDF pojawia się na liście mieszkań
5. Kliknij przycisk - PDF powinien otworzyć się w nowej karcie
6. Upewnij się, że kliknięcie nie otwiera modala mieszkania
7. Sprawdź URL PDF - powinien być WordPress URL (nie API Develogic)

### Sprawdzenie w WP Admin

W **Biblioteka mediów** znajdź pliki dla jednego mieszkania:
- `lokal-M1-projekcja-56-karta-lokalu.pdf` (oryginalny PDF)
- `lokal-M1-projekcja-56-karta-lokalu.jpg` (skonwertowany obraz)

Oba pliki powinny być powiązane z postem mieszkania.

---

## 📌 Uwagi

- Przycisk pojawia się **tylko** gdy projekcja "Karta lokalu" ma zapisany PDF
- **Istniejące mieszkania:** Wymagana ponowna synchronizacja, aby zapisać PDF
- Jeśli brak projekcji, można użyć wzorca PDF w ustawieniach (fallback)
- PDF otwiera się w nowej karcie - użytkownik może go pobrać lub wydrukować
- Oryginalny PDF ma wyższą jakość niż skonwertowany JPG
- PDF jest publicznie dostępny bez potrzeby API key
- Oba pliki (PDF i JPG) są zapisywane w bibliotece mediów WordPress

---

## 🔮 Przyszłe ulepszenia (opcjonalnie)

- [ ] Dodać również przycisk PDF w modalu szczegółów mieszkania
- [ ] Opcja automatycznego pobrania zamiast otwierania w karcie
- [ ] Ikona ładowania podczas pobierania PDF
- [ ] Licznik pobrań PDF dla statystyk

