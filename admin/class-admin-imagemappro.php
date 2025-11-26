<?php
/**
 * Develogic Image Map Pro Admin Settings
 *
 * @package Develogic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Develogic_Admin_ImageMapPro
 */
class Develogic_Admin_ImageMapPro {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'), 20);
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue scripts and styles
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_scripts($hook) {
        // Only load on our page
        if ($hook !== 'develogic_page_develogic-imagemappro') {
            return;
        }
        
        // Color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }
    
    /**
     * Add admin menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'develogic',
            __('Image Map Pro', 'develogic'),
            __('Image Map Pro', 'develogic'),
            'manage_options',
            'develogic-imagemappro',
            array($this, 'render_page')
        );
    }
    
    /**
     * Handle form actions
     */
    public function handle_actions() {
        if (!isset($_POST['develogic_imagemappro_action'])) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['develogic_imagemappro_nonce']) || 
            !wp_verify_nonce($_POST['develogic_imagemappro_nonce'], 'develogic_imagemappro_settings')) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $action = sanitize_text_field($_POST['develogic_imagemappro_action']);
        
        switch ($action) {
            case 'save_colors':
                $this->save_colors();
                break;
                
            case 'save_mappings':
                $this->save_mappings();
                break;
                
            case 'clear_mappings':
                $this->clear_mappings();
                break;
                
            case 'trigger_update':
                $this->trigger_manual_update();
                break;
        }
    }
    
    /**
     * Save color settings
     */
    private function save_colors() {
        if (!isset($_POST['status_colors']) || !is_array($_POST['status_colors'])) {
            return;
        }
        
        $colors = array();
        
        foreach ($_POST['status_colors'] as $status => $color) {
            $status_clean = sanitize_text_field($status);
            $color_clean = sanitize_hex_color_no_hash($color);
            
            if (!empty($status_clean) && !empty($color_clean)) {
                $colors[$status_clean] = $color_clean;
            }
        }
        
        update_option('develogic_imagemappro_colors', $colors);
        
        add_settings_error(
            'develogic_imagemappro',
            'colors_saved',
            __('Kolory statusów zostały zapisane.', 'develogic'),
            'success'
        );
    }
    
    /**
     * Save building mappings
     */
    private function save_mappings() {
        if (!isset($_POST['building_map']) || !is_array($_POST['building_map'])) {
            return;
        }
        
        $mappings = array();
        
        foreach ($_POST['building_map'] as $building => $shortcodes) {
            $building_clean = sanitize_text_field($building);
            
            // Handle multiple shortcodes (array)
            if (is_array($shortcodes)) {
                $shortcodes_clean = array();
                foreach ($shortcodes as $shortcode) {
                    $shortcode_clean = sanitize_text_field($shortcode);
                    if (!empty($shortcode_clean)) {
                        $shortcodes_clean[] = $shortcode_clean;
                    }
                }
                
                if (!empty($building_clean) && !empty($shortcodes_clean)) {
                    $mappings[$building_clean] = $shortcodes_clean;
                }
            } else {
                // Backwards compatibility - single shortcode
                $shortcode_clean = sanitize_text_field($shortcodes);
                if (!empty($building_clean) && !empty($shortcode_clean)) {
                    $mappings[$building_clean] = array($shortcode_clean);
                }
            }
        }
        
        update_option('develogic_imagemappro_building_map', $mappings);
        
        add_settings_error(
            'develogic_imagemappro',
            'mappings_saved',
            __('Mapowania budynków zostały zapisane.', 'develogic'),
            'success'
        );
    }
    
    /**
     * Clear all mappings
     */
    private function clear_mappings() {
        delete_option('develogic_imagemappro_building_map');
        
        add_settings_error(
            'develogic_imagemappro',
            'mappings_cleared',
            __('Wszystkie mapowania zostały usunięte.', 'develogic'),
            'success'
        );
    }
    
    /**
     * Trigger manual color update
     */
    private function trigger_manual_update() {
        // Log start
        error_log('[Develogic] Manual update triggered via admin button');
        
        // Get integration instance
        $integration = new Develogic_ImageMapPro_Integration();
        
        // Create fake stats array
        $stats = array(
            'success' => true,
            'message' => 'Manualna aktualizacja',
        );
        
        // Trigger update
        $integration->update_image_map_pro_colors($stats);
        
        // Log end
        error_log('[Develogic] Manual update completed');
        
        add_settings_error(
            'develogic_imagemappro',
            'manual_update',
            __('Aktualizacja kolorów została uruchomiona. Sprawdź logi synchronizacji oraz wp-content/debug.log', 'develogic'),
            'success'
        );
    }
    
    /**
     * Render admin page
     */
    public function render_page() {
        // Check if Image Map Pro is active
        $imagemappro_active = class_exists('ImageMapPro_v6') || class_exists('ImageMapPro');
        
        // Get current settings
        $colors = get_option('develogic_imagemappro_colors', array());
        $mappings = get_option('develogic_imagemappro_building_map', array());
        
        // Get available buildings
        $buildings = $this->get_buildings();
        
        // Get Image Map Pro projects
        $projects = $imagemappro_active ? $this->get_imagemappro_projects() : array();
        
        // Default statuses
        $default_statuses = array(
            'Wolny' => '7ED322',
            'Sprzedany' => 'ee1c24',
            'Rezerwacja' => 'FFA500',
            'Miękka rezerwacja' => 'FFA500',
            'Niedostępny' => 'cccccc',
        );
        
        // Merge with saved colors
        $all_colors = array_merge($default_statuses, $colors);
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors('develogic_imagemappro'); ?>
            
            <?php if (!$imagemappro_active): ?>
                <div class="notice notice-warning">
                    <p><?php _e('Wtyczka Image Map Pro nie jest aktywna. Ta integracja wymaga zainstalowanej i aktywnej wtyczki Image Map Pro.', 'develogic'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('Jak to działa:', 'develogic'); ?></strong><br>
                    <?php _e('Po każdej synchronizacji lokali z Develogic, kolory kształtów (polygonów) w Image Map Pro będą automatycznie aktualizowane na podstawie statusu lokalu.', 'develogic'); ?>
                </p>
                <p>
                    <?php _e('1. Ustaw kolory dla każdego statusu<br>2. Zmapuj budynki na shortcode\'y Image Map Pro<br>3. Kształty w Image Map Pro muszą mieć w polu "title" numer lokalu odpowiadający numerowi w Develogic', 'develogic'); ?>
                </p>
            </div>
            
            <!-- Color Settings -->
            <div class="card" style="max-width: 800px; margin-bottom: 20px;">
                <h2><?php _e('Kolory statusów', 'develogic'); ?></h2>
                
                <form method="post" action="">
                    <?php wp_nonce_field('develogic_imagemappro_settings', 'develogic_imagemappro_nonce'); ?>
                    <input type="hidden" name="develogic_imagemappro_action" value="save_colors">
                    
                    <table class="form-table">
                        <tbody>
                            <?php foreach ($all_colors as $status => $color): ?>
                                <tr>
                                    <th scope="row">
                                        <label for="color_<?php echo esc_attr($status); ?>">
                                            <?php echo esc_html($status); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <input 
                                            type="text" 
                                            id="color_<?php echo esc_attr($status); ?>" 
                                            name="status_colors[<?php echo esc_attr($status); ?>]" 
                                            value="#<?php echo esc_attr($color); ?>" 
                                            class="develogic-color-picker"
                                            data-default-color="#<?php echo esc_attr($color); ?>"
                                        >
                                        <span class="description">
                                            <?php _e('Kolor hex dla statusu', 'develogic'); ?> "<?php echo esc_html($status); ?>"
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php _e('Zapisz kolory', 'develogic'); ?>
                        </button>
                    </p>
                </form>
            </div>
            
            <!-- Building Mappings -->
            <div class="card" style="max-width: 800px; margin-bottom: 20px;">
                <h2><?php _e('Mapowanie budynków na projekty Image Map Pro', 'develogic'); ?></h2>
                
                <p class="description">
                    <?php _e('Przypisz każdy budynek do odpowiedniego shortcode\'u Image Map Pro. Dzięki temu system wie, które kształty aktualizować dla każdego budynku.', 'develogic'); ?>
                </p>
                
                <?php if (empty($buildings)): ?>
                    <p><em><?php _e('Brak budynków w bazie. Wykonaj najpierw synchronizację lokali.', 'develogic'); ?></em></p>
                <?php elseif (empty($projects)): ?>
                    <p><em><?php _e('Brak projektów Image Map Pro. Utwórz najpierw projekty w Image Map Pro.', 'develogic'); ?></em></p>
                <?php else: ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('develogic_imagemappro_settings', 'develogic_imagemappro_nonce'); ?>
                        <input type="hidden" name="develogic_imagemappro_action" value="save_mappings">
                        
                        <div style="overflow-x: auto;">
                            <table class="widefat striped" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th style="width: 30%;"><?php _e('Budynek (Develogic)', 'develogic'); ?></th>
                                        <th style="width: 70%;"><?php _e('Shortcode Image Map Pro', 'develogic'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($buildings as $building): 
                                        // Get current mappings for this building (now an array)
                                        $selected_shortcodes = isset($mappings[$building['name']]) ? $mappings[$building['name']] : array();
                                        // Ensure it's an array (backwards compatibility)
                                        if (!is_array($selected_shortcodes)) {
                                            $selected_shortcodes = array($selected_shortcodes);
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($building['name']); ?></strong>
                                                <br>
                                                <code>ID: <?php echo esc_html($building['id']); ?></code>
                                            </td>
                                            <td>
                                                <!-- Multi-select dla wielu projektów -->
                                                <select 
                                                    name="building_map[<?php echo esc_attr($building['name']); ?>][]" 
                                                    multiple 
                                                    size="5"
                                                    style="width: 100%; max-width: 400px;"
                                                    class="develogic-multi-select"
                                                >
                                                    <?php foreach ($projects as $project): 
                                                        $version_label = isset($project->version) && $project->version === 'old' ? ' [v4/v5]' : '';
                                                    ?>
                                                        <option 
                                                            value="<?php echo esc_attr($project->shortcode); ?>"
                                                            <?php selected(in_array($project->shortcode, $selected_shortcodes)); ?>
                                                        >
                                                            <?php echo esc_html($project->name); ?> (<?php echo esc_html($project->shortcode); ?>)<?php echo esc_html($version_label); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <p class="description">
                                                    <?php _e('Przytrzymaj Ctrl (Cmd na Mac) aby wybrać wiele projektów', 'develogic'); ?>
                                                </p>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">
                                <?php _e('Zapisz mapowania', 'develogic'); ?>
                            </button>
                            
                            <button 
                                type="submit" 
                                name="develogic_imagemappro_action" 
                                value="clear_mappings" 
                                class="button button-secondary"
                                onclick="return confirm('<?php esc_attr_e('Czy na pewno chcesz usunąć wszystkie mapowania?', 'develogic'); ?>');"
                            >
                                <?php _e('Wyczyść wszystkie', 'develogic'); ?>
                            </button>
                        </p>
                    </form>
                <?php endif; ?>
            </div>
            
            <!-- Manual Update -->
            <div class="card" style="max-width: 800px;">
                <h2><?php _e('Manualna aktualizacja', 'develogic'); ?></h2>
                
                <p class="description">
                    <?php _e('Możesz manualnie uruchomić aktualizację kolorów w Image Map Pro bez czekania na synchronizację.', 'develogic'); ?>
                </p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('develogic_imagemappro_settings', 'develogic_imagemappro_nonce'); ?>
                    <input type="hidden" name="develogic_imagemappro_action" value="trigger_update">
                    
                    <p class="submit">
                        <button type="submit" class="button button-secondary">
                            <?php _e('Aktualizuj kolory teraz', 'develogic'); ?>
                        </button>
                    </p>
                </form>
            </div>
            
            <!-- Info -->
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h3><?php _e('Informacje techniczne', 'develogic'); ?></h3>
                
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><?php _e('Status Image Map Pro:', 'develogic'); ?> 
                        <strong><?php echo $imagemappro_active ? 
                            '<span style="color: green;">✓ Aktywna</span>' : 
                            '<span style="color: red;">✗ Nieaktywna</span>'; ?></strong>
                    </li>
                    <li><?php _e('Liczba projektów Image Map Pro:', 'develogic'); ?> 
                        <strong><?php echo count($projects); ?></strong>
                        <?php 
                        if (!empty($projects)) {
                            $old_count = count(array_filter($projects, function($p) { return isset($p->version) && $p->version === 'old'; }));
                            $new_count = count($projects) - $old_count;
                            if ($old_count > 0 && $new_count > 0) {
                                echo ' <span style="color: #666;">(' . $new_count . ' v6+, ' . $old_count . ' v4/v5)</span>';
                            } elseif ($old_count > 0) {
                                echo ' <span style="color: #666;">(Image Map Pro v4/v5)</span>';
                            } elseif ($new_count > 0) {
                                echo ' <span style="color: #666;">(Image Map Pro v6+)</span>';
                            }
                        }
                        ?>
                    </li>
                    <li><?php _e('Liczba budynków w Develogic:', 'develogic'); ?> 
                        <strong><?php echo count($buildings); ?></strong>
                    </li>
                    <li><?php _e('Liczba skonfigurowanych mapowań:', 'develogic'); ?> 
                        <strong><?php echo count($mappings); ?></strong>
                    </li>
                    <?php if (!empty($mappings)): 
                        $total_shortcodes = 0;
                        foreach ($mappings as $building => $shortcodes) {
                            $total_shortcodes += is_array($shortcodes) ? count($shortcodes) : 1;
                        }
                    ?>
                    <li><?php _e('Łączna liczba przypisań budynek→projekt:', 'develogic'); ?> 
                        <strong><?php echo $total_shortcodes; ?></strong>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <p class="description" style="margin-top: 15px;">
                    <strong><?php _e('Multi-select:', 'develogic'); ?></strong><br>
                    <?php _e('Teraz możesz przypisać jeden budynek do wielu projektów Image Map Pro. Przytrzymaj Ctrl (lub Cmd na Mac) i klikaj na opcje w liście aby wybrać wiele projektów.', 'develogic'); ?>
                </p>
            </div>
        </div>
        
        <style>
            .develogic-color-picker {
                max-width: 100px;
            }
            .develogic-multi-select {
                font-size: 14px;
                padding: 5px;
            }
            .develogic-multi-select option {
                padding: 5px 10px;
                border-bottom: 1px solid #eee;
            }
            .develogic-multi-select option:hover {
                background: #f0f0f0;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize color pickers
            if (typeof $.fn.wpColorPicker !== 'undefined') {
                $('.develogic-color-picker').wpColorPicker();
            }
        });
        </script>
        <?php
    }
    
    /**
     * Get all buildings from Develogic locals
     *
     * @return array Array of buildings with id and name
     */
    private function get_buildings() {
        global $wpdb;
        
        $query = "
            SELECT DISTINCT pm.meta_value as building_id, pm2.meta_value as building_name
            FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->postmeta} pm2 ON pm.post_id = pm2.post_id AND pm2.meta_key = 'building'
            WHERE pm.meta_key = 'buildingId'
            AND pm.meta_value != ''
            ORDER BY building_name ASC
        ";
        
        $results = $wpdb->get_results($query);
        
        $buildings = array();
        
        foreach ($results as $row) {
            if (!empty($row->building_id)) {
                $buildings[] = array(
                    'id' => $row->building_id,
                    'name' => !empty($row->building_name) ? $row->building_name : 'Budynek ' . $row->building_id,
                );
            }
        }
        
        return $buildings;
    }
    
    /**
     * Get all Image Map Pro projects
     *
     * @return array Array of project objects
     */
    private function get_imagemappro_projects() {
        global $wpdb;
        
        $projects = array();
        
        // Try new version (table-based)
        $table_name = $wpdb->prefix . 'image_map_pro_projects';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $table_projects = $wpdb->get_results("SELECT id, name, shortcode FROM $table_name ORDER BY name ASC");
            
            if ($table_projects) {
                foreach ($table_projects as $project) {
                    $project->version = 'new';
                }
                $projects = array_merge($projects, $table_projects);
            }
        }
        
        // Try old version (wp_options based)
        $old_options = get_option('image-map-pro-wordpress-admin-options', false);
        
        if ($old_options && isset($old_options['saves']) && is_array($old_options['saves'])) {
            foreach ($old_options['saves'] as $project_id => $project_data) {
                if (isset($project_data['meta'])) {
                    $project = new stdClass();
                    $project->id = $project_id;
                    $project->name = isset($project_data['meta']['name']) ? $project_data['meta']['name'] : "Project $project_id";
                    $project->shortcode = isset($project_data['meta']['shortcode']) ? $project_data['meta']['shortcode'] : "project_$project_id";
                    $project->version = 'old';
                    
                    $projects[] = $project;
                }
            }
        }
        
        return $projects;
    }
}

