/**
 * Develogic Integration - Main JavaScript
 * @package Develogic
 */

(function($) {
    'use strict';
    
    // Favorites management (localStorage)
    var Favorites = {
        key: 'develogic_favorites',
        
        get: function() {
            var favorites = localStorage.getItem(this.key);
            return favorites ? JSON.parse(favorites) : [];
        },
        
        add: function(localId) {
            var favorites = this.get();
            if (favorites.indexOf(localId) === -1) {
                favorites.push(localId);
                localStorage.setItem(this.key, JSON.stringify(favorites));
                return true;
            }
            return false;
        },
        
        remove: function(localId) {
            var favorites = this.get();
            var index = favorites.indexOf(localId);
            if (index > -1) {
                favorites.splice(index, 1);
                localStorage.setItem(this.key, JSON.stringify(favorites));
                return true;
            }
            return false;
        },
        
        has: function(localId) {
            return this.get().indexOf(localId) > -1;
        }
    };
    
    // Initialize favorites on page load
    $(document).ready(function() {
        // Mark favorited items
        $('.action-favorite').each(function() {
            var $btn = $(this);
            var localId = $btn.data('local-id');
            
            if (Favorites.has(localId)) {
                $btn.addClass('active');
            }
        });
        
        // Favorite button click handler
        $(document).on('click', '.action-favorite, .btn-favorite', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $btn = $(this);
            var localId = $btn.data('local-id');
            
            if (Favorites.has(localId)) {
                Favorites.remove(localId);
                $btn.removeClass('active');
                
                if (typeof develogicData !== 'undefined') {
                    showNotice(develogicData.i18n.removedFromFavorites);
                }
            } else {
                Favorites.add(localId);
                $btn.addClass('active');
                
                if (typeof develogicData !== 'undefined') {
                    showNotice(develogicData.i18n.addedToFavorites);
                }
            }
        });
    });
    
    // Show notification
    function showNotice(message) {
        var $notice = $('<div class="develogic-notice">' + message + '</div>');
        $notice.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '15px 20px',
            background: '#28a745',
            color: '#fff',
            borderRadius: '4px',
            zIndex: 9999,
            boxShadow: '0 4px 12px rgba(0,0,0,0.2)',
            animation: 'slideInRight 0.3s ease'
        });
        
        $('body').append($notice);
        
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // AJAX offers loading (for generic shortcode)
    $(document).ready(function() {
        $('.develogic-offers[data-ajax="true"]').each(function() {
            var $container = $(this);
            var atts = $container.data('atts');
            
            loadOffers($container, atts);
        });
    });
    
    function loadOffers($container, atts) {
        if (typeof develogicData === 'undefined') {
            return;
        }
        
        var params = {
            investment_id: atts.investment_id || '',
            local_type_id: atts.local_type_id || '',
            building_id: atts.building_id || '',
            status: atts.status || '',
            rooms: atts.rooms || '',
            floor: atts.floor || '',
            min_area: atts.min_area || '',
            max_area: atts.max_area || '',
            min_price_gross: atts.min_price_gross || '',
            max_price_gross: atts.max_price_gross || '',
            sort_by: atts.sort_by || 'priceGrossm2',
            sort_dir: atts.sort_dir || 'asc',
            per_page: atts.per_page || 12
        };
        
        $.ajax({
            url: develogicData.restUrl + '/offers',
            method: 'GET',
            data: params,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', develogicData.nonce);
                $container.html('<div class="develogic-loading">' + develogicData.i18n.loading + '</div>');
            },
            success: function(response) {
                if (response.locals && response.locals.length > 0) {
                    renderOffers($container, response.locals, atts.view);
                } else {
                    $container.html('<div class="develogic-no-results"><p>' + develogicData.i18n.noResults + '</p></div>');
                }
            },
            error: function() {
                $container.html('<div class="develogic-error"><p>' + develogicData.i18n.error + '</p></div>');
            }
        });
    }
    
    function renderOffers($container, locals, view) {
        var html = '<div class="develogic-offers-list develogic-offers-' + view + '">';
        
        locals.forEach(function(local) {
            html += '<div class="develogic-offer-card">';
            html += '<h3>' + local.number + '</h3>';
            html += '<p>Status: ' + local.status + '</p>';
            html += '<p>Pokoje: ' + local.rooms + '</p>';
            html += '<p>Powierzchnia: ' + local.area + ' m²</p>';
            html += '<p>Cena: ' + formatPrice(local.priceGross) + '</p>';
            html += '</div>';
        });
        
        html += '</div>';
        
        $container.html(html);
    }
    
    function formatPrice(price) {
        if (!price) return '';
        return price.toLocaleString('pl-PL', {
            style: 'currency',
            currency: 'PLN'
        });
    }
    
    // Add CSS animation
    var style = document.createElement('style');
    style.innerHTML = '@keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }';
    document.head.appendChild(style);
    
})(jQuery);

