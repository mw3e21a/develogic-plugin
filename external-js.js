/* Dodaj tutaj swój kod JavaScript.

Jeśli używasz biblioteki jQuery, nie zapomnij umieścić swojego kodu wewnątrz jQuery.ready() w następujący sposób:

jQuery(document).ready(function( $ ){
    // Twój kod tutaj
});

--

Jeśli chcesz połączyć plik JavaScript znajdujący się na innym serwerze (podobnie jak
<script src="https://example.com/your-js-file.js"></script>), skorzystaj ze
strony „Dodaj kod HTML", ponieważ jest to kod HTML łączący plik JavaScript.

Koniec komentarza */

jQuery(document).ready(function($) {
    var buttons = [
        '#piwnica-i',
        '#miejsca-postojowe-i',
        '#parter-i',
        '#pietro-i-1',
        '#pietro-i-2',
        '#pietro-i-3',
        '#pietro-i-4'
    ];

    var floorMap = {
        '#piwnica-i': '-1',
        '#miejsca-postojowe-i': 'all',
        '#parter-i': '0',
        '#pietro-i-1': '1',
        '#pietro-i-2': '2',
        '#pietro-i-3': '3',
        '#pietro-i-4': '4'
    };

    var localTypeMap = {
        '#miejsca-postojowe-i': 'Miejsce postojowe'
    };

    var artboardToSelector = {
        'Piwnica': '#piwnica-i',
        'Miejsca postojowe': '#miejsca-postojowe-i',
        'Parter': '#parter-i',
        'Pietro 1': '#pietro-i-1',
        'Pietro 2': '#pietro-i-2',
        'Pietro 3': '#pietro-i-3',
        'Pietro 4': '#pietro-i-4'
    };

    var floorValueToArtboard = {
        '-1': 'Piwnica',
        '0': 'Parter',
        '1': 'Pietro 1',
        '2': 'Pietro 2',
        '3': 'Pietro 3',
        '4': 'Pietro 4'
    };

    var floorValueToSelector = {
        '-1': '#piwnica-i',
        '0': '#parter-i',
        '1': '#pietro-i-1',
        '2': '#pietro-i-2',
        '3': '#pietro-i-3',
        '4': '#pietro-i-4'
    };

    // Map local type to Image Map Pro artboard
    var localTypeToArtboard = {
        'Lokal mieszkalny': 'Parter',
        'Garaż': 'Piwnica',
        'Komórka lokatorska': 'Piwnica',
        'Miejsce postojowe': 'Miejsca postojowe'
    };

    // Flag to prevent artboard sync when change comes from setActiveFloor
    var syncingFromButton = false;

    // Flag to prevent poll from overriding localType when artboard change comes from dropdown
    var syncingFromDropdown = false;

    // Track last artboard seen by poll (declared here so dropdown handler can update it)
    var lastArtboard = '';

    // Store original heading text for restoration
    var originalHeadingText = '';
    var headingEl = document.querySelector('.develogic-apartments-container .title');
    if (headingEl) {
        originalHeadingText = headingEl.textContent.trim();
    }

    var basementHeadingHTML = 'Wybierz garaż lub komórkę lokatorską' +
        '<br><small style="font-size: 0.6em; font-weight: normal; line-height: 1.4; display: block; margin-top: 0.5em;">' +
        'UWAGA! Zakup garażu jedynie łącznie z mieszkaniem ' +
        '(Ceny podane w tabeli dotyczą garażu przynależnego do mieszkania! ' +
        'W przypadku zakupu mieszkania i garażu samodzielnego należy doliczyć ' +
        'do podanej ceny garażu ok. 15%).</small>';

    function updateHeading(isBasement) {
        if (!headingEl) return;
        if (isBasement) {
            headingEl.innerHTML = basementHeadingHTML;
        } else {
            headingEl.textContent = originalHeadingText;
        }
    }

    function clearButtonStyles() {
        buttons.forEach(function(selector) {
            var el = document.querySelector(selector);
            if (!el) return;
            el.style.backgroundColor = '';
            el.style.color = '';
            var link = el.querySelector('a');
            if (link) link.style.color = '';
        });
    }

    // source: 'button' = floor button clicked, 'poll' = Image Map Pro artboard detected
    window.setActiveFloor = function(activeSelector, source) {
        clearButtonStyles();
        var activeEl = document.querySelector(activeSelector);
        if (activeEl) {
            activeEl.style.backgroundColor = '#0066cc';
            activeEl.style.color = 'white';
            var link = activeEl.querySelector('a');
            if (link) link.style.color = 'white';
        }

        var floorValue = floorMap[activeSelector];
        var floorFilter = document.getElementById('floorFilter');
        if (floorFilter && floorValue !== undefined) {
            floorFilter.value = floorValue;
        }

        var localTypeFilter = document.getElementById('localTypeFilter');
        if (localTypeFilter) {
            if (source === 'button') {
                // Button click: set local type based on button mapping
                if (localTypeMap[activeSelector]) {
                    localTypeFilter.value = localTypeMap[activeSelector];
                } else if (activeSelector === '#piwnica-i') {
                    // Piwnica button → show all types
                    localTypeFilter.value = 'all';
                } else {
                    localTypeFilter.value = 'Lokal mieszkalny';
                }
            } else if (source === 'poll') {
                // Artboard detected by poll — don't override localType if the change
                // was triggered by the dropdown (syncingFromDropdown flag)
                if (!syncingFromDropdown) {
                    if (localTypeMap[activeSelector]) {
                        localTypeFilter.value = localTypeMap[activeSelector];
                    } else if (activeSelector === '#piwnica-i') {
                        localTypeFilter.value = 'all';
                    } else {
                        localTypeFilter.value = 'Lokal mieszkalny';
                    }
                }
                // If syncingFromDropdown, leave localType as-is
            } else {
                // Legacy/default behavior
                if (localTypeMap[activeSelector]) {
                    localTypeFilter.value = localTypeMap[activeSelector];
                } else {
                    localTypeFilter.value = 'Lokal mieszkalny';
                }
            }
        }

        // Update heading based on whether we're on basement/parking floor
        var isBasement = (activeSelector === '#piwnica-i');
        updateHeading(isBasement);

        if (floorFilter) {
            syncingFromButton = true;
            floorFilter.dispatchEvent(new Event('change'));
            syncingFromButton = false;
        }
    };

    window.resetFloorButtons = function() {
        clearButtonStyles();

        var floorFilter = document.getElementById('floorFilter');
        if (floorFilter) {
            floorFilter.value = 'all';
        }

        var localTypeFilter = document.getElementById('localTypeFilter');
        if (localTypeFilter) {
            localTypeFilter.value = 'all';
        }

        // Restore original heading
        updateHeading(false);

        var resetBtn = document.getElementById('resetFilters');
        if (resetBtn) {
            syncingFromButton = true;
            resetBtn.click();
            syncingFromButton = false;
        } else if (floorFilter) {
            syncingFromButton = true;
            floorFilter.dispatchEvent(new Event('change'));
            syncingFromButton = false;
        }
    };

    // Sync artboard when floor dropdown changes (only from user interaction)
    var floorFilter = document.getElementById('floorFilter');
    if (floorFilter) {
        floorFilter.addEventListener('change', function() {
            if (syncingFromButton) return;

            var val = floorFilter.value;
            if (val === 'all') {
                clearButtonStyles();
                $.imageMapProGoToFloor('Budynki IJKL', 'Budynek I');
                updateHeading(false);
            } else {
                var artboard = floorValueToArtboard[val];
                var selector = floorValueToSelector[val];
                if (artboard) {
                    $.imageMapProGoToFloor('Budynki IJKL', artboard);
                }
                if (selector) {
                    clearButtonStyles();
                    var el = document.querySelector(selector);
                    if (el) {
                        el.style.backgroundColor = '#0066cc';
                        el.style.color = 'white';
                        var link = el.querySelector('a');
                        if (link) link.style.color = 'white';
                    }
                }
                updateHeading(val === '-1');
            }
        });
    }

    // Sync artboard when local type dropdown changes
    var localTypeFilter = document.getElementById('localTypeFilter');
    if (localTypeFilter) {
        localTypeFilter.addEventListener('change', function() {
            var selectedType = localTypeFilter.value;
            var artboard = localTypeToArtboard[selectedType];

            if (artboard) {
                syncingFromDropdown = true;
                $.imageMapProGoToFloor('Budynki IJKL', artboard);

                // Update lastArtboard so poll doesn't re-trigger when flag clears
                lastArtboard = artboard;

                // Also highlight the corresponding button and set floor filter
                var selectorForArtboard = artboardToSelector[artboard];
                if (selectorForArtboard) {
                    clearButtonStyles();
                    var el = document.querySelector(selectorForArtboard);
                    if (el) {
                        el.style.backgroundColor = '#0066cc';
                        el.style.color = 'white';
                        var link = el.querySelector('a');
                        if (link) link.style.color = 'white';
                    }

                    // Update floor filter to match
                    var floorValue = floorMap[selectorForArtboard];
                    var floorFilter = document.getElementById('floorFilter');
                    if (floorFilter && floorValue !== undefined) {
                        floorFilter.value = floorValue;
                    }
                }

                // Update heading for basement types
                var isBasement = (artboard === 'Piwnica');
                updateHeading(isBasement);

                // Clear the flag after a delay — poll is fully skipped during this time
                // and lastArtboard is already updated, so poll won't re-trigger
                setTimeout(function() {
                    syncingFromDropdown = false;
                }, 1000);
            }
        });
    }

    // Poll Image Map Pro layer select for changes
    setInterval(function() {
        // Skip poll entirely when artboard change was triggered by our dropdown handler
        if (syncingFromDropdown) return;

        var layerSelect = document.querySelector('.imp-ui-layers-select');
        if (!layerSelect) return;
        var selectedText = layerSelect.options[layerSelect.selectedIndex].text;
        if (selectedText === lastArtboard) return;
        lastArtboard = selectedText;

        if (selectedText === 'Budynek I') {
            resetFloorButtons();
        } else {
            var selector = artboardToSelector[selectedText];
            if (selector) {
                setActiveFloor(selector, 'poll');
            }
        }
    }, 300);

    document.querySelector('#piwnica-i').addEventListener('click', function(e) {
        e.preventDefault();
        $.imageMapProGoToFloor('Budynki IJKL', 'Piwnica');
        setActiveFloor('#piwnica-i', 'button');
    });
    document.querySelector('#miejsca-postojowe-i').addEventListener('click', function(e) {
        e.preventDefault();
        $.imageMapProGoToFloor('Budynki IJKL', 'Miejsca postojowe');
        setActiveFloor('#miejsca-postojowe-i', 'button');
    });
    document.querySelector('#parter-i').addEventListener('click', function(e) {
        e.preventDefault();
        $.imageMapProGoToFloor('Budynki IJKL', 'Parter');
        setActiveFloor('#parter-i', 'button');
    });
    document.querySelector('#pietro-i-1').addEventListener('click', function(e) {
        e.preventDefault();
        $.imageMapProGoToFloor('Budynki IJKL', 'Pietro 1');
        setActiveFloor('#pietro-i-1', 'button');
    });
    document.querySelector('#pietro-i-2').addEventListener('click', function(e) {
        e.preventDefault();
        $.imageMapProGoToFloor('Budynki IJKL', 'Pietro 2');
        setActiveFloor('#pietro-i-2', 'button');
    });
    document.querySelector('#pietro-i-3').addEventListener('click', function(e) {
        e.preventDefault();
        $.imageMapProGoToFloor('Budynki IJKL', 'Pietro 3');
        setActiveFloor('#pietro-i-3', 'button');
    });
    document.querySelector('#pietro-i-4').addEventListener('click', function(e) {
        e.preventDefault();
        $.imageMapProGoToFloor('Budynki IJKL', 'Pietro 4');
        setActiveFloor('#pietro-i-4', 'button');
    });
});
