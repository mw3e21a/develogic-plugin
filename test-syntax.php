<?php
/**
 * Test syntax of all PHP files
 */

$files = array(
    'develogic-integration.php',
    'includes/class-api-client.php',
    'includes/class-cache-manager.php',
    'includes/class-data-formatter.php',
    'includes/class-filter-sort.php',
    'admin/class-admin-settings.php',
    'public/class-shortcodes.php',
    'public/class-rest-api.php',
    'public/class-assets.php',
);

$errors = array();

foreach ($files as $file) {
    $filepath = __DIR__ . '/' . $file;
    
    if (!file_exists($filepath)) {
        $errors[] = "$file - FILE NOT FOUND";
        continue;
    }
    
    $output = array();
    $return = 0;
    exec("php -l " . escapeshellarg($filepath) . " 2>&1", $output, $return);
    
    if ($return !== 0) {
        $errors[] = "$file - SYNTAX ERROR:\n" . implode("\n", $output);
    } else {
        echo "✓ $file - OK\n";
    }
}

if (!empty($errors)) {
    echo "\n\n=== ERRORS FOUND ===\n\n";
    foreach ($errors as $error) {
        echo $error . "\n\n";
    }
    exit(1);
} else {
    echo "\n\nAll files OK!\n";
    exit(0);
}

