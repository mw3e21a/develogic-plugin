# 🗺️ Image Map Pro - Konfiguracja Krok Po Kroku

## Czego potrzebujesz?

- ✅ WordPress 5.0+
- ✅ PHP 7.4+
- ✅ Wtyczka **Develogic Integration** (ta wtyczka)
- ✅ Wtyczka **Image Map Pro v6+** (zakupiona osobno)
- ✅ Zsynchronizowane lokale z Develogic

---

## 📚 Dodatkowa dokumentacja

- **[Scroll do mieszkania po kliknięciu](IMAGE_MAP_PRO_SCROLL_TO_APARTMENT.md)** - Jak skonfigurować akcję "Run Script" aby po kliknięciu w polygon zjechać do tabeli i podświetlić mieszkanie
- **[Szybki przewodnik](IMAGE_MAP_PRO_QUICK_GUIDE.md)** - Skrócona wersja z przykładami kodu do kopiowania

---

## 📋 Krok 1: Przygotuj projekty w Image Map Pro

### 1.1. Utwórz projekt dla każdego budynku/piętra

W Image Map Pro → Editor:

1. Kliknij **"New Project"**
2. Nadaj nazwę, np. `Pietro 3 Budynek H`
3. Ustaw shortcode, np. `Pietro_3H`
4. Dodaj obraz planu piętra

### 1.2. Dodaj kształty (polygony) dla lokali

Dla każdego lokalu na planie:

1. Wybierz narzędzie **Polygon**
2. Narysuj kształt na obrazie
3. **WAŻNE:** W polu **"Title"** wpisz **numer lokalu**
   - Przykład: jeśli lokal to `44`, wpisz dokładnie: `44`
   - Ten numer musi odpowiadać polu "number" z Develogic!

### 1.3. Ustaw początkowe kolory (opcjonalnie)

Możesz ustawić dowolne kolory - zostaną automatycznie nadpisane.

### 1.4. Zapisz projekt

Kliknij **Save** i zapamiętaj **shortcode** projektu.

---

## ⚙️ Krok 2: Konfiguruj w WordPress

### 2.1. Otwórz panel konfiguracji

W WordPress admin:
```
Develogic → Image Map Pro
```

### 2.2. Ustaw kolory statusów

W sekcji **"Kolory statusów"**:

| Status | Przykładowy kolor |
|--------|-------------------|
| Wolny | 🟢 #7ED322 (zielony) |
| Sprzedany | 🔴 #ee1c24 (czerwony) |
| Rezerwacja | 🟠 #FFA500 (pomarańczowy) |
| Niedostępny | ⚪ #cccccc (szary) |

1. Kliknij w pole koloru
2. Wybierz kolor z palety
3. Kliknij **"Zapisz kolory"**

### 2.3. Zmapuj budynki na projekty

W sekcji **"Mapowanie budynków na projekty Image Map Pro"**:

1. Znajdź swój budynek na liście (np. "H")
2. Wybierz odpowiedni shortcode Image Map Pro (np. "Pietro_3H")
3. Powtórz dla wszystkich budynków
4. Kliknij **"Zapisz mapowania"**

**Przykład mapowania:**
```
Budynek H (ID: 123) → Pietro_3H
Budynek G (ID: 124) → Pietro_3G
```

---

## 🧪 Krok 3: Przetestuj

### 3.1. Uruchom ręczną aktualizację

W sekcji **"Manualna aktualizacja"**:
1. Kliknij **"Aktualizuj kolory teraz"**
2. Poczekaj na komunikat o powodzeniu

### 3.2. Sprawdź logi

Przejdź do:
```
Develogic → Synchronizacja → Zakładka "Logi"
```

Szukaj wpisów typu:
```
[ImageMapPro] Updated project "Pietro 3 Budynek H" (shortcode: Pietro_3H) - 15 shapes updated
```

### 3.3. Zobacz efekt

1. Otwórz stronę z shortcode'em Image Map Pro
2. Sprawdź czy kolory kształtów odpowiadają statusom lokali

**Oczekiwany wynik:**
- Lokal "44" (Sprzedany) → Kształt czerwony 🔴
- Lokal "43" (Rezerwacja) → Kształt pomarańczowy 🟠
- Lokal "42" (Wolny) → Kształt zielony 🟢

---

## 🔄 Automatyczna synchronizacja

Od teraz wszystko działa automatycznie!

Po każdej synchronizacji lokali (co 5 minut lub ręcznej):
1. System pobiera najnowsze statusy z Develogic
2. Automatycznie aktualizuje kolory w Image Map Pro
3. Użytkownicy widzą aktualne dostępności na mapie

---

## ❓ Rozwiązywanie problemów

### Problem: "Kolory się nie aktualizują"

**Sprawdź:**
1. ✅ Czy Image Map Pro jest aktywne?
   - `Wtyczki → Zainstalowane wtyczki`
2. ✅ Czy numery w "title" są poprawne?
   - Otwórz projekt w Image Map Pro Editor
   - Sprawdź pole "Title" każdego kształtu
3. ✅ Czy budynek jest zmapowany?
   - `Develogic → Image Map Pro → Mapowania`
4. ✅ Czy lokale są zsynchronizowane?
   - `Develogic → Synchronizacja`

### Problem: "Nie znajduje kształtów"

**Przyczyny:**
- ❌ Numery w "title" nie pasują do numerów lokali
- ❌ Literówki w numerach (spacje, wielkie litery)
- ❌ Brak mapowania budynku

**Rozwiązanie:**
```
Develogic lokal: number = "44"
Image Map Pro shape: title = "44"  ← MUSZĄ BYĆ IDENTYCZNE
```

### Problem: "Wtyczka nie wykrywa Image Map Pro"

**Sprawdź wersję:**
- Wymagana: Image Map Pro v6.0+
- Nie działa z: starszymi wersjami

**Aktywacja:**
1. `Wtyczki → Zainstalowane wtyczki`
2. Znajdź "Image Map Pro"
3. Kliknij "Aktywuj"

---

## 📊 Status integracji

W panelu `Develogic → Image Map Pro` znajdziesz sekcję **"Informacje techniczne"**:

```
✅ Status Image Map Pro: Aktywna
✅ Liczba projektów: 5
✅ Liczba budynków: 3
✅ Liczba mapowań: 3
```

Wszystkie ✅ = Gotowe do działania! 🎉

---

## 🎯 Dobre praktyki

### 1. Nazewnictwo

**Konsekwentne shortcode'y:**
```
Pietro_1A, Pietro_2A, Pietro_3A
Pietro_1B, Pietro_2B, Pietro_3B
```

**Jasne nazwy projektów:**
```
"Piętro 1 Budynek A"
"Piętro 2 Budynek A"
```

### 2. Kolory

**Użyj kontrastu:**
- Wolny → Jasny, zachęcający (zielony, niebieski)
- Sprzedany → Wyraźny (czerwony)
- Rezerwacja → Uwaga (pomarańczowy, żółty)

**Testy dostępności:**
- Sprawdź czytelność na urządzeniach mobilnych
- Przetestuj z różnymi ustawieniami kontrastu

### 3. Organizacja

**Jeden projekt = Jedno piętro/budynek**
- Łatwiejsze zarządzanie
- Szybsze ładowanie
- Lepsze mapowanie

---

## 📞 Potrzebujesz pomocy?

1. 📖 Przeczytaj: `IMAGE_MAP_PRO_INTEGRATION.md`
2. 📖 Zobacz przykłady: `examples/image-map-pro-config-example.php`
3. 📖 Dokumentacja techniczna: `CHANGELOG_IMAGE_MAP_PRO_INTEGRATION.md`

---

**Powodzenia! 🚀**

