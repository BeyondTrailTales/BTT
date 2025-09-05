<?php
// This script will analyze and provide a solution for the photo persistence issue

session_start();
require_once __DIR__ . '/app/bootstrap.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    die("Please log in first.");
}

// Connect to database
$db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Photo Persistence Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .code { background: #f0f0f0; padding: 10px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; }
        .fix { background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .problem { background: #ffebee; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Photo Persistence Issue Analysis & Fix</h1>";

// Check current state
$stmt = $db->prepare("SELECT id, title, photo_path FROM trips WHERE user_id = ? AND photo_path IS NOT NULL");
$stmt->execute([$user_id]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Current trips with photos:</h2>";
echo "<ul>";
foreach ($trips as $trip) {
    $fileExists = file_exists(__DIR__ . '/' . $trip['photo_path']);
    echo "<li>Trip #{$trip['id']} - {$trip['title']}: {$trip['photo_path']} " . 
         ($fileExists ? "✓ File exists" : "✗ File missing") . "</li>";
}
echo "</ul>";

echo "<div class='problem'>
<h2>PROBLEM IDENTIFIED:</h2>
<p>The issue is likely one of the following:</p>
<ol>
    <li>The trips.js state is not being refreshed after photo upload</li>
    <li>The loadTrips() function is caching old data</li>
    <li>The photo path is being saved but not properly returned in the API response</li>
</ol>
</div>";

echo "<div class='fix'>
<h2>SOLUTION:</h2>
<p>The fix requires updating the trips.js to properly reload trip data after photo upload. Here's what needs to be done:</p>

<h3>1. In trips.js, after successful photo upload:</h3>
<div class='code'>// After successful save with photo
if (hasPhoto) {
    // Force reload the trip from server to get updated photo_path
    const updatedTrip = await BTTApi.get('trips', id);
    
    // Update the trip in state
    const tripIndex = state.trips.findIndex(t => t.id === parseInt(id));
    if (tripIndex !== -1) {
        state.trips[tripIndex] = updatedTrip;
    }
    
    // Update the UI
    updateGrid();
}</div>

<h3>2. Alternative: Force full reload after photo upload:</h3>
<div class='code'>// After successful save with photo
if (hasPhoto) {
    // Reload all trips to ensure we have latest data
    await loadTrips();
    filterTrips();
    updateGrid();
}</div>

<h3>3. Make sure the AJAX handler returns the updated trip data after POST/PUT:</h3>
<p>The ajax-handler.php already does this correctly - it returns the full trip object after create/update.</p>
</div>";

// Test the actual API response
echo "<h2>Test API Response for Trip Update:</h2>";
if (!empty($trips)) {
    $testTrip = $trips[0];
    echo "<p>Testing with trip ID: {$testTrip['id']}</p>";
    
    // Simulate an update
    $testData = [
        'title' => $testTrip['title'] . ' (test)',
        'photo_path' => $testTrip['photo_path']
    ];
    
    echo "<div class='code'>Test data: " . json_encode($testData, JSON_PRETTY_PRINT) . "</div>";
}

echo "</body></html>";
?>