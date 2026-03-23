<?php
/**
 * Develogic Assets Manager
 *
 * @package Develogic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Develogic_Assets
 */
class Develogic_Assets {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        // Shuffle.js for filtering and sorting
        wp_register_script(
            'shufflejs',
            'https://cdn.jsdelivr.net/npm/shufflejs@6.1.0/dist/shuffle.min.js',
            array(),
            '6.1.0',
            true
        );
        
        // Tippy.js for tooltips
        wp_register_script(
            'tippy-popper',
            'https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js',
            array(),
            '2.11.8',
            true
        );
        
        wp_register_script(
            'tippy',
            'https://cdn.jsdelivr.net/npm/tippy.js@6.3.7/dist/tippy-bundle.umd.min.js',
            array('tippy-popper'),
            '6.3.7',
            true
        );
        
        // Main plugin script
        wp_register_script(
            'develogic-main',
            DEVELOGIC_PLUGIN_URL . 'assets/js/main.js',
            array('jquery'),
            DEVELOGIC_VERSION,
            true
        );
        
        // Apartments list script
        wp_register_script(
            'develogic-apartments-list',
            DEVELOGIC_PLUGIN_URL . 'assets/js/apartments-list.js',
            array(),
            DEVELOGIC_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('develogic-main', 'develogicData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('develogic/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'i18n' => array(
                'loading' => __('Ładowanie...', 'develogic'),
                'error' => __('Wystąpił błąd', 'develogic'),
                'noResults' => __('Brak wyników', 'develogic'),
                'filterResults' => __('Filtruj wyniki', 'develogic'),
                'resetFilters' => __('Resetuj filtry', 'develogic'),
                'addedToFavorites' => __('Dodano do obserwowanych', 'develogic'),
                'removedFromFavorites' => __('Usunięto z obserwowanych', 'develogic'),
            ),
        ));
        
        // Localize apartments list script
        wp_localize_script('develogic-apartments-list', 'develogicApartmentsData', array(
            'i18n' => array(
                'zapytaj' => __('zapytaj', 'develogic'),
                'obserwuj' => __('obserwuj', 'develogic'),
                'obserwujesz' => __('obserwujesz', 'develogic'),
            ),
            'restUrl' => rest_url('develogic/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'developer_name' => develogic()->get_setting('developer_name', get_bloginfo('name')),
            'contact_link_desktop' => develogic()->get_setting('contact_link_desktop', ''),
            'contact_link_mobile' => develogic()->get_setting('contact_link_mobile', 'mailto:' . get_option('admin_email')),
            'contact_phone' => develogic()->get_setting('contact_phone', ''),
        ));
    }
    
    /**
     * Enqueue styles
     */
    public function enqueue_styles() {
        // Tippy.js styles
        wp_register_style(
            'tippy',
            'https://cdn.jsdelivr.net/npm/tippy.js@6.3.7/dist/tippy.min.css',
            array(),
            '6.3.7'
        );
        
        // Main plugin styles
        wp_register_style(
            'develogic-main',
            DEVELOGIC_PLUGIN_URL . 'assets/css/main.css',
            array(),
            DEVELOGIC_VERSION
        );
        
        // Apartments list styles
        wp_register_style(
            'develogic-apartments-list',
            DEVELOGIC_PLUGIN_URL . 'assets/css/apartments-list.css',
            array(),
            DEVELOGIC_VERSION
        );
        
        // Add inline CSS with CSS variables for primary color
        add_action('wp_head', array($this, 'add_primary_color_css'), 100);
        
    }
    
    /**
     * Add inline CSS with CSS variables for primary color
     */
    public function add_primary_color_css() {
        $primary_color = develogic()->get_setting('primary_color', '#0066cc');
        
        // Calculate hover color (slightly darker)
        $hover_color = $this->darken_color($primary_color, 0.1);
        
        $css = sprintf(
            ':root {
                --develogic-primary-color: %s;
                --develogic-primary-color-hover: %s;
            }',
            esc_attr($primary_color),
            esc_attr($hover_color)
        );
        
        echo '<style id="develogic-primary-color">' . $css . '</style>';
    }
    
    /**
     * Convert hex color to RGB array
     *
     * @param string $hex Hex color code
     * @return array RGB values
     */
    private function hex_to_rgb($hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return array(
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        );
    }
    
    /**
     * Darken a hex color by a percentage
     *
     * @param string $hex Hex color code
     * @param float $percent Percentage to darken (0-1)
     * @return string Darkened hex color
     */
    private function darken_color($hex, $percent) {
        $rgb = $this->hex_to_rgb($hex);
        $r = max(0, min(255, round($rgb['r'] * (1 - $percent))));
        $g = max(0, min(255, round($rgb['g'] * (1 - $percent))));
        $b = max(0, min(255, round($rgb['b'] * (1 - $percent))));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    /**
     * Enqueue assets for A1 layout
     */
    public static function enqueue_a1_assets() {
        wp_enqueue_style('develogic-main');
        wp_enqueue_script('develogic-main');
    }
    
    /**
     * Enqueue assets for price history (without chart)
     */
    public static function enqueue_price_history_assets() {
        wp_enqueue_script('develogic-main');
        wp_enqueue_style('develogic-main');
    }
}

