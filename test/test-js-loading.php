<?php
/**
 * Test JavaScript loading order for trips and backpacks pages
 */

echo "=== Testing JavaScript Loading Order ===\n\n";

// Check if jQuery is available in vendor
$jqueryPath = __DIR__ . '/../vendor/jquery-3.7.1.min.js';
if (file_exists($jqueryPath)) {
    echo "✓ jQuery found at: /vendor/jquery-3.7.1.min.js\n";
    echo "  File size: " . filesize($jqueryPath) . " bytes\n";
} else {
    echo "✗ jQuery NOT FOUND at expected location\n";
}

// Check pack-builder.js
$packBuilderPath = __DIR__ . '/../assets/js/pack-builder.js';
if (file_exists($packBuilderPath)) {
    echo "✓ pack-builder.js found\n";
    // Check if it's wrapped properly
    $content = file_get_contents($packBuilderPath);
    if (strpos($content, '(function($)') !== false) {
        echo "  ✓ Properly wrapped with jQuery closure\n";
    } else {
        echo "  ✗ Not wrapped with jQuery closure\n";
    }
} else {
    echo "✗ pack-builder.js NOT FOUND\n";
}

// Check trips.js
$tripsPath = __DIR__ . '/../assets/js/trips.js';
if (file_exists($tripsPath)) {
    echo "✓ trips.js found\n";
    $content = file_get_contents($tripsPath);
    if (strpos($content, 'DOMContentLoaded') !== false) {
        echo "  ✓ Uses DOMContentLoaded\n";
    }
    if (strpos($content, 'jQuery') !== false || strpos($content, '$(') !== false) {
        echo "  ⚠ Uses jQuery directly\n";
    } else {
        echo "  ✓ Does not require jQuery\n";
    }
} else {
    echo "✗ trips.js NOT FOUND\n";
}

echo "\n=== Script Loading Order in Template Footer ===\n";

$footerPath = __DIR__ . '/../public/includes/template-footer.php';
if (file_exists($footerPath)) {
    $content = file_get_contents($footerPath);
    
    // Find script tags
    preg_match_all('/<script[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
    
    if (!empty($matches[1])) {
        echo "Scripts load in this order:\n";
        foreach ($matches[1] as $index => $src) {
            // Extract just the filename
            if (strpos($src, 'jquery') !== false) {
                echo ($index + 1) . ". jQuery\n";
            } elseif (strpos($src, 'sortable') !== false) {
                echo ($index + 1) . ". Sortable.js\n";
            } else {
                // Extract filename from PHP echo statements
                preg_match('/\'([^\']+)\'/', $src, $fileMatch);
                if (!empty($fileMatch[1])) {
                    echo ($index + 1) . ". " . basename($fileMatch[1]) . "\n";
                } else {
                    echo ($index + 1) . ". " . $src . "\n";
                }
            }
        }
    }
} else {
    echo "✗ Template footer not found\n";
}

echo "\n=== Recommendations ===\n";
echo "1. jQuery should load FIRST in the footer\n";
echo "2. All page-specific scripts should be added to \$pageScripts array\n";
echo "3. No inline scripts should use jQuery before the footer\n";
echo "4. Use DOMContentLoaded or jQuery's \$(document).ready() in external scripts\n";

echo "\n=== Test Complete ===\n";
