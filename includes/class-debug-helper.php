<?php
/**
 * Debug Helper for Image Map Pro Integration
 * 
 * @package Develogic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Develogic_Debug_Helper
 */
class Develogic_Debug_Helper {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_debug_page'), 100);
    }
    
    /**
     * Add debug page to admin menu
     */
    public function add_debug_page() {
        add_submenu_page(
            'develogic',
            __('Debug M63', 'develogic'),
            __('🔧 Debug M63', 'develogic'),
            'manage_options',
            'develogic-debug-m63',
            array($this, 'render_debug_page')
        );
    }
    
    /**
     * Render debug page
     */
    public function render_debug_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Brak uprawnień', 'develogic'));
        }
        
        echo '<div class="wrap">';
        echo '<h1>🔍 Debug M63 - Diagnostyka Integracji Image Map Pro</h1>';
        
        // 1. Check if M63 exists in Develogic
        $this->check_m63_in_develogic();
        
        // 2. Check Image Map Pro projects
        $this->check_imagemappro_projects();
        
        // 3. Check color mappings
        $this->check_color_mappings();
        
        // 4. Check building mappings
        $this->check_building_mappings();
        
        // 5. Test simulation
        $this->test_update_simulation();
        
        // 6. Suggestions
        $this->show_suggestions();
        
        echo '</div>';
    }
    
    /**
     * Check M63 in Develogic
     */
    private function check_m63_in_develogic() {
        echo '<div class="card" style="max-width: 100%; margin: 20px 0;">';
        echo '<h2>1️⃣ Sprawdzam M63 w Develogic</h2>';
        
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
            echo '<p style="color: green; font-size: 16px; font-weight: bold;">✅ Znaleziono ' . $query->post_count . ' lokal(i)</p>';
            
            foreach ($query->posts as $post) {
                $number = get_post_meta($post->ID, 'number', true);
                $external_number = get_post_meta($post->ID, 'externalNumber', true);
                $status = get_post_meta($post->ID, 'status', true);
                $building = get_post_meta($post->ID, 'building', true);
                $building_id = get_post_meta($post->ID, 'buildingId', true);
                
                echo '<div style="background: #d4edda; padding: 20px; margin: 10px 0; border-left: 5px solid #28a745; border-radius: 5px;">';
                echo '<table style="width: 100%;">';
                echo '<tr><td style="width: 200px;"><strong>Post ID:</strong></td><td>' . $post->ID . '</td></tr>';
                echo '<tr><td><strong>Title:</strong></td><td>' . esc_html($post->post_title) . '</td></tr>';
                echo '<tr><td><strong>number:</strong></td><td><code style="background: #fff; padding: 5px; font-size: 16px; color: blue;">' . esc_html($number) . '</code> (długość: ' . strlen($number) . ')</td></tr>';
                echo '<tr><td><strong>externalNumber:</strong></td><td><code style="background: #fff; padding: 5px;">' . esc_html($external_number) . '</code></td></tr>';
                echo '<tr><td><strong>status:</strong></td><td><span style="font-weight: bold; color: red; font-size: 18px;">' . esc_html($status) . '</span></td></tr>';
                echo '<tr><td><strong>building:</strong></td><td><code style="background: #fff; padding: 5px; font-size: 16px; color: green;">' . esc_html($building) . '</code></td></tr>';
                echo '<tr><td><strong>buildingId:</strong></td><td><code style="background: #fff; padding: 5px;">' . esc_html($building_id) . '</code></td></tr>';
                echo '</table>';
                echo '</div>';
            }
        } else {
            echo '<div style="background: #f8d7da; padding: 20px; margin: 10px 0; border-left: 5px solid #dc3545; border-radius: 5px;">';
            echo '<p style="color: #721c24; font-weight: bold; font-size: 16px;">❌ Nie znaleziono M63 w bazie Develogic!</p>';
            echo '<p>Przejdź do <a href="' . admin_url('admin.php?page=develogic-sync') . '">Develogic → Synchronizacja</a> i kliknij "Synchronizuj teraz"</p>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Check Image Map Pro projects
     */
    private function check_imagemappro_projects() {
        echo '<div class="card" style="max-width: 100%; margin: 20px 0;">';
        echo '<h2>2️⃣ Sprawdzam projekty Image Map Pro</h2>';
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'image_map_pro_projects';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            echo '<p style="color: red;">❌ Tabela Image Map Pro nie istnieje!</p>';
            echo '</div>';
            return;
        }
        
        $projects = $wpdb->get_results("SELECT id, name, shortcode FROM $table_name ORDER BY name ASC");
        
        echo '<p>Znaleziono <strong>' . count($projects) . '</strong> projektów:</p>';
        echo '<ul style="font-size: 14px;">';
        
        $found_m63 = false;
        
        foreach ($projects as $project) {
            echo '<li style="margin: 10px 0;"><strong>' . esc_html($project->name) . '</strong> (shortcode: <code>' . esc_html($project->shortcode) . '</code>)';
            
            // Check if this project has M63 shape
            $project_full = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %s", $project->id));
            $project_data = json_decode(stripslashes($project_full->json), true);
            
            if (!empty($project_data['artboards'])) {
                foreach ($project_data['artboards'] as $artboard) {
                    if (!empty($artboard['children'])) {
                        foreach ($artboard['children'] as $shape) {
                            $shape_title = isset($shape['title']) ? trim($shape['title']) : '';
                            
                            if ($shape_title === 'M63' || $shape_title === '63') {
                                $found_m63 = true;
                                $bg_color = isset($shape['default_style']['background_color']) ? $shape['default_style']['background_color'] : 'brak';
                                
                                echo '<ul style="margin-left: 30px; background: #fff3cd; padding: 10px; border-radius: 5px; margin-top: 10px;">';
                                echo '<li style="color: blue; font-weight: bold;">🎯 ZNALEZIONO KSZTAŁT M63!</li>';
                                echo '<li>Shape ID: <code>' . esc_html($shape['id']) . '</code></li>';
                                echo '<li>Title: <code style="background: white; padding: 5px; font-size: 16px; color: blue;">' . esc_html($shape_title) . '</code> (długość: ' . strlen($shape_title) . ')</li>';
                                echo '<li>Obecny kolor: <span style="background: #' . esc_attr($bg_color) . '; padding: 5px 15px; color: white; border-radius: 3px; font-weight: bold;">#' . esc_html($bg_color) . '</span></li>';
                                echo '</ul>';
                            }
                        }
                    }
                }
            }
            
            echo '</li>';
        }
        
        echo '</ul>';
        
        if (!$found_m63) {
            echo '<div style="background: #f8d7da; padding: 20px; margin: 10px 0; border-left: 5px solid #dc3545; border-radius: 5px;">';
            echo '<p style="color: #721c24; font-weight: bold;">❌ Nie znaleziono kształtu z title="M63" w żadnym projekcie!</p>';
            echo '<p>Sprawdź w Image Map Pro Editor czy kształt ma poprawny title.</p>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Check color mappings
     */
    private function check_color_mappings() {
        echo '<div class="card" style="max-width: 100%; margin: 20px 0;">';
        echo '<h2>3️⃣ Sprawdzam mapowania kolorów</h2>';
        
        $colors = get_option('develogic_imagemappro_colors', array());
        
        if (empty($colors)) {
            echo '<p style="color: orange;">⚠️ Brak skonfigurowanych kolorów - używam domyślnych</p>';
            $colors = array(
                'Wolny' => '7ED322',
                'Sprzedany' => 'ee1c24',
                'Rezerwacja' => 'FFA500',
                'Miękka rezerwacja' => 'FFA500',
                'Niedostępny' => 'cccccc',
            );
        }
        
        echo '<table class="wp-list-table widefat fixed striped" style="max-width: 600px;">';
        echo '<thead><tr><th>Status</th><th>Kolor</th><th>Podgląd</th></tr></thead>';
        echo '<tbody>';
        foreach ($colors as $status => $color) {
            echo '<tr>';
            echo '<td><strong>' . esc_html($status) . '</strong></td>';
            echo '<td><code>#' . esc_html($color) . '</code></td>';
            echo '<td style="background: #' . esc_attr($color) . '; width: 150px; text-align: center; color: white; font-weight: bold;">Przykład</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        
        echo '<p style="margin-top: 15px;">Status "Sprzedany" powinien mieć kolor: <span style="background: #' . (isset($colors['Sprzedany']) ? esc_attr($colors['Sprzedany']) : 'ee1c24') . '; padding: 5px 15px; color: white; border-radius: 3px; font-weight: bold;">#' . (isset($colors['Sprzedany']) ? esc_html($colors['Sprzedany']) : 'ee1c24') . '</span></p>';
        
        echo '</div>';
    }
    
    /**
     * Check building mappings
     */
    private function check_building_mappings() {
        echo '<div class="card" style="max-width: 100%; margin: 20px 0;">';
        echo '<h2>4️⃣ Sprawdzam mapowania budynków</h2>';
        
        $mappings = get_option('develogic_imagemappro_building_map', array());
        
        if (empty($mappings)) {
            echo '<div style="background: #f8d7da; padding: 20px; margin: 10px 0; border-left: 5px solid #dc3545; border-radius: 5px;">';
            echo '<p style="color: #721c24; font-weight: bold; font-size: 16px;">❌ BRAK MAPOWAŃ BUDYNKÓW!</p>';
            echo '<p>To jest najprawdopodobniej powód, dlaczego kolory się nie aktualizują!</p>';
            echo '<p><strong>Rozwiązanie:</strong> Przejdź do <a href="' . admin_url('admin.php?page=develogic-imagemappro') . '">Develogic → Image Map Pro</a> i skonfiguruj mapowania.</p>';
            echo '</div>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped" style="max-width: 800px;">';
            echo '<thead><tr><th>Budynek (ID/Nazwa)</th><th>Shortcode Image Map Pro</th><th>Status</th></tr></thead>';
            echo '<tbody>';
            
            $has_h_mapping = false;
            
            foreach ($mappings as $building => $shortcode) {
                $is_h = ($building === 'H' || $building === 'h');
                if ($is_h) {
                    $has_h_mapping = true;
                }
                
                echo '<tr' . ($is_h ? ' style="background: #d4edda; font-weight: bold;"' : '') . '>';
                echo '<td><code>' . esc_html($building) . '</code>' . ($is_h ? ' 🎯' : '') . '</td>';
                echo '<td><code>' . esc_html($shortcode) . '</code></td>';
                echo '<td>' . ($is_h ? '<span style="color: green;">✅ To ten!</span>' : '') . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
            
            if (!$has_h_mapping) {
                echo '<div style="background: #fff3cd; padding: 20px; margin: 10px 0; border-left: 5px solid #ffc107; border-radius: 5px;">';
                echo '<p style="color: #856404; font-weight: bold;">⚠️ Budynek H nie jest zmapowany!</p>';
                echo '<p>M63 należy do budynku H, więc musisz zmapować budynek H na odpowiedni projekt.</p>';
                echo '</div>';
            }
        }
        
        echo '</div>';
    }
    
    /**
     * Test update simulation
     */
    private function test_update_simulation() {
        echo '<div class="card" style="max-width: 100%; margin: 20px 0;">';
        echo '<h2>5️⃣ Symulacja aktualizacji</h2>';
        
        $args = array(
            'post_type' => 'develogic_local',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => 'number',
                    'value' => 'M63',
                    'compare' => '=',
                ),
            ),
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            $post = $query->posts[0];
            $number = get_post_meta($post->ID, 'number', true);
            $status = get_post_meta($post->ID, 'status', true);
            $building = get_post_meta($post->ID, 'building', true);
            
            $colors = get_option('develogic_imagemappro_colors', array(
                'Wolny' => '7ED322',
                'Sprzedany' => 'ee1c24',
                'Rezerwacja' => 'FFA500',
                'Miękka rezerwacja' => 'FFA500',
                'Niedostępny' => 'cccccc',
            ));
            
            $expected_color = isset($colors[$status]) ? $colors[$status] : 'nieznany';
            
            echo '<div style="background: #e7f3ff; padding: 20px; border-radius: 5px;">';
            echo '<p style="font-size: 16px;"><strong>Lokal do aktualizacji:</strong></p>';
            echo '<ul style="font-size: 14px; line-height: 1.8;">';
            echo '<li>Number: <code style="background: white; padding: 5px;">' . esc_html($number) . '</code></li>';
            echo '<li>Status: <strong style="color: red; font-size: 16px;">' . esc_html($status) . '</strong></li>';
            echo '<li>Building: <code style="background: white; padding: 5px;">' . esc_html($building) . '</code></li>';
            echo '</ul>';
            
            echo '<p style="font-size: 16px; margin-top: 20px;"><strong>Oczekiwany rezultat:</strong></p>';
            echo '<p>Kształt z title="<code>M63</code>" powinien mieć kolor: <span style="background: #' . esc_attr($expected_color) . '; padding: 10px 20px; color: white; border-radius: 5px; font-weight: bold; font-size: 18px;">#' . esc_html($expected_color) . '</span></p>';
            echo '</div>';
        } else {
            echo '<p style="color: red;">❌ M63 nie znaleziony - nie można wykonać symulacji</p>';
        }
        
        echo '</div>';
    }
    
    /**
     * Show suggestions
     */
    private function show_suggestions() {
        echo '<div class="card" style="max-width: 100%; margin: 20px 0; background: #f0f8ff; border: 2px solid #0073aa;">';
        echo '<h2>6️⃣ Następne kroki</h2>';
        
        // Check all conditions
        $m63_exists = false;
        $shape_exists = false;
        $mappings_exist = false;
        $h_mapped = false;
        
        // Check M63
        $args = array(
            'post_type' => 'develogic_local',
            'meta_query' => array(
                array('key' => 'number', 'value' => 'M63', 'compare' => '='),
            ),
            'posts_per_page' => 1,
        );
        $query = new WP_Query($args);
        $m63_exists = $query->have_posts();
        
        // Check mappings
        $mappings = get_option('develogic_imagemappro_building_map', array());
        $mappings_exist = !empty($mappings);
        foreach ($mappings as $building => $shortcode) {
            if ($building === 'H' || $building === 'h') {
                $h_mapped = true;
                break;
            }
        }
        
        echo '<div style="padding: 20px;">';
        
        if (!$m63_exists) {
            echo '<p style="color: red; font-weight: bold; font-size: 16px;">🔴 KROK 1: Zsynchronizuj lokale</p>';
            echo '<p>M63 nie istnieje w bazie. <a href="' . admin_url('admin.php?page=develogic-sync') . '" class="button button-primary">Przejdź do synchronizacji</a></p>';
        } elseif (!$mappings_exist || !$h_mapped) {
            echo '<p style="color: orange; font-weight: bold; font-size: 16px;">🟠 KROK 2: Zmapuj budynek H</p>';
            echo '<p>Budynek H nie jest zmapowany na projekt Image Map Pro. <a href="' . admin_url('admin.php?page=develogic-imagemappro') . '" class="button button-primary">Przejdź do mapowań</a></p>';
        } else {
            echo '<p style="color: green; font-weight: bold; font-size: 16px;">🟢 Wszystko gotowe! Spróbuj teraz:</p>';
            echo '<p><a href="' . admin_url('admin.php?page=develogic-imagemappro') . '" class="button button-primary button-large">Develogic → Image Map Pro → "Aktualizuj kolory teraz"</a></p>';
        }
        
        echo '</div>';
        echo '</div>';
    }
}

