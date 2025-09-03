<?php
/**
 * Test trip photo upload functionality
 */

echo "=== Testing Trip Photo Upload ===\n\n";

// Create a test image file
$test_image_path = __DIR__ . '/test_image.jpg';
if (!file_exists($test_image_path)) {
    echo "Creating test image...\n";
    // Create a simple 1x1 pixel JPEG
    $image = imagecreate(400, 300);
    $bg = imagecolorallocate($image, 74, 222, 128); // Green color
    $text_color = imagecolorallocate($image, 255, 255, 255);
    imagestring($image, 5, 150, 140, 'Test Trip Photo', $text_color);
    imagejpeg($image, $test_image_path, 90);
    imagedestroy($image);
    echo "Test image created: $test_image_path\n\n";
} else {
    echo "Using existing test image: $test_image_path\n\n";
}

// Test the API directly with photo upload
$api_url = 'http://localhost/BTT/api/index.php?route=trips';

// Prepare the upload
$post_data = [
    'title' => 'Test Trip with Photo ' . date('Y-m-d H:i:s'),
    'location' => 'Test Mountain',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d', strtotime('+2 days')),
    'description' => 'Testing photo upload functionality',
    'photo_alt_text' => 'A beautiful test mountain landscape',
    'distance' => '15.5',
    'distance_unit' => 'miles',
    'elevation_gain' => '2500',
    'difficulty' => 'moderate',
    'trip_type' => 'weekend'
];

// Use cURL for file upload
$ch = curl_init();

// Create CURLFile object for the photo
$cfile = new CURLFile($test_image_path, 'image/jpeg', 'test_photo.jpg');
$post_data['photo'] = $cfile;

curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

echo "Uploading trip with photo...\n";
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $http_code\n";

if ($response) {
    $result = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        if (isset($result['success']) && $result['success']) {
            echo "✅ SUCCESS: Trip created with photo\n";
            
            if (isset($result['data'])) {
                $trip = $result['data'];
                echo "\nTrip Details:\n";
                echo "  - ID: {$trip['id']}\n";
                echo "  - Title: {$trip['title']}\n";
                echo "  - Photo Path: " . ($trip['photo_path'] ?? 'Not set') . "\n";
                echo "  - Photo Alt: " . ($trip['photo_alt_text'] ?? 'Not set') . "\n";
                
                if (isset($trip['photo_path']) && $trip['photo_path']) {
                    $photo_url = "http://localhost/BTT/" . $trip['photo_path'];
                    echo "  - Photo URL: $photo_url\n";
                    
                    // Check if the file actually exists
                    $file_path = dirname(__DIR__) . '/' . $trip['photo_path'];
                    if (file_exists($file_path)) {
                        echo "  - ✅ Photo file exists on disk\n";
                        echo "  - File size: " . filesize($file_path) . " bytes\n";
                    } else {
                        echo "  - ❌ Photo file NOT found at: $file_path\n";
                    }
                }
                
                // Clean up - delete the test trip
                echo "\nCleaning up test trip...\n";
                $delete_url = "http://localhost/BTT/api/index.php?route=trips&id={$trip['id']}";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $delete_url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, ['_method' => 'DELETE']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $delete_response = curl_exec($ch);
                curl_close($ch);
                
                $delete_result = json_decode($delete_response, true);
                if ($delete_result && $delete_result['success']) {
                    echo "✅ Test trip deleted successfully\n";
                } else {
                    echo "⚠️ Could not delete test trip\n";
                }
            }
        } else {
            echo "❌ FAILED: " . ($result['error'] ?? 'Unknown error') . "\n";
            echo "Full response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "❌ Invalid JSON response: $response\n";
    }
} else {
    echo "❌ No response from server\n";
}

echo "\n=== Test Complete ===\n";
