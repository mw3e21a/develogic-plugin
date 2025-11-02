<?php
/**
 * Develogic Local Query Helper
 *
 * @package Develogic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Develogic_Local_Query
 * 
 * Helper class for querying locals from CPT instead of API
 */
class Develogic_Local_Query {
    
    /**
     * Get locals from CPT with filters
     *
     * @param array $filters Filters array
     * @return array Array of local data
     */
    public static function get_locals($filters = array()) {
        $args = array(
            'post_type' => 'develogic_local',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        );
        
        // Meta query
        $meta_query = array();
        
        if (!empty($filters['investmentId'])) {
            $tax_query = array(
                array(
                    'taxonomy' => 'develogic_investment',
                    'field' => 'term_id',
                    'terms' => absint($filters['investmentId']),
                ),
            );
            $args['tax_query'] = $tax_query;
        }
        
        if (!empty($filters['localTypeId'])) {
            if (!isset($args['tax_query'])) {
                $args['tax_query'] = array();
            }
            $args['tax_query'][] = array(
                'taxonomy' => 'develogic_local_type',
                'field' => 'term_id',
                'terms' => absint($filters['localTypeId']),
            );
        }
        
        if (!empty($filters['buildingId'])) {
            $meta_query[] = array(
                'key' => 'buildingId',
                'value' => absint($filters['buildingId']),
                'compare' => '=',
            );
        }
        
        if (!empty($filters['status'])) {
            $meta_query[] = array(
                'key' => 'status',
                'value' => $filters['status'],
                'compare' => '=',
            );
        }
        
        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }
        
        $query = new WP_Query($args);
        
        $locals = array();
        
        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $locals[] = self::post_to_local_array($post);
            }
        }
        
        return $locals;
    }
    
    /**
     * Get single local by ID
     *
     * @param int $local_id Develogic localId
     * @return array|null Local data or null
     */
    public static function get_local_by_id($local_id) {
        $query = new WP_Query(array(
            'post_type' => 'develogic_local',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'localId',
                    'value' => absint($local_id),
                    'compare' => '=',
                ),
            ),
            'posts_per_page' => 1,
        ));
        
        if ($query->have_posts()) {
            return self::post_to_local_array($query->posts[0]);
        }
        
        return null;
    }
    
    /**
     * Convert WP_Post to local array (like API response)
     *
     * @param WP_Post $post WordPress post
     * @return array Local data array
     */
    private static function post_to_local_array($post) {
        $local = array();
        
        // Get all meta
        $meta_keys = array(
            'localId', 'subdivisionId', 'subdivision', 'city', 'stageId', 'stage',
            'name', 'number', 'status', 'statusId', 'floor', 'rooms', 'mezzanineRooms',
            'area', 'areaBalcony', 'areaBalcony2', 'areaLoggia', 'areaTerrace',
            'areaGarden', 'areaGround', 'areaProduct', 'areaOther', 'areaUsable',
            'areaGroundFinal', 'areaProductFinal', 'areaBalconyFinal', 'areaBalcony2Final',
            'areaTerraceFinal', 'arreaLoggiaFinal', 'areaOtherFinal', 'areaUsableFinal', 'areaGardenFinal',
            'priceNet', 'priceGross', 'priceNetm2', 'priceGrossm2',
            'omnibusPriceGross', 'omnibusPriceNet', 'omnibusPackagePriceNet', 'omnibusPackagePriceGross',
            'omnibusPackagePriceNetm2', 'omnibusPackagePriceGrossm2',
            'omnibusPackagePriceUsableAreaNetm2', 'omnibusPackagePriceUsableAreaGrossm2',
            'omnibusPriceUsableAreaNetm2', 'omnibusPriceUsableAreaGrossm2',
            'averageOmnibusPriceGrossm2', 'averageOmnibusPriceUsableAreaGrossm2',
            'buildingId', 'building', 'maxDiscountGross', 'maxDiscountPercent',
            'plannedDateOfFinishing', 'localTypeId', 'localType', 'local_URL', 'externalNumber',
            'promoPriceNet', 'promoPriceGross', 'promoPriceNetm2', 'promoPriceGrossm2',
            'packagePriceGross', 'packagePriceNet', 'packagePriceGrossm2', 'PackagePriceNetm2',
            'packagePriceUsableAreaNetm2', 'packagePriceUsableAreaGrossm2',
            'packagePromoPriceNet', 'packagePromoPriceGross', 'packagePromoPriceNetm2', 'packagePromoPriceGrossm2',
            'packagePromoPriceUsableAreaNetm2', 'packagePromoPriceUsableAreaGrossm2',
            'worldDirections', 'omnibusPriceGrossm2', 'omnibusPriceNetm2',
            'averagePriceGrossm2', 'averagePromoPriceGrossm2',
            'offerPriceUsableAreaNetm2', 'offerPriceUsableAreaGrossm2',
            'promoPriceUsableAreaNetm2', 'promoPriceUsableAreaGrossm2',
        );
        
        foreach ($meta_keys as $key) {
            $value = get_post_meta($post->ID, $key, true);
            $local[$key] = $value !== '' ? $value : null;
        }
        
        // Decode JSON fields
        $projections = get_post_meta($post->ID, 'projections', true);
        if (!empty($projections)) {
            $local['projections'] = json_decode($projections, true);
        } else {
            $local['projections'] = array();
        }
        
        $misc_areas = get_post_meta($post->ID, 'miscAreas', true);
        if (!empty($misc_areas)) {
            $local['miscAreas'] = json_decode($misc_areas, true);
        } else {
            $local['miscAreas'] = array();
        }
        
        $attributes = get_post_meta($post->ID, 'attributes', true);
        if (!empty($attributes)) {
            $local['attributes'] = json_decode($attributes, true);
        } else {
            $local['attributes'] = array();
        }
        
        $packages = get_post_meta($post->ID, 'packages', true);
        if (!empty($packages)) {
            $local['packages'] = json_decode($packages, true);
        } else {
            $local['packages'] = array();
        }
        
        return $local;
    }
    
    /**
     * Get investments from taxonomy
     *
     * @return array Array of investments
     */
    public static function get_investments() {
        $terms = get_terms(array(
            'taxonomy' => 'develogic_investment',
            'hide_empty' => false,
        ));
        
        if (is_wp_error($terms)) {
            return array();
        }
        
        $investments = array();
        foreach ($terms as $term) {
            $investment_id = get_term_meta($term->term_id, 'investment_id', true);
            $investments[] = array(
                'ID' => $investment_id ? $investment_id : $term->term_id,
                'Name' => $term->name,
                'term_id' => $term->term_id,
            );
        }
        
        return $investments;
    }
    
    /**
     * Get local types from taxonomy
     *
     * @return array Array of local types
     */
    public static function get_local_types() {
        $terms = get_terms(array(
            'taxonomy' => 'develogic_local_type',
            'hide_empty' => false,
        ));
        
        if (is_wp_error($terms)) {
            return array();
        }
        
        $types = array();
        foreach ($terms as $term) {
            $type_id = get_term_meta($term->term_id, 'local_type_id', true);
            $types[] = array(
                'ID' => $type_id ? $type_id : $term->term_id,
                'Name' => $term->name,
                'term_id' => $term->term_id,
            );
        }
        
        return $types;
    }
    
    /**
     * Get buildings from taxonomy and locals
     *
     * @return array Array of buildings
     */
    public static function get_buildings_for_settings() {
        // Get buildings from taxonomy (synced)
        $terms = get_terms(array(
            'taxonomy' => 'develogic_building',
            'hide_empty' => false,
        ));
        
        if (is_wp_error($terms)) {
            return array();
        }
        
        // Get all locals to extract buildingId mapping
        $locals = self::get_locals();
        
        // Build mapping: building name -> buildingId
        $building_id_map = array();
        foreach ($locals as $local) {
            if (!empty($local['building']) && !empty($local['buildingId'])) {
                $building_id_map[$local['building']] = $local['buildingId'];
            }
        }
        
        $buildings = array();
        foreach ($terms as $term) {
            $building_id = isset($building_id_map[$term->name]) ? $building_id_map[$term->name] : 0;
            $buildings[] = array(
                'ID' => $building_id,
                'Name' => $term->name,
                'term_id' => $term->term_id,
            );
        }
        
        // Sort by name
        usort($buildings, function($a, $b) {
            return strcmp($a['Name'], $b['Name']);
        });
        
        return $buildings;
    }
}

