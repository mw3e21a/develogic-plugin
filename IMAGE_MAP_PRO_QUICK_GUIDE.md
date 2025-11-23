# 🚀 Image Map Pro - Szybki Przewodnik: Scroll do Mieszkania

## TL;DR - Szybka Konfiguracja

### Co musisz zrobić (3 kroki):

1. **W polu "Title"** polygonu wpisz numer mieszkania (np. `M18`)
2. **Dodaj akcję "Run Script"** z kodem:
   ```javascript
   develogicScrollToApartment('{{title}}');
   ```
3. **Zapisz projekt**

**Gotowe!** 🎉

---

## Krok po kroku (z obrazkami)

### 1️⃣ Otwórz Image Map Pro Editor

```
WordPress Admin → Image Map Pro → Editor → [Wybierz projekt]
```

### 2️⃣ Kliknij na polygon mieszkania

Po kliknięciu na polygon, w prawym panelu pojawi się konfiguracja.

### 3️⃣ Ustaw "Title"

```
┌─────────────────────────────────┐
│ Shape Settings                  │
├─────────────────────────────────┤
│ Title: [M18____________]        │  ← Wpisz numer mieszkania
│                                 │
│ Tooltip: [ ] Enable             │
└─────────────────────────────────┘
```

**WAŻNE:** Numer musi być identyczny jak w Develogic!

### 4️⃣ Dodaj akcję

Przewiń w dół do sekcji "Actions":

```
┌─────────────────────────────────┐
│ Actions                         │
├─────────────────────────────────┤
│ [+ Add Action]                  │  ← Kliknij tutaj
└─────────────────────────────────┘
```

### 5️⃣ Wybierz "Run Script"

```
┌─────────────────────────────────┐
│ Action Type:                    │
│ [Run Script ▼]                  │  ← Wybierz z listy
└─────────────────────────────────┘
```

### 6️⃣ Wklej kod

```
┌─────────────────────────────────┐
│ JavaScript Code:                │
│ ┌─────────────────────────────┐ │
│ │ develogicScrollToApartment( │ │
│ │   '{{title}}'               │ │  ← Wklej ten kod
│ │ );                          │ │
│ └─────────────────────────────┘ │
└─────────────────────────────────┘
```

### 7️⃣ Zapisz

```
┌──────────────────────────────────────┐
│  [< Back]              [Save ✓]      │  ← Kliknij Save
└──────────────────────────────────────┘
```

---

## Przykłady konfiguracji

### Przykład 1: Prosty scroll (zalecane)

**Title:** `M18`

**Run Script:**
```javascript
develogicScrollToApartment('{{title}}');
```

**Efekt:** Kliknięcie → scroll → podświetlenie mieszkania M18

---

### Przykład 2: Scroll z czyszczeniem filtrów

Jeśli mieszkanie może być ukryte przez filtry:

**Title:** `M18`

**Run Script:**
```javascript
// Wyczyść filtry
develogicUpdateFilters({
    pokoje: 'all',
    pietro: 'all',
    budynek: 'all'
});

// Poczekaj i scroll
setTimeout(function() {
    develogicScrollToApartment('{{title}}');
}, 300);
```

**Efekt:** Kliknięcie → wyczyść filtry → scroll → podświetlenie

---

### Przykład 3: Scroll tylko dla konkretnego budynku

**Title:** `M18`

**Run Script:**
```javascript
// Ustaw filtr na budynek H
develogicUpdateFilters({ budynek: 'H' });

// Poczekaj i scroll
setTimeout(function() {
    develogicScrollToApartment('{{title}}');
}, 300);
```

**Efekt:** Kliknięcie → pokaż tylko budynek H → scroll → podświetlenie

---

## Bulk Setup - Szybka konfiguracja wielu mieszkań

### Metoda "Copy-Paste"

1. **Skonfiguruj pierwsze mieszkanie** w pełni (Title + Run Script)

2. **Kliknij "Duplicate Shape"** w Image Map Pro

3. **Przesuń duplikat** na następne mieszkanie

4. **Zmień tylko "Title"** na nowy numer

5. **Powtórz** dla wszystkich mieszkań

**Czas:** ~30 sekund na mieszkanie

---

## Testowanie

### Quick Test - Czy działa?

1. Otwórz stronę z mapą i listą mieszkań
2. Kliknij na polygon
3. Sprawdź czy:
   - ✅ Strona zjeżdża w dół
   - ✅ Mieszkanie świeci na żółto
   - ✅ Po 3s podświetlenie znika

### Jeśli nie działa:

**Problem:** Nic się nie dzieje
- **Sprawdź:** Otwórz konsolę (F12) → szukaj błędów
- **Rozwiązanie:** Upewnij się, że shortcode `[develogic_apartments_list]` jest na stronie

**Problem:** "Apartment not found"
- **Sprawdź:** Czy numer w "Title" jest identyczny jak w liście mieszkań
- **Rozwiązanie:** Popraw numer w polu "Title"

**Problem:** "Apartment is filtered out"
- **Sprawdź:** Czy mieszkanie nie jest ukryte przez filtry
- **Rozwiązanie:** Użyj Przykładu 2 (czyszczenie filtrów)

---

## Cheatsheet - Kody do kopiowania

### Podstawowy scroll
```javascript
develogicScrollToApartment('{{title}}');
```

### Scroll + czyszczenie filtrów
```javascript
develogicUpdateFilters({ pokoje: 'all', pietro: 'all', budynek: 'all' });
setTimeout(function() { develogicScrollToApartment('{{title}}'); }, 300);
```

### Scroll + ustawienie budynku
```javascript
develogicUpdateFilters({ budynek: 'H' });
setTimeout(function() { develogicScrollToApartment('{{title}}'); }, 300);
```

### Scroll + ustawienie piętra
```javascript
develogicUpdateFilters({ pietro: '2' });
setTimeout(function() { develogicScrollToApartment('{{title}}'); }, 300);
```

---

## FAQ

**Q: Czy muszę to konfigurować dla każdego mieszkania?**  
A: Tak, ale możesz użyć "Duplicate Shape" - zajmie to tylko 30 sekund na mieszkanie.

**Q: Co jeśli zmienię numer mieszkania w Develogic?**  
A: Musisz też zmienić pole "Title" w Image Map Pro.

**Q: Czy mogę dodać własną animację?**  
A: Tak! Edytuj plik `apartments-list.css` i zmień `.apartment-highlight`.

**Q: Czy działa na mobile?**  
A: Tak! Scroll działa na wszystkich urządzeniach.

**Q: Czy mogę zmienić czas podświetlenia?**  
A: Tak, edytuj `apartments-list.js` i zmień `3000` (3 sekundy) na inną wartość.

---

## Alternatywne rozwiązanie - URL redirect

Jeśli nie chcesz używać JavaScript, możesz przekierować na URL:

**Action Type:** `Open Link`

**URL:**
```
?mieszkanie={{title}}#apartments-list
```

**Efekt:** Podobny, ale z przeładowaniem strony.

---

**Gotowe!** Jeśli coś nie działa, sprawdź pełną dokumentację: [IMAGE_MAP_PRO_SCROLL_TO_APARTMENT.md](IMAGE_MAP_PRO_SCROLL_TO_APARTMENT.md)

