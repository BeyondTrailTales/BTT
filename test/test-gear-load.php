<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['logged_in'] = true;

// Test gear loading
$ch = curl_init('http://localhost/BTT/ajax-handler.php?route=gear');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n\n";

$gear = json_decode($response, true);

if ($gear) {
    echo "Loaded " . count($gear) . " gear items:\n\n";
    foreach (array_slice($gear, 0, 10) as $item) {
        echo "- {$item['name']} ({$item['category']}) - {$item['weight']}g {$item['icon']}\n";
    }
} else {
    echo "Error: " . $response . "\n";
}
