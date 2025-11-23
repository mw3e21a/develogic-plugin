# Changelog: Loading Spinner

## Opis
Dodano spinner ładowania (loading spinner), który wyświetla się w obszarze kontenera z tabelką mieszkań podczas początkowego ładowania. Rozwiązuje to problem, gdzie tabelka była "rozjechana" przez chwilę przed załadowaniem się stylów CSS.

## Data
23 listopada 2025

## Zmiany

### 1. Template (`templates/apartments-list.php`)
- Dodano wrapper `.develogic-apartments-container` okalający całą zawartość
- Dodano overlay z spinnerem ładowania wewnątrz wrappera
- Spinner zawiera animowany element z czterema obracającymi się pierścieniami (mniejsze od wcześniejszej wersji)
- Wyświetla tekst "Ładowanie mieszkań..."

### 2. Style CSS (`assets/css/apartments-list.css`)
- Dodano `.develogic-apartments-container` jako pozycjonowany kontener (position: relative, min-height: 400px)
- Dodano style dla `.develogic-loading-overlay` - overlay z białym tłem (95% opacity) pozycjonowany absolutnie w kontenerze
- Dodano `.develogic-spinner` z animowanymi pierścieniami (60x60px)
- Animacja `develogic-spin` - płynne obracanie pierścieni
- Każdy pierścień ma inny rozmiar i opóźnienie animacji dla efektu "fali"
- Dodano `.develogic-loading-text` dla tekstu ładowania (14px)
- Kontener wewnętrzny (`.container`) początkowo ukryty (opacity: 0)
- Klasa `.loaded` płynnie pokazuje kontener po załadowaniu
- Klasa `.hidden` ukrywa spinner z animacją fade-out

### 3. JavaScript (`assets/js/apartments-list.js`)
- Dodano funkcję `hideLoadingSpinner()`
- Nasłuchuje na event `window.load` - gdy wszystkie zasoby (obrazy, style) są załadowane
- Po załadowaniu:
  - Ukrywa spinner z animacją fade-out
  - Usuwa spinner z DOM po 300ms (czas trwania animacji)
  - Pokazuje kontener z mieszkaniami z animacją fade-in
- Zabezpieczenie fallback: ukrywa spinner po 2 sekundach nawet jeśli nie wszystkie obrazy się załadowały
- Dodano sprawdzanie `parentNode` przed usunięciem elementu dla bezpieczeństwa

## Jak to działa

1. **Start**: Gdy strona się ładuje, użytkownik widzi spinner z animacją w obszarze gdzie pojawi się tabelka
2. **Podczas ładowania**: Kontener z mieszkaniami jest ukryty (opacity: 0), więc nie widać "rozjechanej" tabelki
3. **Po załadowaniu**: 
   - Event `window.load` wykrywa, że wszystkie style i obrazy są gotowe
   - Spinner płynnie znika (fade-out 0.3s)
   - Kontener z mieszkaniami płynnie się pokazuje (fade-in 0.3s)
4. **Fallback**: Po 2 sekundach spinner zniknie automatycznie, nawet jeśli niektóre obrazy jeszcze się ładują

## Korzyści

✅ Użytkownik nie widzi "rozjechanej" tabelki podczas ładowania  
✅ Profesjonalny wygląd - płynne przejścia  
✅ Feedback wizualny - użytkownik wie, że treść się ładuje  
✅ Nie blokuje całej strony - spinner tylko w obszarze tabelki  
✅ Zabezpieczenie - fallback po 2 sekundach zapobiega "zawieszeniu" spinnera  
✅ Lekka implementacja - tylko CSS i vanilla JavaScript  
✅ Responsywny - dostosowuje się do rozmiaru kontenera  

## Dostosowanie

Jeśli chcesz zmienić wygląd spinnera, możesz edytować:
- Kolor pierścieni: `border-top-color` w `.develogic-spinner-ring`
- Rozmiar: zmień `width` i `height` w `.develogic-spinner` (obecnie 60x60px)
- Szybkość animacji: zmień `1.2s` w `animation` property
- Tekst: zmień "Ładowanie mieszkań..." w template
- Kolor tła overlay: zmień `rgba(255, 255, 255, 0.95)` w `.develogic-loading-overlay`
- Minimalną wysokość: zmień `min-height: 400px` w `.develogic-apartments-container`
- Czas fallback: zmień `2000` (2 sekundy) w JavaScript

## Testowanie

Przetestuj na:
1. Szybkim połączeniu internetowym - spinner powinien zniknąć szybko
2. Wolnym połączeniu - spinner powinien być widoczny dłużej
3. Cache wyłączony - sprawdź, czy nie ma "migania" tabelki
4. Różne przeglądarki (Chrome, Firefox, Safari, Edge)
5. Urządzenia mobilne

