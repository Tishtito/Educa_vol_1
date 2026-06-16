<?php
// Check for required extensions
$extensions = ['gd', 'pdo', 'mbstring', 'json'];
$missing = [];

foreach ($extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing[] = $ext;
    }
}

echo "<h2>PHP Extensions Check</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n\n";

foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? "✓ ENABLED" : "✗ MISSING";
    echo "$ext: $status\n";
}

echo "</pre>";

if (empty($missing)) {
    echo "<p style='color: green;'><strong>All required extensions are installed!</strong></p>";
} else {
    echo "<p style='color: red;'><strong>Missing extensions: " . implode(", ", $missing) . "</strong></p>";
}
?>
