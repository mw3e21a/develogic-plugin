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

<div class="develogic-apartments-container" 
     <?php if ($is_residential_local && !empty($building_floors_map)): ?>
     data-building-floors-map="<?php echo esc_attr(json_encode($building_floors_map)); ?>"
     data-enable-dynamic-floors="true"
     <?php endif; ?>>
    <!-- Loading Spinner -->
    <div class="develogic-loading-overlay">
        <div class="develogic-spinner">
            <div class="develogic-spinner-ring"></div>
            <div class="develogic-spinner-ring"></div>
            <div class="develogic-spinner-ring"></div>
            <div class="develogic-spinner-ring"></div>
        </div>
        <p class="develogic-loading-text">Ładowanie mieszkań...</p>
    </div>

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
            // Check if this is a garage listing
            $is_garage = false;
            if ($default_local_type === 'Garaż') {
                $is_garage = true;
            } elseif (!empty($atts['local_types'])) {
                $local_types_array = array_map('trim', explode(',', $atts['local_types']));
                if (in_array('Garaż', $local_types_array) || in_array('Garaz', $local_types_array)) {
                    $is_garage = true;
                }
            }
            
            // Set title based on context
            if (!empty($atts['title'])) {
                $title = $atts['title'];
            } elseif ($is_garage) {
                $title = 'Garaże - Wygoda, spokój i bezpieczeństwo';
            } else {
                $title = __('Lista mieszkań', 'develogic');
            }
            echo esc_html($title);
            ?>
        </h1>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-row">
            <div class="filter-group">
                <label class="filter-label">Ilość pokoi:</label>
                <div class="filter-chips" id="roomsFilter">
                    <?php
                    $default_rooms = !empty($atts['rooms']) ? $atts['rooms'] : 'all';
                    ?>
                    <button class="filter-chip <?php echo ($default_rooms === 'all') ? 'active' : ''; ?>" data-value="all">Wszystkie</button>
                    <button class="filter-chip <?php echo ($default_rooms === '1') ? 'active' : ''; ?>" data-value="1">1</button>
                    <button class="filter-chip <?php echo ($default_rooms === '2') ? 'active' : ''; ?>" data-value="2">2</button>
                    <button class="filter-chip <?php echo ($default_rooms === '3') ? 'active' : ''; ?>" data-value="3">3</button>
                    <button class="filter-chip <?php echo ($default_rooms === '4') ? 'active' : ''; ?>" data-value="4">4</button>
                    <button class="filter-chip <?php echo ($default_rooms === '5') ? 'active' : ''; ?>" data-value="5">5+</button>
                </div>
            </div>
            
            <div class="filter-selects-wrapper">
                <div class="filter-group filter-group-local-type">
                    <label class="filter-label">Typ lokalu:</label>
                    <select class="filter-select" id="localTypeFilter">
                        <?php
                        // Show all available local types in the filter
                        $available_types = array();
                        foreach ($locals as $local) {
                            if (!empty($local['localType'])) {
                                $type = $local['localType'];
                                if (!in_array($type, $available_types)) {
                                    $available_types[] = $type;
                                }
                            }
                        }
                        
                        // Define display order for types
                        $type_order = array('Lokal mieszkalny', 'Garaż', 'Komórka lokatorska', 'Pomieszczenie gospodarcze', 'Miejsce postojowe');
                        $ordered_types = array();
                        foreach ($type_order as $ordered_type) {
                            if (in_array($ordered_type, $available_types)) {
                                $ordered_types[] = $ordered_type;
                            }
                        }
                        // Add any other types that weren't in the order list
                        foreach ($available_types as $type) {
                            if (!in_array($type, $ordered_types)) {
                                $ordered_types[] = $type;
                            }
                        }
                        
                        // Set default type
                        if (!empty($default_local_type)) {
                            // Check if "all" is explicitly set
                            if ($default_local_type === 'all') {
                                $default_type = 'all';
                            } elseif (in_array($default_local_type, $available_types)) {
                                // Use default from shortcode if it exists in available types
                                $default_type = $default_local_type;
                            } else {
                                // Fallback to first available type
                                $default_type = !empty($ordered_types) ? $ordered_types[0] : 'all';
                            }
                        } elseif (!empty($ordered_types)) {
                            // Fallback to first available type
                            $default_type = $ordered_types[0];
                        } else {
                            // Otherwise use "all"
                            $default_type = 'all';
                        }
                        
                        echo '<option value="all"' . ($default_type === 'all' ? ' selected' : '') . '>Wszystkie typy</option>';
                        
                        // Show all available types in defined order
                        foreach ($ordered_types as $type) {
                            $is_selected = ($default_type === $type) ? ' selected' : '';
                            echo '<option value="' . esc_attr($type) . '"' . $is_selected . '>' . esc_html($type) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <?php if (empty($hide_building_filter)): ?>
                <div class="filter-group filter-group-building">
                    <label class="filter-label">Budynek:</label>
                    <select class="filter-select" id="buildingFilter">
                        <?php
                        // Get default building from shortcode
                        $default_building = '';
                        if (!empty($atts['building'])) {
                            // Use building name directly from shortcode (preferred)
                            // Only set default if it's a single building (no comma)
                            if (strpos($atts['building'], ',') === false) {
                                $default_building = trim($atts['building']);
                            }
                            // For multiple buildings, leave "Wszystkie budynki" selected
                        } elseif (!empty($atts['building_id'])) {
                            // Legacy support - get building name from ID
                            foreach ($buildings as $building) {
                                if ($building['id'] == $atts['building_id']) {
                                    $default_building = $building['name'];
                                    break;
                                }
                            }
                        }
                        ?>
                        <option value="all" <?php echo empty($default_building) ? 'selected' : ''; ?>>Wszystkie budynki</option>
                        <?php
                        // Get unique buildings
                        $buildings_list = array();
                        foreach ($locals as $local) {
                            if (!empty($local['building']) && !in_array($local['building'], $buildings_list)) {
                                $buildings_list[] = $local['building'];
                            }
                        }
                        sort($buildings_list);
                        foreach ($buildings_list as $building) {
                            $is_selected = ($building === $default_building) ? ' selected' : '';
                            echo '<option value="' . esc_attr($building) . '"' . $is_selected . '>' . esc_html($building) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <?php if (empty($hide_floor_filter)): ?>
                <div class="filter-group filter-group-floor">
                    <label class="filter-label">Piętro:</label>
                    <select class="filter-select" id="floorFilter">
                        <?php $default_floor = isset($atts['floor']) && $atts['floor'] !== '' ? $atts['floor'] : 'all'; ?>
                        <option value="all" <?php echo ($default_floor === 'all') ? 'selected' : ''; ?>>Wszystkie piętra</option>
                        <option value="0" <?php echo ($default_floor === '0') ? 'selected' : ''; ?>>Parter</option>
                        <option value="1" <?php echo ($default_floor === '1') ? 'selected' : ''; ?>>Piętro I</option>
                        <option value="2" <?php echo ($default_floor === '2') ? 'selected' : ''; ?>>Piętro II</option>
                        <option value="3" <?php echo ($default_floor === '3') ? 'selected' : ''; ?>>Piętro III</option>
                        <option value="4" <?php echo ($default_floor === '4') ? 'selected' : ''; ?>>Piętro IV</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="filter-row">
            <div class="filter-group">
                <label class="filter-label">Metraż (m²):</label>
                <div class="filter-range">
                    <input type="number" class="filter-input" id="areaMin" placeholder="od" step="1" min="0" value="<?php echo !empty($atts['min_area']) ? esc_attr($atts['min_area']) : ''; ?>">
                    <span class="filter-separator">-</span>
                    <input type="number" class="filter-input" id="areaMax" placeholder="do" step="1" min="0" value="<?php echo !empty($atts['max_area']) ? esc_attr($atts['max_area']) : ''; ?>">
                </div>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Cena za lokal (zł):</label>
                <div class="filter-range">
                    <input type="number" class="filter-input" id="priceMin" placeholder="od" step="10000" min="0" value="<?php echo !empty($atts['min_price_gross']) ? esc_attr($atts['min_price_gross']) : ''; ?>">
                    <span class="filter-separator">-</span>
                    <input type="number" class="filter-input" id="priceMax" placeholder="do" step="10000" min="0" value="<?php echo !empty($atts['max_price_gross']) ? esc_attr($atts['max_price_gross']) : ''; ?>">
                </div>
            </div>
            
            <?php if (empty($hide_extras)): ?>
            <div class="filter-group filter-extras">
                <label class="filter-label">Opcje dodatkowe:</label>
                <div class="filter-checkboxes">
                    <label class="filter-checkbox">
                        <input type="checkbox" id="promoFilter" value="promo">
                        <span>W promocji</span>
                    </label>
                    <label class="filter-checkbox">
                        <input type="checkbox" id="bathFilter" value="2bath">
                        <span>2 łazienki</span>
                    </label>
                    <label class="filter-checkbox">
                        <input type="checkbox" id="wardrobeFilter" value="wardrobe">
                        <span>Z garderobą</span>
                    </label>
                </div>
            </div>
            
            <div class="filter-group filter-actions">
                <button class="filter-reset-btn" id="resetFilters">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                        <path d="M21 3v5h-5"/>
                        <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                        <path d="M3 21v-5h5"/>
                    </svg>
                    Resetuj filtry
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($hide_extras)): ?>
        <div class="filter-row">
            <div class="filter-group filter-actions">
                <button class="filter-reset-btn" id="resetFilters">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                        <path d="M21 3v5h-5"/>
                        <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                        <path d="M3 21v-5h5"/>
                    </svg>
                    Resetuj filtry
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($atts['show_favorite'] === 'true' || $atts['show_favorite'] === true): ?>
    <div class="favorites-toggle-container">
        <div class="toggle-buttons-wrapper">
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
        </div>
        
        <div class="sort-bar-right">
            <span class="sort-label">Sortuj po:</span>
            <span class="sort-option active" data-sort="data-floor" data-direction="asc">
                Piętro
                <span class="sort-arrow">
                    <svg class="arrow-up" viewBox="0 0 12 8" fill="currentColor">
                        <path d="M6 0L0 8h12L6 0z"/>
                    </svg>
                    <svg class="arrow-down" viewBox="0 0 12 8" fill="currentColor">
                        <path d="M6 8L0 0h12L6 8z"/>
                    </svg>
                </span>
            </span>
            <span class="sort-option" data-sort="data-area" data-direction="asc">
                Metraż
                <span class="sort-arrow">
                    <svg class="arrow-up" viewBox="0 0 12 8" fill="currentColor">
                        <path d="M6 0L0 8h12L6 0z"/>
                    </svg>
                    <svg class="arrow-down" viewBox="0 0 12 8" fill="currentColor">
                        <path d="M6 8L0 0h12L6 8z"/>
                    </svg>
                </span>
            </span>
            <span class="sort-option" data-sort="data-rooms" data-direction="asc">
                Pokoje
                <span class="sort-arrow">
                    <svg class="arrow-up" viewBox="0 0 12 8" fill="currentColor">
                        <path d="M6 0L0 8h12L6 0z"/>
                    </svg>
                    <svg class="arrow-down" viewBox="0 0 12 8" fill="currentColor">
                        <path d="M6 8L0 0h12L6 8z"/>
                    </svg>
                </span>
            </span>
            <span class="sort-option" data-sort="data-price" data-direction="asc">
                Cena
                <span class="sort-arrow">
                    <svg class="arrow-up" viewBox="0 0 12 8" fill="currentColor">
                        <path d="M6 0L0 8h12L6 0z"/>
                    </svg>
                    <svg class="arrow-down" viewBox="0 0 12 8" fill="currentColor">
                        <path d="M6 8L0 0h12L6 8z"/>
                    </svg>
                </span>
            </span>
            <span class="sort-option" data-sort="data-price-m2" data-direction="asc">
                Cena m²
                <span class="sort-arrow">
                    <svg class="arrow-up" viewBox="0 0 12 8" fill="currentColor">
                        <path d="M6 0L0 8h12L6 0z"/>
                    </svg>
                    <svg class="arrow-down" viewBox="0 0 12 8" fill="currentColor">
                        <path d="M6 8L0 0h12L6 8z"/>
                    </svg>
                </span>
            </span>
        </div>
        
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
        <div class="no-favorites-placeholder" style="display: none;">
            <div class="placeholder-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
            </div>
            <h3>Brak obserwowanych ofert</h3>
            <p>Kliknij ikonę gwiazdki przy wybranym mieszkaniu, aby dodać je do obserwowanych</p>
        </div>
        <?php if (empty($locals)): ?>
            <div class="no-results">
                <p><?php _e('Brak dostępnych nieruchomości', 'develogic'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($locals as $local): 
                // Prepare data
                $price_m2_source = develogic()->get_setting('price_m2_source', 'priceGrossm2');
                $price_m2 = isset($local[$price_m2_source]) ? $local[$price_m2_source] : $local['priceGrossm2'];
                $developer_name = develogic()->get_setting('developer_name', get_bloginfo('name'));
                
                // Projections - MUST be loaded first before using
                $projections = isset($local['projections']) ? $local['projections'] : array();
                
                // Determine which price to use (packagePriceGross or packagePromoPriceGross)
                $package_price = isset($local['packagePriceGross']) ? $local['packagePriceGross'] : $local['priceGross'];
                $package_promo_price = isset($local['packagePromoPriceGross']) ? $local['packagePromoPriceGross'] : null;
                
                // Check if there's a promotion (promo price exists and is lower than package price)
                $has_package_promo = false;
                $display_price = $package_price;
                $old_price = null;
                
                if ($package_promo_price && $package_promo_price < $package_price) {
                    $has_package_promo = true;
                    $display_price = $package_promo_price;
                    $old_price = $package_price;
                }
                
                // For price_m2, use corresponding package price
                if ($has_package_promo && isset($local['packagePromoPriceGrossm2'])) {
                    $price_m2 = $local['packagePromoPriceGrossm2'];
                } elseif (isset($local['packagePriceGrossm2'])) {
                    $price_m2 = $local['packagePriceGrossm2'];
                } else {
                    $price_m2 = isset($local[$price_m2_source]) ? $local[$price_m2_source] : $local['priceGrossm2'];
                }
                
                // PDF link - get WordPress PDF URL from "Karta lokalu" projection
                $pdf_link = '';
                foreach ($projections as $proj) {
                    if (isset($proj['type']) && $proj['type'] === 'Karta lokalu' && !empty($proj['pdf_url'])) {
                        $pdf_link = $proj['pdf_url'];
                        break;
                    }
                }
                
                // Fallback to pattern-based PDF link if projection not found
                if (empty($pdf_link)) {
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
                }
                
                // Prepare sortable data
                $rooms_padded = str_pad($local['rooms'], 2, '0', STR_PAD_LEFT);
                $floor_value = $local['floor'];
                // For sorting: convert -1 to 99, keep 0-99 as is, pad to 3 digits
                $floor_sort = ($floor_value == -1) ? 999 : str_pad(max(0, $floor_value), 3, '0', STR_PAD_LEFT);
                $floor_display = Develogic_Data_Formatter::format_floor($local['floor']);
                $floor_padded = $floor_sort;
                $price_padded = str_pad($display_price, 8, '0', STR_PAD_LEFT);
                $price_m2_padded = str_pad($price_m2, 8, '0', STR_PAD_LEFT);
                $area_padded = str_pad(str_replace(array('.', ','), '', number_format($local['area'], 2, '.', '')), 8, '0', STR_PAD_LEFT);
                
                // Normalize status display
                $display_status = $local['status'];
                if ($display_status === 'Miękka rezerwacja') {
                    $display_status = 'Rezerwacja';
                }
                
                // Status class
                $status_class = Develogic_Data_Formatter::get_status_class($local['status']);
                
                // Building info
                $building_address = apply_filters('develogic_building_address', '', $local['buildingId']);
                $klatka = apply_filters('develogic_local_klatka', '', $local);
                
                // Tags from attributes
                $tags = array();
                $all_attributes = array(); // All attributes for filtering
                
                if (!empty($local['attributes'])) {
                    // Extract all attribute names for filtering
                    foreach ($local['attributes'] as $attr) {
                        if (isset($attr['name'])) {
                            $all_attributes[] = $attr['name'];
                        }
                    }
                    
                    // Filter for display using whitelist
                    $tag_whitelist = apply_filters('develogic_attribute_whitelist', array(
                        'aneks kuchenny',
                        'balkon',
                        '2 balkony',
                        'taras',
                        'ogród',
                        'garderoba',
                        'jasna kuchnia',
                        'winda',
                        'plac zabaw',
                        'osobne WC',
                        '2 lazienki',
                        'pom. gospodarcze',
                        'komórka lokatorska',
                        'klimatyzacja',
                        'parking',
                        'miejsce postojowe'
                    ));
                    
                    foreach ($local['attributes'] as $attr) {
                        if (isset($attr['name']) && in_array(strtolower($attr['name']), array_map('strtolower', $tag_whitelist))) {
                            $tags[] = $attr['name'];
                        }
                    }
                }
                
                // Check if apartment has promotion (do this once and reuse)
                $has_promo = (!empty($local['maxDiscountPercent']) && $local['maxDiscountPercent'] > 0);
                if (!$has_promo && !empty($all_attributes)) {
                    foreach ($all_attributes as $attr_name) {
                        $attr_lower = strtolower(trim($attr_name));
                        if ($attr_lower === 'promocja' || strpos($attr_lower, 'promocja') !== false) {
                            $has_promo = true;
                            break;
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
                    'status' => $display_status,
                    'statusClass' => $status_class,
                    'klatka' => $klatka,
                    'floor' => $local['floor'],
                    'floorDisplay' => $floor_display,
                    'area' => $local['area'],
                    'rooms' => $local['rooms'],
                    'tags' => $tags,
                    'priceGross' => $display_price,
                    'oldPriceGross' => $old_price,
                    'priceM2' => $price_m2,
                    'omnibusPriceGross' => isset($local['omnibusPriceGross']) ? $local['omnibusPriceGross'] : 0,
                    'omnibusPriceGrossm2' => isset($local['omnibusPriceGrossm2']) ? $local['omnibusPriceGrossm2'] : 0,
                    'pdfLink' => $pdf_link,
                    'plannedDate' => isset($local['plannedDateOfFinishing']) ? $local['plannedDateOfFinishing'] : '',
                    'hasPromo' => $has_package_promo,
                    'projections' => array(),
                    'tour3dUrl' => '' // Will be set below
                );
                
                // Find 3D tour link - prioritize 'stage' field if it's a URL
                $tour_3d_url = '';
                if (!empty($local['stage']) && (strpos($local['stage'], 'http://') === 0 || strpos($local['stage'], 'https://') === 0)) {
                    // If stage is a URL, use it
                    $tour_3d_url = $local['stage'];
                } else {
                    // Otherwise, use displayUrl from projections
                    foreach ($projections as $proj) {
                        if (!empty($proj['displayUrl'])) {
                            $tour_3d_url = $proj['displayUrl'];
                            break; // Use the first displayUrl found
                        }
                    }
                }
                $modal_data['tour3dUrl'] = $tour_3d_url;
                
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
                
                // No placeholder images - if no projections exist, modal will show empty state
                
                // Get first two images for list display
                // Order: 1. Karta lokalu, 2. Aranżacyjny (already sorted in sync)
                $image1 = null; // Karta lokalu
                $image2 = null; // Aranżacyjny
                
                // Projections are already sorted: Karta lokalu, Aranżacyjny, Położenie na kondygnacji
                // Image 1: Karta lokalu (first)
                if (!empty($projections[0])) {
                    $image1 = $projections[0];
                }
                
                // Image 2: Aranżacyjny (second)
                if (!empty($projections[1])) {
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
                 data-building="<?php echo esc_attr($local['building']); ?>"
                 data-local-type="<?php echo esc_attr(isset($local['localType']) ? $local['localType'] : ''); ?>"
                 data-floor-number="<?php echo esc_attr($floor_value); ?>"
                 data-area-value="<?php echo esc_attr($local['area']); ?>"
                 data-price-value="<?php echo esc_attr($display_price); ?>"
                 data-rooms-value="<?php echo esc_attr($local['rooms']); ?>"
                 data-has-promo="<?php echo $has_package_promo ? 'true' : 'false'; ?>"
                 data-attributes="<?php echo esc_attr(json_encode($all_attributes)); ?>"
                 data-modal='<?php echo esc_attr(json_encode($modal_data)); ?>'>
                <div class="apartment-info">
                    <div class="building-name"><?php echo esc_html($local['building']); ?></div>
                    <div class="apartment-number"><?php echo esc_html($local['number']); ?></div>
                    <div class="status-badge <?php 
                        if ($status_class === 'reserved') {
                            echo 'reserved';
                        } elseif ($status_class === 'sold') {
                            echo 'sold';
                        }
                    ?>">
                        <?php 
                        if ($status_class === 'available') {
                            echo 'Dostępne';
                        } else {
                            echo esc_html($display_status);
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
                        <?php if ($image1_thumb): ?>
                            <img src="<?php echo esc_url($image1_thumb); ?>" alt="<?php echo esc_attr($local['number']); ?>">
                        <?php else: ?>
                            <div class="no-image-placeholder">Brak zdjęcia</div>
                        <?php endif; ?>
                    </div>
                    <div class="apartment-image">
                        <?php if ($image2_thumb): ?>
                            <img src="<?php echo esc_url($image2_thumb); ?>" alt="<?php echo esc_attr($local['number']); ?> - Plan">
                        <?php else: ?>
                            <div class="no-image-placeholder">Brak planu</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="apartment-price">
                    <div class="price-label">Cena</div>
                    <?php if ($has_package_promo && $old_price): ?>
                        <div class="price-old"><?php echo number_format($old_price, 0, ',', ' '); ?> zł</div>
                    <?php endif; ?>
                    <div class="price-main"><?php echo number_format($display_price, 0, ',', ' '); ?> zł</div>
                    <div class="price-sqm">(<?php echo number_format($price_m2, 2, ',', ' '); ?> zł/m²)</div>
                    <?php if ($has_package_promo && !empty($local['omnibusPriceGross']) && $local['omnibusPriceGross'] > 0): ?>
                        <div class="price-omnibus-label">Najniższa cena z ostatnich 30 dni</div>
                        <div class="price-omnibus-value"><?php echo number_format($local['omnibusPriceGross'], 0, ',', ' '); ?> zł</div>
                    <?php endif; ?>
                    <?php if ($has_package_promo): ?>
                        <div class="promo-badge">Promocja</div>
                    <?php endif; ?>
                </div>

                <div class="apartment-actions">
                    <?php if (!empty($tour_3d_url)): ?>
                    <a href="<?php echo esc_url($tour_3d_url); ?>" class="icon-btn icon-btn-3d icon-btn-360" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Zobacz spacer 3D 360°', 'develogic'); ?>" title="Zobacz spacer 3D 360° (otwiera w nowej karcie)" onclick="event.stopPropagation();">
                        <span class="text-360">360°</span>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($pdf_link)): ?>
                    <a href="<?php echo esc_url($pdf_link); ?>" class="icon-btn icon-btn-pdf" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Pobierz kartę lokalu', 'develogic'); ?>" title="Pobierz kartę lokalu (otwiera PDF w nowej karcie)" onclick="event.stopPropagation();">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                    <button class="icon-btn" data-action="email" aria-label="<?php esc_attr_e('Wyślij email', 'develogic'); ?>" title="Zapytaj o mieszkanie (otwiera klienta email)">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <path d="M3 7l9 6 9-6"/>
                        </svg>
                    </button>
                    <?php if ($atts['show_favorite'] === 'true' || $atts['show_favorite'] === true): ?>
                    <button class="icon-btn" data-action="favorite" data-local-id="<?php echo esc_attr($local['localId']); ?>" aria-label="<?php esc_attr_e('Dodaj do ulubionych', 'develogic'); ?>" title="Dodaj do obserwowanych">
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

        <div class="modal-body">
            <div class="modal-gallery">
                <div class="gallery-main">
                    <div class="gallery-promo-badge" style="display: none;">Promocja</div>
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

                <!-- 3D Tour Link -->
                <a href="#" class="tour-3d-link" target="_blank" rel="noopener noreferrer" style="display: none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <span class="tour-3d-text">Zobacz spacer 3D</span>
                    <svg class="tour-3d-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>

                <!-- Download Card Link -->
                <a href="#" class="tour-3d-link download-card-link" target="_blank" rel="noopener noreferrer" style="display: none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span class="tour-3d-text">Pobierz kartę mieszkania</span>
                    <svg class="tour-3d-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>

                <!-- Promo Banner (full width like 3D tour) -->
                <div class="promo-banner-link" style="display: none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                    <span class="promo-banner-text">Promocja</span>
                    <svg class="promo-banner-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </div>

                <div class="detail-features"></div>

                <div class="detail-price">
                    <div class="price-label">Cena</div>
                    <div class="price-main"></div>
                    <div class="price-per-m2"></div>
                </div>

                <!-- Omnibus price section -->
                <div class="detail-price-omnibus" style="display: none;">
                    <div class="omnibus-label">Najniższa cena z ostatnich 30 dni</div>
                    <div class="omnibus-value"></div>
                </div>

                <!-- Price history section -->
                <div class="detail-price-history">
                    <div class="price-history-label">Historia ceny</div>
                    <div class="price-history-content">
                        <div class="price-history-loader" style="display:none;"></div>
                        <table class="price-history-table" style="display:none;">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th style="text-align:right;">Cena</th>
                                </tr>
                            </thead>
                            <tbody class="price-history-list"></tbody>
                        </table>
                        <div class="price-history-empty" style="display:none;"></div>
                    </div>
                </div>


        </div>
    </div>
    </div><!-- .container -->
</div><!-- .develogic-apartments-container -->

