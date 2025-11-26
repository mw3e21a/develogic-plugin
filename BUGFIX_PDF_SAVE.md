# Bugfix: PDF nie zapisywał się podczas synchronizacji

**Data**: 2025-11-26  
**Problem**: Oryginalny PDF nie był zapisywany, tylko skonwertowany JPG

---

## 🐛 Problem

Po implementacji zapisywania PDF obok JPG, podczas synchronizacji:
- ✅ JPG był zapisywany prawidłowo
- ❌ PDF nie był zapisywany w bibliotece mediów
- ❌ Przycisk "Pobierz kartę lokalu" nie działał (brak PDF URL)

---

## 🔍 Przyczyna

Funkcja WordPress `media_handle_sideload()` **automatycznie usuwa** plik tymczasowy po zapisaniu go jako attachment.

**Pierwotny kod (błędny):**

```php
if ($is_pdf) {
    // Save PDF
    $pdf_attachment_id = media_handle_sideload([
        'tmp_name' => $temp_file,  // PDF file
        // ...
    ]);
    
    // Convert PDF to JPEG
    $jpeg_file = $this->convert_pdf_to_jpeg($temp_file, ...);  // ❌ $temp_file już nie istnieje!
}
```

**Problem:** Po wywołaniu `media_handle_sideload()`, plik `$temp_file` jest usuwany przez WordPress. Próba konwersji na JPG kończyła się błędem, bo plik nie istniał.

---

## ✅ Rozwiązanie

Przed zapisaniem PDF, tworzymy jego **kopię** do konwersji:

```php
if ($is_pdf) {
    // 1. Create a copy for conversion
    $pdf_copy_file = $temp_file . '.copy';
    copy($temp_file, $pdf_copy_file);
    
    // 2. Save original PDF (this deletes $temp_file)
    $pdf_attachment_id = media_handle_sideload([
        'tmp_name' => $temp_file,  // Original PDF - will be deleted
        // ...
    ]);
    
    // 3. Convert the COPY to JPEG
    $jpeg_file = $this->convert_pdf_to_jpeg($pdf_copy_file, ...);  // ✅ Copy exists!
    
    // 4. Use JPEG for next step
    $temp_file = $jpeg_file;
    
    // 5. Clean up copy
    @unlink($pdf_copy_file);
}
```

---

## 🔄 Przepływ plików

1. **Pobierz PDF z API** → `temp_file.pdf`
2. **Skopiuj** → `temp_file.pdf.copy`
3. **Zapisz oryginalny** → `media_handle_sideload(temp_file.pdf)` → **usuwa** `temp_file.pdf`
4. **Konwertuj kopię** → `convert_pdf_to_jpeg(temp_file.pdf.copy)` → `temp_file.jpg`
5. **Zapisz JPG** → `media_handle_sideload(temp_file.jpg)`
6. **Usuń kopię** → `unlink(temp_file.pdf.copy)`

**Rezultat:** Oba pliki zapisane w bibliotece mediów:
- `lokal-M1-projekcja-56-karta-lokalu.pdf` (oryginalny)
- `lokal-M1-projekcja-56-karta-lokalu.jpg` (skonwertowany)

---

## 📝 Zmiany w kodzie

**Plik:** `includes/class-sync.php`  
**Linie:** 520-595

### Dodane:
- Kopiowanie pliku przed zapisaniem: `copy($temp_file, $pdf_copy_file)`
- Obsługa błędu kopiowania
- Logowanie błędów zapisu PDF
- Konwersja kopii zamiast oryginału
- Czyszczenie kopii po konwersji

---

## 🧪 Testowanie

1. Uruchom synchronizację mieszkań
2. Sprawdź **Biblioteka mediów** w WordPress
3. Powinny być widoczne **dwa pliki** dla każdej "Karty lokalu":
   - `lokal-XXX-projekcja-YYY-karta-lokalu.pdf`
   - `lokal-XXX-projekcja-YYY-karta-lokalu.jpg`
4. Sprawdź listę mieszkań - przycisk PDF powinien być widoczny
5. Kliknij przycisk - PDF powinien się otworzyć w nowej karcie

---

## 📌 Wnioski

- `media_handle_sideload()` **zawsze usuwa** plik tymczasowy
- Gdy trzeba użyć pliku wielokrotnie, **najpierw go skopiuj**
- Zawsze sprawdzaj czy operacje na plikach się powiodły
- Pamiętaj o czyszczeniu plików tymczasowych

