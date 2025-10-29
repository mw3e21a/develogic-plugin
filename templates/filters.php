<?php
/**
 * Template: Filters
 *
 * @package Develogic
 * @var array $atts
 * @var array $investments
 * @var array $local_types
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$fields = array_map('trim', explode(',', $atts['fields']));
$expanded = $atts['expanded'] === 'true';
?>

<div class="develogic-filters <?php echo $expanded ? 'expanded' : 'collapsed'; ?>" 
     data-target="<?php echo esc_attr($atts['target']); ?>">
    
    <form class="develogic-filters-form">
        
        <?php if (in_array('investment', $fields) && !empty($investments)): ?>
        <div class="filter-group">
            <label for="filter-investment"><?php _e('Inwestycja:', 'develogic'); ?></label>
            <select name="investment_id" id="filter-investment">
                <option value=""><?php _e('Wszystkie', 'develogic'); ?></option>
                <?php foreach ($investments as $investment): ?>
                    <option value="<?php echo esc_attr($investment['ID']); ?>" 
                            <?php selected($atts['investment_id'], $investment['ID']); ?>>
                        <?php echo esc_html($investment['Name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('localType', $fields) && !empty($local_types)): ?>
        <div class="filter-group">
            <label for="filter-local-type"><?php _e('Typ lokalu:', 'develogic'); ?></label>
            <select name="local_type_id" id="filter-local-type">
                <option value=""><?php _e('Wszystkie', 'develogic'); ?></option>
                <?php foreach ($local_types as $type): ?>
                    <option value="<?php echo esc_attr($type['ID']); ?>">
                        <?php echo esc_html($type['Name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('rooms', $fields)): ?>
        <div class="filter-group">
            <label for="filter-rooms"><?php _e('Pokoje:', 'develogic'); ?></label>
            <select name="rooms" id="filter-rooms">
                <option value=""><?php _e('Wszystkie', 'develogic'); ?></option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5+</option>
            </select>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('area', $fields)): ?>
        <div class="filter-group filter-range">
            <label><?php _e('Powierzchnia (m²):', 'develogic'); ?></label>
            <div class="range-inputs">
                <input type="number" name="min_area" placeholder="<?php esc_attr_e('Od', 'develogic'); ?>" step="0.01">
                <span>-</span>
                <input type="number" name="max_area" placeholder="<?php esc_attr_e('Do', 'develogic'); ?>" step="0.01">
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('price', $fields)): ?>
        <div class="filter-group filter-range">
            <label><?php _e('Cena (zł):', 'develogic'); ?></label>
            <div class="range-inputs">
                <input type="number" name="min_price_gross" placeholder="<?php esc_attr_e('Od', 'develogic'); ?>" step="1000">
                <span>-</span>
                <input type="number" name="max_price_gross" placeholder="<?php esc_attr_e('Do', 'develogic'); ?>" step="1000">
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('floor', $fields)): ?>
        <div class="filter-group">
            <label for="filter-floor"><?php _e('Piętro:', 'develogic'); ?></label>
            <select name="floor" id="filter-floor">
                <option value=""><?php _e('Wszystkie', 'develogic'); ?></option>
                <option value="-1"><?php _e('Piwnica', 'develogic'); ?></option>
                <option value="0"><?php _e('Parter', 'develogic'); ?></option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5+</option>
            </select>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('search', $fields)): ?>
        <div class="filter-group filter-search">
            <label for="filter-search"><?php _e('Szukaj:', 'develogic'); ?></label>
            <input type="text" name="search" id="filter-search" placeholder="<?php esc_attr_e('Numer lub nazwa...', 'develogic'); ?>">
        </div>
        <?php endif; ?>
        
        <?php if (in_array('sort', $fields)): ?>
        <div class="filter-group">
            <label for="filter-sort"><?php _e('Sortuj po:', 'develogic'); ?></label>
            <select name="sort_by" id="filter-sort">
                <option value="priceGross"><?php _e('Cena', 'develogic'); ?></option>
                <option value="priceGrossm2"><?php _e('Cena m²', 'develogic'); ?></option>
                <option value="area"><?php _e('Powierzchnia', 'develogic'); ?></option>
                <option value="rooms"><?php _e('Pokoje', 'develogic'); ?></option>
                <option value="floor"><?php _e('Piętro', 'develogic'); ?></option>
            </select>
            <select name="sort_dir" id="filter-sort-dir">
                <option value="asc"><?php _e('Rosnąco', 'develogic'); ?></option>
                <option value="desc"><?php _e('Malejąco', 'develogic'); ?></option>
            </select>
        </div>
        <?php endif; ?>
        
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary"><?php _e('Filtruj', 'develogic'); ?></button>
            <?php if ($atts['show_reset'] === 'true'): ?>
                <button type="button" class="btn btn-secondary btn-reset"><?php _e('Resetuj', 'develogic'); ?></button>
            <?php endif; ?>
        </div>
        
    </form>
    
</div>

<script>
jQuery(document).ready(function($) {
    var $form = $('.develogic-filters-form');
    var target = '<?php echo esc_js($atts['target']); ?>';
    
    $form.on('submit', function(e) {
        e.preventDefault();
        
        var filters = {};
        $(this).serializeArray().forEach(function(item) {
            if (item.value) {
                filters[item.name] = item.value;
            }
        });
        
        // Trigger custom event for target container
        if (target) {
            $(target).trigger('develogic:filter', [filters]);
        }
        
        console.log('Filters applied:', filters);
    });
    
    $('.btn-reset').on('click', function() {
        $form[0].reset();
        $form.trigger('submit');
    });
});
</script>

