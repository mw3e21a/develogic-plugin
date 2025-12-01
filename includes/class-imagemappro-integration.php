<?php
/**
 * Develogic Image Map Pro Integration
 *
 * @package Develogic
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Develogic_ImageMapPro_Integration
 * 
 * Integrates with Image Map Pro plugin to automatically update shape colors
 * based on local status from Develogic
 */
class Develogic_ImageMapPro_Integration {
    
    /**
     * Status color mapping
     *
     * @var array
     */
    private $status_colors = array(
        'Wolny' => '7ED322',        // Green - available
        'Sprzedany' => 'ee1c24',    // Red - sold
        'Rezerwacja' => 'FFA500',   // Orange - reserved
        'Miękka rezerwacja' => 'FFA500',  // Orange - soft reservation (same as regular reservation)
        'Przeniesiona własność' => 'ee1c24',  // Red - transferred ownership (same as sold)
        'Niedostępny' => 'cccccc',  // Gray - unavailable
    );
    
    /**
     * Building to shortcode mapping
     * Maps building names/IDs to Image Map Pro shortcodes
     *
     * @var array
     */
    private $building_shortcode_map = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into Develogic sync completion
        add_action('develogic_sync_completed', array($this, 'update_image_map_pro_colors'), 10, 1);
        
        // Admin notices
        add_action('admin_notices', array($this, 'show_admin_notices'));
        
        // Load building mapping from settings
        $this->load_building_mapping();
        
        // Load custom color mapping from settings
        $this->load_color_mapping();
    }
    
    /**
     * Load building to shortcode mapping from settings
     */
    private function load_building_mapping() {
        $mapping = get_option('develogic_imagemappro_building_map', array());
        
        if (!empty($mapping) && is_array($mapping)) {
            $this->building_shortcode_map = $mapping;
        }
    }
    
    /**
     * Load custom status color mapping from settings
     */
    private function load_color_mapping() {
        $colors = get_option('develogic_imagemappro_colors', array());
        
        if (!empty($colors) && is_array($colors)) {
            $this->status_colors = array_merge($this->status_colors, $colors);
        }
    }
    
    /**
     * Update Image Map Pro colors after sync
     *
     * @param array $sync_stats Sync statistics
     * @param array $project_ids Optional. Array of specific project IDs to update
     */
    public function update_image_map_pro_colors($sync_stats, $project_ids = array()) {
        $this->log('=== Starting Image Map Pro color update ===', 'info');
        
        // Check if Image Map Pro is active
        if (!$this->is_imagemappro_active()) {
            $this->log('Image Map Pro plugin is not active', 'error');
            return;
        }
        
        $this->log('Image Map Pro is active', 'info');
        
        $updated_projects = 0;
        $updated_shapes = 0;
        
        // Get all Image Map Pro projects
        $projects = $this->get_all_imagemappro_projects();
        
        if (empty($projects)) {
            $this->log('No Image Map Pro projects found', 'error');
            return;
        }
        
        $this->log(sprintf('Found %d Image Map Pro projects', count($projects)), 'info');
        
        // Get all Develogic locals
        $locals = $this->get_all_develogic_locals();
        
        if (empty($locals)) {
            $this->log('No Develogic locals found', 'error');
            return;
        }
        
        $this->log(sprintf('Found %d Develogic locals', count($locals)), 'info');
        
        // Filter projects if specific IDs provided
        if (!empty($project_ids) && is_array($project_ids)) {
            $projects = array_filter($projects, function($project) use ($project_ids) {
                return in_array($project->id, $project_ids) || in_array($project->shortcode, $project_ids);
            });
        }
        
        // Process each project
        foreach ($projects as $project) {
            $result = $this->update_project_colors($project, $locals);
            
            if ($result['updated']) {
                $updated_projects++;
                $updated_shapes += $result['shapes_updated'];
                
                $this->log(sprintf(
                    'Updated project "%s" (shortcode: %s) - %d shapes updated',
                    $project->name,
                    $project->shortcode,
                    $result['shapes_updated']
                ));
            }
        }
        
        if ($updated_projects > 0) {
            $message = sprintf(
                __('Image Map Pro: zaktualizowano %d projektów, %d kształtów', 'develogic'),
                $updated_projects,
                $updated_shapes
            );
            
            $this->log($message, 'success');
            
            // Store notification for admin
            set_transient('develogic_imagemappro_update_notice', $message, 60);
        }
    }
    
    /**
     * Update colors for a single project
     *
     * @param object $project Image Map Pro project object
     * @param array $locals Array of Develogic locals
     * @return array Result with 'updated' flag and 'shapes_updated' count
     */
    private function update_project_colors($project, $locals) {
        $this->log(sprintf('Processing project: %s (shortcode: %s, version: %s)', 
            $project->name, 
            $project->shortcode,
            isset($project->version) ? $project->version : 'unknown'
        ), 'info');
        
        $result = array(
            'updated' => false,
            'shapes_updated' => 0,
        );
        
        // Decode project JSON (slashes already stripped in get_all_imagemappro_projects)
        $project_data = json_decode($project->json, true);
        
        if (empty($project_data) || !is_array($project_data)) {
            $json_error = json_last_error_msg();
            $this->log(sprintf('Failed to decode project JSON for: %s. Error: %s', $project->name, $json_error), 'error');
            $this->log(sprintf('JSON first 200 chars: %s', substr($project->json, 0, 200)), 'error');
            return $result;
        }
        
        $this->log('JSON decoded successfully', 'success');
        
        $modified = false;
        $version = isset($project->version) ? $project->version : 'new';
        
        // Handle different versions
        if ($version === 'old') {
            // OLD version: spots array directly in project
            if (empty($project_data['spots']) || !is_array($project_data['spots'])) {
                $this->log('No spots found in OLD version project: ' . $project->name, 'warning');
                return $result;
            }
            
            $this->log(sprintf('OLD version project has %d spots', count($project_data['spots'])), 'info');
            
            // Process each spot
            foreach ($project_data['spots'] as &$shape) {
                if ($this->process_single_shape($shape, $locals, $project)) {
                    $modified = true;
                    $result['shapes_updated']++;
                }
            }
            
        } else {
            // NEW version: artboards with children
            if (empty($project_data['artboards']) || !is_array($project_data['artboards'])) {
                $this->log('No artboards found in NEW version project: ' . $project->name, 'warning');
                return $result;
            }
            
            $this->log(sprintf('NEW version project has %d artboards', count($project_data['artboards'])), 'info');
            
            // Process each artboard
            foreach ($project_data['artboards'] as &$artboard) {
                if (empty($artboard['children']) || !is_array($artboard['children'])) {
                    continue;
                }
                
                $this->log(sprintf('Processing artboard with %d shapes', count($artboard['children'])), 'info');
                
                // Process each shape (polygon/spot)
                foreach ($artboard['children'] as &$shape) {
                    if ($this->process_single_shape($shape, $locals, $project)) {
                        $modified = true;
                        $result['shapes_updated']++;
                    }
                }
            }
        }
        
        if ($modified) {
            // Encode and save with JSON_UNESCAPED_UNICODE to preserve m² and other Unicode characters
            $new_json = wp_json_encode($project_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            if ($this->save_project_json($project->id, $new_json, $version)) {
                $result['updated'] = true;
            }
        }
        
        return $result;
    }
    
    /**
     * Process a single shape (spot/polygon)
     *
     * @param array &$shape Shape data (passed by reference)
     * @param array $locals Array of Develogic locals
     * @param object $project Project object
     * @return bool True if shape was modified
     */
    private function process_single_shape(&$shape, $locals, $project) {
        $shape_title = isset($shape['title']) ? $shape['title'] : 'untitled';
        
        // Try to match shape with local
        $local = $this->find_local_for_shape($shape, $locals, $project);
        
        if (!$local) {
            $this->log(sprintf('No match for shape "%s"', $shape_title), 'info');
            return false;
        }
        
        $this->log(sprintf('Found match for shape "%s" -> local %s', $shape_title, $local['number']), 'success');
        
        // Get status
        $status = isset($local['status']) ? $local['status'] : '';
        
        if (empty($status)) {
            $this->log(sprintf('Local %s has no status', $local['number']), 'warning');
            return false;
        }
        
        // Get color for status
        $color = $this->get_color_for_status($status);
        
        if (empty($color)) {
            $this->log(sprintf('No color mapped for status "%s"', $status), 'warning');
            return false;
        }
        
        $this->log(sprintf('Updating shape "%s" to color #%s (status: %s)', $shape_title, $color, $status), 'info');
        
        $modified = false;
        
        // Update shape color
        if ($this->update_shape_color($shape, $color)) {
            $modified = true;
            $this->log(sprintf('Successfully updated color for shape "%s"', $shape_title), 'success');
        }
        
        // Update tooltip content
        if ($this->update_shape_tooltip($shape, $local)) {
            $modified = true;
            $this->log(sprintf('Successfully updated tooltip for shape "%s"', $shape_title), 'success');
        }
        
        return $modified;
    }
    
    /**
     * Find Develogic local that matches a shape
     *
     * @param array $shape Shape data
     * @param array $locals Array of locals
     * @param object $project Project object
     * @return array|null Local data or null
     */
    private function find_local_for_shape($shape, $locals, $project) {
        // Try to extract local number from shape title
        $shape_title = isset($shape['title']) ? trim($shape['title']) : '';
        
        if (empty($shape_title)) {
            return null;
        }
        
        // Determine which buildings this project belongs to (can be multiple!)
        $building_ids = array();
        $building_names = array();
        
        // Check if we have a mapping for this shortcode
        if (!empty($this->building_shortcode_map)) {
            $this->log(sprintf('Building map: %s', json_encode($this->building_shortcode_map)), 'info');
            
            // Collect ALL buildings that map to this shortcode
            foreach ($this->building_shortcode_map as $building => $shortcodes) {
                // Handle both single shortcode (string) and multiple (array) - backwards compatibility
                $shortcodes_array = is_array($shortcodes) ? $shortcodes : array($shortcodes);
                
                // Check if this project shortcode is in the building's shortcodes
                if (in_array($project->shortcode, $shortcodes_array)) {
                    // Try to parse building info
                    if (is_numeric($building)) {
                        $building_ids[] = intval($building);
                        $this->log(sprintf('Matched project %s to building ID: %d', $project->shortcode, $building), 'info');
                    } else {
                        $building_names[] = $building;
                        $this->log(sprintf('Matched project %s to building NAME: %s', $project->shortcode, $building), 'info');
                    }
                    // DON'T break - collect all buildings!
                }
            }
        } else {
            $this->log('Building map is empty!', 'warning');
        }
        
        // Search for local by number
        foreach ($locals as $local) {
            $local_number = isset($local['number']) ? trim($local['number']) : '';
            $local_external_number = isset($local['externalNumber']) ? trim($local['externalNumber']) : '';
            
            // Debug logging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                if ($shape_title === 'M63' || $local_number === 'M63') {
                    error_log(sprintf(
                        '[ImageMapPro Debug] Comparing shape "%s" with local "%s" (external: "%s")',
                        $shape_title,
                        $local_number,
                        $local_external_number
                    ));
                }
            }
            
            // Match by number or external number
            if ($shape_title === $local_number || $shape_title === $local_external_number) {
                // Found a number match!
                $this->log(sprintf(
                    'Found number match: shape "%s" = local "%s", checking building...',
                    $shape_title,
                    $local_number
                ), 'info');
                
                // If we have building filters, check if it matches ANY of them
                if (!empty($building_ids)) {
                    $local_building_id = isset($local['buildingId']) ? intval($local['buildingId']) : null;
                    $this->log(sprintf(
                        'Comparing building IDs: expected one of [%s], local has=%s',
                        implode(', ', $building_ids),
                        $local_building_id
                    ), 'info');
                    
                    if (in_array($local_building_id, $building_ids)) {
                        $this->log(sprintf(
                            'Matched shape "%s" to local %s (building ID: %d)',
                            $shape_title,
                            $local_number,
                            $local_building_id
                        ), 'success');
                        return $local;
                    } else {
                        $this->log(sprintf(
                            'Building ID mismatch for "%s": expected one of [%s], got %s',
                            $local_number,
                            implode(', ', $building_ids),
                            $local_building_id
                        ), 'warning');
                    }
                } elseif (!empty($building_names)) {
                    $local_building_name = isset($local['building']) ? $local['building'] : null;
                    $this->log(sprintf(
                        'Comparing building names: expected one of [%s], local has="%s"',
                        implode(', ', $building_names),
                        $local_building_name
                    ), 'info');
                    
                    if (in_array($local_building_name, $building_names)) {
                        $this->log(sprintf(
                            'Matched shape "%s" to local %s (building: %s)',
                            $shape_title,
                            $local_number,
                            $local_building_name
                        ), 'success');
                        return $local;
                    } else {
                        $this->log(sprintf(
                            'Building name mismatch for "%s": expected one of [%s], got "%s"',
                            $local_number,
                            implode(', ', $building_names),
                            $local_building_name
                        ), 'warning');
                    }
                } else {
                    // No building filter, return first match
                    $this->log(sprintf(
                        'Matched shape "%s" to local %s (no building filter)',
                        $shape_title,
                        $local_number
                    ), 'success');
                    return $local;
                }
            }
        }
        
        // Log when shape is not found
        $this->log(sprintf(
            'No local found for shape "%s" in project %s',
            $shape_title,
            $project->shortcode
        ), 'warning');
        
        return null;
    }
    
    /**
     * Get color for status
     *
     * @param string $status Status name
     * @return string|null Hex color without #
     */
    private function get_color_for_status($status) {
        if (isset($this->status_colors[$status])) {
            return ltrim($this->status_colors[$status], '#');
        }
        
        return null;
    }
    
    /**
     * Update shape color
     *
     * @param array &$shape Shape data (passed by reference)
     * @param string $color Hex color without #
     * @return bool True if color was updated
     */
    private function update_shape_color(&$shape, $color) {
        $updated = false;
        
        // Update default_style background_color
        if (isset($shape['default_style']) && is_array($shape['default_style'])) {
            $old_color = isset($shape['default_style']['background_color']) ? $shape['default_style']['background_color'] : '';
            
            // Only update if color is different
            if ($old_color !== $color) {
                $shape['default_style']['background_color'] = $color;
                $updated = true;
            }
        }
        
        // Update mouseover_style to match color but with lighter opacity
        if ($updated) {
            if (!isset($shape['mouseover_style'])) {
                $shape['mouseover_style'] = array();
            }
            
            // Set same color as default_style
            $shape['mouseover_style']['background_color'] = $color;
            
            // Get default opacity, or use 0.7 as default
            $default_opacity = isset($shape['default_style']['background_opacity']) 
                ? floatval($shape['default_style']['background_opacity']) 
                : 0.7;
            
            // Reduce opacity by 0.2 to make it lighter on hover (but not less than 0.2)
            $hover_opacity = max(0.2, $default_opacity - 0.2);
            $shape['mouseover_style']['background_opacity'] = $hover_opacity;
        }
        
        return $updated;
    }
    
    /**
     * Update shape tooltip content
     *
     * @param array &$shape Shape data (passed by reference)
     * @param array $local Local data from Develogic
     * @return bool True if tooltip was updated
     */
    private function update_shape_tooltip(&$shape, $local) {
        $shape_title = isset($shape['title']) ? $shape['title'] : 'untitled';
        $local_type = isset($local['localType']) ? trim($local['localType']) : '';
        $local_number = isset($local['number']) ? trim($local['number']) : '';
        $local_name = isset($local['name']) ? trim($local['name']) : '';
        
        // Check if tooltip is disabled - enable it if so
        $tooltip_was_disabled = false;
        if (isset($shape['tooltip']['enable_tooltip']) && $shape['tooltip']['enable_tooltip'] === false) {
            $tooltip_was_disabled = true;
            // Initialize tooltip object if it doesn't exist
            if (!isset($shape['tooltip'])) {
                $shape['tooltip'] = array();
            }
            // Enable tooltip
            $shape['tooltip']['enable_tooltip'] = true;
            $this->log(sprintf('Tooltip was disabled for shape "%s", enabling it', $shape_title), 'info');
        }
        
        $this->log(sprintf('Updating tooltip for shape "%s" (localType: %s)', $shape_title, $local_type ? $local_type : 'empty'), 'info');
        
        // Check if it's an apartment (mieszkanie)
        $is_apartment = false;
        if (!empty($local_type)) {
            // Check for common apartment type names
            $apartment_types = array('Lokal mieszkalny', 'Mieszkanie', 'mieszkanie');
            $is_apartment = in_array($local_type, $apartment_types, true) || 
                          stripos($local_type, 'mieszkanie') !== false ||
                          stripos($local_type, 'mieszkalny') !== false;
        }
        
        // Build tooltip content HTML
        $tooltip_html_parts = array();
        
        if ($is_apartment) {
            // For apartments: show number, area, and balcony (if exists)
            if (!empty($local_number)) {
                $tooltip_html_parts[] = '<div>Mieszkanie ' . esc_html($local_number) . '</div>';
            }
            
            // Area
            $area = isset($local['area']) ? floatval($local['area']) : 0;
            if ($area > 0) {
                $area_formatted = number_format($area, 2, ',', '');
                $tooltip_html_parts[] = '<div>pow. ' . esc_html($area_formatted) . ' m²</div>';
            }
            
            // Balcony (if exists)
            $balcony = isset($local['areaBalcony']) ? floatval($local['areaBalcony']) : 0;
            if ($balcony > 0) {
                $balcony_formatted = number_format($balcony, 2, ',', '');
                $tooltip_html_parts[] = '<div>balkon ' . esc_html($balcony_formatted) . ' m²</div>';
            }
        } else {
            // For non-apartments: just show the name
            $display_name = !empty($local_name) ? $local_name : (!empty($local_number) ? $local_number : '');
            
            if (!empty($display_name)) {
                $tooltip_html_parts[] = '<div>' . esc_html($display_name) . '</div>';
            }
        }
        
        // Only update if we have content
        if (empty($tooltip_html_parts)) {
            $this->log(sprintf('No tooltip content to generate for shape "%s"', $shape_title), 'info');
            return false;
        }
        
        // Build tooltip HTML
        $tooltip_html = implode('', $tooltip_html_parts);
        
        // Check if tooltip content has changed and detect structure type
        $old_tooltip = isset($shape['tooltip_content']) ? $shape['tooltip_content'] : null;
        $tooltip_changed = true;
        $use_new_structure = false; // Default to old structure
        
        // Check if old tooltip exists and compare content
        if ($old_tooltip) {
            // Detect structure type: new structure has squares_settings
            if (is_array($old_tooltip) && isset($old_tooltip['squares_settings'])) {
                $use_new_structure = true;
                $this->log(sprintf('Detected NEW tooltip structure for shape "%s"', $shape_title), 'info');
            } elseif (is_array($old_tooltip) && !empty($old_tooltip) && isset($old_tooltip[0]['type'])) {
                $use_new_structure = false;
                $this->log(sprintf('Detected OLD tooltip structure for shape "%s"', $shape_title), 'info');
            }
            
            // Handle both old structure (array with type/text) and new structure (squares_settings)
            $old_text = '';
            
            if (is_array($old_tooltip) && !empty($old_tooltip)) {
                // Old structure: array with objects
                if (isset($old_tooltip[0]['text'])) {
                    $old_text = $old_tooltip[0]['text'];
                } elseif (isset($old_tooltip['squares_settings']['containers'][0]['settings']['elements'])) {
                    // New structure: squares_settings - convert to text for comparison
                    $old_elements = $old_tooltip['squares_settings']['containers'][0]['settings']['elements'];
                    // Try to extract text from elements
                    if (!empty($old_elements)) {
                        // Check for heading text
                        if (isset($old_elements[0]['options']['heading']['text'])) {
                            $old_text = $old_elements[0]['options']['heading']['text'];
                        } elseif (isset($old_elements[0]['options']['text']['text'])) {
                            $old_text = $old_elements[0]['options']['text']['text'];
                        }
                    }
                }
            }
            
            // Compare tooltip text content
            if ($old_text === $tooltip_html) {
                $tooltip_changed = false;
                $this->log(sprintf('Tooltip content unchanged for shape "%s"', $shape_title), 'info');
            } else {
                $this->log(sprintf('Tooltip content changed for shape "%s" (old: "%s", new: "%s")', 
                    $shape_title, 
                    substr($old_text, 0, 50), 
                    substr($tooltip_html, 0, 50)
                ), 'info');
            }
        } else {
            // No tooltip exists - create new one (default to old structure)
            $this->log(sprintf('No existing tooltip for shape "%s", creating new one (old structure)', $shape_title), 'info');
            $tooltip_changed = true;
        }
        
        // Build tooltip_content structure based on detected structure type
        if ($use_new_structure) {
            // NEW structure: squares_settings with containers and elements
            $tooltip_elements = array();
            
            // Heading element
            if (!empty($local_number) || !empty($local_name)) {
                $tooltip_elements[] = array(
                    'settings' => array(
                        'name' => 'Heading',
                        'iconClass' => 'fa fa-header'
                    ),
                    'options' => array(
                        'heading' => array(
                            'text' => $tooltip_html
                        )
                    )
                );
            }
            
            // Generate unique container ID
            $container_id = 'sq-container-' . time() . '-' . rand(1000, 9999);
            
            $tooltip_content = array(
                'squares_settings' => array(
                    'containers' => array(
                        array(
                            'id' => $container_id,
                            'settings' => array(
                                'elements' => $tooltip_elements
                            )
                        )
                    )
                )
            );
        } else {
            // OLD structure: array with objects containing type, text, heading, style, etc.
            // Preserve existing properties if they exist, otherwise use defaults
            $existing_tooltip = null;
            if (is_array($old_tooltip) && !empty($old_tooltip) && isset($old_tooltip[0])) {
                $existing_tooltip = $old_tooltip[0];
            }
            
            // Build tooltip element preserving existing properties
            $tooltip_element = array(
                'type' => isset($existing_tooltip['type']) ? $existing_tooltip['type'] : 'Heading',
                'text' => $tooltip_html, // Always update text with dynamic data
                'heading' => isset($existing_tooltip['heading']) ? $existing_tooltip['heading'] : 'h3',
            );
            
            // Preserve 'other' if exists
            if (isset($existing_tooltip['other'])) {
                $tooltip_element['other'] = $existing_tooltip['other'];
            } else {
                $tooltip_element['other'] = array(
                    'id' => '',
                    'classes' => '',
                    'css' => ''
                );
            }
            
            // Preserve 'style' if exists, otherwise use defaults
            if (isset($existing_tooltip['style'])) {
                $tooltip_element['style'] = $existing_tooltip['style'];
            } else {
                $tooltip_element['style'] = array(
                    'fontFamily' => 'sans-serif',
                    'fontSize' => 13.8,
                    'lineHeight' => '18',
                    'color' => '#ffffff',
                    'textAlign' => 'left'
                );
            }
            
            // Preserve 'boxModel' if exists, otherwise use defaults
            if (isset($existing_tooltip['boxModel'])) {
                $tooltip_element['boxModel'] = $existing_tooltip['boxModel'];
            } else {
                $tooltip_element['boxModel'] = array(
                    'width' => 'auto',
                    'height' => 'auto',
                    'margin' => array(
                        'top' => 0,
                        'bottom' => 0,
                        'left' => 0,
                        'right' => 0
                    ),
                    'padding' => array(
                        'top' => 10,
                        'bottom' => 10,
                        'left' => 10,
                        'right' => 10
                    )
                );
            }
            
            // Preserve 'id' if exists (important for Image Map Pro)
            if (isset($existing_tooltip['id'])) {
                $tooltip_element['id'] = $existing_tooltip['id'];
            }
            
            $tooltip_content = array($tooltip_element);
        }
        
        // Update tooltip if content changed OR if tooltip was enabled
        if ($tooltip_changed || $tooltip_was_disabled) {
            $shape['tooltip_content'] = $tooltip_content;
            $structure_type = $use_new_structure ? 'new' : 'old';
            $action = $tooltip_was_disabled ? 'enabled and updated' : 'updated';
            $this->log(sprintf('Tooltip %s for shape "%s" with %s structure, content: %s', 
                $action,
                $shape_title, 
                $structure_type,
                substr($tooltip_html, 0, 100)
            ), 'success');
            return true;
        }
        
        return false;
    }
    
    /**
     * Save project JSON to database
     *
     * @param string $project_id Project ID
     * @param string $json JSON string
     * @param string $version Version ('old' or 'new')
     * @return bool Success
     */
    private function save_project_json($project_id, $json, $version = 'new') {
        global $wpdb;
        
        if ($version === 'old') {
            // OLD version: save to wp_options
            $this->log('Saving to OLD version (wp_options)', 'info');
            
            $old_options = get_option('image-map-pro-wordpress-admin-options', array());
            
            if (!isset($old_options['saves']) || !is_array($old_options['saves'])) {
                $old_options['saves'] = array();
            }
            
            // Update the specific project's JSON
            if (isset($old_options['saves'][$project_id])) {
                $old_options['saves'][$project_id]['json'] = $json;
                
                $result = update_option('image-map-pro-wordpress-admin-options', $old_options);
                
                if ($result) {
                    $this->log('Successfully saved to OLD version', 'success');
                } else {
                    $this->log('Failed to save to OLD version', 'error');
                }
                
                return $result;
            } else {
                $this->log('Project ID not found in OLD version options', 'error');
                return false;
            }
            
        } else {
            // NEW version: save to table
            $this->log('Saving to NEW version (table)', 'info');
            
            $table_name = $wpdb->prefix . 'image_map_pro_projects';
            
            $result = $wpdb->update(
                $table_name,
                array('json' => $json),
                array('id' => $project_id),
                array('%s'),
                array('%s')
            );
            
            if ($result !== false) {
                $this->log('Successfully saved to NEW version', 'success');
            } else {
                $this->log('Failed to save to NEW version: ' . $wpdb->last_error, 'error');
            }
            
            return $result !== false;
        }
    }
    
    /**
     * Get all Image Map Pro projects
     *
     * @return array Array of project objects
     */
    private function get_all_imagemappro_projects() {
        global $wpdb;
        
        $projects = array();
        
        // Try new version (table-based)
        $table_name = $wpdb->prefix . 'image_map_pro_projects';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $this->log('Detected NEW Image Map Pro version (table-based)', 'info');
            
            $table_projects = $wpdb->get_results("SELECT * FROM $table_name ORDER BY name ASC");
            
            // Strip slashes from JSON
            if (!empty($table_projects)) {
                foreach ($table_projects as $key => $value) {
                    $table_projects[$key]->json = stripslashes($value->json);
                    $table_projects[$key]->version = 'new';
                }
                $projects = array_merge($projects, $table_projects);
            }
        }
        
        // Try old version (wp_options based)
        $old_options = get_option('image-map-pro-wordpress-admin-options', false);
        
        if ($old_options && isset($old_options['saves']) && is_array($old_options['saves'])) {
            $this->log('Detected OLD Image Map Pro version (wp_options based)', 'info');
            
            foreach ($old_options['saves'] as $project_id => $project_data) {
                if (isset($project_data['json']) && isset($project_data['meta'])) {
                    $project = new stdClass();
                    $project->id = $project_id;
                    $project->name = isset($project_data['meta']['name']) ? $project_data['meta']['name'] : "Project $project_id";
                    $project->shortcode = isset($project_data['meta']['shortcode']) ? $project_data['meta']['shortcode'] : "project_$project_id";
                    
                    // Strip slashes from JSON (same as new version)
                    $project->json = stripslashes($project_data['json']);
                    $project->version = 'old';
                    
                    $projects[] = $project;
                }
            }
        }
        
        if (empty($projects)) {
            $this->log('No Image Map Pro projects found in either version', 'warning');
        } else {
            $this->log(sprintf('Found %d Image Map Pro projects', count($projects)), 'info');
        }
        
        return $projects;
    }
    
    /**
     * Get all Develogic locals from CPT
     *
     * @return array Array of local data
     */
    private function get_all_develogic_locals() {
        $args = array(
            'post_type' => 'develogic_local',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        );
        
        $query = new WP_Query($args);
        
        if (!$query->have_posts()) {
            return array();
        }
        
        $locals = array();
        
        foreach ($query->posts as $post) {
            // Get all meta data
            $meta = get_post_meta($post->ID);
            
            $local_data = array(
                'post_id' => $post->ID,
                'title' => $post->post_title,
            );
            
            // Flatten meta data
            foreach ($meta as $key => $value) {
                if (is_array($value) && count($value) === 1) {
                    $local_data[$key] = $value[0];
                } else {
                    $local_data[$key] = $value;
                }
            }
            
            $locals[] = $local_data;
        }
        
        return $locals;
    }
    
    /**
     * Check if Image Map Pro plugin is active
     *
     * @return bool
     */
    private function is_imagemappro_active() {
        return class_exists('ImageMapPro_v6') || class_exists('ImageMapPro');
    }
    
    /**
     * Log message
     *
     * @param string $message Message
     * @param string $level Log level (info, success, warning, error)
     */
    private function log($message, $level = 'info') {
        // Always log to error_log for debugging
        error_log(sprintf('[Develogic ImageMapPro] [%s] %s', strtoupper($level), $message));
        
        // Add to sync log
        $log = get_option('develogic_sync_log', array());
        
        $log[] = array(
            'time' => current_time('mysql'),
            'level' => $level,
            'message' => '[ImageMapPro] ' . $message,
        );
        
        // Keep only last 50 entries
        $log = array_slice($log, -50);
        
        update_option('develogic_sync_log', $log);
    }
    
    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        $notice = get_transient('develogic_imagemappro_update_notice');
        
        if ($notice) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . esc_html($notice) . '</p>';
            echo '</div>';
            
            delete_transient('develogic_imagemappro_update_notice');
        }
    }
    
    /**
     * Get status colors mapping
     *
     * @return array
     */
    public function get_status_colors() {
        return $this->status_colors;
    }
    
    /**
     * Set status color
     *
     * @param string $status Status name
     * @param string $color Hex color (with or without #)
     */
    public function set_status_color($status, $color) {
        $this->status_colors[$status] = ltrim($color, '#');
        
        // Save to options
        update_option('develogic_imagemappro_colors', $this->status_colors);
    }
    
    /**
     * Get building to shortcode mapping
     *
     * @return array
     */
    public function get_building_map() {
        return $this->building_shortcode_map;
    }
    
    /**
     * Set building to shortcode mapping
     *
     * @param string $building Building name or ID
     * @param string $shortcode Image Map Pro shortcode
     */
    public function set_building_map($building, $shortcode) {
        $this->building_shortcode_map[$building] = $shortcode;
        
        // Save to options
        update_option('develogic_imagemappro_building_map', $this->building_shortcode_map);
    }
    
    /**
     * Clear all mappings
     */
    public function clear_mappings() {
        $this->building_shortcode_map = array();
        delete_option('develogic_imagemappro_building_map');
    }
}

