<?php
/**
 * Develogic Admin Settings
 *
 * @package Develogic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Develogic_Admin_Settings
 */
class Develogic_Admin_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'show_api_error_notice'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Develogic', 'develogic'),
            __('Develogic', 'develogic'),
            'manage_options',
            'develogic',
            array($this, 'render_settings_page'),
            'dashicons-building',
            58
        );
        
        add_submenu_page(
            'develogic',
            __('Ustawienia', 'develogic'),
            __('Ustawienia', 'develogic'),
            'manage_options',
            'develogic',
            array($this, 'render_settings_page')
        );
        
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('develogic_settings', 'develogic_settings', array($this, 'sanitize_settings'));
        
        // API Settings Section
        add_settings_section(
            'develogic_api_section',
            __('Ustawienia API', 'develogic'),
            array($this, 'render_api_section_description'),
            'develogic'
        );
        
        add_settings_field(
            'api_base_url',
            __('URL bazowy API', 'develogic'),
            array($this, 'render_text_field'),
            'develogic',
            'develogic_api_section',
            array('field' => 'api_base_url', 'placeholder' => 'https://api.develogic.pl')
        );
        
        add_settings_field(
            'api_key',
            __('Klucz API', 'develogic'),
            array($this, 'render_password_field'),
            'develogic',
            'develogic_api_section',
            array('field' => 'api_key')
        );
        
        add_settings_field(
            'api_timeout',
            __('Timeout (sekundy)', 'develogic'),
            array($this, 'render_number_field'),
            'develogic',
            'develogic_api_section',
            array('field' => 'api_timeout', 'min' => 5, 'max' => 120, 'default' => 30)
        );
        
        add_settings_field(
            'sync_secret_key',
            __('Secret Key (dla zewnętrznego CRON)', 'develogic'),
            array($this, 'render_password_field'),
            'develogic',
            'develogic_api_section',
            array('field' => 'sync_secret_key', 'description' => __('Klucz używany do autoryzacji zewnętrznego crona. Wygenerowany automatycznie.', 'develogic'))
        );
        
        // Synchronization Settings Section
        add_settings_section(
            'develogic_sync_section',
            __('Ustawienia synchronizacji', 'develogic'),
            array($this, 'render_sync_section_description'),
            'develogic'
        );
        
        add_settings_field(
            'sync_investments',
            __('Inwestycje do synchronizacji', 'develogic'),
            array($this, 'render_investment_checkboxes'),
            'develogic',
            'develogic_sync_section',
            array('field' => 'sync_investments')
        );
        
        add_settings_field(
            'sync_buildings',
            __('Budynki do wyświetlania', 'develogic'),
            array($this, 'render_building_checkboxes'),
            'develogic',
            'develogic_sync_section',
            array('field' => 'sync_buildings')
        );
        
        // A1 Layout Settings Section (removed cache section)
        // A1 Layout Settings Section
        add_settings_section(
            'develogic_a1_section',
            __('Ustawienia layoutu A1', 'develogic'),
            array($this, 'render_a1_section_description'),
            'develogic'
        );
        
        add_settings_field(
            'developer_name',
            __('Nazwa dewelopera', 'develogic'),
            array($this, 'render_text_field'),
            'develogic',
            'develogic_a1_section',
            array('field' => 'developer_name', 'default' => get_bloginfo('name'))
        );
        
        add_settings_field(
            'default_sort_by',
            __('Domyślne sortowanie', 'develogic'),
            array($this, 'render_sort_by_field'),
            'develogic',
            'develogic_a1_section',
            array('field' => 'default_sort_by')
        );
        
        add_settings_field(
            'default_sort_dir',
            __('Kierunek sortowania', 'develogic'),
            array($this, 'render_sort_dir_field'),
            'develogic',
            'develogic_a1_section',
            array('field' => 'default_sort_dir')
        );
        
        add_settings_field(
            'price_m2_source',
            __('Źródło ceny m²', 'develogic'),
            array($this, 'render_price_source_field'),
            'develogic',
            'develogic_a1_section',
            array('field' => 'price_m2_source')
        );
        
        add_settings_field(
            'visible_statuses',
            __('Widoczne statusy', 'develogic'),
            array($this, 'render_status_checkboxes'),
            'develogic',
            'develogic_a1_section',
            array('field' => 'visible_statuses')
        );
        
        add_settings_field(
            'show_print',
            __('Włącz funkcję druku', 'develogic'),
            array($this, 'render_checkbox_field'),
            'develogic',
            'develogic_a1_section',
            array('field' => 'show_print')
        );
        
        add_settings_field(
            'show_favorite',
            __('Włącz funkcję "obserwuj"', 'develogic'),
            array($this, 'render_checkbox_field'),
            'develogic',
            'develogic_a1_section',
            array('field' => 'show_favorite')
        );
        
        add_settings_field(
            'pdf_source',
            __('Źródło PDF', 'develogic'),
            array($this, 'render_pdf_source_field'),
            'develogic',
            'develogic_a1_section',
            array('field' => 'pdf_source')
        );
        
        add_settings_field(
            'pdf_pattern',
            __('Wzorzec URL PDF', 'develogic'),
            array($this, 'render_text_field'),
            'develogic',
            'develogic_a1_section',
            array('field' => 'pdf_pattern', 'placeholder' => 'https://example.com/pdf/{localId}', 'description' => __('Użyj {localId} lub {number} jako placeholdera', 'develogic'))
        );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $output = array();
        
        $output['api_base_url'] = isset($input['api_base_url']) ? trim($input['api_base_url']) : '';
        $output['api_key'] = isset($input['api_key']) ? trim($input['api_key']) : '';
        $output['api_timeout'] = isset($input['api_timeout']) ? absint($input['api_timeout']) : 30;
        $output['sync_secret_key'] = isset($input['sync_secret_key']) ? sanitize_text_field($input['sync_secret_key']) : wp_generate_password(32, false);
        
        $output['developer_name'] = isset($input['developer_name']) ? sanitize_text_field($input['developer_name']) : get_bloginfo('name');
        $output['default_sort_by'] = isset($input['default_sort_by']) ? sanitize_key($input['default_sort_by']) : 'priceGrossm2';
        $output['default_sort_dir'] = isset($input['default_sort_dir']) ? sanitize_key($input['default_sort_dir']) : 'asc';
        $output['price_m2_source'] = isset($input['price_m2_source']) ? sanitize_key($input['price_m2_source']) : 'priceGrossm2';
        
        $output['visible_statuses'] = isset($input['visible_statuses']) && is_array($input['visible_statuses']) 
            ? array_map('sanitize_text_field', $input['visible_statuses']) 
            : array('Wolny', 'Rezerwacja');
        
        $output['show_print'] = isset($input['show_print']) ? (bool)$input['show_print'] : false;
        $output['show_favorite'] = isset($input['show_favorite']) ? (bool)$input['show_favorite'] : false;
        $output['favorite_persist'] = 'localstorage'; // Always localStorage for now
        
        $output['pdf_source'] = isset($input['pdf_source']) ? sanitize_key($input['pdf_source']) : 'off';
        $output['pdf_pattern'] = isset($input['pdf_pattern']) ? esc_url_raw($input['pdf_pattern']) : '';
        
        $output['sync_investments'] = isset($input['sync_investments']) && is_array($input['sync_investments']) 
            ? array_map('absint', $input['sync_investments']) 
            : array();
        
        $output['sync_buildings'] = isset($input['sync_buildings']) && is_array($input['sync_buildings']) 
            ? array_map('absint', $input['sync_buildings']) 
            : array();
        
        return $output;
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('develogic_settings');
                do_settings_sections('develogic');
                submit_button(__('Zapisz ustawienia', 'develogic'));
                ?>
            </form>
        </div>
        <?php
    }
    
    
    /**
     * Show API error notice
     */
    public function show_api_error_notice() {
        $error = get_transient('develogic_last_api_error');
        
        if ($error) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong><?php _e('Błąd Develogic API:', 'develogic'); ?></strong>
                    <?php echo esc_html($error['message']); ?>
                </p>
                <p>
                    <small>
                        <?php echo esc_html(sprintf(__('Endpoint: %s | Czas: %s', 'develogic'), $error['endpoint'], $error['time'])); ?>
                    </small>
                </p>
                <?php if (!empty($error['details'])): ?>
                <details style="margin-top: 10px;">
                    <summary style="cursor: pointer; font-weight: bold;"><?php _e('Szczegóły techniczne (kliknij aby rozwinąć)', 'develogic'); ?></summary>
                    <pre style="background: #f5f5f5; padding: 10px; margin-top: 10px; overflow-x: auto; font-size: 12px;"><?php echo esc_html($error['details']); ?></pre>
                </details>
                <?php endif; ?>
            </div>
            <?php
        }
        
    }
    
    // Field renderers
    
    public function render_api_section_description() {
        echo '<p>' . __('Skonfiguruj połączenie z API Develogic.', 'develogic') . '</p>';
    }
    
    public function render_sync_section_description() {
        echo '<p>' . __('Wybierz które inwestycje mają być synchronizowane z API Develogic.', 'develogic') . '</p>';
    }
    
    public function render_a1_section_description() {
        echo '<p>' . __('Ustawienia dla layoutu A1 (JeziornaTowers, OstojaOsiedle).', 'develogic') . '</p>';
    }
    
    public function render_text_field($args) {
        $settings = get_option('develogic_settings');
        $value = isset($settings[$args['field']]) ? $settings[$args['field']] : (isset($args['default']) ? $args['default'] : '');
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        
        printf(
            '<input type="text" name="develogic_settings[%s]" value="%s" placeholder="%s" class="regular-text">',
            esc_attr($args['field']),
            esc_attr($value),
            esc_attr($placeholder)
        );
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }
    
    public function render_password_field($args) {
        $settings = get_option('develogic_settings');
        $value = isset($settings[$args['field']]) ? $settings[$args['field']] : '';
        $masked = !empty($value) ? str_repeat('*', 20) : '';
        
        printf(
            '<input type="password" name="develogic_settings[%s]" value="%s" placeholder="%s" class="regular-text">',
            esc_attr($args['field']),
            esc_attr($value),
            esc_attr($masked)
        );
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }
    
    public function render_number_field($args) {
        $settings = get_option('develogic_settings');
        $value = isset($settings[$args['field']]) ? $settings[$args['field']] : (isset($args['default']) ? $args['default'] : 0);
        $min = isset($args['min']) ? $args['min'] : 0;
        $max = isset($args['max']) ? $args['max'] : 999999;
        
        printf(
            '<input type="number" name="develogic_settings[%s]" value="%s" min="%d" max="%d" class="small-text">',
            esc_attr($args['field']),
            esc_attr($value),
            $min,
            $max
        );
    }
    
    public function render_checkbox_field($args) {
        $settings = get_option('develogic_settings');
        $value = isset($settings[$args['field']]) ? $settings[$args['field']] : false;
        
        printf(
            '<input type="checkbox" name="develogic_settings[%s]" value="1" %s>',
            esc_attr($args['field']),
            checked($value, true, false)
        );
    }
    
    public function render_sort_by_field($args) {
        $settings = get_option('develogic_settings');
        $value = isset($settings[$args['field']]) ? $settings[$args['field']] : 'priceGrossm2';
        
        $options = array(
            'floor' => __('Piętro', 'develogic'),
            'area' => __('Metraż', 'develogic'),
            'rooms' => __('Pokoje', 'develogic'),
            'priceGross' => __('Cena', 'develogic'),
            'priceGrossm2' => __('Cena m²', 'develogic'),
        );
        
        echo '<select name="develogic_settings[' . esc_attr($args['field']) . ']">';
        foreach ($options as $key => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($key),
                selected($value, $key, false),
                esc_html($label)
            );
        }
        echo '</select>';
    }
    
    public function render_sort_dir_field($args) {
        $settings = get_option('develogic_settings');
        $value = isset($settings[$args['field']]) ? $settings[$args['field']] : 'asc';
        
        $options = array(
            'asc' => __('Rosnąco', 'develogic'),
            'desc' => __('Malejąco', 'develogic'),
        );
        
        echo '<select name="develogic_settings[' . esc_attr($args['field']) . ']">';
        foreach ($options as $key => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($key),
                selected($value, $key, false),
                esc_html($label)
            );
        }
        echo '</select>';
    }
    
    public function render_price_source_field($args) {
        $settings = get_option('develogic_settings');
        $value = isset($settings[$args['field']]) ? $settings[$args['field']] : 'priceGrossm2';
        
        $options = array(
            'priceGrossm2' => __('Cena standardowa brutto m²', 'develogic'),
            'omnibusPriceGrossm2' => __('Cena omnibus brutto m²', 'develogic'),
        );
        
        echo '<select name="develogic_settings[' . esc_attr($args['field']) . ']">';
        foreach ($options as $key => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($key),
                selected($value, $key, false),
                esc_html($label)
            );
        }
        echo '</select>';
    }
    
    public function render_status_checkboxes($args) {
        $settings = get_option('develogic_settings');
        $selected = isset($settings[$args['field']]) ? $settings[$args['field']] : array('Wolny', 'Rezerwacja');
        
        $statuses = array('Wolny', 'Rezerwacja', 'Sprzedany', 'Sprzedane');
        
        foreach ($statuses as $status) {
            printf(
                '<label><input type="checkbox" name="develogic_settings[%s][]" value="%s" %s> %s</label><br>',
                esc_attr($args['field']),
                esc_attr($status),
                checked(in_array($status, $selected), true, false),
                esc_html($status)
            );
        }
    }
    
    public function render_pdf_source_field($args) {
        $settings = get_option('develogic_settings');
        $value = isset($settings[$args['field']]) ? $settings[$args['field']] : 'off';
        
        $options = array(
            'off' => __('Wyłączone', 'develogic'),
            'pattern' => __('Użyj wzorca URL', 'develogic'),
            'field' => __('Z pola API (jeśli dostępne)', 'develogic'),
        );
        
        echo '<select name="develogic_settings[' . esc_attr($args['field']) . ']">';
        foreach ($options as $key => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($key),
                selected($value, $key, false),
                esc_html($label)
            );
        }
        echo '</select>';
    }
    
    public function render_investment_checkboxes($args) {
        $settings = get_option('develogic_settings');
        $selected = isset($settings[$args['field']]) && is_array($settings[$args['field']]) 
            ? $settings[$args['field']] 
            : array();
        
        // Get investments from taxonomy (already synced)
        $investments = Develogic_Local_Query::get_investments();
        
        if (empty($investments)) {
            echo '<p class="description">' . __('Brak dostępnych inwestycji. Przejdź do zakładki <strong>Synchronizacja</strong> i kliknij "Pobierz listę inwestycji z API".', 'develogic') . '</p>';
            return;
        }
        
        // Show investments as read-only (grayed out)
        echo '<fieldset style="opacity: 0.6;">';
        foreach ($investments as $investment) {
            $investment_id = !empty($investment['ID']) ? absint($investment['ID']) : 0;
            $checked = in_array($investment_id, $selected);
            
            printf(
                '<label style="display: block; margin-bottom: 5px;"><input type="checkbox" disabled %s> %s</label>',
                checked($checked, true, false),
                esc_html($investment['Name'])
            );
        }
        echo '</fieldset>';
        echo '<p class="description">' . __('Wybór inwestycji można zmienić w zakładce <strong>Synchronizacja</strong>.', 'develogic') . '</p>';
    }
    
    public function render_building_checkboxes($args) {
        $settings = get_option('develogic_settings');
        $selected = isset($settings[$args['field']]) && is_array($settings[$args['field']]) 
            ? $settings[$args['field']] 
            : array();
        
        // Get buildings from taxonomy and locals
        $buildings = Develogic_Local_Query::get_buildings_for_settings();
        
        if (empty($buildings)) {
            echo '<p class="description">' . __('Brak dostępnych budynków. Uruchom synchronizację aby pobrać listę budynków z API.', 'develogic') . '</p>';
            return;
        }
        
        echo '<fieldset>';
        foreach ($buildings as $building) {
            $building_id = !empty($building['ID']) ? absint($building['ID']) : 0;
            $checked = in_array($building_id, $selected);
            
            printf(
                '<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="develogic_settings[%s][]" value="%d" %s> %s</label>',
                esc_attr($args['field']),
                $building_id,
                checked($checked, true, false),
                esc_html($building['Name'])
            );
        }
        echo '</fieldset>';
        echo '<p class="description">' . __('Zaznacz budynki które mają być wyświetlane w widoku mieszkań. Jeśli nic nie zostanie zaznaczone, wyświetlane będą wszystkie budynki.', 'develogic') . '</p>';
    }
}

