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

        // Send inquiry from configurator
        register_rest_route(self::NAMESPACE, '/inquiry', array(
            'methods' => 'POST',
            'callback' => array($this, 'send_inquiry'),
            'permission_callback' => '__return_true',
            'args' => array(
                'name' => array(
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'email' => array(
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_email',
                ),
                'phone' => array(
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'survey_data' => array(
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'apartments' => array(
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
            ),
        ));

        // Configurator "meeting request" — same payload as /inquiry but also
        // emails the company a CSV attachment of the selected locals.
        register_rest_route(self::NAMESPACE, '/configurator-meeting', array(
            'methods' => 'POST',
            'callback' => array($this, 'send_configurator_meeting'),
            'permission_callback' => '__return_true',
            'args' => array(
                'name' => array(
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'email' => array(
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_email',
                ),
                'phone' => array(
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'survey_data' => array(
                    'type' => 'string',
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                // JSON array of selected locals (structured, for the CSV).
                'apartments_json' => array(
                    'type' => 'string',
                    'required' => true,
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

    /**
     * Send inquiry email from configurator
     */
    public function send_inquiry($request) {
        $name = $request->get_param('name');
        $email = $request->get_param('email');
        $phone = $request->get_param('phone');
        $survey_data = $request->get_param('survey_data');
        $apartments = $request->get_param('apartments');

        // Validate email
        if (!is_email($email)) {
            return new WP_Error('invalid_email', __('Nieprawidłowy adres email', 'develogic'), array('status' => 400));
        }

        // Validate required fields
        if (empty($name) || empty($apartments)) {
            return new WP_Error('missing_fields', __('Wypełnij wszystkie wymagane pola', 'develogic'), array('status' => 400));
        }

        // Get recipient email from settings
        $to = develogic()->get_setting('contact_email', '');
        if (empty($to)) {
            $to = get_option('admin_email');
        }

        // Build email
        $subject = sprintf('Zapytanie z konfiguratora - %s', $name);

        $body = "Nowe zapytanie z konfiguratora oferty\n\n" .
            "Imię i nazwisko: " . $name . "\n" .
            "Email: " . $email . "\n" .
            "Telefon: " . (!empty($phone) ? $phone : '-') . "\n\n";

        // Add survey answers
        if (!empty($survey_data)) {
            $survey = json_decode(wp_unslash($survey_data), true);
            if (is_array($survey) && !empty($survey)) {
                $body .= "Ankieta:\n";
                foreach ($survey as $question => $answer) {
                    $body .= "  " . $question . ": " . $answer . "\n";
                }
                $body .= "\n";
            }
        }

        $body .= "Wybrane lokale:\n" . $apartments . "\n";

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'Reply-To: ' . $name . ' <' . $email . '>',
        );

        $sent = wp_mail($to, $subject, $body, $headers);

        if (!$sent) {
            return new WP_Error('mail_error', __('Nie udało się wysłać wiadomości. Spróbuj ponownie.', 'develogic'), array('status' => 500));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Zapytanie zostało wysłane pomyślnie', 'develogic'),
        ), 200);
    }

    /**
     * Send a "meeting request" from the configurator's PDF button.
     * Emails the company the same data as the configurator PDF, but with a CSV
     * attachment of the selected locals plus a note requesting a meeting.
     */
    public function send_configurator_meeting($request) {
        $name  = $request->get_param('name');
        $email = $request->get_param('email');
        $phone = $request->get_param('phone');
        $survey_data = $request->get_param('survey_data');
        $apartments_json = $request->get_param('apartments_json');

        if (!is_email($email)) {
            return new WP_Error('invalid_email', __('Nieprawidłowy adres email', 'develogic'), array('status' => 400));
        }

        $apartments = json_decode(wp_unslash($apartments_json), true);
        if (empty($name) || !is_array($apartments) || empty($apartments)) {
            return new WP_Error('missing_fields', __('Wypełnij wszystkie wymagane pola', 'develogic'), array('status' => 400));
        }

        $survey = array();
        if (!empty($survey_data)) {
            $decoded = json_decode(wp_unslash($survey_data), true);
            if (is_array($decoded)) {
                $survey = $decoded;
            }
        }

        // Recipient (company)
        $to = develogic()->get_setting('contact_email', '');
        if (empty($to)) {
            $to = get_option('admin_email');
        }

        // --- Build CSV file (UTF-8 with BOM so Excel reads Polish chars) ------
        $csv_rows = array();
        $csv_rows[] = array('Lp.', 'Typ', 'Numer', 'Budynek', 'Piętro', 'Powierzchnia', 'Pokoje', 'Cena brutto');
        $total = 0.0;
        $i = 0;
        foreach ($apartments as $apt) {
            $i++;
            $price = isset($apt['price']) ? (float) $apt['price'] : 0;
            $total += $price;
            $csv_rows[] = array(
                $i,
                isset($apt['localType']) ? $apt['localType'] : '',
                isset($apt['number']) ? $apt['number'] : '',
                isset($apt['building']) ? $apt['building'] : '',
                isset($apt['floor']) ? $apt['floor'] : '',
                isset($apt['area']) ? $apt['area'] : '',
                isset($apt['rooms']) ? $apt['rooms'] : '',
                $price > 0 ? number_format($price, 2, ',', ' ') . ' zł' : '-',
            );
        }
        $csv_rows[] = array('', '', '', '', '', '', 'Łączna cena:', number_format($total, 2, ',', ' ') . ' zł');

        // Contact + survey block appended below the table.
        $csv_rows[] = array();
        $csv_rows[] = array('Dane kontaktowe');
        $csv_rows[] = array('Imię i nazwisko', $name);
        $csv_rows[] = array('Email', $email);
        $csv_rows[] = array('Telefon', !empty($phone) ? $phone : '-');
        if (!empty($survey)) {
            $csv_rows[] = array();
            $csv_rows[] = array('Ankieta');
            foreach ($survey as $q => $a) {
                $csv_rows[] = array($q, $a);
            }
        }

        $fh = fopen('php://temp', 'r+');
        fputs($fh, "\xEF\xBB\xBF"); // UTF-8 BOM
        foreach ($csv_rows as $row) {
            fputcsv($fh, $row, ';');
        }
        rewind($fh);
        $csv_content = stream_get_contents($fh);
        fclose($fh);

        // Write the CSV to a temp file for wp_mail attachment. wp_mail uses the
        // file's basename as the attachment name shown to the recipient, so we
        // give it a readable name (client's surname + date). Uniqueness on disk
        // is guaranteed by a random sub-directory, not by the visible filename.
        $upload = wp_upload_dir();
        $tmp_dir = trailingslashit($upload['basedir']) . 'develogic-tmp/' . wp_generate_password(12, false);
        wp_mkdir_p($tmp_dir);

        $safe_name = sanitize_file_name($name);          // "Jan Kowalski" -> "Jan-Kowalski"
        if ($safe_name === '') {
            $safe_name = 'klient';
        }
        $filename = 'konfigurator-' . $safe_name . '-' . date('Y-m-d') . '.csv';
        $filepath = trailingslashit($tmp_dir) . $filename;
        file_put_contents($filepath, $csv_content);

        // --- Email --------------------------------------------------------------
        $subject = sprintf('Prośba o spotkanie z konfiguratora - %s', $name);

        $body  = "Nowa prośba o spotkanie z konfiguratora oferty.\n\n";
        $body .= "Klient prosi o kontakt i umówienie spotkania.\n\n";
        $body .= "Imię i nazwisko: " . $name . "\n";
        $body .= "Email: " . $email . "\n";
        $body .= "Telefon: " . (!empty($phone) ? $phone : '-') . "\n\n";
        if (!empty($survey)) {
            $body .= "Ankieta:\n";
            foreach ($survey as $q => $a) {
                $body .= "  " . $q . ": " . $a . "\n";
            }
            $body .= "\n";
        }
        $body .= "Wybrane lokale (" . $i . ") — szczegóły w załączonym pliku CSV.\n";
        $body .= "Łączna cena: " . number_format($total, 2, ',', ' ') . " zł\n";

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'Reply-To: ' . $name . ' <' . $email . '>',
        );

        $sent = wp_mail($to, $subject, $body, $headers, array($filepath));

        // Clean up the temp attachment and its random sub-directory.
        if (file_exists($filepath)) {
            @unlink($filepath);
        }
        if (is_dir($tmp_dir)) {
            @rmdir($tmp_dir);
        }

        if (!$sent) {
            return new WP_Error('mail_error', __('Nie udało się wysłać wiadomości do firmy.', 'develogic'), array('status' => 500));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Prośba o spotkanie została wysłana', 'develogic'),
        ), 200);
    }
}

