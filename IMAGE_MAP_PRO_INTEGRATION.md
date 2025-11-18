# Integracja z Image Map Pro - Przewodnik Użytkownika

## Krótki opis

Automatyczna synchronizacja kolorów kształtów w Image Map Pro z statusami lokali w Develogic.

## Szybki start

### 1. Przygotuj Image Map Pro

W edytorze Image Map Pro:

1. Utwórz projekt dla każdego budynku/piętra (np. "Pietro 3 Budynek H")
2. Dodaj kształty (polygony) reprezentujące lokale
3. **WAŻNE:** W każdym kształcie ustaw pole **"Title"** na numer lokalu
   - Przykład: dla lokalu "44" ustaw title: `44`

### 2. Skonfiguruj kolory

W WordPress admin → **Develogic → Image Map Pro**:

1. **Sekcja "Kolory statusów"**
   - Ustaw kolor dla każdego statusu (Wolny, Sprzedany, Rezerwacja, Niedostępny)
   - Kliknij "Zapisz kolory"

### 3. Zmapuj budynki na projekty

W tej samej stronie, **sekcja "Mapowanie budynków"**:

1. Dla każdego budynku wybierz odpowiedni shortcode Image Map Pro
2. Przykład:
   ```
   Budynek: H (ID: 123) → Shortcode: Pietro_3H
   ```
3. Kliknij "Zapisz mapowania"

### 4. Testuj

1. Kliknij **"Aktualizuj kolory teraz"**
2. Sprawdź komunikat o powodzeniu
3. Otwórz stronę z mapą Image Map Pro i zobacz efekt!

## Automatyczna synchronizacja

Od teraz, po każdej synchronizacji lokali z Develogic (co 5 minut lub ręcznie), kolory w Image Map Pro będą automatycznie aktualizowane! 🎨

## Najczęstsze pytania

### Czy mogę dostosować kolory?

Tak! W panelu **Develogic → Image Map Pro** możesz ustawić dowolny kolor dla każdego statusu.

### Co jeśli mam wiele budynków?

Zmapuj każdy budynek osobno. System automatycznie rozpozna, które kształty należą do którego budynku.

### Czy muszę coś robić ręcznie po każdej zmianie?

Nie! Po konfiguracji wszystko działa automatycznie. Zmiany statusów w Develogic automatycznie aktualizują kolory w Image Map Pro.

### Jak sprawdzić czy działa?

1. Zmień status lokalu w Develogic
2. Poczekaj 5 minut (automatyczna synchronizacja) lub uruchom ręcznie w **Develogic → Synchronizacja**
3. Sprawdź mapę Image Map Pro - kolor powinien się zmienić

### Gdzie znajdę logi?

Wszystkie operacje są logowane w **Develogic → Synchronizacja** (zakładka "Logi").

## Przykładowa konfiguracja

```
╔══════════════════════════════════════════════════════════╗
║ DEVELOGIC                    IMAGE MAP PRO               ║
╠══════════════════════════════════════════════════════════╣
║ Budynek H                    Projekt: Pietro_3H          ║
║ ├─ Lokal 44 (Sprzedany) →   Shape: title="44" 🔴        ║
║ ├─ Lokal 43 (Rezerwacja) →  Shape: title="43" 🟠        ║
║ └─ Lokal 42 (Wolny) →        Shape: title="42" 🟢        ║
╚══════════════════════════════════════════════════════════╝
```

## Wsparcie

Jeśli coś nie działa:
1. Sprawdź logi w **Develogic → Synchronizacja**
2. Upewnij się, że numery w "title" dokładnie odpowiadają numerom lokali
3. Sprawdź mapowanie budynków
4. Zobacz pełną dokumentację: `CHANGELOG_IMAGE_MAP_PRO_INTEGRATION.md`

---

**Status:** ✅ Gotowe do użycia  
**Wymagania:** WordPress 5.0+, PHP 7.4+, Image Map Pro v6+

