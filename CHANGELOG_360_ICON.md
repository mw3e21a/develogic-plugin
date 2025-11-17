# Changelog: Ikona 360° i formatowanie kondygnacji "Parter"

## Data: 2025-11-17

## Zmiany

### 1. Dodano napis "360°" dla wirtualnego spaceru
- **Prosty tekst**: Zamiast skomplikowanej ikony SVG, używamy prostego napisu "360°"
- **Zaktualizowano szablon**: W `templates/apartments-list.php` dodano `<span class="text-360">360°</span>`
- **Dodano style CSS**: Nowa klasa `.text-360` do stylowania napisu
- **Czcionka**: Bold 14px, wyśrodkowana w przycisku
- **Kolory**: 
  - Domyślnie: `#666` (szary)
  - Po najechaniu: `#0066cc` (niebieski)
- **Minimalistyczny design**: Czysty, czytelny i profesjonalny wygląd
- **Zaktualizowano aria-label i tooltip**: "Zobacz spacer 3D 360° (otwiera w nowej karcie)"

### 2. Poprawiono formatowanie kondygnacji "Parter"
- **Bug fix w PHP**: Poprawiono `Develogic_Data_Formatter::format_floor()` - dodano konwersję na string przed porównaniem
- **Problem**: Gdy `$floor` było typu `int` (0), nie pasowało do klucza `'0'` (string) w tablicy
- **Rozwiązanie**: Dodano `$floor_str = (string) $floor;` przed sprawdzeniem w `$floor_map`
- **Szablon**: W `templates/apartments-list.php` linia 307 używa `Develogic_Data_Formatter::format_floor()` do formatowania kondygnacji
- **JavaScript**: Funkcja `formatFloor()` już działała poprawnie - sprawdza zarówno `floor === 0` jak i `floor === '0'` (linia 1100)
- **Modal**: W modalu szczegółów (linia 638 w `apartments-list.js`) używane jest `data.floorDisplay || formatFloor(data.floor)`
- **Teraz działa**: Kondygnacja "0" jest prawidłowo wyświetlana jako "Parter" we wszystkich miejscach

## Pliki zmodyfikowane

### 1. `templates/apartments-list.php`
   - **Linia 551-553**: Zastąpiono starą ikonę globusa prostym napisem "360°"
   - Dodano `<span class="text-360">360°</span>`
   - Dodano klasę `icon-btn-360` do przycisku
   - Zaktualizowano aria-label i tooltip na "Zobacz spacer 3D 360°"

### 2. `assets/css/apartments-list.css`
   - **Linia 550-566**: Dodano style dla `.icon-btn-360` i `.text-360`
   - Wyśrodkowanie napisu: `display: flex; align-items: center; justify-content: center;`
   - Czcionka: `font-size: 14px; font-weight: 700;`
   - Kolor: `#666` z hover effect `#0066cc`

### 3. `includes/class-data-formatter.php`
   - **Linia 65-74**: Poprawiono funkcję `format_floor()` - dodano konwersję na string
   - Dodano `$floor_str = (string) $floor;` przed sprawdzeniem w `$floor_map`
   - **Bug fix**: Naprawiono problem z typami (int vs string) przy porównywaniu wartości 0

## Uwagi techniczne

### Napis 360°
- Prosty, minimalistyczny design - zwykły tekst zamiast grafiki SVG
- Lepiej skaluje się na różnych rozdzielczościach
- Bardziej czytelny i intuicyjny niż ikony graficzne
- Spójny styl z innymi elementami interfejsu

### Formatowanie kondygnacji
- Funkcjonalność była już wcześniej zaimplementowana poprawnie
- PHP i JavaScript używają spójnej logiki formatowania:
  - `-1` → "Piwnica"
  - `0` → "Parter"
  - `1-10` → "Piętro I", "Piętro II", ... (cyfry rzymskie)
  - `>10` → "Piętro 11", "Piętro 12", ... (cyfry arabskie)

## Status
✅ **GOTOWE** - wszystkie zmiany wdrożone i przetestowane

