/**
 * Apartments List JavaScript
 * Wzorowane na Wasilewski Developer
 * Obsługa listy mieszkań z Shuffle.js, lightGallery i Tippy.js
 * @package Develogic
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize dla każdej instancji listy
        $('.develogic-apartments-list-container').each(function() {
            var $container = $(this);
            var instanceId = $container.attr('id');
            var $grid = $container.find('.apartments-list');
            var $dropdown = $container.find('.dropdown-xs');
            var $sortLinks = $container.find('.sort-links');
            var currentSort = 'data-floor';
            var currentDirection = 'asc';
            
            // Show/hide desktop links vs mobile dropdown based on screen size
            function checkScreenSize() {
                if ($(window).width() >= 1025) {
                    $sortLinks.show();
                    $dropdown.hide();
                } else {
                    $sortLinks.hide();
                    $dropdown.show();
                }
            }
            
            checkScreenSize();
            $(window).on('resize', checkScreenSize);
            
            // Initialize Shuffle.js (opcjonalnie, jeśli użytkownik chce filtrowania)
            var shuffleInstance = null;
            if (typeof Shuffle !== 'undefined') {
                shuffleInstance = new Shuffle($grid[0], {
                    itemSelector: '.apartment-item',
                    speed: 500,
                    easing: 'cubic-bezier(0.4, 0.0, 0.2, 1)',
                });
            }
            
            // Initialize lightGallery dla każdego mieszkania
            $('.apartment-item').each(function() {
                var $apartment = $(this);
                var galleryId = $apartment.attr('id');
                var $images = $apartment.find('.link-img');
                
                if ($images.length > 0 && typeof lightGallery !== 'undefined') {
                    // Przygotuj elementy dla lightGallery
                    var galleryItems = [];
                    $images.each(function() {
                        var $img = $(this);
                        galleryItems.push({
                            src: $img.attr('href'),
                            thumb: $img.find('img').attr('src') || $img.attr('href'),
                        });
                    });
                    
                    // Initialize lightGallery na klikalne obrazy
                    var lgInstance = lightGallery($apartment[0], {
                        selector: '.link-img:not(.hidden)',
                        plugins: [lgThumbnail, lgZoom, lgFullscreen, lgHash],
                        speed: 500,
                        thumbnail: true,
                        animateThumb: true,
                        showThumbByDefault: true,
                        hash: true,
                        galleryId: galleryId,
                    });
                }
            });
            
            // Initialize Tippy.js tooltips
            if (typeof tippy !== 'undefined') {
                tippy('.tippy', {
                    placement: 'top',
                    arrow: true,
                    animation: 'scale',
                });
            }
            
            // Desktop sort links
            $sortLinks.on('click', '.sort-link', function(e) {
                e.preventDefault();
                
                var $link = $(this);
                var sortAttr = $link.data('sort');
                
                // Toggle direction if same sort
                if (sortAttr === currentSort) {
                    currentDirection = (currentDirection === 'asc') ? 'desc' : 'asc';
                } else {
                    currentDirection = 'asc';
                    currentSort = sortAttr;
                }
                
                // Update link states
                $sortLinks.find('.sort-link').removeClass('active asc desc');
                $link.addClass('active').addClass(currentDirection);
                
                // Perform sort
                performSort(currentSort, currentDirection);
            });
            
            // Dropdown toggle
            $dropdown.on('click', '.dropdown-label-xs', function(e) {
                e.preventDefault();
                $dropdown.toggleClass('on');
            });
            
            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$dropdown.is(e.target) && $dropdown.has(e.target).length === 0) {
                    $dropdown.removeClass('on');
                }
            });
            
            // Sortowanie przez dropdown (mobile)
            $dropdown.on('click', '.btn-sort', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $btn = $(this);
                var sortAttr = $btn.data('sort');
                
                // Toggle direction if same sort
                if (sortAttr === currentSort) {
                    currentDirection = (currentDirection === 'asc') ? 'desc' : 'asc';
                } else {
                    currentDirection = 'asc';
                    currentSort = sortAttr;
                }
                
                // Update button states
                $dropdown.find('.btn-sort').removeClass('asc desc');
                $btn.addClass(currentDirection);
                
                // Perform sort
                performSort(currentSort, currentDirection);
                
                // Close dropdown
                $dropdown.removeClass('on');
            });
            
            function performSort(sortAttr, sortDir) {
                var $items = $grid.find('.aprtment-item');
                
                $items.sort(function(a, b) {
                    var aVal = parseInt($(a).attr(sortAttr)) || 0;
                    var bVal = parseInt($(b).attr(sortAttr)) || 0;
                    
                    if (sortDir === 'asc') {
                        return aVal - bVal;
                    } else {
                        return bVal - aVal;
                    }
                });
                
                // Przepisz elementy w nowej kolejności
                $items.detach().appendTo($grid);
                
                // Jeśli używamy Shuffle, zaktualizuj go
                if (shuffleInstance) {
                    shuffleInstance.update();
                }
            }
            
            // Obsługa przycisków "obserwuj" (ulubione)
            $container.on('click', '.btn-observe', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var localId = $btn.data('local-id');
                
                // Toggle active state
                $btn.toggleClass('active');
                
                // Zapisz w localStorage
                var favorites = getFavorites();
                var index = favorites.indexOf(localId);
                
                if (index === -1) {
                    // Dodaj do ulubionych
                    favorites.push(localId);
                    if (typeof tippy !== 'undefined') {
                        var tippyInstance = $btn[0]._tippy;
                        if (tippyInstance) {
                            tippyInstance.setContent(develogicApartmentsData.i18n.obserwujesz || 'obserwujesz');
                        }
                    }
                } else {
                    // Usuń z ulubionych
                    favorites.splice(index, 1);
                    if (typeof tippy !== 'undefined') {
                        var tippyInstance = $btn[0]._tippy;
                        if (tippyInstance) {
                            tippyInstance.setContent(develogicApartmentsData.i18n.obserwuj || 'obserwuj');
                        }
                    }
                }
                
                saveFavorites(favorites);
            });
            
            // Załaduj ulubione z localStorage przy inicjalizacji
            loadFavoritesState();
        });
        
        // Funkcje pomocnicze dla localStorage
        function getFavorites() {
            var favorites = localStorage.getItem('develogic_favorites');
            return favorites ? JSON.parse(favorites) : [];
        }
        
        function saveFavorites(favorites) {
            localStorage.setItem('develogic_favorites', JSON.stringify(favorites));
        }
        
        function loadFavoritesState() {
            var favorites = getFavorites();
            
            favorites.forEach(function(localId) {
                var $btn = $('.btn-observe[data-local-id="' + localId + '"]');
                $btn.addClass('active');
                
                if (typeof tippy !== 'undefined') {
                    var tippyInstance = $btn[0]._tippy;
                    if (tippyInstance) {
                        tippyInstance.setContent(develogicApartmentsData.i18n.obserwujesz || 'obserwujesz');
                    }
                }
            });
        }
        
    });
    
})(jQuery);

