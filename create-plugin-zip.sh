#!/bin/bash
# Skrypt do tworzenia poprawnego, CZYSTEGO archiwum ZIP wtyczki WordPress
# Użycie: ./create-plugin-zip.sh
#
# Pakuje tylko pliki potrzebne wtyczce w produkcji (whitelist), dzięki czemu
# archiwum jest małe (~150 KB zamiast kilku MB) i nie puchnie przy kolejnych
# uruchomieniach. Pomija: dokumentację .md, przykłady, pliki debug/test,
# wielkie testowe JSON-y, .git/.idea/.github, poprzednie .zip itp.

set -e

PLUGIN_DIR="develogic-integration"
ZIP_FILE="develogic-integration.zip"
CURRENT_DIR="$(pwd)"

# Sprawdź, czy jesteśmy w katalogu wtyczki
if [ ! -f "develogic-integration.php" ]; then
    echo "❌ Błąd: Uruchom skrypt z katalogu głównego wtyczki"
    echo "   Plik develogic-integration.php nie został znaleziony"
    exit 1
fi

# --- Whitelist: co trafia do archiwum -----------------------------------------
# Katalogi ładowane/ używane przez wtyczkę w runtime.
INCLUDE_DIRS=(
    "includes"
    "admin"
    "public"
    "assets"
    "templates"
)
# Pojedyncze pliki wymagane przez WordPress / wtyczkę.
INCLUDE_FILES=(
    "develogic-integration.php"   # główny plik wtyczki (nagłówek Plugin Name)
    "uninstall.php"               # obsługa odinstalowania
    "readme.txt"                  # metadane wtyczki dla WP
    "LICENSE"
)
# Dołącz languages/ tylko jeśli istnieje (tłumaczenia).
if [ -d "languages" ]; then
    INCLUDE_DIRS+=("languages")
fi

echo "🔍 Sprawdzanie struktury wtyczki..."

# Utwórz tymczasowy katalog roboczy
TEMP_DIR="$(mktemp -d)"
DEST="$TEMP_DIR/$PLUGIN_DIR"
mkdir -p "$DEST"
# Posprzątaj katalog tymczasowy przy każdym wyjściu.
trap 'rm -rf "$TEMP_DIR"' EXIT

echo "📋 Kopiowanie tylko potrzebnych plików..."

# Skopiuj katalogi z whitelisty
for dir in "${INCLUDE_DIRS[@]}"; do
    if [ -d "$CURRENT_DIR/$dir" ]; then
        cp -r "$CURRENT_DIR/$dir" "$DEST/$dir"
        echo "   + $dir/"
    fi
done

# Skopiuj pojedyncze pliki z whitelisty
for file in "${INCLUDE_FILES[@]}"; do
    if [ -f "$CURRENT_DIR/$file" ]; then
        cp "$CURRENT_DIR/$file" "$DEST/$file"
        echo "   + $file"
    fi
done

# --- Dodatkowe czyszczenie w SKOPIOWANYCH plikach -----------------------------
# Usuń śmieci, które mogły przyjechać wewnątrz whitelistowanych katalogów.
echo "🧹 Czyszczenie resztek (debug/test/przykłady/edytorskie)..."
find "$DEST" -type d -name ".git" -prune -exec rm -rf {} + 2>/dev/null || true
find "$DEST" -type d -name ".idea" -prune -exec rm -rf {} + 2>/dev/null || true
find "$DEST" -type d -name ".claude" -prune -exec rm -rf {} + 2>/dev/null || true
find "$DEST" -type d -name "examples" -prune -exec rm -rf {} + 2>/dev/null || true
find "$DEST" -type f -name "debug-*.php" -delete 2>/dev/null || true
find "$DEST" -type f -name "test-*.php" -delete 2>/dev/null || true
find "$DEST" -type f -name ".DS_Store" -delete 2>/dev/null || true
find "$DEST" -type f -name "*.zip" -delete 2>/dev/null || true

# Sprawdź strukturę przed utworzeniem ZIP
if [ ! -f "$DEST/develogic-integration.php" ]; then
    echo "❌ Błąd: Główny plik wtyczki nie znajduje się w katalogu głównym archiwum"
    exit 1
fi

# Utwórz archiwum ZIP (musi zawierać jeden katalog główny, nie luźne pliki)
echo "📦 Tworzenie archiwum ZIP..."
( cd "$TEMP_DIR" && zip -r "$ZIP_FILE" "$PLUGIN_DIR" -q )

if [ ! -f "$TEMP_DIR/$ZIP_FILE" ]; then
    echo "❌ Błąd: Nie udało się utworzyć archiwum ZIP"
    exit 1
fi

# Test integralności
if ! unzip -tq "$TEMP_DIR/$ZIP_FILE" > /dev/null 2>&1; then
    echo "❌ Błąd: Utworzone archiwum jest uszkodzone"
    exit 1
fi

# Przenieś gotowe archiwum do katalogu głównego (nadpisując stare)
mv -f "$TEMP_DIR/$ZIP_FILE" "$CURRENT_DIR/$ZIP_FILE"

ZIP_SIZE="$(du -h "$CURRENT_DIR/$ZIP_FILE" | cut -f1)"
FILE_COUNT="$(unzip -l "$CURRENT_DIR/$ZIP_FILE" | tail -1 | awk '{print $2}')"

echo ""
echo "🔍 Zawartość archiwum (katalogi najwyższego poziomu):"
unzip -l "$CURRENT_DIR/$ZIP_FILE" | awk '{print $4}' | grep "^$PLUGIN_DIR/" \
    | sed "s|^$PLUGIN_DIR/||" | grep "/" | sed 's|/.*||' | sort -u | sed 's|^|   |'

echo ""
echo "✅ Archiwum utworzone pomyślnie: $ZIP_FILE"
echo "📊 Rozmiar: $ZIP_SIZE   |   Plików: $FILE_COUNT"
echo "✅ Gotowe do instalacji w WordPress!"
echo ""
echo "📝 Instrukcja instalacji:"
echo "   1. Zaloguj się do panelu WordPress"
echo "   2. Przejdź do: Wtyczki → Dodaj nową → Wgraj wtyczkę"
echo "   3. Wybierz plik: $ZIP_FILE"
echo "   4. Kliknij: Zainstaluj teraz"
echo ""
