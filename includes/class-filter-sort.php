<?php
/**
 * Develogic Filter and Sort Handler
 *
 * @package Develogic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Develogic_Filter_Sort
 */
class Develogic_Filter_Sort {
    
    /**
     * Filter locals based on criteria
     *
     * @param array $locals Array of locals
     * @param array $filters Filter criteria
     * @return array Filtered locals
     */
    public static function filter_locals($locals, $filters) {
        if (empty($locals) || !is_array($locals)) {
            return array();
        }
        
        return array_filter($locals, function($local) use ($filters) {
            // Investment ID (already filtered by API)
            if (!empty($filters['investment_id']) && $local['subdivisionId'] != $filters['investment_id']) {
                return false;
            }
            
            // Local Type ID (already filtered by API)
            if (!empty($filters['local_type_id']) && $local['localTypeId'] != $filters['local_type_id']) {
                return false;
            }
            
            // Building ID
            if (!empty($filters['building_id']) && $local['buildingId'] != $filters['building_id']) {
                return false;
            }
            
            // Status
            if (!empty($filters['status'])) {
                $status_filter = is_array($filters['status']) ? $filters['status'] : array($filters['status']);
                if (!in_array($local['status'], $status_filter)) {
                    return false;
                }
            }
            
            // City
            if (!empty($filters['city']) && strcasecmp($local['city'], $filters['city']) !== 0) {
                return false;
            }
            
            // Rooms
            if (!empty($filters['rooms'])) {
                if (is_array($filters['rooms'])) {
                    if (!in_array($local['rooms'], $filters['rooms'])) {
                        return false;
                    }
                } else {
                    if ($local['rooms'] != $filters['rooms']) {
                        return false;
                    }
                }
            }
            
            // Floor
            if (isset($filters['floor']) && $filters['floor'] !== '') {
                if (is_array($filters['floor'])) {
                    if (!in_array($local['floor'], $filters['floor'])) {
                        return false;
                    }
                } else {
                    if ($local['floor'] != $filters['floor']) {
                        return false;
                    }
                }
            }
            
            // Area range
            if (!empty($filters['min_area']) && $local['area'] < floatval($filters['min_area'])) {
                return false;
            }
            
            if (!empty($filters['max_area']) && $local['area'] > floatval($filters['max_area'])) {
                return false;
            }
            
            // Price range (gross)
            if (!empty($filters['min_price_gross']) && $local['priceGross'] < floatval($filters['min_price_gross'])) {
                return false;
            }
            
            if (!empty($filters['max_price_gross']) && $local['priceGross'] > floatval($filters['max_price_gross'])) {
                return false;
            }
            
            // Price per m2 range
            if (!empty($filters['min_price_m2']) && $local['priceGrossm2'] < floatval($filters['min_price_m2'])) {
                return false;
            }
            
            if (!empty($filters['max_price_m2']) && $local['priceGrossm2'] > floatval($filters['max_price_m2'])) {
                return false;
            }
            
            // World directions
            if (!empty($filters['world_dir'])) {
                $required_dirs = is_array($filters['world_dir']) 
                    ? $filters['world_dir'] 
                    : array_map('trim', explode(',', $filters['world_dir']));
                
                $local_dirs = Develogic_Data_Formatter::parse_world_directions($local['worldDirections']);
                
                if (empty(array_intersect($required_dirs, $local_dirs))) {
                    return false;
                }
            }
            
            // Search (number, name)
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $number = strtolower($local['number']);
                $name = strtolower($local['name']);
                
                if (strpos($number, $search) === false && strpos($name, $search) === false) {
                    return false;
                }
            }
            
            return true;
        });
    }
    
    /**
     * Sort locals based on criteria
     *
     * @param array $locals Array of locals
     * @param string $sort_by Sort field
     * @param string $sort_dir Sort direction (asc|desc)
     * @return array Sorted locals
     */
    public static function sort_locals($locals, $sort_by = 'priceGrossm2', $sort_dir = 'asc') {
        if (empty($locals) || !is_array($locals)) {
            return array();
        }
        
        $sort_dir = strtolower($sort_dir) === 'desc' ? 'desc' : 'asc';
        
        // Allowed sort fields
        $allowed_fields = array(
            'priceGross', 'priceNet', 'priceGrossm2', 'priceNetm2',
            'omnibusPriceGross', 'omnibusPriceNet', 'omnibusPriceGrossm2', 'omnibusPriceNetm2',
            'area', 'areaUsable', 'rooms', 'floor', 'plannedDateOfFinishing',
            'number', 'building', 'status'
        );
        
        if (!in_array($sort_by, $allowed_fields)) {
            $sort_by = 'priceGrossm2';
        }
        
        usort($locals, function($a, $b) use ($sort_by, $sort_dir) {
            $val_a = isset($a[$sort_by]) ? $a[$sort_by] : '';
            $val_b = isset($b[$sort_by]) ? $b[$sort_by] : '';
            
            // Handle numeric values
            if (is_numeric($val_a) && is_numeric($val_b)) {
                $comparison = $val_a <=> $val_b;
            } else {
                // String comparison
                $comparison = strcasecmp($val_a, $val_b);
            }
            
            return $sort_dir === 'desc' ? -$comparison : $comparison;
        });
        
        return $locals;
    }
    
    /**
     * Get unique values for a field from locals
     *
     * @param array $locals Array of locals
     * @param string $field Field name
     * @return array Unique values
     */
    public static function get_unique_values($locals, $field) {
        if (empty($locals) || !is_array($locals)) {
            return array();
        }
        
        $values = array();
        
        foreach ($locals as $local) {
            if (isset($local[$field]) && $local[$field] !== '' && $local[$field] !== null) {
                $values[] = $local[$field];
            }
        }
        
        return array_unique($values);
    }
    
    /**
     * Get buildings list from locals
     *
     * @param array $locals Array of locals
     * @return array Buildings with ID and name
     */
    public static function get_buildings($locals) {
        if (empty($locals) || !is_array($locals)) {
            return array();
        }
        
        $buildings = array();
        
        foreach ($locals as $local) {
            if (!empty($local['buildingId']) && !isset($buildings[$local['buildingId']])) {
                $buildings[$local['buildingId']] = array(
                    'id' => $local['buildingId'],
                    'name' => $local['building'],
                );
            }
        }
        
        return array_values($buildings);
    }
    
    /**
     * Count locals by status
     *
     * @param array $locals Array of locals
     * @return array Status counts
     */
    public static function count_by_status($locals) {
        if (empty($locals) || !is_array($locals)) {
            return array();
        }
        
        $counts = array();
        
        foreach ($locals as $local) {
            $status = $local['status'];
            
            if (!isset($counts[$status])) {
                $counts[$status] = 0;
            }
            
            $counts[$status]++;
        }
        
        return $counts;
    }
}

