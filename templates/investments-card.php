<?php
/**
 * Template: Investments Card View
 *
 * @package Develogic
 * @var array $investments
 * @var array $atts
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="develogic-investments-grid">
    <?php if (empty($investments)): ?>
        <p class="no-results"><?php _e('Brak inwestycji.', 'develogic'); ?></p>
    <?php else: ?>
        <?php foreach ($investments as $investment): ?>
            <div class="investment-card">
                <h3><?php echo esc_html($investment['Name']); ?></h3>
                
                <?php if ($atts['link_to_offers'] === 'true'): ?>
                    <a href="<?php echo esc_url(add_query_arg('investment_id', $investment['ID'], get_permalink())); ?>" 
                       class="btn btn-primary">
                        <?php _e('Zobacz oferty', 'develogic'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

