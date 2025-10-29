<?php
/**
 * Develogic Sync REST Endpoint
 *
 * @package Develogic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Develogic_Sync_Endpoint
 */
class Develogic_Sync_Endpoint {
    
    /**
     * API namespace
     */
    const NAMESPACE = 'develogic/v1';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Sync endpoint for external cron
        register_rest_route(self::NAMESPACE, '/sync', array(
            'methods' => 'POST',
            'callback' => array($this, 'trigger_sync'),
            'permission_callback' => array($this, 'check_secret_key'),
        ));
        
        // Sync status endpoint (GET)
        register_rest_route(self::NAMESPACE, '/sync/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_sync_status'),
            'permission_callback' => array($this, 'check_secret_key'),
        ));
    }
    
    /**
     * Check secret key for authentication
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function check_secret_key($request) {
        $secret_key = develogic()->get_setting('sync_secret_key');
        
        if (empty($secret_key)) {
            return new WP_Error(
                'no_secret_key',
                __('Secret key nie został skonfigurowany w ustawieniach wtyczki', 'develogic'),
                array('status' => 500)
            );
        }
        
        // Check Authorization header
        $auth_header = $request->get_header('Authorization');
        
        if (empty($auth_header)) {
            // Check query parameter as fallback
            $provided_key = $request->get_param('secret_key');
        } else {
            // Extract from "Bearer {key}" or just "{key}"
            $provided_key = str_replace('Bearer ', '', $auth_header);
        }
        
        if (empty($provided_key)) {
            return new WP_Error(
                'missing_auth',
                __('Brak secret key w żądaniu. Użyj headera Authorization: Bearer {key} lub parametru ?secret_key={key}', 'develogic'),
                array('status' => 401)
            );
        }
        
        if (!hash_equals($secret_key, $provided_key)) {
            return new WP_Error(
                'invalid_auth',
                __('Nieprawidłowy secret key', 'develogic'),
                array('status' => 403)
            );
        }
        
        return true;
    }
    
    /**
     * Trigger sync endpoint
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function trigger_sync($request) {
        // Check if sync is not already running
        $lock = get_transient('develogic_sync_lock');
        
        if ($lock) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Synchronizacja jest już w trakcie. Spróbuj ponownie za chwilę.', 'develogic'),
            ), 409);
        }
        
        // Set lock (5 minutes)
        set_transient('develogic_sync_lock', true, 300);
        
        // Run sync
        $sync = new Develogic_Sync();
        $result = $sync->sync_locals();
        
        // Release lock
        delete_transient('develogic_sync_lock');
        
        $status_code = $result['success'] ? 200 : 500;
        
        return new WP_REST_Response($result, $status_code);
    }
    
    /**
     * Get sync status endpoint
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_sync_status($request) {
        $last_sync = get_option('develogic_last_sync', array());
        $sync_log = get_option('develogic_sync_log', array());
        
        // Count locals in database
        $locals_count = wp_count_posts('develogic_local');
        
        return new WP_REST_Response(array(
            'last_sync' => $last_sync,
            'locals_count' => $locals_count->publish,
            'recent_log' => array_slice($sync_log, -10), // Last 10 entries
            'is_running' => (bool) get_transient('develogic_sync_lock'),
        ), 200);
    }
}

