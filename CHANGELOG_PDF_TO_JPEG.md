# Changelog: Konwersja PDF → JPEG dla Projekcji

**Data**: 2025-11-13  
**Wersja**: 2.2

---

## 🎯 Problem

Projekcje (rzuty lokali) z API Develogic są zwracane jako **pliki PDF**, a nie JPG/PNG:
- Plugin zapisywał je z rozszerzeniem `.jpg`, ale nadal były PDF-ami
- HTML `<img src="...pdf">` nie wyświetlał zdjęć
- Brak obrazków w galerii lokali

---

## ✅ Rozwiązanie

### 1. **Pobieranie z prawidłowego endpointu**

**Przed:**
```php
$image_data = develogic()->api_client->download_projection_image($projection_id);
```

**Po:**
```php
// Używamy URI z projection data (zgodnie z dokumentacją API)
$projection_url = $projection['uri']; // np. https://domelcki.ondevelogic.com/api/fis/v1/feed/projection/180
$response = wp_remote_get($projection_url, [
    'headers' => ['ApiKey' => $api_key],
    'sslverify' => false
]);
$image_data = wp_remote_retrieve_body($response);
```

Zgodnie z CURL:
```bash
curl -L -O -J \
  -H "ApiKey: tRx6d7vh5othPXdtfxu9" \
  "https://domelcki.ondevelogic.com/api/fis/v1/feed/projection/180"
```

---

### 2. **Detekcja formatu pliku (PDF magic bytes)**

```php
// Check if the file is PDF (magic bytes: %PDF)
$is_pdf = (substr($image_data, 0, 4) === '%PDF');
```

---

### 3. **Konwersja PDF → JPEG**

Nowa metoda `convert_pdf_to_jpeg()` z dwoma metodami konwersji:

#### **Metoda 1: Imagick** (preferowana, lepsza jakość)
```php
if (extension_loaded('imagick')) {
    $imagick = new Imagick();
    $imagick->setResolution(150, 150); // DPI
    $imagick->readImage($pdf_file . '[0]'); // Pierwsza strona
    $imagick->setImageFormat('jpeg');
    $imagick->setImageCompressionQuality(85);
    $imagick->writeImage($jpeg_file);
    $imagick->clear();
    $imagick->destroy();
}
```

#### **Metoda 2: Ghostscript** (fallback)
```php
if (function_exists('exec')) {
    exec('gs -dSAFER -dBATCH -dNOPAUSE -sDEVICE=jpeg -dJPEGQ=85 -r150 -dFirstPage=1 -dLastPage=1 -sOutputFile=' . escapeshellarg($jpeg_file) . ' ' . escapeshellarg($pdf_file));
}
```

**Parametry konwersji:**
- **Rozdzielczość**: 150 DPI (optymalny balans jakość/rozmiar)
- **Jakość JPEG**: 85% (wysoka jakość, niezbyt duże pliki)
- **Strona PDF**: Pierwsza strona (0)

---

### 4. **Sortowanie projekcji według typu**

Projekcje są teraz **automatycznie sortowane** podczas synchronizacji:

```php
usort($projections, function($a, $b) {
    $order = array(
        'Karta lokalu' => 1,          // Najważniejsze - główne zdjęcie
        'Aranżacyjny' => 2,           // Drugie - aranżacja
        'Położenie na kondygnacji' => 3,  // Trzecie - plan kondygnacji
    );
    // Pozostałe typy na końcu (999)
});
```

**Kolejność wyświetlania na liście:**
1. **Image 1** (lewy): **Karta lokalu**
2. **Image 2** (prawy): **Aranżacyjny**

**Kolejność w galerii modala:**
1. **Karta lokalu**
2. **Aranżacyjny**
3. **Położenie na kondygnacji**
4. Pozostałe (jeśli są)

---

## 📝 Zmiany w plikach

### `includes/class-sync.php`

#### Nowa metoda: `convert_pdf_to_jpeg()`
```php
/**
 * Convert PDF to JPEG
 *
 * @param string $pdf_file Path to PDF file
 * @param string $output_dir Output directory
 * @param string $local_number Local number for naming
 * @param int $projection_id Projection ID
 * @param string $projection_type Projection type
 * @return string|false Path to JPEG file or false on failure
 */
private function convert_pdf_to_jpeg($pdf_file, $output_dir, $local_number, $projection_id, $projection_type)
```

#### Zmodyfikowana metoda: `process_projections()`
- ✅ Sortowanie projekcji według typu na początku
- ✅ Pobieranie z `uri` zamiast pomocniczej metody
- ✅ Detekcja PDF przez magic bytes
- ✅ Automatyczna konwersja PDF → JPEG
- ✅ Logowanie sukcesów i błędów konwersji

### `templates/apartments-list.php`

**Uproszczenie logiki wyboru zdjęć:**

```php
// Projections are already sorted: Karta lokalu, Aranżacyjny, Położenie na kondygnacji
$image1 = !empty($projections[0]) ? $projections[0] : null; // Karta lokalu
$image2 = !empty($projections[1]) ? $projections[1] : null; // Aranżacyjny
```

Usunięto skomplikowane pętle szukające `displayUrl` i `plan` - teraz po prostu bierzemy pierwsze dwa obrazki z posortowanej tablicy.

---

## 🔧 Wymagania serwera

### Opcja 1: **Imagick** (zalecana)
```bash
# Sprawdź czy zainstalowane
php -m | grep imagick

# Instalacja na Ubuntu/Debian
sudo apt-get install php-imagick
sudo systemctl restart php-fpm

# Instalacja na CentOS/RHEL
sudo yum install php-imagick
sudo systemctl restart php-fpm
```

### Opcja 2: **Ghostscript** (fallback)
```bash
# Sprawdź czy zainstalowane
gs --version

# Instalacja na Ubuntu/Debian
sudo apt-get install ghostscript

# Instalacja na CentOS/RHEL
sudo yum install ghostscript
```

### Sprawdzenie w WordPressie

Po uruchomieniu synchronizacji sprawdź logi w:
- **Develogic** → **Synchronizacja** → **Log synchronizacji**

Logi pokażą:
- ✅ `Skonwertowano PDF na JPEG (Imagick): projekcja 180`
- ✅ `Skonwertowano PDF na JPEG (Ghostscript): projekcja 180`
- ⚠️ `Brak dostępnej metody konwersji PDF→JPEG`

---

## 🧪 Testowanie

### 1. **Ręczne pobranie projekcji**

```bash
# Pobierz projekcję z API
curl -L -O -J \
  -H "ApiKey: TWÓJ_API_KEY" \
  "https://domelcki.ondevelogic.com/api/fis/v1/feed/projection/180"

# Sprawdź typ pliku
file projection-180

# Powinno pokazać: "PDF document"
```

### 2. **Test konwersji Imagick**

```php
<?php
$imagick = new Imagick();
$imagick->setResolution(150, 150);
$imagick->readImage('test.pdf[0]');
$imagick->setImageFormat('jpeg');
$imagick->setImageCompressionQuality(85);
$imagick->writeImage('test.jpg');
echo "Konwersja OK!";
?>
```

### 3. **Test konwersji Ghostscript**

```bash
gs -dSAFER -dBATCH -dNOPAUSE -sDEVICE=jpeg -dJPEGQ=85 -r150 \
   -dFirstPage=1 -dLastPage=1 -sOutputFile=output.jpg input.pdf
```

### 4. **Synchronizacja w pluginie**

1. **Develogic** → **Synchronizacja**
2. Kliknij **"Synchronizuj teraz"**
3. Sprawdź logi - powinny pokazać:
   - Liczbę pobranych projekcji
   - Komunikaty o konwersji PDF → JPEG
4. Przejdź do **Media** → Zobacz nowe obrazki JPEG

---

## 📊 Statystyki

### Przed (PDF):
- Rozmiar pliku: ~98 KB (PDF)
- Format: PDF (nie wyświetla się w `<img>`)
- Miniatury: ❌ Nie generowane

### Po (JPEG):
- Rozmiar pliku: ~120-150 KB (JPEG 150 DPI, 85% jakość)
- Format: JPEG (wyświetla się wszędzie)
- Miniatury: ✅ Automatycznie generowane przez WordPress (thumbnail, medium, large)

---

## 🐛 Troubleshooting

### Problem: "Brak dostępnej metody konwersji PDF→JPEG"

**Przyczyna**: Brak Imagick i Ghostscript

**Rozwiązanie**:
```bash
# Zainstaluj Imagick
sudo apt-get install php-imagick
sudo systemctl restart php-fpm

# Lub zainstaluj Ghostscript
sudo apt-get install ghostscript
```

---

### Problem: "Imagick conversion failed"

**Przyczyna**: Brak uprawnień lub błędna konfiguracja ImageMagick

**Rozwiązanie**:
```bash
# Sprawdź policy.xml
sudo nano /etc/ImageMagick-6/policy.xml

# Znajdź i zmień lub usuń linię:
<policy domain="coder" rights="none" pattern="PDF" />

# Na:
<policy domain="coder" rights="read|write" pattern="PDF" />

# Restart
sudo systemctl restart php-fpm
```

---

### Problem: "Ghostscript conversion failed"

**Przyczyna**: Ghostscript niedostępny lub błędna ścieżka

**Rozwiązanie**:
```bash
# Sprawdź czy gs jest dostępny
which gs

# Sprawdź czy exec() jest dozwolony
php -r "echo function_exists('exec') ? 'OK' : 'DISABLED';"

# Jeśli disabled, usuń 'exec' z disable_functions w php.ini
```

---

## 📈 Kolejne ulepszenia (opcjonalnie)

### 1. **Zwiększenie rozdzielczości dla dużych ekranów**
```php
$imagick->setResolution(300, 300); // 300 DPI (2x większe pliki)
```

### 2. **WebP zamiast JPEG** (nowoczesny format, mniejsze pliki)
```php
$imagick->setImageFormat('webp');
$imagick->setImageCompressionQuality(85);
```

### 3. **Asynchroniczna konwersja** (dla dużej ilości projekcji)
```php
// Konwertuj w tle przez WP Cron
wp_schedule_single_event(time(), 'develogic_convert_pdf', [$projection_id]);
```

---

## ✅ Podsumowanie

Plugin teraz **automatycznie**:
1. ✅ Pobiera projekcje z prawidłowego endpointu (URI z API)
2. ✅ Wykrywa format pliku (PDF vs obraz)
3. ✅ Konwertuje PDF → JPEG (Imagick lub Ghostscript)
4. ✅ Sortuje projekcje według typu (Karta lokalu, Aranżacyjny, Położenie)
5. ✅ Generuje miniatury WordPress (thumbnail, medium, large)
6. ✅ Wyświetla obrazki w odpowiedniej kolejności na liście i w galerii

**Użytkownik widzi**:
- 🖼️ Prawidłowe obrazki zamiast pustych miejsc
- 🎨 "Karta lokalu" jako główne zdjęcie
- 📐 "Aranżacyjny" jako drugie zdjęcie
- 📍 "Położenie na kondygnacji" w galerii modala

---

## 🔗 Dokumentacja API

**Develogic Feed API v1.5**
- Endpoint projekcji: `GET /api/fis/v1/feed/projection/{ID}`
- Header: `ApiKey: {klucz}`
- Response: Plik binarny (PDF lub obraz)

**CURL przykład:**
```bash
curl -L -O -J \
  -H "ApiKey: tRx6d7vh5othPXdtfxu9" \
  "https://domelcki.ondevelogic.com/api/fis/v1/feed/projection/180"
```

