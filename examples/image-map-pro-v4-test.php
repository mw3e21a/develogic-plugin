<?php
/**
 * Test script for Image Map Pro v4/v5 detection
 * 
 * Usage:
 * 1. Upload to WordPress root
 * 2. Access via browser: https://yoursite.com/image-map-pro-v4-test.php
 * 3. Check if old version projects are detected
 * 
 * @package Develogic
 */

// Load WordPress
require_once('wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. Admin only.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Image Map Pro v4/v5 Detection Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        table td, table th { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Image Map Pro v4/v5 Detection Test</h1>
    
    <?php
    global $wpdb;
    
    // Test 1: Check for new version (table)
    echo '<div class="section">';
    echo '<h2>Test 1: Image Map Pro v6+ (Table-based)</h2>';
    
    $table_name = $wpdb->prefix . 'image_map_pro_projects';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if ($table_exists) {
        echo '<p class="success">✓ Tabela wp_image_map_pro_projects istnieje</p>';
        
        $projects = $wpdb->get_results("SELECT id, name, shortcode FROM $table_name ORDER BY name ASC");
        
        if ($projects) {
            echo '<p class="success">✓ Znaleziono ' . count($projects) . ' projektów w tabeli</p>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Nazwa</th><th>Shortcode</th></tr>';
            foreach ($projects as $project) {
                echo '<tr>';
                echo '<td>' . esc_html($project->id) . '</td>';
                echo '<td>' . esc_html($project->name) . '</td>';
                echo '<td>' . esc_html($project->shortcode) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="warning">⚠ Tabela istnieje, ale jest pusta</p>';
        }
    } else {
        echo '<p class="error">✗ Tabela wp_image_map_pro_projects NIE istnieje</p>';
    }
    echo '</div>';
    
    // Test 2: Check for old version (wp_options)
    echo '<div class="section">';
    echo '<h2>Test 2: Image Map Pro v4/v5 (wp_options based)</h2>';
    
    $old_options = get_option('image-map-pro-wordpress-admin-options', false);
    
    if ($old_options) {
        echo '<p class="success">✓ Opcja "image-map-pro-wordpress-admin-options" istnieje</p>';
        
        if (isset($old_options['saves']) && is_array($old_options['saves'])) {
            echo '<p class="success">✓ Znaleziono ' . count($old_options['saves']) . ' projektów w wp_options</p>';
            
            echo '<table>';
            echo '<tr><th>ID</th><th>Nazwa</th><th>Shortcode</th><th>Liczba kształtów</th></tr>';
            
            foreach ($old_options['saves'] as $project_id => $project_data) {
                $name = isset($project_data['meta']['name']) ? $project_data['meta']['name'] : 'Unknown';
                $shortcode = isset($project_data['meta']['shortcode']) ? $project_data['meta']['shortcode'] : 'Unknown';
                
                $spots_count = 0;
                if (isset($project_data['json'])) {
                    $json_data = json_decode($project_data['json'], true);
                    if (isset($json_data['spots']) && is_array($json_data['spots'])) {
                        $spots_count = count($json_data['spots']);
                    }
                }
                
                echo '<tr>';
                echo '<td>' . esc_html($project_id) . '</td>';
                echo '<td>' . esc_html($name) . '</td>';
                echo '<td>' . esc_html($shortcode) . '</td>';
                echo '<td>' . esc_html($spots_count) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            
            // Show first project details
            $first_project = reset($old_options['saves']);
            $first_id = key($old_options['saves']);
            
            if ($first_project) {
                echo '<h3>Przykład: Pierwszy projekt (ID: ' . esc_html($first_id) . ')</h3>';
                
                if (isset($first_project['json'])) {
                    $json_data = json_decode($first_project['json'], true);
                    
                    if ($json_data) {
                        echo '<h4>Struktura ogólna:</h4>';
                        echo '<pre>';
                        echo 'ID: ' . (isset($json_data['id']) ? $json_data['id'] : 'N/A') . "\n";
                        echo 'Nazwa: ' . (isset($json_data['general']['name']) ? $json_data['general']['name'] : 'N/A') . "\n";
                        echo 'Shortcode: ' . (isset($json_data['general']['shortcode']) ? $json_data['general']['shortcode'] : 'N/A') . "\n";
                        echo 'Szerokość: ' . (isset($json_data['general']['width']) ? $json_data['general']['width'] : 'N/A') . "\n";
                        echo 'Wysokość: ' . (isset($json_data['general']['height']) ? $json_data['general']['height'] : 'N/A') . "\n";
                        echo '</pre>';
                        
                        if (isset($json_data['spots']) && is_array($json_data['spots'])) {
                            echo '<h4>Przykładowe kształty (pierwsze 3):</h4>';
                            echo '<table>';
                            echo '<tr><th>ID</th><th>Title</th><th>Type</th><th>Kolor</th></tr>';
                            
                            $count = 0;
                            foreach ($json_data['spots'] as $spot) {
                                if ($count >= 3) break;
                                
                                $id = isset($spot['id']) ? $spot['id'] : 'N/A';
                                $title = isset($spot['title']) ? $spot['title'] : 'N/A';
                                $type = isset($spot['type']) ? $spot['type'] : 'N/A';
                                $color = isset($spot['default_style']['background_color']) ? $spot['default_style']['background_color'] : 'N/A';
                                
                                echo '<tr>';
                                echo '<td>' . esc_html($id) . '</td>';
                                echo '<td><strong>' . esc_html($title) . '</strong></td>';
                                echo '<td>' . esc_html($type) . '</td>';
                                echo '<td style="background: #' . esc_attr($color) . '; color: white;">#' . esc_html($color) . '</td>';
                                echo '</tr>';
                                
                                $count++;
                            }
                            echo '</table>';
                        }
                    }
                }
            }
            
        } else {
            echo '<p class="error">✗ Brak klucza "saves" w opcji</p>';
        }
    } else {
        echo '<p class="error">✗ Opcja "image-map-pro-wordpress-admin-options" NIE istnieje</p>';
    }
    echo '</div>';
    
    // Test 3: Check Develogic integration
    echo '<div class="section">';
    echo '<h2>Test 3: Integracja Develogic</h2>';
    
    if (class_exists('Develogic_ImageMapPro_Integration')) {
        echo '<p class="success">✓ Klasa Develogic_ImageMapPro_Integration istnieje</p>';
        
        // Try to get projects using integration class
        $reflection = new ReflectionClass('Develogic_ImageMapPro_Integration');
        $method = $reflection->getMethod('get_all_imagemappro_projects');
        $method->setAccessible(true);
        
        $integration = new Develogic_ImageMapPro_Integration();
        $all_projects = $method->invoke($integration);
        
        if ($all_projects) {
            echo '<p class="success">✓ Plugin wykrył ' . count($all_projects) . ' projektów łącznie</p>';
            
            $old_count = 0;
            $new_count = 0;
            
            foreach ($all_projects as $project) {
                if (isset($project->version) && $project->version === 'old') {
                    $old_count++;
                } else {
                    $new_count++;
                }
            }
            
            echo '<p>Rozkład wersji:</p>';
            echo '<ul>';
            echo '<li>Image Map Pro v6+: <strong>' . $new_count . '</strong></li>';
            echo '<li>Image Map Pro v4/v5: <strong>' . $old_count . '</strong></li>';
            echo '</ul>';
            
            echo '<h4>Lista wszystkich projektów:</h4>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Nazwa</th><th>Shortcode</th><th>Wersja</th></tr>';
            foreach ($all_projects as $project) {
                $version = isset($project->version) ? $project->version : 'new';
                $version_label = $version === 'old' ? 'v4/v5' : 'v6+';
                
                echo '<tr>';
                echo '<td>' . esc_html($project->id) . '</td>';
                echo '<td>' . esc_html($project->name) . '</td>';
                echo '<td>' . esc_html($project->shortcode) . '</td>';
                echo '<td><strong>' . esc_html($version_label) . '</strong></td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="warning">⚠ Plugin nie wykrył żadnych projektów</p>';
        }
    } else {
        echo '<p class="error">✗ Klasa Develogic_ImageMapPro_Integration nie istnieje</p>';
    }
    echo '</div>';
    
    // Test 4: Check mappings
    echo '<div class="section">';
    echo '<h2>Test 4: Mapowania budynków</h2>';
    
    $mappings = get_option('develogic_imagemappro_building_map', array());
    
    if (!empty($mappings)) {
        echo '<p class="success">✓ Znaleziono ' . count($mappings) . ' mapowań</p>';
        
        echo '<table>';
        echo '<tr><th>Budynek</th><th>Projekty (Shortcodes)</th></tr>';
        
        foreach ($mappings as $building => $shortcodes) {
            $shortcodes_array = is_array($shortcodes) ? $shortcodes : array($shortcodes);
            
            echo '<tr>';
            echo '<td><strong>' . esc_html($building) . '</strong></td>';
            echo '<td>' . esc_html(implode(', ', $shortcodes_array)) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="warning">⚠ Brak skonfigurowanych mapowań</p>';
        echo '<p>Przejdź do: <a href="' . admin_url('admin.php?page=develogic-imagemappro') . '">Develogic → Image Map Pro</a></p>';
    }
    echo '</div>';
    
    // Summary
    echo '<div class="section">';
    echo '<h2>Podsumowanie</h2>';
    
    $total_projects = 0;
    if ($table_exists && isset($projects)) {
        $total_projects += count($projects);
    }
    if ($old_options && isset($old_options['saves'])) {
        $total_projects += count($old_options['saves']);
    }
    
    if ($total_projects > 0) {
        echo '<p class="success"><strong>✓ Wszystko działa poprawnie!</strong></p>';
        echo '<p>Wykryto łącznie <strong>' . $total_projects . '</strong> projektów Image Map Pro.</p>';
        echo '<p>Możesz teraz:</p>';
        echo '<ol>';
        echo '<li>Zmapować budynki na projekty w panelu: <a href="' . admin_url('admin.php?page=develogic-imagemappro') . '">Develogic → Image Map Pro</a></li>';
        echo '<li>Uruchomić manualną aktualizację kolorów</li>';
        echo '<li>Sprawdzić logi w <code>wp-content/debug.log</code></li>';
        echo '</ol>';
    } else {
        echo '<p class="error"><strong>✗ Nie wykryto żadnych projektów Image Map Pro</strong></p>';
        echo '<p>Sprawdź czy:</p>';
        echo '<ul>';
        echo '<li>Plugin Image Map Pro jest zainstalowany i aktywny</li>';
        echo '<li>Utworzyłeś już jakieś projekty w Image Map Pro</li>';
        echo '</ul>';
    }
    echo '</div>';
    ?>
    
    <hr>
    <p><small>Test wykonany: <?php echo date('Y-m-d H:i:s'); ?></small></p>
    <p><small><a href="<?php echo admin_url(); ?>">← Powrót do panelu administracyjnego</a></small></p>
</body>
</html>

