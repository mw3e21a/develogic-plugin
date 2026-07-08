/* ============================================================================
 * Synchronizacja filtra pięter <-> Image Map Pro — BUDYNEK E  (wersja DIAG)
 * ----------------------------------------------------------------------------
 * Ta wersja NIE polega na sztywnej nazwie mapy. Sama wykrywa instancję Image
 * Map Pro i loguje w konsoli, co jest dostępne — dzięki temu zobaczymy, czemu
 * przejście do artboardu nie działa.
 *
 * Otwórz konsolę przeglądarki (F12 -> Console) i patrz na wpisy [E-SYNC].
 * ==========================================================================*/

jQuery(document).ready(function ($) {

    var TAG = '[E-SYNC]';

    // Artboardy (tekst opcji w selekcie IMP). Odczyt jest po tym tekście.
    var ROOT_ARTBOARD = 'Budynek E';

    // floorFilter (wartość <option>)  ->  tekst artboardu w Image Map Pro
    var floorValueToArtboard = {
        '0': 'PARTER',
        '1': 'PIĘTRO I',
        '2': 'PIĘTRO II',
        '3': 'PIĘTRO III',
        '4': 'PIĘTRO IV',
        '5': 'PIĘTRO V',
        '6': 'PIĘTRO VI',
        '7': 'PIĘTRO VII'
    };
    var artboardToFloorValue = {};
    Object.keys(floorValueToArtboard).forEach(function (v) {
        artboardToFloorValue[floorValueToArtboard[v]] = v;
    });

    // --- Stan synchronizacji -----------------------------------------------
    var SUPPRESS_MS = 1500;
    var suppressPollUntil = 0;
    var pendingArtboard = '';
    var lastArtboard = '';

    var headingEl = document.querySelector('.develogic-apartments-container .title');
    var originalHeadingText = headingEl ? headingEl.textContent.trim() : '';

    // =======================================================================
    // DIAGNOSTYKA: co Image Map Pro faktycznie udostępnia?
    // =======================================================================
    console.log(TAG, 'typeof $.imageMapProGoToFloor =', typeof $.imageMapProGoToFloor);
    console.log(TAG, 'window.imageMapPro =', window.imageMapPro);
    // Wypisz wszystkie klucze na window/$ zawierające "imageMap"
    try {
        var hits = [];
        Object.keys(window).forEach(function (k) {
            if (/imagemap/i.test(k)) hits.push('window.' + k);
        });
        Object.keys($).forEach(function (k) {
            if (/imagemap/i.test(k)) hits.push('$.' + k);
        });
        console.log(TAG, 'znalezione API Image Map Pro:', hits);
    } catch (e) {}

    var layerSelectEl = document.querySelector('.imp-ui-layers-select');
    console.log(TAG, '.imp-ui-layers-select =', layerSelectEl);
    if (layerSelectEl) {
        var opts = Array.prototype.map.call(layerSelectEl.options, function (o) {
            return JSON.stringify(o.text);
        });
        console.log(TAG, 'opcje artboardów (dokładny tekst):', opts.join(', '));
    }

    // =======================================================================
    // Uniwersalne przejście do artboardu — próbuje wielu sposobów i loguje,
    // który zadziałał. Zwraca true, jeśli którakolwiek metoda się wykonała.
    // =======================================================================
    function goTo(artboardText) {
        console.log(TAG, '-> goTo(', artboardText, ')');

        // Metoda A: publiczne API po nazwie mapy (jeśli istnieje).
        // Wypróbuj kilka prawdopodobnych nazw mapy.
        if (typeof $.imageMapProGoToFloor === 'function') {
            var mapNames = ['Budynek E', 'budynek-e', 'Budynek_E'];
            for (var i = 0; i < mapNames.length; i++) {
                try {
                    $.imageMapProGoToFloor(mapNames[i], artboardText);
                    console.log(TAG, '   próba imageMapProGoToFloor("' + mapNames[i] + '", "' + artboardText + '")');
                } catch (err) {
                    console.log(TAG, '   błąd imageMapProGoToFloor:', err && err.message);
                }
            }
        }

        // Metoda B (fallback, ZAWSZE działa): przełącz natywny <select> IMP.
        // To dokładnie to, co robi użytkownik klikając w menu warstw mapy,
        // więc mapa na pewno zareaguje — niezależnie od nazwy instancji.
        var sel = document.querySelector('.imp-ui-layers-select');
        if (sel) {
            var matched = false;
            for (var j = 0; j < sel.options.length; j++) {
                if (sel.options[j].text.trim() === artboardText) {
                    sel.selectedIndex = j;
                    matched = true;
                    break;
                }
            }
            if (matched) {
                // Wyzwól zdarzenia, których słucha Image Map Pro.
                sel.dispatchEvent(new Event('change', { bubbles: true }));
                sel.dispatchEvent(new Event('input', { bubbles: true }));
                console.log(TAG, '   fallback: ustawiono .imp-ui-layers-select na', artboardText);
                return true;
            } else {
                console.warn(TAG, '   fallback: brak opcji o tekście', JSON.stringify(artboardText),
                    '— sprawdź, czy nazwy artboardów się zgadzają!');
            }
        }
        return false;
    }

    function pushToMap(artboardText) {
        goTo(artboardText);
        pendingArtboard = artboardText;
        lastArtboard = artboardText;
        suppressPollUntil = Date.now() + SUPPRESS_MS;
    }

    function setFloorFilter(value) {
        var ff = document.getElementById('floorFilter');
        if (!ff || ff.value === value) return;
        // Czy opcja o tej wartości w ogóle istnieje w #floorFilter?
        var hasOption = Array.prototype.some.call(ff.options, function (o) {
            return o.value === value;
        });
        if (!hasOption) {
            console.warn(TAG, 'brak opcji floorFilter dla wartości', JSON.stringify(value),
                '— wtyczka nie wygenerowała tego piętra (brak mieszkań w danych?).');
            return;
        }
        ff.value = value;
        ff.dispatchEvent(new Event('change'));
    }

    function getCurrentArtboard() {
        var sel = document.querySelector('.imp-ui-layers-select');
        if (!sel || sel.selectedIndex < 0) return '';
        return sel.options[sel.selectedIndex].text.trim();
    }

    // =======================================================================
    // KIERUNEK 1:  floorFilter  ->  Image Map Pro
    // =======================================================================
    var floorFilter = document.getElementById('floorFilter');
    if (floorFilter) {
        floorFilter.addEventListener('change', function () {
            var val = floorFilter.value;
            console.log(TAG, 'floorFilter change ->', val);

            if (val === 'all') {
                pushToMap(ROOT_ARTBOARD);
                if (headingEl) headingEl.textContent = originalHeadingText;
                return;
            }
            var artboard = floorValueToArtboard[val];
            if (artboard) {
                pushToMap(artboard);
            } else {
                console.log(TAG, '   brak artboardu dla wartości', val, '(np. Piwnica -1 nie istnieje w Bud. E)');
            }
        });
    } else {
        console.warn(TAG, 'nie znaleziono #floorFilter');
    }

    // =======================================================================
    // KIERUNEK 2:  Image Map Pro  ->  floorFilter
    // =======================================================================
    setInterval(function () {
        var current = getCurrentArtboard();
        if (!current) return;

        if (Date.now() < suppressPollUntil) {
            if (current === pendingArtboard) {
                lastArtboard = current;
                suppressPollUntil = 0;
                pendingArtboard = '';
            }
            return;
        }

        if (current === lastArtboard) return;
        console.log(TAG, 'poll wykrył zmianę artboardu ->', current);
        lastArtboard = current;

        if (current === ROOT_ARTBOARD) {
            setFloorFilter('all');
            if (headingEl) headingEl.textContent = originalHeadingText;
            return;
        }
        var floorVal = artboardToFloorValue[current];
        if (floorVal !== undefined) {
            setFloorFilter(floorVal);
        }
    }, 300);

    // =======================================================================
    // Reset filtrów
    // =======================================================================
    var resetBtn = document.getElementById('resetFilters');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            pushToMap(ROOT_ARTBOARD);
            if (headingEl) headingEl.textContent = originalHeadingText;
        });
    }

    console.log(TAG, 'inicjalizacja zakończona.');
});
