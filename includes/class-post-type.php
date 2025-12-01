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
        
        // Ensure Custom Fields metabox is visible and registered
        if (is_admin()) {
            // Use multiple hooks to ensure metabox is added
            add_action('admin_init', array($this, 'ensure_custom_fields_support'));
            // Use high priority to ensure it runs after other plugins
            add_action('add_meta_boxes', array($this, 'add_custom_fields_metabox'), 99);
            add_filter('default_hidden_meta_boxes', array($this, 'show_custom_fields_metabox'), 10, 2);
            // Also ensure it's not hidden in user meta
            add_filter('hidden_meta_boxes', array($this, 'unhide_custom_fields_metabox'), 10, 2);
        }
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
            // Register metabox callback to ensure Custom Fields metabox is added
            'register_meta_box_cb' => array($this, 'add_custom_fields_metabox'),
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
    
    /**
     * Ensure custom-fields support is enabled
     */
    public function ensure_custom_fields_support() {
        $post_type_object = get_post_type_object('develogic_local');
        if ($post_type_object && !post_type_supports('develogic_local', 'custom-fields')) {
            add_post_type_support('develogic_local', 'custom-fields');
        }
    }
    
    /**
     * Add Custom Fields metabox for develogic_local post type
     * 
     * WordPress doesn't always automatically add the Custom Fields metabox
     * for custom post types, so we need to add it explicitly.
     */
    public function add_custom_fields_metabox($post_type = null) {
        // Get post type from parameter or screen
        if (!$post_type) {
            $screen = get_current_screen();
            $post_type = $screen ? $screen->post_type : null;
        }
        
        if ($post_type === 'develogic_local') {
            // Remove any existing postcustom metabox first (from all contexts)
            remove_meta_box('postcustom', 'develogic_local', 'normal');
            remove_meta_box('postcustom', 'develogic_local', 'side');
            remove_meta_box('postcustom', 'develogic_local', 'advanced');
            
            // Use post_custom_meta_box if available, otherwise use our own callback
            $callback = function_exists('post_custom_meta_box') 
                ? 'post_custom_meta_box' 
                : array($this, 'render_custom_fields_metabox');
            
            // Add the Custom Fields metabox explicitly
            add_meta_box(
                'postcustom',
                __('Custom Fields', 'develogic'),
                $callback,
                'develogic_local',
                'normal',
                'default',
                null // callback args
            );
        }
    }
    
    /**
     * Render Custom Fields metabox (fallback if post_custom_meta_box doesn't exist)
     * 
     * @param WP_Post $post Post object
     */
    public function render_custom_fields_metabox($post) {
        // Use WordPress's built-in function if available
        if (function_exists('post_custom_meta_box')) {
            post_custom_meta_box($post);
            return;
        }
        
        // Fallback: render basic custom fields interface
        wp_nonce_field('add-meta', '_ajax_nonce-add-meta', false);
        ?>
        <div id="postcustomstuff">
            <div id="ajax-response"></div>
            <?php
            $metadata = has_meta($post->ID);
            if ($metadata) {
                ?>
                <table id="list-table">
                    <thead>
                        <tr>
                            <th class="left"><?php _e('Name', 'develogic'); ?></th>
                            <th><?php _e('Value', 'develogic'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="the-list" data-wp-lists="list:meta">
                        <?php
                        foreach ($metadata as $key => $value) {
                            if (is_protected_meta($metadata[$key]['meta_key'], 'post')) {
                                continue;
                            }
                            ?>
                            <tr>
                                <td>
                                    <label class="screen-reader-text" for="meta-<?php echo $key; ?>-key"><?php _e('Key', 'develogic'); ?></label>
                                    <input name="meta[<?php echo $key; ?>][key]" id="meta-<?php echo $key; ?>-key" type="text" size="20" value="<?php echo esc_attr($metadata[$key]['meta_key']); ?>">
                                    <div class="submit">
                                        <input type="submit" name="deletemeta[<?php echo $key; ?>]" id="deletemeta[<?php echo $key; ?>]" class="button deletemeta button-small" value="<?php esc_attr_e('Delete', 'develogic'); ?>">
                                        <input type="submit" name="meta-<?php echo $key; ?>-submit" id="meta-<?php echo $key; ?>-submit" class="button updatemeta button-small" value="<?php esc_attr_e('Update', 'develogic'); ?>">
                                    </div>
                                </td>
                                <td>
                                    <label class="screen-reader-text" for="meta-<?php echo $key; ?>-value"><?php _e('Value', 'develogic'); ?></label>
                                    <textarea name="meta[<?php echo $key; ?>][value]" id="meta-<?php echo $key; ?>-value" rows="2" cols="30"><?php echo esc_textarea($metadata[$key]['meta_value']); ?></textarea>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                <?php
            }
            ?>
            <p><strong><?php _e('Add New Custom Field:', 'develogic'); ?></strong></p>
            <table id="newmeta">
                <thead>
                    <tr>
                        <th class="left"><label for="metakeyselect"><?php _e('Name', 'develogic'); ?></label></th>
                        <th><label for="metavalue"><?php _e('Value', 'develogic'); ?></label></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td id="newmetaleft" class="left">
                            <select id="metakeyselect" name="metakeyselect">
                                <option value="#NONE#"><?php _e('— Select —', 'develogic'); ?></option>
                            </select>
                            <input class="hide-if-js" type="text" id="metakeyinput" name="metakeyinput" value="">
                        </td>
                        <td><textarea id="metavalue" name="metavalue" rows="2" cols="25"></textarea></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div class="submit">
                                <input type="submit" name="addmeta" id="newmeta-submit" class="button" value="<?php esc_attr_e('Add Custom Field', 'develogic'); ?>">
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p><?php _e('Custom fields can be used to add extra metadata to a post that you can use in your theme.', 'develogic'); ?></p>
        <?php
    }
    
    /**
     * Ensure Custom Fields metabox is visible for develogic_local post type
     * 
     * @param array $hidden Array of hidden metabox IDs
     * @param WP_Screen $screen Current screen object
     * @return array Modified array of hidden metabox IDs
     */
    public function show_custom_fields_metabox($hidden, $screen) {
        // Ensure custom fields metabox is not hidden for develogic_local post type
        if ($screen && $screen->post_type === 'develogic_local') {
            // Remove 'postcustom' from hidden metaboxes if it's there
            $hidden = array_diff($hidden, array('postcustom'));
        }
        return $hidden;
    }
    
    /**
     * Unhide Custom Fields metabox from user preferences
     * 
     * @param array $hidden Array of hidden metabox IDs from user meta
     * @param WP_Screen $screen Current screen object
     * @return array Modified array of hidden metabox IDs
     */
    public function unhide_custom_fields_metabox($hidden, $screen) {
        // Force show custom fields metabox for develogic_local post type
        if ($screen && $screen->post_type === 'develogic_local') {
            // Remove 'postcustom' from hidden metaboxes
            $hidden = array_diff($hidden, array('postcustom'));
        }
        return $hidden;
    }
}

