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
        
        btn.classList.toggle('favorited');
        
        const favorites = getFavorites();
        const index = favorites.indexOf(localId);
        
        if (index === -1) {
            favorites.push(localId);
        } else {
            favorites.splice(index, 1);
        }
        
        saveFavorites(favorites);
        
        // Update all favorite buttons for this local
        document.querySelectorAll('.icon-btn[data-local-id="' + localId + '"]').forEach(b => {
            if (index === -1) {
                b.classList.add('favorited');
            } else {
                b.classList.remove('favorited');
            }
        });
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
                // Don't open modal if clicking on buttons or images
                if (e.target.closest('.icon-btn, .apartment-image')) {
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
        let headerTitle = data.building || '';
        if (data.buildingAddress) {
            headerTitle += (headerTitle ? ' - ' : '') + data.buildingAddress;
        }
        modal.querySelector('.modal-title').textContent = headerTitle || 'Szczegóły mieszkania';
        
        // Set location
        let locationText = data.building || '';
        if (data.buildingAddress) {
            locationText += (locationText ? '<br>' : '') + data.buildingAddress;
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
    
})();
