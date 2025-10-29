<?php
/**
 * Develogic Sync Manager
 *
 * @package Develogic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Develogic_Sync
 */
class Develogic_Sync {
    
    /**
     * Sync locals from API to CPT
     *
     * @return array Result with stats
     */
    public function sync_locals() {
        $start_time = microtime(true);
        $stats = array(
            'success' => false,
            'added' => 0,
            'updated' => 0,
            'errors' => 0,
            'total' => 0,
            'time' => 0,
            'message' => '',
        );
        
        // Fetch from API
        $locals = develogic()->api_client->get_locals();
        
        if (is_wp_error($locals)) {
            $stats['message'] = $locals->get_error_message();
            $this->log_sync('error', $stats['message']);
            return $stats;
        }
        
        if (empty($locals) || !is_array($locals)) {
            $stats['message'] = __('Brak danych z API', 'develogic');
            $this->log_sync('warning', $stats['message']);
            return $stats;
        }
        
        $stats['total'] = count($locals);
        
        // Sync each local
        foreach ($locals as $local_data) {
            $result = $this->sync_single_local($local_data);
            
            if ($result === 'added') {
                $stats['added']++;
            } elseif ($result === 'updated') {
                $stats['updated']++;
            } else {
                $stats['errors']++;
            }
        }
        
        // Sync investments and local types
        $this->sync_investments();
        $this->sync_local_types();
        
        $stats['time'] = round(microtime(true) - $start_time, 2);
        $stats['success'] = true;
        $stats['message'] = sprintf(
            __('Synchronizacja zakończona: %d dodanych, %d zaktualizowanych, %d błędów w %s sek', 'develogic'),
            $stats['added'],
            $stats['updated'],
            $stats['errors'],
            $stats['time']
        );
        
        // Save last sync info
        update_option('develogic_last_sync', array(
            'time' => current_time('mysql'),
            'stats' => $stats,
        ));
        
        $this->log_sync('success', $stats['message']);
        
        return $stats;
    }
    
    /**
     * Sync single local
     *
     * @param array $local_data Local data from API
     * @return string 'added', 'updated', or 'error'
     */
    private function sync_single_local($local_data) {
        if (empty($local_data['localId'])) {
            return 'error';
        }
        
        $local_id = absint($local_data['localId']);
        
        // Check if local already exists (by meta localId)
        $existing = $this->get_local_by_local_id($local_id);
        
        $post_data = array(
            'post_type' => 'develogic_local',
            'post_title' => !empty($local_data['number']) ? $local_data['number'] : 'Lokal ' . $local_id,
            'post_status' => 'publish',
            'post_content' => !empty($local_data['name']) ? $local_data['name'] : '',
        );
        
        if ($existing) {
            // Update
            $post_data['ID'] = $existing->ID;
            $post_id = wp_update_post($post_data, true);
            $result = 'updated';
        } else {
            // Insert
            $post_id = wp_insert_post($post_data, true);
            $result = 'added';
        }
        
        if (is_wp_error($post_id)) {
            return 'error';
        }
        
        // Save all data as meta
        $this->save_local_meta($post_id, $local_data);
        
        // Assign taxonomies
        $this->assign_taxonomies($post_id, $local_data);
        
        return $result;
    }
    
    /**
     * Get local by localId meta
     *
     * @param int $local_id Develogic localId
     * @return WP_Post|null
     */
    private function get_local_by_local_id($local_id) {
        $query = new WP_Query(array(
            'post_type' => 'develogic_local',
            'meta_query' => array(
                array(
                    'key' => 'localId',
                    'value' => $local_id,
                    'compare' => '=',
                ),
            ),
            'posts_per_page' => 1,
            'post_status' => 'any',
        ));
        
        return $query->have_posts() ? $query->posts[0] : null;
    }
    
    /**
     * Save local meta data
     *
     * @param int $post_id WordPress Post ID
     * @param array $local_data Local data from API
     */
    private function save_local_meta($post_id, $local_data) {
        // Save original localId
        update_post_meta($post_id, 'localId', $local_data['localId']);
        
        // Save all fields as meta
        $fields = array(
            'subdivisionId', 'subdivision', 'city', 'stageId', 'stage',
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
        
        foreach ($fields as $field) {
            if (isset($local_data[$field])) {
                update_post_meta($post_id, $field, $local_data[$field]);
            }
        }
        
        // Download and save projections with WordPress media library
        if (!empty($local_data['projections'])) {
            $processed_projections = $this->process_projections($post_id, $local_data['projections'], $local_data['number']);
            update_post_meta($post_id, 'projections', json_encode($processed_projections));
        }
        
        if (!empty($local_data['miscAreas'])) {
            update_post_meta($post_id, 'miscAreas', json_encode($local_data['miscAreas']));
        }
        
        if (!empty($local_data['attributes'])) {
            update_post_meta($post_id, 'attributes', json_encode($local_data['attributes']));
        }
        
        if (!empty($local_data['packages'])) {
            update_post_meta($post_id, 'packages', json_encode($local_data['packages']));
        }
    }
    
    /**
     * Process projections - download images and upload to WordPress media library
     *
     * @param int $post_id WordPress Post ID
     * @param array $projections Array of projection data from API
     * @param string $local_number Local number for naming
     * @return array Processed projections with WordPress attachment data
     */
    private function process_projections($post_id, $projections, $local_number) {
        if (empty($projections) || !is_array($projections)) {
            return array();
        }
        
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $processed = array();
        
        foreach ($projections as $index => $projection) {
            // Extract projection ID from URI (e.g., .../projection/5277)
            if (empty($projection['uri'])) {
                continue;
            }
            
            // Parse projection ID from URI
            $projection_id = null;
            if (preg_match('/\/projection\/(\d+)/', $projection['uri'], $matches)) {
                $projection_id = absint($matches[1]);
            }
            
            if (empty($projection_id)) {
                $this->log_sync('warning', sprintf(
                    'Nie można wyodrębnić ID projekcji z URI: %s',
                    $projection['uri']
                ));
                continue;
            }
            
            // Store the extracted ID in the projection data
            $projection['id'] = $projection_id;
            
            // Check if we already have this projection uploaded
            $existing_attachment_id = $this->get_projection_attachment_id($post_id, $projection_id);
            
            if ($existing_attachment_id) {
                // Use existing attachment
                $projection['attachment_id'] = $existing_attachment_id;
                $projection['wordpress_url'] = wp_get_attachment_url($existing_attachment_id);
                $projection['thumbnail_url'] = wp_get_attachment_image_url($existing_attachment_id, 'medium');
                $projection['large_url'] = wp_get_attachment_image_url($existing_attachment_id, 'large');
                $processed[] = $projection;
                continue;
            }
            
            // Download image from API
            $image_data = develogic()->api_client->download_projection_image($projection_id);
            
            if (is_wp_error($image_data)) {
                $this->log_sync('warning', sprintf(
                    'Nie udało się pobrać projekcji %d dla lokalu %s: %s',
                    $projection_id,
                    $local_number,
                    $image_data->get_error_message()
                ));
                $processed[] = $projection;
                continue;
            }
            
            // Determine file extension from content type or use default
            $file_extension = 'jpg';
            if (!empty($projection['type'])) {
                $type_lower = strtolower($projection['type']);
                if (strpos($type_lower, 'png') !== false) {
                    $file_extension = 'png';
                }
            }
            
            // Create filename
            $filename = sprintf(
                'lokal-%s-projekcja-%d-%s.%s',
                sanitize_title($local_number),
                $projection_id,
                sanitize_title($projection['type']),
                $file_extension
            );
            
            // Save to temporary file
            $upload_dir = wp_upload_dir();
            $temp_file = $upload_dir['path'] . '/' . $filename;
            
            if (file_put_contents($temp_file, $image_data) === false) {
                $this->log_sync('warning', sprintf(
                    'Nie udało się zapisać pliku tymczasowego dla projekcji %d',
                    $projection_id
                ));
                $processed[] = $projection;
                continue;
            }
            
            // Prepare attachment data
            $file_array = array(
                'name' => $filename,
                'tmp_name' => $temp_file,
            );
            
            $attachment_id = media_handle_sideload(
                $file_array,
                $post_id,
                sprintf(
                    'Lokal %s - %s',
                    $local_number,
                    $projection['type']
                )
            );
            
            // Clean up temp file
            if (file_exists($temp_file)) {
                @unlink($temp_file);
            }
            
            if (is_wp_error($attachment_id)) {
                $this->log_sync('warning', sprintf(
                    'Nie udało się utworzyć attachmentu dla projekcji %d: %s',
                    $projection_id,
                    $attachment_id->get_error_message()
                ));
                $processed[] = $projection;
                continue;
            }
            
            // Save projection metadata to attachment
            update_post_meta($attachment_id, 'develogic_projection_id', $projection_id);
            update_post_meta($attachment_id, 'develogic_local_post_id', $post_id);
            update_post_meta($attachment_id, 'develogic_projection_type', $projection['type']);
            
            // Add WordPress URLs to projection data
            $projection['attachment_id'] = $attachment_id;
            $projection['wordpress_url'] = wp_get_attachment_url($attachment_id);
            $projection['thumbnail_url'] = wp_get_attachment_image_url($attachment_id, 'medium');
            $projection['large_url'] = wp_get_attachment_image_url($attachment_id, 'large');
            
            $processed[] = $projection;
        }
        
        return $processed;
    }
    
    /**
     * Get existing projection attachment ID
     *
     * @param int $post_id WordPress Post ID
     * @param int $projection_id Develogic projection ID
     * @return int|null Attachment ID or null
     */
    private function get_projection_attachment_id($post_id, $projection_id) {
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'post_parent' => $post_id,
            'meta_query' => array(
                array(
                    'key' => 'develogic_projection_id',
                    'value' => $projection_id,
                    'compare' => '=',
                ),
            ),
            'posts_per_page' => 1,
            'fields' => 'ids',
        ));
        
        return !empty($attachments) ? $attachments[0] : null;
    }
    
    /**
     * Assign taxonomies to local
     *
     * @param int $post_id WordPress Post ID
     * @param array $local_data Local data from API
     */
    private function assign_taxonomies($post_id, $local_data) {
        // Investment
        if (!empty($local_data['subdivision'])) {
            wp_set_object_terms($post_id, $local_data['subdivision'], 'develogic_investment');
        }
        
        // Local type
        if (!empty($local_data['localType'])) {
            wp_set_object_terms($post_id, $local_data['localType'], 'develogic_local_type');
        }
        
        // Building
        if (!empty($local_data['building'])) {
            wp_set_object_terms($post_id, $local_data['building'], 'develogic_building');
        }
        
        // Status
        if (!empty($local_data['status'])) {
            wp_set_object_terms($post_id, $local_data['status'], 'develogic_status');
        }
    }
    
    /**
     * Sync investments taxonomy
     */
    private function sync_investments() {
        $investments = develogic()->api_client->get_investments();
        
        if (is_wp_error($investments) || empty($investments)) {
            return;
        }
        
        foreach ($investments as $investment) {
            if (empty($investment['Name'])) {
                continue;
            }
            
            $term = term_exists($investment['Name'], 'develogic_investment');
            
            if (!$term) {
                $term = wp_insert_term($investment['Name'], 'develogic_investment');
            }
            
            if (!is_wp_error($term) && isset($term['term_id'])) {
                update_term_meta($term['term_id'], 'investment_id', $investment['ID']);
            }
        }
    }
    
    /**
     * Sync local types taxonomy
     */
    private function sync_local_types() {
        $local_types = develogic()->api_client->get_local_types();
        
        if (is_wp_error($local_types) || empty($local_types)) {
            return;
        }
        
        foreach ($local_types as $type) {
            if (empty($type['Name'])) {
                continue;
            }
            
            $term = term_exists($type['Name'], 'develogic_local_type');
            
            if (!$term) {
                $term = wp_insert_term($type['Name'], 'develogic_local_type');
            }
            
            if (!is_wp_error($term) && isset($term['term_id'])) {
                update_term_meta($term['term_id'], 'local_type_id', $type['ID']);
            }
        }
    }
    
    /**
     * Log sync event
     *
     * @param string $level Level: success, error, warning
     * @param string $message Message
     */
    private function log_sync($level, $message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[Develogic Sync] [%s] %s', strtoupper($level), $message));
        }
        
        // Store in option for admin display
        $log = get_option('develogic_sync_log', array());
        
        $log[] = array(
            'time' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
        );
        
        // Keep only last 50 entries
        $log = array_slice($log, -50);
        
        update_option('develogic_sync_log', $log);
    }
}

