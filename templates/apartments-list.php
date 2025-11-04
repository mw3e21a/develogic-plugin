<?php
/**
 * Template: Apartments List
 * Nowy layout zgodny z apartment-list.html
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

<div class="container">
    <div class="header">
        <?php if ($atts['show_counters'] === 'true'): ?>
        <div class="stats">
            <?php if (isset($status_counts['Wolny'])): ?>
            <?php echo absint($status_counts['Wolny']); ?> dostępnych
            <?php endif; ?>
            <?php if (isset($status_counts['Rezerwacja'])): ?>
            <?php echo absint($status_counts['Rezerwacja']); ?> rezerwacje
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <h1 class="title">
            <?php 
            $title = !empty($atts['title']) ? $atts['title'] : __('Lista mieszkań', 'develogic');
            echo esc_html($title);
            ?>
        </h1>
        <div class="sort-bar">
            <span class="sort-label">Sortuj po:</span>
            <span class="sort-option active" data-sort="data-floor">Piętro</span>
            <span class="sort-option" data-sort="data-area">Metraż</span>
            <span class="sort-option" data-sort="data-rooms">Pokoje</span>
            <span class="sort-option" data-sort="data-price">Cena</span>
            <span class="sort-option" data-sort="data-price-m2">Cena m²</span>
        </div>
    </div>

    <?php if ($atts['show_favorite'] === 'true' || $atts['show_favorite'] === true): ?>
    <div class="favorites-toggle-container">
        <button class="favorites-toggle-btn active" data-toggle-view="all">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <line x1="9" y1="3" x2="9" y2="21"/>
            </svg>
            Wszystkie
        </button>
        <button class="favorites-toggle-btn" data-toggle-view="favorites">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            Obserwowane
        </button>
        <span class="favorites-count" id="favoritesCount">0 obserwowanych</span>
        
        <div class="favorites-share-container" id="favoritesShareContainer" style="display: none;">
            <span class="share-label">Udostępnij listę na:</span>
            <button class="share-btn share-twitter" data-share="twitter" title="Twitter">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/>
                </svg>
            </button>
            <button class="share-btn share-facebook" data-share="facebook" title="Facebook">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                </svg>
            </button>
            <button class="share-btn share-email" data-share="email" title="E-mail">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="5" width="18" height="14" rx="2"/>
                    <path d="M3 7l9 6 9-6"/>
                </svg>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <div class="apartment-list">
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
                
                // Prepare sortable data
                $rooms_padded = str_pad($local['rooms'], 2, '0', STR_PAD_LEFT);
                $floor_value = $local['floor'];
                // For sorting: convert -1 to 99, keep 0-99 as is, pad to 3 digits
                $floor_sort = ($floor_value == -1) ? 999 : str_pad(max(0, $floor_value), 3, '0', STR_PAD_LEFT);
                $floor_display = Develogic_Data_Formatter::format_floor($local['floor']);
                $floor_padded = $floor_sort;
                $price_padded = str_pad($local['priceGross'], 8, '0', STR_PAD_LEFT);
                $price_m2_padded = str_pad($price_m2, 8, '0', STR_PAD_LEFT);
                $area_padded = str_pad(str_replace(array('.', ','), '', number_format($local['area'], 2, '.', '')), 8, '0', STR_PAD_LEFT);
                
                // Status class
                $status_class = Develogic_Data_Formatter::get_status_class($local['status']);
                
                // Building info
                $building_address = apply_filters('develogic_building_address', '', $local['buildingId']);
                $klatka = apply_filters('develogic_local_klatka', '', $local);
                
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
                
                // Prepare modal data
                $modal_data = array(
                    'localId' => $local['localId'],
                    'number' => $local['number'],
                    'building' => $local['building'],
                    'buildingAddress' => $building_address,
                    'subdivision' => isset($local['subdivision']) ? $local['subdivision'] : '',
                    'status' => $local['status'],
                    'statusClass' => $status_class,
                    'klatka' => $klatka,
                    'floor' => $local['floor'],
                    'floorDisplay' => $floor_display,
                    'area' => $local['area'],
                    'rooms' => $local['rooms'],
                    'tags' => $tags,
                    'priceGross' => $local['priceGross'],
                    'priceM2' => $price_m2,
                    'pdfLink' => $pdf_link,
                    'plannedDate' => isset($local['plannedDateOfFinishing']) ? $local['plannedDateOfFinishing'] : '',
                    'projections' => array()
                );
                
                // Prepare projections data
                foreach ($projections as $proj) {
                    $proj_url = !empty($proj['wordpress_url']) ? $proj['wordpress_url'] : 
                               (!empty($proj['displayUrl']) ? $proj['displayUrl'] : $proj['uri']);
                    $proj_thumb = !empty($proj['thumbnail_url']) ? $proj['thumbnail_url'] : 
                                 (!empty($proj['thumbnailUrl']) ? $proj['thumbnailUrl'] : $proj_url);
                    $modal_data['projections'][] = array(
                        'url' => $proj_url,
                        'thumb' => $proj_thumb,
                        'type' => isset($proj['type']) ? $proj['type'] : ''
                    );
                }
                
                // Add placeholder images to modal if no projections exist
                if (empty($modal_data['projections'])) {
                    $placeholder_house_url = DEVELOGIC_PLUGIN_URL . 'assets/images/placeholder-house.webp';
                    $placeholder_floorplan_url = DEVELOGIC_PLUGIN_URL . 'assets/images/placeholder-floorplan.webp';
                    
                    $modal_data['projections'][] = array(
                        'url' => $placeholder_house_url,
                        'thumb' => $placeholder_house_url,
                        'type' => 'Widok lokalu'
                    );
                    
                    $modal_data['projections'][] = array(
                        'url' => $placeholder_floorplan_url,
                        'thumb' => $placeholder_floorplan_url,
                        'type' => 'Plan mieszkania'
                    );
                }
                
                // Get first two images for list display
                $image1 = null;
                $image2 = null;
                
                // Find main display image
                foreach ($projections as $proj) {
                    if (isset($proj['displayUrl']) && !empty($proj['displayUrl'])) {
                        $image1 = $proj;
                        break;
                    }
                }
                if (empty($image1) && !empty($projections[0])) {
                    $image1 = $projections[0];
                }
                
                // Find plan image
                foreach ($projections as $proj) {
                    if (isset($proj['type']) && stripos($proj['type'], 'plan') !== false) {
                        $image2 = $proj;
                        break;
                    }
                }
                if (empty($image2) && !empty($projections[1])) {
                    $image2 = $projections[1];
                }
                
                $image1_url = '';
                $image1_thumb = '';
                if ($image1) {
                    $image1_url = !empty($image1['wordpress_url']) ? $image1['wordpress_url'] : 
                                 (!empty($image1['displayUrl']) ? $image1['displayUrl'] : $image1['uri']);
                    $image1_thumb = !empty($image1['thumbnail_url']) ? $image1['thumbnail_url'] : 
                                  (!empty($image1['thumbnailUrl']) ? $image1['thumbnailUrl'] : $image1_url);
                }
                
                $image2_url = '';
                $image2_thumb = '';
                if ($image2) {
                    $image2_url = !empty($image2['wordpress_url']) ? $image2['wordpress_url'] : 
                                 (!empty($image2['displayUrl']) ? $image2['displayUrl'] : $image2['uri']);
                    $image2_thumb = !empty($image2['thumbnail_url']) ? $image2['thumbnail_url'] : 
                                  (!empty($image2['thumbnailUrl']) ? $image2['thumbnailUrl'] : $image2_url);
                }
                
                // Placeholder images URLs
                $placeholder_house = DEVELOGIC_PLUGIN_URL . 'assets/images/placeholder-house.webp';
                $placeholder_floorplan = DEVELOGIC_PLUGIN_URL . 'assets/images/placeholder-floorplan.webp';
            ?>
            
            <div class="apartment-item" 
                 data-rooms="<?php echo esc_attr($rooms_padded); ?>"
                 data-floor="<?php echo esc_attr($floor_padded); ?>"
                 data-price="<?php echo esc_attr($price_padded); ?>"
                 data-price-m2="<?php echo esc_attr($price_m2_padded); ?>"
                 data-area="<?php echo esc_attr($area_padded); ?>"
                 data-modal='<?php echo esc_attr(json_encode($modal_data)); ?>'>
                <div class="apartment-info">
                    <div class="building-name"><?php echo esc_html($local['building']); ?></div>
                    <div class="apartment-number"><?php echo esc_html($local['number']); ?></div>
                    <div class="status-badge <?php echo $status_class === 'reserved' ? 'reserved' : ''; ?>">
                        <?php 
                        if ($status_class === 'available') {
                            echo esc_html($local['status']) . '<br>od ręki';
                        } else {
                            echo esc_html($local['status']);
                        }
                        ?>
                    </div>
                </div>

                <div class="apartment-details">
                    <?php if (!empty($klatka)): ?>
                    <div class="detail-row">
                        <span class="detail-label">Klatka</span>
                        <span class="detail-value"><?php echo esc_html($klatka); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <span class="detail-label">Kondygnacja</span>
                        <span class="detail-value"><?php echo esc_html($floor_display); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Powierzchnia</span>
                        <span class="detail-value"><?php echo number_format($local['area'], 2, ',', ' '); ?> m²</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Ilość pokoi</span>
                        <span class="detail-value"><?php echo absint($local['rooms']); ?></span>
                    </div>
                    <?php if (!empty($tags)): ?>
                    <div class="features"><?php echo esc_html(implode(', ', $tags)); ?></div>
                    <?php endif; ?>
                </div>

                <div class="apartment-images">
                    <div class="apartment-image">
                        <img src="<?php echo esc_url($image1_thumb ? $image1_thumb : $placeholder_house); ?>" alt="<?php echo esc_attr($local['number']); ?>">
                    </div>
                    <div class="apartment-image">
                        <img src="<?php echo esc_url($image2_thumb ? $image2_thumb : $placeholder_floorplan); ?>" alt="<?php echo esc_attr($local['number']); ?> - Plan">
                    </div>
                </div>

                <div class="apartment-price">
                    <div class="price-label">Cena</div>
                    <div class="price-main"><?php echo number_format($local['priceGross'], 0, ',', ' '); ?> zł</div>
                    <div class="price-sqm">(<?php echo number_format($price_m2, 2, ',', ' '); ?> zł/m²)</div>
                </div>

                <div class="apartment-actions">
                    <button class="icon-btn" data-action="email" aria-label="<?php esc_attr_e('Wyślij email', 'develogic'); ?>">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <path d="M3 7l9 6 9-6"/>
                        </svg>
                    </button>
                    <?php if ($atts['show_favorite'] === 'true' || $atts['show_favorite'] === true): ?>
                    <button class="icon-btn" data-action="favorite" data-local-id="<?php echo esc_attr($local['localId']); ?>" aria-label="<?php esc_attr_e('Dodaj do ulubionych', 'develogic'); ?>">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Apartment Detail Modal -->
<div id="apartment-detail-modal" class="apartment-detail-modal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <button class="modal-close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>

        <div class="modal-header">
            <h2 class="modal-title"></h2>
        </div>

        <div class="modal-body">
            <div class="modal-gallery">
                <div class="gallery-main">
                    <img src="" alt="" class="gallery-main-image">
                    <button class="gallery-nav prev">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <button class="gallery-nav next">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <div class="gallery-controls">
                        <button class="gallery-control gallery-zoom-in" title="Powiększ">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="M11 8v6M8 11h6"/>
                            </svg>
                        </button>
                        <button class="gallery-control gallery-zoom-out" title="Pomniejsz">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="M8 11h6"/>
                            </svg>
                        </button>
                        <button class="gallery-control gallery-zoom-reset" title="Resetuj zoom">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 3l3 3m15 0l-3-3M3 21l3-3m15 0l-3 3M21 3v6M3 3v6m18 12v-6M3 21v-6"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                        <button class="gallery-control gallery-fullscreen" title="Pełny ekran">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="fullscreen-open">
                                <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                            </svg>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="fullscreen-close" style="display: none;">
                                <path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="gallery-thumbnails"></div>
            </div>

            <div class="modal-details">
                <div class="detail-header">
                    <div class="location"></div>
                    <h1 class="unit-name"></h1>
                    <div class="status"></div>
                </div>

                <div class="detail-specs">
                    <div class="spec-item">
                        <span class="spec-label"></span>
                        <span class="spec-value"></span>
                    </div>
                </div>

                <div class="detail-features"></div>

                <div class="detail-price">
                    <div class="price-label">Cena</div>
                    <div class="price-main"></div>
                    <div class="price-per-m2"></div>
                </div>

                <div class="info-box" style="display: none;">
                    <div class="info-text"></div>
                    <a href="#" class="download-link" style="display: none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Pobierz kartę mieszkania
                    </a>
                </div>

            <div class="action-buttons">
                <button class="icon-btn" data-action="email-modal" aria-label="<?php esc_attr_e('Wyślij email', 'develogic'); ?>">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="5" width="18" height="14" rx="2"/>
                        <path d="M3 7l9 6 9-6"/>
                    </svg>
                </button>
                <?php if ($atts['show_favorite'] === 'true' || $atts['show_favorite'] === true): ?>
                <button class="icon-btn" data-action="favorite-modal" aria-label="<?php esc_attr_e('Dodaj do ulubionych', 'develogic'); ?>">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

