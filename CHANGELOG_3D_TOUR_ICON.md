# Changelog: Ikona spaceru 3D

## Zmiany

### 1. Dodano link do spaceru 3D z pola `stage`
- Priorytetowo używane jest pole `stage` jako URL spaceru 3D (jeśli zawiera URL rozpoczynający się od http:// lub https://)
- Jeśli `stage` nie jest URLem, używany jest `displayUrl` z projekcji (poprzednia logika)
- Link otwiera się w nowej karcie (`target="_blank"`)

### 2. Dodano ikonę spaceru 3D (globus) na liście mieszkań
- Nowa ikona globusa/sfery 3D wyświetla się w sekcji akcji mieszkania
- Ikona jest widoczna tylko gdy istnieje URL spaceru 3D
- Styl: czarna ikona bez wypełnienia (spójny z ikonami email i ulubione)
- Po najechaniu myszką ikona zmienia kolor na niebieski (#0066cc)
- Dodano `onclick="event.stopPropagation();"` aby kliknięcie nie otwierało modalu

### 3. Dodano tooltips (podpowiedzi) dla ikon akcji
- Po najechaniu myszką na ikonę wyświetla się tooltip z opisem akcji
- Tooltips:
  - Spacer 3D: "Zobacz spacer 3D (otwiera w nowej karcie)"
  - Email: "Zapytaj o mieszkanie (otwiera klienta email)"
  - Ulubione: "Dodaj do obserwowanych"
- Tooltips mają czarne tło (#333), białe litery i strzałkę wskazującą na ikonę
- Animacja: płynne pojawianie się (fade-in) po 0.2s

### 3. Przeniesiono link spaceru 3D w modalu
- Link "Zobacz spacer 3D" w modalu szczegółów przeniesiony pod sekcję `detail-specs`
- Wyświetla się zaraz po specyfikacji mieszkania (kondygnacja, powierzchnia, pokoje)
- Dodano margines dolny dla lepszego odstępu od sekcji `detail-features`

### 4. Styl ikony 360° na liście
- Czarna ikona (#666) bez wypełnienia
- Hover effect: niebieski kolor (#0066cc) - spójny z innymi elementami
- Ikona składa się z okręgu z promieniami i tekstem "360°" w środku

## Pliki zmodyfikowane

1. **templates/apartments-list.php**
   - Linie 397-411: Zmieniona logika pobierania URL spaceru 3D (priorytet dla pola `stage`)
   - Linie 550-558: Dodana ikona 360° w sekcji akcji mieszkania na liście
   - Linie 658-669: Przeniesiony link spaceru 3D pod `detail-specs` w modalu

2. **assets/css/apartments-list.css**
   - Linie 537-556: Dodane/zmienione style dla `.icon-btn-3d` (czarna ikona bez wypełnienia)
   - Linia 1085-1086: Dodany margin-bottom dla linku spaceru 3D w modalu

## Użycie

### Na liście mieszkań:
Ikona 360° pojawi się automatycznie w sekcji akcji (obok email i ulubione), gdy:
- Pole `stage` zawiera URL rozpoczynający się od http:// lub https://, LUB
- Mieszkanie ma projekcję z wypełnionym polem `displayUrl`

### W modalu szczegółów:
Link "Zobacz spacer 3D" wyświetla się pod specyfikacją mieszkania (po kondygnacji, powierzchni, pokojach), przed sekcją z cechami mieszkania.

Kliknięcie w ikonę 360° lub link otwiera spacer 3D w nowej karcie przeglądarki.
