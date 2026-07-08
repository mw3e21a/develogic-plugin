/* ============================================================================
 * Synchronizacja filtra pięter <-> Image Map Pro — BUDYNEK E
 * ----------------------------------------------------------------------------
 * Wkleić jako snippet (Code Snippets / Custom JS) na stronie z mapą Budynku E.
 *
 * WAŻNE: skrypt NIE zakłada, że jQuery jest już załadowane. Czeka na nie, bo
 * w zależności od kolejności ładowania (cache/optymalizacja) snippet potrafi
 * wykonać się PRZED jQuery — wtedy `jQuery(...)` rzuca ReferenceError i cała
 * synchronizacja przestaje działać.
 * ==========================================================================*/

(function () {
    'use strict';

    // Poczekaj aż jQuery będzie dostępne, potem odpal właściwą logikę.
    function whenJQueryReady(cb) {
        if (window.jQuery) {
            window.jQuery(function () { cb(window.jQuery); });
            return;
        }
        var tries = 0;
        var timer = setInterval(function () {
            if (window.jQuery) {
                clearInterval(timer);
                window.jQuery(function () { cb(window.jQuery); });
            } else if (++tries > 100) { // ~10 s i rezygnujemy
                clearInterval(timer);
            }
        }, 100);
    }

    whenJQueryReady(function ($) {

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

        // --- Stan synchronizacji -------------------------------------------
        var SUPPRESS_MS = 1500;
        var suppressPollUntil = 0;
        var pendingArtboard = '';
        var lastArtboard = '';

        var headingEl = document.querySelector('.develogic-apartments-container .title');
        var originalHeadingText = headingEl ? headingEl.textContent.trim() : '';

        // Przejście do artboardu: najpierw publiczne API (jeśli jest), a jako
        // pewny fallback — przełączenie natywnego <select> warstw Image Map Pro
        // (to samo, co robi użytkownik klikając w menu warstw mapy).
        function goTo(artboardText) {
            if (typeof $.imageMapProGoToFloor === 'function') {
                var mapNames = ['Budynek E', 'budynek-e', 'Budynek_E'];
                for (var i = 0; i < mapNames.length; i++) {
                    try { $.imageMapProGoToFloor(mapNames[i], artboardText); } catch (err) {}
                }
            }
            var sel = document.querySelector('.imp-ui-layers-select');
            if (sel) {
                for (var j = 0; j < sel.options.length; j++) {
                    if (sel.options[j].text.trim() === artboardText) {
                        sel.selectedIndex = j;
                        sel.dispatchEvent(new Event('change', { bubbles: true }));
                        sel.dispatchEvent(new Event('input', { bubbles: true }));
                        return true;
                    }
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
            var hasOption = Array.prototype.some.call(ff.options, function (o) {
                return o.value === value;
            });
            if (!hasOption) return; // wtyczka nie wygenerowała tego piętra
            ff.value = value;
            ff.dispatchEvent(new Event('change'));
        }

        function getCurrentArtboard() {
            var sel = document.querySelector('.imp-ui-layers-select');
            if (!sel || sel.selectedIndex < 0) return '';
            return sel.options[sel.selectedIndex].text.trim();
        }

        // ===================================================================
        // KIERUNEK 1:  floorFilter  ->  Image Map Pro
        // ===================================================================
        var floorFilter = document.getElementById('floorFilter');
        if (floorFilter) {
            floorFilter.addEventListener('change', function () {
                var val = floorFilter.value;
                if (val === 'all') {
                    pushToMap(ROOT_ARTBOARD);
                    if (headingEl) headingEl.textContent = originalHeadingText;
                    return;
                }
                var artboard = floorValueToArtboard[val];
                if (artboard) {
                    pushToMap(artboard);
                }
                // val === '-1' (Piwnica) nie istnieje w Budynku E -> nic.
            });
        }

        // ===================================================================
        // KIERUNEK 2:  Image Map Pro  ->  floorFilter
        // ===================================================================
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

        // ===================================================================
        // Reset filtrów -> widok całego budynku
        // ===================================================================
        var resetBtn = document.getElementById('resetFilters');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                pushToMap(ROOT_ARTBOARD);
                if (headingEl) headingEl.textContent = originalHeadingText;
            });
        }
    });
})();
