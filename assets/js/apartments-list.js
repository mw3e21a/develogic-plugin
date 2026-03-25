/**
 * Apartments List JavaScript
 * Nowy layout zgodny z apartment-list.html i apartment-detail.html
 * @package Develogic
 */

(function() {
    'use strict';
    
    // Monitor URL changes made by pushState/replaceState
    const originalPushState = history.pushState;
    const originalReplaceState = history.replaceState;
    
    history.pushState = function() {
        originalPushState.apply(history, arguments);
        window.dispatchEvent(new Event('urlchange'));
    };
    
    history.replaceState = function() {
        originalReplaceState.apply(history, arguments);
        window.dispatchEvent(new Event('urlchange'));
    };
    
    // Wait for DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        setupStickyOffset();
        setupSorting();
        setupFiltering();
        setupFavorites();
        setupWatched();
        setupEmailButtons();
        setupApartmentClicks();
        setup3dTooltip();
        setupModal();
        setupFavoritesViewToggle();
        setupInquiryForm();
        setupShareButtons();
        updateFavoritesCount();
        updateWatchedCount();
        checkSharedFavorites();
        setupWizard();
        setupImageMapProArtboardLogging();
        
        // Apply URL filters first
        applyUrlFilters();

        // Apply filters on page load to respect default localType selection
        applyFilters();

        // Initial sort: available first, then floor asc, then number asc
        performInitialSort();
        
        // Scroll to specific apartment if specified in URL
        scrollToApartmentFromUrl();
        
        // Listen for URL changes (browser back/forward)
        window.addEventListener('popstate', function() {
            applyUrlFilters();
            applyFilters();
        });
        
        // Listen for URL changes (pushState/replaceState)
        window.addEventListener('urlchange', function() {
            applyUrlFilters();
            applyFilters();
            scrollToApartmentFromUrl();
        });
        
        // Hide loading spinner and show content
        hideLoadingSpinner();
    }
    
    // ===========================
    // Loading Spinner
    // ===========================
    function hideLoadingSpinner() {
        const loadingOverlay = document.querySelector('.develogic-loading-overlay');
        const container = document.querySelector('.develogic-apartments-container');
        
        // Wait for images and styles to load
        window.addEventListener('load', function() {
            if (loadingOverlay) {
                loadingOverlay.classList.add('hidden');
                // Remove from DOM after animation
                setTimeout(function() {
                    if (loadingOverlay.parentNode) {
                        loadingOverlay.remove();
                    }
                }, 300);
            }
            
            if (container) {
                container.classList.add('loaded');
            }
        });
        
        // Fallback: hide after 2 seconds even if images aren't fully loaded
        setTimeout(function() {
            if (loadingOverlay && !loadingOverlay.classList.contains('hidden')) {
                loadingOverlay.classList.add('hidden');
                setTimeout(function() {
                    if (loadingOverlay.parentNode) {
                        loadingOverlay.remove();
                    }
                }, 300);
            }
            
            if (container && !container.classList.contains('loaded')) {
                container.classList.add('loaded');
            }
        }, 2000);
    }
    
    // ===========================
    // Sticky: fix overflow:hidden on ancestors (breaks position:sticky)
    // Must be module-level so modal close handlers can call it
    // ===========================
    function fixStickyAncestors() {
        document.querySelectorAll('.apartment-list-header, .apartment-list-mobile-header').forEach(function(el) {
            var parent = el.parentElement;
            while (parent && parent !== document.documentElement) {
                var style = window.getComputedStyle(parent);
                if (style.overflow === 'hidden' || style.overflowY === 'hidden') {
                    parent.style.overflow = 'visible';
                }
                parent = parent.parentElement;
            }
        });
    }

    // ===========================
    // Sticky Offset for sorting bar
    // ===========================
    function setupStickyOffset() {
        function getStickyHeaderHeight() {
            var height = 0;

            // WP admin bar
            var adminBar = document.getElementById('wpadminbar');
            if (adminBar) {
                height += adminBar.offsetHeight;
            }

            // Site header: pick whichever is visible (desktop hidden on mobile and vice versa)
            var headerDesktop = document.querySelector('.header-desktop');
            var headerMobile = document.querySelector('.header-mobile');
            var siteHeader = (headerDesktop && headerDesktop.offsetHeight > 0) ? headerDesktop
                           : (headerMobile && headerMobile.offsetHeight > 0) ? headerMobile
                           : null;
            if (siteHeader) {
                height += siteHeader.offsetHeight;
            } else {
                // Fallback: scan for sticky/fixed elements near top
                var candidates = document.querySelectorAll(
                    '.is-fixed, .is-stuck, [data-elementor-type="header"], .site-header, #masthead'
                );
                var maxBottom = 0;
                candidates.forEach(function(el) {
                    if (el.offsetHeight > 0) {
                        maxBottom = Math.max(maxBottom, el.offsetTop + el.offsetHeight);
                    }
                });
                height = Math.max(height, maxBottom);
            }

            return height;
        }

        function updateStickyTop() {
            var h = getStickyHeaderHeight();
            document.documentElement.style.setProperty('--develogic-sticky-top', h + 'px');
        }

        updateStickyTop();
        fixStickyAncestors();
        window.addEventListener('resize', updateStickyTop);
        window.addEventListener('load', function() {
            updateStickyTop();
            fixStickyAncestors();
        });
    }

    // ===========================
    // Image Map Pro Artboard Logging
    // ===========================
    function setupImageMapProArtboardLogging() {
        // Store original localType options for restoration
        let originalLocalTypeOptions = null;
        
        // Wait for Image Map Pro API to be available
        function initImageMapProLogging() {
            if (typeof ImageMapPro !== 'undefined' && ImageMapPro.subscribe) {
                // Subscribe to Image Map Pro events
                ImageMapPro.subscribe(function(action) {
                    if (action.type === 'artboardChange') {
                        const artboardName = action.payload && action.payload.artboard ? action.payload.artboard : 'unknown';
                        console.log('Image Map Pro - Artboard changed:', artboardName);
                        
                        const localTypeFilter = document.getElementById('localTypeFilter');
                        const floorFilter = document.getElementById('floorFilter');
                        
                        // Store original options on first run
                        if (localTypeFilter && !originalLocalTypeOptions) {
                            originalLocalTypeOptions = Array.from(localTypeFilter.options).map(opt => ({
                                value: opt.value,
                                text: opt.text,
                                selected: opt.selected
                            }));
                        }
                        
                        // Check if artboard is "Budynek E" (or default-id) vs specific floor
                        const isBuildingView = artboardName === 'Budynek E' || artboardName === 'default-id';
                        
                        if (localTypeFilter) {
                            if (isBuildingView) {
                                // Restore all options when on building view
                                if (originalLocalTypeOptions) {
                                    const currentValue = localTypeFilter.value;
                                    localTypeFilter.innerHTML = '';
                                    
                                    originalLocalTypeOptions.forEach(opt => {
                                        const option = document.createElement('option');
                                        option.value = opt.value;
                                        option.textContent = opt.text;
                                        localTypeFilter.appendChild(option);
                                    });
                                    
                                    // Restore previous selection if still available
                                    const availableValues = Array.from(localTypeFilter.options).map(opt => opt.value);
                                    if (availableValues.includes(currentValue)) {
                                        localTypeFilter.value = currentValue;
                                    }
                                }
                            } else {
                                // On floor view - limit to "Lokal mieszkalny" only
                                const currentValue = localTypeFilter.value;
                                const hasLokalMieszkalny = Array.from(localTypeFilter.options).some(opt => opt.value === 'Lokal mieszkalny');
                                
                                if (hasLokalMieszkalny) {
                                    // Clear and add only "Lokal mieszkalny"
                                    localTypeFilter.innerHTML = '';
                                    const option = document.createElement('option');
                                    option.value = 'Lokal mieszkalny';
                                    option.textContent = 'Lokal mieszkalny';
                                    option.selected = true;
                                    localTypeFilter.appendChild(option);
                                    
                                    console.log('Image Map Pro - Floor view: Limited localType to "Lokal mieszkalny"');
                                }
                            }
                        }
                        
                        // Try to extract floor value from artboard name and update filter
                        if (floorFilter && artboardName !== 'unknown' && !isBuildingView) {
                            // Extract floor value from artboard name (e.g., "PIĘTRO I" -> "I piętro" or "1")
                            // Try to normalize the artboard name to a floor value
                            const normalizedFloor = parseFloorToNumber(artboardName);
                            
                            if (normalizedFloor !== null) {
                                const normalizedValue = String(normalizedFloor);
                                const option = floorFilter.querySelector(`option[value="${normalizedValue}"]`);
                                
                                if (option) {
                                    floorFilter.value = normalizedValue;
                                    // Trigger filter update
                                    applyFilters();
                                    console.log('Image Map Pro - Artboard changed:', artboardName, '-> Floor filter:', normalizedValue);
                                } else {
                                    console.log('Image Map Pro - Floor value', normalizedValue, 'not available in filter options. Setting to all floors.');
                                    floorFilter.value = 'all';
                                    applyFilters();
                                }
                            }
                        } else if (isBuildingView) {
                            // Reset to all floors when on building view
                            if (floorFilter) {
                                floorFilter.value = 'all';
                                applyFilters();
                            }
                        }
                    }
                });
            } else {
                // Retry after a short delay if Image Map Pro is not yet loaded
                setTimeout(initImageMapProLogging, 500);
            }
        }
        
        // Start initialization
        initImageMapProLogging();
    }
    
    // ===========================
    // Sorting functionality
    // ===========================
    function setupSorting() {
        const sortOptions = document.querySelectorAll('.header-sort');
        let currentSort = 'data-floor';
        let currentDirection = 'asc';

        sortOptions.forEach(option => {
            option.addEventListener('click', function(e) {
                e.stopPropagation();
                const sortAttr = this.getAttribute('data-sort');

                // Toggle direction if same sort
                if (sortAttr === currentSort) {
                    currentDirection = (currentDirection === 'asc') ? 'desc' : 'asc';
                } else {
                    currentDirection = 'asc';
                    currentSort = sortAttr;
                }

                // Remove active class from all options
                sortOptions.forEach(opt => {
                    opt.classList.remove('active');
                    opt.removeAttribute('data-direction');
                });

                // Add active class and direction to clicked option
                this.classList.add('active');
                this.setAttribute('data-direction', currentDirection);

                performSort(sortAttr, currentDirection);
            });
        });
    }
    
    function performSort(sortAttr, sortDir) {
        const apartmentList = document.querySelector('.apartment-list');
        if (!apartmentList) return;
        
        const items = Array.from(apartmentList.querySelectorAll('.apartment-item'));
        
        items.sort(function(a, b) {
            const aRaw = a.getAttribute(sortAttr) || '';
            const bRaw = b.getAttribute(sortAttr) || '';
            const aVal = parseInt(aRaw);
            const bVal = parseInt(bRaw);

            var result;
            if (isNaN(aVal) || isNaN(bVal)) {
                result = aRaw.localeCompare(bRaw, undefined, { numeric: true, sensitivity: 'base' });
            } else {
                result = aVal - bVal;
            }

            return sortDir === 'asc' ? result : -result;
        });
        
        // Re-append sorted items
        items.forEach(item => apartmentList.appendChild(item));
    }

    function performInitialSort() {
        var apartmentList = document.querySelector('.apartment-list');
        if (!apartmentList) return;

        var items = Array.from(apartmentList.querySelectorAll('.apartment-item'));

        var typeOrder = { 'Lokal mieszkalny': 0, 'Komórka lokatorska': 1, 'Miejsce postojowe': 2, 'Garaż': 3 };
        var statusOrder = { 'available': 0, 'reserved': 1, 'sold': 2 };

        items.sort(function(a, b) {
            // 1. Local type: apartments first, then storage, parking, garages
            var aType = typeOrder[a.getAttribute('data-local-type')] ?? 4;
            var bType = typeOrder[b.getAttribute('data-local-type')] ?? 4;
            if (aType !== bType) return aType - bType;

            // 2. Status: available first, then reserved, then sold
            var aStatus = statusOrder[a.getAttribute('data-status-class')] ?? 3;
            var bStatus = statusOrder[b.getAttribute('data-status-class')] ?? 3;
            if (aStatus !== bStatus) return aStatus - bStatus;

            // 3. Number ascending (natural sort: M1, M2, ... M10, M11, M12)
            var aNum = a.getAttribute('data-number') || '';
            var bNum = b.getAttribute('data-number') || '';
            return aNum.localeCompare(bNum, undefined, { numeric: true, sensitivity: 'base' });
        });

        items.forEach(function(item) { apartmentList.appendChild(item); });
    }

    // ===========================
    // URL Filters functionality
    // ===========================

    var highlightTimer = null;

    /**
     * Scroll to specific apartment from URL parameter
     */
    function scrollToApartmentFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        const apartmentNumber = urlParams.get('mieszkanie') || urlParams.get('m');
        
        if (!apartmentNumber) {
            return;
        }
        
        // Find apartment by number
        const apartmentItems = document.querySelectorAll('.apartment-item');
        let targetApartment = null;
        
        apartmentItems.forEach(item => {
            const dataNumber = item.getAttribute('data-number');
            if (dataNumber && dataNumber.trim().toUpperCase() === apartmentNumber.toUpperCase()) {
                targetApartment = item;
            }
        });
        
        if (!targetApartment) {
            console.log('Apartment not found:', apartmentNumber);
            return;
        }
        
        // Check if apartment is visible (not filtered out)
        if (targetApartment.style.display === 'none') {
            console.log('Apartment is filtered out:', apartmentNumber);
            return;
        }
        
        // Clear any existing highlight
        document.querySelectorAll('.apartment-item.apartment-highlight').forEach(function(el) {
            el.classList.remove('apartment-highlight');
        });
        if (highlightTimer) {
            clearTimeout(highlightTimer);
            highlightTimer = null;
        }

        // Scroll to apartment with smooth behavior
        setTimeout(function() {
            targetApartment.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            // Add highlight class
            targetApartment.classList.add('apartment-highlight');

            // Remove highlight after 15 seconds
            highlightTimer = setTimeout(function() {
                targetApartment.classList.remove('apartment-highlight');
                highlightTimer = null;
            }, 15000);
        }, 500); // Delay to ensure filters are applied first
    }
    
    /**
     * Helper function to update URL params and apply filters
     * Usage: updateUrlFilters({ pietro: 3, pokoje: 2 })
     */
    window.develogicUpdateFilters = function(params) {
        const url = new URL(window.location.href);
        
        // Clear existing filter params or update them
        Object.keys(params).forEach(key => {
            if (params[key] === null || params[key] === '' || params[key] === 'all') {
                url.searchParams.delete(key);
            } else {
                url.searchParams.set(key, params[key]);
            }
        });
        
        // Update URL without reload
        window.history.pushState({}, '', url.toString());
        
        // Trigger filter application
        applyUrlFilters();
        applyFilters();
    };
    
    /**
     * Helper function to scroll to specific apartment
     * Usage: scrollToApartment('M18')
     */
    window.develogicScrollToApartment = function(apartmentNumber) {
        // Close wizard modal if it's open
        var wizardModal = document.getElementById('wizardModal');
        if (wizardModal && wizardModal.classList.contains('visible')) {
            closeWizardModal();
        }

        // Switch to "all" view if currently in favorites or watched view
        var apartmentList = document.querySelector('.apartment-list');
        if (apartmentList && (apartmentList.classList.contains('hide-favorites') || apartmentList.classList.contains('hide-watched'))) {
            switchToAllView();
        }

        // Reset filters so the target apartment is not hidden by localType/floor/etc.
        resetFilters();
        applyFilters();

        const url = new URL(window.location.href);
        url.searchParams.set('mieszkanie', apartmentNumber);
        window.history.pushState({}, '', url.toString());
        scrollToApartmentFromUrl();
    };
    
    function applyUrlFilters() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Rooms filter (pokoje)
        if (urlParams.has('pokoje')) {
            const rooms = urlParams.get('pokoje');
            const roomChips = document.querySelectorAll('#roomsFilter .filter-chip');
            roomChips.forEach(chip => {
                chip.classList.toggle('active', chip.getAttribute('data-value') === rooms);
            });
        }
        
        // Floor filter (pietro)
        if (urlParams.has('pietro')) {
            const floor = urlParams.get('pietro');
            const floorFilter = document.getElementById('floorFilter');
            if (floorFilter) {
                // Normalize floor value (handles "I piętro" -> "1", "parter" -> "0", etc.)
                const normalizedFloor = parseFloorToNumber(floor);
                
                if (normalizedFloor !== null) {
                    // Try to find option with normalized numeric value
                    const normalizedValue = String(normalizedFloor);
                    const option = floorFilter.querySelector(`option[value="${normalizedValue}"]`);
                    
                    if (option) {
                        floorFilter.value = normalizedValue;
                    } else {
                        // If normalized value not found, try original value
                        const originalOption = floorFilter.querySelector(`option[value="${floor}"]`);
                        if (originalOption) {
                            floorFilter.value = floor;
                        } else {
                            console.log('Image Map Pro - Floor value', floor, 'not available in filter options. Setting to all floors.');
                            floorFilter.value = 'all';
                        }
                    }
                } else {
                    // If can't parse, try original value
                    const originalOption = floorFilter.querySelector(`option[value="${floor}"]`);
                    if (originalOption) {
                        floorFilter.value = floor;
                    } else {
                        console.log('Image Map Pro - Floor value', floor, 'not available in filter options. Setting to all floors.');
                        floorFilter.value = 'all';
                    }
                }
            }
        }
        
        // Building filter (budynek)
        if (urlParams.has('budynek')) {
            const building = urlParams.get('budynek');
            const buildingFilter = document.getElementById('buildingFilter');
            if (buildingFilter) {
                buildingFilter.value = building;
            }
        }
        
        // Local type filter (typ_lokalu)
        if (urlParams.has('typ_lokalu')) {
            const localType = urlParams.get('typ_lokalu');
            const localTypeFilter = document.getElementById('localTypeFilter');
            if (localTypeFilter) {
                localTypeFilter.value = localType;
            }
        }
        
        // Area min (metraz_od)
        if (urlParams.has('metraz_od')) {
            const areaMin = urlParams.get('metraz_od');
            const areaMinInput = document.getElementById('areaMin');
            if (areaMinInput) {
                areaMinInput.value = areaMin;
            }
        }
        
        // Area max (metraz_do)
        if (urlParams.has('metraz_do')) {
            const areaMax = urlParams.get('metraz_do');
            const areaMaxInput = document.getElementById('areaMax');
            if (areaMaxInput) {
                areaMaxInput.value = areaMax;
            }
        }
        
        // Price min (cena_od)
        if (urlParams.has('cena_od')) {
            const priceMin = urlParams.get('cena_od');
            const priceMinInput = document.getElementById('priceMin');
            if (priceMinInput) {
                priceMinInput.value = priceMin;
            }
        }
        
        // Price max (cena_do)
        if (urlParams.has('cena_do')) {
            const priceMax = urlParams.get('cena_do');
            const priceMaxInput = document.getElementById('priceMax');
            if (priceMaxInput) {
                priceMaxInput.value = priceMax;
            }
        }
        
        // Additional options from URL - match by feature name
        // URL param: extra=FeatureName (can be repeated)
        const extraParams = urlParams.getAll('extra');
        if (extraParams.length > 0) {
            extraParams.forEach(extraValue => {
                const filter = document.querySelector(`.filter-extras input[data-feature-name="${extraValue}"]`);
                if (filter) {
                    filter.checked = true;
                }
            });
        }
    }
    
    // ===========================
    // Filtering functionality
    // ===========================
    function setupFiltering() {
        // Room filter chips
        const roomChips = document.querySelectorAll('#roomsFilter .filter-chip');
        roomChips.forEach(chip => {
            chip.addEventListener('click', function() {
                roomChips.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                applyFilters();
            });
        });
        
        // Local type filter
        const localTypeFilter = document.getElementById('localTypeFilter');
        if (localTypeFilter) {
            localTypeFilter.addEventListener('change', function() {
                autoSelectFloorForKLPG();
                applyFilters();
            });
        }
        
        // Building filter
        const buildingFilter = document.getElementById('buildingFilter');
        if (buildingFilter) {
            buildingFilter.addEventListener('change', function() {
                updateFloorOptions();
                applyFilters();
            });
        }
        
        // Floor filter
        const floorFilter = document.getElementById('floorFilter');
        if (floorFilter) {
            floorFilter.addEventListener('change', function() {
                resetLocalTypeIfEmptyOnFloor();
                applyFilters();
            });
        }
        
        // Initialize dynamic floor filtering if enabled
        updateFloorOptions();
        
        // Ensure floor options are available for KL/PG/Garaż on initial load
        autoSelectFloorForKLPG();
        
        // Area range filters (select dropdowns)
        const areaMin = document.getElementById('areaMin');
        const areaMax = document.getElementById('areaMax');
        if (areaMin) areaMin.addEventListener('change', applyFilters);
        if (areaMax) areaMax.addEventListener('change', applyFilters);
        
        // Price range filters (select dropdowns)
        const priceMin = document.getElementById('priceMin');
        const priceMax = document.getElementById('priceMax');
        if (priceMin) priceMin.addEventListener('change', applyFilters);
        if (priceMax) priceMax.addEventListener('change', applyFilters);
        
        // Additional options checkboxes - feature names from shortcode
        const additionalOptionFilters = document.querySelectorAll('.filter-extras input[type="checkbox"][data-feature-name]');
        additionalOptionFilters.forEach(filter => {
            filter.addEventListener('change', applyFilters);
        });
        
        // Reset button
        const resetBtn = document.getElementById('resetFilters');
        if (resetBtn) {
            resetBtn.addEventListener('click', resetFilters);
        }

        // Check promotions button - toggles promo filter for current building
        const checkPromotionsBtn = document.getElementById('checkPromotionsBtn');
        if (checkPromotionsBtn) {
            checkPromotionsBtn.addEventListener('click', function() {
                window.location.href = '/dom-godny-polecenia/';
            });
        }
        
        // Show more filters button (mobile)
        const showMoreBtn = document.getElementById('showMoreFilters');
        if (showMoreBtn) {
            showMoreBtn.addEventListener('click', function() {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                const hiddenFilters = document.querySelectorAll('.filter-mobile-hidden');
                const showMoreText = this.querySelector('.show-more-text');
                const showLessText = this.querySelector('.show-less-text');
                const arrowIcon = this.querySelector('.filter-arrow-icon');
                
                if (isExpanded) {
                    // Hide filters
                    hiddenFilters.forEach(filter => {
                        filter.classList.remove('filter-mobile-visible');
                    });
                    this.setAttribute('aria-expanded', 'false');
                    if (showMoreText) showMoreText.style.display = 'inline';
                    if (showLessText) showLessText.style.display = 'none';
                    if (arrowIcon) arrowIcon.style.transform = 'rotate(0deg)';
                } else {
                    // Show filters
                    hiddenFilters.forEach(filter => {
                        filter.classList.add('filter-mobile-visible');
                    });
                    this.setAttribute('aria-expanded', 'true');
                    if (showMoreText) showMoreText.style.display = 'none';
                    if (showLessText) showLessText.style.display = 'inline';
                    if (arrowIcon) arrowIcon.style.transform = 'rotate(180deg)';
                }
            });
        }
    }
    
    /**
     * When the user changes the floor filter, check if any apartments of the
     * currently selected local type exist on that floor. If not, reset the
     * local type filter to "all" so the user doesn't see "no results".
     */
    function resetLocalTypeIfEmptyOnFloor() {
        const localTypeFilter = document.getElementById('localTypeFilter');
        const floorFilter = document.getElementById('floorFilter');
        if (!localTypeFilter || !floorFilter) return;

        const selectedLocalType = localTypeFilter.value;
        const selectedFloor = floorFilter.value;

        // Nothing to reset if either filter is already "all"
        if (selectedLocalType === 'all' || selectedFloor === 'all') return;

        const selectedFloorNum = parseFloorToNumber(selectedFloor);
        if (selectedFloorNum === null) return;

        // Also respect the current building filter
        const selectedBuilding = document.getElementById('buildingFilter')?.value || 'all';

        const apartmentItems = document.querySelectorAll('.apartment-item');
        const hasMatch = Array.from(apartmentItems).some(item => {
            const itemLocalType = item.getAttribute('data-local-type') || '';
            if (itemLocalType !== selectedLocalType) return false;

            const itemFloorNum = parseFloorToNumber(item.getAttribute('data-floor-number'));
            if (itemFloorNum !== selectedFloorNum) return false;

            if (selectedBuilding !== 'all') {
                const itemBuilding = item.getAttribute('data-building') || '';
                if (itemBuilding && itemBuilding !== selectedBuilding) return false;
            }

            return true;
        });

        if (!hasMatch) {
            localTypeFilter.value = 'all';
        }
    }

    /**
     * Auto-select floor when KL/PG/Garaż is selected and only one floor exists
     * Reset to "all" for other local types
     * Ensures floor options are available in the select
     */
    function autoSelectFloorForKLPG() {
        const container = document.querySelector('.develogic-apartments-container');
        const localTypeFilter = document.getElementById('localTypeFilter');
        const floorFilter = document.getElementById('floorFilter');
        
        if (!container || !localTypeFilter || !floorFilter) {
            return;
        }
        
        const selectedLocalType = localTypeFilter.value;
        
        // Types that should have auto-selected floor
        const typesWithAutoFloor = ['Komórka lokatorska', 'Garaż'];
        
        // Check if floor filter has a default value from shortcode
        const hasDefaultFloor = container.getAttribute('data-default-floor');
        
        // If user selects "Lokal mieszkalny" or "all", reset to "Wszystkie piętra"
        // But only if there's no default floor from shortcode (in which case we preserve it)
        if (!typesWithAutoFloor.includes(selectedLocalType) && !hasDefaultFloor) {
            floorFilter.value = 'all';
            return;
        }
        
        // Remember the current floor value (could be from shortcode or previous selection)
        const currentFloorValue = floorFilter.value;
        
        // Get KL/PG floors
        const klPgFloorsStr = container.getAttribute('data-kl-pg-floors');
        if (!klPgFloorsStr) {
            // If no KL/PG floors data, reset to "all" for non-KL/PG types
            if (!typesWithAutoFloor.includes(selectedLocalType) && !hasDefaultFloor) {
                floorFilter.value = 'all';
            }
            return;
        }
        
        let klPgFloors = [];
        try {
            klPgFloors = JSON.parse(klPgFloorsStr);
        } catch (e) {
            return;
        }
        
        if (!klPgFloors || !Array.isArray(klPgFloors) || klPgFloors.length === 0) {
            return;
        }
        
        // Always ensure KL/PG floor options exist in the select
        // This is crucial when dynamic floor filtering is disabled
        const existingFloorValues = Array.from(floorFilter.options).map(opt => opt.value);
        let optionsAdded = false;
        
        klPgFloors.forEach(floor => {
            const floorStr = String(floor);
            if (!existingFloorValues.includes(floorStr)) {
                const option = document.createElement('option');
                option.value = floorStr;
                option.textContent = formatFloor(floorStr);
                floorFilter.appendChild(option);
                existingFloorValues.push(floorStr);
                optionsAdded = true;
            }
        });
        
        // Sort options if we added new ones
        if (optionsAdded) {
            const allOption = floorFilter.querySelector('option[value="all"]');
            const otherOptions = Array.from(floorFilter.options).filter(opt => opt.value !== 'all');
            
            // Sort by floor number
            otherOptions.sort((a, b) => {
                const aInt = parseFloorToNumber(a.value) ?? 999;
                const bInt = parseFloorToNumber(b.value) ?? 999;
                return aInt - bInt;
            });
            
            // Rebuild select with sorted options, preserving current selection
            const savedValue = floorFilter.value;
            floorFilter.innerHTML = '';
            if (allOption) {
                floorFilter.appendChild(allOption);
            }
            otherOptions.forEach(opt => floorFilter.appendChild(opt));
            
            // Restore selection if it's still available
            const availableValues = Array.from(floorFilter.options).map(opt => opt.value);
            if (availableValues.includes(savedValue)) {
                floorFilter.value = savedValue;
            }
        }
        
        // Auto-select floor when KL/PG/Garaż type is selected
        // Always switch to the correct floor for these types (they only exist on specific floors)
        if (typesWithAutoFloor.includes(selectedLocalType)) {
            if (klPgFloors.length === 1) {
                const targetFloor = String(klPgFloors[0]);
                const floorOption = Array.from(floorFilter.options).find(opt => opt.value === targetFloor);
                if (floorOption) {
                    floorFilter.value = targetFloor;
                }
            } else if (klPgFloors.length > 1) {
                // Multiple KL/PG floors — check if current floor is one of them, if not reset to 'all'
                const currentInKlPg = klPgFloors.some(f => String(f) === currentFloorValue);
                if (!currentInKlPg) {
                    floorFilter.value = 'all';
                }
            }
        }
    }
    
    /**
     * Update floor filter options based on selected building
     * Only works for "Lokal mieszkalny" type
     */
    function updateFloorOptions() {
        const container = document.querySelector('.develogic-apartments-container');
        const floorFilter = document.getElementById('floorFilter');
        const buildingFilter = document.getElementById('buildingFilter');
        
        if (!container || !floorFilter || !buildingFilter) {
            return;
        }
        
        // Check if dynamic floor filtering is enabled
        const enableDynamicFloors = container.getAttribute('data-enable-dynamic-floors') === 'true';
        if (!enableDynamicFloors) {
            return;
        }
        
        // Get building -> floors map
        const buildingFloorsMapStr = container.getAttribute('data-building-floors-map');
        if (!buildingFloorsMapStr) {
            return;
        }
        
        let buildingFloorsMap;
        try {
            buildingFloorsMap = JSON.parse(buildingFloorsMapStr);
        } catch (e) {
            console.error('Error parsing building floors map:', e);
            return;
        }
        
        // Get KL/PG floors if available
        let klPgFloors = [];
        const klPgFloorsStr = container.getAttribute('data-kl-pg-floors');
        if (klPgFloorsStr) {
            try {
                klPgFloors = JSON.parse(klPgFloorsStr);
            } catch (e) {
                console.error('Error parsing kl_pg_floors:', e);
            }
        }
        
        // Get selected building
        const selectedBuilding = buildingFilter.value;
        
        // Save current floor selection
        const currentFloor = floorFilter.value;
        
        // Clear existing options (except "all")
        const allOption = floorFilter.querySelector('option[value="all"]');
        floorFilter.innerHTML = '';
        if (allOption) {
            floorFilter.appendChild(allOption);
        } else {
            // Create "all" option if it doesn't exist
            const allOpt = document.createElement('option');
            allOpt.value = 'all';
            allOpt.textContent = 'Wszystkie piętra';
            floorFilter.appendChild(allOpt);
        }
        
        // If "all" buildings selected, show all unique floors from all buildings
        if (selectedBuilding === 'all' || !selectedBuilding) {
            // Collect all unique floors from all buildings
            const allUniqueFloors = new Set();
            Object.values(buildingFloorsMap).forEach(floors => {
                floors.forEach(floor => allUniqueFloors.add(String(floor)));
            });
            
            // Add KL/PG floors to the set
            if (klPgFloors && Array.isArray(klPgFloors)) {
                klPgFloors.forEach(floor => allUniqueFloors.add(String(floor)));
            }
            
            // Convert to array and sort numerically using parseFloorToNumber for proper handling
            const sortedFloors = Array.from(allUniqueFloors).sort((a, b) => {
                const aInt = parseFloorToNumber(a) ?? 999;
                const bInt = parseFloorToNumber(b) ?? 999;
                return aInt - bInt;
            });
            
            // Add floor options
            sortedFloors.forEach(floor => {
                const option = document.createElement('option');
                option.value = floor;
                option.textContent = formatFloor(floor);
                floorFilter.appendChild(option);
            });
        } else {
            // Get floors for selected building
            const buildingFloors = buildingFloorsMap[selectedBuilding];
            if (buildingFloors && Array.isArray(buildingFloors) && buildingFloors.length > 0) {
                // Sort floors numerically using parseFloorToNumber for proper handling
                const sortedFloors = [...buildingFloors].sort((a, b) => {
                    const aInt = parseFloorToNumber(a) ?? 999;
                    const bInt = parseFloorToNumber(b) ?? 999;
                    return aInt - bInt;
                });
                
                // Add floor options
                sortedFloors.forEach(floor => {
                    const option = document.createElement('option');
                    option.value = floor;
                    option.textContent = formatFloor(floor);
                    floorFilter.appendChild(option);
                });
            } else {
                // If no floors found for building, show all standard floors as fallback
                // Also include KL/PG floors if available
                const allFloorsSet = new Set(['-1', '0', '1', '2', '3', '4']);
                if (klPgFloors && Array.isArray(klPgFloors)) {
                    klPgFloors.forEach(floor => allFloorsSet.add(String(floor)));
                }
                
                const allFloors = Array.from(allFloorsSet).sort((a, b) => {
                    const aInt = parseFloorToNumber(a) ?? 999;
                    const bInt = parseFloorToNumber(b) ?? 999;
                    return aInt - bInt;
                });
                
                allFloors.forEach(floor => {
                    const option = document.createElement('option');
                    option.value = floor;
                    option.textContent = formatFloor(floor);
                    floorFilter.appendChild(option);
                });
            }
        }
        
        // Restore previous selection if it's still available
        const availableFloors = Array.from(floorFilter.options).map(opt => opt.value);
        if (availableFloors.includes(currentFloor)) {
            floorFilter.value = currentFloor;
        } else {
            // If previous selection is not available, select "all"
            floorFilter.value = 'all';
        }
    }
    
    function applyFilters() {
        const apartmentItems = document.querySelectorAll('.apartment-item');
        const container = document.querySelector('.develogic-apartments-container');
        
        // Get filter values
        const selectedRooms = document.querySelector('#roomsFilter .filter-chip.active')?.getAttribute('data-value') || 'all';
        const selectedLocalType = document.getElementById('localTypeFilter')?.value || 'all';
        const selectedBuilding = document.getElementById('buildingFilter')?.value || 'all';
        
        // Floor filter - use data-default-floor if filter is hidden
        let selectedFloor = 'all';
        const floorFilter = document.getElementById('floorFilter');
        if (floorFilter) {
            selectedFloor = floorFilter.value;
        } else if (container) {
            // If floor filter is hidden, use data-default-floor from container
            const defaultFloor = container.getAttribute('data-default-floor');
            if (defaultFloor) {
                selectedFloor = defaultFloor;
            }
        }
        const areaMin = parseFloat(document.getElementById('areaMin')?.value) || 0;
        const areaMax = parseFloat(document.getElementById('areaMax')?.value) || Infinity;
        const priceMin = parseFloat(document.getElementById('priceMin')?.value) || 0;
        const priceMax = parseFloat(document.getElementById('priceMax')?.value) || Infinity;
        // Get all checked additional options (feature names from shortcode)
        const checkedFeatureNames = [];
        const additionalOptionFilters = document.querySelectorAll('.filter-extras input[type="checkbox"][data-feature-name]');
        additionalOptionFilters.forEach(filter => {
            if (filter.checked) {
                checkedFeatureNames.push(filter.getAttribute('data-feature-name'));
            }
        });
        
        let visibleCount = 0;
        
        apartmentItems.forEach(item => {
            let shouldShow = true;
            
            // Room filter
            if (selectedRooms !== 'all') {
                const itemRooms = parseInt(item.getAttribute('data-rooms-value')) || 0;
                if (selectedRooms === '4') {
                    // 4+ rooms
                    shouldShow = shouldShow && itemRooms >= 4;
                } else {
                    shouldShow = shouldShow && itemRooms === parseInt(selectedRooms);
                }
            }
            
            // Local type filter - each type shows only itself (no grouping)
            if (selectedLocalType !== 'all') {
                const itemLocalType = item.getAttribute('data-local-type') || '';
                // Exact match - no grouping, each type is independent
                shouldShow = shouldShow && itemLocalType === selectedLocalType;
            }
            
            // Building filter - apply to all types that have building data
            if (selectedBuilding !== 'all') {
                const itemBuilding = item.getAttribute('data-building') || '';
                // Only filter if item has building data
                if (itemBuilding) {
                    shouldShow = shouldShow && itemBuilding === selectedBuilding;
                }
            }
            
            // Floor filter
            if (selectedFloor !== 'all') {
                const itemFloorAttr = item.getAttribute('data-floor-number');
                const itemFloorNum = parseFloorToNumber(itemFloorAttr);
                const selectedFloorNum = parseFloorToNumber(selectedFloor);
                
                // Compare both as numbers, handling null/empty values
                if (itemFloorNum === null || selectedFloorNum === null) {
                    shouldShow = false;
                } else {
                    shouldShow = shouldShow && itemFloorNum === selectedFloorNum;
                }
            }
            
            // Area filter
            const itemArea = parseFloat(item.getAttribute('data-area-value')) || 0;
            shouldShow = shouldShow && itemArea >= areaMin && itemArea <= areaMax;
            
            // Price filter
            const itemPrice = parseFloat(item.getAttribute('data-price-value')) || 0;
            shouldShow = shouldShow && itemPrice >= priceMin && itemPrice <= priceMax;
            
            // Additional options filters - match feature names against attributes
            if (checkedFeatureNames.length > 0) {
                const attributes = JSON.parse(item.getAttribute('data-attributes') || '[]');

                for (const featureName of checkedFeatureNames) {
                    const featureNorm = normalizeFeatureName(featureName);

                    const hasMatch = attributes.some(attr => {
                        const attrName = typeof attr === 'string' ? attr : (attr.name || '');
                        const attrNorm = normalizeFeatureName(attrName);
                        // Exact match
                        if (attrNorm === featureNorm) return true;
                        // Attribute starts with filter name followed by " (" bracket (e.g. "przeszklony balkon (oranżeria)" matches "przeszklony balkon")
                        if (attrNorm.length > featureNorm.length && attrNorm.startsWith(featureNorm) && attrNorm.substring(featureNorm.length).trimStart().charAt(0) === '(') return true;
                        return false;
                    });

                    if (!hasMatch) {
                        shouldShow = false;
                        break;
                    }
                }
            }
            
            // Apply visibility
            if (shouldShow) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // If localType + floor combination yields no results, reset both filters and re-run
        if (visibleCount === 0 && selectedLocalType !== 'all' && selectedFloor !== 'all') {
            const localTypeFilter = document.getElementById('localTypeFilter');
            const floorFilterEl = document.getElementById('floorFilter');
            if (localTypeFilter) localTypeFilter.value = 'all';
            if (floorFilterEl) floorFilterEl.value = 'all';
            // Re-apply filters after reset (avoid infinite recursion by checking we actually changed something)
            applyFilters();
            return;
        }

        // Show/hide no results message
        updateNoResultsMessage(visibleCount);

        // Update stats counter with filtered count
        updateStatsCounter(visibleCount);

        // Show floor sort option only when multiple different floors are visible
        const floorSortOption = document.querySelector('.header-sort[data-sort="data-floor"]');
        if (floorSortOption) {
            const visibleItems = Array.from(apartmentItems).filter(item => item.style.display !== 'none');
            const uniqueFloors = new Set(visibleItems.map(item => item.getAttribute('data-floor-number')).filter(f => f !== null && f !== ''));
            const show = uniqueFloors.size > 1;
            floorSortOption.style.display = show ? '' : 'none';
            // If floor sort was active and now hidden, switch to next sort
            if (!show && floorSortOption.classList.contains('active')) {
                floorSortOption.classList.remove('active');
                const nextOption = document.querySelector('.header-sort:not([data-sort="data-floor"])');
                if (nextOption) nextOption.classList.add('active');
            }
        }
    }
    
    function resetFilters() {
        // Reset room chips
        const roomChips = document.querySelectorAll('#roomsFilter .filter-chip');
        roomChips.forEach(chip => {
            chip.classList.toggle('active', chip.getAttribute('data-value') === 'all');
        });
        
        // Reset dropdowns
        const localTypeFilter = document.getElementById('localTypeFilter');
        if (localTypeFilter) {
            localTypeFilter.value = 'all';
        }
        
        const buildingFilter = document.getElementById('buildingFilter');
        if (buildingFilter) buildingFilter.value = 'all';
        
        const floorFilter = document.getElementById('floorFilter');
        if (floorFilter) floorFilter.value = 'all';
        
        // Reset range inputs
        const areaMin = document.getElementById('areaMin');
        const areaMax = document.getElementById('areaMax');
        const priceMin = document.getElementById('priceMin');
        const priceMax = document.getElementById('priceMax');
        if (areaMin) areaMin.value = '';
        if (areaMax) areaMax.value = '';
        if (priceMin) priceMin.value = '';
        if (priceMax) priceMax.value = '';
        
        // Reset additional options checkboxes
        const additionalOptionFilters = document.querySelectorAll('.filter-extras input[type="checkbox"][data-feature-name]');
        additionalOptionFilters.forEach(filter => {
            filter.checked = false;
        });
        
        // Apply filters (showing all)
        applyFilters();
    }
    
    function updateNoResultsMessage(visibleCount) {
        const apartmentList = document.querySelector('.apartment-list');
        if (!apartmentList) return;
        
        let noResultsMsg = apartmentList.querySelector('.no-results-filter');
        
        if (visibleCount === 0) {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('div');
                noResultsMsg.className = 'no-results no-results-filter';
                noResultsMsg.innerHTML = '<p>Brak nieruchomości spełniających wybrane kryteria. Spróbuj zmienić filtry.</p>';
                apartmentList.appendChild(noResultsMsg);
            }
            noResultsMsg.style.display = 'block';
        } else {
            if (noResultsMsg) {
                noResultsMsg.style.display = 'none';
            }
        }
    }
    
    function normalizeFeatureName(name) {
        // Fix broken unicode escapes (e.g. "u017c" -> "ż") that may come from API data
        var fixed = name.replace(/u([0-9a-fA-F]{4})/g, function(match, hex) {
            var code = parseInt(hex, 16);
            // Only fix if it's a plausible unicode char (Latin Extended range 0x00C0-0x024F)
            if (code >= 0x00C0 && code <= 0x024F) return String.fromCharCode(code);
            return match;
        });
        // Lowercase, normalize unicode (NFD decompose + remove diacritics), trim
        return fixed.toLowerCase().trim().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\u0142/g, 'l').replace(/\u0141/g, 'L');
    }

    function updateStatsCounter(visibleCount) {
        var statsEl = document.querySelector('.stats');
        if (!statsEl) return;

        // Find the text node that contains "dostępnych"
        var childNodes = statsEl.childNodes;
        for (var i = 0; i < childNodes.length; i++) {
            var node = childNodes[i];
            var text = node.textContent || '';
            if (text.indexOf('dostępnych') !== -1) {
                // Replace the number before "dostępnych"
                node.textContent = text.replace(/\d+\s*dostępnych/, visibleCount + ' dostępnych');
                break;
            }
        }
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // ===========================
    // Favorite toggle functionality
    // ===========================
    function setupFavorites() {
        // List favorites
        document.querySelectorAll('.icon-btn[data-action="favorite"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleFavorite(this);
            });
        });
        
        // Mobile configurator buttons
        document.querySelectorAll('.mobile-configurator-btn[data-action="favorite"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleFavorite(this);
            });
        });

        // Modal favorites
        document.addEventListener('click', function(e) {
            if (e.target.closest('.icon-btn[data-action="favorite-modal"]')) {
                e.preventDefault();
                toggleFavorite(e.target.closest('.icon-btn'));
            }
        });
        
        // Load favorites state
        loadFavoritesState();
    }
    
    function toggleFavorite(btn) {
        const localId = btn.getAttribute('data-local-id');
        if (!localId) return;
        
        const favorites = getFavorites();
        const index = favorites.indexOf(localId);
        const isAdding = index === -1;
        
        if (isAdding) {
            favorites.push(localId);
        } else {
            favorites.splice(index, 1);
        }
        
        saveFavorites(favorites);
        
        // Update all favorite buttons for this local
        document.querySelectorAll('[data-local-id="' + localId + '"][data-action="favorite"], .icon-btn[data-local-id="' + localId + '"][data-action="favorite-modal"]').forEach(b => {
            b.classList.toggle('favorited', isAdding);
            b.classList.toggle('is-favorite', isAdding);
            b.setAttribute('title', isAdding ? 'Usuń z konfiguratora oferty' : 'Dodaj do konfiguratora oferty');
            // Update mobile configurator button text
            var btnText = b.querySelector('.mobile-configurator-btn-text');
            if (btnText) {
                btnText.textContent = isAdding ? 'Usuń z konfiguratora oferty' : 'Dodaj do konfiguratora oferty';
            }
        });
        
        // Update apartment item favorite class
        document.querySelectorAll('.apartment-item').forEach(item => {
            const modalData = item.getAttribute('data-modal');
            if (modalData) {
                try {
                    const data = JSON.parse(modalData);
                    if (String(data.localId) === String(localId)) {
                        item.classList.toggle('is-favorite', isAdding);
                    }
                } catch (e) {
                    console.error('Error parsing modal data:', e);
                }
            }
        });
        
        // Update favorites count
        updateFavoritesCount();
        
        // Check placeholder visibility if in favorites view
        const apartmentList = document.querySelector('.apartment-list');
        if (apartmentList && apartmentList.classList.contains('hide-favorites')) {
            checkAndToggleNoFavoritesPlaceholder();
        }
        
        // Show toast notification when adding to favorites
        if (isAdding) {
            showToast();
            // Trigger wizard flow if active
            handleWizardAfterFavoriteAdd();
        }
    }
    
    function getPageId() {
        const container = document.querySelector('.develogic-apartments-container');
        return container ? (container.getAttribute('data-page-id') || '') : '';
    }

    function getFavoritesKey() {
        return 'develogic_fav_v3_' + getPageId();
    }

    function getWatchedKey() {
        return 'develogic_watch_v3_' + getPageId();
    }

    function getFavorites() {
        const favorites = localStorage.getItem(getFavoritesKey());
        return favorites ? JSON.parse(favorites) : [];
    }

    function saveFavorites(favorites) {
        localStorage.setItem(getFavoritesKey(), JSON.stringify(favorites));
        document.dispatchEvent(new CustomEvent('develogic:favorites_changed'));
    }
    
    function loadFavoritesState() {
        const favorites = getFavorites();
        
        favorites.forEach(localId => {
            document.querySelectorAll('[data-local-id="' + localId + '"][data-action="favorite"], .icon-btn[data-local-id="' + localId + '"][data-action="favorite-modal"]').forEach(btn => {
                btn.classList.add('favorited');
                btn.classList.add('is-favorite');
                btn.setAttribute('title', 'Usuń z konfiguratora oferty');
                var btnText = btn.querySelector('.mobile-configurator-btn-text');
                if (btnText) {
                    btnText.textContent = 'Usuń z konfiguratora oferty';
                }
            });
            
            // Mark apartment items as favorites
            document.querySelectorAll('.apartment-item').forEach(item => {
                const modalData = item.getAttribute('data-modal');
                if (modalData) {
                    try {
                        const data = JSON.parse(modalData);
                        if (String(data.localId) === String(localId)) {
                            item.classList.add('is-favorite');
                        }
                    } catch (e) {
                        console.error('Error parsing modal data:', e);
                    }
                }
            });
        });
    }
    
    // ===========================
    // Watched (Obserwowane) functionality
    // ===========================
    function setupWatched() {
        // List watched buttons
        document.querySelectorAll('.icon-btn[data-action="watched"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleWatched(this);
            });
        });

        // Mobile watched buttons
        document.querySelectorAll('.mobile-watched-btn[data-action="watched"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleWatched(this);
            });
        });

        // Modal watched
        document.addEventListener('click', function(e) {
            if (e.target.closest('.icon-btn[data-action="watched-modal"]')) {
                e.preventDefault();
                toggleWatched(e.target.closest('.icon-btn'));
            }
        });

        // Load watched state
        loadWatchedState();
    }

    function toggleWatched(btn) {
        const localId = btn.getAttribute('data-local-id');
        if (!localId) return;

        const watched = getWatched();
        const index = watched.indexOf(localId);
        const isAdding = index === -1;

        if (isAdding) {
            watched.push(localId);
        } else {
            watched.splice(index, 1);
        }

        saveWatched(watched);

        // Update all watched buttons for this local
        document.querySelectorAll('[data-local-id="' + localId + '"][data-action="watched"], .icon-btn[data-local-id="' + localId + '"][data-action="watched-modal"]').forEach(b => {
            b.classList.toggle('watched', isAdding);
            b.setAttribute('title', isAdding ? 'Usuń z obserwowanych' : 'Dodaj do obserwowanych');
        });

        // Update mobile watched button text
        document.querySelectorAll('.mobile-watched-btn[data-local-id="' + localId + '"]').forEach(b => {
            b.classList.toggle('is-watched', isAdding);
            var btnText = b.querySelector('.mobile-watched-btn-text');
            if (btnText) {
                btnText.textContent = isAdding ? 'Usuń z obserwowanych' : 'Dodaj do obserwowanych';
            }
        });

        // Update apartment item watched class
        document.querySelectorAll('.apartment-item').forEach(item => {
            const modalData = item.getAttribute('data-modal');
            if (modalData) {
                try {
                    const data = JSON.parse(modalData);
                    if (String(data.localId) === String(localId)) {
                        item.classList.toggle('is-watched', isAdding);
                    }
                } catch (e) {}
            }
        });

        // Update watched count
        updateWatchedCount();

        // Check placeholder visibility if in watched view
        const apartmentList = document.querySelector('.apartment-list');
        if (apartmentList && apartmentList.classList.contains('hide-watched')) {
            checkAndToggleNoWatchedPlaceholder();
        }

        // Show toast notification when adding to watched
        if (isAdding) {
            showWatchedToast();
        }
    }

    function getWatched() {
        const watched = localStorage.getItem(getWatchedKey());
        return watched ? JSON.parse(watched) : [];
    }

    function saveWatched(watched) {
        localStorage.setItem(getWatchedKey(), JSON.stringify(watched));
        document.dispatchEvent(new CustomEvent('develogic:watched_changed'));
    }

    function loadWatchedState() {
        const watched = getWatched();

        watched.forEach(localId => {
            document.querySelectorAll('[data-local-id="' + localId + '"][data-action="watched"], .icon-btn[data-local-id="' + localId + '"][data-action="watched-modal"]').forEach(btn => {
                btn.classList.add('watched');
                btn.setAttribute('title', 'Usuń z obserwowanych');
            });

            document.querySelectorAll('.mobile-watched-btn[data-local-id="' + localId + '"]').forEach(btn => {
                btn.classList.add('is-watched');
                var btnText = btn.querySelector('.mobile-watched-btn-text');
                if (btnText) {
                    btnText.textContent = 'Usuń z obserwowanych';
                }
            });

            // Mark apartment items as watched
            document.querySelectorAll('.apartment-item').forEach(item => {
                const modalData = item.getAttribute('data-modal');
                if (modalData) {
                    try {
                        const data = JSON.parse(modalData);
                        if (String(data.localId) === String(localId)) {
                            item.classList.add('is-watched');
                        }
                    } catch (e) {}
                }
            });
        });
    }

    function updateWatchedCount() {
        const watchedCount = document.getElementById('watchedCount');
        if (!watchedCount) return;

        const onPage = getWatchedOnPage();
        const count = onPage.length;

        watchedCount.textContent = count + ' ' + (count === 1 ? 'obserwowane' : 'obserwowanych');
    }

    function checkAndToggleNoWatchedPlaceholder() {
        const apartmentList = document.querySelector('.apartment-list');
        if (!apartmentList) return;

        const onPage = getWatchedOnPage();
        const hasWatched = onPage.length > 0;

        if (hasWatched) {
            apartmentList.classList.remove('has-no-watched');
        } else {
            apartmentList.classList.add('has-no-watched');
        }
    }

    function showWatchedToast() {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `
            <div class="toast-icon toast-icon-watched"></div>
            <div class="toast-content">
                <div class="toast-title">Dodano do obserwowanych</div>
                <span class="toast-link" id="toastWatchedLink">Zobacz listę</span>
            </div>
        `;

        container.appendChild(toast);

        // Setup click handler for the "Zobacz listę" link
        const toastWatchedLink = toast.querySelector('#toastWatchedLink');
        if (toastWatchedLink) {
            toastWatchedLink.addEventListener('click', function(e) {
                e.preventDefault();
                // Close detail modal if open
                closeApartmentModal();
                // Switch to watched view
                const watchedBtn = document.querySelector('.favorites-toggle-btn[data-toggle-view="watched"]');
                if (watchedBtn) {
                    watchedBtn.click();
                }
            });
        }

        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        setTimeout(() => {
            toast.classList.add('hide');
            setTimeout(() => {
                if (container.contains(toast)) {
                    container.removeChild(toast);
                }
            }, 300);
        }, 4000);
    }

    // ===========================
    // Email button functionality
    // ===========================
    function setupEmailButtons() {
        // List email buttons
        document.querySelectorAll('.icon-btn[data-action="email"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const apartmentItem = this.closest('.apartment-item');
                const apartmentNumber = apartmentItem.querySelector('.apartment-number').textContent;
                handleEmail(apartmentNumber);
            });
        });
        
        // Modal email button
        document.addEventListener('click', function(e) {
            if (e.target.closest('.icon-btn[data-action="email-modal"]')) {
                e.preventDefault();
                const apartmentNumber = document.querySelector('.unit-name').textContent;
                handleEmail(apartmentNumber);
            }
        });
    }
    
    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
               (window.innerWidth <= 768);
    }
    
    function handleEmail(apartmentNumber) {
        const developerName = window.develogicApartmentsData?.developer_name || '';
        const isMobile = isMobileDevice();
        
        // Get appropriate contact link based on device type
        let contactLink = '';
        if (isMobile) {
            contactLink = window.develogicApartmentsData?.contact_link_mobile || '';
        } else {
            contactLink = window.develogicApartmentsData?.contact_link_desktop || '';
            // Fallback to mobile link if desktop link is not set
            if (!contactLink) {
                contactLink = window.develogicApartmentsData?.contact_link_mobile || '';
            }
        }
        
        if (!contactLink) {
            return;
        }
        
        // If it's a mailto: link, append subject and body
        if (contactLink.startsWith('mailto:')) {
            const subject = encodeURIComponent('Mieszkanie ' + apartmentNumber + ' – ' + developerName);
            const body = encodeURIComponent('\n---\n' + window.location.href);
            
            // Check if mailto already has query params
            const separator = contactLink.indexOf('?') !== -1 ? '&' : '?';
            window.location.href = contactLink + separator + 'Subject=' + subject + '&body=' + body;
        } else {
            // Regular URL - open in new tab
            window.open(contactLink, '_blank');
        }
    }
    
    // ===========================
    // Apartment click to open modal
    // ===========================
    function isMobileView() {
        return window.innerWidth <= 992;
    }

    function setupApartmentClicks() {
        document.querySelectorAll('.apartment-item').forEach(item => {
            item.addEventListener('click', function(e) {
                // Don't open modal if clicking on buttons or expandable area
                if (e.target.closest('.icon-btn') || e.target.closest('.apartment-mobile-expand') || e.target.closest('.mobile-expand-detail-btn')) {
                    return;
                }

                // On mobile, toggle expand instead of opening modal
                if (isMobileView()) {
                    this.classList.toggle('mobile-expanded');
                    return;
                }

                const modalData = this.getAttribute('data-modal');
                if (modalData) {
                    try {
                        const data = JSON.parse(modalData);
                        openApartmentModal(data);
                    } catch (err) {
                        console.error('Error parsing modal data:', err);
                    }
                }
            });
        });

        // Mobile expand toggle button
        document.querySelectorAll('.mobile-expand-toggle').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var item = this.closest('.apartment-item');
                if (item) {
                    item.classList.toggle('mobile-expanded');
                }
            });
        });

        // Mobile "Zobacz szczegóły" button - opens modal
        document.querySelectorAll('.mobile-expand-detail-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var item = this.closest('.apartment-item');
                if (item) {
                    var modalData = item.getAttribute('data-modal');
                    if (modalData) {
                        try {
                            var data = JSON.parse(modalData);
                            openApartmentModal(data);
                        } catch (err) {
                            console.error('Error parsing modal data:', err);
                        }
                    }
                }
            });
        });

        // Also handle clicks directly on apartment images
        document.querySelectorAll('.apartment-image').forEach(imgContainer => {
            imgContainer.addEventListener('click', function(e) {
                e.stopPropagation();
                const apartmentItem = this.closest('.apartment-item');
                if (apartmentItem) {
                    const modalData = apartmentItem.getAttribute('data-modal');
                    if (modalData) {
                        try {
                            const data = JSON.parse(modalData);
                            openApartmentModal(data);
                        } catch (err) {
                            console.error('Error parsing modal data:', err);
                        }
                    }
                }
            });
        });
    }

    // ===========================
    // Desktop 3D Floor Plan Tooltip
    // ===========================
    function setup3dTooltip() {
        const tooltip = document.getElementById('apartment3dTooltip');
        if (!tooltip) return;

        // Move to body so it's not clipped by overflow containers
        document.body.appendChild(tooltip);

        const tooltipImg = tooltip.querySelector('img');
        let showTimeout = null;
        let currentItem = null;

        document.querySelectorAll('.apartment-item[data-marketing-img]').forEach(item => {
            item.addEventListener('mouseenter', function(e) {
                if (isMobileView()) return;

                const imgUrl = this.getAttribute('data-marketing-img');
                if (!imgUrl) return;

                currentItem = this;

                showTimeout = setTimeout(() => {
                    tooltipImg.src = imgUrl;
                    tooltip.classList.add('visible');
                    positionTooltipBelowRow();
                }, 300);
            });

            item.addEventListener('mouseleave', function() {
                clearTimeout(showTimeout);
                tooltip.classList.remove('visible');
                currentItem = null;
            });
        });

        function positionTooltipBelowRow() {
            if (!currentItem) return;

            const rowRect = currentItem.getBoundingClientRect();
            const tooltipWidth = 560;
            const margin = 8;

            // Position below the row
            let top = rowRect.bottom + margin;
            // Center horizontally relative to the row
            let left = rowRect.left + (rowRect.width - tooltipWidth) / 2;

            // Keep within viewport horizontally
            if (left < margin) {
                left = margin;
            }
            if (left + tooltipWidth > window.innerWidth - margin) {
                left = window.innerWidth - tooltipWidth - margin;
            }

            // If not enough space below, check if tooltip fits in remaining viewport
            const tooltipHeight = tooltip.offsetHeight || 400;
            if (top + tooltipHeight > window.innerHeight - margin) {
                top = window.innerHeight - tooltipHeight - margin;
            }
            if (top < rowRect.bottom + margin) {
                top = rowRect.bottom + margin;
            }

            tooltip.style.left = left + 'px';
            tooltip.style.top = top + 'px';
        }
    }

    // ===========================
    // Modal functionality
    // ===========================
    let currentModalData = null;
    let currentGalleryIndex = 0;
    let zoomLevel = 1;
    let imageOffsetX = 0;
    let imageOffsetY = 0;
    
    function setupModal() {
        // Move modal to body to ensure it's above all containers (including sticky header)
        const modal = document.getElementById('apartment-detail-modal');
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        
        // Close button
        const closeBtn = document.querySelector('.apartment-detail-modal .modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeApartmentModal);
        }
        
        // Close on overlay click
        const overlay = document.querySelector('.modal-overlay');
        if (overlay) {
            overlay.addEventListener('click', closeApartmentModal);
        }
        
        // Close on escape key
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('apartment-detail-modal');
            if (modal && modal.style.display !== 'none') {
                if (e.key === 'Escape') {
                    if (document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement) {
                        exitFullscreen();
                    } else {
                        closeApartmentModal();
                    }
                }
                if (e.key === 'ArrowLeft') {
                    prevImage();
                }
                if (e.key === 'ArrowRight') {
                    nextImage();
                }
            }
        });

        // Gallery navigation
        const prevBtn = document.querySelector('.gallery-nav.prev');
        const nextBtn = document.querySelector('.gallery-nav.next');
        if (prevBtn) {
            prevBtn.addEventListener('click', prevImage);
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', nextImage);
        }
        
        // Zoom controls
        setupZoomControls();
        
        // Fullscreen controls
        setupFullscreenControls();
    }
    
    function openApartmentModal(data) {
        currentModalData = data;
        currentGalleryIndex = 0;
        
        // Reset zoom when opening modal
        resetZoom();
        
        const modal = document.getElementById('apartment-detail-modal');
        if (!modal) return;
        
        // Set header title
        let headerTitle = '';
        if (data.building) {
            headerTitle = 'Budynek ' + data.building;
        }
        if (data.number) {
            // Determine the local type label
            let localTypeLabel = 'Lokal';
            if (data.localType) {
                // Map local types to display names
                const typeMap = {
                    'Lokal mieszkalny': 'Mieszkanie',
                    'Garaż': 'Garaż',
                    'Komórka lokatorska': 'Komórka lokatorska',
                    'Miejsce postojowe': 'Miejsce postojowe'
                };
                localTypeLabel = typeMap[data.localType] || data.localType;
            }
            headerTitle += (headerTitle ? ' - ' : '') + localTypeLabel + ' ' + data.number;
        }
        modal.querySelector('.modal-title').textContent = headerTitle || 'Szczegóły lokalu';
        
        // Set location (Building and Subdivision in one row)
        let locationText = '';
        if (data.building) {
            locationText = 'Budynek ' + data.building;
        }
        if (data.subdivision) {
            locationText += (locationText ? ' • ' : '') + data.subdivision;
        }
        modal.querySelector('.location').innerHTML = locationText;
        
        // Set unit name
        modal.querySelector('.unit-name').textContent = data.number || '';
        
        // Set status
        const statusEl = modal.querySelector('.status');
        if (data.statusClass === 'available') {
            statusEl.innerHTML = '<span style="color: #00b341;">Dostępne</span>';
        } else if (data.statusClass === 'reserved') {
            statusEl.innerHTML = '<span style="color: #ff9500;">Rezerwacja</span>';
        } else if (data.statusClass === 'sold') {
            const statusText = data.status || 'Sprzedany';
            statusEl.innerHTML = '<span style="color: #8b0000;">' + statusText + '</span>';
        } else {
            statusEl.textContent = data.status || '';
        }
        
        // Set specs
        const detailSpecs = modal.querySelector('.detail-specs');
        detailSpecs.innerHTML = '';
        
        if (data.klatka) {
            addSpecRow(detailSpecs, 'Klatka', data.klatka);
        }
        
        addSpecRow(detailSpecs, 'Kondygnacja', data.floorDisplay || formatFloor(data.floor));
        addSpecRow(detailSpecs, 'Powierzchnia', formatArea(data.area));
        addSpecRow(detailSpecs, 'Ilość pokoi', data.rooms);
        
        // Set features - include plannedDate in tags if available
        const featuresEl = modal.querySelector('.detail-features');
        featuresEl.innerHTML = '';
        
        const allTags = [];
        
        if (data.tags && data.tags.length > 0) {
            allTags.push(...data.tags);
        }
        
        if (data.plannedDate) {
            const plannedDateText = 'Planowane oddanie: ' + formatDate(data.plannedDate);
            allTags.push(plannedDateText);
        }
        
        // Create tag elements
        allTags.forEach(tag => {
            const tagEl = document.createElement('span');
            tagEl.className = 'detail-feature-tag';
            tagEl.textContent = tag;
            featuresEl.appendChild(tagEl);
        });
        
        // Set price with old price if promo
        const priceMain = modal.querySelector('.detail-price .price-main');
        const pricePerM2 = modal.querySelector('.detail-price .price-per-m2');
        
        if (data.hasPromo && data.oldPriceGross) {
            // Show old price as strikethrough before main price
            priceMain.innerHTML = '<span style="font-size: 24px; color: #999; text-decoration: line-through; display: block; margin-bottom: 8px;">' + formatPrice(data.oldPriceGross) + '</span>' + formatPrice(data.priceGross);
            priceMain.classList.add('promo-price');
        } else {
            priceMain.textContent = formatPrice(data.priceGross);
            priceMain.classList.remove('promo-price');
        }
        
        pricePerM2.textContent = '(' + formatPriceM2(data.priceM2) + ' zł/m²)';
        
        // Set omnibus price if available
        const omnibusContainer = modal.querySelector('.detail-price-omnibus');
        if (omnibusContainer) {
            if (data.hasPromo && data.omnibusPriceGross && data.omnibusPriceGross > 0) {
                omnibusContainer.querySelector('.omnibus-value').textContent = formatPrice(data.omnibusPriceGross);
                omnibusContainer.style.display = 'block';
            } else {
                omnibusContainer.style.display = 'none';
            }
        }
        
        // Show/hide promo banner (full width)
        const promoBanner = modal.querySelector('.promo-banner-link');
        if (promoBanner) {
            promoBanner.style.display = data.hasPromo ? 'flex' : 'none';
        }
        
        // Show/hide promo badge in gallery
        const promoBadgeGallery = modal.querySelector('.gallery-promo-badge');
        if (promoBadgeGallery) {
            promoBadgeGallery.style.display = data.hasPromo ? 'inline-flex' : 'none';
        }
        
        // Set 3D tour link
        const tour3dLink = modal.querySelector('.tour-3d-link:not(.download-card-link)');
        if (tour3dLink && data.tour3dUrl) {
            tour3dLink.href = data.tour3dUrl;
            tour3dLink.style.display = 'flex';
        } else if (tour3dLink) {
            tour3dLink.style.display = 'none';
        }
        
        // Set download card link
        const downloadCardLink = modal.querySelector('.download-card-link');
        if (downloadCardLink && data.pdfLink) {
            downloadCardLink.href = data.pdfLink;
            downloadCardLink.style.display = 'flex';
        } else if (downloadCardLink) {
            downloadCardLink.style.display = 'none';
        }
        
        // Set watched button
        const watchedBtn = modal.querySelector('.icon-btn[data-action="watched-modal"]');
        if (watchedBtn) {
            watchedBtn.setAttribute('data-local-id', data.localId);
            const watched = getWatched();
            if (watched.indexOf(data.localId.toString()) !== -1) {
                watchedBtn.classList.add('watched');
            } else {
                watchedBtn.classList.remove('watched');
            }
        }

        // Set favorite button
        const favoriteBtn = modal.querySelector('.icon-btn[data-action="favorite-modal"]');
        if (favoriteBtn) {
            favoriteBtn.setAttribute('data-local-id', data.localId);
            const favorites = getFavorites();
            if (favorites.indexOf(data.localId.toString()) !== -1) {
                favoriteBtn.classList.add('favorited');
            } else {
                favoriteBtn.classList.remove('favorited');
            }
        }
        
        // Setup gallery
        setupGallery(data.projections || []);
        
        // Load price history
        loadPriceHistory(data.localId);

        // Update show-cart button visibility
        updateModalCartButton();

        // Show modal
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    function setupGallery(projections) {
        const mainImage = document.querySelector('.gallery-main-image');
        const galleryContainer = document.querySelector('.gallery-thumbnails');
        const galleryMain = document.querySelector('.gallery-main');
        
        if (!mainImage || !galleryContainer) return;
        
        galleryContainer.innerHTML = '';
        
        if (projections.length === 0) {
            mainImage.src = '';
            mainImage.alt = 'Brak zdjęć';
            return;
        }
        
        // Ensure gallery container has proper constraints
        if (galleryMain) {
            galleryMain.style.width = '100%';
            galleryMain.style.maxWidth = '100%';
            galleryMain.style.overflow = 'hidden';
        }
        
        // Set main image
        currentGalleryIndex = 0;
        updateMainImage(projections[0].url);
        
        // Create gallery items
        projections.forEach((proj, index) => {
            const thumb = document.createElement('img');
            thumb.src = proj.thumb;
            thumb.alt = proj.type || 'Gallery ' + (index + 1);
            thumb.className = 'gallery-thumbnail' + (index === 0 ? ' active' : '');
            
            thumb.addEventListener('click', function() {
                setImage(index);
            });
            
            galleryContainer.appendChild(thumb);
        });
        
        currentModalData.galleryImages = projections;
    }
    
    function setImage(index) {
        if (!currentModalData.galleryImages || index < 0 || index >= currentModalData.galleryImages.length) return;
        
        currentGalleryIndex = index;
        const mainImage = document.querySelector('.gallery-main-image');
        
        if (mainImage) {
            mainImage.src = currentModalData.galleryImages[index].url;
            mainImage.alt = currentModalData.galleryImages[index].type || 'Gallery ' + (index + 1);
            // Reset zoom when changing image
            resetZoom();
        }
        
        // Update thumbnails
        document.querySelectorAll('.gallery-thumbnail').forEach((thumb, i) => {
            thumb.classList.toggle('active', i === index);
        });
    }
    
    function prevImage() {
        if (!currentModalData.galleryImages) return;
        const newIndex = currentGalleryIndex > 0 ? currentGalleryIndex - 1 : currentModalData.galleryImages.length - 1;
        setImage(newIndex);
    }
    
    function nextImage() {
        if (!currentModalData.galleryImages) return;
        const newIndex = currentGalleryIndex < currentModalData.galleryImages.length - 1 ? currentGalleryIndex + 1 : 0;
        setImage(newIndex);
    }
    
    function updateMainImage(url) {
        const mainImage = document.querySelector('.gallery-main-image');
        if (mainImage) {
            // Reset zoom when changing image
            resetZoom();
            
            // Ensure image loads with proper constraints
            mainImage.onload = function() {
                // Force recalculation of container bounds
                const galleryMain = mainImage.closest('.gallery-main');
                if (galleryMain) {
                    galleryMain.style.width = '100%';
                }
            };
            
            mainImage.src = url;
        }
    }
    
    // ===========================
    // Zoom functionality
    // ===========================
    function setupZoomControls() {
        const zoomInBtn = document.querySelector('.gallery-zoom-in');
        const zoomOutBtn = document.querySelector('.gallery-zoom-out');
        const zoomResetBtn = document.querySelector('.gallery-zoom-reset');
        const mainImage = document.querySelector('.gallery-main-image');
        const galleryMain = document.querySelector('.gallery-main');
        
        if (!mainImage || !galleryMain) return;
        
        if (zoomInBtn) {
            zoomInBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                zoomIn();
            });
        }
        
        if (zoomOutBtn) {
            zoomOutBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                zoomOut();
            });
        }
        
        if (zoomResetBtn) {
            zoomResetBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                resetZoom();
            });
        }
        
        // Mouse wheel zoom
        galleryMain.addEventListener('wheel', function(e) {
            if (e.ctrlKey || e.metaKey) {
                e.preventDefault();
                const delta = e.deltaY > 0 ? -0.1 : 0.1;
                zoomTo(Math.max(1, Math.min(5, zoomLevel + delta)));
            }
        }, { passive: false });
        
        // Drag to pan when zoomed
        let isPanning = false;
        let panStartX = 0;
        let panStartY = 0;
        
        mainImage.addEventListener('mousedown', function(e) {
            if (zoomLevel > 1) {
                isPanning = true;
                panStartX = e.clientX - imageOffsetX;
                panStartY = e.clientY - imageOffsetY;
                mainImage.style.cursor = 'grabbing';
                e.preventDefault();
            }
        });
        
        document.addEventListener('mousemove', function(e) {
            if (isPanning && zoomLevel > 1) {
                imageOffsetX = e.clientX - panStartX;
                imageOffsetY = e.clientY - panStartY;
                applyZoomTransform();
                e.preventDefault();
            }
        });
        
        document.addEventListener('mouseup', function() {
            if (isPanning) {
                isPanning = false;
                mainImage.style.cursor = zoomLevel > 1 ? 'grab' : 'default';
            }
        });
        
        // Touch support for mobile
        let touchStartDistance = 0;
        let touchStartZoom = 1;
        
        galleryMain.addEventListener('touchstart', function(e) {
            if (e.touches.length === 2) {
                e.preventDefault();
                touchStartDistance = getTouchDistance(e.touches[0], e.touches[1]);
                touchStartZoom = zoomLevel;
            } else if (e.touches.length === 1 && zoomLevel > 1) {
                isPanning = true;
                panStartX = e.touches[0].clientX - imageOffsetX;
                panStartY = e.touches[0].clientY - imageOffsetY;
            }
        }, { passive: false });
        
        galleryMain.addEventListener('touchmove', function(e) {
            if (e.touches.length === 2) {
                e.preventDefault();
                const touchDistance = getTouchDistance(e.touches[0], e.touches[1]);
                const scale = touchDistance / touchStartDistance;
                zoomTo(Math.max(1, Math.min(5, touchStartZoom * scale)));
            } else if (e.touches.length === 1 && isPanning && zoomLevel > 1) {
                e.preventDefault();
                imageOffsetX = e.touches[0].clientX - panStartX;
                imageOffsetY = e.touches[0].clientY - panStartY;
                applyZoomTransform();
            }
        }, { passive: false });
        
        galleryMain.addEventListener('touchend', function() {
            isPanning = false;
        });
    }
    
    function getTouchDistance(touch1, touch2) {
        const dx = touch1.clientX - touch2.clientX;
        const dy = touch1.clientY - touch2.clientY;
        return Math.sqrt(dx * dx + dy * dy);
    }
    
    function zoomIn() {
        zoomTo(Math.min(5, zoomLevel + 0.5));
    }
    
    function zoomOut() {
        zoomTo(Math.max(1, zoomLevel - 0.5));
    }
    
    function resetZoom() {
        zoomTo(1);
    }
    
    function zoomTo(level) {
        zoomLevel = level;
        const mainImage = document.querySelector('.gallery-main-image');
        
        if (zoomLevel === 1) {
            imageOffsetX = 0;
            imageOffsetY = 0;
        }
        
        applyZoomTransform();
        
        if (mainImage) {
            mainImage.style.cursor = zoomLevel > 1 ? 'grab' : 'default';
            mainImage.closest('.gallery-main').classList.toggle('zoomed', zoomLevel > 1);
        }
    }
    
    function applyZoomTransform() {
        const mainImage = document.querySelector('.gallery-main-image');
        if (mainImage) {
            mainImage.style.transform = `scale(${zoomLevel}) translate(${imageOffsetX / zoomLevel}px, ${imageOffsetY / zoomLevel}px)`;
            mainImage.style.transformOrigin = 'center center';
            mainImage.style.transition = zoomLevel === 1 ? 'transform 0.3s ease-out' : 'transform 0.1s ease-out';
        }
    }
    
    // ===========================
    // Fullscreen functionality
    // ===========================
    function setupFullscreenControls() {
        const fullscreenBtn = document.querySelector('.gallery-fullscreen');
        const galleryMain = document.querySelector('.gallery-main');
        
        if (!fullscreenBtn || !galleryMain) return;
        
        fullscreenBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleFullscreen();
        });
        
        // Listen for fullscreen changes
        document.addEventListener('fullscreenchange', updateFullscreenButton);
        document.addEventListener('webkitfullscreenchange', updateFullscreenButton);
        document.addEventListener('mozfullscreenchange', updateFullscreenButton);
        document.addEventListener('MSFullscreenChange', updateFullscreenButton);
    }
    
    function toggleFullscreen() {
        const galleryMain = document.querySelector('.gallery-main');
        if (!galleryMain) return;
        
        if (isFullscreen()) {
            exitFullscreen();
        } else {
            enterFullscreen(galleryMain);
        }
    }
    
    function isFullscreen() {
        return !!(document.fullscreenElement || 
                 document.webkitFullscreenElement || 
                 document.mozFullScreenElement || 
                 document.msFullscreenElement);
    }
    
    function enterFullscreen(element) {
        if (element.requestFullscreen) {
            element.requestFullscreen();
        } else if (element.webkitRequestFullscreen) {
            element.webkitRequestFullscreen();
        } else if (element.mozRequestFullScreen) {
            element.mozRequestFullScreen();
        } else if (element.msRequestFullscreen) {
            element.msRequestFullscreen();
        }
    }
    
    function exitFullscreen() {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    }
    
    function updateFullscreenButton() {
        const fullscreenBtn = document.querySelector('.gallery-fullscreen');
        if (!fullscreenBtn) return;
        
        const fullscreen = isFullscreen();
        const openIcon = fullscreenBtn.querySelector('.fullscreen-open');
        const closeIcon = fullscreenBtn.querySelector('.fullscreen-close');
        
        if (openIcon && closeIcon) {
            if (fullscreen) {
                openIcon.style.display = 'none';
                closeIcon.style.display = 'block';
            } else {
                openIcon.style.display = 'block';
                closeIcon.style.display = 'none';
            }
        }
    }
    
    function closeApartmentModal() {
        const modal = document.getElementById('apartment-detail-modal');
        if (modal) {
            // Exit fullscreen if active
            if (isFullscreen()) {
                exitFullscreen();
            }
            // Reset zoom
            resetZoom();
            modal.style.display = 'none';
            document.body.style.overflow = '';
            // Re-fix sticky ancestors after modal close (overflow changes may break position:sticky)
            fixStickyAncestors();
        }
    }
    
    function addDetailRow(container, label, value) {
        const row = document.createElement('div');
        row.className = 'detail-row';
        row.innerHTML = '<span class="detail-label">' + label + '</span><span class="detail-value">' + value + '</span>';
        container.appendChild(row);
    }
    
    function addSpecRow(container, label, value) {
        const row = document.createElement('div');
        row.className = 'spec-item';
        row.innerHTML = '<span class="spec-label">' + label + '</span><span class="spec-value">' + value + '</span>';
        container.appendChild(row);
    }
    
    function formatPrice(price) {
        if (!price) return '0 zł';
        return parseFloat(price).toLocaleString('pl-PL', {minimumFractionDigits: 0, maximumFractionDigits: 0}) + ' zł';
    }
    
    function formatPriceM2(price) {
        if (!price) return '0,00';
        return parseFloat(price).toLocaleString('pl-PL', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    
    function formatArea(area) {
        if (!area) return '0,00 m²';
        return parseFloat(area).toLocaleString('pl-PL', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' m²';
    }
    
    /**
     * Parse floor value to number, handling various formats:
     * - Numbers: 6, "6", -1, 0
     * - Roman numerals: "VI", "VI piętro", "Piętro VI"
     * - Text with numbers: "6 piętro", "Piętro 6"
     */
    function parseFloorToNumber(floor) {
        if (floor === '' || floor === null || floor === undefined) return null;
        
        const floorStr = String(floor).trim();
        
        // Handle special cases
        if (floorStr === '0' || floorStr === 'Parter' || floorStr.toLowerCase() === 'parter') return 0;
        if (floorStr === '-1' || floorStr === 'Piwnica' || floorStr.toLowerCase() === 'piwnica') return -1;
        
        // Try direct parseInt first
        const directParse = parseInt(floorStr);
        if (!isNaN(directParse)) {
            return directParse;
        }
        
        // Try to extract number from text like "VI piętro", "Piętro VI", "6 piętro"
        // Check Roman numerals in descending order (longer first) to avoid partial matches
        const romanNumerals = [
            ['VIII', 8], ['VII', 7], ['III', 3], ['VI', 6], ['IV', 4], ['IX', 9],
            ['II', 2], ['X', 10], ['V', 5], ['I', 1]
        ];
        
        // Check for Roman numerals in the string (only if no Arabic digits)
        if (!floorStr.match(/\d/)) {
            for (const [roman, num] of romanNumerals) {
                if (floorStr.includes(roman)) {
                    return num;
                }
            }
        }
        
        // Try to extract Arabic number from text
        const numberMatch = floorStr.match(/\d+/);
        if (numberMatch) {
            return parseInt(numberMatch[0]);
        }
        
        return null;
    }
    
    function formatFloor(floor) {
        if (floor === '' || floor === null || floor === undefined) return '';
        // Convert to string for consistent comparison
        const floorStr = String(floor);
        if (floorStr === '0') return 'Parter';
        if (floorStr === '-1') return 'Piwnica';
        const floorNum = parseInt(floor);
        if (floorNum > 0) {
            // Format as "Piętro I", "Piętro II", etc.
            const romanNumerals = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
            if (floorNum <= 10 && romanNumerals[floorNum]) {
                return 'Piętro ' + romanNumerals[floorNum];
            }
            return 'Piętro ' + floorNum;
        }
        return floorStr;
    }
    
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const months = ['styczeń', 'luty', 'marzec', 'kwiecień', 'maj', 'czerwiec', 
                       'lipiec', 'sierpień', 'wrzesień', 'październik', 'listopad', 'grudzień'];
        return months[date.getMonth()] + ' ' + date.getFullYear();
    }

    // ===========================
    // Price history
    // ===========================
    function loadPriceHistory(localId) {
        const historyContainer = document.querySelector('.detail-price-history');
        if (!historyContainer) return;
        const listEl = historyContainer.querySelector('.price-history-list');
        const emptyEl = historyContainer.querySelector('.price-history-empty');
        const loaderEl = historyContainer.querySelector('.price-history-loader');
        const tableEl = historyContainer.querySelector('.price-history-table');
        
        // Reset state
        if (listEl) listEl.innerHTML = '';
        if (emptyEl) emptyEl.style.display = 'none';
        if (tableEl) tableEl.style.display = 'none';
        if (loaderEl) loaderEl.style.display = 'flex';
        
        const baseUrl = (window.develogicData && window.develogicData.restUrl) ? window.develogicData.restUrl : '/wp-json/develogic/v1';
        const url = baseUrl.replace(/\/$/, '') + '/price-history/' + encodeURIComponent(localId);
        
        fetch(url, { credentials: 'same-origin' })
            .then(res => res.json())
            .then(history => {
                if (loaderEl) loaderEl.style.display = 'none';
                
                const prices = Array.isArray(history?.prices) ? history.prices : [];
                if (!prices.length) {
                    if (emptyEl) {
                        emptyEl.style.display = 'block';
                        emptyEl.textContent = 'Brak danych o historii cen.';
                    }
                    return;
                }
                
                // Sort by appliesFrom descending (newest first)
                prices.sort((a, b) => new Date(b.appliesFrom) - new Date(a.appliesFrom));
                
                // Build table rows (latest 6 entries)
                const last = prices.slice(0, 6);
                
                last.forEach(p => {
                    const label = formatDateShort(p.appliesFrom);
                    const gross = pickNumber(p.priceGross, p.packagePriceGross, p.promoPriceGross);
                    const grossm2 = pickNumber(p.priceGrossm2, p.packagePriceGrossm2, p.promoPriceGrossm2);
                    let valueText = '';
                    if (isFiniteNumber(gross)) {
                        valueText = formatPrice(gross);
                    } else if (isFiniteNumber(grossm2)) {
                        valueText = formatPriceM2(grossm2) + ' zł/m²';
                    }
                    if (listEl && valueText) {
                        const row = document.createElement('tr');
                        row.innerHTML = '<td class="date-cell">' + label + '</td><td class="value-cell">' + valueText + '</td>';
                        listEl.appendChild(row);
                    }
                });
                
                if (tableEl && listEl.children.length > 0) {
                    tableEl.style.display = 'table';
                }
            })
            .catch(() => {
                if (loaderEl) loaderEl.style.display = 'none';
                if (emptyEl) {
                    emptyEl.style.display = 'block';
                    emptyEl.textContent = 'Nie udało się pobrać historii cen.';
                }
            });
    }
    
    function isFiniteNumber(n) {
        return typeof n === 'number' && isFinite(n);
    }
    
    function pickNumber() {
        for (let i = 0; i < arguments.length; i++) {
            const v = arguments[i];
            if (isFiniteNumber(v)) return v;
        }
        return null;
    }
    
    function formatDateShort(dateString) {
        if (!dateString) return '';
        const d = new Date(dateString);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return day + '.' + month + '.' + year;
    }
    
    // ===========================
    // Toast notification
    // ===========================
    function showToast() {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `
            <div class="toast-icon"></div>
            <div class="toast-content">
                <div class="toast-title">Dodano do konfiguratora oferty</div>
                <span class="toast-link" id="toastFavoritesLink">Zobacz listę</span>
            </div>
        `;
        
        container.appendChild(toast);
        
        // Show toast with animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Setup click handler for the "Zobacz listę" link
        const toastLink = toast.querySelector('#toastFavoritesLink');
        if (toastLink) {
            toastLink.addEventListener('click', function(e) {
                e.preventDefault();
                // Close detail modal if open
                closeApartmentModal();
                // Switch to favorites view
                const favoritesBtn = document.querySelector('.favorites-toggle-btn[data-toggle-view="favorites"]');
                if (favoritesBtn) {
                    favoritesBtn.click();
                }
            });
        }
        
        // Hide and remove toast after 4 seconds
        setTimeout(() => {
            toast.classList.add('hide');
            setTimeout(() => {
                if (container.contains(toast)) {
                    container.removeChild(toast);
                }
            }, 300);
        }, 4000);
    }
    
    // ===========================
    // Favorites view toggle
    // ===========================
    function setupFavoritesViewToggle() {
        const toggleButtons = document.querySelectorAll('.favorites-toggle-btn');
        const apartmentList = document.querySelector('.apartment-list');
        const shareContainer = document.getElementById('favoritesShareContainer');

        if (!toggleButtons.length || !apartmentList) return;

        toggleButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const view = this.getAttribute('data-toggle-view');

                // Intercept: if clicking "Konfigurator oferty" and it's empty, show wizard
                if (view === 'favorites' && handleConfiguratorClick()) {
                    return;
                }

                // Update button active states - sync all buttons with same data-toggle-view
                const clickedView = this.getAttribute('data-toggle-view');
                toggleButtons.forEach(b => {
                    const bView = b.getAttribute('data-toggle-view');
                    b.classList.toggle('active', bView === clickedView);
                });

                // Notify quote button about view change
                document.dispatchEvent(new CustomEvent('develogic:view_changed', { detail: { view: view } }));

                // Clear all view classes first
                apartmentList.classList.remove('hide-favorites', 'has-no-favorites', 'hide-watched', 'has-no-watched');

                if (view === 'favorites') {
                    // Reset filters to show all observed apartments
                    resetFilters();
                    // Override localType to "all" so all favorite types (apartments, garages, storage) are visible
                    const localTypeFilter = document.getElementById('localTypeFilter');
                    if (localTypeFilter) {
                        localTypeFilter.value = 'all';
                        applyFilters();
                    }
                    apartmentList.classList.add('hide-favorites');
                    // Check if there are any favorites
                    checkAndToggleNoFavoritesPlaceholder();
                    // Show share buttons when in favorites view
                    if (shareContainer) {
                        shareContainer.style.display = 'flex';
                    }
                    // Update URL with favorites
                    updateUrlWithFavorites();
                    // Show configurator summary
                    updateConfiguratorSummary();
                } else if (view === 'watched') {
                    // Reset filters to show all watched apartments
                    resetFilters();
                    const localTypeFilter = document.getElementById('localTypeFilter');
                    if (localTypeFilter) {
                        localTypeFilter.value = 'all';
                        applyFilters();
                    }
                    apartmentList.classList.add('hide-watched');
                    checkAndToggleNoWatchedPlaceholder();
                    if (shareContainer) {
                        shareContainer.style.display = 'none';
                    }
                    removeFavoritesFromUrl();
                    updateConfiguratorSummary();
                } else {
                    // Hide share buttons when in all view
                    if (shareContainer) {
                        shareContainer.style.display = 'none';
                    }
                    // Remove favorites from URL
                    removeFavoritesFromUrl();
                    // Hide configurator summary
                    updateConfiguratorSummary();
                }
            });
        });
    }
    
    // ===========================
    // Inquiry form (in configurator summary)
    // ===========================
    function setupInquiryForm() {
        var form = document.getElementById('inquiryForm');
        if (!form) return;

        // Toggle inline selects when their parent checkbox is checked/unchecked
        var childrenCheckbox = document.getElementById('surveyPromoChildren');
        var specialCheckbox = document.getElementById('surveyPromoSpecial');
        if (childrenCheckbox) {
            childrenCheckbox.addEventListener('change', function() {
                var sel = form.querySelector('select[name="survey_children_count"]');
                if (sel) { sel.disabled = !this.checked; if (!this.checked) sel.value = ''; }
            });
        }
        if (specialCheckbox) {
            specialCheckbox.addEventListener('change', function() {
                var sel = form.querySelector('select[name="survey_special_promo"]');
                if (sel) { sel.disabled = !this.checked; if (!this.checked) sel.value = ''; }
            });
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var nameField = document.getElementById('inquiryName');
            var emailField = document.getElementById('inquiryEmail');
            var phoneField = document.getElementById('inquiryPhone');
            var submitBtn = document.getElementById('summaryInquiryBtn');
            var messageEl = document.getElementById('inquiryFormMessage');
            var rodoCheckbox = document.getElementById('inquiryRodoConsent');
            var rodoWrapper = rodoCheckbox ? rodoCheckbox.closest('.inquiry-rodo-consent') : null;

            // Clear previous errors
            [nameField, emailField].forEach(function(f) {
                f.classList.remove('field-error');
            });
            if (rodoWrapper) rodoWrapper.classList.remove('field-error');
            // Clear survey section errors
            form.querySelectorAll('.inquiry-survey-section').forEach(function(s) {
                s.classList.remove('field-error');
            });
            messageEl.style.display = 'none';

            // Validate
            var hasError = false;
            if (!nameField.value.trim()) { nameField.classList.add('field-error'); hasError = true; }
            if (!emailField.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value.trim())) {
                emailField.classList.add('field-error'); hasError = true;
            }

            // Validate first 4 survey questions (required)
            var requiredSurveys = ['survey_area', 'survey_rooms', 'survey_purchase_status', 'survey_age'];
            requiredSurveys.forEach(function(name) {
                var checked = form.querySelector('input[name="' + name + '"]:checked');
                if (!checked) {
                    var section = form.querySelector('input[name="' + name + '"]');
                    if (section) {
                        var sectionWrapper = section.closest('.inquiry-survey-section');
                        if (sectionWrapper) sectionWrapper.classList.add('field-error');
                    }
                    hasError = true;
                }
            });

            if (rodoCheckbox && !rodoCheckbox.checked) {
                if (rodoWrapper) rodoWrapper.classList.add('field-error');
                hasError = true;
            }

            if (hasError) {
                messageEl.style.display = 'block';
                messageEl.className = 'inquiry-form-message error';
                messageEl.textContent = 'Uzupełnij brakujące informacje.';
                return;
            }

            // Check if configurator has only 1 item - suggest adding more for better offer
            if (hasOnlyOneItemInFavorites()) {
                showBetterOfferPopup(function() {
                    submitInquiryForm(form, nameField, emailField, phoneField, submitBtn, messageEl);
                });
                return;
            }

            submitInquiryForm(form, nameField, emailField, phoneField, submitBtn, messageEl);
        });
    }

    function submitInquiryForm(form, nameField, emailField, phoneField, submitBtn, messageEl) {
            // Collect survey data
            var surveyData = {};
            var areaRadio = form.querySelector('input[name="survey_area"]:checked');
            if (areaRadio) surveyData['Metraż'] = areaRadio.value;
            var roomsRadio = form.querySelector('input[name="survey_rooms"]:checked');
            if (roomsRadio) surveyData['Liczba pokoi'] = roomsRadio.value;
            var purchaseRadio = form.querySelector('input[name="survey_purchase_status"]:checked');
            if (purchaseRadio) surveyData['Kupował od Dom Ełcki'] = purchaseRadio.value;
            var ageRadio = form.querySelector('input[name="survey_age"]:checked');
            if (ageRadio) surveyData['Grupa wiekowa'] = ageRadio.value;

            // Promo checkboxes
            var promoChecked = Array.from(form.querySelectorAll('input[name="survey_promo[]"]:checked'));
            var promoValues = [];
            promoChecked.forEach(function(cb) {
                var val = cb.value;
                if (val === 'dzieci') {
                    var countSel = form.querySelector('select[name="survey_children_count"]');
                    var count = countSel ? countSel.value : '';
                    val = 'Dzieci: ' + (count || 'nie podano liczby');
                } else if (val === 'promocja specjalna') {
                    var promoSel = form.querySelector('select[name="survey_special_promo"]');
                    var promo = promoSel ? promoSel.value : '';
                    val = 'Promocja specjalna: ' + (promo || 'nie wybrano');
                }
                promoValues.push(val);
            });
            if (promoValues.length) surveyData['Promocje'] = promoValues.join('; ');

            // Collect apartment data
            var favorites = getFavoritesOnPage();
            if (!favorites.length) return;

            var lines = [];
            favorites.forEach(function(localId) {
                var found = null;
                document.querySelectorAll('.apartment-item').forEach(function(el) {
                    try {
                        var data = JSON.parse(el.getAttribute('data-modal') || '{}');
                        if (String(data.localId) === String(localId)) {
                            found = data;
                        }
                    } catch(err) {}
                });
                if (found) {
                    var price = found.priceGross ? Number(found.priceGross).toLocaleString('pl-PL') + ' zł' : '-';
                    var area = found.area ? Number(found.area).toLocaleString('pl-PL', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' m²' : '-';
                    var localType = found.localType || 'Lokal';
                    lines.push(localType + ' ' + (found.number || localId) + ' | ' + (found.building || '') + ' | Piętro: ' + (found.floorDisplay || found.floor || '-') + ' | Pow.: ' + area + ' | Cena: ' + price);
                } else {
                    lines.push('ID: ' + localId);
                }
            });

            // Disable button
            submitBtn.disabled = true;
            var originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;flex-shrink:0;animation:spin 1s linear infinite"><circle cx="12" cy="12" r="10" stroke-dasharray="31.4 31.4" stroke-dashoffset="10"/></svg> Wysyłanie...';

            // Send via REST API
            var restUrl = (typeof develogicApartmentsData !== 'undefined' && develogicApartmentsData.restUrl) ? develogicApartmentsData.restUrl : '/wp-json/develogic/v1';
            var nonce = (typeof develogicApartmentsData !== 'undefined' && develogicApartmentsData.nonce) ? develogicApartmentsData.nonce : '';

            fetch(restUrl + '/inquiry', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                body: JSON.stringify({
                    name: nameField.value.trim(),
                    email: emailField.value.trim(),
                    phone: phoneField.value.trim(),
                    survey_data: JSON.stringify(surveyData),
                    apartments: lines.join('\n')
                })
            })
            .then(function(response) { return response.json().then(function(data) { return { ok: response.ok, data: data }; }); })
            .then(function(result) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                messageEl.style.display = 'block';

                if (result.ok && result.data.success) {
                    messageEl.className = 'inquiry-form-message success';
                    messageEl.textContent = 'Zapytanie zostało wysłane pomyślnie!';
                    form.reset();
                    // Re-disable selects after reset
                    form.querySelectorAll('.inquiry-inline-select').forEach(function(s) { s.disabled = true; });
                } else {
                    messageEl.className = 'inquiry-form-message error';
                    messageEl.textContent = (result.data && result.data.message) ? result.data.message : 'Nie udało się wysłać zapytania. Spróbuj ponownie.';
                }
            })
            .catch(function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                messageEl.style.display = 'block';
                messageEl.className = 'inquiry-form-message error';
                messageEl.textContent = 'Wystąpił błąd połączenia. Spróbuj ponownie.';
            });
    }

    function hasOnlyOneItemInFavorites() {
        var favorites = getFavoritesOnPage();
        return favorites.length === 1;
    }

    function showBetterOfferPopup(onContinue) {
        var popup = document.getElementById('betterOfferPopup');
        if (!popup) { onContinue(); return; }

        popup.style.display = 'flex';

        var closePopup = function() {
            popup.style.display = 'none';
            // Remove event listeners
            continueBtn.removeEventListener('click', handleContinue);
            addMoreBtn.removeEventListener('click', handleAddMore);
            closeBtn.removeEventListener('click', handleClose);
            overlay.removeEventListener('click', handleClose);
        };

        var continueBtn = document.getElementById('betterOfferContinue');
        var addMoreBtn = document.getElementById('betterOfferAddMore');
        var closeBtn = popup.querySelector('.better-offer-popup-close');
        var overlay = popup.querySelector('.better-offer-popup-overlay');

        var handleContinue = function() {
            closePopup();
            onContinue();
        };

        var handleAddMore = function() {
            closePopup();
            switchToAllView();
        };

        var handleClose = function() {
            closePopup();
        };

        continueBtn.addEventListener('click', handleContinue);
        addMoreBtn.addEventListener('click', handleAddMore);
        closeBtn.addEventListener('click', handleClose);
        overlay.addEventListener('click', handleClose);
    }

    function checkAndToggleNoFavoritesPlaceholder() {
        const apartmentList = document.querySelector('.apartment-list');
        if (!apartmentList) return;
        
        const onPage = getFavoritesOnPage();
        const hasFavorites = onPage.length > 0;

        if (hasFavorites) {
            apartmentList.classList.remove('has-no-favorites');
        } else {
            apartmentList.classList.add('has-no-favorites');
        }
    }
    
    function updateUrlWithFavorites() {
        const favorites = getFavoritesOnPage();
        if (favorites.length === 0) {
            removeFavoritesFromUrl();
            return;
        }
        
        const favoritesParam = favorites.join(',');
        const url = new URL(window.location.href);
        
        // Set or update favorites parameter while preserving all other params
        url.searchParams.set('favorites', favoritesParam);
        
        // Update URL without reload
        window.history.pushState({ favorites: favorites }, '', url.toString());
    }
    
    function removeFavoritesFromUrl() {
        const url = new URL(window.location.href);
        url.searchParams.delete('favorites');
        
        // Update URL without reload
        window.history.pushState({}, '', url.toString());
    }
    
    function getLocalIdsInDom() {
        const ids = new Set();
        document.querySelectorAll('.apartment-item[data-modal]').forEach(item => {
            try {
                const data = JSON.parse(item.getAttribute('data-modal'));
                if (data.localId) ids.add(String(data.localId));
            } catch (e) {}
        });
        return ids;
    }

    function getFavoritesOnPage() {
        const favorites = getFavorites();
        const domIds = getLocalIdsInDom();
        return favorites.filter(id => domIds.has(String(id)));
    }

    function getWatchedOnPage() {
        const watched = getWatched();
        const domIds = getLocalIdsInDom();
        return watched.filter(id => domIds.has(String(id)));
    }

    function updateFavoritesCount() {
        const favoritesCount = document.getElementById('favoritesCount');
        const headerCount = document.getElementById('headerConfiguratorCount');

        const onPage = getFavoritesOnPage();
        const count = onPage.length;

        if (favoritesCount) {
            favoritesCount.textContent = count + ' ' + (count === 1 ? 'wybrane' : 'wybranych');
        }
        if (headerCount) {
            headerCount.textContent = count > 0 ? count : '';
        }
    }

    // ===========================
    // Configurator Summary
    // ===========================
    function updateConfiguratorSummary() {
        var summary = document.getElementById('configuratorSummary');
        var itemsContainer = document.getElementById('configuratorSummaryItems');
        var totalPriceEl = document.getElementById('configuratorTotalPrice');
        if (!summary || !itemsContainer || !totalPriceEl) return;

        // Only show in favorites view
        var apartmentList = document.querySelector('.apartment-list');
        var isInFavoritesView = apartmentList && apartmentList.classList.contains('hide-favorites');

        var favorites = getFavoritesOnPage();

        if (!isInFavoritesView || favorites.length === 0) {
            summary.classList.remove('visible');
            return;
        }

        // Collect data for each favorite
        var items = [];
        var totalPrice = 0;

        favorites.forEach(function(localId) {
            var found = null;
            document.querySelectorAll('.apartment-item').forEach(function(el) {
                try {
                    var data = JSON.parse(el.getAttribute('data-modal') || '{}');
                    if (String(data.localId) === String(localId)) {
                        found = data;
                    }
                } catch(e) {}
            });

            if (found) {
                var price = found.priceGross ? Number(found.priceGross) : 0;
                var area = found.area ? Number(found.area).toLocaleString('pl-PL', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' m²' : '';
                var label = (found.localType || 'Lokal') + ' ' + (found.number || localId);
                if (area) label += ' (' + area + ')';

                items.push({ label: label, price: price });
                totalPrice += price;
            }
        });

        // Build summary rows
        var html = '';
        items.forEach(function(item) {
            html += '<div class="summary-row">';
            html += '<span class="summary-label">' + item.label + '</span>';
            html += '<span class="summary-value">' + (item.price > 0 ? Number(item.price).toLocaleString('pl-PL') + ' zł' : '-') + '</span>';
            html += '</div>';
        });

        itemsContainer.innerHTML = html;
        totalPriceEl.textContent = totalPrice > 0 ? totalPrice.toLocaleString('pl-PL') + ' zł' : '0 zł';
        summary.classList.add('visible');
    }

    // ===========================
    // Configurator PDF Export
    // ===========================
    (function() {
        var pdfBtn = document.getElementById('configuratorPdfBtn');
        if (!pdfBtn) return;

        pdfBtn.addEventListener('click', function() {
            var favorites = getFavoritesOnPage();
            if (favorites.length === 0) return;

            var items = [];
            var totalPrice = 0;

            favorites.forEach(function(localId) {
                document.querySelectorAll('.apartment-item').forEach(function(el) {
                    try {
                        var data = JSON.parse(el.getAttribute('data-modal') || '{}');
                        if (String(data.localId) === String(localId)) {
                            var price = data.priceGross ? Number(data.priceGross) : 0;
                            items.push({
                                number: data.number || localId,
                                building: data.building || '',
                                localType: data.localType || '',
                                floor: data.floorDisplay || data.floor || '',
                                area: data.area ? Number(data.area).toLocaleString('pl-PL', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' m\u00B2' : '',
                                rooms: data.rooms || '',
                                price: price,
                                priceFormatted: price > 0 ? price.toLocaleString('pl-PL') + ' z\u0142' : '-',
                                priceM2: data.priceM2 ? Number(data.priceM2).toLocaleString('pl-PL') + ' z\u0142/m\u00B2' : ''
                            });
                            totalPrice += price;
                        }
                    } catch(e) {}
                });
            });

            var today = new Date();
            var dateStr = today.toLocaleDateString('pl-PL', { year: 'numeric', month: 'long', day: 'numeric' });

            var rows = '';
            items.forEach(function(item, i) {
                rows += '<tr>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td>' + item.localType + '</td>' +
                    '<td><strong>' + item.number + '</strong></td>' +
                    '<td>' + item.building + '</td>' +
                    '<td>' + item.floor + '</td>' +
                    '<td>' + item.area + '</td>' +
                    '<td>' + item.rooms + '</td>' +
                    '<td style="text-align:right;">' + item.priceFormatted + '</td>' +
                    '</tr>';
            });

            // Collect survey answers for PDF
            var surveyHtml = '';
            var form = document.getElementById('inquiryForm');
            if (form) {
                var surveyRows = [];

                // Contact fields
                var nameField = document.getElementById('inquiryName');
                var emailField = document.getElementById('inquiryEmail');
                var phoneField = document.getElementById('inquiryPhone');
                if (nameField && nameField.value.trim()) surveyRows.push({q: 'Imi\u0119 i nazwisko', a: nameField.value.trim()});
                if (emailField && emailField.value.trim()) surveyRows.push({q: 'Email', a: emailField.value.trim()});
                if (phoneField && phoneField.value.trim()) surveyRows.push({q: 'Telefon', a: phoneField.value.trim()});

                // Radio questions
                var areaRadio = form.querySelector('input[name="survey_area"]:checked');
                if (areaRadio) surveyRows.push({q: 'Jaki metra\u017c wybranego mieszkania Ci\u0119 interesuje?', a: areaRadio.value});
                var roomsRadio = form.querySelector('input[name="survey_rooms"]:checked');
                if (roomsRadio) surveyRows.push({q: 'Ile pokoi ma mie\u0107 Twoje wymarzone mieszkanie?', a: roomsRadio.value});
                var purchaseRadio = form.querySelector('input[name="survey_purchase_status"]:checked');
                if (purchaseRadio) surveyRows.push({q: 'Czy kupowa\u0142e\u015b od firmy Dom E\u0142cki?', a: purchaseRadio.value});
                var ageRadio = form.querySelector('input[name="survey_age"]:checked');
                if (ageRadio) surveyRows.push({q: 'Do kt\u00f3rej grupy wiekowej nale\u017cysz?', a: ageRadio.value});

                // Promo checkboxes
                var promoChecked = Array.from(form.querySelectorAll('input[name="survey_promo[]"]:checked'));
                if (promoChecked.length) {
                    var promoVals = [];
                    promoChecked.forEach(function(cb) {
                        var val = cb.value;
                        if (val === 's\u0142u\u017cby mundurowe') {
                            val = 'Pracownik s\u0142u\u017cb mundurowych';
                        } else if (val === '\u015blub 2025/2026') {
                            val = '\u015alub w 2025 lub 2026 roku';
                        } else if (val === 'dzieci') {
                            var countSel = form.querySelector('select[name="survey_children_count"]');
                            var count = countSel ? countSel.value : '';
                            val = 'Dzieci: ' + (count || 'nie podano liczby');
                        } else if (val === 'promocja specjalna') {
                            var promoSel = form.querySelector('select[name="survey_special_promo"]');
                            var promo = promoSel ? promoSel.value : '';
                            val = 'Promocja specjalna: ' + (promo || 'nie wybrano');
                        } else if (val === 'rezerwacja 7 dni') {
                            val = 'Rezerwacja wybranych lokali na 7 dni';
                        }
                        promoVals.push(val);
                    });
                    surveyRows.push({q: 'Oferta specjalna', a: promoVals.join('; ')});
                }

                if (surveyRows.length > 0) {
                    surveyHtml = '<h2 style="font-size:16px;color:#0066cc;margin-top:30px;margin-bottom:10px;">Ankieta</h2>' +
                        '<table><tbody>';
                    surveyRows.forEach(function(row) {
                        surveyHtml += '<tr><td style="font-weight:600;width:50%;">' + row.q + '</td><td>' + row.a + '</td></tr>';
                    });
                    surveyHtml += '</tbody></table>';
                }
            }

            var htmlContent = '<!DOCTYPE html>' +
                '<html lang="pl"><head><meta charset="UTF-8">' +
                '<title>Oferta - Konfigurator</title>' +
                '<style>' +
                'body { font-family: Arial, Helvetica, sans-serif; color: #333; margin: 40px; font-size: 14px; }' +
                '.header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px; }' +
                '.header-url { font-size: 12px; color: #888; text-align: right; }' +
                'h1 { font-size: 22px; color: #0066cc; margin: 0; }' +
                '.date { color: #888; font-size: 13px; margin-bottom: 30px; }' +
                'table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }' +
                'th { background: #0066cc; color: #fff; padding: 10px 12px; text-align: left; font-size: 13px; }' +
                'td { padding: 9px 12px; border-bottom: 1px solid #e5e7eb; font-size: 13px; }' +
                'tr:nth-child(even) td { background: #f8f9fa; }' +
                '.total-row td { border-top: 2px solid #0066cc; font-weight: 700; font-size: 15px; background: none !important; }' +
                '.footer { margin-top: 40px; font-size: 12px; color: #999; border-top: 1px solid #e5e7eb; padding-top: 12px; }' +
                '@media print { body { margin: 20px; } }' +
                '</style></head><body>' +
                '<div class="header"><h1>Konfigurator oferty</h1><div class="header-url">' + window.location.hostname + '</div></div>' +
                '<div class="date">' + dateStr + '</div>' +
                '<table>' +
                '<thead><tr>' +
                '<th>Lp.</th><th>Typ</th><th>Numer</th><th>Budynek</th><th>Pi\u0119tro</th><th>Powierzchnia</th><th>Pokoje</th><th style="text-align:right;">Cena brutto</th>' +
                '</tr></thead>' +
                '<tbody>' + rows +
                '<tr class="total-row"><td colspan="7" style="text-align:right;">\u0141\u0105czna cena:</td>' +
                '<td style="text-align:right;">' + (totalPrice > 0 ? totalPrice.toLocaleString('pl-PL') + ' z\u0142' : '0 z\u0142') + '</td></tr>' +
                '</tbody></table>' +
                surveyHtml +
                '<div class="footer">Niniejszy dokument ma charakter informacyjny i nie stanowi oferty w rozumieniu Kodeksu Cywilnego.<br>' +
                'Ceny mog\u0105 ulec zmianie.</div>' +
                '</body></html>';

            var pdfWindow = window.open('', '_blank');
            if (!pdfWindow) {
                alert('Odblokuj wyskakuj\u0105ce okna w przegl\u0105darce, aby pobra\u0107 PDF.');
                return;
            }
            pdfWindow.document.write(htmlContent);
            pdfWindow.document.close();
            pdfWindow.onload = function() {
                pdfWindow.print();
            };
        });
    })();

    // ===========================
    // Modal Cart Button
    // ===========================
    function updateModalCartButton() {
        var btn = document.getElementById('modalShowCartBtn');
        var countEl = document.getElementById('modalCartCount');
        if (!btn) return;

        var favorites = getFavoritesOnPage();
        if (favorites.length > 0) {
            btn.style.display = 'flex';
            if (countEl) {
                countEl.textContent = favorites.length;
            }
        } else {
            btn.style.display = 'none';
        }
    }

    // Cart button click — close modal, switch to favorites view
    (function() {
        var btn = document.getElementById('modalShowCartBtn');
        if (!btn) return;

        btn.addEventListener('click', function() {
            // Close the detail modal
            closeApartmentModal();

            // Switch to favorites/configurator view
            var favBtn = document.querySelector('.favorites-toggle-btn[data-toggle-view="favorites"]');
            if (favBtn && !favBtn.classList.contains('active')) {
                favBtn.click();
            }

            // Scroll to the apartment list
            var container = document.querySelector('.develogic-apartments-container');
            if (container) {
                container.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    })();

    // Listen for favorites changes to update summary and cart button
    document.addEventListener('develogic:favorites_changed', function() {
        updateConfiguratorSummary();
        updateFavoritesCount();
        updateModalCartButton();
    });

    // ===========================
    // Share buttons functionality
    // ===========================
    function setupShareButtons() {
        const shareButtons = document.querySelectorAll('.share-btn');
        
        shareButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const shareType = this.getAttribute('data-share');
                shareFavorites(shareType);
            });
        });
    }
    
    function generateShareLink() {
        const favorites = getFavorites();
        
        // If no favorites, return null
        if (favorites.length === 0) {
            return null;
        }
        
        // Return current URL (which should already have favorites if in favorites view)
        return window.location.href;
    }
    
    function shareFavorites(platform) {
        const shareLink = generateShareLink();
        
        if (!shareLink) {
            // Show message that there are no favorites
            alert('Nie masz żadnych wybranych mieszkań do udostępnienia.');
            return;
        }
        
        const title = 'Sprawdź moją listę wybranych mieszkań';
        
        switch (platform) {
            case 'twitter':
                window.open('https://twitter.com/intent/tweet?text=' + encodeURIComponent(title) + '&url=' + encodeURIComponent(shareLink), '_blank', 'width=550,height=420');
                break;
                
            case 'facebook':
                window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareLink), '_blank', 'width=550,height=420');
                break;
                
            case 'email':
                const subject = encodeURIComponent(title);
                const body = encodeURIComponent('Sprawdź moją listę wybranych mieszkań:\n\n' + shareLink);
                window.location.href = 'mailto:?subject=' + subject + '&body=' + body;
                break;
        }
    }
    
    // ===========================
    // Check for shared favorites
    // ===========================
    function checkSharedFavorites() {
        const urlParams = new URLSearchParams(window.location.search);
        const sharedFavorites = urlParams.get('favorites');
        
        if (sharedFavorites) {
            // Decode and parse the favorites list
            const favoritesList = sharedFavorites.split(',');
            
            // Add all shared favorites to localStorage
            const currentFavorites = getFavorites();
            favoritesList.forEach(fav => {
                if (currentFavorites.indexOf(fav) === -1) {
                    currentFavorites.push(fav);
                }
            });
            saveFavorites(currentFavorites);
            
            // Refresh the favorite state
            loadFavoritesState();
            updateFavoritesCount();
            
            // Switch to favorites view without triggering URL update
            const apartmentList = document.querySelector('.apartment-list');
            const shareContainer = document.getElementById('favoritesShareContainer');
            const toggleButtons = document.querySelectorAll('.favorites-toggle-btn');

            // Reset filters to show all types
            resetFilters();
            const localTypeFilter = document.getElementById('localTypeFilter');
            if (localTypeFilter) {
                localTypeFilter.value = 'all';
                applyFilters();
            }

            if (apartmentList) {
                apartmentList.classList.add('hide-favorites');
            }
            if (shareContainer) {
                shareContainer.style.display = 'flex';
            }
            toggleButtons.forEach(b => {
                b.classList.toggle('active', b.getAttribute('data-toggle-view') === 'favorites');
            });
            
            // Show a message
            setTimeout(() => {
                const container = document.getElementById('toastContainer');
                if (container) {
                    const toast = document.createElement('div');
                    toast.className = 'toast';
                    toast.innerHTML = `
                        <div class="toast-icon"></div>
                        <div class="toast-content">
                            <div class="toast-title">Dodano ${favoritesList.length} ${favoritesList.length === 1 ? 'mieszkanie' : ' mieszkań'} z udostępnionej listy</div>
                        </div>
                    `;
                    
                    container.appendChild(toast);
                    
                    setTimeout(() => {
                        toast.classList.add('show');
                    }, 10);
                    
                    setTimeout(() => {
                        toast.classList.add('hide');
                        setTimeout(() => {
                            if (container.contains(toast)) {
                                container.removeChild(toast);
                            }
                        }, 300);
                    }, 4000);
                }
            }, 500);
        }
    }
    
    // ===========================
    // Purchase Configurator Wizard
    // ===========================

    var wizardState = null;

    function setupWizard() {
        const wizardModal = document.getElementById('wizardModal');
        if (!wizardModal) return;

        // Close button
        wizardModal.querySelector('.wizard-modal-close').addEventListener('click', closeWizardModal);

        // Overlay click to close
        wizardModal.querySelector('.wizard-modal-overlay').addEventListener('click', closeWizardModal);

        // Step 1: Find apartment button
        wizardModal.querySelector('[data-wizard-action="find-apartment"]').addEventListener('click', function() {
            closeWizardModal();

            // Switch to "Wszystkie" view
            switchToAllView();

            // Set filter to "Lokal mieszkalny"
            setLocalTypeFilter('Lokal mieszkalny');
        });

        // Step 2: Find garage button
        wizardModal.querySelector('[data-wizard-action="find-garage"]').addEventListener('click', function() {
            closeWizardModal();

            switchToAllView();
            setLocalTypeFilter('Garaż');
        });

        // Step 2: Find parking button
        var parkingBtn = wizardModal.querySelector('[data-wizard-action="find-parking"]');
        if (parkingBtn) {
            parkingBtn.addEventListener('click', function() {
                wizardState = 'finding_storage';

                closeWizardModal();

                switchToAllView();
                setLocalTypeFilter('Miejsce postojowe');
            });
        }

        // Step 2: Find storage/cellar button
        wizardModal.querySelector('[data-wizard-action="find-storage"]').addEventListener('click', function() {
            closeWizardModal();

            switchToAllView();

            // Set filter to Komórka lokatorska
            const localTypeFilter = document.getElementById('localTypeFilter');
            if (localTypeFilter) {
                const options = Array.from(localTypeFilter.options).map(o => o.value);
                if (options.includes('Komórka lokatorska')) {
                    setLocalTypeFilter('Komórka lokatorska');
                }
            }
        });

        // Step 3: Send inquiry
        wizardModal.querySelector('[data-wizard-action="send-inquiry"]').addEventListener('click', function() {
            closeWizardModal();

            // Switch to favorites view and scroll to inquiry form
            switchToFavoritesView();

            setTimeout(function() {
                var formWrapper = document.getElementById('inquiryFormWrapper');
                if (formWrapper) {
                    formWrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 300);
        });

        // Step 3: Back to list
        wizardModal.querySelector('[data-wizard-action="back-to-list"]').addEventListener('click', function() {
            closeWizardModal();

            switchToAllView();
            setLocalTypeFilter('Lokal mieszkalny');
        });

        // All steps: Go to cart buttons
        wizardModal.querySelectorAll('[data-wizard-action="go-to-cart"]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                wizardState = null;
                closeWizardModal();
                switchToFavoritesView();
            });
        });

        // Update cart buttons on favorites change
        document.addEventListener('develogic:favorites_changed', updateWizardCartButtons);
    }

    function updateWizardCartButtons() {
        var favorites = getFavorites();
        var count = favorites.length;
        var cartBtns = document.querySelectorAll('.wizard-go-to-cart');

        cartBtns.forEach(function(btn) {
            if (count > 0) {
                btn.style.display = 'inline-flex';
                var countEl = btn.querySelector('.wizard-cart-count');
                if (countEl) {
                    countEl.textContent = count;
                }
            } else {
                btn.style.display = 'none';
            }
        });
    }

    function showWizardStep(stepNumber) {
        const wizardModal = document.getElementById('wizardModal');
        if (!wizardModal) return;

        // Hide all steps
        wizardModal.querySelectorAll('.wizard-step').forEach(function(step) {
            step.style.display = 'none';
        });

        // Show requested step
        const targetStep = wizardModal.querySelector('[data-wizard-step="' + stepNumber + '"]');
        if (targetStep) {
            targetStep.style.display = 'flex';
        }

        // Update step content based on current favorites
        if (stepNumber === 1) {
            updateWizardStep1();
        }
        if (stepNumber === 2) {
            updateWizardStep2();
        }

        // Update cart buttons visibility
        updateWizardCartButtons();

        // Show modal
        wizardModal.style.display = 'flex';
        // Force reflow for transition
        wizardModal.offsetHeight;
        wizardModal.classList.add('visible');
        document.body.style.overflow = 'hidden';
    }

    function closeWizardModal() {
        const wizardModal = document.getElementById('wizardModal');
        if (!wizardModal) return;

        // Close detail modal if open (wizard can appear on top of details)
        closeApartmentModal();

        wizardModal.classList.remove('visible');
        document.body.style.overflow = '';
        // Re-fix sticky ancestors after modal close
        fixStickyAncestors();

        setTimeout(function() {
            wizardModal.style.display = 'none';
        }, 300);
    }

    function switchToAllView() {
        const allBtn = document.querySelector('.favorites-toggle-btn[data-toggle-view="all"]');
        if (allBtn && !allBtn.classList.contains('active')) {
            allBtn.click();
        }
    }

    function switchToFavoritesView() {
        const favBtn = document.querySelector('.favorites-toggle-btn[data-toggle-view="favorites"]');
        if (favBtn && !favBtn.classList.contains('active')) {
            favBtn.click();
        }
    }

    function setLocalTypeFilter(value) {
        const localTypeFilter = document.getElementById('localTypeFilter');
        if (localTypeFilter) {
            const options = Array.from(localTypeFilter.options).map(o => o.value);
            if (options.includes(value)) {
                localTypeFilter.value = value;
                autoSelectFloorForKLPG();
                applyFilters();
            }
        }
    }

    /**
     * Returns an object describing which local types are present in favorites.
     */
    function getFavoriteTypes() {
        var favorites = getFavorites();
        var result = { hasApartment: false, hasGarage: false, hasParking: false, hasStorage: false };

        // Build a map of localId → localType from all apartment-item elements in the DOM
        var typeMap = {};
        document.querySelectorAll('.apartment-item[data-modal]').forEach(function(item) {
            try {
                var data = JSON.parse(item.getAttribute('data-modal'));
                if (data && data.localId) {
                    typeMap[String(data.localId)] = item.getAttribute('data-local-type') || '';
                }
            } catch (e) {}
        });

        favorites.forEach(function(localId) {
            var localType = typeMap[String(localId)] || '';
            if (localType === 'Lokal mieszkalny') result.hasApartment = true;
            else if (localType === 'Garaż') result.hasGarage = true;
            else if (localType === 'Miejsce postojowe') result.hasParking = true;
            else if (localType === 'Komórka lokatorska') result.hasStorage = true;
        });

        return result;
    }

    /**
     * Updates step 2 content based on what's already in favorites.
     * Shows only the buttons for types that are still missing.
     */
    function updateWizardStep2() {
        var wizardModal = document.getElementById('wizardModal');
        if (!wizardModal) return;
        var step2 = wizardModal.querySelector('[data-wizard-step="2"]');
        if (!step2) return;

        var types = getFavoriteTypes();
        var garageBtn = step2.querySelector('[data-wizard-action="find-garage"]');
        var parkingBtn = step2.querySelector('[data-wizard-action="find-parking"]');
        var storageBtn = step2.querySelector('[data-wizard-action="find-storage"]');
        var description = step2.querySelector('p');

        if (garageBtn) garageBtn.style.display = types.hasGarage ? 'none' : 'inline-flex';
        if (parkingBtn) parkingBtn.style.display = types.hasParking ? 'none' : 'inline-flex';
        if (storageBtn) storageBtn.style.display = types.hasStorage ? 'none' : 'inline-flex';

        if (description) {
            var missing = [];
            if (!types.hasGarage) missing.push('garaż');
            if (!types.hasParking) missing.push('miejsce postojowe na zewnątrz');
            if (!types.hasStorage) missing.push('komórkę lokatorską lub pom. gospodarcze');
            if (missing.length > 0) {
                description.textContent = 'Świetny wybór! Teraz uzupełnij swoją ofertę o ' + missing.join(', ') + '.';
            }
        }
    }

    /**
     * Updates step 1 content based on whether configurator already has non-apartment items.
     * Shows appropriate heading and description so user isn't told the configurator is empty
     * when they've already added a parking spot, garage, or storage unit.
     */
    function updateWizardStep1() {
        var wizardModal = document.getElementById('wizardModal');
        if (!wizardModal) return;
        var step1 = wizardModal.querySelector('[data-wizard-step="1"]');
        if (!step1) return;

        var favorites = getFavoritesOnPage();
        var h3 = step1.querySelector('h3');
        var p = step1.querySelector('p');

        if (favorites.length > 0) {
            if (h3) h3.textContent = 'Teraz wybierz mieszkanie';
            if (p) p.textContent = 'Lokal został dodany do konfiguratora. Uzupełnij swój wybór o wymarzone mieszkanie z naszej oferty.';
        } else {
            if (h3) h3.textContent = 'Dodaj pierwsze mieszkanie';
            if (p) p.textContent = 'Twój konfigurator zakupu jest pusty. Zacznij od wybrania wymarzonego mieszkania z naszej oferty.';
        }
    }

    /**
     * Called when user clicks on "Konfigurator oferty" tab.
     * If empty, show wizard step 1 instead of empty list.
     * Returns true if wizard was shown (to prevent normal toggle behavior).
     */
    function handleConfiguratorClick() {
        const favorites = getFavoritesOnPage();
        if (favorites.length === 0) {
            showWizardStep(1);
            return true;
        }
        return false;
    }

    /**
     * Called after a favorite is added. Handles wizard flow transitions.
     */
    function handleWizardAfterFavoriteAdd() {
        // Always show the wizard after adding to configurator with the correct step
        setTimeout(function() {
            var types = getFavoriteTypes();

            if (types.hasApartment) {
                // Has apartment - check what else is needed
                var needsMore = !types.hasGarage || !types.hasParking || !types.hasStorage;
                if (needsMore) {
                    showWizardStep(2);
                } else {
                    showWizardStep(3);
                }
            } else {
                // No apartment yet - show step 1 (find apartment)
                showWizardStep(1);
            }
        }, 400);
    }

})();
