<?php
/**
 * Template: Price History Chart
 *
 * @package Develogic
 * @var array $price_history
 * @var array $atts
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$chart_id = 'price-chart-' . uniqid();

// Prepare data for Chart.js
$prices = isset($price_history['prices']) ? $price_history['prices'] : array();

if (empty($prices)) {
    echo '<p>' . __('Brak danych historycznych.', 'develogic') . '</p>';
    return;
}

// Reverse to show oldest first
$prices = array_reverse($prices);

$labels = array();
$data_gross = array();
$data_net = array();
$data_gross_m2 = array();

foreach ($prices as $price) {
    $date_from = isset($price['appliesFrom']) ? $price['appliesFrom'] : '';
    $labels[] = date('d.m.Y', strtotime($date_from));
    
    $data_gross[] = isset($price['packagePriceGross']) ? $price['packagePriceGross'] : 0;
    $data_net[] = isset($price['packagePriceNet']) ? $price['packagePriceNet'] : 0;
    $data_gross_m2[] = isset($price['packagePriceGrossm2']) ? $price['packagePriceGrossm2'] : 0;
}
?>

<div class="develogic-price-history">
    
    <?php if ($atts['chart'] !== 'none'): ?>
    <div class="price-chart-container">
        <canvas id="<?php echo esc_attr($chart_id); ?>"></canvas>
    </div>
    
    <script>
    (function() {
        var ctx = document.getElementById('<?php echo esc_js($chart_id); ?>').getContext('2d');
        
        var chartData = {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    label: '<?php _e('Cena brutto', 'develogic'); ?>',
                    data: <?php echo json_encode($data_gross); ?>,
                    borderColor: '#0066cc',
                    backgroundColor: 'rgba(0, 102, 204, 0.1)',
                    tension: 0.3
                },
                {
                    label: '<?php _e('Cena brutto za m²', 'develogic'); ?>',
                    data: <?php echo json_encode($data_gross_m2); ?>,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.3,
                    yAxisID: 'y1'
                }
            ]
        };
        
        var chartConfig = {
            type: '<?php echo $atts['chart'] === 'bar' ? 'bar' : 'line'; ?>',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: '<?php _e('Cena całkowita (zł)', 'develogic'); ?>'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: '<?php _e('Cena za m² (zł)', 'develogic'); ?>'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += new Intl.NumberFormat('pl-PL', {
                                    style: 'currency',
                                    currency: 'PLN'
                                }).format(context.parsed.y);
                                return label;
                            }
                        }
                    }
                }
            }
        };
        
        new Chart(ctx, chartConfig);
    })();
    </script>
    <?php endif; ?>
    
    <!-- Table view -->
    <div class="price-history-table">
        <table>
            <thead>
                <tr>
                    <th><?php _e('Data od', 'develogic'); ?></th>
                    <th><?php _e('Data do', 'develogic'); ?></th>
                    <th><?php _e('Cena brutto', 'develogic'); ?></th>
                    <th><?php _e('Cena brutto/m²', 'develogic'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($prices) as $price): ?>
                <tr>
                    <td><?php echo esc_html(Develogic_Data_Formatter::format_date($price['appliesFrom'])); ?></td>
                    <td>
                        <?php 
                        echo isset($price['appliesTo']) && $price['appliesTo'] 
                            ? esc_html(Develogic_Data_Formatter::format_date($price['appliesTo'])) 
                            : __('Aktualna', 'develogic'); 
                        ?>
                    </td>
                    <td><?php echo Develogic_Data_Formatter::format_price($price['packagePriceGross']); ?></td>
                    <td><?php echo Develogic_Data_Formatter::format_price($price['packagePriceGrossm2']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
</div>

