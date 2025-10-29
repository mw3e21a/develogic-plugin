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
        add_action('admin_post_develogic_clear_locals', array($this, 'handle_clear_locals'));
        add_action('admin_post_develogic_unlock_sync', array($this, 'handle_unlock_sync'));
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
        $secret_key = develogic()->get_setting('sync_secret_key');
        
        // Debug
        $api_base_url = develogic()->get_setting('api_base_url');
        $api_key = develogic()->get_setting('api_key');
        $api_configured = !empty($api_base_url) && !empty($api_key);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Synchronizacja z Develogic API', 'develogic'); ?></h1>
            
            <!-- DEBUG INFO -->
            <div class="notice notice-info">
                <p><strong>🔍 DEBUG:</strong></p>
                <ul style="margin-left: 20px;">
                    <li>api_base_url: <code><?php echo esc_html($api_base_url); ?></code></li>
                    <li>api_key: <code><?php echo esc_html(substr($api_key, 0, 5)); ?>...</code></li>
                    <li>api_configured: <strong style="color: <?php echo $api_configured ? 'green' : 'red'; ?>;"><?php echo $api_configured ? 'TRUE ✅' : 'FALSE ❌'; ?></strong></li>
                    <li>is_running: <strong style="color: <?php echo $is_running ? 'orange' : 'green'; ?>;"><?php echo $is_running ? 'TRUE (ZABLOKOWANE) 🔒' : 'FALSE (ODBLOKOWANE) 🔓'; ?></strong></li>
                </ul>
            </div>
            
            <?php if (!$api_configured): ?>
                <div class="notice notice-warning">
                    <p><?php _e('API nie zostało skonfigurowane. Przejdź do Ustawień i wprowadź URL oraz klucz API.', 'develogic'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['unlocked']) && $_GET['unlocked'] == '1'): ?>
                <div class="notice notice-success is-dismissible">
                    <p>✅ <?php _e('Synchronizacja została odblokowana. Możesz teraz uruchomić nową synchronizację.', 'develogic'); ?></p>
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
                
                <p><strong>DEBUG:</strong> api_configured = <?php echo $api_configured ? 'TRUE' : 'FALSE'; ?> | is_running = <?php echo $is_running ? 'TRUE' : 'FALSE'; ?></p>
                
                <?php if ($api_configured): ?>
                    <p style="color: green;">✅ Warunek $api_configured SPEŁNIONY - przyciski powinny być widoczne</p>
                    
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block; margin-right: 10px;">
                        <input type="hidden" name="action" value="develogic_manual_sync">
                        <?php wp_nonce_field('develogic_manual_sync', 'develogic_sync_nonce'); ?>
                        <?php 
                        $disabled_attr = $is_running ? array('disabled' => 'disabled') : array();
                        submit_button(__('Synchronizuj teraz (ręcznie)', 'develogic'), 'primary', 'submit', false, $disabled_attr); 
                        ?>
                        <p class="description">disabled = <?php echo $is_running ? 'TRUE (bo is_running=TRUE)' : 'FALSE'; ?></p>
                    </form>
                    
                    <?php if ($is_running): ?>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block; margin-right: 10px;">
                        <input type="hidden" name="action" value="develogic_unlock_sync">
                        <?php wp_nonce_field('develogic_unlock_sync', 'develogic_unlock_nonce'); ?>
                        <?php submit_button(__('🔓 Odblokuj synchronizację', 'develogic'), 'secondary', 'submit', false); ?>
                        <p class="description"><?php _e('Użyj jeśli synchronizacja się zablokowała (timeout, błąd itp.)', 'develogic'); ?></p>
                    </form>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="color: red;">❌ Warunek $api_configured NIE SPEŁNIONY - przyciski NIE będą widoczne</p>
                <?php endif; ?>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block;" onsubmit="return confirm('<?php esc_attr_e('Czy na pewno chcesz usunąć wszystkie lokale z bazy? Ta operacja jest nieodwracalna!', 'develogic'); ?>');">
                    <input type="hidden" name="action" value="develogic_clear_locals">
                    <?php wp_nonce_field('develogic_clear_locals', 'develogic_clear_nonce'); ?>
                    <?php submit_button(__('Wyczyść wszystkie lokale', 'develogic'), 'secondary', 'submit', false, array('disabled' => $is_running)); ?>
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
                
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Czas', 'develogic'); ?></th>
                            <th><?php _e('Poziom', 'develogic'); ?></th>
                            <th><?php _e('Wiadomość', 'develogic'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse(array_slice($sync_log, -20)) as $entry): ?>
                        <tr>
                            <td><?php echo esc_html($entry['time']); ?></td>
                            <td>
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
                            <td><?php echo esc_html($entry['message']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
        
        $sync = new Develogic_Sync();
        $result = $sync->sync_locals();
        
        $message_type = $result['success'] ? 'success' : 'error';
        
        wp_redirect(add_query_arg(array(
            'page' => 'develogic-sync',
            'sync_result' => $message_type,
            'sync_message' => urlencode($result['message']),
        ), admin_url('admin.php')));
        exit;
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
     * Handle unlock sync action
     */
    public function handle_unlock_sync() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Brak uprawnień', 'develogic'));
        }
        
        check_admin_referer('develogic_unlock_sync', 'develogic_unlock_nonce');
        
        // Delete sync lock transient
        delete_transient('develogic_sync_lock');
        
        wp_redirect(add_query_arg(array(
            'page' => 'develogic-sync',
            'unlocked' => '1',
        ), admin_url('admin.php')));
        exit;
    }
}

