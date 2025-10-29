<?php
/**
 * Template: Local Types Chip View
 *
 * @package Develogic
 * @var array $local_types
 * @var array $atts
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="develogic-local-types-chips">
    <?php if (empty($local_types)): ?>
        <p class="no-results"><?php _e('Brak typów lokali.', 'develogic'); ?></p>
    <?php else: ?>
        <?php foreach ($local_types as $type): ?>
            <?php if ($atts['link_to_offers'] === 'true'): ?>
                <a href="<?php echo esc_url(add_query_arg('local_type_id', $type['ID'], get_permalink())); ?>" 
                   class="type-chip">
                    <?php echo esc_html($type['Name']); ?>
                </a>
            <?php else: ?>
                <span class="type-chip">
                    <?php echo esc_html($type['Name']); ?>
                </span>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

