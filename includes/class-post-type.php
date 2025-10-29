<?php
/**
 * Develogic Custom Post Type and Taxonomies
 *
 * @package Develogic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Develogic_Post_Type
 */
class Develogic_Post_Type {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
    }
    
    /**
     * Register Custom Post Type for locals
     */
    public function register_post_type() {
        $labels = array(
            'name' => __('Lokale', 'develogic'),
            'singular_name' => __('Lokal', 'develogic'),
            'menu_name' => __('Lokale Develogic', 'develogic'),
            'add_new' => __('Dodaj nowy', 'develogic'),
            'add_new_item' => __('Dodaj nowy lokal', 'develogic'),
            'edit_item' => __('Edytuj lokal', 'develogic'),
            'new_item' => __('Nowy lokal', 'develogic'),
            'view_item' => __('Zobacz lokal', 'develogic'),
            'search_items' => __('Szukaj lokali', 'develogic'),
            'not_found' => __('Nie znaleziono lokali', 'develogic'),
            'not_found_in_trash' => __('Nie znaleziono lokali w koszu', 'develogic'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false, // Nie pokazuj publicznie (tylko przez shortcody)
            'show_ui' => true,
            'show_in_menu' => 'develogic',
            'show_in_rest' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'rewrite' => false,
            'supports' => array('title', 'custom-fields'),
            'menu_icon' => 'dashicons-building',
        );
        
        register_post_type('develogic_local', $args);
    }
    
    /**
     * Register Taxonomies
     */
    public function register_taxonomies() {
        // Investment taxonomy
        register_taxonomy('develogic_investment', 'develogic_local', array(
            'labels' => array(
                'name' => __('Inwestycje', 'develogic'),
                'singular_name' => __('Inwestycja', 'develogic'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'hierarchical' => false,
            'show_admin_column' => true,
        ));
        
        // Local type taxonomy
        register_taxonomy('develogic_local_type', 'develogic_local', array(
            'labels' => array(
                'name' => __('Typy lokali', 'develogic'),
                'singular_name' => __('Typ lokalu', 'develogic'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'hierarchical' => false,
            'show_admin_column' => true,
        ));
        
        // Building taxonomy
        register_taxonomy('develogic_building', 'develogic_local', array(
            'labels' => array(
                'name' => __('Budynki', 'develogic'),
                'singular_name' => __('Budynek', 'develogic'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'hierarchical' => false,
            'show_admin_column' => true,
        ));
        
        // Status taxonomy
        register_taxonomy('develogic_status', 'develogic_local', array(
            'labels' => array(
                'name' => __('Statusy', 'develogic'),
                'singular_name' => __('Status', 'develogic'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'hierarchical' => false,
            'show_admin_column' => true,
        ));
    }
}

