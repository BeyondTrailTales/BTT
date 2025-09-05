<?php
require_once 'app/bootstrap.php';

// Start session like the main app
session_start();

echo "<h1>Gear Library Debug</h1>\n";

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    echo "<p>✅ User is logged in: ID = " . $_SESSION['user_id'] . "</p>\n";
    
    // Test direct database query
    try {
        $stmt = $db->prepare("SELECT * FROM user_gear WHERE user_id = ? AND (deleted_at IS NULL OR deleted_at = '') ORDER BY category, name");
        $stmt->execute([$_SESSION['user_id']]);
        $gear = $stmt->fetchAll();
        
        echo "<p>📦 Found " . count($gear) . " gear items in database</p>\n";
        
        if (count($gear) > 0) {
            echo "<h2>Sample gear items:</h2>\n";
            echo "<pre>" . json_encode(array_slice($gear, 0, 3), JSON_PRETTY_PRINT) . "</pre>\n";
        }
        
        // Test AJAX endpoint directly
        echo "<h2>Testing AJAX Endpoint</h2>\n";
        echo "<div id='ajax-test'>Loading...</div>\n";
        
        echo "<script src='assets/js/jquery-3.7.1.min.js'></script>\n";
        echo "<script>\n";
        echo "$(document).ready(function() {\n";
        echo "    $.ajax({\n";
        echo "        url: '/BTT/ajax-handler.php?route=gear',\n";
        echo "        method: 'GET',\n";
        echo "        dataType: 'json',\n";
        echo "        success: function(response) {\n";
        echo "            console.log('AJAX Success:', response);\n";
        echo "            $('#ajax-test').html('<pre>' + JSON.stringify(response, null, 2) + '</pre>');\n";
        echo "        },\n";
        echo "        error: function(xhr, status, error) {\n";
        echo "            console.log('AJAX Error:', status, error, xhr.responseText);\n";
        echo "            $('#ajax-test').html('AJAX Error: ' + status + ' - ' + error + '<br>Response: ' + xhr.responseText);\n";
        echo "        }\n";
        echo "    });\n";
        echo "});\n";
        echo "</script>\n";
        
    } catch (Exception $e) {
        echo "<p>❌ Database error: " . $e->getMessage() . "</p>\n";
    }
} else {
    echo "<p>❌ User is not logged in</p>\n";
    echo "<p>Session contents:</p>\n";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>\n";
}

// Check auth configuration
echo "<h2>Authentication Status</h2>\n";
if (isset($_SESSION)) {
    echo "<p>Session started: YES</p>\n";
    echo "<p>Session ID: " . session_id() . "</p>\n";
} else {
    echo "<p>Session started: NO</p>\n";
}
?>