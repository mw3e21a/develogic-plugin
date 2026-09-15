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
                // JSON array of selected locals (structured, for the CSV).
                // Opcjonalny: starszy, zakeszowany JS potrafi go nie wysłać —
                // wtedy mail idzie bez załącznika, ale idzie.
                'apartments_json' => array(
                    'type' => 'string',
                    'required' => false,
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
        $apartments_json = $request->get_param('apartments_json');

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
        $survey = array();
        if (!empty($survey_data)) {
            $decoded = json_decode(wp_unslash($survey_data), true);
            if (is_array($decoded)) {
                $survey = $decoded;
            }
        }
        if (!empty($survey)) {
            $body .= "Ankieta:\n";
            foreach ($survey as $question => $answer) {
                $body .= "  " . $question . ": " . $answer . "\n";
            }
            $body .= "\n";
        }

        $body .= "Wybrane lokale:\n" . $apartments . "\n";

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'Reply-To: ' . $name . ' <' . $email . '>',
        );

        // Załącznik CSV — taki sam jak przy "Umów się na spotkanie". Wcześniej
        // ten formularz wysyłał samą treść, więc firma dostawała zestawienie
        // tylko jedną z dwóch dróg.
        $attachments = array();
        $filepath = '';
        $csv_source = '';

        $apartments_list = json_decode(wp_unslash((string) $apartments_json), true);
        if (is_array($apartments_list) && !empty($apartments_list)) {
            $csv = self::build_quote_csv($apartments_list, $name, $email, $phone, $survey);
            $csv_source = 'json';
        } else {
            // Zapas na wypadek zakeszowanego, starszego skryptu, który wysyła
            // wyłącznie gotowy tekst bez danych strukturalnych. Lepszy załącznik
            // z surowymi wierszami niż żaden — firma i tak dostaje zestawienie
            // w pliku, a nie tylko w treści maila.
            $csv = self::build_quote_csv_from_text($apartments, $name, $email, $phone, $survey);
            $csv_source = 'tekst';
        }

        $safe_name = sanitize_file_name($name);
        if ($safe_name === '') {
            $safe_name = 'klient';
        }
        $filepath = self::write_temp_attachment(
            'konfigurator-' . $safe_name . '-' . date('Y-m-d') . '.csv',
            $csv['content']
        );
        if ($filepath !== '' && is_readable($filepath) && filesize($filepath) > 0) {
            $attachments[] = $filepath;
        }

        $mail_error = '';
        $capture = function ($wp_error) use (&$mail_error) {
            if (is_wp_error($wp_error)) {
                $mail_error = $wp_error->get_error_message();
            }
        };
        add_action('wp_mail_failed', $capture);
        $sent = wp_mail($to, $subject, $body, $headers, $attachments);
        remove_action('wp_mail_failed', $capture);

        self::log_mail(
            sprintf(
                'Formularz -> %s | załącznik: %s | wynik: %s%s',
                $to,
                empty($attachments) ? 'BRAK' : basename($filepath) . ' (' . filesize($filepath) . ' B)',
                $sent ? 'wysłano' : 'BŁĄD',
                $mail_error !== '' ? ' | ' . $mail_error : ''
            ),
            array(
                'to'         => $to,
                'client'     => $name,
                'source'     => 'Wyślij formularz',
                'attachment' => empty($attachments) ? '' : basename($filepath),
                'size'       => empty($attachments) ? 0 : filesize($filepath),
                'sent'       => (bool) $sent,
                'error'      => $mail_error,
            )
        );

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
        $csv = self::build_quote_csv($apartments, $name, $email, $phone, $survey);
        $csv_content = $csv['content'];
        $csv_rows    = $csv['rows'];
        $total       = $csv['total'];
        $i           = $csv['count'];

        // Write the CSV to a temp file for wp_mail attachment. wp_mail uses the
        // file's basename as the attachment name shown to the recipient, so we
        // give it a readable name (client's surname + date). Uniqueness on disk
        // is guaranteed by a random sub-directory, not by the visible filename.
        $safe_name = sanitize_file_name($name);          // "Jan Kowalski" -> "Jan-Kowalski"
        if ($safe_name === '') {
            $safe_name = 'klient';
        }
        $filename = 'konfigurator-' . $safe_name . '-' . date('Y-m-d') . '.csv';
        $filepath = self::write_temp_attachment($filename, $csv_content);

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

        // Załącznik dokładamy tylko wtedy, gdy plik naprawdę powstał i da się go
        // odczytać. PHPMailer na brakującym pliku rzuca wyjątek, przez co cała
        // wiadomość nie wychodzi — lepiej wysłać ją bez załącznika, z tabelą
        // wklejoną w treść, niż nie wysłać wcale.
        $attachments = array();
        if ($filepath !== '' && is_readable($filepath) && filesize($filepath) > 0) {
            $attachments[] = $filepath;
        } else {
            self::log_mail('Nie udało się przygotować pliku CSV — wysyłam zestawienie w treści wiadomości.');
            $body .= "\n--- Zestawienie (załącznik CSV się nie utworzył) ---\n";
            foreach ($csv_rows as $row) {
                $body .= implode(' | ', (array) $row) . "\n";
            }
        }

        // Przechwyć powód odrzucenia wiadomości przez PHPMailer / wtyczkę SMTP,
        // żeby w logu było widać, co się stało — samo `false` z wp_mail() nic
        // nie mówi przy zgłoszeniach typu "mail nie doszedł".
        $mail_error = '';
        $capture = function ($wp_error) use (&$mail_error) {
            if (is_wp_error($wp_error)) {
                $mail_error = $wp_error->get_error_message();
            }
        };
        add_action('wp_mail_failed', $capture);
        $sent = wp_mail($to, $subject, $body, $headers, $attachments);
        remove_action('wp_mail_failed', $capture);

        self::log_mail(
            sprintf(
                'Konfigurator -> %s | załącznik: %s | wynik: %s%s',
                $to,
                empty($attachments) ? 'BRAK' : basename($filepath) . ' (' . filesize($filepath) . ' B)',
                $sent ? 'wysłano' : 'BŁĄD',
                $mail_error !== '' ? ' | ' . $mail_error : ''
            ),
            array(
                'to'         => $to,
                'client'     => $name,
                'source'     => 'Umów się na spotkanie',
                'attachment' => empty($attachments) ? '' : basename($filepath),
                'size'       => empty($attachments) ? 0 : filesize($filepath),
                'sent'       => (bool) $sent,
                'error'      => $mail_error,
            )
        );

        // Pliku NIE kasujemy tutaj. Wtyczki SMTP/API (WP Mail SMTP, FluentSMTP,
        // Post SMTP i podobne) potrafią kolejkować wysyłkę i sięgać po załącznik
        // dopiero po zakończeniu tego żądania — skasowany od razu plik znikał
        // wtedy sprzed nosa i wiadomość szła bez załącznika. Sprzątanie robi
        // sweep starych plików przy kolejnym zgłoszeniu (patrz niżej).
        if (!$sent) {
            return new WP_Error('mail_error', __('Nie udało się wysłać wiadomości do firmy.', 'develogic'), array('status' => 500));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Prośba o spotkanie została wysłana', 'develogic'),
        ), 200);
    }
    /**
     * Buduje zestawienie CSV z wybranych lokali (UTF-8 z BOM, separator ";").
     *
     * Wspólne dla obu przycisków konfiguratora — "Wyślij formularz" i "Umów się
     * na spotkanie" — żeby firma dostawała identyczny załącznik niezależnie od
     * tego, którą drogą klient wysłał zgłoszenie.
     *
     * @param array  $apartments Lokale (tablice z kluczami localType/number/...)
     * @param string $name       Imię i nazwisko klienta
     * @param string $email      Email klienta
     * @param string $phone      Telefon klienta
     * @param array  $survey     Odpowiedzi z ankiety (pytanie => odpowiedź)
     * @return array{content: string, rows: array, total: float, count: int}
     */
    private static function build_quote_csv($apartments, $name, $email, $phone, $survey = array()) {
        $rows = array();
        $rows[] = array('Lp.', 'Typ', 'Numer', 'Budynek', 'Piętro', 'Powierzchnia', 'Pokoje', 'Cena brutto');
        $total = 0.0;
        $count = 0;

        foreach ((array) $apartments as $apt) {
            if (!is_array($apt)) {
                continue;
            }
            $count++;
            $price = isset($apt['price']) ? (float) $apt['price'] : 0;
            // Konfigurator wysyła cenę raz jako "price", raz jako "priceGross".
            if ($price <= 0 && isset($apt['priceGross'])) {
                $price = (float) $apt['priceGross'];
            }
            $total += $price;
            $rows[] = array(
                $count,
                isset($apt['localType']) ? $apt['localType'] : '',
                isset($apt['number']) ? $apt['number'] : '',
                isset($apt['building']) ? $apt['building'] : '',
                isset($apt['floorDisplay']) ? $apt['floorDisplay'] : (isset($apt['floor']) ? $apt['floor'] : ''),
                isset($apt['area']) ? $apt['area'] : '',
                isset($apt['rooms']) ? $apt['rooms'] : '',
                $price > 0 ? number_format($price, 2, ',', ' ') . ' zł' : '-',
            );
        }
        $rows[] = array('', '', '', '', '', '', 'Łączna cena:', number_format($total, 2, ',', ' ') . ' zł');

        // Contact + survey block appended below the table.
        $rows[] = array();
        $rows[] = array('Dane kontaktowe');
        $rows[] = array('Imię i nazwisko', $name);
        $rows[] = array('Email', $email);
        $rows[] = array('Telefon', !empty($phone) ? $phone : '-');
        if (!empty($survey)) {
            $rows[] = array();
            $rows[] = array('Ankieta');
            foreach ($survey as $q => $a) {
                $rows[] = array($q, $a);
            }
        }

        $fh = fopen('php://temp', 'r+');
        fputs($fh, "\xEF\xBB\xBF"); // UTF-8 BOM
        foreach ($rows as $row) {
            fputcsv($fh, $row, ';');
        }
        rewind($fh);
        $content = stream_get_contents($fh);
        fclose($fh);

        return array(
            'content' => $content,
            'rows'    => $rows,
            'total'   => $total,
            'count'   => $count,
        );
    }

    /**
     * Buduje CSV z gotowego, tekstowego zestawienia lokali.
     *
     * Używane tylko jako zapas, gdy zgłoszenie nie przyniosło danych
     * strukturalnych (np. przeglądarka trzyma starszą wersję skryptu).
     * Kolumny rozbijamy po separatorze "|", którym konfigurator skleja wiersz.
     *
     * @param string $apartments_text Wiersze rozdzielone znakiem nowej linii
     * @param string $name            Imię i nazwisko klienta
     * @param string $email           Email klienta
     * @param string $phone           Telefon klienta
     * @param array  $survey          Odpowiedzi z ankiety
     * @return array{content: string, rows: array, total: float, count: int}
     */
    private static function build_quote_csv_from_text($apartments_text, $name, $email, $phone, $survey = array()) {
        $rows = array();
        $rows[] = array('Lp.', 'Lokal');

        $count = 0;
        $lines = preg_split('/\r\n|\r|\n/', (string) $apartments_text);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $count++;
            $parts = array_map('trim', explode('|', $line));
            $rows[] = array_merge(array($count), $parts);
        }

        $rows[] = array();
        $rows[] = array('Dane kontaktowe');
        $rows[] = array('Imię i nazwisko', $name);
        $rows[] = array('Email', $email);
        $rows[] = array('Telefon', !empty($phone) ? $phone : '-');
        if (!empty($survey)) {
            $rows[] = array();
            $rows[] = array('Ankieta');
            foreach ($survey as $q => $a) {
                $rows[] = array($q, $a);
            }
        }

        $fh = fopen('php://temp', 'r+');
        fputs($fh, "\xEF\xBB\xBF"); // UTF-8 BOM
        foreach ($rows as $row) {
            fputcsv($fh, $row, ';');
        }
        rewind($fh);
        $content = stream_get_contents($fh);
        fclose($fh);

        return array(
            'content' => $content,
            'rows'    => $rows,
            'total'   => 0.0,
            'count'   => $count,
        );
    }

    /**
     * Zapisuje treść załącznika do pliku tymczasowego i zwraca jego ścieżkę.
     *
     * Katalog systemowy (get_temp_dir()) jest pierwszym wyborem, bo nie jest
     * dostępny z przeglądarki — w pliku są dane osobowe klienta. Gdy nie da się
     * do niego pisać (open_basedir na części hostingów), schodzimy do
     * uploads/develogic-tmp/.
     *
     * @param string $filename Nazwa widoczna dla odbiorcy maila
     * @param string $content  Zawartość pliku
     * @return string Ścieżka do pliku albo '' gdy zapis się nie powiódł
     */
    private static function write_temp_attachment($filename, $content) {
        self::cleanup_old_attachments();

        $bases = array();
        $sys_tmp = get_temp_dir();
        if (!empty($sys_tmp) && is_writable($sys_tmp)) {
            $bases[] = trailingslashit($sys_tmp) . 'develogic-tmp';
        }
        $upload = wp_upload_dir();
        if (empty($upload['error']) && !empty($upload['basedir'])) {
            $bases[] = trailingslashit($upload['basedir']) . 'develogic-tmp';
        }

        foreach ($bases as $base) {
            // Losowy podkatalog: dzięki niemu widoczna nazwa pliku może być
            // czytelna (nazwisko + data) i nie musi być unikalna.
            $dir = trailingslashit($base) . wp_generate_password(12, false);
            if (!wp_mkdir_p($dir)) {
                continue;
            }
            // Katalog w uploads jest dostępny z sieci — zablokuj listowanie.
            if (!file_exists(trailingslashit($base) . 'index.html')) {
                @file_put_contents(trailingslashit($base) . 'index.html', '');
            }
            $path = trailingslashit($dir) . $filename;
            if (file_put_contents($path, $content) !== false) {
                return $path;
            }
        }

        self::log_mail('Brak zapisywalnego katalogu tymczasowego na załącznik (' . implode(', ', $bases) . ').');
        return '';
    }

    /**
     * Kasuje załączniki starsze niż godzina.
     *
     * Plik musi przeżyć samo żądanie, bo wtyczki kolejkujące pocztę sięgają po
     * niego później. Sprzątamy więc przy okazji kolejnego zgłoszenia, zamiast
     * zaraz po wp_mail() — i bez zależności od WP-Cron, który bywa wyłączony.
     */
    private static function cleanup_old_attachments() {
        $bases = array();
        $sys_tmp = get_temp_dir();
        if (!empty($sys_tmp)) {
            $bases[] = trailingslashit($sys_tmp) . 'develogic-tmp';
        }
        $upload = wp_upload_dir();
        if (empty($upload['error']) && !empty($upload['basedir'])) {
            $bases[] = trailingslashit($upload['basedir']) . 'develogic-tmp';
        }

        $cutoff = time() - HOUR_IN_SECONDS;
        foreach ($bases as $base) {
            if (!is_dir($base)) {
                continue;
            }
            $entries = @scandir($base);
            if (!is_array($entries)) {
                continue;
            }
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..' || $entry === 'index.html') {
                    continue;
                }
                $dir = trailingslashit($base) . $entry;
                if (!is_dir($dir) || @filemtime($dir) > $cutoff) {
                    continue;
                }
                foreach ((array) @glob(trailingslashit($dir) . '*') as $file) {
                    @unlink($file);
                }
                @rmdir($dir);
            }
        }
    }

    /**
     * Nazwa opcji z logiem wysyłek konfiguratora.
     */
    const MAIL_LOG_OPTION = 'develogic_mail_log';

    /**
     * Ile ostatnich wpisów trzymamy w logu.
     */
    const MAIL_LOG_LIMIT = 30;

    /**
     * Log wysyłki maila z konfiguratora.
     *
     * Wpis idzie do error_log ORAZ do opcji w bazie — dzięki temu da się go
     * obejrzeć w panelu WordPressa (Develogic → Diagnostyka maili), bez dostępu
     * do logów serwera.
     *
     * @param string $message Treść wpisu
     * @param array  $context Dodatkowe pola do pokazania w tabeli w adminie
     */
    private static function log_mail($message, $context = array()) {
        error_log(sprintf('[Develogic Konfigurator] %s', $message));

        $log = get_option(self::MAIL_LOG_OPTION, array());
        if (!is_array($log)) {
            $log = array();
        }
        array_unshift($log, array_merge(array(
            'time'    => current_time('mysql'),
            'message' => $message,
        ), $context));
        $log = array_slice($log, 0, self::MAIL_LOG_LIMIT);
        update_option(self::MAIL_LOG_OPTION, $log, false);
    }

    /**
     * Wysyła testową wiadomość konfiguratora tą samą ścieżką co realna.
     *
     * Używana przez przycisk w panelu — pozwala sprawdzić, czy wiadomość
     * z załącznikiem w ogóle wychodzi, bez składania prawdziwego zapytania
     * przez formularz na stronie.
     *
     * @param string $to Adres odbiorcy
     * @return array{sent: bool, error: string, attachment: string}
     */
    public static function send_test_quote_mail($to) {
        $csv  = "\xEF\xBB\xBF";
        $csv .= "Lp.;Typ;Numer;Cena brutto\n";
        $csv .= "1;Lokal mieszkalny;M1;500 000,00 zł\n";
        $csv .= "2;Garaż;G1-21;45 000,00 zł\n";

        $filename = 'konfigurator-test-' . date('Y-m-d') . '.csv';
        $filepath = self::write_temp_attachment($filename, $csv);

        $attachments = array();
        if ($filepath !== '' && is_readable($filepath) && filesize($filepath) > 0) {
            $attachments[] = $filepath;
        }

        $mail_error = '';
        $capture = function ($wp_error) use (&$mail_error) {
            if (is_wp_error($wp_error)) {
                $mail_error = $wp_error->get_error_message();
            }
        };
        add_action('wp_mail_failed', $capture);
        $sent = wp_mail(
            $to,
            'TEST konfiguratora Develogic',
            "To jest wiadomość testowa z wtyczki Develogic.\n\n" .
            "Powinien być do niej dołączony plik CSV — jeśli go nie widzisz,\n" .
            "załącznik jest gubiony po drodze (wtyczka SMTP, serwer pocztowy\n" .
            "albo skaner antywirusowy), a nie przy generowaniu.\n",
            array('Content-Type: text/plain; charset=UTF-8'),
            $attachments
        );
        remove_action('wp_mail_failed', $capture);

        self::log_mail(
            sprintf('TEST -> %s | wynik: %s%s', $to, $sent ? 'wysłano' : 'BŁĄD', $mail_error !== '' ? ' | ' . $mail_error : ''),
            array(
                'to'         => $to,
                'source'     => 'Test z panelu',
                'attachment' => empty($attachments) ? '' : basename($filepath),
                'size'       => empty($attachments) ? 0 : filesize($filepath),
                'sent'       => (bool) $sent,
                'error'      => $mail_error,
                'test'       => true,
            )
        );

        return array(
            'sent'       => (bool) $sent,
            'error'      => $mail_error,
            'attachment' => empty($attachments) ? '' : basename($filepath),
        );
    }

}

