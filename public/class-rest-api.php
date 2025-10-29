<?php
/**
 * Develogic REST API
 *
 * @package Develogic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Develogic_REST_API
 */
class Develogic_REST_API {
    
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
        // Get filtered and sorted offers
        register_rest_route(self::NAMESPACE, '/offers', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_offers'),
            'permission_callback' => '__return_true',
            'args' => $this->get_offers_args(),
        ));
        
        // Get single local
        register_rest_route(self::NAMESPACE, '/local/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_local'),
            'permission_callback' => '__return_true',
        ));
        
        // Get price history
        register_rest_route(self::NAMESPACE, '/price-history/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_price_history'),
            'permission_callback' => '__return_true',
        ));
        
        // Get investments
        register_rest_route(self::NAMESPACE, '/investments', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_investments'),
            'permission_callback' => '__return_true',
        ));
        
        // Get local types
        register_rest_route(self::NAMESPACE, '/local-types', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_local_types'),
            'permission_callback' => '__return_true',
        ));
        
        // Get buildings
        register_rest_route(self::NAMESPACE, '/buildings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_buildings'),
            'permission_callback' => '__return_true',
            'args' => array(
                'investment_id' => array(
                    'type' => 'integer',
                    'required' => false,
                ),
            ),
        ));
    }
    
    /**
     * Get offers args
     */
    private function get_offers_args() {
        return array(
            'investment_id' => array('type' => 'integer'),
            'local_type_id' => array('type' => 'integer'),
            'building_id' => array('type' => 'integer'),
            'status' => array('type' => 'string'),
            'city' => array('type' => 'string'),
            'rooms' => array('type' => 'string'),
            'floor' => array('type' => 'string'),
            'min_area' => array('type' => 'number'),
            'max_area' => array('type' => 'number'),
            'min_price_gross' => array('type' => 'number'),
            'max_price_gross' => array('type' => 'number'),
            'min_price_m2' => array('type' => 'number'),
            'max_price_m2' => array('type' => 'number'),
            'world_dir' => array('type' => 'string'),
            'search' => array('type' => 'string'),
            'sort_by' => array('type' => 'string', 'default' => 'priceGrossm2'),
            'sort_dir' => array('type' => 'string', 'default' => 'asc'),
            'page' => array('type' => 'integer', 'default' => 1),
            'per_page' => array('type' => 'integer', 'default' => 12),
        );
    }
    
    /**
     * Get offers endpoint
     */
    public function get_offers($request) {
        $filters = array(
            'investment_id' => $request->get_param('investment_id'),
            'local_type_id' => $request->get_param('local_type_id'),
            'building_id' => $request->get_param('building_id'),
            'status' => $request->get_param('status'),
            'city' => $request->get_param('city'),
            'rooms' => $request->get_param('rooms'),
            'floor' => $request->get_param('floor'),
            'min_area' => $request->get_param('min_area'),
            'max_area' => $request->get_param('max_area'),
            'min_price_gross' => $request->get_param('min_price_gross'),
            'max_price_gross' => $request->get_param('max_price_gross'),
            'min_price_m2' => $request->get_param('min_price_m2'),
            'max_price_m2' => $request->get_param('max_price_m2'),
            'world_dir' => $request->get_param('world_dir'),
            'search' => $request->get_param('search'),
        );
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });
        
        // Get data from CPT
        $cpt_filters = array();
        if (!empty($filters['investment_id'])) {
            $cpt_filters['investmentId'] = $filters['investment_id'];
        }
        if (!empty($filters['local_type_id'])) {
            $cpt_filters['localTypeId'] = $filters['local_type_id'];
        }
        
        $locals = Develogic_Local_Query::get_locals($cpt_filters);
        
        // Apply additional filters
        $locals = Develogic_Filter_Sort::filter_locals($locals, $filters);
        
        // Sort
        $sort_by = $request->get_param('sort_by') ?: 'priceGrossm2';
        $sort_dir = $request->get_param('sort_dir') ?: 'asc';
        $locals = Develogic_Filter_Sort::sort_locals($locals, $sort_by, $sort_dir);
        
        // Pagination
        $page = max(1, $request->get_param('page') ?: 1);
        $per_page = max(1, min(100, $request->get_param('per_page') ?: 12));
        $total = count($locals);
        $total_pages = ceil($total / $per_page);
        $offset = ($page - 1) * $per_page;
        
        $locals = array_slice($locals, $offset, $per_page);
        
        // Get status counts for all filtered results (before pagination)
        $all_filtered = Develogic_Filter_Sort::filter_locals(
            Develogic_Local_Query::get_locals($cpt_filters),
            $filters
        );
        $status_counts = Develogic_Filter_Sort::count_by_status($all_filtered);
        
        return new WP_REST_Response(array(
            'locals' => array_values($locals),
            'pagination' => array(
                'total' => $total,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'per_page' => $per_page,
            ),
            'status_counts' => $status_counts,
        ), 200);
    }
    
    /**
     * Get single local endpoint
     */
    public function get_local($request) {
        $local_id = absint($request->get_param('id'));
        
        if (empty($local_id)) {
            return new WP_Error('invalid_id', __('Nieprawidłowe ID lokalu', 'develogic'), array('status' => 400));
        }
        
        // Get local from CPT
        $local = Develogic_Local_Query::get_local_by_id($local_id);
        
        if (!$local) {
            return new WP_Error('not_found', __('Lokal nie został znaleziony', 'develogic'), array('status' => 404));
        }
        
        return new WP_REST_Response($local, 200);
    }
    
    /**
     * Get price history endpoint
     */
    public function get_price_history($request) {
        $local_id = absint($request->get_param('id'));
        
        if (empty($local_id)) {
            return new WP_Error('invalid_id', __('Nieprawidłowe ID lokalu', 'develogic'), array('status' => 400));
        }
        
        // Price history always from API (real-time)
        $history = develogic()->api_client->get_price_history($local_id);
        
        if (is_wp_error($history)) {
            return $history;
        }
        
        return new WP_REST_Response($history, 200);
    }
    
    /**
     * Get investments endpoint
     */
    public function get_investments($request) {
        $investments = Develogic_Local_Query::get_investments();
        
        return new WP_REST_Response($investments, 200);
    }
    
    /**
     * Get local types endpoint
     */
    public function get_local_types($request) {
        $local_types = Develogic_Local_Query::get_local_types();
        
        return new WP_REST_Response($local_types, 200);
    }
    
    /**
     * Get buildings endpoint
     */
    public function get_buildings($request) {
        $investment_id = $request->get_param('investment_id');
        
        $filters = array();
        if (!empty($investment_id)) {
            $filters['investmentId'] = $investment_id;
        }
        
        $locals = Develogic_Local_Query::get_locals($filters);
        $buildings = Develogic_Filter_Sort::get_buildings($locals);
        
        return new WP_REST_Response($buildings, 200);
    }
}

