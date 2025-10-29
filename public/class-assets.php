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
        
        // LightGallery
        wp_register_script(
            'lightgallery',
            'https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/lightgallery.min.js',
            array(),
            '2.7.2',
            true
        );
        
        wp_register_script(
            'lightgallery-thumbnail',
            'https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/plugins/thumbnail/lg-thumbnail.min.js',
            array('lightgallery'),
            '2.7.2',
            true
        );
        
        wp_register_script(
            'lightgallery-zoom',
            'https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/plugins/zoom/lg-zoom.min.js',
            array('lightgallery'),
            '2.7.2',
            true
        );
        
        wp_register_script(
            'lightgallery-fullscreen',
            'https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/plugins/fullscreen/lg-fullscreen.min.js',
            array('lightgallery'),
            '2.7.2',
            true
        );
        
        wp_register_script(
            'lightgallery-hash',
            'https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/plugins/hash/lg-hash.min.js',
            array('lightgallery'),
            '2.7.2',
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
            array('jquery', 'shufflejs', 'tippy', 'lightgallery', 'lightgallery-thumbnail', 'lightgallery-zoom', 'lightgallery-fullscreen', 'lightgallery-hash'),
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
        
        // LightGallery
        wp_register_style(
            'lightgallery',
            'https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/css/lightgallery.min.css',
            array(),
            '2.7.2'
        );
        
        wp_register_style(
            'lightgallery-thumbnail',
            'https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/css/lg-thumbnail.min.css',
            array('lightgallery'),
            '2.7.2'
        );
        
        wp_register_style(
            'lightgallery-zoom',
            'https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/css/lg-zoom.min.css',
            array('lightgallery'),
            '2.7.2'
        );
        
        wp_register_style(
            'lightgallery-fullscreen',
            'https://cdn.jsdelivr.net/npm/lightgallery@2.7.2/css/lg-fullscreen.min.css',
            array('lightgallery'),
            '2.7.2'
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
            array('tippy', 'lightgallery', 'lightgallery-thumbnail', 'lightgallery-zoom', 'lightgallery-fullscreen'),
            DEVELOGIC_VERSION
        );
        
        // Google Fonts - Lato
        wp_register_style(
            'google-fonts-lato',
            'https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,300;0,400;0,700;0,900;1,400;1,700&display=swap',
            array(),
            null
        );
        
        // New layout styles
        wp_register_style(
            'develogic-new-layout',
            DEVELOGIC_PLUGIN_URL . 'assets/css/new-layout.css',
            array('google-fonts-lato', 'tippy', 'lightgallery', 'lightgallery-thumbnail', 'lightgallery-zoom', 'lightgallery-fullscreen'),
            DEVELOGIC_VERSION
        );
    }
    
    /**
     * Enqueue assets for A1 layout
     */
    public static function enqueue_a1_assets() {
        wp_enqueue_style('lightgallery');
        wp_enqueue_style('lightgallery-thumbnail');
        wp_enqueue_style('lightgallery-zoom');
        wp_enqueue_style('lightgallery-fullscreen');
        wp_enqueue_style('develogic-main');
        
        wp_enqueue_script('lightgallery');
        wp_enqueue_script('lightgallery-thumbnail');
        wp_enqueue_script('lightgallery-zoom');
        wp_enqueue_script('lightgallery-fullscreen');
        wp_enqueue_script('lightgallery-hash');
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

