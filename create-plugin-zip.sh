#!/bin/bash
# Skrypt do tworzenia poprawnego archiwum ZIP wtyczki WordPress
# Użycie: ./create-plugin-zip.sh

PLUGIN_DIR="develogic-integration"
ZIP_FILE="develogic-integration.zip"
CURRENT_DIR=$(pwd)

# Sprawdź, czy jesteśmy w katalogu wtyczki
if [ ! -f "develogic-integration.php" ]; then
    echo "❌ Błąd: Uruchom skrypt z katalogu głównego wtyczki"
    echo "   Plik develogic-integration.php nie został znaleziony"
    exit 1
fi

echo "🔍 Sprawdzanie struktury wtyczki..."

# Sprawdź czy istnieje główny plik wtyczki
if [ ! -f "develogic-integration.php" ]; then
    echo "❌ Błąd: Brak głównego pliku wtyczki develogic-integration.php"
    exit 1
fi

# Sprawdź czy istnieje readme.txt
if [ ! -f "readme.txt" ]; then
    echo "⚠️  Ostrzeżenie: Brak pliku readme.txt (może powodować problemy z instalacją)"
fi

# Utwórz tymczasowy katalog
TEMP_DIR=$(mktemp -d)
echo "📦 Tworzenie archiwum w katalogu tymczasowym: $TEMP_DIR"

# Skopiuj wszystkie pliki do tymczasowego katalogu z właściwą nazwą
echo "📋 Kopiowanie plików..."
cp -r "$CURRENT_DIR" "$TEMP_DIR/$PLUGIN_DIR"

# Przejdź do katalogu tymczasowego
cd "$TEMP_DIR"

# Usuń niepotrzebne pliki i foldery (opcjonalnie - zakomentuj jeśli chcesz je zachować)
echo "🧹 Czyszczenie niepotrzebnych plików..."
rm -rf "$PLUGIN_DIR/.git" 2>/dev/null
rm -rf "$PLUGIN_DIR/.gitignore" 2>/dev/null
rm -f "$PLUGIN_DIR/create-plugin-zip.sh" 2>/dev/null
rm -f "$PLUGIN_DIR/debug-*.php" 2>/dev/null || true
rm -f "$PLUGIN_DIR/test-*.php" 2>/dev/null || true
rm -f "$PLUGIN_DIR/*.sh" 2>/dev/null || true

# Sprawdź strukturę przed utworzeniem ZIP
echo "✅ Sprawdzanie struktury archiwum..."
if [ ! -f "$PLUGIN_DIR/develogic-integration.php" ]; then
    echo "❌ Błąd: Główny plik wtyczki nie znajduje się w katalogu głównym archiwum"
    cd "$CURRENT_DIR"
    rm -rf "$TEMP_DIR"
    exit 1
fi

# Utwórz archiwum ZIP (ważne: ZIP musi zawierać folder, nie pliki bezpośrednio)
echo "📦 Tworzenie archiwum ZIP..."
zip -r "$ZIP_FILE" "$PLUGIN_DIR" -q

# Sprawdź czy archiwum zostało utworzone
if [ ! -f "$ZIP_FILE" ]; then
    echo "❌ Błąd: Nie udało się utworzyć archiwum ZIP"
    cd "$CURRENT_DIR"
    rm -rf "$TEMP_DIR"
    exit 1
fi

# Sprawdź rozmiar archiwum
ZIP_SIZE=$(du -h "$ZIP_FILE" | cut -f1)
echo "📊 Rozmiar archiwum: $ZIP_SIZE"

# Sprawdź zawartość ZIP
echo "🔍 Zawartość archiwum:"
unzip -l "$ZIP_FILE" | head -20

# Przenieś archiwum do katalogu głównego
mv "$ZIP_FILE" "$CURRENT_DIR/"

# Usuń katalog tymczasowy
cd "$CURRENT_DIR"
rm -rf "$TEMP_DIR"

echo ""
echo "✅ Archiwum utworzone pomyślnie: $ZIP_FILE"
echo "✅ Gotowe do instalacji w WordPress!"
echo ""
echo "📝 Instrukcja instalacji:"
echo "   1. Zaloguj się do panelu WordPress"
echo "   2. Przejdź do: Wtyczki → Dodaj nową → Wgraj wtyczkę"
echo "   3. Wybierz plik: $ZIP_FILE"
echo "   4. Kliknij: Zainstaluj teraz"
echo ""

