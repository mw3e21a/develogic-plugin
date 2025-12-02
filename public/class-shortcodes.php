<?php
/**
 * Develogic Shortcodes
 *
 * @package Develogic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Develogic_Shortcodes
 */
class Develogic_Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('develogic_offers_a1', array($this, 'render_offers_a1'));
        add_shortcode('develogic_offers', array($this, 'render_offers'));
        add_shortcode('develogic_apartments_list', array($this, 'render_apartments_list'));
        add_shortcode('develogic_apartments_list_new', array($this, 'render_apartments_list_new'));
        add_shortcode('develogic_filters', array($this, 'render_filters'));
        add_shortcode('develogic_local', array($this, 'render_local'));
        add_shortcode('develogic_price_history', array($this, 'render_price_history'));
        add_shortcode('develogic_investments', array($this, 'render_investments'));
        add_shortcode('develogic_local_types', array($this, 'render_local_types'));
    }
    
    /**
     * Render offers A1 shortcode (main layout for JeziornaTowers, OstojaOsiedle)
     */
    public function render_offers_a1($atts) {
        $atts = shortcode_atts(array(
            'investment_id' => '',
            'investment' => '',
            'local_type_id' => '',
            'buildings_panel' => 'true',
            'building_id' => '',
            'ajax' => 'true',
            'show_counters' => 'true',
            'show_print' => develogic()->get_setting('show_print', true),
            'show_favorite' => develogic()->get_setting('show_favorite', true),
            'sort_by' => develogic()->get_setting('default_sort_by', 'priceGrossm2'),
            'sort_dir' => develogic()->get_setting('default_sort_dir', 'asc'),
            'per_page' => '12',
            'gallery' => 'true',
        ), $atts, 'develogic_offers_a1');
        
        // Enqueue assets
        Develogic_Assets::enqueue_a1_assets();
        
        // Get data from CPT
        $filters = array();
        
        // Handle investment filter - by ID or by name
        if (!empty($atts['investment_id'])) {
            $filters['investmentId'] = absint($atts['investment_id']);
        } elseif (!empty($atts['investment'])) {
            // Find investment by name
            $investment_name = trim($atts['investment']);
            $term = get_term_by('name', $investment_name, 'develogic_investment');
            if ($term && !is_wp_error($term)) {
                $filters['investmentId'] = $term->term_id;
            }
        }
        
        if (!empty($atts['local_type_id'])) {
            $filters['localTypeId'] = absint($atts['local_type_id']);
        }
        
        $locals = Develogic_Local_Query::get_locals($filters);
        
        // Get buildings
        $buildings = Develogic_Filter_Sort::get_buildings($locals);
        
        // Apply building filter if specified
        if (!empty($atts['building_id'])) {
            $locals = Develogic_Filter_Sort::filter_locals($locals, array(
                'building_id' => absint($atts['building_id'])
            ));
        }
        
        // Apply visible statuses filter
        $visible_statuses = develogic()->get_setting('visible_statuses', array('Wolny', 'Rezerwacja'));
        $locals = Develogic_Filter_Sort::filter_locals($locals, array(
            'status' => $visible_statuses
        ));
        
        // Sort
        $locals = Develogic_Filter_Sort::sort_locals($locals, $atts['sort_by'], $atts['sort_dir']);
        
        // Count by status
        $status_counts = Develogic_Filter_Sort::count_by_status($locals);
        
        // Generate unique ID for this instance
        $instance_id = 'develogic-a1-' . uniqid();
        
        // Load template
        ob_start();
        $this->load_template('a1-layout', array(
            'instance_id' => $instance_id,
            'atts' => $atts,
            'locals' => $locals,
            'buildings' => $buildings,
            'status_counts' => $status_counts,
        ));
        return ob_get_clean();
    }
    
    /**
     * Render apartments list shortcode (nowy layout zgodny z apartment-list.html)
     */
    public function render_apartments_list($atts) {
        $atts = shortcode_atts(array(
            'investment_id' => '',
            'investment' => '',
            'local_type_id' => '',
            'local_types' => '',
            'garage_name' => '',
            'pg_name' => '',
            'kl_from' => '',
            'kl_to' => '',
            'building_id' => '',
            'building' => '',
            'title' => '',
            'show_counters' => 'true',
            'show_print' => develogic()->get_setting('show_print', true),
            'show_favorite' => develogic()->get_setting('show_favorite', true),
            'sort_by' => develogic()->get_setting('default_sort_by', 'priceGrossm2'),
            'sort_dir' => develogic()->get_setting('default_sort_dir', 'asc'),
            'gallery' => 'true',
            'rooms' => '',
            'floor' => '',
            'min_area' => '',
            'max_area' => '',
            'min_price_gross' => '',
            'max_price_gross' => '',
            'status' => '',
            'hide_floor_filter' => 'false',
        ), $atts, 'develogic_apartments_list');
        
        // Enqueue assets
        wp_enqueue_style('develogic-apartments-list');
        wp_enqueue_script('develogic-apartments-list');
        Develogic_Assets::enqueue_price_history_assets();
        
        // Get data from CPT
        $filters = array();
        
        // Handle investment filter - by ID or by name
        if (!empty($atts['investment_id'])) {
            $filters['investmentId'] = absint($atts['investment_id']);
        } elseif (!empty($atts['investment'])) {
            // Find investment by name
            $investment_name = trim($atts['investment']);
            $term = get_term_by('name', $investment_name, 'develogic_investment');
            if ($term && !is_wp_error($term)) {
                $filters['investmentId'] = $term->term_id;
            }
        }
        
        if (!empty($atts['local_type_id'])) {
            $filters['localTypeId'] = absint($atts['local_type_id']);
        }
        
        $locals = Develogic_Local_Query::get_locals($filters);
        
        // Check if this is a garage listing - if so, don't filter by building
        $is_garage_listing = false;
        if (!empty($atts['local_types'])) {
            $local_types_array = array_map('trim', explode(',', $atts['local_types']));
            if (in_array('Garaż', $local_types_array) || in_array('Garaz', $local_types_array)) {
                $is_garage_listing = true;
            }
        }
        
        // Apply building filter from settings if specified (but not for garage listings)
        if (!$is_garage_listing) {
            $selected_buildings = develogic()->get_setting('sync_buildings', array());
            if (!empty($selected_buildings) && is_array($selected_buildings)) {
                $locals = array_filter($locals, function($local) use ($selected_buildings) {
                    return !empty($local['buildingId']) && in_array(absint($local['buildingId']), array_map('absint', $selected_buildings));
                });
            }
        }
        
        // Get buildings
        $buildings = Develogic_Filter_Sort::get_buildings($locals);
        
        // Only apply hard filters from settings (status and building_id if explicitly set for data limiting)
        $filter_criteria = array();
        
        // Apply local types filter if specified
        if (!empty($atts['local_types'])) {
            $local_types_string = $atts['local_types'];
            $local_types_array = array_map('trim', explode(',', $local_types_string));
            
            // No automatic grouping - use only the types specified in shortcode
            $filter_criteria['local_type'] = $local_types_string;
        }
        
        // Apply visible statuses filter (this is always a hard filter)
        if (!empty($atts['status'])) {
            // Custom status from shortcode
            $filter_criteria['status'] = is_array($atts['status']) ? $atts['status'] : array($atts['status']);
        } else {
            // Default visible statuses from settings
            $visible_statuses = develogic()->get_setting('visible_statuses', array('Wolny', 'Rezerwacja'));
            $filter_criteria['status'] = $visible_statuses;
        }
        
        // Apply hard filters (only if explicitly needed for data limiting)
        // For garage listings, exclude building filters
        if (!empty($filter_criteria)) {
            if ($is_garage_listing) {
                // Remove building filters for garage listings
                unset($filter_criteria['building_id']);
                unset($filter_criteria['building']);
            }
            $locals = Develogic_Filter_Sort::filter_locals($locals, $filter_criteria);
        }
        
        // Apply garage name filter if specified and local_types is "Garaż"
        // Note: This filter applies only to garages, not to "Komórka lokatorska" or "Pomieszczenie gospodarcze"
        if (!empty($atts['garage_name']) && !empty($atts['local_types'])) {
            $local_types_array = array_map('trim', explode(',', $atts['local_types']));
            if (in_array('Garaż', $local_types_array)) {
                $garage_name_filter = trim($atts['garage_name']);
                $locals = array_filter($locals, function($local) use ($garage_name_filter) {
                    $local_type = isset($local['localType']) ? trim($local['localType']) : '';
                    // Skip garage_name filter for "Komórka lokatorska" and "Pomieszczenie gospodarcze" - they have their own filters
                    if ($local_type === 'Komórka lokatorska' || $local_type === 'Pomieszczenie gospodarcze') {
                        return true; // Keep all KL and PG cells, they will be filtered by their own parameters if specified
                    }
                    
                    $number = isset($local['number']) ? trim($local['number']) : '';
                    $name = isset($local['name']) ? trim($local['name']) : '';
                    // Check if garage name appears in number or name field
                    return stripos($number, $garage_name_filter) !== false || 
                           stripos($name, $garage_name_filter) !== false;
                });
            }
        }
        
        // Apply PG (Pomieszczenie gospodarcze) name filter if specified
        if (!empty($atts['pg_name']) && !empty($atts['local_types'])) {
            $local_types_array = array_map('trim', explode(',', $atts['local_types']));
            if (in_array('Garaż', $local_types_array)) {
                $pg_name_filter = trim($atts['pg_name']);
                $locals = array_filter($locals, function($local) use ($pg_name_filter) {
                    $local_type = isset($local['localType']) ? trim($local['localType']) : '';
                    // Only filter "Pomieszczenie gospodarcze" type
                    if ($local_type !== 'Pomieszczenie gospodarcze') {
                        return true; // Keep non-PG locals
                    }
                    
                    $number = isset($local['number']) ? trim($local['number']) : '';
                    $name = isset($local['name']) ? trim($local['name']) : '';
                    // Check if PG name/number appears in number or name field
                    return stripos($number, $pg_name_filter) !== false || 
                           stripos($name, $pg_name_filter) !== false;
                });
            }
        }
        
        // Handle KL (Komórka lokatorska) and PG (Pomieszczenie gospodarcze) filtering
        // If local_types includes "Garaż", handle KL and PG cells based on whether parameters are provided
        if (!empty($atts['local_types'])) {
            $local_types_array = array_map('trim', explode(',', $atts['local_types']));
            if (in_array('Garaż', $local_types_array)) {
                $has_kl_params = !empty($atts['kl_from']) || !empty($atts['kl_to']);
                $has_pg_params = !empty($atts['pg_name']);
                
                if ($has_kl_params) {
                    // Filter KL cells by range if parameters are provided
                    $kl_from = !empty($atts['kl_from']) ? absint($atts['kl_from']) : null;
                    $kl_to = !empty($atts['kl_to']) ? absint($atts['kl_to']) : null;
                    
                    $locals = array_filter($locals, function($local) use ($kl_from, $kl_to) {
                        // Only filter "Komórka lokatorska" type
                        $local_type = isset($local['localType']) ? trim($local['localType']) : '';
                        if ($local_type !== 'Komórka lokatorska') {
                            return true; // Keep non-KL locals
                        }
                        
                        // Extract number from KL format (e.g., "KL123" -> 123)
                        $number = isset($local['number']) ? trim($local['number']) : '';
                        if (empty($number)) {
                            return false; // No number, exclude
                        }
                        
                        // Check if number starts with "KL" (case insensitive)
                        if (stripos($number, 'KL') !== 0) {
                            return true; // Not a KL number, keep it
                        }
                        
                        // Extract numeric part after "KL"
                        $numeric_part = substr($number, 2);
                        if (!is_numeric($numeric_part)) {
                            return true; // Invalid format, keep it
                        }
                        
                        $kl_number = intval($numeric_part);
                        
                        // Check range (inclusive)
                        if ($kl_from !== null && $kl_number < $kl_from) {
                            return false;
                        }
                        if ($kl_to !== null && $kl_number > $kl_to) {
                            return false;
                        }
                        
                        return true;
                    });
                } else {
                    // If no KL parameters are provided, exclude all KL cells
                    $locals = array_filter($locals, function($local) {
                        $local_type = isset($local['localType']) ? trim($local['localType']) : '';
                        return $local_type !== 'Komórka lokatorska';
                    });
                }
                
                // If no PG parameters are provided, exclude all PG cells
                if (!$has_pg_params) {
                    $locals = array_filter($locals, function($local) {
                        $local_type = isset($local['localType']) ? trim($local['localType']) : '';
                        return $local_type !== 'Pomieszczenie gospodarcze';
                    });
                }
            }
        }
        
        // Note: Other parameters (building, rooms, floor, area, price) are only used
        // for setting default UI values in the template, not for filtering data
        
        // Sort
        $locals = Develogic_Filter_Sort::sort_locals($locals, $atts['sort_by'], $atts['sort_dir']);
        
        // Count by status
        $status_counts = Develogic_Filter_Sort::count_by_status($locals);
        
        // Check if floor filter should be hidden - only via shortcode attribute
        $hide_floor_filter = ($atts['hide_floor_filter'] === 'true' || $atts['hide_floor_filter'] === true);
        $hide_building_filter = false;
        $default_local_type = null;
        $is_residential_local = false;
        if (!empty($atts['local_types'])) {
            $local_types_array = array_map('trim', explode(',', $atts['local_types']));
            // If only one type is specified, use it as default
            if (count($local_types_array) === 1) {
                $default_local_type = $local_types_array[0];
            }
            // Check if "Lokal mieszkalny" is in the list
            if (in_array('Lokal mieszkalny', $local_types_array)) {
                $is_residential_local = true;
            }
        }
        
        // Build building -> floors mapping for residential locals
        $building_floors_map = array();
        if ($is_residential_local) {
            foreach ($locals as $local) {
                $local_type = isset($local['localType']) ? trim($local['localType']) : '';
                // Only process residential locals
                if ($local_type === 'Lokal mieszkalny' && !empty($local['building'])) {
                    $building = $local['building'];
                    $floor = isset($local['floor']) ? $local['floor'] : '';
                    
                    if ($floor !== '' && $floor !== null) {
                        if (!isset($building_floors_map[$building])) {
                            $building_floors_map[$building] = array();
                        }
                        // Convert floor to string for consistency
                        $floor_str = (string) $floor;
                        if (!in_array($floor_str, $building_floors_map[$building])) {
                            $building_floors_map[$building][] = $floor_str;
                        }
                    }
                }
            }
            // Sort floors for each building
            foreach ($building_floors_map as $building => $floors) {
                // Sort numerically, handling -1 (piwnica) and 0 (parter)
                usort($building_floors_map[$building], function($a, $b) {
                    $a_int = intval($a);
                    $b_int = intval($b);
                    return $a_int <=> $b_int;
                });
            }
        }
        
        // Generate unique ID for this instance
        $instance_id = 'develogic-apartments-list-' . uniqid();
        
        // Load template
        ob_start();
        $this->load_template('apartments-list', array(
            'instance_id' => $instance_id,
            'atts' => $atts,
            'locals' => $locals,
            'buildings' => $buildings,
            'status_counts' => $status_counts,
            'hide_floor_filter' => $hide_floor_filter,
            'hide_building_filter' => $hide_building_filter,
            'default_local_type' => $default_local_type,
            'building_floors_map' => $building_floors_map,
            'is_residential_local' => $is_residential_local,
        ));
        return ob_get_clean();
    }
    
    /**
     * Render apartments list shortcode - NEW layout (alias to main apartments list)
     */
    public function render_apartments_list_new($atts) {
        // Use the same renderer as main apartments list
        return $this->render_apartments_list($atts);
    }
    
    /**
     * Render offers shortcode (generic)
     */
    public function render_offers($atts) {
        $atts = shortcode_atts(array(
            'investment_id' => '',
            'local_type_id' => '',
            'building_id' => '',
            'status' => '',
            'rooms' => '',
            'floor' => '',
            'min_area' => '',
            'max_area' => '',
            'min_price_gross' => '',
            'max_price_gross' => '',
            'sort_by' => 'priceGrossm2',
            'sort_dir' => 'asc',
            'per_page' => '12',
            'view' => 'grid',
            'ajax' => 'false',
        ), $atts, 'develogic_offers');
        
        wp_enqueue_style('develogic-main');
        wp_enqueue_script('develogic-main');
        
        // Get data via REST API if AJAX, otherwise server-side
        if ($atts['ajax'] === 'true') {
            $instance_id = 'develogic-offers-' . uniqid();
            
            ob_start();
            ?>
            <div id="<?php echo esc_attr($instance_id); ?>" 
                 class="develogic-offers develogic-offers-<?php echo esc_attr($atts['view']); ?>"
                 data-ajax="true"
                 data-atts="<?php echo esc_attr(json_encode($atts)); ?>">
                <div class="develogic-loading"><?php _e('Ładowanie...', 'develogic'); ?></div>
            </div>
            <?php
            return ob_get_clean();
        } else {
            // Server-side rendering
            $filters = array_filter(array(
                'investment_id' => $atts['investment_id'],
                'local_type_id' => $atts['local_type_id'],
                'building_id' => $atts['building_id'],
                'status' => $atts['status'],
                'rooms' => $atts['rooms'],
                'floor' => $atts['floor'],
                'min_area' => $atts['min_area'],
                'max_area' => $atts['max_area'],
                'min_price_gross' => $atts['min_price_gross'],
                'max_price_gross' => $atts['max_price_gross'],
            ));
            
            $cpt_filters = array();
            if (!empty($filters['investment_id'])) {
                $cpt_filters['investmentId'] = $filters['investment_id'];
            }
            if (!empty($filters['local_type_id'])) {
                $cpt_filters['localTypeId'] = $filters['local_type_id'];
            }
            
            $locals = Develogic_Local_Query::get_locals($cpt_filters);
            
            $locals = Develogic_Filter_Sort::filter_locals($locals, $filters);
            $locals = Develogic_Filter_Sort::sort_locals($locals, $atts['sort_by'], $atts['sort_dir']);
            
            ob_start();
            $this->load_template('offers-' . $atts['view'], array(
                'locals' => $locals,
                'atts' => $atts,
            ));
            return ob_get_clean();
        }
    }
    
    /**
     * Render filters shortcode
     */
    public function render_filters($atts) {
        $atts = shortcode_atts(array(
            'target' => '',
            'fields' => 'investment,localType,price,area,rooms,sort',
            'expanded' => 'false',
            'show_reset' => 'true',
            'investment_id' => '',
        ), $atts, 'develogic_filters');
        
        wp_enqueue_style('develogic-main');
        wp_enqueue_script('develogic-main');
        
        // Get reference data from CPT
        $investments = Develogic_Local_Query::get_investments();
        $local_types = Develogic_Local_Query::get_local_types();
        
        ob_start();
        $this->load_template('filters', array(
            'atts' => $atts,
            'investments' => $investments,
            'local_types' => $local_types,
        ));
        return ob_get_clean();
    }
    
    /**
     * Render single local shortcode
     */
    public function render_local($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'template' => 'single',
            'show_price_history' => 'false',
        ), $atts, 'develogic_local');
        
        if (empty($atts['id'])) {
            return $this->render_error(__('ID lokalu jest wymagane', 'develogic'));
        }
        
        wp_enqueue_style('develogic-main');
        wp_enqueue_script('develogic-main');
        
        $local_id = absint($atts['id']);
        
        // Get local from CPT
        $local = Develogic_Local_Query::get_local_by_id($local_id);
        
        if (!$local) {
            return $this->render_error(__('Lokal nie został znaleziony', 'develogic'));
        }
        
        // Get price history if requested
        $price_history = null;
        if ($atts['show_price_history'] === 'true') {
            Develogic_Assets::enqueue_price_history_assets();
            
            // Price history always from API (real-time)
            $price_history = develogic()->api_client->get_price_history($local_id);
        }
        
        ob_start();
        $this->load_template('local-' . $atts['template'], array(
            'local' => $local,
            'price_history' => $price_history,
            'atts' => $atts,
        ));
        return ob_get_clean();
    }
    
    /**
     * Render price history shortcode
     */
    public function render_price_history($atts) {
        $atts = shortcode_atts(array(
            'local_id' => '',
            'template' => 'list',
        ), $atts, 'develogic_price_history');
        
        if (empty($atts['local_id'])) {
            return $this->render_error(__('ID lokalu jest wymagane', 'develogic'));
        }
        
        Develogic_Assets::enqueue_price_history_assets();
        
        $local_id = absint($atts['local_id']);
        
        // Price history always from API (real-time)
        $price_history = develogic()->api_client->get_price_history($local_id);
        
        if (is_wp_error($price_history)) {
            return $this->render_error($price_history->get_error_message());
        }
        
        ob_start();
        $this->load_template('price-history-' . $atts['template'], array(
            'price_history' => $price_history,
            'atts' => $atts,
        ));
        return ob_get_clean();
    }
    
    /**
     * Render investments shortcode
     */
    public function render_investments($atts) {
        $atts = shortcode_atts(array(
            'template' => 'card',
            'link_to_offers' => 'false',
            'per_page' => '12',
        ), $atts, 'develogic_investments');
        
        wp_enqueue_style('develogic-main');
        
        $investments = Develogic_Local_Query::get_investments();
        
        ob_start();
        $this->load_template('investments-' . $atts['template'], array(
            'investments' => $investments,
            'atts' => $atts,
        ));
        return ob_get_clean();
    }
    
    /**
     * Render local types shortcode
     */
    public function render_local_types($atts) {
        $atts = shortcode_atts(array(
            'template' => 'chip',
            'link_to_offers' => 'false',
        ), $atts, 'develogic_local_types');
        
        wp_enqueue_style('develogic-main');
        
        $local_types = Develogic_Local_Query::get_local_types();
        
        ob_start();
        $this->load_template('local-types-' . $atts['template'], array(
            'local_types' => $local_types,
            'atts' => $atts,
        ));
        return ob_get_clean();
    }
    
    /**
     * Load template
     */
    private function load_template($template_name, $args = array()) {
        extract($args);
        
        // Check theme override
        $theme_template = get_stylesheet_directory() . '/develogic/' . $template_name . '.php';
        
        if (file_exists($theme_template)) {
            include $theme_template;
            return;
        }
        
        // Load plugin template
        $plugin_template = DEVELOGIC_PLUGIN_DIR . 'templates/' . $template_name . '.php';
        
        if (file_exists($plugin_template)) {
            include $plugin_template;
            return;
        }
        
        // Fallback error
        echo $this->render_error(sprintf(__('Szablon "%s" nie został znaleziony', 'develogic'), $template_name));
    }
    
    /**
     * Render error message
     */
    private function render_error($message) {
        return sprintf(
            '<div class="develogic-error"><p>%s</p></div>',
            esc_html($message)
        );
    }
}

