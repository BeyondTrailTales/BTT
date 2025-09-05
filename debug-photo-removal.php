<?php
session_start();

// Load config and auth manually to avoid bootstrap redirects
require_once __DIR__ . '/app/config.php';

header('Content-Type: text/plain');

echo "=== SESSION DEBUG ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session data: " . print_r($_SESSION, true) . "\n";

if (!isset($_SESSION['user_id'])) {
    echo "❌ Not logged in - please log in to BTT first, then try again\n";
    echo "Visit: http://localhost/BTT/public/auth/login.php\n";
    exit;
} else {
    echo "✅ Logged in as user ID: " . $_SESSION['user_id'] . "\n\n";
}

echo "=== PHOTO REMOVAL DATABASE TEST ===\n\n";

try {
    $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get current state of trip 6
    echo "BEFORE - Trip 6 current state:\n";
    $stmt = $db->query("SELECT id, title, photo_path, photo_alt_text, updated_at FROM trips WHERE id = 6");
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($trip) {
        foreach ($trip as $key => $value) {
            echo "  $key: " . ($value ?? 'NULL') . "\n";
        }
    } else {
        echo "  Trip 6 not found!\n";
    }
    
    echo "\n=== MANUAL PHOTO REMOVAL TEST ===\n";
    
    // Manually remove photo
    $stmt = $db->prepare("UPDATE trips SET photo_path = NULL, photo_alt_text = NULL, updated_at = datetime('now') WHERE id = 6");
    $result = $stmt->execute();
    
    echo "Manual UPDATE result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    echo "Rows affected: " . $stmt->rowCount() . "\n";
    
    // Check result
    echo "\nAFTER - Trip 6 state after manual removal:\n";
    $stmt = $db->query("SELECT id, title, photo_path, photo_alt_text, updated_at FROM trips WHERE id = 6");
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($trip) {
        foreach ($trip as $key => $value) {
            echo "  $key: " . ($value ?? 'NULL') . "\n";
        }
    }
    
    echo "\n=== API ENDPOINT TEST ===\n";
    
    // Test what the API actually receives
    echo "Testing API endpoint directly...\n";
    
    // Simulate the API call
    $_POST = [
        '_method' => 'PUT',
        'title' => 'Test Title',
        'remove_photo' => '1'
    ];
    
    echo "Simulated _POST data:\n";
    foreach ($_POST as $key => $value) {
        echo "  $key: $value\n";
    }
    
    echo "\n=== CHECK API ROUTES HANDLING ===\n";
    
    // Check if the main API trips route handles remove_photo
    $apiFile = __DIR__ . '/api/routes/trips.php';
    if (file_exists($apiFile)) {
        $apiContent = file_get_contents($apiFile);
        if (strpos($apiContent, 'remove_photo') !== false) {
            echo "✅ Main API routes/trips.php contains 'remove_photo' handling\n";
            
            // Find the exact lines
            $lines = explode("\n", $apiContent);
            foreach ($lines as $lineNum => $line) {
                if (stripos($line, 'remove_photo') !== false) {
                    echo "  Line " . ($lineNum + 1) . ": " . trim($line) . "\n";
                }
            }
        } else {
            echo "❌ Main API routes/trips.php does NOT contain 'remove_photo' handling\n";
        }
    }
    
    // Check ajax-handler
    $ajaxFile = __DIR__ . '/ajax-handler.php';
    if (file_exists($ajaxFile)) {
        $ajaxContent = file_get_contents($ajaxFile);
        if (strpos($ajaxContent, 'remove_photo') !== false) {
            echo "✅ ajax-handler.php contains 'remove_photo' handling\n";
        } else {
            echo "❌ ajax-handler.php does NOT contain 'remove_photo' handling\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>