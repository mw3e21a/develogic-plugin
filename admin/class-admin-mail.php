<?php
/**
 * Develogic Admin – diagnostyka maili konfiguratora
 *
 * @package Develogic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Develogic_Admin_Mail
 *
 * Pokazuje w panelu, czy wiadomości z konfiguratora wychodzą i czy miały
 * załącznik. Bez tego jedynym źródłem wiedzy był error_log na serwerze, do
 * którego nie każdy ma dostęp.
 */
class Develogic_Admin_Mail {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 21);
        add_action('admin_post_develogic_test_mail', array($this, 'handle_test_mail'));
        add_action('admin_post_develogic_clear_mail_log', array($this, 'handle_clear_log'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'develogic',
            __('Diagnostyka maili', 'develogic'),
            __('Diagnostyka maili', 'develogic'),
            'manage_options',
            'develogic-mail',
            array($this, 'render_page')
        );
    }

    /**
     * Wyślij testową wiadomość z załącznikiem.
     */
    public function handle_test_mail() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Brak uprawnień', 'develogic'));
        }
        check_admin_referer('develogic_test_mail');

        $to = isset($_POST['test_email']) ? sanitize_email(wp_unslash($_POST['test_email'])) : '';
        if (!is_email($to)) {
            wp_safe_redirect(add_query_arg('develogic_mail', 'bad_email', $this->page_url()));
            exit;
        }

        $result = Develogic_REST_API::send_test_quote_mail($to);

        wp_safe_redirect(add_query_arg(
            'develogic_mail',
            $result['sent'] ? 'sent' : 'failed',
            $this->page_url()
        ));
        exit;
    }

    /**
     * Wyczyść log.
     */
    public function handle_clear_log() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Brak uprawnień', 'develogic'));
        }
        check_admin_referer('develogic_clear_mail_log');

        delete_option(Develogic_REST_API::MAIL_LOG_OPTION);
        wp_safe_redirect($this->page_url());
        exit;
    }

    /**
     * Adres strony diagnostyki.
     *
     * @return string
     */
    private function page_url() {
        return admin_url('admin.php?page=develogic-mail');
    }

    /**
     * Render page
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $log = get_option(Develogic_REST_API::MAIL_LOG_OPTION, array());
        if (!is_array($log)) {
            $log = array();
        }

        $contact_email = develogic()->get_setting('contact_email', '');
        if (empty($contact_email)) {
            $contact_email = get_option('admin_email');
        }

        $notice = isset($_GET['develogic_mail']) ? sanitize_key(wp_unslash($_GET['develogic_mail'])) : '';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Diagnostyka maili konfiguratora', 'develogic'); ?></h1>

            <?php if ($notice === 'sent'): ?>
                <div class="notice notice-success"><p>
                    <?php esc_html_e('Wiadomość testowa została przekazana do wysyłki. Sprawdź skrzynkę — także folder SPAM — i czy jest w niej załącznik CSV.', 'develogic'); ?>
                </p></div>
            <?php elseif ($notice === 'failed'): ?>
                <div class="notice notice-error"><p>
                    <?php esc_html_e('WordPress nie zdołał wysłać wiadomości. Powód znajdziesz w tabeli poniżej, w kolumnie „Błąd".', 'develogic'); ?>
                </p></div>
            <?php elseif ($notice === 'bad_email'): ?>
                <div class="notice notice-error"><p><?php esc_html_e('Nieprawidłowy adres email.', 'develogic'); ?></p></div>
            <?php endif; ?>

            <div class="card" style="max-width: 800px;">
                <h2><?php esc_html_e('Wyślij wiadomość testową', 'develogic'); ?></h2>
                <p><?php esc_html_e('Wysyła wiadomość tą samą ścieżką co konfigurator, z takim samym załącznikiem CSV. Jeśli dojdzie z plikiem — wtyczka działa poprawnie, a problem leży w danych zgłoszenia. Jeśli dojdzie bez pliku — załącznik gubi wtyczka SMTP, serwer pocztowy albo skaner antywirusowy.', 'develogic'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('develogic_test_mail'); ?>
                    <input type="hidden" name="action" value="develogic_test_mail">
                    <p>
                        <label for="test_email"><?php esc_html_e('Wyślij na adres:', 'develogic'); ?></label><br>
                        <input type="email" id="test_email" name="test_email" class="regular-text"
                               value="<?php echo esc_attr($contact_email); ?>" required>
                    </p>
                    <p>
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e('Wyślij test', 'develogic'); ?>
                        </button>
                    </p>
                </form>
                <p class="description">
                    <?php
                    printf(
                        /* translators: %s: adres odbiorcy zgłoszeń */
                        esc_html__('Zgłoszenia z konfiguratora trafiają na: %s', 'develogic'),
                        '<code>' . esc_html($contact_email) . '</code>'
                    );
                    ?>
                </p>
            </div>

            <h2><?php esc_html_e('Ostatnie wysyłki', 'develogic'); ?></h2>
            <?php if (empty($log)): ?>
                <p><?php esc_html_e('Brak wpisów. Pojawią się tutaj po pierwszym zgłoszeniu z konfiguratora lub po wysłaniu testu.', 'develogic'); ?></p>
            <?php else: ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Data', 'develogic'); ?></th>
                            <th><?php esc_html_e('Odbiorca', 'develogic'); ?></th>
                            <th><?php esc_html_e('Klient', 'develogic'); ?></th>
                            <th><?php esc_html_e('Skąd', 'develogic'); ?></th>
                            <th><?php esc_html_e('Załącznik', 'develogic'); ?></th>
                            <th><?php esc_html_e('Wysłano', 'develogic'); ?></th>
                            <th><?php esc_html_e('Błąd', 'develogic'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($log as $entry): ?>
                        <?php
                        $attachment = isset($entry['attachment']) ? $entry['attachment'] : '';
                        $size = isset($entry['size']) ? (int) $entry['size'] : 0;
                        $sent = !empty($entry['sent']);
                        ?>
                        <tr>
                            <td><?php echo esc_html(isset($entry['time']) ? $entry['time'] : '-'); ?>
                                <?php if (!empty($entry['test'])): ?><em>(test)</em><?php endif; ?>
                            </td>
                            <td><?php echo esc_html(isset($entry['to']) ? $entry['to'] : '-'); ?></td>
                            <td><?php echo esc_html(isset($entry['client']) ? $entry['client'] : '-'); ?></td>
                            <td><?php echo esc_html(isset($entry['source']) ? $entry['source'] : '-'); ?></td>
                            <td>
                                <?php if ($attachment !== ''): ?>
                                    <?php echo esc_html($attachment); ?>
                                    <small>(<?php echo esc_html(size_format($size)); ?>)</small>
                                <?php else: ?>
                                    <span style="color:#b32d2e;"><?php esc_html_e('BRAK', 'develogic'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($sent): ?>
                                    <span style="color:#008a20;">✔</span>
                                <?php else: ?>
                                    <span style="color:#b32d2e;">✘</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(isset($entry['error']) ? $entry['error'] : ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px;">
                    <?php wp_nonce_field('develogic_clear_mail_log'); ?>
                    <input type="hidden" name="action" value="develogic_clear_mail_log">
                    <button type="submit" class="button"><?php esc_html_e('Wyczyść log', 'develogic'); ?></button>
                </form>
            <?php endif; ?>

            <h2><?php esc_html_e('Jak czytać tabelę', 'develogic'); ?></h2>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><?php esc_html_e('Załącznik z rozmiarem + „Wysłano ✔" — wtyczka zrobiła swoje. Jeśli mail dotarł bez pliku, gubi go coś dalej: wtyczka SMTP, serwer pocztowy lub antywirus.', 'develogic'); ?></li>
                <li><?php esc_html_e('Załącznik „BRAK" — nie udało się zapisać pliku tymczasowego (uprawnienia katalogu). Zestawienie poszło wtedy w treści wiadomości.', 'develogic'); ?></li>
                <li><?php esc_html_e('„Wysłano ✘" — WordPress w ogóle nie oddał wiadomości serwerowi pocztowemu. Powód jest w kolumnie „Błąd".', 'develogic'); ?></li>
                <li><?php esc_html_e('Brak nowych wpisów mimo wysłania formularza — zgłoszenie nie dotarło do wtyczki (błąd JavaScript lub blokada po stronie zabezpieczeń).', 'develogic'); ?></li>
            </ul>
        </div>
        <?php
    }
}
