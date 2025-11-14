<?php
/**
 * Uninstall Develogic Integration Plugin
 *
 * This file is executed when the plugin is deleted through WordPress admin.
 * It removes all plugin data including:
 * - Custom Post Types (develogic_local)
 * - Custom Taxonomies (terms and metadata)
 * - Options and settings
 * - Transients
 * - Media attachments (projections)
 *
 * @package Develogic
 */

// Exit if uninstall not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up all plugin data
 */
function develogic_uninstall_cleanup() {
    global $wpdb;
    
    // Remove scheduled cron jobs
    $timestamp = wp_next_scheduled('develogic_sync_cron');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'develogic_sync_cron');
    }
    
    // Clear all scheduled instances of the cron (in case there are multiple)
    wp_clear_scheduled_hook('develogic_sync_cron');
    
    // Delete all develogic_local posts
    $posts = get_posts(array(
        'post_type' => 'develogic_local',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ));
    
    foreach ($posts as $post_id) {
        // Delete all attachments (projections) for each local
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'post_parent' => $post_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
        ));
        
        foreach ($attachments as $attachment_id) {
            wp_delete_attachment($attachment_id, true);
        }
        
        // Delete the post itself (with meta)
        wp_delete_post($post_id, true);
    }
    
    // Delete all attachments with develogic meta (in case some weren't parented)
    $attachment_ids = $wpdb->get_col(
        "SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
         WHERE meta_key IN ('develogic_projection_id', 'develogic_local_post_id', 'develogic_projection_type')"
    );
    
    if (!empty($attachment_ids)) {
        foreach ($attachment_ids as $attachment_id) {
            wp_delete_attachment($attachment_id, true);
        }
    }
    
    // Delete all taxonomy terms and relationships using direct SQL
    // This ensures cleanup even if taxonomies aren't registered during uninstall
    $taxonomies = array(
        'develogic_investment',
        'develogic_local_type',
        'develogic_building',
        'develogic_status',
    );
    
    foreach ($taxonomies as $taxonomy) {
        // Get all term_taxonomy_ids and term_ids for this taxonomy
        $term_data = $wpdb->get_results($wpdb->prepare(
            "SELECT term_taxonomy_id, term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
            $taxonomy
        ), ARRAY_A);
        
        if (!empty($term_data)) {
            $term_taxonomy_ids = array_column($term_data, 'term_taxonomy_id');
            $term_ids = array_unique(array_column($term_data, 'term_id'));
            
            // Delete term relationships
            if (!empty($term_taxonomy_ids)) {
                $placeholders = implode(',', array_map('absint', $term_taxonomy_ids));
                $wpdb->query(
                    "DELETE FROM {$wpdb->term_relationships} 
                     WHERE term_taxonomy_id IN ({$placeholders})"
                );
            }
            
            // Delete term meta
            if (!empty($term_ids)) {
                $placeholders = implode(',', array_map('absint', $term_ids));
                $wpdb->query(
                    "DELETE FROM {$wpdb->termmeta} 
                     WHERE term_id IN ({$placeholders})"
                );
            }
        }
        
        // Delete term taxonomy entries
        $wpdb->delete(
            $wpdb->term_taxonomy,
            array('taxonomy' => $taxonomy),
            array('%s')
        );
    }
    
    // Delete orphaned terms (terms that don't belong to any taxonomy)
    $wpdb->query(
        "DELETE t FROM {$wpdb->terms} t
         LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
         WHERE tt.term_id IS NULL"
    );
    
    // Delete all options
    $options = array(
        'develogic_settings',
        'develogic_last_sync',
        'develogic_sync_log',
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // Delete all transients
    $transients = array(
        'develogic_sync_lock',
        'develogic_last_api_error',
    );
    
    foreach ($transients as $transient) {
        delete_transient($transient);
    }
    
    // Delete all transients with develogic prefix (just in case)
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_develogic_%' 
            OR option_name LIKE '_transient_timeout_develogic_%'"
    );
    
    // Delete all post meta with develogic prefix
    $wpdb->query(
        "DELETE FROM {$wpdb->postmeta} 
         WHERE meta_key LIKE 'develogic_%'"
    );
    
    // Delete all post meta for local data (including localId and all other fields)
    $wpdb->query(
        "DELETE FROM {$wpdb->postmeta} 
         WHERE meta_key IN (
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
             'averagePriceGrossm2', 'averagePromoPriceGrossm2'
         )"
    );
    
    // Delete all term meta with develogic-related keys
    $wpdb->query(
        "DELETE FROM {$wpdb->termmeta} 
         WHERE meta_key IN ('investment_id', 'local_type_id')"
    );
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Run cleanup
develogic_uninstall_cleanup();

