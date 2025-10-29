<?php
/**
 * Develogic Data Formatter
 *
 * @package Develogic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Develogic_Data_Formatter
 */
class Develogic_Data_Formatter {
    
    /**
     * Format price in Polish format
     *
     * @param float $price Price value
     * @param bool $show_currency Show currency symbol
     * @return string
     */
    public static function format_price($price, $show_currency = true) {
        if (empty($price) || !is_numeric($price)) {
            return '';
        }
        
        $formatted = number_format($price, 2, ',', ' ');
        
        if ($show_currency) {
            $formatted .= ' zł';
        }
        
        return $formatted;
    }
    
    /**
     * Format area in m²
     *
     * @param float $area Area value
     * @param int $decimals Number of decimal places
     * @return string
     */
    public static function format_area($area, $decimals = 2) {
        if (empty($area) || !is_numeric($area)) {
            return '';
        }
        
        return number_format($area, $decimals, ',', ' ') . ' m²';
    }
    
    /**
     * Format floor number
     *
     * @param mixed $floor Floor value
     * @return string
     */
    public static function format_floor($floor) {
        if ($floor === '' || $floor === null) {
            return '';
        }
        
        $floor_map = array(
            '-1' => __('Piwnica', 'develogic'),
            '0' => __('Parter', 'develogic'),
        );
        
        if (isset($floor_map[$floor])) {
            return $floor_map[$floor];
        }
        
        return absint($floor);
    }
    
    /**
     * Format date
     *
     * @param string $date Date string
     * @param string $format Date format
     * @return string
     */
    public static function format_date($date, $format = 'j F Y') {
        if (empty($date)) {
            return '';
        }
        
        $timestamp = strtotime($date);
        
        if (!$timestamp) {
            return $date;
        }
        
        return date_i18n($format, $timestamp);
    }
    
    /**
     * Format planned finishing date message
     *
     * @param string $date Date string
     * @return string
     */
    public static function format_planned_finishing($date) {
        if (empty($date)) {
            return '';
        }
        
        $timestamp = strtotime($date);
        
        if (!$timestamp) {
            return '';
        }
        
        $now = current_time('timestamp');
        
        if ($timestamp > $now) {
            // Future date - planned
            $quarter = ceil(date('n', $timestamp) / 3);
            $year = date('Y', $timestamp);
            $quarter_names = array(
                1 => __('I kwartał', 'develogic'),
                2 => __('II kwartał', 'develogic'),
                3 => __('III kwartał', 'develogic'),
                4 => __('IV kwartał', 'develogic'),
            );
            
            return sprintf(
                __('Planowane oddanie – %s %d', 'develogic'),
                $quarter_names[$quarter],
                $year
            );
        } else {
            // Past date - completed
            $month = date_i18n('F', $timestamp);
            $year = date('Y', $timestamp);
            
            return sprintf(
                __('Budynek oddany do użytku w %s %d', 'develogic'),
                $month,
                $year
            );
        }
    }
    
    /**
     * Parse world directions from string to array
     *
     * @param string $directions Comma-separated directions
     * @return array
     */
    public static function parse_world_directions($directions) {
        if (empty($directions)) {
            return array();
        }
        
        return array_map('trim', explode(',', $directions));
    }
    
    /**
     * Format world directions with full names
     *
     * @param string|array $directions Directions string or array
     * @return string
     */
    public static function format_world_directions($directions) {
        if (is_string($directions)) {
            $directions = self::parse_world_directions($directions);
        }
        
        if (empty($directions)) {
            return '';
        }
        
        $direction_names = array(
            'N' => __('Północ', 'develogic'),
            'E' => __('Wschód', 'develogic'),
            'S' => __('Południe', 'develogic'),
            'W' => __('Zachód', 'develogic'),
            'NE' => __('Północny Wschód', 'develogic'),
            'NW' => __('Północny Zachód', 'develogic'),
            'SE' => __('Południowy Wschód', 'develogic'),
            'SW' => __('Południowy Zachód', 'develogic'),
        );
        
        $formatted = array();
        foreach ($directions as $dir) {
            $dir = trim($dir);
            if (isset($direction_names[$dir])) {
                $formatted[] = $direction_names[$dir];
            } else {
                $formatted[] = $dir;
            }
        }
        
        return implode(', ', $formatted);
    }
    
    /**
     * Get status class for CSS
     *
     * @param string $status Status name
     * @return string
     */
    public static function get_status_class($status) {
        $status_map = array(
            'Wolny' => 'available',
            'Rezerwacja' => 'reserved',
            'Sprzedany' => 'sold',
            'Sprzedane' => 'sold',
        );
        
        return isset($status_map[$status]) ? $status_map[$status] : sanitize_title($status);
    }
    
    /**
     * Sanitize and validate local data
     *
     * @param array $local Local data from API
     * @return array
     */
    public static function sanitize_local($local) {
        return array(
            'localId' => isset($local['localId']) ? absint($local['localId']) : 0,
            'subdivisionId' => isset($local['subdivisionId']) ? absint($local['subdivisionId']) : 0,
            'subdivision' => isset($local['subdivision']) ? sanitize_text_field($local['subdivision']) : '',
            'city' => isset($local['city']) ? sanitize_text_field($local['city']) : '',
            'stageId' => isset($local['stageId']) ? absint($local['stageId']) : 0,
            'stage' => isset($local['stage']) ? sanitize_text_field($local['stage']) : '',
            'name' => isset($local['name']) ? sanitize_text_field($local['name']) : '',
            'number' => isset($local['number']) ? sanitize_text_field($local['number']) : '',
            'status' => isset($local['status']) ? sanitize_text_field($local['status']) : '',
            'statusId' => isset($local['statusId']) ? absint($local['statusId']) : 0,
            'floor' => isset($local['floor']) ? $local['floor'] : '',
            'rooms' => isset($local['rooms']) ? absint($local['rooms']) : 0,
            'area' => isset($local['area']) ? floatval($local['area']) : 0,
            'areaUsable' => isset($local['areaUsable']) ? floatval($local['areaUsable']) : 0,
            'priceNet' => isset($local['priceNet']) ? floatval($local['priceNet']) : 0,
            'priceGross' => isset($local['priceGross']) ? floatval($local['priceGross']) : 0,
            'priceNetm2' => isset($local['priceNetm2']) ? floatval($local['priceNetm2']) : 0,
            'priceGrossm2' => isset($local['priceGrossm2']) ? floatval($local['priceGrossm2']) : 0,
            'omnibusPriceGross' => isset($local['omnibusPriceGross']) ? floatval($local['omnibusPriceGross']) : 0,
            'omnibusPriceNet' => isset($local['omnibusPriceNet']) ? floatval($local['omnibusPriceNet']) : 0,
            'omnibusPriceGrossm2' => isset($local['omnibusPriceGrossm2']) ? floatval($local['omnibusPriceGrossm2']) : 0,
            'omnibusPriceNetm2' => isset($local['omnibusPriceNetm2']) ? floatval($local['omnibusPriceNetm2']) : 0,
            'buildingId' => isset($local['buildingId']) ? absint($local['buildingId']) : 0,
            'building' => isset($local['building']) ? sanitize_text_field($local['building']) : '',
            'localTypeId' => isset($local['localTypeId']) ? absint($local['localTypeId']) : 0,
            'localType' => isset($local['localType']) ? sanitize_text_field($local['localType']) : '',
            'plannedDateOfFinishing' => isset($local['plannedDateOfFinishing']) ? sanitize_text_field($local['plannedDateOfFinishing']) : '',
            'worldDirections' => isset($local['worldDirections']) ? sanitize_text_field($local['worldDirections']) : '',
            'projections' => isset($local['projections']) ? $local['projections'] : array(),
            'miscAreas' => isset($local['miscAreas']) ? $local['miscAreas'] : array(),
            'attributes' => isset($local['attributes']) ? $local['attributes'] : array(),
            'packages' => isset($local['packages']) ? $local['packages'] : array(),
        );
    }
}

