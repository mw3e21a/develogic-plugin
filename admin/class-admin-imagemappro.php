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
                
            case 'save_counters':
                $this->save_counters();
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

        foreach ($_POST['status_colors'] as $status => $data) {
            $status_clean = sanitize_text_field($status);
            if (empty($status_clean) || !is_array($data)) {
                continue;
            }

            $color = isset($data['color']) ? sanitize_hex_color_no_hash($data['color']) : '';
            $hover_color = isset($data['hover_color']) ? sanitize_hex_color_no_hash($data['hover_color']) : '';

            if (empty($color)) {
                continue;
            }
            if (empty($hover_color)) {
                $hover_color = $color;
            }

            $colors[$status_clean] = array(
                'color' => $color,
                'opacity' => isset($data['opacity']) ? max(0, min(1, floatval($data['opacity']))) : 0.65,
                'hover_color' => $hover_color,
                'hover_opacity' => isset($data['hover_opacity']) ? max(0, min(1, floatval($data['hover_opacity']))) : 0.40,
            );
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

        foreach ($_POST['building_map'] as $building => $entries) {
            $building_clean = sanitize_text_field($building);
            if (empty($building_clean)) {
                continue;
            }

            $building_entries = array();

            if (is_array($entries)) {
                foreach ($entries as $entry) {
                    if (is_array($entry) && !empty($entry['shortcode'])) {
                        // New format: {shortcode, layers[]}
                        $shortcode = sanitize_text_field($entry['shortcode']);
                        if (empty($shortcode)) {
                            continue;
                        }
                        $layers = array();
                        if (!empty($entry['layers']) && is_array($entry['layers'])) {
                            $layers = array_map('intval', $entry['layers']);
                            $layers = array_filter($layers, function($l) { return $l >= 0; });
                        }
                        if (!empty($layers)) {
                            $building_entries[] = $shortcode . ':' . implode(',', $layers);
                        } else {
                            $building_entries[] = $shortcode;
                        }
                    } elseif (is_string($entry)) {
                        // Old format: plain shortcode string
                        $shortcode = sanitize_text_field($entry);
                        if (!empty($shortcode)) {
                            $building_entries[] = $shortcode;
                        }
                    }
                }
            }

            if (!empty($building_entries)) {
                $mappings[$building_clean] = $building_entries;
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
     * Save availability counter settings
     */
    private function save_counters() {
        if (!isset($_POST['counters']) || !is_array($_POST['counters'])) {
            delete_option('develogic_imagemappro_counters');
            add_settings_error(
                'develogic_imagemappro',
                'counters_saved',
                __('Liczniki dostępności zostały zapisane.', 'develogic'),
                'success'
            );
            return;
        }

        $counters = array();

        foreach ($_POST['counters'] as $entry) {
            if (!is_array($entry) || empty($entry['shortcode']) || empty($entry['spot_id'])) {
                continue;
            }

            $counter = array(
                'shortcode'   => sanitize_text_field($entry['shortcode']),
                'spot_id'     => sanitize_text_field($entry['spot_id']),
                'mode'        => in_array($entry['mode'], array('floor', 'building'), true) ? $entry['mode'] : 'floor',
                'building_id' => !empty($entry['building_id']) ? sanitize_text_field($entry['building_id']) : '',
                'floor'       => isset($entry['floor']) ? sanitize_text_field($entry['floor']) : '',
                'template'    => !empty($entry['template']) ? sanitize_text_field($entry['template']) : 'Ilość dostępnych mieszkań - {count}',
            );

            $counters[] = $counter;
        }

        update_option('develogic_imagemappro_counters', $counters);

        add_settings_error(
            'develogic_imagemappro',
            'counters_saved',
            __('Liczniki dostępności zostały zapisane.', 'develogic'),
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
        $counters = get_option('develogic_imagemappro_counters', array());
        
        // Get available buildings
        $buildings = $this->get_buildings();
        
        // Get Image Map Pro projects
        $projects = $imagemappro_active ? $this->get_imagemappro_projects() : array();

        // Get layers for projects that have them
        $project_layers = $imagemappro_active ? $this->get_project_layers() : array();
        
        // Default statuses (new format)
        $default_statuses = array(
            'Wolny'                    => array('color' => '7ED322', 'opacity' => 0.65, 'hover_color' => '7ED322', 'hover_opacity' => 0.40),
            'Sprzedany'                => array('color' => 'ee1c24', 'opacity' => 0.65, 'hover_color' => 'ee1c24', 'hover_opacity' => 0.40),
            'Rezerwacja'               => array('color' => 'FFA500', 'opacity' => 0.65, 'hover_color' => 'FFA500', 'hover_opacity' => 0.40),
            'Miękka rezerwacja'        => array('color' => 'FFA500', 'opacity' => 0.65, 'hover_color' => 'FFA500', 'hover_opacity' => 0.40),
            'Przeniesiona własność'    => array('color' => 'ee1c24', 'opacity' => 0.65, 'hover_color' => 'ee1c24', 'hover_opacity' => 0.40),
            'Niedostępny'              => array('color' => 'cccccc', 'opacity' => 0.65, 'hover_color' => 'cccccc', 'hover_opacity' => 0.40),
            'Wyłączony ze sprzedaży'   => array('color' => 'cccccc', 'opacity' => 0.65, 'hover_color' => 'cccccc', 'hover_opacity' => 0.40),
        );

        // Merge with saved colors (handle old string format)
        $all_colors = $default_statuses;
        if (!empty($colors) && is_array($colors)) {
            foreach ($colors as $status => $value) {
                if (is_array($value)) {
                    $all_colors[$status] = $value;
                } else {
                    $hex = ltrim($value, '#');
                    $all_colors[$status] = array('color' => $hex, 'opacity' => 0.65, 'hover_color' => $hex, 'hover_opacity' => 0.40);
                }
            }
        }
        
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
                    
                    <table class="widefat striped" style="max-width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 18%;"><?php _e('Status', 'develogic'); ?></th>
                                <th style="width: 20%;"><?php _e('Kolor', 'develogic'); ?></th>
                                <th style="width: 12%;"><?php _e('Opacity', 'develogic'); ?></th>
                                <th style="width: 20%;"><?php _e('Kolor (hover)', 'develogic'); ?></th>
                                <th style="width: 12%;"><?php _e('Opacity (hover)', 'develogic'); ?></th>
                                <th style="width: 18%;"><?php _e('Podgląd', 'develogic'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_colors as $status => $cfg): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($status); ?></strong></td>
                                    <td>
                                        <input
                                            type="text"
                                            name="status_colors[<?php echo esc_attr($status); ?>][color]"
                                            value="#<?php echo esc_attr($cfg['color']); ?>"
                                            class="develogic-color-picker"
                                            data-default-color="#<?php echo esc_attr($cfg['color']); ?>"
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            name="status_colors[<?php echo esc_attr($status); ?>][opacity]"
                                            value="<?php echo esc_attr($cfg['opacity']); ?>"
                                            min="0" max="1" step="0.05"
                                            style="width: 70px;"
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            name="status_colors[<?php echo esc_attr($status); ?>][hover_color]"
                                            value="#<?php echo esc_attr($cfg['hover_color']); ?>"
                                            class="develogic-color-picker"
                                            data-default-color="#<?php echo esc_attr($cfg['hover_color']); ?>"
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            name="status_colors[<?php echo esc_attr($status); ?>][hover_opacity]"
                                            value="<?php echo esc_attr($cfg['hover_opacity']); ?>"
                                            min="0" max="1" step="0.05"
                                            style="width: 70px;"
                                        >
                                    </td>
                                    <td>
                                        <span style="display:inline-block;width:30px;height:30px;border-radius:4px;border:1px solid #ccc;vertical-align:middle;background:rgba(<?php
                                            $r = hexdec(substr($cfg['color'], 0, 2));
                                            $g = hexdec(substr($cfg['color'], 2, 2));
                                            $b = hexdec(substr($cfg['color'], 4, 2));
                                            echo "$r,$g,$b," . esc_attr($cfg['opacity']);
                                        ?>);" title="default"></span>
                                        <span style="margin: 0 4px;">&rarr;</span>
                                        <span style="display:inline-block;width:30px;height:30px;border-radius:4px;border:1px solid #ccc;vertical-align:middle;background:rgba(<?php
                                            $rh = hexdec(substr($cfg['hover_color'], 0, 2));
                                            $gh = hexdec(substr($cfg['hover_color'], 2, 2));
                                            $bh = hexdec(substr($cfg['hover_color'], 4, 2));
                                            echo "$rh,$gh,$bh," . esc_attr($cfg['hover_opacity']);
                                        ?>);" title="hover"></span>
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
            <div class="card" style="max-width: 900px; margin-bottom: 20px;">
                <h2><?php _e('Mapowanie budynków na projekty Image Map Pro', 'develogic'); ?></h2>

                <p class="description">
                    <?php _e('Przypisz każdy budynek do projektu Image Map Pro. Jeśli projekt ma warstwy (layers), możesz wybrać konkretne warstwy — dzięki temu system wie, które kształty należą do tego budynku.', 'develogic'); ?>
                </p>

                <?php if (empty($buildings)): ?>
                    <p><em><?php _e('Brak budynków w bazie. Wykonaj najpierw synchronizację lokali.', 'develogic'); ?></em></p>
                <?php elseif (empty($projects)): ?>
                    <p><em><?php _e('Brak projektów Image Map Pro. Utwórz najpierw projekty w Image Map Pro.', 'develogic'); ?></em></p>
                <?php else: ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('develogic_imagemappro_settings', 'develogic_imagemappro_nonce'); ?>
                        <input type="hidden" name="develogic_imagemappro_action" value="save_mappings">

                        <?php
                        // Encode layers data for JavaScript
                        $layers_json = wp_json_encode($project_layers);
                        ?>
                        <script>var develogicProjectLayers = <?php echo $layers_json; ?>;</script>

                        <div style="overflow-x: auto;">
                            <table class="widefat striped" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th style="width: 25%;"><?php _e('Budynek (Develogic)', 'develogic'); ?></th>
                                        <th style="width: 75%;"><?php _e('Projekty i warstwy Image Map Pro', 'develogic'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($buildings as $building):
                                        $building_key = $building['id'];
                                        // Get current mapping entries for this building
                                        $building_entries = isset($mappings[$building_key]) ? $mappings[$building_key] : array();
                                        if (!is_array($building_entries)) {
                                            $building_entries = array($building_entries);
                                        }

                                        // Parse entries into shortcode => layers format
                                        $parsed_entries = array();
                                        foreach ($building_entries as $entry) {
                                            if (strpos($entry, ':') !== false) {
                                                list($sc, $layers_str) = explode(':', $entry, 2);
                                                $parsed_entries[$sc] = array_map('intval', explode(',', $layers_str));
                                            } else {
                                                $parsed_entries[$entry] = array();
                                            }
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($building['name']); ?></strong>
                                                <br>
                                                <code>ID: <?php echo esc_html($building['id']); ?></code>
                                            </td>
                                            <td>
                                                <div class="develogic-mapping-entries" data-building="<?php echo esc_attr($building_key); ?>">
                                                    <?php
                                                    // Show existing entries or one empty row
                                                    $entries_to_show = !empty($parsed_entries) ? $parsed_entries : array('' => array());
                                                    $entry_index = 0;
                                                    foreach ($entries_to_show as $selected_sc => $selected_layers):
                                                    ?>
                                                    <div class="develogic-mapping-row" style="margin-bottom: 10px; padding: 8px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 3px;">
                                                        <div style="display: flex; gap: 10px; align-items: flex-start; flex-wrap: wrap;">
                                                            <div style="flex: 1; min-width: 200px;">
                                                                <label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 3px;"><?php _e('Projekt:', 'develogic'); ?></label>
                                                                <select
                                                                    name="building_map[<?php echo esc_attr($building_key); ?>][<?php echo $entry_index; ?>][shortcode]"
                                                                    class="develogic-project-select"
                                                                    style="width: 100%;"
                                                                >
                                                                    <option value=""><?php _e('— wybierz projekt —', 'develogic'); ?></option>
                                                                    <?php foreach ($projects as $project): ?>
                                                                        <option
                                                                            value="<?php echo esc_attr($project->shortcode); ?>"
                                                                            <?php selected($project->shortcode, $selected_sc); ?>
                                                                            data-has-layers="<?php echo isset($project_layers[$project->shortcode]) ? '1' : '0'; ?>"
                                                                        >
                                                                            <?php echo esc_html($project->name); ?> (<?php echo esc_html($project->shortcode); ?>)
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="develogic-layers-container" style="flex: 1; min-width: 200px; <?php echo empty($project_layers[$selected_sc]) ? 'display:none;' : ''; ?>">
                                                                <label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 3px;"><?php _e('Warstwy (opcjonalnie):', 'develogic'); ?></label>
                                                                <?php if (!empty($selected_sc) && isset($project_layers[$selected_sc])): ?>
                                                                    <div class="develogic-layers-checkboxes">
                                                                    <?php foreach ($project_layers[$selected_sc] as $layer): ?>
                                                                        <label style="display: block; font-size: 13px; padding: 2px 0;">
                                                                            <input type="checkbox"
                                                                                name="building_map[<?php echo esc_attr($building_key); ?>][<?php echo $entry_index; ?>][layers][]"
                                                                                value="<?php echo esc_attr($layer['id']); ?>"
                                                                                <?php checked(in_array($layer['id'], $selected_layers)); ?>
                                                                            >
                                                                            <?php echo esc_html($layer['title']); ?>
                                                                            <span style="color: #888;">(<?php echo $layer['spots_count']; ?> kształtów)</span>
                                                                        </label>
                                                                    <?php endforeach; ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <p class="description" style="font-size: 11px; margin-top: 4px;">
                                                                    <?php _e('Brak zaznaczenia = wszystkie warstwy', 'develogic'); ?>
                                                                </p>
                                                            </div>
                                                            <div style="padding-top: 18px;">
                                                                <button type="button" class="button button-small develogic-remove-row" title="<?php _e('Usuń', 'develogic'); ?>">&times;</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php
                                                    $entry_index++;
                                                    endforeach;
                                                    ?>
                                                    <button type="button" class="button button-small develogic-add-row">+ <?php _e('Dodaj projekt', 'develogic'); ?></button>
                                                </div>
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
            
            <!-- Availability Counters -->
            <div class="card" style="max-width: 900px; margin-bottom: 20px;">
                <h2><?php _e('Liczniki dostępnych lokali', 'develogic'); ?></h2>

                <p class="description">
                    <?php _e('Skonfiguruj automatyczne wyświetlanie liczby wolnych lokali na wybranych spotach (np. piętra na widoku budynku). Przy każdej synchronizacji tooltip wybranego spota zostanie zaktualizowany o liczbę dostępnych lokali.', 'develogic'); ?>
                </p>

                <div class="notice notice-info inline" style="margin: 10px 0;">
                    <p>
                        <strong><?php _e('Szablon:', 'develogic'); ?></strong>
                        <?php _e('Użyj <code>{count}</code> = liczba wolnych lokali, <code>{name}</code> = nazwa spota. Np.: <code>Ilość dostępnych mieszkań - {count}</code> &rarr; "Ilość dostępnych mieszkań - 5". Nagłówek spota (np. "PIĘTRO 4") pozostaje bez zmian.', 'develogic'); ?>
                    </p>
                </div>

                <?php if (empty($projects)): ?>
                    <p><em><?php _e('Brak projektów Image Map Pro.', 'develogic'); ?></em></p>
                <?php else: ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('develogic_imagemappro_settings', 'develogic_imagemappro_nonce'); ?>
                        <input type="hidden" name="develogic_imagemappro_action" value="save_counters">

                        <?php
                        // Build spots data for JS (shortcode => [{id, title, layerID}])
                        $project_spots = $imagemappro_active ? $this->get_project_spots() : array();
                        $spots_json = wp_json_encode($project_spots);
                        ?>
                        <script>var develogicProjectSpots = <?php echo $spots_json; ?>;</script>

                        <div id="develogic-counters-container">
                            <?php
                            $counter_entries = !empty($counters) ? $counters : array();
                            foreach ($counter_entries as $ci => $counter):
                            ?>
                            <div class="develogic-counter-row" style="margin-bottom: 12px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 3px;">
                                <div style="display: flex; gap: 10px; align-items: flex-start; flex-wrap: wrap;">
                                    <div style="min-width: 180px;">
                                        <label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 3px;"><?php _e('Projekt:', 'develogic'); ?></label>
                                        <select name="counters[<?php echo $ci; ?>][shortcode]" class="develogic-counter-project" style="width: 100%;">
                                            <option value=""><?php _e('— wybierz —', 'develogic'); ?></option>
                                            <?php foreach ($projects as $p): ?>
                                                <option value="<?php echo esc_attr($p->shortcode); ?>" <?php selected($p->shortcode, $counter['shortcode']); ?>>
                                                    <?php echo esc_html($p->name); ?> (<?php echo esc_html($p->shortcode); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div style="min-width: 180px;">
                                        <label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 3px;"><?php _e('Spot (kształt):', 'develogic'); ?></label>
                                        <select name="counters[<?php echo $ci; ?>][spot_id]" class="develogic-counter-spot" style="width: 100%;">
                                            <option value=""><?php _e('— wybierz —', 'develogic'); ?></option>
                                            <?php if (!empty($counter['shortcode']) && isset($project_spots[$counter['shortcode']])): ?>
                                                <?php foreach ($project_spots[$counter['shortcode']] as $spot): ?>
                                                    <option value="<?php echo esc_attr($spot['id']); ?>" <?php selected($spot['id'], $counter['spot_id']); ?>>
                                                        <?php echo esc_html($spot['title']); ?> (<?php echo esc_html($spot['id']); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div style="min-width: 120px;">
                                        <label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 3px;"><?php _e('Tryb:', 'develogic'); ?></label>
                                        <select name="counters[<?php echo $ci; ?>][mode]" class="develogic-counter-mode" style="width: 100%;">
                                            <option value="floor" <?php selected($counter['mode'], 'floor'); ?>><?php _e('Per piętro', 'develogic'); ?></option>
                                            <option value="building" <?php selected($counter['mode'], 'building'); ?>><?php _e('Per budynek', 'develogic'); ?></option>
                                        </select>
                                    </div>
                                    <div style="min-width: 150px;">
                                        <label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 3px;"><?php _e('Budynek:', 'develogic'); ?></label>
                                        <select name="counters[<?php echo $ci; ?>][building_id]" class="develogic-counter-building" style="width: 100%;">
                                            <option value=""><?php _e('— wybierz —', 'develogic'); ?></option>
                                            <?php foreach ($buildings as $b): ?>
                                                <option value="<?php echo esc_attr($b['id']); ?>" <?php selected($b['id'], $counter['building_id']); ?>>
                                                    <?php echo esc_html($b['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="develogic-counter-floor-wrap" style="min-width: 80px; <?php echo $counter['mode'] === 'building' ? 'display:none;' : ''; ?>">
                                        <label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 3px;"><?php _e('Piętro:', 'develogic'); ?></label>
                                        <input type="text" name="counters[<?php echo $ci; ?>][floor]" value="<?php echo esc_attr($counter['floor']); ?>" placeholder="np. 0, 1, 2..." style="width: 80px;">
                                    </div>
                                    <div style="min-width: 150px;">
                                        <label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 3px;"><?php _e('Szablon:', 'develogic'); ?></label>
                                        <input type="text" name="counters[<?php echo $ci; ?>][template]" value="<?php echo esc_attr($counter['template']); ?>" placeholder="Ilość dostępnych mieszkań - {count}" style="width: 150px;">
                                    </div>
                                    <div style="padding-top: 18px;">
                                        <button type="button" class="button button-small develogic-remove-counter" title="<?php _e('Usuń', 'develogic'); ?>">&times;</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="button button-small" id="develogic-add-counter">+ <?php _e('Dodaj licznik', 'develogic'); ?></button>

                        <p class="submit">
                            <button type="submit" class="button button-primary">
                                <?php _e('Zapisz liczniki', 'develogic'); ?>
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

            var projectLayers = (typeof develogicProjectLayers !== 'undefined') ? develogicProjectLayers : {};

            // When project select changes, show/hide layers
            $(document).on('change', '.develogic-project-select', function() {
                var $row = $(this).closest('.develogic-mapping-row');
                var $layersContainer = $row.find('.develogic-layers-container');
                var shortcode = $(this).val();
                var layers = projectLayers[shortcode] || [];

                if (layers.length > 1) {
                    var buildingKey = $(this).closest('.develogic-mapping-entries').data('building');
                    var entryIndex = $row.index();
                    var html = '<div class="develogic-layers-checkboxes">';
                    layers.forEach(function(layer) {
                        html += '<label style="display: block; font-size: 13px; padding: 2px 0;">';
                        html += '<input type="checkbox" name="building_map[' + buildingKey + '][' + entryIndex + '][layers][]" value="' + layer.id + '">';
                        html += ' ' + layer.title + ' <span style="color: #888;">(' + layer.spots_count + ' kształtów)</span>';
                        html += '</label>';
                    });
                    html += '</div>';
                    html += '<p class="description" style="font-size: 11px; margin-top: 4px;">Brak zaznaczenia = wszystkie warstwy</p>';
                    $layersContainer.html(html).show();
                } else {
                    $layersContainer.hide().html('');
                }
            });

            // Add new mapping row
            $(document).on('click', '.develogic-add-row', function() {
                var $container = $(this).closest('.develogic-mapping-entries');
                var buildingKey = $container.data('building');
                var entryIndex = $container.find('.develogic-mapping-row').length;
                var $firstRow = $container.find('.develogic-mapping-row:first');
                var $newRow = $firstRow.clone();

                // Reset values
                $newRow.find('select').val('');
                $newRow.find('.develogic-layers-container').hide().html('');

                // Update names
                $newRow.find('select').attr('name', 'building_map[' + buildingKey + '][' + entryIndex + '][shortcode]');

                $newRow.insertBefore($(this));
            });

            // --- Availability Counters ---
            var projectSpots = (typeof develogicProjectSpots !== 'undefined') ? develogicProjectSpots : {};

            // When counter project changes, populate spots dropdown
            $(document).on('change', '.develogic-counter-project', function() {
                var $row = $(this).closest('.develogic-counter-row');
                var $spotSelect = $row.find('.develogic-counter-spot');
                var shortcode = $(this).val();
                var spots = projectSpots[shortcode] || [];

                $spotSelect.html('<option value="">— wybierz —</option>');
                spots.forEach(function(spot) {
                    $spotSelect.append('<option value="' + spot.id + '">' + spot.title + ' (' + spot.id + ')</option>');
                });
            });

            // When mode changes, show/hide floor field
            $(document).on('change', '.develogic-counter-mode', function() {
                var $row = $(this).closest('.develogic-counter-row');
                if ($(this).val() === 'building') {
                    $row.find('.develogic-counter-floor-wrap').hide();
                } else {
                    $row.find('.develogic-counter-floor-wrap').show();
                }
            });

            // Add counter row
            $('#develogic-add-counter').on('click', function() {
                var $container = $('#develogic-counters-container');
                var idx = $container.find('.develogic-counter-row').length;

                var projectOptions = '<option value="">— wybierz —</option>';
                $('.develogic-counter-project:first option').each(function() {
                    if ($(this).val()) {
                        projectOptions += '<option value="' + $(this).val() + '">' + $(this).text() + '</option>';
                    }
                });
                // If no existing rows, build from projects variable
                if (!projectOptions.match(/value="[^"]+"/)) {
                    <?php if (!empty($projects)): ?>
                    <?php foreach ($projects as $p): ?>
                    projectOptions += '<option value="<?php echo esc_js($p->shortcode); ?>"><?php echo esc_js($p->name . ' (' . $p->shortcode . ')'); ?></option>';
                    <?php endforeach; ?>
                    <?php endif; ?>
                }

                var buildingOptions = '<option value="">— wybierz —</option>';
                <?php foreach ($buildings as $b): ?>
                buildingOptions += '<option value="<?php echo esc_js($b['id']); ?>"><?php echo esc_js($b['name']); ?></option>';
                <?php endforeach; ?>

                var html = '<div class="develogic-counter-row" style="margin-bottom: 12px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 3px;">' +
                    '<div style="display: flex; gap: 10px; align-items: flex-start; flex-wrap: wrap;">' +
                    '<div style="min-width: 180px;"><label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 3px;">Projekt:</label>' +
                    '<select name="counters[' + idx + '][shortcode]" class="develogic-counter-project" style="width: 100%;">' + projectOptions + '</select></div>' +
                    '<div style="min-width: 180px;"><label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 3px;">Spot (kształt):</label>' +
                    '<select name="counters[' + idx + '][spot_id]" class="develogic-counter-spot" style="width: 100%;"><option value="">— wybierz —</option></select></div>' +
                    '<div style="min-width: 120px;"><label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 3px;">Tryb:</label>' +
                    '<select name="counters[' + idx + '][mode]" class="develogic-counter-mode" style="width: 100%;"><option value="floor">Per piętro</option><option value="building">Per budynek</option></select></div>' +
                    '<div style="min-width: 150px;"><label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 3px;">Budynek:</label>' +
                    '<select name="counters[' + idx + '][building_id]" class="develogic-counter-building" style="width: 100%;">' + buildingOptions + '</select></div>' +
                    '<div class="develogic-counter-floor-wrap" style="min-width: 80px;"><label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 3px;">Piętro:</label>' +
                    '<input type="text" name="counters[' + idx + '][floor]" placeholder="np. 0, 1, 2..." style="width: 80px;"></div>' +
                    '<div style="min-width: 150px;"><label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 3px;">Szablon:</label>' +
                    '<input type="text" name="counters[' + idx + '][template]" placeholder="Ilość dostępnych mieszkań - {count}" value="Ilość dostępnych mieszkań - {count}" style="width: 150px;"></div>' +
                    '<div style="padding-top: 18px;"><button type="button" class="button button-small develogic-remove-counter" title="Usuń">&times;</button></div>' +
                    '</div></div>';

                $container.append(html);
            });

            // Remove counter row
            $(document).on('click', '.develogic-remove-counter', function() {
                $(this).closest('.develogic-counter-row').remove();
            });

            // Remove mapping row
            $(document).on('click', '.develogic-remove-row', function() {
                var $container = $(this).closest('.develogic-mapping-entries');
                var $rows = $container.find('.develogic-mapping-row');
                if ($rows.length > 1) {
                    $(this).closest('.develogic-mapping-row').remove();
                } else {
                    // Last row - just clear it
                    $rows.find('select').val('');
                    $rows.find('.develogic-layers-container').hide().html('');
                }
            });
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

    /**
     * Get spots for all Image Map Pro projects
     *
     * @return array Shortcode => array of spots [{id, title, layerID}]
     */
    private function get_project_spots() {
        $spots_map = array();

        $old_options = get_option('image-map-pro-wordpress-admin-options', false);
        if ($old_options && isset($old_options['saves']) && is_array($old_options['saves'])) {
            foreach ($old_options['saves'] as $project_id => $project_data) {
                $shortcode = isset($project_data['meta']['shortcode']) ? $project_data['meta']['shortcode'] : '';
                if (empty($shortcode) || empty($project_data['json'])) {
                    continue;
                }
                $json = json_decode(stripslashes($project_data['json']), true);
                if (!$json) {
                    continue;
                }
                $spots_map[$shortcode] = $this->extract_spots_from_json($json);
            }
        }

        // Also try new version table
        global $wpdb;
        $table_name = $wpdb->prefix . 'image_map_pro_projects';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $table_projects = $wpdb->get_results("SELECT shortcode, json FROM $table_name");
            if ($table_projects) {
                foreach ($table_projects as $proj) {
                    if (isset($spots_map[$proj->shortcode])) {
                        continue;
                    }
                    $json = json_decode(stripslashes($proj->json), true);
                    if (!$json) {
                        continue;
                    }
                    $spots_map[$proj->shortcode] = $this->extract_spots_from_json($json);
                }
            }
        }

        return $spots_map;
    }

    /**
     * Extract spots from project JSON (supports both spots array and artboards)
     *
     * @param array $json Decoded project JSON
     * @return array Array of spots [{id, title, layerID}]
     */
    private function extract_spots_from_json($json) {
        $spots = array();

        // From spots array
        if (!empty($json['spots']) && is_array($json['spots'])) {
            foreach ($json['spots'] as $spot) {
                $spots[] = array(
                    'id'      => isset($spot['id']) ? $spot['id'] : '',
                    'title'   => isset($spot['title']) ? $spot['title'] : '',
                    'layerID' => isset($spot['layerID']) ? $spot['layerID'] : '',
                );
            }
        }

        // From artboards
        if (!empty($json['artboards']) && is_array($json['artboards'])) {
            foreach ($json['artboards'] as $artboard) {
                if (empty($artboard['children']) || !is_array($artboard['children'])) {
                    continue;
                }
                foreach ($artboard['children'] as $child) {
                    $spots[] = array(
                        'id'      => isset($child['id']) ? $child['id'] : '',
                        'title'   => isset($child['title']) ? $child['title'] : '',
                        'layerID' => isset($child['layerID']) ? $child['layerID'] : '',
                    );
                }
            }
        }

        return $spots;
    }

    /**
     * Get layers for all Image Map Pro projects that have them
     *
     * @return array Shortcode => array of layers [{id, title, spots_count}]
     */
    private function get_project_layers() {
        $layers_map = array();

        $old_options = get_option('image-map-pro-wordpress-admin-options', false);
        if ($old_options && isset($old_options['saves']) && is_array($old_options['saves'])) {
            foreach ($old_options['saves'] as $project_id => $project_data) {
                $shortcode = isset($project_data['meta']['shortcode']) ? $project_data['meta']['shortcode'] : '';
                if (empty($shortcode) || empty($project_data['json'])) {
                    continue;
                }
                $json = json_decode(stripslashes($project_data['json']), true);
                if (!$json) {
                    continue;
                }
                $layers = isset($json['layers']['layers_list']) ? $json['layers']['layers_list'] : array();
                $spots = isset($json['spots']) ? $json['spots'] : array();
                if (empty($layers) || count($layers) <= 1) {
                    continue;
                }
                $project_layers = array();
                foreach ($layers as $layer) {
                    $layer_id = $layer['id'];
                    $layer_spots = array_filter($spots, function($s) use ($layer_id) {
                        return isset($s['layerID']) && $s['layerID'] == $layer_id;
                    });
                    $project_layers[] = array(
                        'id' => $layer_id,
                        'title' => $layer['title'],
                        'spots_count' => count($layer_spots),
                    );
                }
                $layers_map[$shortcode] = $project_layers;
            }
        }

        return $layers_map;
    }
}

