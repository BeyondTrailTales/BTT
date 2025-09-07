<?php
session_start();

// Set user session for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';

header('Content-Type: text/plain');
echo "=== PHOTO UPLOAD DEBUG ===\n";

try {
    // Check if uploads directory exists
    $uploadsDir = __DIR__ . '/assets/img/trips';
    echo "1. Uploads directory check:\n";
    echo "   Path: $uploadsDir\n";
    echo "   Exists: " . (is_dir($uploadsDir) ? "✅ YES" : "❌ NO") . "\n";
    echo "   Writable: " . (is_writable($uploadsDir) ? "✅ YES" : "❌ NO") . "\n";
    
    // Check PHP upload settings
    echo "\n2. PHP Upload Settings:\n";
    echo "   file_uploads: " . (ini_get('file_uploads') ? "✅ Enabled" : "❌ Disabled") . "\n";
    echo "   upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
    echo "   post_max_size: " . ini_get('post_max_size') . "\n";
    echo "   max_file_uploads: " . ini_get('max_file_uploads') . "\n";
    
    // Check database structure
    echo "\n3. Database table structure:\n";
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $db->query("PRAGMA table_info(trips)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $photoFields = [];
    foreach ($columns as $col) {
        if (stripos($col['name'], 'photo') !== false || stripos($col['name'], 'image') !== false) {
            $photoFields[] = $col['name'] . " (" . $col['type'] . ")";
        }
    }
    
    if (empty($photoFields)) {
        echo "   ❌ No photo fields found in trips table\n";
    } else {
        echo "   ✅ Photo fields found:\n";
        foreach ($photoFields as $field) {
            echo "     - $field\n";
        }
    }
    
    // Check if API endpoints exist
    echo "\n4. API Endpoints check:\n";
    echo "   ajax-handler.php exists: " . (file_exists(__DIR__ . '/ajax-handler.php') ? "✅ YES" : "❌ NO") . "\n";
    echo "   api/routes/trips.php exists: " . (file_exists(__DIR__ . '/api/routes/trips.php') ? "✅ YES" : "❌ NO") . "\n";
    
    // Check for sample trip with photo
    echo "\n5. Sample trips with photos:\n";
    $stmt = $db->query("SELECT id, title, photo_path, photo_alt_text FROM trips WHERE photo_path IS NOT NULL AND photo_path != '' LIMIT 3");
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($trips)) {
        echo "   ❌ No trips with photos found\n";
    } else {
        foreach ($trips as $trip) {
            echo "   Trip {$trip['id']}: {$trip['title']}\n";
            echo "     Photo: {$trip['photo_path']}\n";
            echo "     Alt: " . ($trip['photo_alt_text'] ?: 'None') . "\n";
            
            // Check if file exists
            $fullPath = __DIR__ . '/' . $trip['photo_path'];
            echo "     File exists: " . (file_exists($fullPath) ? "✅ YES" : "❌ NO") . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>