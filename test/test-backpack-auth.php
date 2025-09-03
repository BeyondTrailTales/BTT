<?php
/**
 * Test backpack API with proper authentication
 * Simulates what happens when a logged-in user accesses the backpack page
 */

// Start session
session_start();

// Set up as admin user (simulating a logged-in session)
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['email'] = 'admin@example.com';
$_SESSION['logged_in'] = true;
$_SESSION['login_time'] = time();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo "=== Testing Backpack Access as Logged-In User ===\n\n";
echo "Simulated login as: " . $_SESSION['username'] . " (ID: " . $_SESSION['user_id'] . ")\n\n";

// Now make the API call
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['route'] = 'backpacks';

// Capture output
ob_start();
require __DIR__ . '/../api/index.php';
$output = ob_get_clean();

// Parse the response
$response = json_decode($output, true);

if ($response) {
    echo "API Response Status: " . ($response['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    echo "Message: " . $response['message'] . "\n";
    echo "Number of backpacks returned: " . count($response['data']) . "\n\n";
    
    if (!empty($response['data'])) {
        echo "Backpacks:\n";
        foreach ($response['data'] as $pack) {
            echo "  - ID: {$pack['id']}, Name: {$pack['name']}, Items: {$pack['total_items']}\n";
        }
    } else {
        echo "No backpacks found for this user.\n";
    }
} else {
    echo "Failed to parse API response.\n";
    echo "Raw output:\n" . $output . "\n";
}

// Clean up session
session_destroy();
