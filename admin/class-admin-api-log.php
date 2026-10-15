<?php
/**
 * Develogic Admin – log wywołań API Develogic
 *
 * @package Develogic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Develogic_Admin_API_Log
 *
 * Pokazuje każde odpytanie API Develogic: kiedy, skąd, jaki adres (z parametrami)
 * i ile danych wróciło. Pozwala sprawdzić, czy synchronizacja pyta tylko
 * o wybrane inwestycje i nie częściej niż raz na godzinę.
 */
class Develogic_Admin_API_Log {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 22);
        add_action('admin_post_develogic_clear_api_log', array($this, 'handle_clear_log'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'develogic',
            __('Logi API', 'develogic'),
            __('Logi API', 'develogic'),
            'manage_options',
            'develogic-api-log',
            array($this, 'render_page')
        );
    }

    /**
     * Wyczyść log.
     */
    public function handle_clear_log() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Brak uprawnień', 'develogic'));
        }
        check_admin_referer('develogic_clear_api_log');

        delete_option(Develogic_API_Client::CALL_LOG_OPTION);
        wp_safe_redirect(admin_url('admin.php?page=develogic-api-log'));
        exit;
    }

    /**
     * Ścieżka endpointu i parametry zapytania z pełnego adresu.
     *
     * @param string $url
     * @return array array(path, query-array)
     */
    private function split_url($url) {
        $path = (string) wp_parse_url($url, PHP_URL_PATH);
        $query = array();
        $query_string = wp_parse_url($url, PHP_URL_QUERY);
        if ($query_string) {
            parse_str($query_string, $query);
        }
        return array($path, $query);
    }

    /**
     * Czy to pobranie listy lokali bez filtra inwestycji (cała baza dewelopera).
     *
     * @param string $url
     * @return bool
     */
    private function is_unfiltered_locals($url) {
        list($path, $query) = $this->split_url($url);
        return strpos($path, 'feed/locals') !== false && empty($query['investmentId']);
    }

    /**
     * Render page
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $log = get_option(Develogic_API_Client::CALL_LOG_OPTION, array());
        if (!is_array($log)) {
            $log = array();
        }

        $selected = develogic()->get_setting('sync_investments', array());
        $selected = is_array($selected) ? array_filter(array_map('absint', $selected)) : array();

        $date_format = get_option('date_format') . ' H:i:s';
        $last_auto = (int) get_option('develogic_last_auto_sync', 0);
        $next_cron = wp_next_scheduled('develogic_sync_cron');
        $cron_enabled = (bool) develogic()->get_setting('enable_cron_sync', false);

        // Podsumowanie ostatnich 24 h
        $since = time() - DAY_IN_SECONDS;
        $by_endpoint = array();
        $unfiltered = 0;
        $skipped = 0;
        $syncs = array();
        foreach ($log as $entry) {
            if (empty($entry['ts']) || $entry['ts'] < $since) {
                continue;
            }
            if (!empty($entry['skipped'])) {
                $skipped++;
                continue;
            }
            list($path, $query) = $this->split_url($entry['url']);
            $key = $path . (isset($query['investmentId']) ? '?investmentId=' . $query['investmentId'] : '');
            if (strpos($path, 'feed/localPrices/') !== false) {
                $key = preg_replace('#localPrices/\d+#', 'localPrices/{id}', $path);
            } elseif (strpos($path, 'feed/projection/') !== false) {
                $key = preg_replace('#projection/\d+#', 'projection/{id}', $path);
            }
            if (!isset($by_endpoint[$key])) {
                $by_endpoint[$key] = array('count' => 0, 'bytes' => 0);
            }
            $by_endpoint[$key]['count']++;
            $by_endpoint[$key]['bytes'] += (int) $entry['bytes'];
            if ($this->is_unfiltered_locals($entry['url'])) {
                $unfiltered++;
            }
            if (strpos($path, 'feed/locals') !== false) {
                // Wywołania jednej synchronizacji dzieją się w ciągu kilku sekund — grupujemy po minucie
                $syncs[floor($entry['ts'] / 60)] = true;
            }
        }
        ksort($by_endpoint);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Logi wywołań API Develogic', 'develogic'); ?></h1>

            <div class="card" style="max-width: 900px;">
                <h2><?php esc_html_e('Konfiguracja', 'develogic'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><?php esc_html_e('Wybrane inwestycje', 'develogic'); ?></th>
                        <td>
                            <?php if (empty($selected)): ?>
                                <strong style="color:#b32d2e;"><?php esc_html_e('Brak — synchronizacja pobiera CAŁĄ bazę lokali.', 'develogic'); ?></strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=develogic-sync')); ?>"><?php esc_html_e('Wybierz w zakładce Synchronizacja', 'develogic'); ?></a>
                            <?php else: ?>
                                <code><?php echo esc_html(implode(', ', $selected)); ?></code>
                                — <?php esc_html_e('każda pobierana osobno przez ?investmentId=', 'develogic'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Limit automatycznej synchronizacji', 'develogic'); ?></th>
                        <td>
                            <?php
                            printf(
                                esc_html__('co najwyżej raz na %d min (REST /sync i WP-Cron)', 'develogic'),
                                (int) round(Develogic_Sync::get_auto_sync_interval() / MINUTE_IN_SECONDS)
                            );
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Ostatnia automatyczna', 'develogic'); ?></th>
                        <td><?php echo $last_auto ? esc_html(wp_date($date_format, $last_auto)) : '—'; ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('WP-Cron', 'develogic'); ?></th>
                        <td>
                            <?php if (!$cron_enabled): ?>
                                <?php esc_html_e('wyłączony w ustawieniach', 'develogic'); ?>
                            <?php elseif ($next_cron): ?>
                                <?php
                                printf(
                                    esc_html__('harmonogram: %1$s, następne uruchomienie: %2$s', 'develogic'),
                                    esc_html((string) wp_get_schedule('develogic_sync_cron')),
                                    esc_html(wp_date($date_format, $next_cron))
                                );
                                ?>
                            <?php else: ?>
                                <?php esc_html_e('włączony, ale nie zaplanowany', 'develogic'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <h2><?php esc_html_e('Ostatnie 24 godziny', 'develogic'); ?></h2>
            <p>
                <?php
                printf(
                    esc_html__('Synchronizacje pobierające lokale: %1$d · pominięte przez limit: %2$d · pobrania całej bazy (bez investmentId): %3$s', 'develogic'),
                    count($syncs),
                    $skipped,
                    $unfiltered > 0
                        ? '<strong style="color:#b32d2e;">' . (int) $unfiltered . '</strong>'
                        : '<strong style="color:#008a20;">0</strong>'
                );
                ?>
            </p>
            <?php if (!empty($by_endpoint)): ?>
                <table class="widefat striped" style="max-width: 900px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Endpoint', 'develogic'); ?></th>
                            <th><?php esc_html_e('Wywołań', 'develogic'); ?></th>
                            <th><?php esc_html_e('Pobrano łącznie', 'develogic'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($by_endpoint as $key => $row): ?>
                        <tr>
                            <td><code><?php echo esc_html($key); ?></code></td>
                            <td><?php echo (int) $row['count']; ?></td>
                            <td><?php echo esc_html(size_format($row['bytes'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <h2>
                <?php
                printf(
                    esc_html__('Wszystkie wywołania (ostatnie %d)', 'develogic'),
                    (int) Develogic_API_Client::CALL_LOG_LIMIT
                );
                ?>
            </h2>
            <?php if (empty($log)): ?>
                <p><?php esc_html_e('Brak wpisów. Pojawią się po pierwszym odpytaniu API (synchronizacja, historia cen itp.).', 'develogic'); ?></p>
            <?php else: ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Data', 'develogic'); ?></th>
                            <th><?php esc_html_e('Źródło', 'develogic'); ?></th>
                            <th><?php esc_html_e('Adres', 'develogic'); ?></th>
                            <th><?php esc_html_e('HTTP', 'develogic'); ?></th>
                            <th><?php esc_html_e('Czas', 'develogic'); ?></th>
                            <th><?php esc_html_e('Rozmiar', 'develogic'); ?></th>
                            <th><?php esc_html_e('Rekordów', 'develogic'); ?></th>
                            <th><?php esc_html_e('Uwagi', 'develogic'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($log as $entry): ?>
                        <?php
                        $is_skip = !empty($entry['skipped']);
                        $is_full = !$is_skip && $this->is_unfiltered_locals($entry['url']);
                        $status = (int) $entry['status'];
                        $row_style = $is_full ? ' style="background:#fcf0f1;"' : '';
                        ?>
                        <tr<?php echo $row_style; ?>>
                            <td style="white-space:nowrap;"><?php echo esc_html(wp_date($date_format, (int) $entry['ts'])); ?></td>
                            <td><?php echo esc_html($entry['source']); ?></td>
                            <td style="word-break:break-all;">
                                <?php if ($is_skip): ?>
                                    <em><?php esc_html_e('— brak zapytania do API —', 'develogic'); ?></em>
                                <?php else: ?>
                                    <code><?php echo esc_html($entry['method'] . ' ' . $entry['url']); ?></code>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($is_skip): ?>
                                    —
                                <?php elseif ($status >= 200 && $status < 300): ?>
                                    <span style="color:#008a20;"><?php echo $status; ?></span>
                                <?php else: ?>
                                    <span style="color:#b32d2e;"><?php echo $status ? $status : '✘'; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $is_skip ? '—' : esc_html(number_format_i18n((int) $entry['ms']) . ' ms'); ?></td>
                            <td><?php echo $is_skip ? '—' : esc_html(size_format((int) $entry['bytes'])); ?></td>
                            <td><?php echo $entry['items'] === null ? '—' : (int) $entry['items']; ?></td>
                            <td>
                                <?php if ($is_full): ?>
                                    <strong style="color:#b32d2e;"><?php esc_html_e('Cała baza (bez investmentId)', 'develogic'); ?></strong><br>
                                <?php endif; ?>
                                <?php echo esc_html($entry['error']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;">
                    <?php wp_nonce_field('develogic_clear_api_log'); ?>
                    <input type="hidden" name="action" value="develogic_clear_api_log">
                    <button type="submit" class="button"><?php esc_html_e('Wyczyść log', 'develogic'); ?></button>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
}
