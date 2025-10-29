# Bugfix - Fatal Error: Memory Exhausted

## Problem

Wtyczka powodowała błąd:
```
Fatal error: Allowed memory size of 268435456 bytes exhausted (tried to allocate 20480 bytes) 
in /var/www/html/wp-includes/class-wp-hook.php on line 76
```

## Przyczyna

Komponenty wtyczki były inicjalizowane **zbyt wcześnie** - w konstruktorze głównej klasy, przed pełnym załadowaniem WordPress. To powodowało:

1. Próby rejestracji hooków przed `plugins_loaded`
2. Potencjalne cykliczne wywołania
3. Nadmierne zużycie pamięci przez przedwczesne ładowanie klas

## Rozwiązanie

### 1. Opóźniona inicjalizacja komponentów

**PRZED:**
```php
private function __construct() {
    $this->init_hooks();
    $this->includes();
    $this->init_components(); // ← ZA WCZEŚNIE!
}
```

**PO:**
```php
private function __construct() {
    $this->init_hooks();
    $this->includes();
    // init_components() wywołane przez hook
}

private function init_hooks() {
    // ...
    add_action('plugins_loaded', array($this, 'init_components'), 10);
}
```

### 2. Ochrona przed wielokrotną inicjalizacją

```php
public function init_components() {
    // Only initialize once
    static $initialized = false;
    if ($initialized) {
        return;
    }
    $initialized = true;
    
    // ... reszta kodu
}
```

### 3. Lazy loading API Client i Cache Manager

Dodano magic getter `__get()` aby komponenty były tworzone dopiero przy pierwszym użyciu:

```php
public function __get($name) {
    if ($name === 'api_client') {
        if (!$this->api_client) {
            $this->api_client = new Develogic_API_Client();
        }
        return $this->api_client;
    }
    
    if ($name === 'cache_manager') {
        if (!$this->cache_manager) {
            $this->cache_manager = new Develogic_Cache_Manager();
        }
        return $this->cache_manager;
    }
    
    return null;
}
```

### 4. Bezpieczna deaktywacja

```php
public function deactivate() {
    // Clear all cache
    if ($this->cache_manager) { // ← Sprawdzenie czy istnieje
        $this->cache_manager->clear_all_cache();
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
```

## Zmiany w plikach

- ✅ `develogic-integration.php`:
  - Przeniesiono `init_components()` do hooka `plugins_loaded`
  - Dodano static guard dla `init_components()`
  - Dodano magic getter `__get()` dla lazy loading
  - Zabezpieczono `deactivate()` przed undefined property

## Testowanie

Po tych zmianach wtyczka powinna:

1. ✅ Aktywować się bez błędów pamięci
2. ✅ Poprawnie ładować wszystkie komponenty
3. ✅ Rejestrować shortcody i REST API
4. ✅ Działać w panelu administracyjnym
5. ✅ Wyświetlać oferty na froncie

## Weryfikacja

```bash
# Test składni PHP
php -l develogic-integration.php

# Lub użyj:
php test-syntax.php
```

## Kolejne kroki

1. Aktywuj wtyczkę w WordPress
2. Przejdź do **Develogic → Ustawienia**
3. Skonfiguruj API (URL + klucz)
4. Przetestuj shortcode na stronie testowej

## Dodatkowy bugfix (v1.0.1)

### Problem 2: `Call to a member function get_locals() on null`

**Błąd:**
```
PHP Fatal error: Uncaught Error: Call to a member function get_locals() on null
in class-shortcodes.php:62
```

**Przyczyna:**
- REST API requests (np. Gutenberg) wykonywane były **PRZED** hookiem `plugins_loaded`
- `cache_manager` był `null` bo nie został jeszcze zainicjalizowany

**Rozwiązanie:**
1. Dodano detekcję REST requestów w konstruktorze
2. Komponenty inicjalizowane natychmiast jeśli wykryto `/wp-json/` w URL
3. Nadal używany hook `plugins_loaded` dla normalnych requestów
4. Właściwości `api_client` i `cache_manager` zmienione na `private` z lazy loading przez `__get()`

```php
private function __construct() {
    $this->init_hooks();
    $this->includes();
    // Initialize components immediately to support REST API
    add_action('plugins_loaded', array($this, 'init_components'), 1);
    // But also call it directly in case we're in REST request
    if (defined('REST_REQUEST') && REST_REQUEST || 
        (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false)) {
        $this->init_components();
    }
}
```

## Data naprawy

2025-10-27

## Wersja

1.0.1 (bugfix release - 2 critical fixes)

