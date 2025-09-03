<?php
// Test API authentication
require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Services\AuthService;

// Create a test session to simulate logged-in user
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'email' => 'admin@btt.local'];
$_SESSION['logged_in'] = true;

echo "Session created. Now testing API...\n\n";

// Test API GET request
$ch = curl_init('http://localhost/BTT/api/index.php?route=trips');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'BTTSESSID=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "API Response (HTTP $httpCode):\n";
echo $response . "\n";
?>
