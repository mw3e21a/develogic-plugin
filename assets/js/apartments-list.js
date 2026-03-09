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
        setupSorting();
        setupFiltering();
        setupFavorites();
        setupEmailButtons();
        setupApartmentClicks();
        setupModal();
        setupFavoritesViewToggle();
        setupShareButtons();
        updateFavoritesCount();
        checkSharedFavorites();
        setupImageMapProArtboardLogging();
        
        // Apply URL filters first
        applyUrlFilters();
        
        // Apply filters on page load to respect default localType selection
        applyFilters();
        
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
        const sortOptions = document.querySelectorAll('.sort-option');
        let currentSort = 'data-floor';
        let currentDirection = 'asc';
        
        sortOptions.forEach(option => {
            option.addEventListener('click', function() {
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
            const aVal = parseInt(a.getAttribute(sortAttr)) || 0;
            const bVal = parseInt(b.getAttribute(sortAttr)) || 0;
            
            if (sortDir === 'asc') {
                return aVal - bVal;
            } else {
                return bVal - aVal;
            }
        });
        
        // Re-append sorted items
        items.forEach(item => apartmentList.appendChild(item));
    }
    
    // ===========================
    // URL Filters functionality
    // ===========================
    
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
            const numberEl = item.querySelector('.apartment-number');
            if (numberEl && numberEl.textContent.trim().toUpperCase() === apartmentNumber.toUpperCase()) {
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
        
        // Scroll to apartment with smooth behavior
        setTimeout(function() {
            targetApartment.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
            
            // Add highlight class
            targetApartment.classList.add('apartment-highlight');
            
            // Remove highlight after 3 seconds
            setTimeout(function() {
                targetApartment.classList.remove('apartment-highlight');
            }, 3000);
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
        
        // Additional options from URL - dynamic based on available filters
        // Map URL parameter names to option keys
        const urlParamToOptionKey = {
            'promocja': 'promo',
            '2_lazienki': '2bath',
            'garderoba': 'wardrobe',
            'widok_na_jezioro': 'lake_view',
            'balkon': 'balcony',
            '2_balkony': '2balconies',
            'taras': 'terrace',
            'ogrod': 'garden',
            'aneks_kuchenny': 'kitchen_annex',
            'jasna_kuchnia': 'bright_kitchen',
            'winda': 'elevator',
            'osobne_wc': 'separate_wc',
            'pom_gospodarcze': 'storage',
            'komorka_lokatorska': 'cellar',
            'klimatyzacja': 'air_conditioning',
            'parking': 'parking',
            'miejsce_postojowe': 'parking_space',
            'plac_zabaw': 'playground'
        };
        
        // Apply URL parameters to additional options filters
        for (const [urlParam, optionKey] of Object.entries(urlParamToOptionKey)) {
            if (urlParams.has(urlParam)) {
                const paramValue = urlParams.get(urlParam);
                const filter = document.querySelector(`.filter-extras input[data-option-key="${optionKey}"]`);
                if (filter && (paramValue === '1' || paramValue === 'true')) {
                    filter.checked = true;
                }
            }
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
            floorFilter.addEventListener('change', applyFilters);
        }
        
        // Initialize dynamic floor filtering if enabled
        updateFloorOptions();
        
        // Ensure floor options are available for KL/PG/Garaż on initial load
        autoSelectFloorForKLPG();
        
        // Area range filters
        const areaMin = document.getElementById('areaMin');
        const areaMax = document.getElementById('areaMax');
        if (areaMin) areaMin.addEventListener('input', debounce(applyFilters, 500));
        if (areaMax) areaMax.addEventListener('input', debounce(applyFilters, 500));
        
        // Price range filters
        const priceMin = document.getElementById('priceMin');
        const priceMax = document.getElementById('priceMax');
        if (priceMin) priceMin.addEventListener('input', debounce(applyFilters, 500));
        if (priceMax) priceMax.addEventListener('input', debounce(applyFilters, 500));
        
        // Additional options checkboxes - dynamic based on settings
        const additionalOptionFilters = document.querySelectorAll('.filter-extras input[type="checkbox"][data-option-key]');
        additionalOptionFilters.forEach(filter => {
            filter.addEventListener('change', applyFilters);
        });
        
        // Reset button
        const resetBtn = document.getElementById('resetFilters');
        if (resetBtn) {
            resetBtn.addEventListener('click', resetFilters);
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
        const typesWithAutoFloor = ['Komórka lokatorska', 'Pomieszczenie gospodarcze', 'Garaż'];
        
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
        
        // Only auto-select floor if current value is "all" AND we have KL/PG/Garaż type selected
        // This way we don't override explicit floor selections from shortcode
        if (currentFloorValue === 'all' && typesWithAutoFloor.includes(selectedLocalType)) {
            // If only one floor exists for KL/PG/Garaż, auto-select it
            if (klPgFloors.length === 1) {
                const targetFloor = String(klPgFloors[0]);
                
                // Check if this floor option exists in the select (it should now)
                const floorOption = Array.from(floorFilter.options).find(opt => opt.value === targetFloor);
                if (floorOption) {
                    floorFilter.value = targetFloor;
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
        // Get all checked additional options dynamically
        const checkedAdditionalOptions = {};
        const additionalOptionFilters = document.querySelectorAll('.filter-extras input[type="checkbox"][data-option-key]');
        additionalOptionFilters.forEach(filter => {
            const optionKey = filter.getAttribute('data-option-key');
            if (optionKey && filter.checked) {
                checkedAdditionalOptions[optionKey] = true;
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
            
            // Additional options filters - dynamic based on settings
            const attributes = JSON.parse(item.getAttribute('data-attributes') || '[]');
            
            // Map option keys to attribute matching patterns
            const optionAttributePatterns = {
                'promo': {
                    checkPromo: true, // Special handling for promo
                    patterns: ['promocja']
                },
                '2bath': {
                    patterns: ['2 lazienki', 'dwie lazienki', '2 lazienk']
                },
                'wardrobe': {
                    patterns: ['garderoba']
                },
                'lake_view': {
                    patterns: ['widok na jezioro', 'widok na jezior', 'jezioro']
                },
                'balcony': {
                    patterns: ['balkon']
                },
                '2balconies': {
                    patterns: ['2 balkony', 'dwa balkony', '2 balkon']
                },
                'terrace': {
                    patterns: ['taras']
                },
                'garden': {
                    patterns: ['ogród', 'ogrodek']
                },
                'kitchen_annex': {
                    patterns: ['aneks kuchenny', 'aneks']
                },
                'bright_kitchen': {
                    patterns: ['jasna kuchnia']
                },
                'elevator': {
                    patterns: ['winda']
                },
                'separate_wc': {
                    patterns: ['osobne wc', 'osobne WC']
                },
                'storage': {
                    patterns: ['pom. gospodarcze', 'pomieszczenie gospodarcze']
                },
                'cellar': {
                    patterns: ['komórka lokatorska', 'komorka lokatorska']
                },
                'air_conditioning': {
                    patterns: ['klimatyzacja']
                },
                'parking': {
                    patterns: ['parking']
                },
                'parking_space': {
                    patterns: ['miejsce postojowe']
                },
                'playground': {
                    patterns: ['plac zabaw']
                }
            };
            
            // Check each selected additional option
            for (const optionKey in checkedAdditionalOptions) {
                if (checkedAdditionalOptions[optionKey]) {
                    const optionConfig = optionAttributePatterns[optionKey];
                    if (!optionConfig) continue;
                    
                    let hasMatch = false;
                    
                    // Special handling for promo
                    if (optionKey === 'promo' && optionConfig.checkPromo) {
                        const hasPromoDiscount = item.getAttribute('data-has-promo') === 'true';
                        const hasPromoAttribute = attributes.some(attr => {
                            const attrName = typeof attr === 'string' ? attr : (attr.name || '');
                            const attrLower = attrName.toLowerCase().trim();
                            return attrLower === 'promocja' || attrLower.includes('promocja');
                        });
                        hasMatch = hasPromoDiscount || hasPromoAttribute;
                    } else {
                        // Check attributes against patterns
                        hasMatch = attributes.some(attr => {
                            const attrName = typeof attr === 'string' ? attr : (attr.name || '');
                            const attrLower = attrName.toLowerCase().trim();
                            return optionConfig.patterns.some(pattern => {
                                return attrLower === pattern || attrLower.includes(pattern);
                            });
                        });
                    }
                    
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
        
        // Show/hide no results message
        updateNoResultsMessage(visibleCount);
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
            // Reset to "Lokal mieszkalny" if available, otherwise "Garaż", otherwise "all"
            const lokalMieszkalnyOption = Array.from(localTypeFilter.options).find(opt => opt.value === 'Lokal mieszkalny');
            if (lokalMieszkalnyOption) {
                localTypeFilter.value = 'Lokal mieszkalny';
            } else {
                const garazOption = Array.from(localTypeFilter.options).find(opt => opt.value === 'Garaż');
                localTypeFilter.value = garazOption ? 'Garaż' : 'all';
            }
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
        
        // Reset additional options checkboxes - dynamic
        const additionalOptionFilters = document.querySelectorAll('.filter-extras input[type="checkbox"][data-option-key]');
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
        document.querySelectorAll('.icon-btn[data-local-id="' + localId + '"]').forEach(b => {
            b.classList.toggle('favorited', isAdding);
        });
        
        // Update apartment item favorite class
        document.querySelectorAll('.apartment-item').forEach(item => {
            const modalData = item.getAttribute('data-modal');
            if (modalData) {
                try {
                    const data = JSON.parse(modalData);
                    if (data.localId === localId) {
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
        }
    }
    
    function getFavorites() {
        const favorites = localStorage.getItem('develogic_favorites');
        return favorites ? JSON.parse(favorites) : [];
    }
    
    function saveFavorites(favorites) {
        localStorage.setItem('develogic_favorites', JSON.stringify(favorites));
    }
    
    function loadFavoritesState() {
        const favorites = getFavorites();
        
        favorites.forEach(localId => {
            document.querySelectorAll('.icon-btn[data-local-id="' + localId + '"]').forEach(btn => {
                btn.classList.add('favorited');
            });
            
            // Mark apartment items as favorites
            document.querySelectorAll('.apartment-item').forEach(item => {
                const modalData = item.getAttribute('data-modal');
                if (modalData) {
                    try {
                        const data = JSON.parse(modalData);
                        if (data.localId === localId) {
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
    function setupApartmentClicks() {
        document.querySelectorAll('.apartment-item').forEach(item => {
            item.addEventListener('click', function(e) {
                // Don't open modal if clicking on buttons
                if (e.target.closest('.icon-btn')) {
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
                    'Pomieszczenie gospodarcze': 'Pomieszczenie gospodarcze',
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
                <div class="toast-title">Dodano do obserwowanych</div>
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
                
                // Update button active states
                toggleButtons.forEach(b => b.classList.toggle('active', b === this));
                
                // Toggle apartment list classes
                if (view === 'favorites') {
                    // Reset filters to show all observed apartments
                    resetFilters();
                    apartmentList.classList.add('hide-favorites');
                    // Check if there are any favorites
                    checkAndToggleNoFavoritesPlaceholder();
                    // Show share buttons when in favorites view
                    if (shareContainer) {
                        shareContainer.style.display = 'flex';
                    }
                    // Update URL with favorites
                    updateUrlWithFavorites();
                } else {
                    apartmentList.classList.remove('hide-favorites');
                    apartmentList.classList.remove('has-no-favorites');
                    // Hide share buttons when in all view
                    if (shareContainer) {
                        shareContainer.style.display = 'none';
                    }
                    // Remove favorites from URL
                    removeFavoritesFromUrl();
                }
            });
        });
    }
    
    function checkAndToggleNoFavoritesPlaceholder() {
        const apartmentList = document.querySelector('.apartment-list');
        if (!apartmentList) return;
        
        const favorites = getFavorites();
        const hasFavorites = favorites.length > 0;
        
        if (hasFavorites) {
            apartmentList.classList.remove('has-no-favorites');
        } else {
            apartmentList.classList.add('has-no-favorites');
        }
    }
    
    function updateUrlWithFavorites() {
        const favorites = getFavorites();
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
    
    function updateFavoritesCount() {
        const favoritesCount = document.getElementById('favoritesCount');
        if (!favoritesCount) return;
        
        const favorites = getFavorites();
        const count = favorites.length;
        
        favoritesCount.textContent = count + ' ' + (count === 1 ? 'obserwowane' : 'obserwowanych');
    }
    
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
            alert('Nie masz żadnych obserwowanych mieszkań do udostępnienia.');
            return;
        }
        
        const title = 'Sprawdź moją listę obserwowanych mieszkań';
        
        switch (platform) {
            case 'twitter':
                window.open('https://twitter.com/intent/tweet?text=' + encodeURIComponent(title) + '&url=' + encodeURIComponent(shareLink), '_blank', 'width=550,height=420');
                break;
                
            case 'facebook':
                window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareLink), '_blank', 'width=550,height=420');
                break;
                
            case 'email':
                const subject = encodeURIComponent(title);
                const body = encodeURIComponent('Sprawdź moją listę obserwowanych mieszkań:\n\n' + shareLink);
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
    
})();
