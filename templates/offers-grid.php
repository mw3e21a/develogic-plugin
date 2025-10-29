<?php
/**
 * Template: Offers Grid View
 *
 * @package Develogic
 * @var array $locals
 * @var array $atts
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="develogic-offers-grid">
    <?php if (empty($locals)): ?>
        <p class="no-results"><?php _e('Brak ofert spełniających kryteria.', 'develogic'); ?></p>
    <?php else: ?>
        <?php foreach ($locals as $local): ?>
            <div class="offer-card">
                <?php if (!empty($local['projections'][0])): 
                    // Use WordPress-hosted image if available, fallback to API URL
                    $proj_url = !empty($local['projections'][0]['wordpress_url']) ? $local['projections'][0]['wordpress_url'] : $local['projections'][0]['uri'];
                ?>
                    <div class="card-image">
                        <img src="<?php echo esc_url($proj_url); ?>" 
                             alt="<?php echo esc_attr($local['number']); ?>">
                    </div>
                <?php endif; ?>
                
                <div class="card-content">
                    <h3><?php echo esc_html($local['number']); ?></h3>
                    
                    <div class="card-meta">
                        <span class="status status-<?php echo esc_attr(Develogic_Data_Formatter::get_status_class($local['status'])); ?>">
                            <?php echo esc_html($local['status']); ?>
                        </span>
                    </div>
                    
                    <ul class="card-details">
                        <?php if (!empty($local['rooms'])): ?>
                            <li><strong><?php _e('Pokoje:', 'develogic'); ?></strong> <?php echo absint($local['rooms']); ?></li>
                        <?php endif; ?>
                        
                        <?php if (!empty($local['area'])): ?>
                            <li><strong><?php _e('Powierzchnia:', 'develogic'); ?></strong> <?php echo Develogic_Data_Formatter::format_area($local['area']); ?></li>
                        <?php endif; ?>
                        
                        <?php if (!empty($local['floor'])): ?>
                            <li><strong><?php _e('Piętro:', 'develogic'); ?></strong> <?php echo esc_html(Develogic_Data_Formatter::format_floor($local['floor'])); ?></li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="card-price">
                        <div class="price-total"><?php echo Develogic_Data_Formatter::format_price($local['priceGross']); ?></div>
                        <div class="price-m2"><?php echo Develogic_Data_Formatter::format_price($local['priceGrossm2']); ?> / m²</div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

