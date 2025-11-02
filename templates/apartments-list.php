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
                    <?php if ($image1_thumb): ?>
                    <div class="apartment-image">
                        <img src="<?php echo esc_url($image1_thumb); ?>" alt="<?php echo esc_attr($local['number']); ?>">
                    </div>
                    <?php endif; ?>
                    <?php if ($image2_thumb): ?>
                    <div class="apartment-image">
                        <img src="<?php echo esc_url($image2_thumb); ?>" alt="<?php echo esc_attr($local['number']); ?> - Plan">
                    </div>
                    <?php endif; ?>
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

<!-- Apartment Detail Modal -->
<div id="apartment-detail-modal" class="apartment-detail-modal" style="display: none;">
    <div class="header">
        <div class="header-title"></div>
        <button class="close-btn">✕</button>
    </div>

    <div class="container">
        <div class="left-panel">
            <div class="main-image">
                <img src="" alt="">
            </div>
            <div class="gallery"></div>
        </div>

        <div class="right-panel">
            <div class="location"></div>
            <h1 class="unit-name"></h1>
            <div class="status"></div>

            <div class="section">
                <div class="detail-grid"></div>
            </div>

            <div class="section">
                <div class="features"></div>
            </div>

            <div class="section">
                <div class="section-label">Cena</div>
                <div class="price-main"></div>
                <div class="price-sqm"></div>
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

