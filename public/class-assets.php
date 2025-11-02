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
        
        // Chart.js for price history
        wp_register_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            array(),
            '4.4.0',
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
            'developer_name' => develogic()->get_setting('developer_name', get_bloginfo('name')),
            'contact_email' => develogic()->get_setting('contact_email', get_option('admin_email')),
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
        
    }
    
    /**
     * Enqueue assets for A1 layout
     */
    public static function enqueue_a1_assets() {
        wp_enqueue_style('develogic-main');
        wp_enqueue_script('develogic-main');
    }
    
    /**
     * Enqueue assets for price history
     */
    public static function enqueue_price_history_assets() {
        wp_enqueue_script('chartjs');
        wp_enqueue_script('develogic-main');
        wp_enqueue_style('develogic-main');
    }
}

