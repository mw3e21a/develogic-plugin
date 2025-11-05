/**
 * Apartments List JavaScript
 * Nowy layout zgodny z apartment-list.html i apartment-detail.html
 * @package Develogic
 */

(function() {
    'use strict';
    
    // Wait for DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        setupSorting();
        setupFavorites();
        setupEmailButtons();
        setupApartmentClicks();
        setupModal();
        setupFavoritesViewToggle();
        setupShareButtons();
        updateFavoritesCount();
        checkSharedFavorites();
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
                // Remove active class from all options
                sortOptions.forEach(opt => opt.classList.remove('active'));
                // Add active class to clicked option
                this.classList.add('active');
                
                const sortAttr = this.getAttribute('data-sort');
                
                // Toggle direction if same sort
                if (sortAttr === currentSort) {
                    currentDirection = (currentDirection === 'asc') ? 'desc' : 'asc';
                } else {
                    currentDirection = 'asc';
                    currentSort = sortAttr;
                }
                
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
    
    function handleEmail(apartmentNumber) {
        const developerName = window.develogicApartmentsData?.developer_name || '';
        const contactEmail = window.develogicApartmentsData?.contact_email || '';
        
        const subject = encodeURIComponent('Mieszkanie ' + apartmentNumber + ' – ' + developerName);
        const body = encodeURIComponent('\n---\n' + window.location.href);
        
        window.location.href = 'mailto:' + contactEmail + '?Subject=' + subject + '&body=' + body;
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
            headerTitle += (headerTitle ? ' - ' : '') + 'Mieszkanie ' + data.number;
        }
        modal.querySelector('.modal-title').textContent = headerTitle || 'Szczegóły mieszkania';
        
        // Set location
        let locationText = '';
        if (data.building) {
            locationText = 'Budynek ' + data.building;
        }
        if (data.subdivision) {
            locationText += (locationText ? '<br>' : '') + data.subdivision;
        }
        modal.querySelector('.location').innerHTML = locationText;
        
        // Set unit name
        modal.querySelector('.unit-name').textContent = data.number || '';
        
        // Set status
        const statusEl = modal.querySelector('.status');
        if (data.statusClass === 'available') {
            statusEl.innerHTML = '<span style="color: #00b341;">Dostępne</span> od ręki';
        } else if (data.statusClass === 'reserved') {
            statusEl.innerHTML = '<span style="color: #ff9500;">Rezerwacja</span>';
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
        
        // Add omnibus price if available
        if (data.omnibusPriceGross && data.omnibusPriceGross > 0) {
            const omnibusPriceText = formatPrice(data.omnibusPriceGross);
            if (data.omnibusPriceGrossm2 && data.omnibusPriceGrossm2 > 0) {
                const omnibusPriceM2Text = formatPriceM2(data.omnibusPriceGrossm2);
                addSpecRow(detailSpecs, 'Cena omnibus', omnibusPriceText + ' (' + omnibusPriceM2Text + ' zł/m²)');
            } else {
                addSpecRow(detailSpecs, 'Cena omnibus', omnibusPriceText);
            }
        }
        
        // Set features
        const featuresEl = modal.querySelector('.detail-features');
        if (data.tags && data.tags.length > 0) {
            featuresEl.textContent = data.tags.join(', ');
        } else {
            featuresEl.textContent = '';
        }
        
        // Set price
        modal.querySelector('.detail-price .price-main').textContent = formatPrice(data.priceGross);
        modal.querySelector('.detail-price .price-per-m2').textContent = '(' + formatPriceM2(data.priceM2) + ' zł/m²)';
        
        // Set info box
        const infoBox = modal.querySelector('.info-box');
        if (data.plannedDate) {
            const infoText = 'Planowane oddanie budynku - ' + formatDate(data.plannedDate);
            modal.querySelector('.info-text').textContent = infoText;
            infoBox.style.display = 'block';
        } else {
            infoBox.style.display = 'none';
        }
        
        // Set download link
        const downloadLink = modal.querySelector('.download-link');
        if (data.pdfLink) {
            downloadLink.href = data.pdfLink;
            downloadLink.style.display = 'flex';
        } else {
            downloadLink.style.display = 'none';
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
    
    function formatFloor(floor) {
        if (floor === '' || floor === null || floor === undefined) return '';
        if (floor === 0 || floor === '0') return 'Parter';
        if (floor == -1 || floor === '-1') return 'Piwnica';
        const floorNum = parseInt(floor);
        if (floorNum > 0) {
            // Format as "Piętro I", "Piętro II", etc.
            const romanNumerals = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
            if (floorNum <= 10 && romanNumerals[floorNum]) {
                return 'Piętro ' + romanNumerals[floorNum];
            }
            return 'Piętro ' + floorNum;
        }
        return floor;
    }
    
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const months = ['stycznia', 'lutego', 'marca', 'kwietnia', 'maja', 'czerwca', 
                       'lipca', 'sierpnia', 'września', 'października', 'listopada', 'grudnia'];
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
                
                // Sort by appliesFrom ascending
                prices.sort((a, b) => new Date(a.appliesFrom) - new Date(b.appliesFrom));
                
                // Build table rows (latest 6 entries)
                const last = prices.slice(-6);
                
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
