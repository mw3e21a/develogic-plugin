# Changelog: Bordowy kolor dla statusu "Sprzedany"

## Data: 2025-11-14

### Zmiana
Dodano bordowy kolor dla statusu "Sprzedany" (i "Sprzedane") w liście mieszkań i w modalu szczegółów.

### Problem
Status "Sprzedany" wyświetlał się domyślnym zielonym kolorem (jak "Dostępne"), co mogło wprowadzać w błąd użytkowników.

### Rozwiązanie
Dodano dedykowany styl CSS dla statusu "sold" z bordowym kolorem (#8b0000 - dark red/bordowy).

### Zmiany w plikach

#### 1. `assets/css/apartments-list.css`
Dodano nową klasę CSS dla statusu sprzedanego:

```css
.status-badge.sold {
    color: #8b0000;
}
```

#### 2. `templates/apartments-list.php`
Zaktualizowano logikę przypisywania klas CSS do badge'a statusu:

**Przed:**
```php
<div class="status-badge <?php echo $status_class === 'reserved' ? 'reserved' : ''; ?>">
```

**Po:**
```php
<div class="status-badge <?php 
    if ($status_class === 'reserved') {
        echo 'reserved';
    } elseif ($status_class === 'sold') {
        echo 'sold';
    }
?>">
```

#### 3. `assets/js/apartments-list.js`
Dodano obsługę statusu "sold" w modalu szczegółów mieszkania:

```javascript
// Set status
const statusEl = modal.querySelector('.status');
if (data.statusClass === 'available') {
    statusEl.innerHTML = '<span style="color: #00b341;">Dostępne</span> od ręki';
} else if (data.statusClass === 'reserved') {
    statusEl.innerHTML = '<span style="color: #ff9500;">Rezerwacja</span>';
} else if (data.statusClass === 'sold') {
    statusEl.innerHTML = '<span style="color: #8b0000;">Sprzedany</span>';
} else {
    statusEl.textContent = data.status || '';
}
```

### Kolory statusów

Po wprowadzeniu zmian:
- **Dostępny/Wolny**: 🟢 Zielony (#00b341)
- **Rezerwacja**: 🟠 Pomarańczowy (#ff9500)
- **Sprzedany**: 🔴 Bordowy (#8b0000)

### Testowanie

1. Znajdź mieszkanie ze statusem "Sprzedany" lub "Sprzedane"
2. Sprawdź, czy na liście mieszkań status wyświetla się bordowym kolorem
3. Kliknij na mieszkanie, aby otworzyć modal
4. Sprawdź, czy w modalu status również jest bordowy

### Notatki techniczne

- Klasa `Develogic_Data_Formatter::get_status_class()` już obsługiwała mapowanie statusów "Sprzedany" i "Sprzedane" na klasę 'sold'
- Zmiana jest w pełni wstecznie kompatybilna
- Kolor #8b0000 to standardowy dark red (bordowy), który jest wyraźnie widoczny i czytelny

