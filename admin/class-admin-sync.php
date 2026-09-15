<?php
/**
 * Develogic Admin Sync Page
 *
 * @package Develogic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Develogic_Admin_Sync
 */
class Develogic_Admin_Sync {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);
        add_action('admin_post_develogic_manual_sync', array($this, 'handle_manual_sync'));
        // Worker synchronizacji w tle. Pętla zwrotna (loopback) leci bez ciasteczek,
        // więc trafia w wariant _nopriv — autoryzuje ją jednorazowy token.
        add_action('admin_post_nopriv_develogic_run_background_sync', array($this, 'handle_run_background_sync'));
        add_action('admin_post_develogic_run_background_sync', array($this, 'handle_run_background_sync'));
        // Zapas, gdy hosting blokuje loopback — dokańcza zakolejkowaną synchronizację.
        add_action('develogic_background_sync_fallback', array($this, 'run_queued_sync_fallback'));
        add_action('admin_post_develogic_clear_locals', array($this, 'handle_clear_locals'));
        add_action('admin_post_develogic_unlock_sync', array($this, 'handle_unlock_sync'));
        add_action('admin_post_develogic_fetch_investments', array($this, 'handle_fetch_investments'));
        add_action('admin_post_develogic_save_investments', array($this, 'handle_save_investments'));
        add_action('admin_post_develogic_force_resync_projections', array($this, 'handle_force_resync_projections'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'develogic',
            __('Synchronizacja', 'develogic'),
            __('Synchronizacja', 'develogic'),
            'manage_options',
            'develogic-sync',
            array($this, 'render_sync_page')
        );
    }
    
    /**
     * Render sync management page
     */
    public function render_sync_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $last_sync = get_option('develogic_last_sync', array());
        $sync_log = get_option('develogic_sync_log', array());
        $locals_count = wp_count_posts('develogic_local');
        $is_running = (bool) get_transient('develogic_sync_lock');
        $bg_state = get_option('develogic_sync_bg', array());
        if (!is_array($bg_state)) {
            $bg_state = array();
        }
        $bg_status = isset($bg_state['status']) ? $bg_state['status'] : '';
        $bg_active = in_array($bg_status, array('queued', 'running'), true);
        $secret_key = develogic()->get_setting('sync_secret_key');
        
        // Debug
        $api_base_url = develogic()->get_setting('api_base_url');
        $api_key = develogic()->get_setting('api_key');
        $api_configured = !empty($api_base_url) && !empty($api_key);
        
        // Check if investments exist
        $investments = Develogic_Local_Query::get_investments();
        $has_investments = !empty($investments);
        
        // Check if investments are selected
        $settings = get_option('develogic_settings', array());
        $selected_investments = isset($settings['sync_investments']) && is_array($settings['sync_investments']) 
            ? $settings['sync_investments'] 
            : array();
        $has_selected_investments = !empty($selected_investments);
        
        // Check if user wants to change investments
        $change_investments = isset($_GET['change_investments']) && $_GET['change_investments'] == '1';
        
        ?>
        <div class="wrap">
            <h1><?php _e('Synchronizacja z Develogic API', 'develogic'); ?></h1>

            <?php if (isset($_GET['sync_result']) && $_GET['sync_result'] === 'queued'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html(isset($_GET['sync_message']) ? urldecode(wp_unslash($_GET['sync_message'])) : ''); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($bg_active): ?>
                <?php
                $since = isset($bg_state['started_at']) ? $bg_state['started_at'] : (isset($bg_state['queued_at']) ? $bg_state['queued_at'] : time());
                $elapsed = max(0, time() - (int) $since);
                ?>
                <div class="notice notice-info">
                    <p>
                        <span class="spinner is-active" style="float:none;margin:0 6px 0 0;"></span>
                        <strong><?php
                            echo $bg_status === 'running'
                                ? esc_html__('Synchronizacja w toku…', 'develogic')
                                : esc_html__('Synchronizacja zakolejkowana, zaraz ruszy…', 'develogic');
                        ?></strong>
                        <?php printf(
                            /* translators: %s: czas trwania */
                            esc_html__('(trwa %s)', 'develogic'),
                            esc_html(human_time_diff((int) $since, time()))
                        ); ?>
                    </p>
                    <p class="description">
                        <?php esc_html_e('Możesz zamknąć tę stronę — synchronizacja działa po stronie serwera. Strona odświeży się sama.', 'develogic'); ?>
                    </p>
                </div>
                <script>
                    // Odświeżamy widok, dopóki synchronizacja trwa — postęp jest
                    // zapisywany po stronie serwera, więc wystarczy przeładowanie.
                    setTimeout(function () { window.location.reload(); }, 15000);
                </script>
            <?php elseif ($bg_status === 'done' && !empty($bg_state['result'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php esc_html_e('Synchronizacja w tle zakończona.', 'develogic'); ?></strong>
                    <?php echo esc_html(isset($bg_state['result']['message']) ? $bg_state['result']['message'] : ''); ?></p>
                </div>
            <?php elseif ($bg_status === 'error' && !empty($bg_state['result'])): ?>
                <div class="notice notice-error is-dismissible">
                    <p><strong><?php esc_html_e('Synchronizacja w tle zakończona błędem.', 'develogic'); ?></strong>
                    <?php echo esc_html(isset($bg_state['result']['message']) ? $bg_state['result']['message'] : ''); ?></p>
                </div>
            <?php endif; ?>

            
            <?php if (!$api_configured): ?>
                <div class="notice notice-warning">
                    <p><?php _e('API nie zostało skonfigurowane. Przejdź do Ustawień i wprowadź URL oraz klucz API.', 'develogic'); ?></p>
                </div>
            <?php elseif (!$has_investments): ?>
                <div class="notice notice-warning">
                    <p><?php _e('Brak dostępnych inwestycji. Najpierw pobierz listę inwestycji z API.', 'develogic'); ?></p>
                </div>
            <?php elseif (!$has_selected_investments): ?>
                <div class="notice notice-warning">
                    <p><?php _e('Nie wybrano żadnych inwestycji do synchronizacji. Wybierz inwestycje poniżej.', 'develogic'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['fetch_investments'])): ?>
                <?php if ($_GET['fetch_investments'] === 'success'): ?>
                    <?php $count = isset($_GET['count']) ? absint($_GET['count']) : 0; ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php printf(__('Pobrano %d inwestycji z API. Wybierz poniżej które mają być synchronizowane.', 'develogic'), $count); ?></p>
                    </div>
                <?php elseif ($_GET['fetch_investments'] === 'error'): ?>
                    <?php $error_message = isset($_GET['error_message']) ? urldecode($_GET['error_message']) : __('Nieznany błąd', 'develogic'); ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php printf(__('Błąd podczas pobierania inwestycji: %s', 'develogic'), esc_html($error_message)); ?></p>
                    </div>
                <?php elseif ($_GET['fetch_investments'] === 'empty'): ?>
                    <div class="notice notice-warning is-dismissible">
                        <p><?php _e('API nie zwróciło żadnych inwestycji. Sprawdź konfigurację API.', 'develogic'); ?></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if (isset($_GET['investments_saved']) && $_GET['investments_saved'] == '1'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Wybór inwestycji został zapisany. Możesz teraz uruchomić synchronizację.', 'develogic'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['unlocked']) && $_GET['unlocked'] == '1'): ?>
                <div class="notice notice-success is-dismissible">
                    <p>✅ <?php _e('Synchronizacja została odblokowana. Możesz teraz uruchomić nową synchronizację.', 'develogic'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['resync_projections']) && $_GET['resync_projections'] == 'success'): ?>
                <?php 
                $deleted = isset($_GET['deleted_count']) ? absint($_GET['deleted_count']) : 0;
                $locals = isset($_GET['local_count']) ? absint($_GET['local_count']) : 0;
                ?>
                <div class="notice notice-success is-dismissible">
                    <p>✅ <?php printf(__('Usunięto %d załączników projekcji z %d mieszkań. <strong>Uruchom teraz synchronizację aby pobrać pliki ponownie.</strong>', 'develogic'), $deleted, $locals); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Investments Table Section -->
            <?php if ($has_investments): ?>
            <div class="card">
                <h2><?php _e('Lista inwestycji', 'develogic'); ?></h2>
                <p class="description"><?php _e('Poniżej znajduje się lista wszystkich dostępnych inwestycji z ich ID. Możesz użyć tych ID w shortcode\'ach (parametr investment_id).', 'develogic'); ?></p>
                
                <div style="overflow-x: auto; margin-top: 15px;">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th style="width: 80px;"><?php _e('ID', 'develogic'); ?></th>
                                <th><?php _e('Nazwa inwestycji', 'develogic'); ?></th>
                                <th style="width: 120px;"><?php _e('Status', 'develogic'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($investments as $investment): ?>
                                <?php 
                                $investment_id = !empty($investment['ID']) ? absint($investment['ID']) : 0;
                                $is_selected = in_array($investment_id, $selected_investments);
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html($investment_id); ?></strong></td>
                                    <td><?php echo esc_html($investment['Name']); ?></td>
                                    <td>
                                        <?php if ($is_selected): ?>
                                            <span style="color: #28a745; font-weight: bold;">✓ <?php _e('Wybrana', 'develogic'); ?></span>
                                        <?php else: ?>
                                            <span style="color: #6c757d;"><?php _e('Niewybrana', 'develogic'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <p style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-left: 4px solid #2271b1;">
                    <strong><?php _e('Przykład użycia w shortcode:', 'develogic'); ?></strong><br>
                    <code>[develogic_apartments_list investment_id="123"]</code> lub <code>[develogic_apartments_list investment="Nazwa Inwestycji"]</code>
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Status Section -->
            <div class="card">
                <h2><?php _e('Status synchronizacji', 'develogic'); ?></h2>
                
                <table class="widefat">
                    <tbody>
                        <tr>
                            <td><strong><?php _e('Liczba lokali w bazie:', 'develogic'); ?></strong></td>
                            <td><?php echo absint($locals_count->publish); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Status:', 'develogic'); ?></strong></td>
                            <td>
                                <?php if ($is_running): ?>
                                    <span style="color: orange;">⏳ <?php _e('Synchronizacja w trakcie...', 'develogic'); ?></span>
                                <?php else: ?>
                                    <span style="color: green;">✓ <?php _e('Gotowy', 'develogic'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($last_sync)): ?>
                        <tr>
                            <td><strong><?php _e('Ostatnia synchronizacja:', 'develogic'); ?></strong></td>
                            <td><?php echo esc_html($last_sync['time']); ?></td>
                        </tr>
                        <?php if (!empty($last_sync['stats'])): ?>
                        <tr>
                            <td><strong><?php _e('Wynik:', 'develogic'); ?></strong></td>
                            <td>
                                <?php
                                printf(
                                    __('%d dodanych, %d zaktualizowanych, %d błędów (czas: %s sek)', 'develogic'),
                                    $last_sync['stats']['added'],
                                    $last_sync['stats']['updated'],
                                    $last_sync['stats']['errors'],
                                    $last_sync['stats']['time']
                                );
                                ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Actions Section -->
            <div class="card">
                <h2><?php _e('Akcje', 'develogic'); ?></h2>
                
                <?php if ($api_configured): ?>
                    <?php if (!$has_investments): ?>
                        <p><?php _e('Najpierw pobierz listę inwestycji z API:', 'develogic'); ?></p>
                        <p>
                            <a href="<?php echo wp_nonce_url(
                                admin_url('admin-post.php?action=develogic_fetch_investments'),
                                'develogic_fetch_investments',
                                'develogic_fetch_nonce'
                            ); ?>" class="button button-primary">
                                <?php _e('Pobierz listę inwestycji z API', 'develogic'); ?>
                            </a>
                        </p>
                    <?php elseif (!$has_selected_investments || $change_investments): ?>
                        <h3><?php _e('Wybierz inwestycje do synchronizacji:', 'develogic'); ?></h3>
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-bottom: 20px;">
                            <input type="hidden" name="action" value="develogic_save_investments">
                            <?php wp_nonce_field('develogic_save_investments', 'develogic_save_investments_nonce'); ?>
                            
                            <fieldset style="margin: 15px 0;">
                                <?php foreach ($investments as $investment): ?>
                                    <?php 
                                    $investment_id = !empty($investment['ID']) ? absint($investment['ID']) : 0;
                                    $checked = in_array($investment_id, $selected_investments);
                                    ?>
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="checkbox" name="investments[]" value="<?php echo esc_attr($investment_id); ?>" <?php checked($checked, true); ?>>
                                        <?php echo esc_html($investment['Name']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                            
                            <?php submit_button(__('Zapisz wybór', 'develogic'), 'primary', 'submit', false); ?>
                            <p class="description"><?php _e('Zaznacz inwestycje które mają być synchronizowane. Jeśli nic nie zostanie zaznaczone, synchronizowane będą wszystkie inwestycje.', 'develogic'); ?></p>
                        </form>
                    <?php else: ?>
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block; margin-right: 10px;">
                            <input type="hidden" name="action" value="develogic_manual_sync">
                            <?php wp_nonce_field('develogic_manual_sync', 'develogic_sync_nonce'); ?>
                            <?php 
                            $disabled_attr = ($is_running || $bg_active) ? array('disabled' => 'disabled') : array();
                            submit_button(__('Synchronizuj teraz (w tle)', 'develogic'), 'primary', 'submit', false, $disabled_attr); 
                            ?>
                            <p class="description"><?php _e('Uruchamia synchronizację po stronie serwera. Przeglądarka nie czeka, więc nie ma timeoutu — stronę można zamknąć.', 'develogic'); ?></p>
                        </form>
                        
                        <?php if ($is_running): ?>
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block; margin-right: 10px;">
                            <input type="hidden" name="action" value="develogic_unlock_sync">
                            <?php wp_nonce_field('develogic_unlock_sync', 'develogic_unlock_nonce'); ?>
                            <?php submit_button(__('🔓 Odblokuj synchronizację', 'develogic'), 'secondary', 'submit', false); ?>
                            <p class="description"><?php _e('Użyj jeśli synchronizacja się zablokowała (timeout, błąd itp.)', 'develogic'); ?></p>
                        </form>
                        <?php endif; ?>
                        
                        <p style="margin-top: 15px;">
                            <a href="<?php echo admin_url('admin.php?page=develogic-sync&change_investments=1'); ?>" class="button button-secondary">
                                <?php _e('Zmień wybór inwestycji', 'develogic'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <p><?php _e('Najpierw skonfiguruj API w Ustawieniach.', 'develogic'); ?></p>
                <?php endif; ?>
                
                <hr style="margin: 30px 0;">
                
                <h3><?php _e('Operacje zaawansowane', 'develogic'); ?></h3>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block; margin-right: 10px;" onsubmit="return confirm('<?php esc_attr_e('To usunie wszystkie zdjęcia i PDF projekcji. Po tej operacji musisz uruchomić pełną synchronizację. Czy na pewno chcesz kontynuować?', 'develogic'); ?>');">
                    <input type="hidden" name="action" value="develogic_force_resync_projections">
                    <?php wp_nonce_field('develogic_force_resync_projections', 'develogic_resync_nonce'); ?>
                    <?php submit_button(__('🔄 Wymuś re-synchronizację projekcji', 'develogic'), 'secondary', 'submit', false); ?>
                    <p class="description" style="max-width: 400px;">
                        <?php _e('Usuwa wszystkie załączniki projekcji (zdjęcia i PDF) i wymusza ponowne pobranie podczas następnej synchronizacji. Użyj gdy chcesz zaktualizować wszystkie pliki projekcji.', 'develogic'); ?>
                    </p>
                </form>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block;" onsubmit="return confirm('<?php esc_attr_e('Czy na pewno chcesz usunąć wszystkie lokale z bazy? Ta operacja jest nieodwracalna!', 'develogic'); ?>');">
                    <input type="hidden" name="action" value="develogic_clear_locals">
                    <?php wp_nonce_field('develogic_clear_locals', 'develogic_clear_nonce'); ?>
                    <?php submit_button(__('Wyczyść wszystkie lokale', 'develogic'), 'secondary', 'submit', false); ?>
                    <p class="description" style="max-width: 400px;">
                        <?php _e('Usuwa wszystkie mieszkania z bazy danych. Użyj ostrożnie!', 'develogic'); ?>
                    </p>
                </form>
            </div>
            
            <!-- Endpoint Section -->
            <div class="card">
                <h2><?php _e('Konfiguracja zewnętrznego CRON', 'develogic'); ?></h2>
                
                <p><?php _e('Skonfiguruj zewnętrzny CRON (np. cron-job.org) aby wywoływał synchronizację co 1 minutę:', 'develogic'); ?></p>
                
                <h3><?php _e('Endpoint:', 'develogic'); ?></h3>
                <pre style="background: #f5f5f5; padding: 15px; overflow-x: auto;"><?php echo esc_html(rest_url('develogic/v1/sync')); ?></pre>
                
                <h3><?php _e('Metoda:', 'develogic'); ?></h3>
                <pre style="background: #f5f5f5; padding: 15px;">POST</pre>
                
                <h3><?php _e('Authorization Header:', 'develogic'); ?></h3>
                <pre style="background: #f5f5f5; padding: 15px; overflow-x: auto;">Authorization: Bearer <?php echo esc_html($secret_key); ?></pre>
                
                <h3><?php _e('Przykład CURL:', 'develogic'); ?></h3>
                <pre style="background: #282c34; color: #abb2bf; padding: 15px; overflow-x: auto; border-radius: 4px;">curl -X POST "<?php echo esc_html(rest_url('develogic/v1/sync')); ?>" \
  -H "Authorization: Bearer <?php echo esc_html($secret_key); ?>"</pre>
                
                <h3><?php _e('Sprawdzenie statusu (GET):', 'develogic'); ?></h3>
                <pre style="background: #282c34; color: #abb2bf; padding: 15px; overflow-x: auto; border-radius: 4px;">curl "<?php echo esc_html(rest_url('develogic/v1/sync/status')); ?>" \
  -H "Authorization: Bearer <?php echo esc_html($secret_key); ?>"</pre>
                
                <p>
                    <strong><?php _e('Ważne:', 'develogic'); ?></strong>
                    <?php _e('Secret key jest generowany automatycznie przy aktywacji wtyczki. Możesz go zmienić w ustawieniach.', 'develogic'); ?>
                </p>
            </div>
            
            <!-- Log Section -->
            <?php if (!empty($sync_log)): ?>
            <div class="card">
                <h2><?php _e('Log synchronizacji (ostatnie 20 wpisów)', 'develogic'); ?></h2>
                
                <div style="overflow-x: auto;">
                    <table class="widefat striped" style="table-layout: fixed; width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 150px;"><?php _e('Czas', 'develogic'); ?></th>
                                <th style="width: 100px;"><?php _e('Poziom', 'develogic'); ?></th>
                                <th style="width: auto;"><?php _e('Wiadomość', 'develogic'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse(array_slice($sync_log, -20)) as $entry): ?>
                            <tr>
                                <td style="white-space: nowrap;"><?php echo esc_html($entry['time']); ?></td>
                                <td style="white-space: nowrap;">
                                    <?php
                                    $level_colors = array(
                                        'success' => '#28a745',
                                        'error' => '#dc3545',
                                        'warning' => '#ffc107',
                                    );
                                    $color = isset($level_colors[$entry['level']]) ? $level_colors[$entry['level']] : '#6c757d';
                                    ?>
                                    <span style="color: <?php echo $color; ?>; font-weight: bold;">
                                        <?php echo esc_html(strtoupper($entry['level'])); ?>
                                    </span>
                                </td>
                                <td style="word-break: break-word; overflow-wrap: break-word;"><?php echo esc_html($entry['message']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Help Section -->
            <div class="card">
                <h2><?php _e('Pomoc', 'develogic'); ?></h2>
                
                <h3><?php _e('Jak to działa?', 'develogic'); ?></h3>
                <ol>
                    <li><?php _e('Zewnętrzny CRON wywołuje endpoint co 1 minutę', 'develogic'); ?></li>
                    <li><?php _e('Wtyczka pobiera dane z Develogic API', 'develogic'); ?></li>
                    <li><?php _e('Dane zapisywane są w bazie WordPress jako Custom Post Type', 'develogic'); ?></li>
                    <li><?php _e('Shortcody wyświetlają dane z lokalnej bazy (szybko!)', 'develogic'); ?></li>
                </ol>
                
                <h3><?php _e('Jak skonfigurować CRON na cron-job.org?', 'develogic'); ?></h3>
                <ol>
                    <li><?php _e('Zarejestruj się na https://cron-job.org', 'develogic'); ?></li>
                    <li><?php _e('Utwórz nowy cronjob', 'develogic'); ?></li>
                    <li><?php printf(__('URL: %s', 'develogic'), '<code>' . esc_html(rest_url('develogic/v1/sync')) . '</code>'); ?></li>
                    <li><?php _e('Request method: POST', 'develogic'); ?></li>
                    <li><?php printf(__('Headers → Add header: Name: %s, Value: %s', 'develogic'), '<code>Authorization</code>', '<code>Bearer ' . esc_html($secret_key) . '</code>'); ?></li>
                    <li><?php _e('Interval: Every minute (* * * * *)', 'develogic'); ?></li>
                    <li><?php _e('Save i uruchom!', 'develogic'); ?></li>
                </ol>
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Handle manual sync action
     */
    public function handle_manual_sync() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Brak uprawnień', 'develogic'));
        }
        
        check_admin_referer('develogic_manual_sync', 'develogic_sync_nonce');

        // Synchronizacja pełnej bazy trwa kilka minut (ostatnio 354 s), więc
        // uruchamiana wprost z przeglądarki kończyła się timeoutem i błędem 500
        // — mimo że w tle i tak dobiegała końca. Teraz odpalamy ją osobnym
        // procesem, a przeglądarka dostaje odpowiedź natychmiast.
        if (get_transient('develogic_sync_lock')) {
            wp_redirect(add_query_arg(array(
                'page' => 'develogic-sync',
                'sync_result' => 'error',
                'sync_message' => urlencode(__('Synchronizacja jest już w trakcie.', 'develogic')),
            ), admin_url('admin.php')));
            exit;
        }

        $this->queue_background_sync();

        wp_redirect(add_query_arg(array(
            'page' => 'develogic-sync',
            'sync_result' => 'queued',
            'sync_message' => urlencode(__('Synchronizacja została uruchomiona w tle. Postęp zobaczysz poniżej — tej strony nie trzeba trzymać otwartej.', 'develogic')),
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Kolejkuje synchronizację i startuje proces roboczy.
     *
     * Lock trzyma 30 minut (pełny przebieg to kilka minut), żeby równoległe
     * kliknięcie nie odpaliło drugiej synchronizacji. Gdyby worker padł,
     * lock wygaśnie sam, a w panelu jest przycisk odblokowania.
     */
    private function queue_background_sync() {
        set_transient('develogic_sync_lock', true, 30 * MINUTE_IN_SECONDS);

        update_option('develogic_sync_bg', array(
            'status'    => 'queued',
            'queued_at' => time(),
        ), false);

        $token = wp_generate_password(32, false);
        set_transient('develogic_sync_token', $token, 10 * MINUTE_IN_SECONDS);

        $url = add_query_arg(array(
            'action' => 'develogic_run_background_sync',
            'token'  => $token,
        ), admin_url('admin-post.php'));

        // blocking=false: nie czekamy na odpowiedź, żądanie ma tylko wystartować
        // drugi proces PHP. Timeout musi być niezerowy, inaczej część serwerów
        // zrywa połączenie zanim PHP zdąży wejść w handler.
        wp_remote_post($url, array(
            'timeout'   => 1,
            'blocking'  => false,
            'sslverify' => false,
            'cookies'   => array(),
        ));

        // Zapas na hostingach blokujących loopback.
        if (!wp_next_scheduled('develogic_background_sync_fallback')) {
            wp_schedule_single_event(time() + 90, 'develogic_background_sync_fallback');
        }
    }

    /**
     * Proces roboczy synchronizacji w tle.
     */
    public function handle_run_background_sync() {
        $token = isset($_REQUEST['token']) ? sanitize_text_field(wp_unslash($_REQUEST['token'])) : '';
        $expected = get_transient('develogic_sync_token');

        if (empty($expected) || empty($token) || !hash_equals($expected, $token)) {
            status_header(403);
            exit;
        }
        // Token jednorazowy — drugie wywołanie tym samym już nie przejdzie.
        delete_transient('develogic_sync_token');

        $this->run_sync_detached();
        exit;
    }

    /**
     * Zapasowe uruchomienie z WP-Cron, gdy loopback nie wystartował workera.
     */
    public function run_queued_sync_fallback() {
        $state = get_option('develogic_sync_bg', array());
        if (!is_array($state) || !isset($state['status']) || $state['status'] !== 'queued') {
            return; // worker już ruszył albo nic nie czeka w kolejce
        }
        $this->run_sync_detached();
    }

    /**
     * Wykonuje synchronizację w oderwaniu od żądania przeglądarki.
     */
    private function run_sync_detached() {
        // Klient (loopback) rozłącza się od razu — bez tego PHP przerwałoby
        // pracę w połowie. Limit czasu zdejmujemy, bo przebieg trwa minuty.
        ignore_user_abort(true);
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        @ini_set('max_execution_time', '0');

        $state = get_option('develogic_sync_bg', array());
        if (!is_array($state)) {
            $state = array();
        }
        $state['status'] = 'running';
        $state['started_at'] = time();
        update_option('develogic_sync_bg', $state, false);

        // Odśwież lock — liczymy czas od faktycznego startu pracy.
        set_transient('develogic_sync_lock', true, 30 * MINUTE_IN_SECONDS);

        $sync = new Develogic_Sync();
        $result = $sync->sync_locals();

        delete_transient('develogic_sync_lock');

        $state['status'] = !empty($result['success']) ? 'done' : 'error';
        $state['finished_at'] = time();
        $state['result'] = $result;
        update_option('develogic_sync_bg', $state, false);

        error_log(sprintf(
            '[Develogic Sync w tle] Zakończono: %s',
            isset($result['message']) ? $result['message'] : ''
        ));
    }
    
    /**
     * Handle clear locals action
     */
    public function handle_clear_locals() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Brak uprawnień', 'develogic'));
        }
        
        check_admin_referer('develogic_clear_locals', 'develogic_clear_nonce');
        
        $query = new WP_Query(array(
            'post_type' => 'develogic_local',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids',
        ));
        
        $deleted = 0;
        foreach ($query->posts as $post_id) {
            if (wp_delete_post($post_id, true)) {
                $deleted++;
            }
        }
        
        wp_redirect(add_query_arg(array(
            'page' => 'develogic-sync',
            'cleared' => '1',
            'deleted_count' => $deleted,
        ), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Handle fetch investments from API
     */
    public function handle_fetch_investments() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Brak uprawnień', 'develogic'));
        }
        
        check_admin_referer('develogic_fetch_investments', 'develogic_fetch_nonce');
        
        // Fetch investments from API
        $investments = develogic()->api_client->get_investments();
        
        if (is_wp_error($investments)) {
            wp_redirect(add_query_arg(array(
                'page' => 'develogic-sync',
                'fetch_investments' => 'error',
                'error_message' => urlencode($investments->get_error_message()),
            ), admin_url('admin.php')));
            exit;
        }
        
        if (empty($investments) || !is_array($investments)) {
            wp_redirect(add_query_arg(array(
                'page' => 'develogic-sync',
                'fetch_investments' => 'empty',
            ), admin_url('admin.php')));
            exit;
        }
        
        // Save investments to taxonomy (same logic as sync_investments)
        $count = 0;
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
                $count++;
            }
        }
        
        wp_redirect(add_query_arg(array(
            'page' => 'develogic-sync',
            'fetch_investments' => 'success',
            'count' => $count,
        ), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Handle save investments selection
     */
    public function handle_save_investments() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Brak uprawnień', 'develogic'));
        }
        
        check_admin_referer('develogic_save_investments', 'develogic_save_investments_nonce');
        
        // Get current settings
        $settings = get_option('develogic_settings', array());
        
        // Save selected investments
        $selected = isset($_POST['investments']) && is_array($_POST['investments'])
            ? array_map('absint', $_POST['investments'])
            : array();
        
        $settings['sync_investments'] = $selected;
        update_option('develogic_settings', $settings);
        
        wp_redirect(add_query_arg(array(
            'page' => 'develogic-sync',
            'investments_saved' => '1',
        ), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Handle unlock sync action
     */
    public function handle_unlock_sync() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Brak uprawnień', 'develogic'));
        }
        
        check_admin_referer('develogic_unlock_sync', 'develogic_unlock_nonce');
        
        // Delete sync lock transient
        delete_transient('develogic_sync_lock');
        delete_transient('develogic_sync_token');
        // Skasuj też stan synchronizacji w tle — inaczej panel dalej pokazywałby
        // "w toku" dla przebiegu, który już nie istnieje.
        delete_option('develogic_sync_bg');
        
        wp_redirect(add_query_arg(array(
            'page' => 'develogic-sync',
            'unlocked' => '1',
        ), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Handle force resync projections action
     */
    public function handle_force_resync_projections() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Brak uprawnień', 'develogic'));
        }
        
        check_admin_referer('develogic_force_resync_projections', 'develogic_resync_nonce');
        
        // Get all locals
        $args = array(
            'post_type' => 'develogic_local',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
        );
        
        $query = new WP_Query($args);
        $post_ids = $query->posts;
        
        $deleted_count = 0;
        $local_count = 0;
        
        foreach ($post_ids as $post_id) {
            // Find and delete all projection attachments for this local
            $attachments = get_posts(array(
                'post_type' => 'attachment',
                'post_parent' => $post_id,
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => array(
                    array(
                        'key' => 'develogic_projection_id',
                        'compare' => 'EXISTS',
                    ),
                ),
            ));
            
            $local_deleted = 0;
            foreach ($attachments as $attachment_id) {
                // Delete attachment and its files
                if (wp_delete_attachment($attachment_id, true)) {
                    $deleted_count++;
                    $local_deleted++;
                }
            }
            
            if ($local_deleted > 0) {
                $local_count++;
            }
        }
        
        // Log the operation
        $log = get_option('develogic_sync_log', array());
        $log[] = array(
            'time' => current_time('mysql'),
            'level' => 'info',
            'message' => sprintf(
                __('Wymuszono re-synchronizację projekcji: usunięto %d attachmentów z %d mieszkań. Uruchom synchronizację aby pobrać pliki ponownie.', 'develogic'),
                $deleted_count,
                $local_count
            ),
        );
        $log = array_slice($log, -50);
        update_option('develogic_sync_log', $log);
        
        wp_redirect(add_query_arg(array(
            'page' => 'develogic-sync',
            'resync_projections' => 'success',
            'deleted_count' => $deleted_count,
            'local_count' => $local_count,
        ), admin_url('admin.php')));
        exit;
    }
}

