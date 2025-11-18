<?php
/**
 * Przykładowa konfiguracja Image Map Pro Integration
 * 
 * Ten plik pokazuje, jak programatycznie skonfigurować integrację
 * z Image Map Pro bez używania panelu administracyjnego.
 * 
 * UWAGA: Ten plik służy tylko jako przykład. Nie umieszczaj go w motywie/pluginie
 * bez zrozumienia jego działania.
 */

// NIE UŻYWAJ tego pliku bezpośrednio - to tylko przykład!
if (!defined('EXAMPLE_FILE')) {
    exit;
}

/**
 * Przykład 1: Programatyczne ustawienie kolorów statusów
 */
function my_setup_develogic_imagemappro_colors() {
    $colors = array(
        'Wolny'       => '00FF00',  // Zielony
        'Sprzedany'   => 'FF0000',  // Czerwony
        'Rezerwacja'  => 'FFA500',  // Pomarańczowy
        'Niedostępny' => 'CCCCCC',  // Szary
    );
    
    update_option('develogic_imagemappro_colors', $colors);
}

/**
 * Przykład 2: Programatyczne ustawienie mapowania budynków
 */
function my_setup_develogic_imagemappro_mappings() {
    $mappings = array(
        // Building ID => Image Map Pro Shortcode
        '123' => 'Pietro_3H',
        '124' => 'Pietro_4H',
        '125' => 'Pietro_5H',
    );
    
    update_option('develogic_imagemappro_building_map', $mappings);
}

/**
 * Przykład 3: Hook po synchronizacji - własna logika
 */
add_action('develogic_sync_completed', function($stats) {
    // Możesz dodać własną logikę po synchronizacji
    // Np. wysłać powiadomienie email
    
    if ($stats['success'] && $stats['updated'] > 0) {
        // Wyślij email do admina
        wp_mail(
            get_option('admin_email'),
            'Develogic - Zaktualizowano lokale',
            sprintf(
                'Synchronizacja zakończona. Zaktualizowano %d lokali.',
                $stats['updated']
            )
        );
    }
}, 20, 1); // Priority 20 - po aktualizacji Image Map Pro (która ma priority 10)

/**
 * Przykład 4: Filtrowanie kolorów przed aktualizacją
 */
add_filter('develogic_imagemappro_status_colors', function($colors) {
    // Możesz dynamicznie modyfikować kolory
    // Np. w zależności od pory roku
    
    $month = date('m');
    
    if ($month == 12) {
        // W grudniu - świąteczne kolory
        $colors['Wolny'] = 'FF0000'; // Czerwony
        $colors['Rezerwacja'] = '00FF00'; // Zielony
    }
    
    return $colors;
});

/**
 * Przykład 5: Ręczna aktualizacja kolorów z poziomu kodu
 */
function my_manual_update_imagemappro_colors() {
    // Możesz wywołać aktualizację ręcznie
    $integration = new Develogic_ImageMapPro_Integration();
    
    $fake_stats = array(
        'success' => true,
        'message' => 'Manualna aktualizacja',
    );
    
    $integration->update_image_map_pro_colors($fake_stats);
}

/**
 * Przykład 6: Własna logika mapowania kształtów
 * 
 * Jeśli standardowa logika mapowania (po numerze lokalu) nie wystarcza,
 * możesz dodać własny hook
 */
add_filter('develogic_imagemappro_shape_match', function($local, $shape, $project) {
    // Standardowo zwrócony $local lub null
    // Możesz dodać własną logikę dopasowania
    
    if ($local) {
        return $local; // Już znaleziono, zwracamy
    }
    
    // Przykład: dopasowanie po custom field
    $shape_title = isset($shape['title']) ? $shape['title'] : '';
    
    // Pobierz wszystkie lokale z custom field 'map_id' = shape title
    $args = array(
        'post_type' => 'develogic_local',
        'meta_key' => 'map_id',
        'meta_value' => $shape_title,
        'posts_per_page' => 1,
    );
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        $post = $query->posts[0];
        
        // Zwróć dane lokalu
        $meta = get_post_meta($post->ID);
        $local_data = array('post_id' => $post->ID);
        
        foreach ($meta as $key => $value) {
            $local_data[$key] = is_array($value) && count($value) === 1 ? $value[0] : $value;
        }
        
        return $local_data;
    }
    
    return null;
}, 10, 3);

/**
 * Przykład 7: Dodanie własnych statusów i kolorów
 */
function my_add_custom_statuses() {
    $colors = get_option('develogic_imagemappro_colors', array());
    
    // Dodaj nowe statusy
    $colors['W budowie'] = '0000FF'; // Niebieski
    $colors['Promocja'] = 'FFD700';  // Złoty
    
    update_option('develogic_imagemappro_colors', $colors);
}

/**
 * Przykład 8: Sprawdzenie statusu integracji
 */
function my_check_imagemappro_integration_status() {
    // Sprawdź czy Image Map Pro jest aktywne
    $is_active = class_exists('ImageMapPro_v6') || class_exists('ImageMapPro');
    
    // Sprawdź mapowania
    $mappings = get_option('develogic_imagemappro_building_map', array());
    
    // Sprawdź kolory
    $colors = get_option('develogic_imagemappro_colors', array());
    
    return array(
        'imagemappro_active' => $is_active,
        'mappings_count' => count($mappings),
        'colors_count' => count($colors),
        'configured' => ($is_active && !empty($mappings) && !empty($colors)),
    );
}

/**
 * Przykład 9: Debug - wyświetl wszystkie projekty Image Map Pro
 */
function my_debug_imagemappro_projects() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_map_pro_projects';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        echo 'Tabela Image Map Pro nie istnieje.';
        return;
    }
    
    $projects = $wpdb->get_results("SELECT id, name, shortcode FROM $table_name");
    
    echo '<pre>';
    print_r($projects);
    echo '</pre>';
}

/**
 * Przykład 10: Shortcode do wyświetlenia statusu integracji
 */
add_shortcode('develogic_imagemappro_status', function() {
    $status = my_check_imagemappro_integration_status();
    
    ob_start();
    ?>
    <div class="develogic-imagemappro-status">
        <h3>Status integracji Image Map Pro</h3>
        <ul>
            <li>Image Map Pro: <?php echo $status['imagemappro_active'] ? '✅ Aktywne' : '❌ Nieaktywne'; ?></li>
            <li>Mapowania budynków: <?php echo $status['mappings_count']; ?></li>
            <li>Kolory statusów: <?php echo $status['colors_count']; ?></li>
            <li>Status: <?php echo $status['configured'] ? '✅ Skonfigurowane' : '⚠️ Wymaga konfiguracji'; ?></li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
});

