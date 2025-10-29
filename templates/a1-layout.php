<?php
/**
 * Template: A1 Layout (JeziornaTowers, OstojaOsiedle)
 *
 * @package Develogic
 * @var string $instance_id
 * @var array $atts
 * @var array $locals
 * @var array $buildings
 * @var array $status_counts
 * @var array $api_filters
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="<?php echo esc_attr($instance_id); ?>" class="develogic-a1-container" data-atts="<?php echo esc_attr(json_encode($atts)); ?>" data-api-filters="<?php echo esc_attr(json_encode($api_filters)); ?>">
    
    <?php if ($atts['buildings_panel'] === 'true' && !empty($buildings)): ?>
    <!-- Buildings Panel -->
    <div class="develogic-buildings-panel">
        <div class="develogic-buildings-grid">
            <?php foreach ($buildings as $building): ?>
                <div class="develogic-building-card <?php echo (!empty($atts['building_id']) && $building['id'] == $atts['building_id']) ? 'active' : ''; ?>" 
                     data-building-id="<?php echo esc_attr($building['id']); ?>">
                    <div class="building-thumbnail">
                        <?php
                        // Get building thumbnail from settings or use placeholder
                        $thumbnail_url = apply_filters('develogic_building_thumbnail', '', $building['id']);
                        if (empty($thumbnail_url)) {
                            $thumbnail_url = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"%3E%3Crect fill="%23ddd" width="200" height="200"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" fill="%23999" font-size="24"%3E' . esc_attr($building['name']) . '%3C/text%3E%3C/svg%3E';
                        }
                        ?>
                        <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr($building['name']); ?>">
                    </div>
                    <div class="building-info">
                        <h3><?php echo esc_html($building['name']); ?></h3>
                        <?php
                        $address = apply_filters('develogic_building_address', '', $building['id']);
                        if (!empty($address)):
                        ?>
                            <p class="building-address"><?php echo esc_html($address); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Header with counters and sorting -->
    <div class="develogic-a1-header">
        <div class="develogic-counters">
            <?php if ($atts['show_counters'] === 'true'): ?>
                <?php if (isset($status_counts['Wolny'])): ?>
                    <span class="counter counter-available">
                        <strong><?php echo absint($status_counts['Wolny']); ?></strong> 
                        <?php _e('dostępne', 'develogic'); ?>
                    </span>
                <?php endif; ?>
                
                <?php if (isset($status_counts['Rezerwacja'])): ?>
                    <span class="counter counter-reserved">
                        <strong><?php echo absint($status_counts['Rezerwacja']); ?></strong> 
                        <?php _e('rezerwacja', 'develogic'); ?>
                    </span>
                <?php endif; ?>
                
                <span class="counter counter-total">
                    <?php
                    printf(
                        __('Oferty mieszkań: <strong>%d</strong>', 'develogic'),
                        count($locals)
                    );
                    ?>
                </span>
            <?php endif; ?>
        </div>
        
        <div class="develogic-sorting">
            <label for="<?php echo esc_attr($instance_id); ?>-sort"><?php _e('Sortuj po:', 'develogic'); ?></label>
            <select id="<?php echo esc_attr($instance_id); ?>-sort" class="develogic-sort-select">
                <option value="floor" <?php selected($atts['sort_by'], 'floor'); ?>><?php _e('Piętro', 'develogic'); ?></option>
                <option value="area" <?php selected($atts['sort_by'], 'area'); ?>><?php _e('Metraż', 'develogic'); ?></option>
                <option value="rooms" <?php selected($atts['sort_by'], 'rooms'); ?>><?php _e('Pokoje', 'develogic'); ?></option>
                <option value="priceGross" <?php selected($atts['sort_by'], 'priceGross'); ?>><?php _e('Cena', 'develogic'); ?></option>
                <option value="priceGrossm2" <?php selected($atts['sort_by'], 'priceGrossm2'); ?>><?php _e('Cena m²', 'develogic'); ?></option>
            </select>
            
            <button class="develogic-sort-direction" data-direction="<?php echo esc_attr($atts['sort_dir']); ?>" title="<?php esc_attr_e('Zmień kierunek sortowania', 'develogic'); ?>">
                <span class="arrow <?php echo $atts['sort_dir'] === 'asc' ? 'up' : 'down'; ?>">↕</span>
            </button>
        </div>
    </div>
    
    <!-- Offers List -->
    <div class="develogic-a1-list">
        <?php if (empty($locals)): ?>
            <div class="develogic-no-results">
                <p><?php _e('Brak dostępnych ofert spełniających kryteria.', 'develogic'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($locals as $local): ?>
                <?php include DEVELOGIC_PLUGIN_DIR . 'templates/a1-card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php if ($atts['show_print'] === 'true' || $atts['show_print'] === true): ?>
    <!-- Print Button -->
    <div class="develogic-actions">
        <button class="develogic-print-btn" onclick="window.print();">
            <?php _e('Lista do wydruku', 'develogic'); ?>
        </button>
    </div>
    <?php endif; ?>
    
</div>

<?php if ($atts['gallery'] === 'true'): ?>
<script>
jQuery(document).ready(function($) {
    // Initialize LightGallery for each card
    $('.develogic-a1-card').each(function() {
        var $card = $(this);
        var localId = $card.data('local-id');
        
        $card.find('.develogic-images').lightGallery({
            selector: '.image-item',
            plugins: [lgThumbnail, lgZoom, lgFullscreen, lgHash],
            speed: 500,
            licenseKey: 'your-license-key',
            thumbnail: true,
            animateThumb: true,
            showThumbByDefault: true,
            hash: true,
            galleryId: localId
        });
    });
    
    // Building selection
    $('.develogic-building-card').on('click', function() {
        var buildingId = $(this).data('building-id');
        $('.develogic-building-card').removeClass('active');
        $(this).addClass('active');
        
        // Filter cards by building
        if (buildingId) {
            $('.develogic-a1-card').hide();
            $('.develogic-a1-card[data-building-id="' + buildingId + '"]').show();
        } else {
            $('.develogic-a1-card').show();
        }
        
        // Update counters
        updateCounters();
    });
    
    // Sorting
    $('#<?php echo esc_js($instance_id); ?>-sort').on('change', function() {
        performSort();
    });
    
    $('.develogic-sort-direction').on('click', function() {
        var currentDir = $(this).data('direction');
        var newDir = currentDir === 'asc' ? 'desc' : 'asc';
        $(this).data('direction', newDir);
        $(this).find('.arrow').removeClass('up down').addClass(newDir === 'asc' ? 'up' : 'down');
        performSort();
    });
    
    function performSort() {
        var sortBy = $('#<?php echo esc_js($instance_id); ?>-sort').val();
        var sortDir = $('.develogic-sort-direction').data('direction');
        var $container = $('.develogic-a1-list');
        var $cards = $container.find('.develogic-a1-card:visible');
        
        $cards.sort(function(a, b) {
            var aVal = $(a).data('sort-' + sortBy);
            var bVal = $(b).data('sort-' + sortBy);
            
            if (typeof aVal === 'number' && typeof bVal === 'number') {
                return sortDir === 'asc' ? aVal - bVal : bVal - aVal;
            } else {
                var comparison = String(aVal).localeCompare(String(bVal));
                return sortDir === 'asc' ? comparison : -comparison;
            }
        });
        
        $cards.detach().appendTo($container);
    }
    
    function updateCounters() {
        var $visibleCards = $('.develogic-a1-card:visible');
        var counters = {};
        
        $visibleCards.each(function() {
            var status = $(this).data('status');
            counters[status] = (counters[status] || 0) + 1;
        });
        
        $('.counter-available strong').text(counters['Wolny'] || 0);
        $('.counter-reserved strong').text(counters['Rezerwacja'] || 0);
        $('.counter-total strong').text($visibleCards.length);
    }
});
</script>
<?php endif; ?>

