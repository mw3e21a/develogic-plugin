<?php
/**
 * Develogic Integration - Przykładowe snippety dla functions.php
 *
 * Skopiuj wybrane fragmenty do pliku functions.php swojego motywu
 *
 * @package Develogic
 */

// ============================================================================
// KONFIGURACJA BUDYNKÓW - JeziornaTowers
// ============================================================================

/**
 * Miniatury budynków dla JeziornaTowers
 */
add_filter('develogic_building_thumbnail', function($url, $building_id) {
    $thumbnails = array(
        66 => get_stylesheet_directory_uri() . '/images/buildings/budynek-h.jpg',
        67 => get_stylesheet_directory_uri() . '/images/buildings/budynek-g.jpg',
    );
    
    return isset($thumbnails[$building_id]) ? $thumbnails[$building_id] : $url;
}, 10, 2);

/**
 * Adresy budynków dla JeziornaTowers
 */
add_filter('develogic_building_address', function($address, $building_id) {
    $addresses = array(
        66 => 'ul. Jeziorna Towers, Budynek H, Poznań',
        67 => 'ul. Jeziorna Towers, Budynek G, Poznań',
    );
    
    return isset($addresses[$building_id]) ? $addresses[$building_id] : $address;
}, 10, 2);

// ============================================================================
// KONFIGURACJA BUDYNKÓW - OstojaOsiedle
// ============================================================================

/**
 * Miniatury budynków dla OstojaOsiedle
 */
add_filter('develogic_building_thumbnail', function($url, $building_id) {
    $thumbnails = array(
        101 => get_stylesheet_directory_uri() . '/images/ostoja/budynek-a.jpg',
        102 => get_stylesheet_directory_uri() . '/images/ostoja/budynek-b.jpg',
        103 => get_stylesheet_directory_uri() . '/images/ostoja/budynek-c.jpg',
    );
    
    return isset($thumbnails[$building_id]) ? $thumbnails[$building_id] : $url;
}, 10, 2);

/**
 * Adresy budynków dla OstojaOsiedle
 */
add_filter('develogic_building_address', function($address, $building_id) {
    $addresses = array(
        101 => 'ul. Ostoja Osiedle, Budynek A, Poznań',
        102 => 'ul. Ostoja Osiedle, Budynek B, Poznań',
        103 => 'ul. Ostoja Osiedle, Budynek C, Poznań',
    );
    
    return isset($addresses[$building_id]) ? $addresses[$building_id] : $address;
}, 10, 2);

// ============================================================================
// DODATKOWE POLA I ATRYBUTY
// ============================================================================

/**
 * Pobierz klatkę z atrybutów lokalu
 */
add_filter('develogic_local_klatka', function($klatka, $local) {
    if (!empty($local['attributes'])) {
        foreach ($local['attributes'] as $attr) {
            $name = strtolower($attr['name']);
            
            // Szukaj atrybutu zawierającego "klatka"
            if (strpos($name, 'klatka') !== false) {
                // Przykład: "Klatka: A" -> zwróć "A"
                return trim(str_replace(array('klatka:', 'klatka'), '', $name));
            }
        }
    }
    
    return $klatka;
}, 10, 2);

/**
 * Rozszerzenie whitelist tagów (atrybutów)
 */
add_filter('develogic_attribute_whitelist', function($whitelist) {
    $additional_tags = array(
        'winda',
        'klimatyzacja',
        'monitoring',
        'domofon',
        'parking podziemny',
        'komórka lokatorska',
        'antresola',
        'taras na dachu',
    );
    
    return array_merge($whitelist, $additional_tags);
});

// ============================================================================
// DOSTOSOWANIE LINKÓW PDF
// ============================================================================

/**
 * Generuj link do PDF dla konkretnego lokalu
 * Wymaga ustawienia w panelu: Źródło PDF = "pattern"
 * Wzorzec: https://jeziornatowers.pl/pdf/{number}.pdf
 */
// Ustawione w panelu administracyjnym

/**
 * Alternatywnie: dynamiczne generowanie PDF
 */
add_filter('develogic_pdf_link', function($link, $local) {
    // Przykład: generuj PDF przez własny endpoint
    return home_url('/generate-pdf/?local_id=' . $local['localId']);
}, 10, 2);

// ============================================================================
// APARTMENTS LIST - CUSTOMIZACJA
// ============================================================================

/**
 * Customizacja whitelist tagów dla apartments-list
 */
add_filter('develogic_attribute_whitelist', function($whitelist) {
    return array(
        'aneks',
        'balkon',
        '2 balkony',
        '3 balkony',
        'garderoba',
        'taras',
        'ogród',
        'pom. gospodarcze',
        'komórka lokatorska',
        'parking',
        'winda',
    );
});

/**
 * Dodaj własne ikony dla tagów w apartments-list
 */
add_action('wp_head', function() {
    ?>
    <style>
        /* Ikony dla tagów */
        .tag-aneks::before { content: "🍳 "; }
        .tag-balkon::before { content: "🪴 "; }
        .tag-2-balkony::before { content: "🪴🪴 "; }
        .tag-taras::before { content: "☀️ "; }
        .tag-ogrod::before { content: "🌳 "; }
        .tag-parking::before { content: "🚗 "; }
        .tag-winda::before { content: "🛗 "; }
    </style>
    <?php
});

/**
 * Klatka dla apartments-list - ekstrakcja z numeru mieszkania
 */
add_filter('develogic_local_klatka', function($klatka, $local) {
    // Przykład: "A/01" -> klatka "A"
    if (preg_match('/^([A-Z]+)\//', $local['number'], $matches)) {
        return $matches[1];
    }
    
    // Przykład: pobierz z atrybutów
    if (!empty($local['attributes'])) {
        foreach ($local['attributes'] as $attr) {
            if (stripos($attr['name'], 'klatka') !== false) {
                return $attr['value'];
            }
        }
    }
    
    return $klatka;
}, 10, 2);

/**
 * Adres budynku dla apartments-list
 */
add_filter('develogic_building_address', function($address, $building_id) {
    $addresses = array(
        1 => 'ul. Falewicza 6',
        2 => 'ul. Kowalska 10',
        3 => 'ul. Nowa 5',
    );
    
    return isset($addresses[$building_id]) ? $addresses[$building_id] : $address;
}, 10, 2);

// ============================================================================
// DOSTOSOWANIE EMAILI KONTAKTOWYCH
// ============================================================================

/**
 * Zmień domyślny email kontaktowy
 */
add_filter('develogic_contact_email', function($email, $local) {
    // Możesz ustawić różne emaile dla różnych inwestycji
    if ($local['subdivisionId'] == 3118) {
        return 'jeziornatowers@example.com';
    } elseif ($local['subdivisionId'] == 4157) {
        return 'ostoja@example.com';
    }
    
    return 'sprzedaz@example.com'; // Domyślny
}, 10, 2);

/**
 * Dostosuj temat emaila
 */
add_filter('develogic_email_subject', function($subject, $local) {
    return sprintf(
        'Zapytanie o mieszkanie %s w inwestycji %s',
        $local['number'],
        $local['subdivision']
    );
}, 10, 2);

// ============================================================================
// TRACKING I ANALYTICS
// ============================================================================

/**
 * Google Analytics - tracking kliknięć w oferty
 */
add_action('wp_footer', function() {
    if (!is_singular()) return;
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Track gallery opens
        $('.develogic-images .image-thumbnail').on('click', function() {
            var localId = $(this).closest('.develogic-a1-card').data('local-id');
            
            if (typeof gtag !== 'undefined') {
                gtag('event', 'view_offer_gallery', {
                    'event_category': 'Offers',
                    'event_label': 'Local ID: ' + localId
                });
            }
        });
        
        // Track favorite adds
        $('.action-favorite').on('click', function() {
            var localId = $(this).data('local-id');
            var isFavorited = $(this).hasClass('active');
            
            if (typeof gtag !== 'undefined') {
                gtag('event', isFavorited ? 'add_to_favorites' : 'remove_from_favorites', {
                    'event_category': 'Offers',
                    'event_label': 'Local ID: ' + localId
                });
            }
        });
        
        // Track email clicks
        $('.action-mail, .btn-mail').on('click', function() {
            var localId = $(this).closest('.develogic-a1-card').data('local-id');
            
            if (typeof gtag !== 'undefined') {
                gtag('event', 'contact_click', {
                    'event_category': 'Offers',
                    'event_label': 'Local ID: ' + localId
                });
            }
        });
    });
    </script>
    <?php
});

// ============================================================================
// PERFORMANCE I CACHE
// ============================================================================

/**
 * Prefetch danych w tle (opcjonalnie)
 */
add_action('init', function() {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }
    
    // Prefetch investments i local types przy pierwszym ładowaniu strony
    if (!develogic()->cache_manager->get_investments()) {
        $investments = develogic()->api_client->get_investments();
        if (!is_wp_error($investments)) {
            develogic()->cache_manager->set_investments($investments);
        }
    }
    
    if (!develogic()->cache_manager->get_local_types()) {
        $local_types = develogic()->api_client->get_local_types();
        if (!is_wp_error($local_types)) {
            develogic()->cache_manager->set_local_types($local_types);
        }
    }
});

/**
 * Wyczyść cache Develogic przy czyszczeniu cache WordPress
 */
add_action('wp_cache_flush', function() {
    develogic()->cache_manager->clear_all_cache();
});

// ============================================================================
// RESPONSYWNOŚĆ I MOBILE
// ============================================================================

/**
 * Dodaj viewport meta dla urządzeń mobilnych (jeśli brak w motywie)
 */
add_action('wp_head', function() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
}, 1);

/**
 * Wyłącz niektóre funkcje na mobile (opcjonalnie)
 */
add_filter('develogic_show_print_on_mobile', '__return_false');
add_filter('develogic_show_buildings_panel_on_mobile', '__return_false');

// ============================================================================
// DOSTOSOWANIA STYLU (przez PHP zamiast CSS)
// ============================================================================

/**
 * Dodaj inline styles dla brandingu
 */
add_action('wp_head', function() {
    $brand_color = get_theme_mod('brand_color', '#0066cc');
    
    ?>
    <style>
        .price-total,
        .develogic-print-btn,
        .action-btn:hover {
            background-color: <?php echo esc_attr($brand_color); ?>;
            border-color: <?php echo esc_attr($brand_color); ?>;
        }
        
        .develogic-building-card.active {
            border-color: <?php echo esc_attr($brand_color); ?>;
        }
    </style>
    <?php
}, 100);

// ============================================================================
// INTEGRACJA Z INNYMI WTYCZKAMI
// ============================================================================

/**
 * Integracja z Contact Form 7
 */
add_filter('wpcf7_form_tag', function($tag) {
    if ($tag['name'] === 'local-number' && isset($_GET['local_id'])) {
        // Pobierz numer lokalu na podstawie ID z URL
        $local_id = absint($_GET['local_id']);
        // ... fetch local data and populate
    }
    
    return $tag;
});

/**
 * Integracja z WooCommerce (opcjonalnie)
 */
add_action('woocommerce_before_add_to_cart_button', function() {
    global $product;
    
    // Jeśli produkt jest powiązany z lokalem Develogic
    $local_id = get_post_meta($product->get_id(), '_develogic_local_id', true);
    
    if ($local_id) {
        echo do_shortcode('[develogic_local id="' . $local_id . '"]');
    }
});

// ============================================================================
// DODATKOWE HOOKI (ZAAWANSOWANE)
// ============================================================================

/**
 * Modyfikuj dane lokalu przed wyświetleniem
 */
add_filter('develogic_local_data', function($local) {
    // Przykład: dodaj niestandardowe pole
    $local['custom_field'] = 'Wartość niestandardowa';
    
    return $local;
});

/**
 * Akcja po wyświetleniu karty oferty
 */
add_action('develogic_after_card_render', function($local) {
    // Log, analytics, itp.
    error_log('Wyświetlono kartę lokalu: ' . $local['localId']);
});

/**
 * Niestandardowa logika sortowania
 */
add_filter('develogic_sort_locals', function($locals, $sort_by, $sort_dir) {
    // Przykład: sortuj po niestandardowym polu
    if ($sort_by === 'custom_score') {
        usort($locals, function($a, $b) use ($sort_dir) {
            $score_a = calculate_custom_score($a);
            $score_b = calculate_custom_score($b);
            
            return $sort_dir === 'asc' ? $score_a - $score_b : $score_b - $score_a;
        });
    }
    
    return $locals;
}, 10, 3);

/**
 * Funkcja pomocnicza do obliczania niestandardowego score
 */
function calculate_custom_score($local) {
    $score = 0;
    
    // Przykład: preferuj mieszkania z balkonem
    foreach ($local['attributes'] as $attr) {
        if (stripos($attr['name'], 'balkon') !== false) {
            $score += 10;
        }
    }
    
    // Preferuj niższe piętra
    $score -= absint($local['floor']) * 2;
    
    return $score;
}

// ============================================================================
// DEBUGOWANIE
// ============================================================================

/**
 * Log wszystkich wywołań API (tylko dla developerów)
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('develogic_api_request', function($endpoint, $params) {
        error_log(sprintf(
            '[Develogic API] Request: %s | Params: %s',
            $endpoint,
            print_r($params, true)
        ));
    }, 10, 2);
    
    add_action('develogic_api_response', function($endpoint, $response) {
        error_log(sprintf(
            '[Develogic API] Response: %s | Data: %s',
            $endpoint,
            is_wp_error($response) ? $response->get_error_message() : 'Success'
        ));
    }, 10, 2);
}

