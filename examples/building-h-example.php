<?php
/**
 * Przykład konfiguracji dla Budynku H - Jeziorna Towers
 * 
 * Ten plik pokazuje konkretny przykład konfiguracji dla budynku H
 * z Pietro 3, zgodnie z danymi z bazy Image Map Pro.
 * 
 * UWAGA: Ten plik służy tylko jako przykład dokumentacyjny.
 */

// NIE UŻYWAJ tego pliku bezpośrednio - to tylko przykład!
if (!defined('EXAMPLE_FILE')) {
    exit;
}

/**
 * Dane z Image Map Pro dla Budynku H, Piętro 3
 * 
 * Shortcode: Pietro_3H
 * Lokale: 44, 43, 42, 41, 16, 15, 14, 13, 40, 39, 38, 65, 64, 63, 62
 */

/**
 * Krok 1: Konfiguracja kolorów dla Jeziorna Towers
 */
function jeziornatowers_setup_colors() {
    $colors = array(
        'Wolny'       => '7ED322',  // Zielony - jak w obecnej konfiguracji
        'Sprzedany'   => 'ee1c24',  // Czerwony - jak w obecnej konfiguracji
        'Rezerwacja'  => 'FFA500',  // Pomarańczowy - jak w obecnej konfiguracji
        'Niedostępny' => 'cccccc',  // Szary - stan domyślny
    );
    
    update_option('develogic_imagemappro_colors', $colors);
}

/**
 * Krok 2: Mapowanie budynku H na projekt Image Map Pro
 */
function jeziornatowers_setup_building_h_mapping() {
    // Pobierz ID budynku H z Develogic
    // W rzeczywistości trzeba najpierw zsynchronizować lokale,
    // aby poznać buildingId dla budynku H
    
    $mappings = array(
        // Zakładając że budynek H ma ID np. 8 w Develogic
        '8' => 'Pietro_3H',
        
        // Możesz dodać inne piętra/budynki
        // '8' => 'Pietro_1H',  // Piętro 1 Budynek H
        // '8' => 'Pietro_2H',  // Piętro 2 Budynek H
        // '9' => 'Pietro_3G',  // Piętro 3 Budynek G
    );
    
    update_option('develogic_imagemappro_building_map', $mappings);
}

/**
 * Krok 3: Weryfikacja mapowania dla konkretnych lokali
 * 
 * Ten przykład pokazuje jak sprawdzić czy lokale z Image Map Pro
 * pasują do lokali w Develogic
 */
function jeziornatowers_verify_locals_mapping() {
    // Lokale z Image Map Pro (Pietro 3 Budynek H)
    $imagemappro_locals = array(
        44, 43, 42, 41, 16, 15, 14, 13, 40, 39, 38, 65, 64, 63, 62
    );
    
    // Sprawdź każdy lokal w Develogic
    foreach ($imagemappro_locals as $local_number) {
        $args = array(
            'post_type' => 'develogic_local',
            'meta_query' => array(
                array(
                    'key' => 'number',
                    'value' => $local_number,
                    'compare' => '=',
                ),
            ),
            'posts_per_page' => 1,
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            $post = $query->posts[0];
            $status = get_post_meta($post->ID, 'status', true);
            $building = get_post_meta($post->ID, 'building', true);
            
            echo sprintf(
                "Lokal %s - Budynek: %s, Status: %s ✅\n",
                $local_number,
                $building,
                $status
            );
        } else {
            echo sprintf(
                "Lokal %s - NIE ZNALEZIONO w Develogic ❌\n",
                $local_number
            );
        }
    }
}

/**
 * Krok 4: Przykład aktualizacji tylko projektu Pietro_3H
 */
function jeziornatowers_update_pietro_3h_only() {
    $integration = new Develogic_ImageMapPro_Integration();
    
    $fake_stats = array(
        'success' => true,
        'message' => 'Aktualizacja tylko Pietro_3H',
    );
    
    // Aktualizuj tylko projekt Pietro_3H
    $integration->update_image_map_pro_colors($fake_stats, array('Pietro_3H'));
}

/**
 * Krok 5: Debug - pokaż statusy lokali z budynku H
 */
function jeziornatowers_debug_building_h_locals() {
    $args = array(
        'post_type' => 'develogic_local',
        'meta_query' => array(
            array(
                'key' => 'building',
                'value' => 'H',
                'compare' => '=',
            ),
        ),
        'posts_per_page' => -1,
        'orderby' => 'meta_value_num',
        'meta_key' => 'number',
        'order' => 'ASC',
    );
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        echo "<h3>Lokale w budynku H:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Numer</th><th>Status</th><th>Piętro</th><th>Pokoje</th><th>Powierzchnia</th></tr>";
        
        foreach ($query->posts as $post) {
            $number = get_post_meta($post->ID, 'number', true);
            $status = get_post_meta($post->ID, 'status', true);
            $floor = get_post_meta($post->ID, 'floor', true);
            $rooms = get_post_meta($post->ID, 'rooms', true);
            $area = get_post_meta($post->ID, 'area', true);
            
            // Kolor tła według statusu
            $bg_color = '';
            switch ($status) {
                case 'Wolny':
                    $bg_color = '#7ED322';
                    break;
                case 'Sprzedany':
                    $bg_color = '#ee1c24';
                    break;
                case 'Rezerwacja':
                    $bg_color = '#FFA500';
                    break;
                default:
                    $bg_color = '#cccccc';
            }
            
            echo sprintf(
                "<tr style='background-color: %s; color: white;'><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s m²</td></tr>",
                $bg_color,
                esc_html($number),
                esc_html($status),
                esc_html($floor),
                esc_html($rooms),
                esc_html($area)
            );
        }
        
        echo "</table>";
    } else {
        echo "Brak lokali w budynku H";
    }
}

/**
 * Krok 6: Shortcode do wizualizacji statusów dla budynku H
 */
add_shortcode('jeziornatowers_building_h_status', function() {
    ob_start();
    jeziornatowers_debug_building_h_locals();
    return ob_get_clean();
});

/**
 * Krok 7: Automatyczna aktualizacja po synchronizacji TYLKO dla budynku H
 */
add_action('develogic_sync_completed', function($stats) {
    // Aktualizuj tylko projekty związane z budynkiem H
    $h_projects = array(
        'Pietro_1H',
        'Pietro_2H',
        'Pietro_3H',
        'Pietro_4H',
        'Pietro_5H',
    );
    
    $integration = new Develogic_ImageMapPro_Integration();
    $integration->update_image_map_pro_colors($stats, $h_projects);
    
    // Log do debug
    error_log('[Jeziorna Towers] Zaktualizowano projekty budynku H po synchronizacji');
}, 15, 1); // Priority 15 - między standardową aktualizacją (10) a innymi hookkami

/**
 * Krok 8: Weryfikacja konfiguracji przed uruchomieniem
 */
function jeziornatowers_verify_configuration() {
    $status = array(
        'imagemappro_active' => class_exists('ImageMapPro_v6'),
        'colors_configured' => false,
        'building_mapped' => false,
        'locals_synced' => false,
    );
    
    // Sprawdź kolory
    $colors = get_option('develogic_imagemappro_colors', array());
    $status['colors_configured'] = !empty($colors);
    
    // Sprawdź mapowanie
    $mappings = get_option('develogic_imagemappro_building_map', array());
    $status['building_mapped'] = in_array('Pietro_3H', $mappings);
    
    // Sprawdź lokale
    $locals_count = wp_count_posts('develogic_local');
    $status['locals_synced'] = $locals_count->publish > 0;
    
    return $status;
}

/**
 * Krok 9: Admin notice - pokazanie statusu konfiguracji
 */
add_action('admin_notices', function() {
    $screen = get_current_screen();
    if ($screen->id !== 'toplevel_page_develogic') {
        return;
    }
    
    $status = jeziornatowers_verify_configuration();
    
    if (!$status['imagemappro_active']) {
        echo '<div class="notice notice-error"><p>❌ Image Map Pro nie jest aktywne!</p></div>';
        return;
    }
    
    if (!$status['colors_configured']) {
        echo '<div class="notice notice-warning"><p>⚠️ Kolory nie są skonfigurowane. Przejdź do <a href="admin.php?page=develogic-imagemappro">Develogic → Image Map Pro</a></p></div>';
        return;
    }
    
    if (!$status['building_mapped']) {
        echo '<div class="notice notice-warning"><p>⚠️ Budynek H nie jest zmapowany na projekt Pietro_3H!</p></div>';
        return;
    }
    
    if (!$status['locals_synced']) {
        echo '<div class="notice notice-warning"><p>⚠️ Brak lokali w bazie. Wykonaj synchronizację w <a href="admin.php?page=develogic-sync">Develogic → Synchronizacja</a></p></div>';
        return;
    }
    
    echo '<div class="notice notice-success"><p>✅ Integracja Image Map Pro dla budynku H jest poprawnie skonfigurowana!</p></div>';
});

/**
 * Krok 10: Testowanie - porównanie danych
 */
function jeziornatowers_test_integration() {
    echo "<h2>Test integracji dla Budynku H</h2>";
    
    // 1. Sprawdź projekt w Image Map Pro
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_map_pro_projects';
    $project = $wpdb->get_row("SELECT * FROM $table_name WHERE shortcode = 'Pietro_3H'");
    
    if (!$project) {
        echo "<p style='color: red;'>❌ Projekt Pietro_3H nie istnieje w Image Map Pro!</p>";
        return;
    }
    
    echo "<p style='color: green;'>✅ Projekt Pietro_3H znaleziony</p>";
    
    // 2. Sprawdź kształty w projekcie
    $project_data = json_decode(stripslashes($project->json), true);
    $shapes_count = 0;
    
    if (!empty($project_data['artboards'])) {
        foreach ($project_data['artboards'] as $artboard) {
            if (!empty($artboard['children'])) {
                $shapes_count += count($artboard['children']);
            }
        }
    }
    
    echo "<p>Liczba kształtów w projekcie: <strong>{$shapes_count}</strong></p>";
    
    // 3. Sprawdź lokale w Develogic
    $args = array(
        'post_type' => 'develogic_local',
        'meta_query' => array(
            array(
                'key' => 'building',
                'value' => 'H',
                'compare' => '=',
            ),
        ),
        'posts_per_page' => -1,
    );
    
    $query = new WP_Query($args);
    echo "<p>Liczba lokali w budynku H: <strong>{$query->post_count}</strong></p>";
    
    // 4. Pokaż mapowanie
    $mappings = get_option('develogic_imagemappro_building_map', array());
    echo "<p>Mapowanie: ";
    foreach ($mappings as $building => $shortcode) {
        echo "<code>{$building} → {$shortcode}</code> ";
    }
    echo "</p>";
    
    // 5. Podsumowanie
    if ($shapes_count > 0 && $query->post_count > 0) {
        echo "<p style='color: green; font-weight: bold;'>✅ Gotowe do aktualizacji!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Brakujące dane - sprawdź konfigurację</p>";
    }
}

