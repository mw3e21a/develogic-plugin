<?php
/**
 * Develogic API Client
 *
 * @package Develogic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Develogic_API_Client
 */
class Develogic_API_Client {
    
    /**
     * Opcja z logiem wywołań API (podgląd w zakładce „Logi API")
     */
    const CALL_LOG_OPTION = 'develogic_api_log';
    
    /**
     * Ile ostatnich wpisów trzymamy w logu
     */
    const CALL_LOG_LIMIT = 500;
    
    /**
     * Źródło bieżących wywołań (ustawiane przez punkty wejścia synchronizacji)
     *
     * @var string
     */
    private static $context = '';
    
    /**
     * Ustaw źródło wywołań widoczne w logu, np. „WP-Cron", „REST /sync".
     *
     * @param string $context
     */
    public static function set_context($context) {
        self::$context = (string) $context;
    }
    
    /**
     * Źródło wywołania — jawnie ustawione albo odgadnięte z typu żądania.
     *
     * @return string
     */
    private static function get_context() {
        if (self::$context !== '') {
            return self::$context;
        }
        if (function_exists('wp_doing_cron') && wp_doing_cron()) {
            return 'WP-Cron';
        }
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return 'REST';
        }
        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
            return 'AJAX';
        }
        if (is_admin()) {
            return 'Panel';
        }
        return 'Frontend';
    }
    
    /**
     * Dopisz wpis do logu wywołań.
     *
     * @param array $entry
     */
    public static function add_call_log($entry) {
        $log = get_option(self::CALL_LOG_OPTION, array());
        if (!is_array($log)) {
            $log = array();
        }
        
        array_unshift($log, array_merge(array(
            'ts'       => time(),
            'source'   => self::get_context(),
            'method'   => '',
            'url'      => '',
            'status'   => 0,
            'ms'       => 0,
            'bytes'    => 0,
            'items'    => null,
            'error'    => '',
            'skipped'  => false,
        ), $entry));
        
        $log = array_slice($log, 0, self::CALL_LOG_LIMIT);
        update_option(self::CALL_LOG_OPTION, $log, false);
    }
    
    /**
     * API Base URL
     *
     * @var string
     */
    private $base_url;
    
    /**
     * API Key
     *
     * @var string
     */
    private $api_key;
    
    /**
     * Request timeout
     *
     * @var int
     */
    private $timeout;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->base_url = develogic()->get_setting('api_base_url');
        $this->api_key = develogic()->get_setting('api_key');
        $this->timeout = develogic()->get_setting('api_timeout', 30);
    }
    
    /**
     * Make API request
     *
     * @param string $endpoint API endpoint
     * @param array $params Optional query parameters
     * @param string $method Request method (GET, POST)
     * @return array|WP_Error Response data or error
     */
    private function request($endpoint, $params = array(), $method = 'GET') {
        if (empty($this->base_url) || empty($this->api_key)) {
            return new WP_Error('missing_config', __('API URL lub klucz API nie zostały skonfigurowane.', 'develogic'));
        }
        
        $url = trailingslashit($this->base_url) . ltrim($endpoint, '/');
        
        // Add query parameters
        if (!empty($params) && $method === 'GET') {
            $url = add_query_arg($params, $url);
        }
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[Develogic API] Request: %s %s', $method, $url));
        }
        
        $args = array(
            'method' => $method,
            'timeout' => $this->timeout,
            'headers' => array(
                'ApiKey' => $this->api_key,
                'Accept' => 'application/json',
            ),
        );
        
        if ($method === 'POST' && !empty($params)) {
            $args['body'] = json_encode($params);
            $args['headers']['Content-Type'] = 'application/json';
        }
        
        $started = microtime(true);
        $response = wp_remote_request($url, $args);
        $call = array(
            'method' => $method,
            'url'    => $url,
            'ms'     => (int) round((microtime(true) - $started) * 1000),
        );
        
        if (is_wp_error($response)) {
            self::add_call_log(array_merge($call, array('error' => $response->get_error_message())));
            $this->log_error('Request failed: ' . $response->get_error_message(), $endpoint);
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $call['status'] = (int) $status_code;
        $call['bytes'] = strlen($body);
        
        if ($status_code < 200 || $status_code >= 300) {
            self::add_call_log(array_merge($call, array('error' => 'HTTP ' . $status_code)));
            $error_message = sprintf(__('API zwróciło kod błędu: %d', 'develogic'), $status_code);
            $error_details = sprintf(
                'URL: %s | Status: %d | Response: %s',
                $url,
                $status_code,
                substr($body, 0, 500)
            );
            $this->log_error($error_message, $endpoint, $error_details);
            return new WP_Error('api_error', $error_message . ' - Sprawdź URL API w ustawieniach', array('status' => $status_code, 'url' => $url));
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::add_call_log(array_merge($call, array('error' => 'Niepoprawny JSON')));
            $error_message = __('Błąd parsowania JSON z API.', 'develogic');
            $this->log_error($error_message, $endpoint, $body);
            return new WP_Error('json_error', $error_message);
        }
        
        self::add_call_log(array_merge($call, array('items' => is_array($data) ? count($data) : null)));
        
        return $data;
    }
    
    /**
     * Get list of locals (apartments/properties)
     *
     * @param array $filters Optional filters (investmentId, localTypeId)
     * @return array|WP_Error
     */
    public function get_locals($filters = array()) {
        $params = array();
        
        if (!empty($filters['investmentId'])) {
            $params['investmentId'] = absint($filters['investmentId']);
        }
        
        if (!empty($filters['localTypeId'])) {
            $params['localTypeId'] = absint($filters['localTypeId']);
        }
        
        return $this->request('api/fis/v1/feed/locals', $params);
    }
    
    /**
     * Get list of investments
     *
     * @return array|WP_Error
     */
    public function get_investments() {
        return $this->request('api/fis/v1/feed/investments');
    }
    
    /**
     * Get list of local types
     *
     * @return array|WP_Error
     */
    public function get_local_types() {
        return $this->request('api/fis/v1/feed/localTypes');
    }
    
    /**
     * Get price history for a local
     *
     * @param int $local_id Local ID
     * @return array|WP_Error
     */
    public function get_price_history($local_id) {
        if (empty($local_id)) {
            return new WP_Error('invalid_param', __('ID lokalu jest wymagane.', 'develogic'));
        }
        
        return $this->request('api/fis/v1/feed/localPrices/' . absint($local_id));
    }
    
    /**
     * Get projection image
     *
     * @param int $projection_id Projection ID
     * @return string|WP_Error URL or error
     */
    public function get_projection_url($projection_id) {
        if (empty($projection_id)) {
            return new WP_Error('invalid_param', __('ID projekcji jest wymagane.', 'develogic'));
        }
        
        return trailingslashit($this->base_url) . 'api/fis/v1/feed/projection/' . absint($projection_id);
    }
    
    /**
     * Download projection image from API
     *
     * @param int $projection_id Projection ID
     * @return string|WP_Error Image data or error
     */
    public function download_projection_image($projection_id) {
        if (empty($projection_id)) {
            return new WP_Error('invalid_param', __('ID projekcji jest wymagane.', 'develogic'));
        }
        
        $url = $this->get_projection_url($projection_id);
        
        $args = array(
            'timeout' => $this->timeout,
            'headers' => array(
                'ApiKey' => $this->api_key,
            ),
        );
        
        $started = microtime(true);
        $response = wp_remote_get($url, $args);
        $call = array(
            'method' => 'GET',
            'url'    => $url,
            'ms'     => (int) round((microtime(true) - $started) * 1000),
        );
        
        if (is_wp_error($response)) {
            self::add_call_log(array_merge($call, array('error' => $response->get_error_message())));
            $this->log_error('Failed to download projection: ' . $response->get_error_message(), $url);
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        self::add_call_log(array_merge($call, array(
            'status' => (int) $status_code,
            'bytes'  => strlen(wp_remote_retrieve_body($response)),
            'error'  => $status_code !== 200 ? 'HTTP ' . $status_code : '',
        )));
        
        if ($status_code !== 200) {
            $error_message = sprintf(__('Nie udało się pobrać projekcji. Kod błędu: %d', 'develogic'), $status_code);
            $this->log_error($error_message, $url);
            return new WP_Error('download_error', $error_message);
        }
        
        return wp_remote_retrieve_body($response);
    }
    
    /**
     * Log error
     *
     * @param string $message Error message
     * @param string $endpoint API endpoint
     * @param mixed $details Additional details
     */
    private function log_error($message, $endpoint = '', $details = null) {
        $details_str = is_string($details) ? $details : print_r($details, true);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[Develogic API Error] %s | Endpoint: %s | Details: %s',
                $message,
                $endpoint,
                $details_str
            ));
        }
        
        // Store last error in transient for admin notice
        set_transient('develogic_last_api_error', array(
            'message' => $message,
            'endpoint' => $endpoint,
            'details' => $details_str,
            'time' => current_time('mysql'),
        ), 3600);
    }
}

