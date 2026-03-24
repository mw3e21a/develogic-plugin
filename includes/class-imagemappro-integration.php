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
        'Wolny'                    => array('color' => '7ED322', 'opacity' => 0.65, 'hover_color' => '7ED322', 'hover_opacity' => 0.40),
        'Sprzedany'                => array('color' => 'ee1c24', 'opacity' => 0.65, 'hover_color' => 'ee1c24', 'hover_opacity' => 0.40),
        'Rezerwacja'               => array('color' => 'FFA500', 'opacity' => 0.65, 'hover_color' => 'FFA500', 'hover_opacity' => 0.40),
        'Miękka rezerwacja'        => array('color' => 'FFA500', 'opacity' => 0.65, 'hover_color' => 'FFA500', 'hover_opacity' => 0.40),
        'Przeniesiona własność'    => array('color' => 'ee1c24', 'opacity' => 0.65, 'hover_color' => 'ee1c24', 'hover_opacity' => 0.40),
        'Niedostępny'              => array('color' => 'cccccc', 'opacity' => 0.65, 'hover_color' => 'cccccc', 'hover_opacity' => 0.40),
        'Wyłączony ze sprzedaży'   => array('color' => 'cccccc', 'opacity' => 0.65, 'hover_color' => 'cccccc', 'hover_opacity' => 0.40),
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
            foreach ($colors as $status => $value) {
                if (is_array($value)) {
                    // New format
                    $this->status_colors[$status] = $value;
                } else {
                    // Old format (plain hex string) — migrate to new format
                    $hex = ltrim($value, '#');
                    $this->status_colors[$status] = array(
                        'color' => $hex,
                        'opacity' => 0.65,
                        'hover_color' => $hex,
                        'hover_opacity' => 0.40,
                    );
                }
            }
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
        
        // Update availability counters
        $counter_shapes = $this->update_availability_counters($locals, $projects);
        if ($counter_shapes > 0) {
            $updated_shapes += $counter_shapes;
            $this->log(sprintf('Updated %d availability counter shapes', $counter_shapes), 'success');
        }

        if ($updated_projects > 0 || $counter_shapes > 0) {
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
            // NEW version: try artboards first, then fall back to spots
            $has_artboards = !empty($project_data['artboards']) && is_array($project_data['artboards']);
            $has_spots = !empty($project_data['spots']) && is_array($project_data['spots']);

            if ($has_artboards) {
                $this->log(sprintf('NEW version project has %d artboards', count($project_data['artboards'])), 'info');

                foreach ($project_data['artboards'] as &$artboard) {
                    if (empty($artboard['children']) || !is_array($artboard['children'])) {
                        continue;
                    }

                    $this->log(sprintf('Processing artboard with %d shapes', count($artboard['children'])), 'info');

                    foreach ($artboard['children'] as &$shape) {
                        if ($this->process_single_shape($shape, $locals, $project)) {
                            $modified = true;
                            $result['shapes_updated']++;
                        }
                    }
                }
            } elseif ($has_spots) {
                // NEW version stored in table but uses spots structure (e.g. Image Map Pro v6 with layers)
                $this->log(sprintf('NEW version project uses spots structure, has %d spots', count($project_data['spots'])), 'info');

                foreach ($project_data['spots'] as &$shape) {
                    if ($this->process_single_shape($shape, $locals, $project)) {
                        $modified = true;
                        $result['shapes_updated']++;
                    }
                }
            } else {
                $this->log('No artboards or spots found in NEW version project: ' . $project->name, 'warning');
                return $result;
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
     * Update availability counters on configured spots
     *
     * Reads counter config from options, counts available locals per floor/building,
     * and updates the tooltip text of the configured spots.
     *
     * @param array $locals Array of Develogic locals
     * @param array $projects Array of Image Map Pro project objects
     * @return int Number of counter shapes updated
     */
    private function update_availability_counters($locals, $projects) {
        $counters = get_option('develogic_imagemappro_counters', array());
        if (empty($counters) || !is_array($counters)) {
            return 0;
        }

        $this->log(sprintf('Processing %d availability counters', count($counters)), 'info');

        // Pre-compute counts: [building_id][floor] => count of "Wolny" locals
        $counts_by_floor = array();
        $counts_by_building = array();

        foreach ($locals as $local) {
            $status = isset($local['status']) ? trim($local['status']) : '';
            if ($status !== 'Wolny') {
                continue;
            }
            $building_id = isset($local['buildingId']) ? trim($local['buildingId']) : '';
            $raw_floor = isset($local['floor']) ? trim($local['floor']) : '';
            $floor = $this->normalize_floor($raw_floor);

            if (!empty($building_id)) {
                if (!isset($counts_by_building[$building_id])) {
                    $counts_by_building[$building_id] = 0;
                }
                $counts_by_building[$building_id]++;

                $floor_key = $building_id . '_' . $floor;
                if (!isset($counts_by_floor[$floor_key])) {
                    $counts_by_floor[$floor_key] = 0;
                }
                $counts_by_floor[$floor_key]++;
            }
        }

        $this->log(sprintf('Availability counts: %d buildings, building keys: [%s], floor keys: [%s]',
            count($counts_by_building),
            implode(', ', array_map(function($k, $v) { return $k . '=' . $v; }, array_keys($counts_by_building), $counts_by_building)),
            implode(', ', array_map(function($k, $v) { return $k . '=' . $v; }, array_keys($counts_by_floor), $counts_by_floor))
        ), 'info');

        // Log counter config for debugging
        foreach ($counters as $ci => $c) {
            $this->log(sprintf('Counter config [%d]: shortcode=%s, spot=%s, mode=%s, building_id="%s", floor="%s"',
                $ci, $c['shortcode'], $c['spot_id'], $c['mode'], $c['building_id'], $c['floor']
            ), 'info');
        }

        // Log first few "Wolny" locals for debugging
        $debug_count = 0;
        foreach ($locals as $local) {
            $status = isset($local['status']) ? trim($local['status']) : '';
            if ($status === 'Wolny' && $debug_count < 5) {
                $this->log(sprintf('Sample Wolny local: number=%s, buildingId="%s" (type=%s), floor="%s" (type=%s)',
                    isset($local['number']) ? $local['number'] : '?',
                    isset($local['buildingId']) ? $local['buildingId'] : 'MISSING',
                    isset($local['buildingId']) ? gettype($local['buildingId']) : 'N/A',
                    isset($local['floor']) ? $local['floor'] : 'MISSING',
                    isset($local['floor']) ? gettype($local['floor']) : 'N/A'
                ), 'info');
                $debug_count++;
            }
        }

        // Index projects by shortcode for quick lookup
        $projects_by_shortcode = array();
        foreach ($projects as $project) {
            $projects_by_shortcode[$project->shortcode] = $project;
        }

        $updated = 0;

        // Group counters by project shortcode to avoid re-decoding JSON multiple times
        $counters_by_project = array();
        foreach ($counters as $counter) {
            $sc = $counter['shortcode'];
            if (!isset($counters_by_project[$sc])) {
                $counters_by_project[$sc] = array();
            }
            $counters_by_project[$sc][] = $counter;
        }

        foreach ($counters_by_project as $shortcode => $project_counters) {
            if (!isset($projects_by_shortcode[$shortcode])) {
                $this->log(sprintf('Counter: project "%s" not found', $shortcode), 'warning');
                continue;
            }

            $project = $projects_by_shortcode[$shortcode];

            // Re-read fresh JSON from database (colors may have been updated by update_project_colors)
            $fresh_json = $this->get_project_json_from_db($project->id, isset($project->version) ? $project->version : 'new');
            if (empty($fresh_json)) {
                $fresh_json = $project->json;
            }

            $project_data = json_decode($fresh_json, true);
            if (empty($project_data)) {
                $this->log(sprintf('Counter: empty project data for "%s"', $shortcode), 'warning');
                continue;
            }

            // Collect references to all spots (from spots array and/or artboards)
            $all_spots_refs = array();
            if (!empty($project_data['spots']) && is_array($project_data['spots'])) {
                foreach ($project_data['spots'] as $sk => &$s) {
                    $all_spots_refs[] = &$project_data['spots'][$sk];
                }
                unset($s);
            }
            if (!empty($project_data['artboards']) && is_array($project_data['artboards'])) {
                foreach ($project_data['artboards'] as $ak => &$artboard) {
                    if (!empty($artboard['children']) && is_array($artboard['children'])) {
                        foreach ($artboard['children'] as $ck => &$child) {
                            $all_spots_refs[] = &$project_data['artboards'][$ak]['children'][$ck];
                        }
                        unset($child);
                    }
                }
                unset($artboard);
            }

            if (empty($all_spots_refs)) {
                $this->log(sprintf('Counter: no spots in project "%s"', $shortcode), 'warning');
                continue;
            }

            $version = isset($project->version) ? $project->version : 'new';
            $project_modified = false;

            foreach ($project_counters as $counter) {
                $spot_id = $counter['spot_id'];
                $mode = $counter['mode'];
                $building_id = $counter['building_id'];
                $floor = $counter['floor'];
                $template = !empty($counter['template']) ? $counter['template'] : 'Ilość dostępnych mieszkań - {count}';

                // Compute count
                if ($mode === 'building') {
                    $count = isset($counts_by_building[$building_id]) ? $counts_by_building[$building_id] : 0;
                } else {
                    $floor_key = $building_id . '_' . $floor;
                    $count = isset($counts_by_floor[$floor_key]) ? $counts_by_floor[$floor_key] : 0;
                }

                // Find the spot in all spots (spots + artboards)
                $spot_found = false;
                foreach ($all_spots_refs as &$spot) {
                    $sid = isset($spot['id']) ? $spot['id'] : '';
                    if ($sid !== $spot_id) {
                        continue;
                    }

                    $spot_found = true;
                    $spot_name = isset($spot['title']) ? $spot['title'] : '';

                    // Build the display text from template
                    $display_text = str_replace(
                        array('{name}', '{count}'),
                        array($spot_name, $count),
                        $template
                    );

                    $this->log(sprintf(
                        'Counter: spot "%s" (%s) -> %s (mode=%s, building=%s, floor=%s, count=%d)',
                        $spot_name, $spot_id, $display_text, $mode, $building_id, $floor, $count
                    ), 'info');

                    // Update tooltip content with the counter text
                    if ($this->update_counter_tooltip($spot, $display_text)) {
                        $project_modified = true;
                        $updated++;
                    }
                    break;
                }
                unset($spot);

                if (!$spot_found) {
                    $this->log(sprintf('Counter: spot "%s" not found in project "%s"', $spot_id, $shortcode), 'warning');
                }
            }

            // Save project if modified
            if ($project_modified) {
                $new_json = wp_json_encode($project_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $this->save_project_json($project->id, $new_json, $version);
            }
        }

        return $updated;
    }

    /**
     * Update tooltip of a counter spot with availability text
     *
     * Preserves the existing first container (heading) and adds/updates
     * a second container (Paragraph) with the counter text.
     *
     * @param array &$spot Spot data (passed by reference)
     * @param string $text Display text (e.g. "Ilość dostępnych mieszkań - 5")
     * @return bool True if tooltip was modified
     */
    private function update_counter_tooltip(&$spot, $text) {
        $old_tooltip = isset($spot['tooltip_content']) ? $spot['tooltip_content'] : null;
        $use_new_structure = is_array($old_tooltip) && isset($old_tooltip['squares_settings']);

        if ($use_new_structure) {
            $containers = isset($old_tooltip['squares_settings']['containers'])
                ? $old_tooltip['squares_settings']['containers']
                : array();

            // Find existing counter container (second container with Paragraph)
            $counter_index = null;
            $old_text = '';
            for ($i = 1; $i < count($containers); $i++) {
                $els = isset($containers[$i]['settings']['elements']) ? $containers[$i]['settings']['elements'] : array();
                foreach ($els as $el) {
                    $name = isset($el['settings']['name']) ? $el['settings']['name'] : '';
                    if ($name === 'Paragraph') {
                        $counter_index = $i;
                        $old_text = isset($el['options']['text']['text']) ? $el['options']['text']['text'] : '';
                        break 2;
                    }
                }
            }

            if ($old_text === $text) {
                return false;
            }

            // Build the counter container
            $counter_container_id = 'sq-container-counter-' . (isset($spot['id']) ? $spot['id'] : rand(1000, 9999));
            if ($counter_index !== null && isset($containers[$counter_index]['id'])) {
                $counter_container_id = $containers[$counter_index]['id'];
            }

            $counter_container = array(
                'id' => $counter_container_id,
                'settings' => array(
                    'elements' => array(
                        array(
                            'settings' => array(
                                'name' => 'Paragraph',
                                'iconClass' => 'fa fa-paragraph',
                            ),
                            'options' => array(
                                'text' => array('text' => $text),
                                'font' => array('line_height' => '14'),
                            ),
                        ),
                    ),
                ),
            );

            if ($counter_index !== null) {
                // Update existing counter container
                $containers[$counter_index] = $counter_container;
            } else {
                // Append new counter container after the heading
                $containers[] = $counter_container;
            }

            $spot['tooltip_content']['squares_settings']['containers'] = $containers;
        } else {
            // Old structure: array of tooltip elements
            // Preserve first element, add/update second with counter text
            $old_elements = is_array($old_tooltip) ? $old_tooltip : array();

            $old_text = '';
            if (isset($old_elements[1]['text'])) {
                $old_text = $old_elements[1]['text'];
            }
            if ($old_text === $text) {
                return false;
            }

            $counter_element = array(
                'type'    => 'Paragraph',
                'text'    => $text,
                'heading' => '',
                'other'   => array('id' => '', 'classes' => '', 'css' => ''),
                'style'   => array(
                    'fontFamily' => 'sans-serif',
                    'fontSize'   => 14,
                    'lineHeight' => '18',
                    'fontWeight' => 'normal',
                    'color'      => '#ffffff',
                    'textAlign'  => 'center',
                ),
            );

            if (isset($old_elements[1]['boxModel'])) {
                $counter_element['boxModel'] = $old_elements[1]['boxModel'];
            }

            if (empty($old_elements)) {
                // No tooltip at all — create heading + counter
                $spot['tooltip_content'] = array(
                    array(
                        'type'    => 'Heading',
                        'text'    => isset($spot['title']) ? $spot['title'] : '',
                        'heading' => 'h3',
                        'style'   => array(
                            'fontFamily' => 'sans-serif',
                            'fontSize'   => 18,
                            'fontWeight' => 'bold',
                            'color'      => '#ffffff',
                            'textAlign'  => 'center',
                        ),
                    ),
                    $counter_element,
                );
            } else {
                // Keep first element (heading), set second as counter
                $spot['tooltip_content'] = array($old_elements[0], $counter_element);
            }
        }

        return true;
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
        
        // Get color config for status
        $color_config = $this->get_color_for_status($status);

        if (empty($color_config)) {
            $this->log(sprintf('No color mapped for status "%s"', $status), 'warning');
            return false;
        }

        $this->log(sprintf('Updating shape "%s" to color #%s (status: %s)', $shape_title, $color_config['color'], $status), 'info');

        $modified = false;

        // Update shape color
        if ($this->update_shape_color($shape, $color_config)) {
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
     * Uses the building mapping to determine which building a shape belongs to.
     * Mapping entries can include layer filters (format: "shortcode:layer1,layer2")
     * to disambiguate shapes in multi-building projects.
     *
     * @param array $shape Shape data
     * @param array $locals Array of locals
     * @param object $project Project object
     * @return array|null Local data or null
     */
    private function find_local_for_shape($shape, $locals, $project) {
        $shape_title = isset($shape['title']) ? trim($shape['title']) : '';

        if (empty($shape_title)) {
            return null;
        }

        $shape_layer_id = isset($shape['layerID']) ? $shape['layerID'] : null;

        // Find which building ID this shape belongs to based on shortcode + layer mapping
        $matched_building_id = $this->resolve_building_for_shape($project->shortcode, $shape_layer_id);

        if ($matched_building_id !== null) {
            $this->log(sprintf(
                'Shape "%s" (layer %s) resolved to building ID: %d',
                $shape_title, $shape_layer_id, $matched_building_id
            ), 'info');

            // Search for local matching this number AND building
            foreach ($locals as $local) {
                $local_number = isset($local['number']) ? trim($local['number']) : '';
                $local_external = isset($local['externalNumber']) ? trim($local['externalNumber']) : '';

                if ($shape_title === $local_number || $shape_title === $local_external) {
                    $local_building_id = isset($local['buildingId']) ? intval($local['buildingId']) : null;
                    if ($local_building_id === $matched_building_id) {
                        $this->log(sprintf(
                            'Matched shape "%s" to local %s (building ID: %d)',
                            $shape_title, $local_number, $matched_building_id
                        ), 'success');
                        return $local;
                    }
                }
            }

            $this->log(sprintf(
                'No local found for shape "%s" in building %d',
                $shape_title, $matched_building_id
            ), 'warning');
            return null;
        }

        // No mapping resolved — fallback: return first matching local (legacy behavior)
        $this->log(sprintf(
            'No building mapping for shape "%s" in project %s (layer %s) — using first match',
            $shape_title, $project->shortcode, $shape_layer_id
        ), 'warning');

        foreach ($locals as $local) {
            $local_number = isset($local['number']) ? trim($local['number']) : '';
            $local_external = isset($local['externalNumber']) ? trim($local['externalNumber']) : '';

            if ($shape_title === $local_number || $shape_title === $local_external) {
                return $local;
            }
        }

        return null;
    }

    /**
     * Resolve which building ID a shape belongs to based on project shortcode and layer
     *
     * Mapping format: building_id => ["shortcode", "shortcode:layer1,layer2", ...]
     *
     * @param string $project_shortcode The Image Map Pro project shortcode
     * @param int|null $shape_layer_id The layer ID of the shape (null if no layers)
     * @return int|null Building ID or null if no mapping found
     */
    private function resolve_building_for_shape($project_shortcode, $shape_layer_id) {
        if (empty($this->building_shortcode_map)) {
            return null;
        }

        foreach ($this->building_shortcode_map as $building_key => $entries) {
            if (!is_numeric($building_key)) {
                continue;
            }
            $building_id = intval($building_key);
            $entries_array = is_array($entries) ? $entries : array($entries);

            foreach ($entries_array as $entry) {
                // Parse entry: "shortcode" or "shortcode:layer1,layer2"
                if (strpos($entry, ':') !== false) {
                    list($entry_shortcode, $layers_str) = explode(':', $entry, 2);
                    $allowed_layers = array_map('intval', explode(',', $layers_str));
                } else {
                    $entry_shortcode = $entry;
                    $allowed_layers = array(); // empty = all layers
                }

                // Check if shortcode matches
                if ($entry_shortcode !== $project_shortcode) {
                    continue;
                }

                // If no layer restriction, this building matches
                if (empty($allowed_layers)) {
                    return $building_id;
                }

                // If shape has a layer, check if it's in the allowed list
                if ($shape_layer_id !== null && in_array(intval($shape_layer_id), $allowed_layers)) {
                    return $building_id;
                }
            }
        }

        return null;
    }
    
    /**
     * Get color config for status
     *
     * @param string $status Status name
     * @return array|null Array with color, opacity, hover_color, hover_opacity
     */
    private function get_color_for_status($status) {
        if (isset($this->status_colors[$status])) {
            return $this->status_colors[$status];
        }

        return null;
    }
    
    /**
     * Update shape color
     *
     * @param array &$shape Shape data (passed by reference)
     * @param array $config Array with color, opacity, hover_color, hover_opacity
     * @return bool True if color was updated
     */
    private function update_shape_color(&$shape, $config) {
        $color = ltrim($config['color'], '#');
        $opacity = floatval($config['opacity']);
        $hover_color = ltrim($config['hover_color'], '#');
        $hover_opacity = floatval($config['hover_opacity']);

        if (!isset($shape['default_style']) || !is_array($shape['default_style'])) {
            $shape['default_style'] = array();
        }
        if (!isset($shape['mouseover_style'])) {
            $shape['mouseover_style'] = array();
        }

        $old_color = isset($shape['default_style']['background_color']) ? $shape['default_style']['background_color'] : '';
        $old_opacity = isset($shape['default_style']['background_opacity']) ? floatval($shape['default_style']['background_opacity']) : -1;
        $old_hover_color = isset($shape['mouseover_style']['background_color']) ? $shape['mouseover_style']['background_color'] : '';
        $old_hover_opacity = isset($shape['mouseover_style']['background_opacity']) ? floatval($shape['mouseover_style']['background_opacity']) : -1;

        if ($old_color === $color && $old_opacity === $opacity && $old_hover_color === $hover_color && $old_hover_opacity === $hover_opacity) {
            return false;
        }

        $shape['default_style']['background_color'] = $color;
        $shape['default_style']['background_opacity'] = $opacity;
        $shape['mouseover_style']['background_color'] = $hover_color;
        $shape['mouseover_style']['background_opacity'] = $hover_opacity;

        return true;
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
        
        // Get status and normalize it
        $status = isset($local['status']) ? trim($local['status']) : '';
        // Normalize "Miękka rezerwacja" to "Rezerwacja" for consistency
        if ($status === 'Miękka rezerwacja') {
            $status = 'Rezerwacja';
        }
        // Change "Wolny" to "Dostępny" for tooltip display
        if ($status === 'Wolny') {
            $status = 'Dostępny';
        }
        
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
        
        // Add status at the top of tooltip
        if (!empty($status)) {
            $tooltip_html_parts[] = '<div>' . esc_html($status) . '</div>';
        }
        
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
            // Use consistent formatting for all tooltips (garages, cells, storage rooms, apartments)
            $existing_tooltip = null;
            if (is_array($old_tooltip) && !empty($old_tooltip) && isset($old_tooltip[0])) {
                $existing_tooltip = $old_tooltip[0];
            }
            
            // Build tooltip element with consistent formatting
            // Always use Heading type with h3 for uniform appearance
            $tooltip_element = array(
                'type' => 'Heading',
                'text' => $tooltip_html, // Always update text with dynamic data
                'heading' => 'h3',
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
            
            // Use consistent style for all tooltips (bold, uniform font size)
            $tooltip_element['style'] = array(
                'fontFamily' => 'sans-serif',
                'fontSize' => 14,
                'lineHeight' => '22',
                'fontWeight' => 'bold',
                'color' => '#ffffff',
                'textAlign' => 'left'
            );
            
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
     * Normalize floor value to a simple number string
     *
     * Converts values like "4 piętro", "parter", "piwnica", "Piętro IV" etc. to "4", "0", "-1"
     *
     * @param string $floor Raw floor value from Develogic
     * @return string Normalized floor number as string
     */
    private function normalize_floor($floor) {
        $floor = trim($floor);
        $lower = mb_strtolower($floor, 'UTF-8');

        // Already a plain number
        if (is_numeric($floor)) {
            return (string) intval($floor);
        }

        // Special cases
        if ($lower === 'parter' || $lower === 'ground' || $lower === 'parter/ground') {
            return '0';
        }
        if ($lower === 'piwnica' || $lower === 'basement' || $lower === '-1 piętro') {
            return '-1';
        }

        // "4 piętro", "4 pietro", "piętro 4", "pietro 4", "4. piętro" etc.
        if (preg_match('/(\d+)/', $floor, $m)) {
            return $m[1];
        }

        // Roman numerals: "Piętro IV", "IV piętro"
        $roman_map = array(
            'I' => '1', 'II' => '2', 'III' => '3', 'IV' => '4', 'V' => '5',
            'VI' => '6', 'VII' => '7', 'VIII' => '8', 'IX' => '9', 'X' => '10',
        );
        foreach ($roman_map as $roman => $num) {
            if (preg_match('/\b' . $roman . '\b/', $floor)) {
                return $num;
            }
        }

        // Fallback — return as-is
        return $floor;
    }

    /**
     * Get fresh project JSON from database
     *
     * @param string $project_id Project ID
     * @param string $version Version ('old' or 'new')
     * @return string|null JSON string or null
     */
    private function get_project_json_from_db($project_id, $version = 'new') {
        global $wpdb;

        if ($version === 'old') {
            $old_options = get_option('image-map-pro-wordpress-admin-options', false);
            if ($old_options && isset($old_options['saves'][$project_id]['json'])) {
                return stripslashes($old_options['saves'][$project_id]['json']);
            }
            return null;
        }

        $table_name = $wpdb->prefix . 'image_map_pro_projects';
        $json = $wpdb->get_var($wpdb->prepare("SELECT json FROM $table_name WHERE id = %s", $project_id));
        return $json ? stripslashes($json) : null;
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
    public function set_status_color($status, $config) {
        if (is_string($config)) {
            // Legacy: plain hex string
            $hex = ltrim($config, '#');
            $config = array('color' => $hex, 'opacity' => 0.65, 'hover_color' => $hex, 'hover_opacity' => 0.40);
        }
        $this->status_colors[$status] = $config;

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

