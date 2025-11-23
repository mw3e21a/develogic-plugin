# 🎯 Image Map Pro - Scroll do mieszkania po kliknięciu

## Czego to dotyczy?

Po kliknięciu w polygon (mieszkanie na rzucie) w Image Map Pro, strona automatycznie:
1. Zjedzie w dół do tabeli z listą mieszkań
2. Podświetli wybrane mieszkanie (żółte tło + animacja pulsowania)
3. Mieszkanie będzie widoczne przez 3 sekundy z podświetleniem

## Jak to skonfigurować?

### Krok 1: Otwórz edytor Image Map Pro

W WordPress admin:
```
Image Map Pro → Editor → [Wybierz swój projekt]
```

### Krok 2: Edytuj polygon (kształt mieszkania)

1. Kliknij na polygon mieszkania, które chcesz skonfigurować
2. W prawym panelu pojawi się konfiguracja tego shape'a

### Krok 3: Ustaw tytuł mieszkania

W polu **"Title"** wpisz dokładny **numer mieszkania** z Develogic.

**Przykłady:**
- `44` (jeśli w Develogic numer to "44")
- `M18` (jeśli w Develogic numer to "M18")
- `A2-15` (jeśli w Develogic numer to "A2-15")

**WAŻNE:** Numer musi być dokładnie taki sam jak w polu `number` z API Develogic!

### Krok 4: Dodaj akcję "Run Script"

1. Przewiń w prawym panelu do sekcji **"Actions"**
2. Kliknij **"+ Add Action"**
3. Z dropdown'a wybierz: **"Run Script"**

### Krok 5: Wklej kod JavaScript

W polu **"JavaScript Code"** wklej następujący kod:

```javascript
develogicScrollToApartment('{{title}}');
```

**Wyjaśnienie:**
- `develogicScrollToApartment()` - funkcja dostępna globalnie z wtyczki Develogic
- `{{title}}` - placeholder Image Map Pro, który zostanie zastąpiony wartością z pola "Title" (numer mieszkania)

### Krok 6: Opcjonalnie - dodaj tooltip

Możesz dodać tooltip, który pojawi się po najechaniu na polygon:

1. W sekcji **"Tooltip"** włącz opcję
2. W polu **"Tooltip Content"** wpisz:
```
Mieszkanie {{title}} - kliknij aby zobaczyć szczegóły
```

### Krok 7: Zapisz zmiany

Kliknij **Save** w prawym górnym rogu.

### Krok 8: Powtórz dla wszystkich mieszkań

Powtórz kroki 2-6 dla każdego polygonu (mieszkania) na mapie.

---

## Przykład konfiguracji

### Dla mieszkania "M18"

**Title:** `M18`

**Action - Run Script:**
```javascript
develogicScrollToApartment('{{title}}');
```

Po kliknięciu na ten polygon, strona:
1. Zjedzie do tabeli
2. Znajdzie mieszkanie z numerem "M18"
3. Podświetli je na żółto przez 3 sekundy

---

## Zaawansowane opcje

### Opcja 1: Dodaj opóźnienie przed scroll

Jeśli chcesz dodać opóźnienie (np. 500ms):

```javascript
setTimeout(function() {
    develogicScrollToApartment('{{title}}');
}, 500);
```

### Opcja 2: Scroll + otwarcie modala

Jeśli chcesz najpierw zjechać do mieszkania, a potem otworzyć modal:

```javascript
develogicScrollToApartment('{{title}}');
setTimeout(function() {
    // Tutaj można dodać kod do otwarcia modala
    // (wymaga rozszerzenia apartments-list.js)
}, 1000);
```

### Opcja 3: Ustaw filtry przed scroll

Jeśli chcesz najpierw wyczyścić filtry (żeby mieszkanie było widoczne):

```javascript
// Wyczyść wszystkie filtry
develogicUpdateFilters({
    pokoje: 'all',
    pietro: 'all',
    budynek: 'all'
});

// Poczekaj chwilę i scroll
setTimeout(function() {
    develogicScrollToApartment('{{title}}');
}, 300);
```

---

## Troubleshooting

### Problem: Po kliknięciu nic się nie dzieje

**Rozwiązanie 1:** Sprawdź konsolę przeglądarki (F12)
- Jeśli widzisz błąd `develogicScrollToApartment is not defined`, wtyczka Develogic nie jest załadowana na tej stronie
- Upewnij się, że shortcode `[develogic_apartments_list]` jest na stronie

**Rozwiązanie 2:** Sprawdź czy numer mieszkania jest poprawny
- Otwórz konsolę (F12) i wpisz:
  ```javascript
  document.querySelectorAll('.apartment-number')
  ```
- Sprawdź czy któryś element ma tekst identyczny jak w polu "Title"

### Problem: Mieszkanie nie jest podświetlone

**Rozwiązanie:** Sprawdź czy CSS został załadowany
- Otwórz konsolę (F12) → zakładka "Elements"
- Znajdź `.apartment-item` dla Twojego mieszkania
- Sprawdź czy ma klasę `apartment-highlight`

### Problem: "Apartment is filtered out" w konsoli

**Rozwiązanie:** Mieszkanie jest ukryte przez filtry
- Użyj rozwiązania z Opcji 3 (wyczyść filtry przed scroll)
- Lub upewnij się, że filtry są ustawione tak, żeby mieszkanie było widoczne

### Problem: Scroll działa, ale mieszkanie nie jest wycentrowane

**Rozwiązanie:** Dostosuj offset
- Edytuj plik `apartments-list.js` i zmień:
  ```javascript
  targetApartment.scrollIntoView({ 
      behavior: 'smooth', 
      block: 'center'  // zmień na 'start' lub 'end'
  });
  ```

---

## Testowanie

### Test 1: Podstawowy scroll
1. Otwórz stronę z Image Map Pro i listą mieszkań
2. Kliknij na polygon mieszkania
3. Sprawdź czy:
   - ✅ Strona zjeżdża do tabeli
   - ✅ Mieszkanie jest podświetlone żółtym tłem
   - ✅ Po 3 sekundach podświetlenie znika

### Test 2: Mieszkanie ukryte przez filtry
1. Ustaw filtry tak, żeby mieszkanie było ukryte (np. wybierz inne piętro)
2. Kliknij na polygon tego mieszkania
3. Sprawdź w konsoli komunikat "Apartment is filtered out"
4. (Opcjonalnie) Dodaj czyszczenie filtrów jak w Opcji 3

### Test 3: Wiele mieszkań
1. Kliknij na różne polygony
2. Sprawdź czy każdy pokazuje poprawne mieszkanie

---

## Bulk Configuration (dla wielu mieszkań naraz)

Jeśli masz wiele mieszkań i nie chcesz konfigurować każdego osobno:

### Opcja A: Użyj funkcji "Duplicate Shape"
1. Skonfiguruj jeden polygon w pełni (z akcją Run Script)
2. Kliknij "Duplicate Shape"
3. Przesuń duplikat na nowe mieszkanie
4. Zmień tylko pole "Title" na nowy numer

### Opcja B: Export/Import JSON (zaawansowane)
1. Image Map Pro → Export Project → Download JSON
2. Edytuj JSON w edytorze tekstu
3. Dodaj akcje "Run Script" do wszystkich shape'ów
4. Import JSON z powrotem

---

## Dodatkowe funkcje dostępne globalnie

Wtyczka Develogic udostępnia następujące funkcje JavaScript:

### `develogicScrollToApartment(apartmentNumber)`
Zjedź do mieszkania i podświetl je.
```javascript
develogicScrollToApartment('M18');
```

### `develogicUpdateFilters(params)`
Ustaw filtry programowo.
```javascript
develogicUpdateFilters({ 
    pokoje: '3',      // 3 pokoje
    pietro: '2',      // Piętro II
    budynek: 'H'      // Budynek H
});
```

### Przykład kombinacji:
```javascript
// Ustaw filtry na budynek H, piętro 2
develogicUpdateFilters({ 
    budynek: 'H',
    pietro: '2' 
});

// Poczekaj aż filtry się zastosują (300ms)
setTimeout(function() {
    // Scroll do mieszkania M18
    develogicScrollToApartment('M18');
}, 300);
```

---

## Podsumowanie

1. ✅ W Image Map Pro, w polu **"Title"** wpisz numer mieszkania (np. `M18`)
2. ✅ Dodaj akcję **"Run Script"** z kodem: `develogicScrollToApartment('{{title}}');`
3. ✅ Zapisz i przetestuj
4. ✅ Powtórz dla wszystkich mieszkań

**To wszystko!** Kliknięcie w polygon automatycznie zjedzie do mieszkania i je podświetli.

---

**Dokumentacja stworzona:** 2025-11-23  
**Wersja wtyczki:** 2.0+  
**Wymaga:** Image Map Pro v6+

