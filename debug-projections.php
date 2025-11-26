<?php
/**
 * Debug script to check projections for a specific local
 * 
 * Usage: http://your-site.com/wp-content/plugins/develogic-integration/debug-projections.php?number=M6
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. You must be an administrator.');
}

// Get local number from URL parameter
$local_number = isset($_GET['number']) ? sanitize_text_field($_GET['number']) : '';

if (empty($local_number)) {
    die('Please provide a local number: ?number=M6');
}

// Find the local post
$posts = get_posts(array(
    'post_type' => 'local',
    'meta_query' => array(
        array(
            'key' => 'number',
            'value' => $local_number,
            'compare' => '='
        )
    ),
    'posts_per_page' => 1
));

if (empty($posts)) {
    die(sprintf('Local %s not found in database', $local_number));
}

$post = $posts[0];
$post_id = $post->ID;

echo "<h1>Debug Projections for Local: {$local_number}</h1>";
echo "<p>Post ID: {$post_id}</p>";
echo "<p>Post Status: {$post->post_status}</p>";

// Get projections metadata
$projections_json = get_post_meta($post_id, 'projections', true);

echo "<h2>Projections JSON:</h2>";
echo "<pre>";
print_r($projections_json);
echo "</pre>";

if (empty($projections_json)) {
    die('No projections found in metadata');
}

$projections = json_decode($projections_json, true);

if (empty($projections)) {
    die('Could not decode projections JSON');
}

echo "<h2>Decoded Projections:</h2>";
echo "<pre>";
print_r($projections);
echo "</pre>";

// Check attachments
echo "<h2>Attachments (media files):</h2>";

$attachments = get_posts(array(
    'post_type' => 'attachment',
    'post_parent' => $post_id,
    'posts_per_page' => -1,
    'orderby' => 'ID',
    'order' => 'ASC'
));

echo "<p>Found " . count($attachments) . " attachments</p>";

foreach ($attachments as $attachment) {
    echo "<h3>Attachment ID: {$attachment->ID}</h3>";
    echo "<p>Title: {$attachment->post_title}</p>";
    echo "<p>File: " . wp_get_attachment_url($attachment->ID) . "</p>";
    echo "<p>Type: {$attachment->post_mime_type}</p>";
    
    // Get metadata
    $projection_id = get_post_meta($attachment->ID, 'develogic_projection_id', true);
    $local_post_id = get_post_meta($attachment->ID, 'develogic_local_post_id', true);
    $projection_type = get_post_meta($attachment->ID, 'develogic_projection_type', true);
    $is_pdf = get_post_meta($attachment->ID, 'develogic_is_pdf_original', true);
    
    echo "<ul>";
    echo "<li>Projection ID: {$projection_id}</li>";
    echo "<li>Local Post ID: {$local_post_id}</li>";
    echo "<li>Projection Type: {$projection_type}</li>";
    echo "<li>Is PDF Original: " . ($is_pdf ? 'Yes' : 'No') . "</li>";
    echo "</ul>";
}

// Now check if there are any attachments with the SAME projection_id but DIFFERENT parent
echo "<h2>Check for Duplicate Projections:</h2>";

foreach ($projections as $projection) {
    if (empty($projection['id'])) {
        continue;
    }
    
    $projection_id = $projection['id'];
    $projection_type = isset($projection['type']) ? $projection['type'] : 'Unknown';
    
    echo "<h3>Projection #{$projection_id} - {$projection_type}</h3>";
    
    // Find ALL attachments with this projection_id
    $all_attachments = get_posts(array(
        'post_type' => 'attachment',
        'meta_query' => array(
            array(
                'key' => 'develogic_projection_id',
                'value' => $projection_id,
                'compare' => '='
            )
        ),
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    echo "<p>Found " . count($all_attachments) . " attachment(s) with this projection_id</p>";
    
    foreach ($all_attachments as $att_id) {
        $parent_id = wp_get_post_parent_id($att_id);
        $parent_number = '';
        
        if ($parent_id) {
            $parent_number = get_post_meta($parent_id, 'number', true);
        }
        
        $url = wp_get_attachment_url($att_id);
        $is_pdf = get_post_meta($att_id, 'develogic_is_pdf_original', true);
        $type = $is_pdf ? 'PDF' : 'Image';
        
        echo "<ul>";
        echo "<li>Attachment ID: {$att_id} ({$type})</li>";
        echo "<li>Parent Post ID: {$parent_id} (Local: {$parent_number})</li>";
        echo "<li>URL: {$url}</li>";
        echo "</ul>";
        
        if ($parent_id != $post_id) {
            echo "<p style='color: red;'><strong>⚠️ WARNING: This attachment belongs to a DIFFERENT local ({$parent_number})!</strong></p>";
        }
    }
}

echo "<hr>";
echo "<p><a href='?number={$local_number}'>Refresh</a></p>";

