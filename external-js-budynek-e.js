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

        // Ustaw na true, żeby snippet raportował w konsoli co robi przy kliknięciu.
        var DEBUG = false;
        function log() {
            if (!DEBUG || !window.console) return;
            console.log.apply(console, ['[sync pięter]'].concat(Array.prototype.slice.call(arguments)));
        }

        // Artboardy (tekst opcji w selekcie IMP). Odczyt jest po tym tekście.
        var ROOT_ARTBOARD = 'Budynek E';

        // floorFilter (wartość <option>)  ->  kandydaci na tekst artboardu w IMP.
        // Pierwszy z listy, który faktycznie istnieje w menu warstw, zostaje użyty.
        // W Budynku E kondygnacje garażowe to "PIWNICA -1" i "PIWNICA -2" — stoją
        // na początku list; reszta wariantów to zapas na wypadek zmiany nazw.
        var floorValueToArtboard = {
            '-2': ['PIWNICA -2', 'GARAŻ -2', 'GARAZ -2', 'POZIOM -2', 'KONDYGNACJA -2', '-2'],
            '-1': ['PIWNICA -1', 'GARAŻ -1', 'GARAZ -1', 'POZIOM -1', 'KONDYGNACJA -1', 'PIWNICA', 'GARAŻ', 'GARAZ', '-1'],
            '0': ['PARTER'],
            '1': ['PIĘTRO I'],
            '2': ['PIĘTRO II'],
            '3': ['PIĘTRO III'],
            '4': ['PIĘTRO IV'],
            '5': ['PIĘTRO V'],
            '6': ['PIĘTRO VI'],
            '7': ['PIĘTRO VII']
        };
        // Odwrotna mapa: tekst artboardu (UPPERCASE) -> wartość filtra pięter.
        var artboardToFloorValue = {};
        Object.keys(floorValueToArtboard).forEach(function (v) {
            floorValueToArtboard[v].forEach(function (name) {
                artboardToFloorValue[name.toUpperCase()] = v;
            });
        });

        // --- Stan synchronizacji -------------------------------------------
        var SUPPRESS_MS = 1500;
        var suppressPollUntil = 0;
        // Ustawiana na czas programowej zmiany #floorFilter z przycisku, żeby
        // handler zmiany nie przełączał artboardu drugi raz.
        var syncingFromButton = false;
        var pendingArtboard = '';
        var lastArtboard = '';

        var headingEl = document.querySelector('.develogic-apartments-container .title');
        var originalHeadingText = headingEl ? headingEl.textContent.trim() : '';

        // Przejście do artboardu: najpierw publiczne API (jeśli jest), a jako
        // pewny fallback — przełączenie natywnego <select> warstw Image Map Pro
        // (to samo, co robi użytkownik klikając w menu warstw mapy).
        // Zwraca tekst artboardu tak, jak zapisany jest w menu warstw mapy —
        // z listy kandydatów bierze pierwszy, który w tym menu istnieje.
        // Porównanie bez uwzględniania wielkości liter, bo nazwy warstw bywają
        // zapisane różnie ("Piwnica" vs "PIWNICA").
        function resolveArtboard(candidates) {
            var list = Array.isArray(candidates) ? candidates : [candidates];
            var sel = document.querySelector('.imp-ui-layers-select');
            if (!sel) return list[0];
            for (var i = 0; i < list.length; i++) {
                for (var j = 0; j < sel.options.length; j++) {
                    if (sel.options[j].text.trim().toUpperCase() === list[i].toUpperCase()) {
                        return sel.options[j].text.trim();
                    }
                }
            }
            return null; // mapa nie ma takiego artboardu
        }

        // Fallback: dopasowanie po samym numerze kondygnacji. Dzięki temu snippet
        // trafia w artboard nawet gdy nazwano go inaczej niż przewiduje lista
        // kandydatów (np. "Garaż podziemny -2" albo "Hala -1").
        function resolveArtboardByNumber(floorValue) {
            var sel = document.querySelector('.imp-ui-layers-select');
            if (!sel) return null;
            var want = normalizeFloorValue(floorValue);
            if (want === '' || isNaN(parseInt(want, 10))) return null;
            for (var i = 0; i < sel.options.length; i++) {
                var text = sel.options[i].text.trim();
                if (text.toUpperCase() === ROOT_ARTBOARD.toUpperCase()) continue;
                if (normalizeFloorValue(text) === want) {
                    return text;
                }
            }
            return null;
        }

        function goTo(artboardText) {
            if (typeof $.imageMapProGoToFloor === 'function') {
                var mapNames = ['Budynek E', 'budynek-e', 'Budynek_E'];
                for (var i = 0; i < mapNames.length; i++) {
                    try { $.imageMapProGoToFloor(mapNames[i], artboardText); } catch (err) { }
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
            if (!artboardText) return;
            goTo(artboardText);
            pendingArtboard = artboardText;
            lastArtboard = artboardText;
            suppressPollUntil = Date.now() + SUPPRESS_MS;
        }

        // "piwnica"/"Piwnica"/"-1" -> "-1", "parter" -> "0" itd. Wtyczka renderuje
        // opcje pięter z danych API, gdzie kondygnacja podziemna bywa tekstem.
        function normalizeFloorValue(value) {
            var str = String(value == null ? '' : value).trim();
            if (str === '') return '';
            if (str.toLowerCase() === 'parter') return '0';
            if (str.toLowerCase() === 'piwnica') return '-1';
            // Minus musi być częścią dopasowania, inaczej "Piwnica -2" -> "2".
            var m = str.match(/-?\d+/);
            if (m) return m[0];
            // Zapis rzymski ("PIĘTRO VII", "Piętro IV") — tylko gdy brak cyfr.
            var roman = [['VIII', '8'], ['VII', '7'], ['III', '3'], ['VI', '6'],
                         ['IV', '4'], ['IX', '9'], ['II', '2'], ['X', '10'],
                         ['V', '5'], ['I', '1']];
            var upper = str.toUpperCase();
            for (var i = 0; i < roman.length; i++) {
                if (new RegExp('\\b' + roman[i][0] + '\\b').test(upper)) {
                    return roman[i][1];
                }
            }
            return str;
        }

        // force = wyślij zdarzenie 'change' także wtedy, gdy wartość się nie zmieniła.
        // Potrzebne przy kliknięciu przycisku: wtyczka mogła już ustawić tę samą
        // wartość, ale bez przefiltrowania listy.
        function setFloorFilter(value, force) {
            var ff = document.getElementById('floorFilter');
            if (!ff) { log('brak #floorFilter na stronie'); return false; }
            // Dopasowanie po znormalizowanej wartości — opcja może mieć value
            // "piwnica", a my przychodzimy z "-1" (i odwrotnie).
            var target = null;
            for (var i = 0; i < ff.options.length; i++) {
                var optVal = ff.options[i].value;
                if (optVal === value || (value !== 'all' && normalizeFloorValue(optVal) === normalizeFloorValue(value))) {
                    target = optVal;
                    break;
                }
            }
            if (target === null) {
                // Najczęstsza przyczyna: wtyczka nie wygenerowała opcji dla tej
                // kondygnacji, bo na niej nie ma żadnego lokalu w widocznym
                // statusie — albo na stronie działa jeszcze stara wersja wtyczki,
                // która nie rozpoznaje kondygnacji "piwnica -1" / "piwnica -2".
                if (window.console) {
                    console.warn('[sync pięter] #floorFilter nie ma opcji dla piętra "' + value +
                        '". Dostępne: ' + Array.prototype.map.call(ff.options, function (o) {
                            return o.value;
                        }).join(', ') + '. Tabelka nie zostanie przefiltrowana.');
                }
                return false;
            }
            if (ff.value === target && !force) return true;
            ff.value = target;
            ff.dispatchEvent(new Event('change', { bubbles: true }));
            log('ustawiono #floorFilter =', target);
            return true;
        }

        function getCurrentArtboard() {
            var sel = document.querySelector('.imp-ui-layers-select');
            if (!sel || sel.selectedIndex < 0) return '';
            return sel.options[sel.selectedIndex].text.trim();
        }

        // ===================================================================
        // Przyciski pięter (widgety tekstowe Elementora)
        // -------------------------------------------------------------------
        // Elementor wkleja KAŻDY przycisk z tym samym id="piwnica-i", więc
        // querySelector('#piwnica-i') trafia wyłącznie w pierwszy z nich i
        // wiązanie po id z założenia nie może działać. Jedyne, co odróżnia te
        // przyciski, to TEKST etykiety — i po nim je rozpoznajemy. Dzięki temu
        // dochodzące kondygnacje ("Piwnica -2") działają bez zmian w kodzie.
        // ===================================================================
        var ACTIVE_BG = '#0066cc';
        var floorButtons = [];

        function collectFloorButtons() {
            var found = [];
            var nodes = document.querySelectorAll('.elementor-widget-text-editor p');
            Array.prototype.forEach.call(nodes, function (el) {
                var text = (el.textContent || '').trim();
                if (!/^(piwnica|parter|pi[eę]tro)\b/i.test(text)) return;
                var value = normalizeFloorValue(text);
                if (value === '' || isNaN(parseInt(value, 10))) return;
                found.push({ el: el, value: String(parseInt(value, 10)), text: text });
            });
            return found;
        }

        function clearButtonStyles() {
            floorButtons.forEach(function (btn) {
                btn.el.style.backgroundColor = '';
                btn.el.style.color = '';
                var link = btn.el.querySelector('a');
                if (link) link.style.color = '';
            });
        }

        // Podświetla przycisk odpowiadający danej kondygnacji ('all' = żaden).
        function highlightFloorButton(floorValue) {
            clearButtonStyles();
            if (floorValue === 'all' || floorValue === undefined || floorValue === null) return;
            var want = normalizeFloorValue(floorValue);
            floorButtons.forEach(function (btn) {
                if (btn.value !== want) return;
                btn.el.style.backgroundColor = ACTIVE_BG;
                btn.el.style.color = 'white';
                var link = btn.el.querySelector('a');
                if (link) link.style.color = 'white';
            });
        }

        // Wspólna ścieżka dla kliknięcia przycisku i zmiany w filtrze pięter.
        function goToFloor(floorValue) {
            var key = normalizeFloorValue(floorValue);
            var artboard = resolveArtboard(floorValueToArtboard[key] || [])
                || resolveArtboardByNumber(key);
            if (artboard) {
                pushToMap(artboard);
            }
            highlightFloorButton(key);
            return artboard;
        }

        // Po kliknięciu przycisku wtyczka i tak zareaguje na 'artboardChange'
        // Image Map Pro — asynchronicznie, czyli JUŻ PO naszym setFloorFilter —
        // i potrafi wpisać do #floorFilter własną wartość. Przy kondygnacjach
        // podziemnych bywa ona błędna (starsze wersje wtyczki gubiły minus i
        // z "PIWNICA -2" robiły piętro 2). Przy zmianie z poziomu przełącznika
        // warstw poprawiał to poll, ale po kliknięciu przycisku poll jest
        // wyciszony — i nikt tego nie prostował. Stąd blokada: przez chwilę po
        // kliknięciu pilnujemy, żeby filtr trzymał wybraną kondygnację.
        var FLOOR_LOCK_MS = 2500;
        var floorLock = null;       // { value: '-2', until: timestamp }
        var restoringFloor = false; // zabezpieczenie przed rekurencją

        function lockFloor(value) {
            floorLock = { value: normalizeFloorValue(value), until: Date.now() + FLOOR_LOCK_MS };
        }

        (function watchFloorOverrides() {
            var ff = document.getElementById('floorFilter');
            if (!ff) return;
            ff.addEventListener('change', function () {
                if (!floorLock || restoringFloor) return;
                if (Date.now() > floorLock.until) { floorLock = null; return; }
                if (normalizeFloorValue(ff.value) === floorLock.value) return;
                log('ktoś nadpisał piętro na', ff.value, '- przywracam', floorLock.value);
                restoringFloor = true;
                setFloorFilter(floorLock.value, true);
                restoringFloor = false;
                highlightFloorButton(floorLock.value);
            });
        })();

        // Przełącza mapę i listę na wskazaną kondygnację.
        function activateFloor(value) {
            log('klik przycisku ->', value);
            lockFloor(value);
            var artboard = goToFloor(value);
            if (!artboard) {
                log('nie znalazłem artboardu dla piętra', value,
                    '— warstwy w mapie:', Array.prototype.map.call(
                        (document.querySelector('.imp-ui-layers-select') || { options: [] }).options,
                        function (o) { return o.text.trim(); }).join(' | '));
            }
            syncingFromButton = true;
            setFloorFilter(value, true);
            syncingFromButton = false;

            // Image Map Pro zgłasza 'artboardChange' asynchronicznie (po animacji
            // przejścia), a wtyczka na to zdarzenie sama przestawia #floorFilter —
            // potrafi więc nadpisać naszą wartość już PO kliknięciu, np. na "all".
            // Dlatego wymuszamy ją jeszcze raz, gdy mapa się uspokoi. Przy zgodnej
            // wartości setFloorFilter bez 'force' nic nie robi, więc nie pętli się.
            [150, 500, 900].forEach(function (ms) {
                setTimeout(function () {
                    syncingFromButton = true;
                    setFloorFilter(value, false);
                    syncingFromButton = false;
                    highlightFloorButton(value);
                }, ms);
            });
        }

        floorButtons = collectFloorButtons();
        log('rozpoznane przyciski pięter:', floorButtons.map(function (b) {
            return b.text + '=' + b.value;
        }).join(', ') || 'BRAK');

        floorButtons.forEach(function (btn) {
            btn.el.style.cursor = 'pointer';

            function onClick(e) {
                // Nasłuch wisi i na <p>, i na <a> w środku — bez tej flagi jedno
                // kliknięcie w link obsłużyłoby się dwa razy.
                if (e.__floorHandled) return;
                e.__floorHandled = true;
                e.preventDefault();
                activateFloor(btn.value);
            }

            // Nasłuch na <a> osobno: gdyby motyw albo Elementor zatrzymał
            // propagację na linku, handler na <p> nigdy by nie dostał zdarzenia.
            var link = btn.el.querySelector('a');
            if (link) link.addEventListener('click', onClick);
            btn.el.addEventListener('click', onClick);
        });

        // ===================================================================
        // KIERUNEK 1:  floorFilter  ->  Image Map Pro
        // ===================================================================
        var floorFilter = document.getElementById('floorFilter');
        if (floorFilter) {
            floorFilter.addEventListener('change', function () {
                if (syncingFromButton || restoringFloor) return;
                // Ręczna zmiana w selekcie znosi blokadę z przycisku.
                floorLock = null;
                var val = floorFilter.value;
                if (val === 'all') {
                    pushToMap(ROOT_ARTBOARD);
                    clearButtonStyles();
                    if (headingEl) headingEl.textContent = originalHeadingText;
                    return;
                }
                // Normalizujemy wartość filtra ("piwnica"/"Piwnica" -> "-1"),
                // bo opcje pięter mogą przyjść z API jako tekst, nie jako liczba.
                goToFloor(val);
                // Brak takiego artboardu w mapie -> zostawiamy widok bez zmian.
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
                clearButtonStyles();
                if (headingEl) headingEl.textContent = originalHeadingText;
                return;
            }
            var floorVal = artboardToFloorValue[current.toUpperCase()];
            if (floorVal === undefined) {
                // Artboard spoza mapy nazw — spróbuj odczytać numer kondygnacji
                // wprost z jego nazwy ("GARAŻ -2" -> "-2").
                var parsed = normalizeFloorValue(current);
                if (parsed !== '' && !isNaN(parseInt(parsed, 10))) {
                    floorVal = parsed;
                }
            }
            if (floorVal !== undefined) {
                setFloorFilter(floorVal);
                highlightFloorButton(floorVal);
            }
        }, 300);

        // ===================================================================
        // Reset filtrów -> widok całego budynku
        // ===================================================================
        var resetBtn = document.getElementById('resetFilters');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                floorLock = null;
                pushToMap(ROOT_ARTBOARD);
                clearButtonStyles();
                if (headingEl) headingEl.textContent = originalHeadingText;
            });
        }
    });
})();
