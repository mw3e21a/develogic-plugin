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

<?php
// Count unique floors in the dataset for conditional floor sorting
$unique_floors_in_data = array();
foreach ($locals as $local) {
    $f = isset($local['floor']) ? $local['floor'] : '';
    if ($f !== '' && $f !== null && !in_array($f, $unique_floors_in_data)) {
        $unique_floors_in_data[] = $f;
    }
}
$has_multiple_floors = count($unique_floors_in_data) > 1;
?>
<div class="develogic-apartments-container"
     <?php if ($is_residential_local && !empty($building_floors_map)): ?>
     data-building-floors-map="<?php echo esc_attr(json_encode($building_floors_map)); ?>"
     data-enable-dynamic-floors="true"
     <?php endif; ?>
     <?php if (!empty($kl_pg_floors)): ?>
     data-kl-pg-floors="<?php echo esc_attr(json_encode($kl_pg_floors)); ?>"
     <?php endif; ?>
     <?php if (isset($atts['floor']) && $atts['floor'] !== ''): ?>
     data-default-floor="<?php echo esc_attr($atts['floor']); ?>"
     <?php endif; ?>
     data-multiple-floors="<?php echo $has_multiple_floors ? 'true' : 'false'; ?>"
     <?php if (!empty($investment_id)): ?>
     data-investment-id="<?php echo esc_attr($investment_id); ?>"
     <?php endif; ?>
     <?php if (!empty($page_id)): ?>
     data-page-id="<?php echo esc_attr($page_id); ?>"
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
            <div class="filter-selects-wrapper">
                <div class="filter-group filter-group-local-type">
                    <label class="filter-label">Typ lokalu:</label>
                    <select class="filter-select" id="localTypeFilter">
                        <?php
                        // Show all available local types in the filter
                        // "Pomieszczenie gospodarcze" is merged with "Komórka lokatorska"
                        $available_types = array();
                        foreach ($locals as $local) {
                            if (!empty($local['localType'])) {
                                $type = $local['localType'];
                                // Remap "Pomieszczenie gospodarcze" to "Komórka lokatorska"
                                if ($type === 'Pomieszczenie gospodarcze') {
                                    $type = 'Komórka lokatorska';
                                }
                                if (!in_array($type, $available_types)) {
                                    $available_types[] = $type;
                                }
                            }
                        }
                        
                        // If local_types is specified in shortcode, add those types to available types
                        // This ensures types are shown in select even if no locals of that type exist in current data
                        if (!empty($atts['local_types'])) {
                            $shortcode_types = array_map('trim', explode(',', $atts['local_types']));
                            foreach ($shortcode_types as $type) {
                                if (!in_array($type, $available_types)) {
                                    $available_types[] = $type;
                                }
                            }
                        }
                        
                        // Define display order for types
                        $type_order = array('Lokal mieszkalny', 'Garaż', 'Komórka lokatorska', 'Miejsce postojowe');
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
                <div class="filter-group filter-group-building filter-mobile-hidden">
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
                        <?php
                        // Get available buildings list from shortcode parameter
                        $available_buildings_list = array();
                        if (!empty($atts['available_buildings'])) {
                            $available_buildings_list = array_map('trim', explode(',', $atts['available_buildings']));
                        }
                        
                        // Get unique buildings from locals
                        $buildings_list = array();
                        foreach ($locals as $local) {
                            if (!empty($local['building']) && !in_array($local['building'], $buildings_list)) {
                                // If available_buildings is set, only include buildings from that list
                                if (empty($available_buildings_list) || in_array($local['building'], $available_buildings_list)) {
                                    $buildings_list[] = $local['building'];
                                }
                            }
                        }
                        sort($buildings_list);
                        
                        // Show "Wszystkie budynki" option only if there are multiple buildings or no available_buildings filter
                        if (empty($available_buildings_list) || count($buildings_list) > 1) {
                            echo '<option value="all" ' . (empty($default_building) ? 'selected' : '') . '>Wszystkie budynki</option>';
                        }
                        
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
                        <?php 
                        $default_floor = isset($atts['floor']) && $atts['floor'] !== '' ? $atts['floor'] : 'all';
                        
                        // Get all available floors from KL/PG if they exist
                        $available_floors = array('0', '1', '2', '3', '4'); // Default floors
                        
                        // Check if kl_pg_floors is defined and not empty
                        if (isset($kl_pg_floors) && !empty($kl_pg_floors) && is_array($kl_pg_floors)) {
                            // Add KL/PG floors to available floors
                            foreach ($kl_pg_floors as $floor) {
                                // Ensure floor is a string for comparison
                                $floor_str = (string) $floor;
                                if (!in_array($floor_str, $available_floors)) {
                                    $available_floors[] = $floor_str;
                                }
                            }
                            // Sort floors numerically
                            usort($available_floors, function($a, $b) {
                                return intval($a) <=> intval($b);
                            });
                        }
                        
                        // Floor labels mapping
                        $floor_labels = array(
                            '-1' => 'Piwnica',
                            '0' => 'Parter',
                            '1' => 'Piętro I',
                            '2' => 'Piętro II',
                            '3' => 'Piętro III',
                            '4' => 'Piętro IV',
                            '5' => 'Piętro V',
                            '6' => 'Piętro VI',
                        );
                        ?>
                        <option value="all" <?php echo ($default_floor === 'all') ? 'selected' : ''; ?>>Wszystkie piętra</option>
                        <?php
                        foreach ($available_floors as $floor) {
                            $floor_label = isset($floor_labels[$floor]) ? $floor_labels[$floor] : 'Piętro ' . $floor;
                            $is_selected = ($default_floor === $floor) ? ' selected' : '';
                            echo '<option value="' . esc_attr($floor) . '"' . $is_selected . '>' . esc_html($floor_label) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="filter-group filter-mobile-hidden">
                    <label class="filter-label">Ilość pokoi:</label>
                    <div class="filter-chips" id="roomsFilter">
                        <?php
                        $default_rooms = !empty($atts['rooms']) ? $atts['rooms'] : 'all';
                        ?>
                        <button class="filter-chip <?php echo ($default_rooms === 'all') ? 'active' : ''; ?>" data-value="all">Wszystkie</button>
                        <button class="filter-chip <?php echo ($default_rooms === '1') ? 'active' : ''; ?>" data-value="1">1</button>
                        <button class="filter-chip <?php echo ($default_rooms === '2') ? 'active' : ''; ?>" data-value="2">2</button>
                        <button class="filter-chip <?php echo ($default_rooms === '3') ? 'active' : ''; ?>" data-value="3">3</button>
                        <button class="filter-chip <?php echo ($default_rooms === '4') ? 'active' : ''; ?>" data-value="4">4+</button>
                    </div>
                </div>

                <div class="filter-group filter-group-area filter-mobile-hidden">
                    <label class="filter-label">Metraż (m²):</label>
                    <div class="filter-range">
                        <select class="filter-select" id="areaMin">
                            <option value="">od</option>
                            <option value="1" <?php selected(!empty($atts['min_area']) ? $atts['min_area'] : '', '1'); ?>>1m²</option>
                            <option value="10" <?php selected(!empty($atts['min_area']) ? $atts['min_area'] : '', '10'); ?>>10m²</option>
                            <option value="20" <?php selected(!empty($atts['min_area']) ? $atts['min_area'] : '', '20'); ?>>20m²</option>
                            <option value="30" <?php selected(!empty($atts['min_area']) ? $atts['min_area'] : '', '30'); ?>>30m²</option>
                            <option value="40" <?php selected(!empty($atts['min_area']) ? $atts['min_area'] : '', '40'); ?>>40m²</option>
                            <option value="50" <?php selected(!empty($atts['min_area']) ? $atts['min_area'] : '', '50'); ?>>50m²</option>
                            <option value="60" <?php selected(!empty($atts['min_area']) ? $atts['min_area'] : '', '60'); ?>>60m²</option>
                            <option value="70" <?php selected(!empty($atts['min_area']) ? $atts['min_area'] : '', '70'); ?>>70m²</option>
                            <option value="80" <?php selected(!empty($atts['min_area']) ? $atts['min_area'] : '', '80'); ?>>80m²</option>
                            <option value="90" <?php selected(!empty($atts['min_area']) ? $atts['min_area'] : '', '90'); ?>>90m²</option>
                            <option value="100" <?php selected(!empty($atts['min_area']) ? $atts['min_area'] : '', '100'); ?>>100m²</option>
                            <option value="120" <?php selected(!empty($atts['min_area']) ? $atts['min_area'] : '', '120'); ?>>120m²</option>
                        </select>
                        <span class="filter-separator">-</span>
                        <select class="filter-select" id="areaMax">
                            <option value="">do</option>
                            <option value="1" <?php selected(!empty($atts['max_area']) ? $atts['max_area'] : '', '1'); ?>>1m²</option>
                            <option value="10" <?php selected(!empty($atts['max_area']) ? $atts['max_area'] : '', '10'); ?>>10m²</option>
                            <option value="20" <?php selected(!empty($atts['max_area']) ? $atts['max_area'] : '', '20'); ?>>20m²</option>
                            <option value="30" <?php selected(!empty($atts['max_area']) ? $atts['max_area'] : '', '30'); ?>>30m²</option>
                            <option value="40" <?php selected(!empty($atts['max_area']) ? $atts['max_area'] : '', '40'); ?>>40m²</option>
                            <option value="50" <?php selected(!empty($atts['max_area']) ? $atts['max_area'] : '', '50'); ?>>50m²</option>
                            <option value="60" <?php selected(!empty($atts['max_area']) ? $atts['max_area'] : '', '60'); ?>>60m²</option>
                            <option value="70" <?php selected(!empty($atts['max_area']) ? $atts['max_area'] : '', '70'); ?>>70m²</option>
                            <option value="80" <?php selected(!empty($atts['max_area']) ? $atts['max_area'] : '', '80'); ?>>80m²</option>
                            <option value="90" <?php selected(!empty($atts['max_area']) ? $atts['max_area'] : '', '90'); ?>>90m²</option>
                            <option value="100" <?php selected(!empty($atts['max_area']) ? $atts['max_area'] : '', '100'); ?>>100m²</option>
                            <option value="120" <?php selected(!empty($atts['max_area']) ? $atts['max_area'] : '', '120'); ?>>120m²</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($hide_price_filter)): ?>
                <div class="filter-group filter-group-price filter-mobile-hidden">
                    <label class="filter-label">Cena za lokal:</label>
                    <div class="filter-range">
                        <?php
                        $price_steps = array(
                            5000, 10000, 50000, 100000, 150000, 200000, 250000,
                            300000, 350000, 400000, 450000, 500000, 600000, 700000,
                            750000, 800000, 900000, 1000000, 1500000
                        );
                        $default_price_min = !empty($atts['min_price_gross']) ? $atts['min_price_gross'] : '';
                        $default_price_max = !empty($atts['max_price_gross']) ? $atts['max_price_gross'] : '';
                        ?>
                        <select class="filter-select" id="priceMin">
                            <option value=""<?php echo $default_price_min === '' ? ' selected' : ''; ?>>od</option>
                            <?php foreach ($price_steps as $step): ?>
                            <option value="<?php echo $step; ?>"<?php echo ($default_price_min == $step) ? ' selected' : ''; ?>><?php echo number_format($step, 0, ',', ' '); ?> zł</option>
                            <?php endforeach; ?>
                        </select>
                        <span class="filter-separator">-</span>
                        <select class="filter-select" id="priceMax">
                            <option value=""<?php echo $default_price_max === '' ? ' selected' : ''; ?>>do</option>
                            <?php foreach ($price_steps as $step): ?>
                            <option value="<?php echo $step; ?>"<?php echo ($default_price_max == $step) ? ' selected' : ''; ?>><?php echo number_format($step, 0, ',', ' '); ?> zł</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="filter-row">
            <?php if (empty($hide_extras) && !empty($shortcode_extras)):
                // extras are defined in shortcode as feature names from Develogic API
                $additional_options = $shortcode_extras;
                ?>
            <div class="filter-group filter-extras">
                <label class="filter-label">Opcje dodatkowe:</label>
                <div class="filter-checkboxes">
                    <?php foreach ($additional_options as $feature_name):
                        $feature_slug = sanitize_title($feature_name); ?>
                    <label class="filter-checkbox">
                        <input type="checkbox" id="<?php echo esc_attr($feature_slug); ?>Filter" value="<?php echo esc_attr($feature_name); ?>" data-feature-name="<?php echo esc_attr($feature_name); ?>">
                        <span><?php echo esc_html($feature_name); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <button type="button" class="filter-show-more-btn mobile-only" id="showMoreFilters" aria-expanded="false">
                <span class="show-more-text">Pokaż więcej filtrów</span>
                <span class="show-less-text" style="display: none;">Ukryj dodatkowe filtry</span>
                <svg class="filter-arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </button>

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
        
    </div>

    <?php if ($atts['show_favorite'] === 'true' || $atts['show_favorite'] === true): ?>
    <div class="favorites-toggle-container"
         <?php if ($show_quote_btn): ?>
         data-quote-email="<?php echo esc_attr($quote_email); ?>"
         data-quote-subject="<?php echo esc_attr($quote_subject); ?>"
         <?php endif; ?>>
        <div class="toggle-buttons-wrapper">
            <button class="favorites-toggle-btn active" data-toggle-view="all">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <line x1="9" y1="3" x2="9" y2="21"/>
                </svg>
                Wszystkie
            </button>
            <button class="favorites-toggle-btn" data-toggle-view="watched">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                Obserwowane
            </button>
            <span class="watched-count" id="watchedCount">0 obserwowanych</span>
            <button class="favorites-toggle-btn" data-toggle-view="favorites">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>
                </svg>
                Konfigurator oferty
            </button>
            <span class="favorites-count" id="favoritesCount">0 wybranych</span>
        </div>
        
        
        <button class="header-promo-btn" id="checkPromotionsBtn" title="Sprawdź aktualne promocje dla tego budynku">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
                <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/>
                <line x1="7" y1="7" x2="7.01" y2="7"/>
            </svg>
            Sprawdź aktualne promocje
        </button>

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
    <?php else: ?>
    <div class="promo-btn-standalone" style="display: flex; justify-content: flex-end; margin-bottom: 12px;">
        <button class="header-promo-btn" id="checkPromotionsBtn" title="Sprawdź aktualne promocje dla tego budynku">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
                <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/>
                <line x1="7" y1="7" x2="7.01" y2="7"/>
            </svg>
            Sprawdź aktualne promocje
        </button>
    </div>
    <?php endif; ?>

    <div class="apartment-list-mobile-header">
        <div class="mh-nr">
            <span class="header-sort" data-sort="data-number">
                Nr
                <span class="sort-arrow">
                    <svg class="arrow-up" viewBox="0 0 12 8" fill="currentColor"><path d="M6 0L0 8h12L6 0z"/></svg>
                    <svg class="arrow-down" viewBox="0 0 12 8" fill="currentColor"><path d="M6 8L0 0h12L6 8z"/></svg>
                </span>
            </span>
        </div>
        <div class="mh-area">
            <span class="header-sort" data-sort="data-area">
                Pow.
                <span class="sort-arrow">
                    <svg class="arrow-up" viewBox="0 0 12 8" fill="currentColor"><path d="M6 0L0 8h12L6 0z"/></svg>
                    <svg class="arrow-down" viewBox="0 0 12 8" fill="currentColor"><path d="M6 8L0 0h12L6 8z"/></svg>
                </span>
            </span>
        </div>
        <div class="mh-price">
            <span class="header-sort" data-sort="data-price">
                Cena
                <span class="sort-arrow">
                    <svg class="arrow-up" viewBox="0 0 12 8" fill="currentColor"><path d="M6 0L0 8h12L6 0z"/></svg>
                    <svg class="arrow-down" viewBox="0 0 12 8" fill="currentColor"><path d="M6 8L0 0h12L6 8z"/></svg>
                </span>
            </span>
        </div>
        <div class="mh-actions"></div>
    </div>

    <div class="apartment-list-header">
        <div class="header-cell header-nr">
            <span class="header-sort" data-sort="data-number">
                Nr lokalu
                <span class="sort-arrow">
                    <svg class="arrow-up" viewBox="0 0 12 8" fill="currentColor"><path d="M6 0L0 8h12L6 0z"/></svg>
                    <svg class="arrow-down" viewBox="0 0 12 8" fill="currentColor"><path d="M6 8L0 0h12L6 8z"/></svg>
                </span>
            </span>
        </div>
        <div class="header-cell header-status">Dostępność</div>
        <div class="header-cell header-floor">
            <span class="header-sort" data-sort="data-floor">
                Kondygnacja
                <span class="sort-arrow">
                    <svg class="arrow-up" viewBox="0 0 12 8" fill="currentColor"><path d="M6 0L0 8h12L6 0z"/></svg>
                    <svg class="arrow-down" viewBox="0 0 12 8" fill="currentColor"><path d="M6 8L0 0h12L6 8z"/></svg>
                </span>
            </span>
        </div>
        <div class="header-cell header-area">
            <span class="header-sort" data-sort="data-area">
                Powierzchnia
                <span class="sort-arrow">
                    <svg class="arrow-up" viewBox="0 0 12 8" fill="currentColor"><path d="M6 0L0 8h12L6 0z"/></svg>
                    <svg class="arrow-down" viewBox="0 0 12 8" fill="currentColor"><path d="M6 8L0 0h12L6 8z"/></svg>
                </span>
            </span>
        </div>
        <div class="header-cell header-rooms">
            <span class="header-sort" data-sort="data-rooms">
                Licz. pokoi
                <span class="sort-arrow">
                    <svg class="arrow-up" viewBox="0 0 12 8" fill="currentColor"><path d="M6 0L0 8h12L6 0z"/></svg>
                    <svg class="arrow-down" viewBox="0 0 12 8" fill="currentColor"><path d="M6 8L0 0h12L6 8z"/></svg>
                </span>
            </span>
        </div>
        <div class="header-cell header-price-m2">
            <span class="header-sort" data-sort="data-price-m2">
                Cena m²
                <span class="sort-arrow">
                    <svg class="arrow-up" viewBox="0 0 12 8" fill="currentColor"><path d="M6 0L0 8h12L6 0z"/></svg>
                    <svg class="arrow-down" viewBox="0 0 12 8" fill="currentColor"><path d="M6 8L0 0h12L6 8z"/></svg>
                </span>
            </span>
        </div>
        <div class="header-cell header-price">
            <span class="header-sort" data-sort="data-price">
                Cena
                <span class="sort-arrow">
                    <svg class="arrow-up" viewBox="0 0 12 8" fill="currentColor"><path d="M6 0L0 8h12L6 0z"/></svg>
                    <svg class="arrow-down" viewBox="0 0 12 8" fill="currentColor"><path d="M6 8L0 0h12L6 8z"/></svg>
                </span>
            </span>
        </div>
        <div class="header-cell header-configurator" style="grid-column: span 3;">
            <button class="header-configurator-btn favorites-toggle-btn" data-toggle-view="favorites">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
                    <line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>
                </svg>
                Konfigurator oferty
                <span class="header-configurator-count" id="headerConfiguratorCount"></span>
            </button>
        </div>
    </div>

    <div class="apartment-list">
        <div class="no-watched-placeholder" style="display: none;">
            <div class="placeholder-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
            </div>
            <h3>Brak obserwowanych ofert</h3>
            <p>Kliknij ikonę gwiazdki przy wybranym mieszkaniu, aby dodać je do obserwowanych</p>
        </div>
        <div class="no-favorites-placeholder" style="display: none;">
            <div class="placeholder-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>
                </svg>
            </div>
            <h3>Brak wybranych ofert</h3>
            <p>Kliknij ikonę konfiguratora przy wybranym mieszkaniu, aby dodać je do konfiguratora oferty</p>
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
                    
                    // Build whitelist from shortcode extras (feature names) or use default
                    if (!empty($shortcode_extras)) {
                        $tag_whitelist = $shortcode_extras;
                    } else {
                        $tag_whitelist = apply_filters('develogic_attribute_whitelist', array());
                    }
                    
                    // Filter attributes for display using whitelist
                    foreach ($local['attributes'] as $attr) {
                        if (isset($attr['name'])) {
                            $attr_name_lower = strtolower(trim($attr['name']));
                            // Check if attribute matches any pattern in whitelist
                            $matches = false;
                            foreach ($tag_whitelist as $whitelist_item) {
                                $whitelist_lower = strtolower(trim($whitelist_item));
                                // Exact match or attribute contains the whitelist pattern
                                if ($attr_name_lower === $whitelist_lower || 
                                    strpos($attr_name_lower, $whitelist_lower) !== false ||
                                    strpos($whitelist_lower, $attr_name_lower) !== false) {
                                    $matches = true;
                                    break;
                                }
                            }
                            if ($matches) {
                                $tags[] = $attr['name'];
                            }
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
                    'localType' => isset($local['localType']) ? $local['localType'] : '',
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
                    // Otherwise, use default from settings
                    $tour_3d_url = develogic()->get_setting('tour_360_url', '');
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

                // Find "Marketingowy" projection for mobile expand image (3D floor plan)
                $marketing_image_url = '';
                $marketing_image_thumb = '';
                foreach ($projections as $proj) {
                    if (isset($proj['type']) && $proj['type'] === 'Marketingowy') {
                        $marketing_image_url = !empty($proj['wordpress_url']) ? $proj['wordpress_url'] :
                                              (!empty($proj['displayUrl']) ? $proj['displayUrl'] : $proj['uri']);
                        $marketing_image_thumb = !empty($proj['thumbnail_url']) ? $proj['thumbnail_url'] :
                                               (!empty($proj['thumbnailUrl']) ? $proj['thumbnailUrl'] : $marketing_image_url);
                        break;
                    }
                }
                
            ?>
            
            <div class="apartment-item"
                 data-number="<?php echo esc_attr($local['number']); ?>"
                 data-rooms="<?php echo esc_attr($rooms_padded); ?>"
                 data-floor="<?php echo esc_attr($floor_padded); ?>"
                 data-price="<?php echo esc_attr($price_padded); ?>"
                 data-price-m2="<?php echo esc_attr($price_m2_padded); ?>"
                 data-area="<?php echo esc_attr($area_padded); ?>"
                 data-building="<?php echo esc_attr($local['building']); ?>"
                 data-local-type="<?php $lt = isset($local['localType']) ? $local['localType'] : ''; if ($lt === 'Pomieszczenie gospodarcze') { $lt = 'Komórka lokatorska'; } echo esc_attr($lt); ?>"
                 data-floor-number="<?php echo esc_attr($floor_value); ?>"
                 data-area-value="<?php echo esc_attr($local['area']); ?>"
                 data-price-value="<?php echo esc_attr($display_price); ?>"
                 data-rooms-value="<?php echo esc_attr($local['rooms']); ?>"
                 data-has-promo="<?php echo $has_package_promo ? 'true' : 'false'; ?>"
                 data-status-class="<?php echo esc_attr($status_class); ?>"
                 data-attributes="<?php echo esc_attr(json_encode($all_attributes)); ?>"
                 data-modal='<?php echo esc_attr(json_encode($modal_data)); ?>'>
                <div class="apartment-info">
                    <div class="apartment-number"><span class="building-name"><?php echo esc_html($local['building']); ?></span> <?php echo esc_html($local['number']); ?></div>
                </div>

                <div class="apartment-status">
                    <span class="status-badge <?php
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
                    </span>
                </div>

                <div class="apartment-floor">
                    <span class="cell-value"><?php echo esc_html($floor_display); ?></span>
                </div>

                <div class="apartment-area">
                    <span class="cell-value"><?php echo number_format($local['area'], 2, ',', ' '); ?> m²</span>
                </div>

                <div class="apartment-rooms">
                    <span class="cell-value"><?php echo absint($local['rooms']); ?></span>
                </div>

                <div class="apartment-price-m2">
                    <span class="price-m2-value"><?php echo number_format($price_m2, 2, ',', ' '); ?> zł/m²</span>
                </div>

                <div class="apartment-price">
                    <?php if ($has_package_promo && $old_price): ?>
                        <div class="price-old"><?php echo number_format($old_price, 0, ',', ' '); ?> zł</div>
                    <?php endif; ?>
                    <div class="price-main"><?php echo number_format($display_price, 0, ',', ' '); ?> zł</div>
                    <?php if ($has_package_promo): ?>
                        <div class="promo-badge">Promocja</div>
                    <?php endif; ?>
                    <?php if ($show_omnibus_price && $has_package_promo && !empty($local['omnibusPriceGross']) && $local['omnibusPriceGross'] > 0): ?>
                        <div class="price-omnibus-label">Najniższa cena z ostatnich 30 dni</div>
                        <div class="price-omnibus-value"><?php echo number_format($local['omnibusPriceGross'], 0, ',', ' '); ?> zł</div>
                    <?php endif; ?>
                </div>

                <div class="apartment-pdf">
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
                </div>

                <div class="apartment-watched">
                    <?php if ($atts['show_favorite'] === 'true' || $atts['show_favorite'] === true): ?>
                    <button class="icon-btn gtm-watched-btn" data-action="watched" data-local-id="<?php echo esc_attr($local['localId']); ?>" aria-label="<?php esc_attr_e('Obserwuj', 'develogic'); ?>" title="Dodaj do obserwowanych">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>

                <div class="apartment-favorite">
                    <?php if ($atts['show_favorite'] === 'true' || $atts['show_favorite'] === true): ?>
                    <button class="icon-btn gtm-favorite-btn" data-action="favorite" data-local-id="<?php echo esc_attr($local['localId']); ?>" aria-label="<?php esc_attr_e('Dodaj do konfiguratora oferty', 'develogic'); ?>" title="Dodaj do konfiguratora oferty">
                        <svg viewBox="0 0 24 24">
                            <line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>

                <div class="apartment-actions mobile-only">
                    <!-- Mobile expand toggle -->
                    <button class="icon-btn mobile-expand-toggle" data-action="expand" aria-label="<?php esc_attr_e('Pokaż szczegóły', 'develogic'); ?>" title="Pokaż szczegóły" onclick="event.stopPropagation();">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                    <?php if ($show_360 && !empty($tour_3d_url)): ?>
                    <a href="<?php echo esc_url($tour_3d_url); ?>" class="icon-btn icon-btn-3d icon-btn-360" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Zobacz spacer 3D 360°', 'develogic'); ?>" title="Zobacz spacer 3D 360° (otwiera w nowej karcie)" onclick="event.stopPropagation();">
                        <span class="text-360">360°</span>
                    </a>
                    <?php endif; ?>
                    <?php
                    $contact_phone = develogic()->get_setting('contact_phone', '');
                    if (!empty($contact_phone)):
                    ?>
                    <a href="tel:<?php echo esc_attr(preg_replace('/[^\d\+]/', '', $contact_phone)); ?>" class="icon-btn icon-btn-phone" aria-label="<?php esc_attr_e('Zadzwoń', 'develogic'); ?>" title="<?php echo esc_attr(sprintf(__('Zadzwoń: %s', 'develogic'), $contact_phone)); ?>" onclick="event.stopPropagation();">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($show_email_btn): ?>
                    <button class="icon-btn gtm-email-btn" data-action="email" aria-label="<?php esc_attr_e('Wyślij email', 'develogic'); ?>" title="Zapytaj o nieruchomość">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <path d="M3 7l9 6 9-6"/>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Mobile expandable section with floor plan and detail button -->
                <div class="apartment-mobile-expand">
                    <div class="mobile-expand-details">
                        <div class="mobile-detail-item">
                            <span class="mobile-detail-label">Powierzchnia</span>
                            <span class="mobile-detail-value"><?php echo number_format($local['area'], 2, ',', ' '); ?> m²</span>
                        </div>
                        <div class="mobile-detail-item">
                            <span class="mobile-detail-label">Piętro</span>
                            <span class="mobile-detail-value"><?php echo esc_html($floor_display); ?></span>
                        </div>
                        <div class="mobile-detail-item">
                            <span class="mobile-detail-label">Pokoje</span>
                            <span class="mobile-detail-value"><?php echo absint($local['rooms']); ?></span>
                        </div>
                        <div class="mobile-detail-item">
                            <span class="mobile-detail-label">Cena mieszkania</span>
                            <span class="mobile-detail-value"><?php echo number_format($display_price, 0, ',', ' '); ?> zł</span>
                        </div>
                        <div class="mobile-detail-item">
                            <span class="mobile-detail-label">Cena za m²</span>
                            <span class="mobile-detail-value"><?php echo number_format($price_m2, 0, ',', ' '); ?> zł/m²</span>
                        </div>
                        <div class="mobile-detail-item">
                            <span class="mobile-detail-label">Status</span>
                            <span class="mobile-detail-value"><?php
                                if ($status_class === 'available') {
                                    echo 'Dostępne';
                                } else {
                                    echo esc_html($display_status);
                                }
                            ?></span>
                        </div>
                    </div>
                    <?php if ($status_class === 'available' && ($atts['show_favorite'] === 'true' || $atts['show_favorite'] === true)): ?>
                    <button class="mobile-watched-btn" data-action="watched" data-local-id="<?php echo esc_attr($local['localId']); ?>" onclick="event.stopPropagation();">
                        <svg viewBox="0 0 24 24" width="18" height="18">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <span class="mobile-watched-btn-text">Dodaj do obserwowanych</span>
                    </button>
                    <button class="mobile-configurator-btn" data-action="favorite" data-local-id="<?php echo esc_attr($local['localId']); ?>" onclick="event.stopPropagation();">
                        <svg viewBox="0 0 24 24" width="18" height="18">
                            <line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>
                        </svg>
                        <span class="mobile-configurator-btn-text">Dodaj do konfiguratora oferty</span>
                    </button>
                    <?php endif; ?>
                    <?php
                    // Use "Marketingowy" projection for mobile expand, fallback to Karta lokalu
                    $mobile_img_url = $marketing_image_url ? $marketing_image_url : $image1_url;
                    $mobile_img_thumb = $marketing_image_thumb ? $marketing_image_thumb : $image1_thumb;
                    $mobile_img_alt = $marketing_image_url ? 'Rzut 3D' : 'Rzut lokalu';
                    ?>
                    <?php if ($mobile_img_thumb): ?>
                    <div class="mobile-expand-image">
                        <img src="<?php echo esc_url($mobile_img_url ? $mobile_img_url : $mobile_img_thumb); ?>" alt="<?php echo esc_attr($local['number']); ?> - <?php echo $mobile_img_alt; ?>" loading="lazy">
                    </div>
                    <?php endif; ?>
                    <button class="mobile-expand-detail-btn" data-action="open-modal" onclick="event.stopPropagation();">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        Zobacz szczegóły
                    </button>
                </div>
            </div>
            
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($atts['show_favorite'] === 'true' || $atts['show_favorite'] === true): ?>
    <!-- Configurator Summary (shown in favorites view) -->
    <div class="configurator-summary" id="configuratorSummary">
        <div id="configuratorSummaryItems"></div>
        <div class="summary-row summary-total">
            <span class="summary-label">Łączna cena</span>
            <span class="summary-value" id="configuratorTotalPrice">0 zł</span>
        </div>
        <?php if ($show_quote_btn): ?>
        <div class="inquiry-form-wrapper" id="inquiryFormWrapper">
            <h4 class="inquiry-form-title" style="color: #0066cc;">Chcesz uzyskać rabat specjalny i ofertę dopasowaną do Ciebie? Odpowiedz na kilka pytań i ciesz się niższą ceną!</h4>
            <form class="inquiry-form" id="inquiryForm" novalidate>
                <div class="inquiry-form-layout">
                    <div class="inquiry-form-fields">
                        <div class="inquiry-form-row">
                            <div class="inquiry-form-field">
                                <input type="text" id="inquiryName" name="name" placeholder="Imię i nazwisko *" required>
                            </div>
                            <div class="inquiry-form-field">
                                <input type="tel" id="inquiryPhone" name="phone" placeholder="Telefon">
                            </div>
                            <div class="inquiry-form-field">
                                <input type="email" id="inquiryEmail" name="email" placeholder="Email *" required>
                            </div>
                        </div>
                        <div class="inquiry-survey">
                            <div class="inquiry-survey-section">
                                <span class="inquiry-survey-label">Jaki metraż wybranego mieszkania Cię interesuje?</span>
                                <div class="inquiry-survey-options">
                                    <label class="inquiry-checkbox"><input type="radio" name="survey_area" value="do 40 m²"> do 40 m²</label>
                                    <label class="inquiry-checkbox"><input type="radio" name="survey_area" value="40–60 m²"> 40–60 m²</label>
                                    <label class="inquiry-checkbox"><input type="radio" name="survey_area" value="powyżej 60 m²"> powyżej 60 m²</label>
                                </div>
                            </div>
                            <div class="inquiry-survey-section">
                                <span class="inquiry-survey-label">Ile pokoi ma mieć Twoje wymarzone mieszkanie?</span>
                                <div class="inquiry-survey-options">
                                    <label class="inquiry-checkbox"><input type="radio" name="survey_rooms" value="1"> 1</label>
                                    <label class="inquiry-checkbox"><input type="radio" name="survey_rooms" value="2"> 2</label>
                                    <label class="inquiry-checkbox"><input type="radio" name="survey_rooms" value="3"> 3</label>
                                    <label class="inquiry-checkbox"><input type="radio" name="survey_rooms" value="4+"> 4+</label>
                                </div>
                            </div>
                            <div class="inquiry-survey-section">
                                <span class="inquiry-survey-label">Czy kiedykolwiek kupiłeś dowolną nieruchomość bezpośrednio od firmy Dom Ełcki?</span>
                                <div class="inquiry-survey-options">
                                    <label class="inquiry-checkbox"><input type="radio" name="survey_purchase_status" value="tak"> tak</label>
                                    <label class="inquiry-checkbox"><input type="radio" name="survey_purchase_status" value="nie"> nie</label>
                                </div>
                            </div>
                            <div class="inquiry-survey-section">
                                <span class="inquiry-survey-label">Do której grupy wiekowej należysz?</span>
                                <div class="inquiry-survey-options">
                                    <label class="inquiry-checkbox"><input type="radio" name="survey_age" value="18–30 lat"> 18–30 lat</label>
                                    <label class="inquiry-checkbox"><input type="radio" name="survey_age" value="30–40 lat"> 30–40 lat</label>
                                    <label class="inquiry-checkbox"><input type="radio" name="survey_age" value="40–60 lat"> 40–60 lat</label>
                                    <label class="inquiry-checkbox"><input type="radio" name="survey_age" value="60+"> 60+</label>
                                </div>
                            </div>
                            <div class="inquiry-survey-section">
                                <span class="inquiry-survey-label inquiry-survey-label--large"><strong>W celu uzyskania oferty specjalnej zaznacz jedno lub kilka z poniższych zdań, które najbardziej pasuje do Ciebie:</strong></span>
                                <div class="inquiry-survey-options inquiry-survey-options--column">
                                    <label class="inquiry-checkbox"><input type="checkbox" name="survey_promo[]" value="służby mundurowe"> Jestem pracownikiem tzw. służb mundurowych</label>
                                    <label class="inquiry-checkbox"><input type="checkbox" name="survey_promo[]" value="ślub 2025/2026"> Wziąłem/wzięłam ślub w 2025 lub 2026 roku</label>
                                    <label class="inquiry-checkbox">
                                        <input type="checkbox" name="survey_promo[]" value="dzieci" id="surveyPromoChildren"> Posiadam dwójkę lub większą liczbę dzieci do 18 roku życia (lub do 26 r.ż. w przypadku dzieci uczących się):
                                        <select name="survey_children_count" class="inquiry-inline-select" disabled>
                                            <option value="">—</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4+">4+</option>
                                        </select>
                                    </label>
                                    <label class="inquiry-checkbox">
                                        <input type="checkbox" name="survey_promo[]" value="promocja specjalna" id="surveyPromoSpecial"> W ramach promocji specjalnej wolałbym otrzymać:
                                        <select name="survey_special_promo" class="inquiry-inline-select" disabled>
                                            <option value="">—</option>
                                            <option value="klimatyzacja gratis">klimatyzację gratis</option>
                                            <option value="szpachlowanie z malowaniem gratis">szpachlowanie z malowaniem gratis</option>
                                        </select>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="inquiry-rodo-consent">
                        <label class="inquiry-rodo-btn" id="inquiryRodoLabel">
                            <input type="checkbox" id="inquiryRodoConsent" required>
                            <span class="inquiry-rodo-btn-text">Wyrażam zgodę na przetwarzanie moich danych osobowych zgodnie z <a href="/polityka-bezpieczenstwa/" target="_blank">Polityką Prywatności</a></span>
                        </label>
                    </div>
                    <div class="inquiry-form-action">
                        <button type="submit" class="summary-inquiry-btn" id="summaryInquiryBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="5" width="18" height="14" rx="2"/>
                                <path d="M3 7l9 6 9-6"/>
                            </svg>
                            Wyślij zapytanie
                        </button>
                    </div>
                </div>
                <div class="inquiry-form-message" id="inquiryFormMessage" style="display: none;"></div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<!-- Popup: better offer with full package -->
<div id="betterOfferPopup" class="better-offer-popup" style="display: none;">
    <div class="better-offer-popup-overlay"></div>
    <div class="better-offer-popup-content">
        <button class="better-offer-popup-close" aria-label="Zamknij">&times;</button>
        <div class="better-offer-popup-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:48px;height:48px;color:#f59e0b;">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
        </div>
        <h3>Możesz uzyskać korzystniejszą ofertę!</h3>
        <p>Jeżeli zdecydujesz się na zakup pełnego pakietu tj.: <strong>Mieszkanie + Komórka lokatorska + Miejsce postojowe</strong> lub <strong>Mieszkanie + Komórka lokatorska + Garaż</strong> &mdash; uzyskasz korzystniejszą ofertę specjalną.</p>
        <div class="better-offer-popup-actions">
            <button class="better-offer-btn better-offer-btn-secondary" id="betterOfferContinue">Wyślij mimo to</button>
            <button class="better-offer-btn better-offer-btn-primary" id="betterOfferAddMore">Dodaj więcej lokali</button>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<?php if ($atts['show_favorite'] === 'true' || $atts['show_favorite'] === true): ?>
<!-- Wizard Modal for Purchase Configurator -->
<div id="wizardModal" class="wizard-modal" style="display: none;">
    <div class="wizard-modal-overlay"></div>
    <div class="wizard-modal-content">
        <button class="wizard-modal-close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>

        <!-- Step 1: Empty configurator - add first apartment -->
        <div class="wizard-step" data-wizard-step="1">
            <div class="wizard-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            <h3>Dodaj pierwsze mieszkanie</h3>
            <p>Twój konfigurator zakupu jest pusty. Zacznij od wybrania wymarzonego mieszkania z naszej oferty.</p>
            <div class="wizard-hint">
                <span class="wizard-hint-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2">
                        <line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>
                    </svg>
                </span>
                <span>Naciśnij ikonę konfiguratora przy wybranym lokalu, aby dodać go do konfiguratora</span>
            </div>
            <button class="wizard-btn wizard-btn-primary" data-wizard-action="find-apartment">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                Znajdź wymarzone mieszkanie
            </button>
            <button class="wizard-btn wizard-btn-cart wizard-go-to-cart" data-wizard-action="go-to-cart" style="display: none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>
                </svg>
                <span class="wizard-cart-text">Przejdź do listy wybranych lokali</span>
                <span class="wizard-cart-count"></span>
            </button>
        </div>

        <!-- Step 2: Congratulations - now pick garage/parking/storage -->
        <div class="wizard-step" data-wizard-step="2" style="display: none;">
            <div class="wizard-icon wizard-icon-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <h3>Gratulacje!</h3>
            <p>Świetny wybór! Teraz uzupełnij swoją ofertę o garaż, komórkę lokatorską lub miejsce postojowe na zewnątrz.</p>
            <div class="wizard-btn-group">
                <button class="wizard-btn wizard-btn-primary" data-wizard-action="find-garage">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="3" width="15" height="13"/>
                        <polygon points="16 8 20 3 20 16 16 16 16 8"/>
                        <circle cx="5.5" cy="18.5" r="2.5"/>
                        <circle cx="13.5" cy="18.5" r="2.5"/>
                    </svg>
                    Wybierz garaż
                </button>
                <button class="wizard-btn wizard-btn-primary" data-wizard-action="find-parking">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <text x="12" y="16" text-anchor="middle" font-size="12" font-weight="bold" fill="currentColor" stroke="none">P</text>
                    </svg>
                    Wybierz miejsce postojowe na zewnątrz
                </button>
                <button class="wizard-btn wizard-btn-primary" data-wizard-action="find-storage">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    </svg>
                    Wybierz komórkę lokatorską lub pom. gospodarcze
                </button>
                <button class="wizard-btn wizard-btn-cart wizard-go-to-cart" data-wizard-action="go-to-cart" style="display: none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>
                    </svg>
                    <span class="wizard-cart-text">Przejdź do listy wybranych lokali</span>
                    <span class="wizard-cart-count"></span>
                </button>
            </div>
        </div>

        <!-- Step 3: All done - send inquiry or go back -->
        <div class="wizard-step" data-wizard-step="3" style="display: none;">
            <div class="wizard-icon wizard-icon-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
            </div>
            <h3>Czy chcesz uzyskać szczegóły oferty?</h3>
            <p>Twoja lista jest gotowa. Wyślij zapytanie, aby uzyskać szczegóły oferty, lub wróć do listy i dodaj więcej lokali.</p>
            <div class="wizard-btn-group">
                <button class="wizard-btn wizard-btn-primary" data-wizard-action="send-inquiry">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="5" width="18" height="14" rx="2"/>
                        <path d="M3 7l9 6 9-6"/>
                    </svg>
                    Wyślij zapytanie
                </button>
                <button class="wizard-btn wizard-btn-secondary" data-wizard-action="back-to-list">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    Wróć do listy i dodaj więcej
                </button>
                <button class="wizard-btn wizard-btn-cart wizard-go-to-cart" data-wizard-action="go-to-cart" style="display: none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>
                    </svg>
                    <span class="wizard-cart-text">Przejdź do listy wybranych lokali</span>
                    <span class="wizard-cart-count"></span>
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

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
                <?php if ($show_email_btn): ?>
                <button class="icon-btn" data-action="email-modal" aria-label="<?php esc_attr_e('Wyślij email', 'develogic'); ?>" title="Zapytaj o nieruchomość">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="5" width="18" height="14" rx="2"/>
                        <path d="M3 7l9 6 9-6"/>
                    </svg>
                </button>
                <?php endif; ?>
                <?php if ($atts['show_favorite'] === 'true' || $atts['show_favorite'] === true): ?>
                <button class="icon-btn" data-action="watched-modal" aria-label="<?php esc_attr_e('Obserwuj', 'develogic'); ?>" title="Dodaj do obserwowanych">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </button>
                <button class="icon-btn" data-action="favorite-modal" aria-label="<?php esc_attr_e('Dodaj do konfiguratora oferty', 'develogic'); ?>" title="Dodaj do konfiguratora oferty">
                    <svg viewBox="0 0 24 24">
                        <line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>
                    </svg>
                </button>
                <?php endif; ?>
                <?php 
                $contact_phone = develogic()->get_setting('contact_phone', '');
                if (!empty($contact_phone)): 
                ?>
                <a href="tel:<?php echo esc_attr(preg_replace('/[^\d\+]/', '', $contact_phone)); ?>" class="icon-btn icon-btn-phone mobile-only" aria-label="<?php esc_attr_e('Zadzwoń', 'develogic'); ?>" title="<?php echo esc_attr(sprintf(__('Zadzwoń: %s', 'develogic'), $contact_phone)); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                </a>
                <?php endif; ?>
                <?php 
                if (!empty($contact_phone)): 
                    // Format phone number: remove all non-digits, then add dashes every 3 digits
                    $phone_digits = preg_replace('/[^\d]/', '', $contact_phone);
                    $formatted_phone = '';
                    for ($i = 0; $i < strlen($phone_digits); $i++) {
                        if ($i > 0 && $i % 3 == 0) {
                            $formatted_phone .= '-';
                        }
                        $formatted_phone .= $phone_digits[$i];
                    }
                ?>
                <div class="contact-phone-desktop desktop-only">
                    <span class="phone-label">Tel:</span>
                    <span class="phone-number"><?php echo esc_html($formatted_phone); ?></span>
                </div>
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

                <?php if ($atts['show_favorite'] === 'true' || $atts['show_favorite'] === true): ?>
                <!-- Show Cart Button -->
                <button class="show-cart-btn" id="modalShowCartBtn" style="display: none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>
                    </svg>
                    <span class="show-cart-btn-text">Pokaż konfigurator</span>
                    <span class="show-cart-btn-count" id="modalCartCount"></span>
                </button>
                <?php endif; ?>

        </div>
    </div>
    </div><!-- .container -->
</div><!-- .develogic-apartments-container -->

