<?php
/**
 * Template: Single Local
 *
 * @package Develogic
 * @var array $local
 * @var array|null $price_history
 * @var array $atts
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$price_m2_source = develogic()->get_setting('price_m2_source', 'priceGrossm2');
$price_m2 = isset($local[$price_m2_source]) ? $local[$price_m2_source] : $local['priceGrossm2'];
?>

<div class="develogic-local-single" data-local-id="<?php echo esc_attr($local['localId']); ?>">
    
    <div class="local-header">
        <h1 class="local-title">
            <?php echo esc_html($local['number']); ?>
            <?php if (!empty($local['name'])): ?>
                <span class="local-name"><?php echo esc_html($local['name']); ?></span>
            <?php endif; ?>
        </h1>
        
        <div class="local-status status-<?php echo esc_attr(Develogic_Data_Formatter::get_status_class($local['status'])); ?>">
            <?php echo esc_html($local['status']); ?>
        </div>
    </div>
    
    <div class="local-content">
        
        <div class="local-main">
            
            <?php if (!empty($local['projections'])): ?>
            <div class="local-gallery">
                <?php foreach ($local['projections'] as $projection): 
                    // Use WordPress-hosted image if available, fallback to API URL
                    $proj_url = !empty($projection['wordpress_url']) ? $projection['wordpress_url'] : $projection['uri'];
                ?>
                    <div class="gallery-item">
                        <img src="<?php echo esc_url($proj_url); ?>" 
                             alt="<?php echo esc_attr($projection['type']); ?>">
                        <p class="projection-type"><?php echo esc_html($projection['type']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="local-details">
                <h2><?php _e('Szczegóły', 'develogic'); ?></h2>
                
                <table class="details-table">
                    <tbody>
                        <?php if (!empty($local['subdivision'])): ?>
                        <tr>
                            <th><?php _e('Inwestycja:', 'develogic'); ?></th>
                            <td><?php echo esc_html($local['subdivision']); ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (!empty($local['building'])): ?>
                        <tr>
                            <th><?php _e('Budynek:', 'develogic'); ?></th>
                            <td><?php echo esc_html($local['building']); ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (!empty($local['stage'])): ?>
                        <tr>
                            <th><?php _e('Etap:', 'develogic'); ?></th>
                            <td><?php echo esc_html($local['stage']); ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <tr>
                            <th><?php _e('Kondygnacja:', 'develogic'); ?></th>
                            <td><?php echo esc_html(Develogic_Data_Formatter::format_floor($local['floor'])); ?></td>
                        </tr>
                        
                        <?php if (!empty($local['rooms'])): ?>
                        <tr>
                            <th><?php _e('Pokoje:', 'develogic'); ?></th>
                            <td><?php echo absint($local['rooms']); ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (!empty($local['area'])): ?>
                        <tr>
                            <th><?php _e('Powierzchnia:', 'develogic'); ?></th>
                            <td><?php echo Develogic_Data_Formatter::format_area($local['area']); ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (!empty($local['areaUsable'])): ?>
                        <tr>
                            <th><?php _e('Powierzchnia użytkowa:', 'develogic'); ?></th>
                            <td><?php echo Develogic_Data_Formatter::format_area($local['areaUsable']); ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (!empty($local['worldDirections'])): ?>
                        <tr>
                            <th><?php _e('Kierunki świata:', 'develogic'); ?></th>
                            <td><?php echo esc_html(Develogic_Data_Formatter::format_world_directions($local['worldDirections'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (!empty($local['plannedDateOfFinishing'])): ?>
                        <tr>
                            <th><?php _e('Termin oddania:', 'develogic'); ?></th>
                            <td><?php echo esc_html(Develogic_Data_Formatter::format_planned_finishing($local['plannedDateOfFinishing'])); ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($local['miscAreas'])): ?>
            <div class="local-rooms">
                <h2><?php _e('Pomieszczenia', 'develogic'); ?></h2>
                <table class="rooms-table">
                    <thead>
                        <tr>
                            <th><?php _e('Nazwa', 'develogic'); ?></th>
                            <th><?php _e('Powierzchnia', 'develogic'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($local['miscAreas'] as $room): ?>
                        <tr>
                            <td><?php echo esc_html($room['name']); ?></td>
                            <td><?php echo Develogic_Data_Formatter::format_area($room['contractualArea']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($local['attributes'])): ?>
            <div class="local-attributes">
                <h2><?php _e('Cechy', 'develogic'); ?></h2>
                <ul class="attributes-list">
                    <?php foreach ($local['attributes'] as $attr): ?>
                        <li><?php echo esc_html($attr['name']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($local['packages'])): ?>
            <div class="local-packages">
                <h2><?php _e('Lokale w pakiecie', 'develogic'); ?></h2>
                <table class="packages-table">
                    <thead>
                        <tr>
                            <th><?php _e('Nazwa/Numer', 'develogic'); ?></th>
                            <th><?php _e('Typ', 'develogic'); ?></th>
                            <th><?php _e('Cena', 'develogic'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($local['packages'] as $package): ?>
                        <tr>
                            <td><?php echo esc_html($package['number']); ?></td>
                            <td><?php echo esc_html($package['localType']); ?></td>
                            <td><?php echo Develogic_Data_Formatter::format_price($package['priceGross']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
        </div>
        
        <div class="local-sidebar">
            
            <div class="price-box">
                <h2><?php _e('Cena', 'develogic'); ?></h2>
                <div class="price-total"><?php echo Develogic_Data_Formatter::format_price($local['priceGross']); ?></div>
                <div class="price-m2"><?php echo Develogic_Data_Formatter::format_price($price_m2); ?> / m²</div>
            </div>
            
            <div class="contact-box">
                <h2><?php _e('Kontakt', 'develogic'); ?></h2>
                <a href="mailto:?subject=<?php echo rawurlencode(sprintf(__('Zapytanie o lokal %s', 'develogic'), $local['number'])); ?>" 
                   class="btn btn-primary btn-contact">
                    <?php _e('Wyślij zapytanie', 'develogic'); ?>
                </a>
            </div>
            
        </div>
        
    </div>
    
    <?php if (!empty($price_history)): ?>
    <div class="local-price-history">
        <h2><?php _e('Historia cen', 'develogic'); ?></h2>
        <?php
        // Include price history template
        $atts_history = array('chart' => 'line', 'template' => 'chart');
        include DEVELOGIC_PLUGIN_DIR . 'templates/price-history-chart.php';
        ?>
    </div>
    <?php endif; ?>
    
</div>

