<?php
/**
 * Template: Apartments List Layout
 * Lista mieszkań w stylu katalogowym z obsługą Shuffle.js, lightGallery i Tippy.js
 *
 * @package Develogic
 * @var string $instance_id
 * @var array $atts
 * @var array $locals
 * @var array $buildings
 * @var array $status_counts
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="<?php echo esc_attr($instance_id); ?>" class="develogic-apartments-list-container" 
     data-atts="<?php echo esc_attr(json_encode($atts)); ?>">
    
    <div class="container apartments-sort shuffle-sort">
        <div class="txt">
            <!-- Header with counters and sorting -->
            <div class="row-auto apartments-header">
                <div class="col apartments-title">
                    <h3 class="apartments-count">
                        <?php if ($atts['show_counters'] === 'true'): ?>
                        <small class="l status-count">
                            <?php if (isset($status_counts['Wolny'])): ?>
                            <span class="status-count-txt">
                                <?php echo absint($status_counts['Wolny']); ?> <?php _e('dostępne', 'develogic'); ?>
                            </span>
                            <?php endif; ?>
                            <?php if (isset($status_counts['Rezerwacja'])): ?>
                            <span class="status-count-txt">
                                <?php echo absint($status_counts['Rezerwacja']); ?> <?php _e('rezerwacje', 'develogic'); ?>
                            </span>
                            <?php endif; ?>
                        </small>
                        <?php endif; ?>
                        <span class="status-count-h">
                            <?php 
                            $title = !empty($atts['title']) ? $atts['title'] : __('Lista mieszkań', 'develogic');
                            echo esc_html($title);
                            ?>
                            <small>
                                <span class="filter-count"><?php echo count($locals); ?></span>
                            </small>
                        </span>
                    </h3>
                </div>
                <div class="col text-right l apartments-sort">
                    <!-- Desktop Sort Links -->
                    <div class="sort-links" style="display: none;">
                        <span class="l"><?php _e('Sortuj po:', 'develogic'); ?></span>
                        <a href="#" class="sort-link" data-sort="data-floor">
                            <?php _e('Piętro', 'develogic'); ?>
                        </a>
                        <a href="#" class="sort-link" data-sort="data-area">
                            <?php _e('Metraż', 'develogic'); ?>
                        </a>
                        <a href="#" class="sort-link" data-sort="data-rooms">
                            <?php _e('Pokoje', 'develogic'); ?>
                        </a>
                        <a href="#" class="sort-link" data-sort="data-price">
                            <?php _e('Cena', 'develogic'); ?>
                        </a>
                        <a href="#" class="sort-link" data-sort="data-price-m2">
                            <?php _e('Cena m²', 'develogic'); ?>
                        </a>
                    </div>
                    
                    <!-- Mobile Dropdown -->
                    <div class="dropdown-xs dropdown-xs-init">
                        <span class="dropdown-label-xs">
                            <span class="dropdown-label-txt-xs"><?php _e('Sortuj po:', 'develogic'); ?></span>
                            <span class="dropdown-label-icon-xs"></span>
                        </span>
                        <span class="doprdow-list-xs">
                            <span class="a dropdown-list-label-xs">
                                <?php _e('Sortuj po:', 'develogic'); ?>
                            </span>
                            <a href="#" class="btn-sort" data-sort="data-floor">
                                <?php _e('Piętro', 'develogic'); ?>
                            </a>
                            <a href="#" class="btn-sort" data-sort="data-area">
                                <?php _e('Metraż', 'develogic'); ?>
                            </a>
                            <a href="#" class="btn-sort" data-sort="data-rooms">
                                <?php _e('Pokoje', 'develogic'); ?>
                            </a>
                            <a href="#" class="btn-sort" data-sort="data-price">
                                <?php _e('Cena', 'develogic'); ?>
                            </a>
                            <a href="#" class="btn-sort" data-sort="data-price-m2">
                                <?php _e('Cena m²', 'develogic'); ?>
                            </a>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="apartmnets-list-container">
            <!-- Apartments List with Shuffle.js support -->
            <div class="apartments-list shuffle" id="<?php echo esc_attr($instance_id); ?>-grid">
        <?php if (empty($locals)): ?>
            <div class="no-results">
                <p><?php _e('Brak dostępnych mieszkań.', 'develogic'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($locals as $local): 
                // Prepare data
                $price_m2_source = develogic()->get_setting('price_m2_source', 'priceGrossm2');
                $price_m2 = isset($local[$price_m2_source]) ? $local[$price_m2_source] : $local['priceGrossm2'];
                $developer_name = develogic()->get_setting('developer_name', get_bloginfo('name'));
                
                // PDF link
                $pdf_link = '';
                $pdf_source = develogic()->get_setting('pdf_source', 'off');
                if ($pdf_source === 'pattern') {
                    $pdf_pattern = develogic()->get_setting('pdf_pattern', '');
                    if (!empty($pdf_pattern)) {
                        $pdf_link = str_replace(
                            array('{localId}', '{number}'),
                            array($local['localId'], $local['number']),
                            $pdf_pattern
                        );
                    }
                }
                
                // Projections
                $projections = isset($local['projections']) ? $local['projections'] : array();
                
                // Prepare sortable data with leading zeros
                $rooms_padded = str_pad($local['rooms'], 2, '0', STR_PAD_LEFT);
                $floor_padded = str_pad($local['floor'], 2, '0', STR_PAD_LEFT);
                $price_padded = str_pad($local['priceGross'], 8, '0', STR_PAD_LEFT);
                $price_m2_padded = str_pad($price_m2, 8, '0', STR_PAD_LEFT);
                $area_padded = str_pad(str_replace('.', '', number_format($local['area'], 2, '', '')), 8, '0', STR_PAD_LEFT);
                
                // Status class
                $status_class = Develogic_Data_Formatter::get_status_class($local['status']);
                
                // Building info
                $building_address = apply_filters('develogic_building_address', '', $local['buildingId']);
                
                // Tags from attributes
                $tags = array();
                if (!empty($local['attributes'])) {
                    $tag_whitelist = apply_filters('develogic_attribute_whitelist', array(
                        'aneks', 'balkon', '2 balkony', 'garderoba', 'taras', 'ogród', 'pom. gospodarcze'
                    ));
                    
                    foreach ($local['attributes'] as $attr) {
                        if (isset($attr['name']) && in_array(strtolower($attr['name']), array_map('strtolower', $tag_whitelist))) {
                            $tags[] = $attr['name'];
                        }
                    }
                }
                
                // Gallery ID for lightGallery
                $gallery_id = 'mieszkanie-' . $local['localId'];
            ?>
            
            <div id="<?php echo esc_attr($gallery_id); ?>" 
                 class="apartment-item shuffle-item shuffle-item--visible" 
                 data-rooms="<?php echo esc_attr($rooms_padded); ?>"
                 data-floor="<?php echo esc_attr($floor_padded); ?>"
                 data-price="<?php echo esc_attr($price_padded); ?>"
                 data-price-m2="<?php echo esc_attr($price_m2_padded); ?>"
                 data-area="<?php echo esc_attr($area_padded); ?>"
                 data-title="<?php echo esc_attr($local['building']); ?>"
                 data-status="<?php echo esc_attr($status_class); ?>"
                 data-local-id="<?php echo esc_attr($local['localId']); ?>"
                 data-building-id="<?php echo esc_attr($local['buildingId']); ?>"
                 data-groups='<?php echo esc_attr(json_encode(array('rooms-' . $local['rooms'], 'status-' . $status_class))); ?>'>
                 
                <div class="row-md apartment apartment-html">
                    
                    <!-- Column: Name -->
                    <div class="col c-name">
                        <div>
                            <?php if (!empty($local['building'])): ?>
                            <p class="l">
                                <?php echo esc_html($local['building']); ?>
                                <?php if (!empty($building_address)): ?>
                                <small><?php echo esc_html($building_address); ?></small>
                                <?php endif; ?>
                            </p>
                            <?php endif; ?>
                            
                            <h3 class="l-title">
                                <?php echo esc_html($local['number']); ?>
                            </h3>
                            
                            <span class="status status-<?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($local['status']); ?>
                                <?php if ($status_class === 'available'): ?>
                                <i><?php _e('od ręki', 'develogic'); ?></i>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Column: Details -->
                    <div class="col c-details">
                        <div>
                            <ul>
                                <?php
                                // Klatka - optional
                                $klatka = apply_filters('develogic_local_klatka', '', $local);
                                if (!empty($klatka)):
                                ?>
                                <li class="li-staircase">
                                    <span class="l"><?php _e('Klatka', 'develogic'); ?></span> 
                                    <b><?php echo esc_html($klatka); ?></b>
                                </li>
                                <?php endif; ?>
                                
                                <li class="li-floor">
                                    <span class="l"><?php _e('Kondygnacja', 'develogic'); ?></span> 
                                    <b><?php echo esc_html(Develogic_Data_Formatter::format_floor($local['floor'])); ?></b>
                                </li>
                                
                                <?php if (!empty($local['area'])): ?>
                                <li class="li-area">
                                    <span class="l"><?php _e('Powierzchnia', 'develogic'); ?></span> 
                                    <b><?php echo number_format($local['area'], 2, ',', ' '); ?> m<sup>2</sup></b>
                                </li>
                                <?php endif; ?>
                                
                                <?php if (!empty($local['rooms'])): ?>
                                <li class="li-rooms">
                                    <span class="l"><?php _e('Ilość pokoi', 'develogic'); ?></span> 
                                    <b data-xs-label="pokoje"><?php echo absint($local['rooms']); ?></b>
                                </li>
                                <?php endif; ?>
                            </ul>
                            
                            <?php if (!empty($tags)): ?>
                            <p class="tags-list">
                                <?php foreach ($tags as $index => $tag): ?>
                                    <span class="tag-<?php echo esc_attr(sanitize_title($tag)); ?>"><?php echo esc_html($tag); ?></span><?php if ($index < count($tags) - 1): ?>, <?php endif; ?>
                                <?php endforeach; ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Column: Image 1 (displayUrl jako obrazek) -->
                    <div class="col c-image c-image-1">
                        <?php 
                        // Find projection with displayUrl (Karta lokalu)
                        $projection_main = null;
                        foreach ($projections as $proj) {
                            if (isset($proj['displayUrl']) && !empty($proj['displayUrl'])) {
                                $projection_main = $proj;
                                break;
                            }
                        }
                        // Fallback do pierwszej projekcji jeśli brak displayUrl
                        if (empty($projection_main) && !empty($projections[0])) {
                            $projection_main = $projections[0];
                        }
                        
                        if (!empty($projection_main)):
                            // Use WordPress-hosted image if available, fallback to API URL
                            $image_url = !empty($projection_main['wordpress_url']) ? $projection_main['wordpress_url'] : 
                                        (!empty($projection_main['displayUrl']) ? $projection_main['displayUrl'] : $projection_main['uri']);
                        ?>
                        <a href="<?php echo esc_url($image_url); ?>" 
                           class="link-img" 
                           title="<?php _e('podgląd', 'develogic'); ?>"
                           data-lg-id="<?php echo esc_attr($gallery_id); ?>">
                            <div class="responsive-1x1">
                                <img src="<?php echo esc_url($image_url); ?>" 
                                     class="img-responsive" 
                                     alt="<?php echo esc_attr($local['number']); ?>"
                                     loading="lazy">
                            </div>
                        </a>
                        <?php else: ?>
                        <div class="image-placeholder">
                            <div class="responsive-1x1">
                                <span><?php _e('Brak wizualizacji', 'develogic'); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Column: Image 2 (Plan) -->
                    <div class="col c-image c-image-2">
                        <?php 
                        $projection_plan = null;
                        foreach ($projections as $proj) {
                            if (isset($proj['type']) && stripos($proj['type'], 'plan') !== false) {
                                $projection_plan = $proj;
                                break;
                            }
                        }
                        if (empty($projection_plan) && !empty($projections[1])) {
                            $projection_plan = $projections[1];
                        }
                        
                        if (!empty($projection_plan)):
                            // Use WordPress-hosted image if available, fallback to API URL
                            $plan_url = !empty($projection_plan['wordpress_url']) ? $projection_plan['wordpress_url'] : $projection_plan['uri'];
                        ?>
                        <a href="<?php echo esc_url($plan_url); ?>" 
                           class="link-img" 
                           title="<?php _e('podgląd', 'develogic'); ?>"
                           data-lg-id="<?php echo esc_attr($gallery_id); ?>">
                            <div class="responsive-1x1">
                                <img src="<?php echo esc_url($plan_url); ?>" 
                                     class="img-responsive" 
                                     alt="<?php echo esc_attr($local['number']); ?>"
                                     loading="lazy">
                            </div>
                        </a>
                        <?php else: ?>
                        <div class="image-placeholder">
                            <div class="responsive-1x1">
                                <span><?php _e('Brak rzutu', 'develogic'); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Hidden additional images for gallery -->
                    <?php if (count($projections) > 2): ?>
                        <?php for ($i = 2; $i < count($projections); $i++): 
                            // Use WordPress-hosted image if available, fallback to API URL
                            $proj_url = !empty($projections[$i]['wordpress_url']) ? $projections[$i]['wordpress_url'] : $projections[$i]['uri'];
                        ?>
                        <a href="<?php echo esc_url($proj_url); ?>" 
                           class="link-img hidden" 
                           data-lg-id="<?php echo esc_attr($gallery_id); ?>"></a>
                        <?php endfor; ?>
                    <?php endif; ?>
                    
                    <!-- Column: Price -->
                    <div class="col c-price c-price-yes c-bg">
                        <div>
                            <p class="l c-price-l">
                                <?php _e('Cena', 'develogic'); ?>
                            </p>
                            <b class="h3">
                                <?php echo number_format($local['priceGross'], 0, ',', ' '); ?> zł
                            </b>
                            <p class="c-price-l2">
                                (<?php echo number_format($price_m2, 2, ',', ' '); ?> zł/m<sup>2</sup>)
                            </p>
                        </div>
                    </div>
                    
                    <!-- Column: Actions -->
                    <div class="col c-action c-bg">
                        <div>
                            <a href="mailto:<?php echo esc_attr(develogic()->get_setting('contact_email', get_option('admin_email'))); ?>?Subject=<?php echo rawurlencode(sprintf(__('Mieszkanie %s – %s', 'develogic'), $local['number'], $developer_name)); ?>&body=%0D%0A---%0D%0A<?php echo rawurlencode(get_permalink()); ?>" 
                               class="btn-mail btn-border tippy" 
                               data-tippy-content="<?php esc_attr_e('zapytaj', 'develogic'); ?>">
                                <span class="icon icon-action-mail"></span>
                            </a>
                            
                            <?php if ($atts['show_favorite'] === 'true' || $atts['show_favorite'] === true): ?>
                            <a href="#<?php echo esc_attr($gallery_id); ?>" 
                               class="btn-observe btn-border observe-ready <?php echo esc_attr($gallery_id); ?>-o tippy" 
                               data-tippy-content="<?php esc_attr_e('obserwuj', 'develogic'); ?>"
                               data-local-id="<?php echo esc_attr($local['localId']); ?>">
                                <span class="icon icon-action-star"></span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Download/Description -->
                    <?php if (!empty($local['plannedDateOfFinishing']) || !empty($pdf_link)): ?>
                    <div class="c-download">
                        <?php if (!empty($local['plannedDateOfFinishing'])): ?>
                        <div class="c-description">
                            <p><?php echo esc_html(Develogic_Data_Formatter::format_planned_finishing($local['plannedDateOfFinishing'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($pdf_link)): ?>
                        <a href="<?php echo esc_url($pdf_link); ?>" download class="btn-download">
                            <span class="icon icon-action-printer"></span> 
                            <?php _e('Pobierz kartę mieszkania', 'develogic'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
            
            <?php endforeach; ?>
        <?php endif; ?>
            </div>
        </div>
        
        <?php if ($atts['show_print'] === 'true' || $atts['show_print'] === true): ?>
        <div class="txt">
            <p class="text-right">
                <a href="javascript:window.print();" class="btn-print" title="<?php esc_attr_e('Wydrukuj listę', 'develogic'); ?>">
                    <span class="btn-print-txt"><?php _e('lista do wydruku', 'develogic'); ?></span> 
                    <span class="btn-print-icon icon icon-action-printer"></span>
                </a>
            </p>
        </div>
        <?php endif; ?>
    </div>
    
</div>

