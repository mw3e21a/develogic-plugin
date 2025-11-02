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
    
    function setupModal() {
        // Close button
        const closeBtn = document.querySelector('.apartment-detail-modal .close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeApartmentModal);
        }
        
        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeApartmentModal();
            }
        });
        
        // Close on backdrop click
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('apartment-detail-modal');
            if (modal && modal.style.display !== 'none' && e.target === modal) {
                closeApartmentModal();
            }
        });
    }
    
    function openApartmentModal(data) {
        currentModalData = data;
        currentGalleryIndex = 0;
        
        const modal = document.getElementById('apartment-detail-modal');
        if (!modal) return;
        
        // Set header title
        let headerTitle = data.building || '';
        if (data.buildingAddress) {
            headerTitle += (headerTitle ? ' - ' : '') + data.buildingAddress;
        }
        modal.querySelector('.header-title').textContent = headerTitle || 'Szczegóły mieszkania';
        
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
        let statusText = data.status || '';
        if (data.statusClass === 'available') {
            statusText = 'Dostępne od ręki';
        }
        statusEl.textContent = statusText;
        statusEl.className = 'status';
        if (data.statusClass === 'reserved') {
            statusEl.classList.add('reserved');
        }
        
        // Set details grid
        const detailGrid = modal.querySelector('.detail-grid');
        detailGrid.innerHTML = '';
        
        if (data.klatka) {
            addDetailRow(detailGrid, 'Klatka', data.klatka);
        }
        
        addDetailRow(detailGrid, 'Kondygnacja', data.floorDisplay || formatFloor(data.floor));
        addDetailRow(detailGrid, 'Powierzchnia', formatArea(data.area));
        addDetailRow(detailGrid, 'Ilość pokoi', data.rooms);
        
        // Set features
        const featuresEl = modal.querySelector('.features');
        if (data.tags && data.tags.length > 0) {
            featuresEl.textContent = data.tags.join(', ');
        } else {
            featuresEl.textContent = '';
        }
        
        // Set price
        modal.querySelector('.price-main').textContent = formatPrice(data.priceGross);
        modal.querySelector('.price-sqm').textContent = '(' + formatPriceM2(data.priceM2) + ' zł/m²)';
        
        // Set info box
        const infoBox = modal.querySelector('.info-box');
        if (data.plannedDate) {
            const infoText = 'Planowane oddanie budynku - ' + formatDate(data.plannedDate) + '.';
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
        const mainImage = document.querySelector('.main-image img');
        const galleryContainer = document.querySelector('.gallery');
        
        if (!mainImage || !galleryContainer) return;
        
        galleryContainer.innerHTML = '';
        
        if (projections.length === 0) {
            mainImage.src = '';
            mainImage.alt = 'Brak zdjęć';
            return;
        }
        
        // Set main image
        currentGalleryIndex = 0;
        updateMainImage(projections[0].url);
        
        // Create gallery items
        projections.forEach((proj, index) => {
            const galleryItem = document.createElement('div');
            galleryItem.className = 'gallery-item' + (index === 0 ? ' active' : '');
            galleryItem.innerHTML = '<img src="' + proj.thumb + '" alt="Gallery ' + (index + 1) + '">';
            
            galleryItem.addEventListener('click', function() {
                // Remove active from all
                galleryContainer.querySelectorAll('.gallery-item').forEach(item => {
                    item.classList.remove('active');
                });
                // Add active to clicked
                this.classList.add('active');
                
                currentGalleryIndex = index;
                updateMainImage(proj.url);
            });
            
            galleryContainer.appendChild(galleryItem);
        });
    }
    
    function updateMainImage(url) {
        const mainImage = document.querySelector('.main-image img');
        if (mainImage) {
            mainImage.src = url;
        }
    }
    
    function closeApartmentModal() {
        const modal = document.getElementById('apartment-detail-modal');
        if (modal) {
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
