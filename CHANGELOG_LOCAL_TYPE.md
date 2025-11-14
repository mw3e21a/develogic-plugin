# Changelog: Filtrowanie po typie lokalu

## Data: 2025-11-14

### Dodane funkcje
- Dodano filtrowanie po typie lokalu (localType)
- Domyślnie ustawiony filtr "Lokal mieszkalny" jeśli dostępny w danych
- Filtr umieszczony przed filtrem "Budynek" w interfejsie

### Zmiany w plikach

#### 1. `templates/apartments-list.php`
- Dodano nowy select `#localTypeFilter` z opcjami typów lokali
- Dodano dynamiczne generowanie opcji z danych (Lokal mieszkalny, Garaż, Komórka lokatorska, Miejsce postojowe)
- Dodano atrybut `data-local-type` do każdego elementu `.apartment-item`
- Domyślnie wybrana opcja "Lokal mieszkalny" jeśli dostępna

#### 2. `assets/js/apartments-list.js`
- Dodano obsługę filtra `localTypeFilter` w funkcji `setupFiltering()`
- Dodano filtrowanie po `data-local-type` w funkcji `applyFilters()`
- Dodano reset filtra do wartości "Lokal mieszkalny" w funkcji `resetFilters()`
- Dodano automatyczne zastosowanie filtrów przy inicjalizacji strony w funkcji `init()`

### Jak to działa

1. **Ładowanie strony**: 
   - Select automatycznie ustawia się na "Lokal mieszkalny" jeśli taka opcja istnieje
   - Filtry są automatycznie aplikowane, pokazując tylko lokale mieszkalne

2. **Zmiana filtra**: 
   - Użytkownik może wybrać inny typ lokalu z listy rozwijanej
   - Lista mieszkań aktualizuje się natychmiast

3. **Reset filtrów**: 
   - Przycisk "Resetuj filtry" przywraca domyślną wartość "Lokal mieszkalny"
   - Wszystkie inne filtry są resetowane do wartości domyślnych

### Testowanie

1. Otwórz stronę z listą mieszkań
2. Sprawdź, czy domyślnie wyświetlane są tylko lokale mieszkalne
3. Zmień typ lokalu na "Garaż" - lista powinna się zaktualizować
4. Kliknij "Resetuj filtry" - filtr powinien wrócić do "Lokal mieszkalny"

### Kompatybilność

- Zmiana jest wstecznie kompatybilna
- Jeśli pole `localType` nie istnieje w danych, filtr będzie działał poprawnie (puste wartości)
- Jeśli w danych nie ma opcji "Lokal mieszkalny", domyślnie wybrana będzie opcja "Wszystkie typy"

