<?php
/**
 * Debug script for M63 local
 * 
 * Run this from WordPress admin or via WP-CLI to debug the M63 mapping issue
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h1>Debug M63 Local & Image Map Pro</h1>";

// 1. Check if M63 exists in Develogic
echo "<h2>1. Sprawdzam M63 w Develogic</h2>";

$args = array(
    'post_type' => 'develogic_local',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => 'number',
            'value' => 'M63',
            'compare' => '=',
        ),
        array(
            'key' => 'number',
            'value' => '63',
            'compare' => '=',
        ),
        array(
            'key' => 'externalNumber',
            'value' => 'M63',
            'compare' => '=',
        ),
    ),
);

$query = new WP_Query($args);

if ($query->have_posts()) {
    echo "<p style='color: green;'>✅ Znaleziono " . $query->post_count . " lokal(i)</p>";
    
    foreach ($query->posts as $post) {
        $number = get_post_meta($post->ID, 'number', true);
        $external_number = get_post_meta($post->ID, 'externalNumber', true);
        $status = get_post_meta($post->ID, 'status', true);
        $building = get_post_meta($post->ID, 'building', true);
        $building_id = get_post_meta($post->ID, 'buildingId', true);
        
        echo "<div style='background: #f0f0f0; padding: 15px; margin: 10px 0; border-left: 4px solid green;'>";
        echo "<strong>Post ID:</strong> {$post->ID}<br>";
        echo "<strong>Title:</strong> {$post->post_title}<br>";
        echo "<strong>number:</strong> '<code>" . esc_html($number) . "</code>' (length: " . strlen($number) . ")<br>";
        echo "<strong>externalNumber:</strong> '<code>" . esc_html($external_number) . "</code>'<br>";
        echo "<strong>status:</strong> <span style='font-weight: bold; color: red;'>" . esc_html($status) . "</span><br>";
        echo "<strong>building:</strong> " . esc_html($building) . "<br>";
        echo "<strong>buildingId:</strong> " . esc_html($building_id) . "<br>";
        echo "</div>";
    }
} else {
    echo "<p style='color: red;'>❌ Nie znaleziono M63 w bazie Develogic!</p>";
    echo "<p>Czy synchronizacja została wykonana?</p>";
}

// 2. Check Image Map Pro projects
echo "<h2>2. Sprawdzam projekty Image Map Pro</h2>";

global $wpdb;
$table_name = $wpdb->prefix . 'image_map_pro_projects';

if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    echo "<p style='color: red;'>❌ Tabela Image Map Pro nie istnieje!</p>";
} else {
    $projects = $wpdb->get_results("SELECT id, name, shortcode FROM $table_name ORDER BY name ASC");
    
    echo "<p>Znaleziono " . count($projects) . " projektów:</p>";
    echo "<ul>";
    foreach ($projects as $project) {
        echo "<li><strong>{$project->name}</strong> (shortcode: <code>{$project->shortcode}</code>)</li>";
        
        // Check if this project has M63 shape
        $project_full = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %s", $project->id));
        $project_data = json_decode(stripslashes($project_full->json), true);
        
        if (!empty($project_data['artboards'])) {
            foreach ($project_data['artboards'] as $artboard) {
                if (!empty($artboard['children'])) {
                    foreach ($artboard['children'] as $shape) {
                        $shape_title = isset($shape['title']) ? trim($shape['title']) : '';
                        
                        if ($shape_title === 'M63' || $shape_title === '63') {
                            $bg_color = isset($shape['default_style']['background_color']) ? $shape['default_style']['background_color'] : 'brak';
                            
                            echo "<ul style='margin-left: 30px;'>";
                            echo "<li style='color: blue;'>🎯 ZNALEZIONO kształt!<br>";
                            echo "Shape ID: <code>{$shape['id']}</code><br>";
                            echo "Title: '<code>" . esc_html($shape_title) . "</code>' (length: " . strlen($shape_title) . ")<br>";
                            echo "Kolor: <span style='background: #{$bg_color}; padding: 2px 10px; color: white;'>#{$bg_color}</span><br>";
                            echo "</li>";
                            echo "</ul>";
                        }
                    }
                }
            }
        }
    }
    echo "</ul>";
}

// 3. Check color mappings
echo "<h2>3. Sprawdzam mapowania kolorów</h2>";

$colors = get_option('develogic_imagemappro_colors', array());

if (empty($colors)) {
    echo "<p style='color: orange;'>⚠️ Brak skonfigurowanych kolorów - używam domyślnych</p>";
    $colors = array(
        'Wolny' => '7ED322',
        'Sprzedany' => 'ee1c24',
        'Rezerwacja' => 'FFA500',
        'Niedostępny' => 'cccccc',
    );
}

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Status</th><th>Kolor</th><th>Podgląd</th></tr>";
foreach ($colors as $status => $color) {
    echo "<tr>";
    echo "<td><strong>{$status}</strong></td>";
    echo "<td><code>#{$color}</code></td>";
    echo "<td style='background: #{$color}; width: 100px;'>&nbsp;</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Check building mappings
echo "<h2>4. Sprawdzam mapowania budynków</h2>";

$mappings = get_option('develogic_imagemappro_building_map', array());

if (empty($mappings)) {
    echo "<p style='color: red;'>❌ Brak mapowań budynków!</p>";
    echo "<p>Przejdź do <a href='" . admin_url('admin.php?page=develogic-imagemappro') . "'>Develogic → Image Map Pro</a> i skonfiguruj mapowania.</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Budynek (ID/Nazwa)</th><th>Shortcode Image Map Pro</th></tr>";
    foreach ($mappings as $building => $shortcode) {
        echo "<tr>";
        echo "<td><code>{$building}</code></td>";
        echo "<td><code>{$shortcode}</code></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 5. Manual test - try to update colors
echo "<h2>5. Test aktualizacji (symulacja)</h2>";

if (!empty($query->posts)) {
    $local_data = array();
    $post = $query->posts[0];
    
    $meta = get_post_meta($post->ID);
    $local_data['post_id'] = $post->ID;
    $local_data['title'] = $post->post_title;
    
    foreach ($meta as $key => $value) {
        if (is_array($value) && count($value) === 1) {
            $local_data[$key] = $value[0];
        } else {
            $local_data[$key] = $value;
        }
    }
    
    echo "<p><strong>Dane lokalu do aktualizacji:</strong></p>";
    echo "<pre>";
    print_r(array(
        'number' => $local_data['number'],
        'externalNumber' => isset($local_data['externalNumber']) ? $local_data['externalNumber'] : 'brak',
        'status' => $local_data['status'],
        'building' => $local_data['building'],
        'buildingId' => $local_data['buildingId'],
    ));
    echo "</pre>";
    
    $status = $local_data['status'];
    $expected_color = isset($colors[$status]) ? $colors[$status] : 'nieznany';
    
    echo "<p style='font-size: 18px;'>";
    echo "Status: <strong style='color: red;'>{$status}</strong><br>";
    echo "Oczekiwany kolor: <span style='background: #{$expected_color}; padding: 5px 15px; color: white;'>#{$expected_color}</span>";
    echo "</p>";
}

// 6. Suggestions
echo "<h2>6. Sugestie rozwiązania</h2>";

$issues = array();

if ($query->post_count === 0) {
    $issues[] = "❌ Lokal M63 nie istnieje w bazie - wykonaj synchronizację";
}

if (empty($mappings)) {
    $issues[] = "❌ Brak mapowań budynków - skonfiguruj w panelu admin";
}

if (!empty($issues)) {
    echo "<ul style='color: red; font-weight: bold;'>";
    foreach ($issues as $issue) {
        echo "<li>{$issue}</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✅ Konfiguracja wygląda poprawnie!</p>";
    echo "<p>Spróbuj:</p>";
    echo "<ol>";
    echo "<li>Przejdź do <a href='" . admin_url('admin.php?page=develogic-imagemappro') . "'>Develogic → Image Map Pro</a></li>";
    echo "<li>Kliknij 'Aktualizuj kolory teraz'</li>";
    echo "<li>Sprawdź logi w <a href='" . admin_url('admin.php?page=develogic-sync') . "'>Develogic → Synchronizacja</a></li>";
    echo "</ol>";
}

