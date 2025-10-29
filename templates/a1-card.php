<?php
/**
 * Template: A1 Card (Single Offer Card)
 *
 * @package Develogic
 * @var array $local
 * @var array $atts
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

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

// Prepare projections
$projections = isset($local['projections']) ? $local['projections'] : array();
$first_two_projections = array_slice($projections, 0, 2);
?>

<div class="develogic-a1-card" 
     data-local-id="<?php echo esc_attr($local['localId']); ?>"
     data-building-id="<?php echo esc_attr($local['buildingId']); ?>"
     data-status="<?php echo esc_attr($local['status']); ?>"
     data-sort-floor="<?php echo esc_attr($local['floor']); ?>"
     data-sort-area="<?php echo esc_attr($local['area']); ?>"
     data-sort-rooms="<?php echo esc_attr($local['rooms']); ?>"
     data-sort-priceGross="<?php echo esc_attr($local['priceGross']); ?>"
     data-sort-priceGrossm2="<?php echo esc_attr($local['priceGrossm2']); ?>">
    
    <!-- Column 1: Meta -->
    <div class="card-column card-meta">
        <?php if (!empty($local['building'])): ?>
            <div class="building-name">
                <?php echo esc_html($local['building']); ?>
                <?php
                $address = apply_filters('develogic_building_address', '', $local['buildingId']);
                if (!empty($address)):
                ?>
                    <span class="building-address"><?php echo esc_html($address); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="local-number">
            <?php echo esc_html($local['number']); ?>
        </div>
        
        <div class="local-status status-<?php echo esc_attr(Develogic_Data_Formatter::get_status_class($local['status'])); ?>">
            <?php echo esc_html($local['status']); ?>
        </div>
    </div>
    
    <!-- Column 2: Details -->
    <div class="card-column card-details">
        <?php
        // Klatka - optional, from custom field or attribute
        $klatka = apply_filters('develogic_local_klatka', '', $local);
        if (!empty($klatka)):
        ?>
            <div class="detail-row">
                <span class="detail-label"><?php _e('Klatka:', 'develogic'); ?></span>
                <span class="detail-value"><?php echo esc_html($klatka); ?></span>
            </div>
        <?php endif; ?>
        
        <div class="detail-row">
            <span class="detail-label"><?php _e('Kondygnacja:', 'develogic'); ?></span>
            <span class="detail-value"><?php echo esc_html(Develogic_Data_Formatter::format_floor($local['floor'])); ?></span>
        </div>
        
        <?php if (!empty($local['area'])): ?>
            <div class="detail-row">
                <span class="detail-label"><?php _e('Powierzchnia:', 'develogic'); ?></span>
                <span class="detail-value"><?php echo Develogic_Data_Formatter::format_area($local['area']); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($local['rooms'])): ?>
            <div class="detail-row">
                <span class="detail-label"><?php _e('Ilość pokoi:', 'develogic'); ?></span>
                <span class="detail-value"><?php echo absint($local['rooms']); ?></span>
            </div>
        <?php endif; ?>
        
        <?php
        // Tags from attributes
        if (!empty($local['attributes'])):
            $tag_whitelist = apply_filters('develogic_attribute_whitelist', array(
                'aneks', 'balkon', 'garderoba', 'taras', 'ogród', 'pom. gospodarcze'
            ));
            
            $tags = array();
            foreach ($local['attributes'] as $attr) {
                if (isset($attr['name']) && in_array(strtolower($attr['name']), array_map('strtolower', $tag_whitelist))) {
                    $tags[] = $attr['name'];
                }
            }
            
            if (!empty($tags)):
        ?>
            <div class="detail-row detail-tags">
                <?php foreach ($tags as $tag): ?>
                    <span class="tag"><?php echo esc_html($tag); ?></span>
                <?php endforeach; ?>
            </div>
        <?php
            endif;
        endif;
        ?>
    </div>
    
    <!-- Columns 3-4: Images -->
    <div class="card-column card-images develogic-images">
        <?php if (!empty($projections)): ?>
            <?php foreach ($projections as $index => $projection): 
                // Use WordPress-hosted image if available, fallback to API URL
                $proj_url = !empty($projection['wordpress_url']) ? $projection['wordpress_url'] : $projection['uri'];
                $proj_thumb = !empty($projection['thumbnail_url']) ? $projection['thumbnail_url'] : $proj_url;
            ?>
                <a href="<?php echo esc_url($proj_url); ?>" 
                   class="image-item <?php echo $index < 2 ? 'image-thumbnail' : 'image-hidden'; ?>"
                   data-sub-html="<h4><?php echo esc_attr($local['number']); ?></h4><p><?php echo esc_attr($projection['type']); ?></p>">
                    <?php if ($index < 2): ?>
                        <img src="<?php echo esc_url($proj_thumb); ?>" 
                             alt="<?php echo esc_attr($projection['type']); ?>">
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="image-placeholder">
                <span><?php _e('Brak wizualizacji', 'develogic'); ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Gallery info panel (shown in lightbox) -->
        <div class="gallery-info-panel" style="display: none;">
            <div class="info-content">
                <h3><?php echo esc_html($local['number']); ?></h3>
                <p class="status status-<?php echo esc_attr(Develogic_Data_Formatter::get_status_class($local['status'])); ?>">
                    <?php echo esc_html($local['status']); ?>
                </p>
                
                <div class="info-details">
                    <p><strong><?php _e('Kondygnacja:', 'develogic'); ?></strong> <?php echo esc_html(Develogic_Data_Formatter::format_floor($local['floor'])); ?></p>
                    <p><strong><?php _e('Pokoje:', 'develogic'); ?></strong> <?php echo absint($local['rooms']); ?></p>
                    <p><strong><?php _e('Powierzchnia:', 'develogic'); ?></strong> <?php echo Develogic_Data_Formatter::format_area($local['area']); ?></p>
                </div>
                
                <div class="info-prices">
                    <p class="price-total"><?php echo Develogic_Data_Formatter::format_price($local['priceGross']); ?></p>
                    <p class="price-m2"><?php echo Develogic_Data_Formatter::format_price($price_m2); ?> / m²</p>
                </div>
                
                <?php if (!empty($local['plannedDateOfFinishing'])): ?>
                    <p class="finishing-date">
                        <?php echo esc_html(Develogic_Data_Formatter::format_planned_finishing($local['plannedDateOfFinishing'])); ?>
                    </p>
                <?php endif; ?>
                
                <div class="info-actions">
                    <a href="mailto:?subject=<?php echo rawurlencode(sprintf(__('Mieszkanie %s – %s', 'develogic'), $local['number'], $developer_name)); ?>" 
                       class="btn btn-mail">
                        <?php _e('Zapytaj', 'develogic'); ?>
                    </a>
                    
                    <?php if ($atts['show_favorite'] === 'true' || $atts['show_favorite'] === true): ?>
                        <button class="btn btn-favorite" data-local-id="<?php echo esc_attr($local['localId']); ?>">
                            <span class="icon">★</span>
                            <?php _e('Obserwuj', 'develogic'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <?php if (!empty($pdf_link)): ?>
                        <a href="<?php echo esc_url($pdf_link); ?>" 
                           class="btn btn-pdf" 
                           target="_blank">
                            <?php _e('Pobierz kartę mieszkania', 'develogic'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Column 5: Price -->
    <div class="card-column card-price">
        <?php if (!empty($local['priceGross'])): ?>
            <div class="price-total">
                <?php echo Develogic_Data_Formatter::format_price($local['priceGross']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($price_m2)): ?>
            <div class="price-m2">
                <?php echo Develogic_Data_Formatter::format_price($price_m2); ?> / m²
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Column 6: Actions -->
    <div class="card-column card-actions">
        <a href="mailto:?subject=<?php echo rawurlencode(sprintf(__('Mieszkanie %s – %s', 'develogic'), $local['number'], $developer_name)); ?>" 
           class="action-btn action-mail" 
           title="<?php esc_attr_e('Wyślij e-mail', 'develogic'); ?>">
            <span class="icon">✉</span>
        </a>
        
        <?php if ($atts['show_favorite'] === 'true' || $atts['show_favorite'] === true): ?>
            <button class="action-btn action-favorite" 
                    data-local-id="<?php echo esc_attr($local['localId']); ?>"
                    title="<?php esc_attr_e('Dodaj do obserwowanych', 'develogic'); ?>">
                <span class="icon">★</span>
            </button>
        <?php endif; ?>
    </div>
    
</div>

