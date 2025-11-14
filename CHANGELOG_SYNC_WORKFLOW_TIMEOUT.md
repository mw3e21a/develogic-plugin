# Changelog: Timeout dla GitHub Actions Sync Workflow

## Data: 2025-11-14

### Zmiana
Dodano timeout dla curl w GitHub Actions workflow, aby nie czekać na pełną odpowiedź z serwera.

### Problem
Workflow czekał na pełną odpowiedź z endpointu `/wp-json/develogic/v1/sync`, co mogło powodować długie wykonywanie się zadania cron, jeśli synchronizacja trwa długo.

### Rozwiązanie
Dodano timeouty do curl:
- `--max-time 3` - maksymalnie 3 sekundy na całą operację
- `--connect-timeout 2` - maksymalnie 2 sekundy na połączenie
- `|| true` - workflow nie kończy się błędem przy timeout

### Zmiany w plikach

#### `.github/workflows/sync.yml`

**Przed:**
```yaml
- name: Send POST request cron
  run: |
    curl -X POST "https://develogic.dfirma.pl/wp-json/develogic/v1/sync?secret_key=1234"
```

**Po:**
```yaml
- name: Send POST request cron
  run: |
    curl -X POST --max-time 3 --connect-timeout 2 "https://develogic.dfirma.pl/wp-json/develogic/v1/sync?secret_key=1234" || true
```

### Jak to działa

1. **`--max-time 3`** - Curl czeka maksymalnie 3 sekundy na odpowiedź
2. **`--connect-timeout 2`** - Curl czeka maksymalnie 2 sekundy na nawiązanie połączenia
3. **`|| true`** - Jeśli curl zwróci błąd (np. timeout), komenda i tak zwraca sukces

### Zachowanie

- Request POST jest wysyłany do serwera
- Endpoint otrzymuje request i rozpoczyna synchronizację
- Curl czeka maksymalnie 3 sekundy
- Po 3 sekundach workflow kończy się sukcesem, niezależnie od tego czy synchronizacja się zakończyła
- Synchronizacja na serwerze działa dalej w tle

### Uwagi

Jest to podejście "fire and forget" - workflow wysyła request i nie czeka na jego zakończenie. Endpoint musi być odpowiednio skonfigurowany, aby działać asynchronicznie.

Jeśli potrzebujesz pełnego asynchronicznego podejścia bez żadnego czekania, rozważ:
- Użycie `--max-time 1` dla jeszcze krótszego timeoutu
- Usunięcie timeoutu i uruchomienie w tle z `nohup` lub `&`
- Zmianę endpointu, aby natychmiast zwracał 200 OK i uruchamiał synchronizację w tle

