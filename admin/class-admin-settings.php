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
        // Disabled: API error notices in admin panel
        // add_action('admin_notices', array($this, 'show_api_error_notice'));
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
            array('field' => 'api_timeout', 'min' => 5, 'max' => 360, 'default' => 30)
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
            'enable_cron_sync',
            __('Automatyczna synchronizacja (WP-Cron)', 'develogic'),
            array($this, 'render_checkbox_field'),
            'develogic',
            'develogic_sync_section',
            array(
                'field' => 'enable_cron_sync',
                'description' => __('Włącz automatyczną synchronizację co 5 minut za pomocą WordPress Cron. Wymaga prawidłowo skonfigurowanego WP-Cron.', 'develogic')
            )
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
        
        add_settings_field(
            'contact_email',
            __('Email kontaktowy (zapytaj o mieszkanie)', 'develogic'),
            array($this, 'render_text_field'),
            'develogic',
            'develogic_a1_section',
            array('field' => 'contact_email', 'placeholder' => get_option('admin_email'), 'description' => __('Adres email na który będą wysyłane zapytania o mieszkanie. Jeśli nie ustawiono, używany jest email administratora.', 'develogic'))
        );
        
        add_settings_field(
            'tour_360_url',
            __('Domyślny link 360°', 'develogic'),
            array($this, 'render_text_field'),
            'develogic',
            'develogic_a1_section',
            array('field' => 'tour_360_url', 'placeholder' => 'https://example.com/360-tour', 'description' => __('Domyślny URL do spaceru 360° używany gdy lokal nie ma własnego linku w polu "stage" ani w projekcjach.', 'develogic'))
        );
        
        // Appearance Settings Section
        add_settings_section(
            'develogic_appearance_section',
            __('Wygląd', 'develogic'),
            array($this, 'render_appearance_section_description'),
            'develogic'
        );
        
        add_settings_field(
            'primary_color',
            __('Kolor primary', 'develogic'),
            array($this, 'render_color_field'),
            'develogic',
            'develogic_appearance_section',
            array('field' => 'primary_color', 'default' => '#0066cc', 'description' => __('Główny kolor przycisków i elementów interaktywnych', 'develogic'))
        );
        
        // Additional Options Settings Section
        add_settings_section(
            'develogic_additional_options_section',
            __('Opcje dodatkowe', 'develogic'),
            array($this, 'render_additional_options_section_description'),
            'develogic'
        );
        
        add_settings_field(
            'additional_options',
            __('Dostępne opcje dodatkowe', 'develogic'),
            array($this, 'render_additional_options_checkboxes'),
            'develogic',
            'develogic_additional_options_section',
            array('field' => 'additional_options')
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
        
        // Cron sync setting
        $old_settings = get_option('develogic_settings', array());
        $old_cron_enabled = isset($old_settings['enable_cron_sync']) ? $old_settings['enable_cron_sync'] : false;
        $new_cron_enabled = isset($input['enable_cron_sync']) ? (bool)$input['enable_cron_sync'] : false;
        $output['enable_cron_sync'] = $new_cron_enabled;
        
        // Handle cron scheduling changes
        if ($old_cron_enabled !== $new_cron_enabled) {
            if ($new_cron_enabled) {
                // Enable cron - schedule if not already scheduled
                if (!wp_next_scheduled('develogic_sync_cron')) {
                    wp_schedule_event(time(), 'every_5_minutes', 'develogic_sync_cron');
                }
            } else {
                // Disable cron - unschedule if scheduled
                $timestamp = wp_next_scheduled('develogic_sync_cron');
                if ($timestamp) {
                    wp_unschedule_event($timestamp, 'develogic_sync_cron');
                }
            }
        }
        
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
        
        // Contact email - sanitize email
        $output['contact_email'] = isset($input['contact_email']) ? sanitize_email(trim($input['contact_email'])) : '';
        
        // Tour 360 URL - sanitize URL
        $output['tour_360_url'] = isset($input['tour_360_url']) ? esc_url_raw(trim($input['tour_360_url'])) : '';
        
        // Primary color - sanitize hex color
        if (isset($input['primary_color'])) {
            $color = sanitize_text_field($input['primary_color']);
            // Validate hex color format
            if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
                $output['primary_color'] = $color;
            } else {
                $output['primary_color'] = '#0066cc'; // Default if invalid
            }
        } else {
            $output['primary_color'] = '#0066cc'; // Default
        }
        
        // Additional options - sanitize array
        if (isset($input['additional_options']) && is_array($input['additional_options'])) {
            $output['additional_options'] = array_map('sanitize_text_field', $input['additional_options']);
        } else {
            // Default options if not set
            $output['additional_options'] = array('promo', '2bath', 'wardrobe', 'lake_view');
        }
        
        // Preserve sync_investments and sync_buildings if not in input
        // (they are managed on the sync page, not in settings)
        if (isset($input['sync_investments']) && is_array($input['sync_investments'])) {
            $output['sync_investments'] = array_map('absint', $input['sync_investments']);
        } else {
            // Keep existing value if not in input (use $old_settings already loaded above)
            $output['sync_investments'] = isset($old_settings['sync_investments']) && is_array($old_settings['sync_investments'])
                ? $old_settings['sync_investments']
                : array();
        }
        
        if (isset($input['sync_buildings']) && is_array($input['sync_buildings'])) {
            $output['sync_buildings'] = array_map('absint', $input['sync_buildings']);
        } else {
            // Keep existing value if not in input (use $old_settings already loaded above)
            $output['sync_buildings'] = isset($old_settings['sync_buildings']) && is_array($old_settings['sync_buildings'])
                ? $old_settings['sync_buildings']
                : array();
        }
        
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
    
    public function render_appearance_section_description() {
        echo '<p>' . __('Dostosuj wygląd elementów interfejsu.', 'develogic') . '</p>';
    }
    
    public function render_additional_options_section_description() {
        echo '<p>' . __('Wybierz które opcje dodatkowe mają być dostępne w filtrach mieszkań. Opcje są filtrowane na podstawie atrybutów mieszkań z API.', 'develogic') . '</p>';
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
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
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
        
        $statuses = array('Wolny', 'Rezerwacja', 'Miękka rezerwacja', 'Sprzedany', 'Sprzedane');
        
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
    
    public function render_color_field($args) {
        $settings = get_option('develogic_settings');
        $value = isset($settings[$args['field']]) ? $settings[$args['field']] : (isset($args['default']) ? $args['default'] : '#0066cc');
        
        printf(
            '<input type="color" name="develogic_settings[%s]" value="%s" class="color-picker">',
            esc_attr($args['field']),
            esc_attr($value)
        );
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }
    
    public function render_additional_options_checkboxes($args) {
        $settings = get_option('develogic_settings');
        $selected = isset($settings[$args['field']]) && is_array($settings[$args['field']]) 
            ? $settings[$args['field']] 
            : array('promo', '2bath', 'wardrobe', 'lake_view');
        
        // Available options with their labels and attribute matching patterns
        $available_options = array(
            'promo' => array(
                'label' => __('W promocji', 'develogic'),
                'description' => __('Filtruje mieszkania z promocją (sprawdza atrybut "promocja" oraz maxDiscountPercent)', 'develogic')
            ),
            '2bath' => array(
                'label' => __('2 łazienki', 'develogic'),
                'description' => __('Filtruje mieszkania z 2 łazienkami (sprawdza atrybut "2 lazienki")', 'develogic')
            ),
            'wardrobe' => array(
                'label' => __('Z garderobą', 'develogic'),
                'description' => __('Filtruje mieszkania z garderobą (sprawdza atrybut "garderoba")', 'develogic')
            ),
            'lake_view' => array(
                'label' => __('Widok na jezioro', 'develogic'),
                'description' => __('Filtruje mieszkania z widokiem na jezioro (sprawdza atrybut "widok na jezioro")', 'develogic')
            ),
            'balcony' => array(
                'label' => __('Balkon', 'develogic'),
                'description' => __('Filtruje mieszkania z balkonem (sprawdza atrybut "balkon")', 'develogic')
            ),
            '2balconies' => array(
                'label' => __('2 balkony', 'develogic'),
                'description' => __('Filtruje mieszkania z 2 balkonami (sprawdza atrybut "2 balkony")', 'develogic')
            ),
            'terrace' => array(
                'label' => __('Taras', 'develogic'),
                'description' => __('Filtruje mieszkania z tarasem (sprawdza atrybut "taras")', 'develogic')
            ),
            'garden' => array(
                'label' => __('Ogród', 'develogic'),
                'description' => __('Filtruje mieszkania z ogrodem (sprawdza atrybut "ogród")', 'develogic')
            ),
            'kitchen_annex' => array(
                'label' => __('Aneks kuchenny', 'develogic'),
                'description' => __('Filtruje mieszkania z aneksem kuchennym (sprawdza atrybut "aneks kuchenny")', 'develogic')
            ),
            'bright_kitchen' => array(
                'label' => __('Jasna kuchnia', 'develogic'),
                'description' => __('Filtruje mieszkania z jasną kuchnią (sprawdza atrybut "jasna kuchnia")', 'develogic')
            ),
            'elevator' => array(
                'label' => __('Winda', 'develogic'),
                'description' => __('Filtruje mieszkania z windą (sprawdza atrybut "winda")', 'develogic')
            ),
            'separate_wc' => array(
                'label' => __('Osobne WC', 'develogic'),
                'description' => __('Filtruje mieszkania z osobnym WC (sprawdza atrybut "osobne WC")', 'develogic')
            ),
            'storage' => array(
                'label' => __('Pom. gospodarcze', 'develogic'),
                'description' => __('Filtruje mieszkania z pomieszczeniem gospodarczym (sprawdza atrybut "pom. gospodarcze")', 'develogic')
            ),
            'cellar' => array(
                'label' => __('Komórka lokatorska', 'develogic'),
                'description' => __('Filtruje mieszkania z komórką lokatorską (sprawdza atrybut "komórka lokatorska")', 'develogic')
            ),
            'air_conditioning' => array(
                'label' => __('Klimatyzacja', 'develogic'),
                'description' => __('Filtruje mieszkania z klimatyzacją (sprawdza atrybut "klimatyzacja")', 'develogic')
            ),
            'parking' => array(
                'label' => __('Parking', 'develogic'),
                'description' => __('Filtruje mieszkania z parkingiem (sprawdza atrybut "parking")', 'develogic')
            ),
            'parking_space' => array(
                'label' => __('Miejsce postojowe', 'develogic'),
                'description' => __('Filtruje mieszkania z miejscem postojowym (sprawdza atrybut "miejsce postojowe")', 'develogic')
            ),
            'playground' => array(
                'label' => __('Plac zabaw', 'develogic'),
                'description' => __('Filtruje mieszkania z placem zabaw (sprawdza atrybut "plac zabaw")', 'develogic')
            )
        );
        
        echo '<fieldset>';
        foreach ($available_options as $key => $option) {
            $checked = in_array($key, $selected);
            printf(
                '<label style="display: block; margin-bottom: 10px;"><input type="checkbox" name="develogic_settings[%s][]" value="%s" %s> <strong>%s</strong><br><span style="color: #666; font-size: 12px; margin-left: 24px;">%s</span></label>',
                esc_attr($args['field']),
                esc_attr($key),
                checked($checked, true, false),
                esc_html($option['label']),
                esc_html($option['description'])
            );
        }
        echo '</fieldset>';
        echo '<p class="description">' . __('Zaznacz opcje które mają być dostępne w filtrach mieszkań. Opcje są filtrowane na podstawie atrybutów mieszkań z API Develogic.', 'develogic') . '</p>';
    }
}

