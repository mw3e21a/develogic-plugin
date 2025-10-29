<?php
/**
 * Plugin Name: Develogic Integration
 * Plugin URI: https://github.com/yourusername/develogic-wp-plugin
 * Description: Integracja z API Develogic - wyświetlanie ofert mieszkań, filtrowanie, sortowanie, galerie i więcej
 * Version: 2.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: develogic
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('DEVELOGIC_VERSION', '2.0.0');
define('DEVELOGIC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DEVELOGIC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DEVELOGIC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Develogic Integration Class
 */
final class Develogic_Integration {
    
    /**
     * The single instance of the class
     *
     * @var Develogic_Integration
     */
    private static $instance = null;
    
    /**
     * API Client instance (lazy loaded)
     *
     * @var Develogic_API_Client|null
     */
    private $api_client = null;
    
    /**
     * Main Develogic_Integration Instance
     *
     * Ensures only one instance of Develogic_Integration is loaded or can be loaded.
     *
     * @return Develogic_Integration - Main instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Develogic_Integration Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->includes();
        // Initialize components immediately to support REST API
        add_action('plugins_loaded', array($this, 'init_components'), 1);
        // But also call it directly in case we're in REST request before plugins_loaded
        if (defined('REST_REQUEST') && REST_REQUEST || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false)) {
            $this->init_components();
        }
    }
    
    /**
     * Hook into actions and filters
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'), 0);
    }
    
    /**
     * Include required core files
     */
    private function includes() {
        // Core
        require_once DEVELOGIC_PLUGIN_DIR . 'includes/class-api-client.php';
        require_once DEVELOGIC_PLUGIN_DIR . 'includes/class-data-formatter.php';
        require_once DEVELOGIC_PLUGIN_DIR . 'includes/class-filter-sort.php';
        require_once DEVELOGIC_PLUGIN_DIR . 'includes/class-post-type.php';
        require_once DEVELOGIC_PLUGIN_DIR . 'includes/class-sync.php';
        require_once DEVELOGIC_PLUGIN_DIR . 'includes/class-sync-endpoint.php';
        require_once DEVELOGIC_PLUGIN_DIR . 'includes/class-local-query.php';
        
        // Admin
        if (is_admin()) {
            require_once DEVELOGIC_PLUGIN_DIR . 'admin/class-admin-settings.php';
            require_once DEVELOGIC_PLUGIN_DIR . 'admin/class-admin-sync.php';
        }
        
        // Public
        require_once DEVELOGIC_PLUGIN_DIR . 'public/class-shortcodes.php';
        require_once DEVELOGIC_PLUGIN_DIR . 'public/class-rest-api.php';
        require_once DEVELOGIC_PLUGIN_DIR . 'public/class-assets.php';
    }
    
    /**
     * Initialize components
     */
    public function init_components() {
        // Only initialize once
        static $initialized = false;
        if ($initialized) {
            return;
        }
        $initialized = true;
        
        // Initialize CPT and Taxonomies
        new Develogic_Post_Type();
        
        // Initialize Sync Endpoint
        new Develogic_Sync_Endpoint();
        
        // Initialize admin components
        if (is_admin()) {
            new Develogic_Admin_Settings();
            new Develogic_Admin_Sync();
        }
        
        // Initialize public components  
        new Develogic_Shortcodes();
        new Develogic_REST_API();
        new Develogic_Assets();
    }
    
    /**
     * Init when WordPress Initialises
     */
    public function init() {
        // Before init action
        do_action('develogic_before_init');
        
        // Set up localisation
        $this->load_textdomain();
        
        // After init action
        do_action('develogic_init');
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('develogic', false, dirname(DEVELOGIC_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $default_options = array(
            'api_base_url' => '',
            'api_key' => '',
            'api_timeout' => 30,
            'sync_secret_key' => wp_generate_password(32, false), // Generate random secret key
            'developer_name' => get_bloginfo('name'),
            'default_sort_by' => 'priceGrossm2',
            'default_sort_dir' => 'asc',
            'price_m2_source' => 'priceGrossm2',
            'visible_statuses' => array('Wolny', 'Rezerwacja'),
            'show_print' => true,
            'show_favorite' => true,
            'favorite_persist' => 'localstorage',
            'pdf_source' => 'off',
            'pdf_pattern' => '',
        );
        
        add_option('develogic_settings', $default_options);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Get plugin settings
     *
     * @param string $key Optional. Specific setting key
     * @param mixed $default Optional. Default value if setting not found
     * @return mixed
     */
    public function get_setting($key = null, $default = null) {
        $settings = get_option('develogic_settings', array());
        
        if ($key === null) {
            return $settings;
        }
        
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Magic getter for lazy loading components
     *
     * @param string $name Property name
     * @return mixed
     */
    public function __get($name) {
        if ($name === 'api_client') {
            if (!$this->api_client) {
                $this->api_client = new Develogic_API_Client();
            }
            return $this->api_client;
        }
        
        return null;
    }
}

/**
 * Main instance of Develogic_Integration
 *
 * @return Develogic_Integration
 */
function develogic() {
    return Develogic_Integration::instance();
}

// Initialize the plugin
develogic();

