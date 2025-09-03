<?php
/**
 * Smoke test for Trips API
 * Tests creating, updating, and fetching trips with and without default image fields
 * 
 * Usage: php test/trips_api_smoke.php
 */

// Configuration
$base_url = 'http://localhost/BTT/api/index.php';
$test_passed = true;
$tests_run = 0;
$tests_passed = 0;

// Colors for terminal output
$green = "\033[0;32m";
$red = "\033[0;31m";
$yellow = "\033[0;33m";
$reset = "\033[0m";

echo "=== Trips API Smoke Test ===\n\n";

// Helper function to make API requests
function api_request($method, $endpoint, $data = null) {
    global $base_url;
    
    $url = $base_url . '?route=' . $endpoint;
    
    $options = [
        'http' => [
            'method' => $method,
            'header' => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ],
            'ignore_errors' => true
        ]
    ];
    
    if ($data !== null && ($method === 'POST' || $method === 'PUT')) {
        if ($method === 'PUT') {
            $data['_method'] = 'PUT';
            $options['http']['method'] = 'POST';
        }
        $options['http']['content'] = http_build_query($data);
    }
    
    if ($method === 'DELETE') {
        $options['http']['method'] = 'POST';
        $options['http']['content'] = http_build_query(['_method' => 'DELETE']);
    }
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return ['success' => false, 'error' => 'Request failed'];
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Invalid JSON response', 'raw' => $response];
    }
    
    return $data;
}

// Helper function to run a test
function run_test($name, $callback) {
    global $tests_run, $tests_passed, $green, $red, $reset;
    
    $tests_run++;
    echo "Test $tests_run: $name... ";
    
    try {
        $result = $callback();
        if ($result === true) {
            echo "{$green}PASSED{$reset}\n";
            $tests_passed++;
            return true;
        } else {
            echo "{$red}FAILED{$reset}: $result\n";
            return false;
        }
    } catch (Exception $e) {
        echo "{$red}ERROR{$reset}: " . $e->getMessage() . "\n";
        return false;
    }
}

// Test 1: Create a trip without default image fields
$trip1_id = null;
run_test('Create trip without default image fields', function() use (&$trip1_id) {
    $data = [
        'title' => 'Test Trip 1 - No Image',
        'location' => 'Test Location',
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+2 days')),
        'distance' => 10.5,
        'distance_unit' => 'miles',
        'elevation_gain' => 1500,
        'difficulty' => 'moderate',
        'trip_type' => 'weekend',
        'description' => 'Test trip without default image fields'
    ];
    
    $response = api_request('POST', 'trips', $data);
    
    if (!isset($response['success']) || !$response['success']) {
        return 'Failed to create trip: ' . ($response['error'] ?? 'Unknown error');
    }
    
    if (!isset($response['data']['id'])) {
        return 'No trip ID returned';
    }
    
    $trip1_id = $response['data']['id'];
    return true;
});

// Test 2: Create a trip with default image fields
$trip2_id = null;
run_test('Create trip with default image fields', function() use (&$trip2_id) {
    $data = [
        'title' => 'Test Trip 2 - With Image',
        'location' => 'Test Mountain Trail',
        'start_date' => date('Y-m-d', strtotime('+7 days')),
        'end_date' => date('Y-m-d', strtotime('+10 days')),
        'distance' => 25.3,
        'distance_unit' => 'miles',
        'elevation_gain' => 3200,
        'difficulty' => 'hard',
        'trip_type' => 'section_hike',
        'description' => 'Test trip with default image fields',
        'default_image_url' => 'https://picsum.photos/seed/btt-test/800/600.jpg',
        'default_image_alt' => 'Random mountain landscape for testing'
    ];
    
    $response = api_request('POST', 'trips', $data);
    
    if (!isset($response['success']) || !$response['success']) {
        return 'Failed to create trip: ' . ($response['error'] ?? 'Unknown error');
    }
    
    if (!isset($response['data']['id'])) {
        return 'No trip ID returned';
    }
    
    $trip2_id = $response['data']['id'];
    
    // Verify the fields were saved
    if (!isset($response['data']['default_image_url']) || 
        $response['data']['default_image_url'] !== $data['default_image_url']) {
        return 'default_image_url not saved correctly';
    }
    
    if (!isset($response['data']['default_image_alt']) || 
        $response['data']['default_image_alt'] !== $data['default_image_alt']) {
        return 'default_image_alt not saved correctly';
    }
    
    return true;
});

// Test 3: Update trip with new default image fields
if ($trip1_id) {
    run_test('Update trip to add default image fields', function() use ($trip1_id) {
        $data = [
            'default_image_url' => 'https://picsum.photos/seed/btt-updated/800/600.jpg',
            'default_image_alt' => 'Updated test image'
        ];
        
        $response = api_request('PUT', 'trips&id=' . $trip1_id, $data);
        
        if (!isset($response['success']) || !$response['success']) {
            return 'Failed to update trip: ' . ($response['error'] ?? 'Unknown error');
        }
        
        // Verify the fields were updated
        if (!isset($response['data']['default_image_url']) || 
            $response['data']['default_image_url'] !== $data['default_image_url']) {
            return 'default_image_url not updated correctly';
        }
        
        if (!isset($response['data']['default_image_alt']) || 
            $response['data']['default_image_alt'] !== $data['default_image_alt']) {
            return 'default_image_alt not updated correctly';
        }
        
        return true;
    });
}

// Test 4: Fetch trip and verify fields
if ($trip2_id) {
    run_test('Fetch trip and verify all fields', function() use ($trip2_id) {
        $response = api_request('GET', 'trips&id=' . $trip2_id, null);
        
        if (!isset($response['success']) || !$response['success']) {
            return 'Failed to fetch trip: ' . ($response['error'] ?? 'Unknown error');
        }
        
        if (!isset($response['data'])) {
            return 'No trip data returned';
        }
        
        $trip = $response['data'];
        
        // Check required fields
        $required_fields = ['id', 'title', 'location', 'start_date', 'end_date'];
        foreach ($required_fields as $field) {
            if (!isset($trip[$field])) {
                return "Missing required field: $field";
            }
        }
        
        // Check image fields
        if (!isset($trip['default_image_url']) || empty($trip['default_image_url'])) {
            return 'default_image_url not returned or empty';
        }
        
        if (!isset($trip['default_image_alt']) || empty($trip['default_image_alt'])) {
            return 'default_image_alt not returned or empty';
        }
        
        return true;
    });
}

// Test 5: List all trips
run_test('List all trips', function() {
    $response = api_request('GET', 'trips', null);
    
    if (!isset($response['success']) || !$response['success']) {
        return 'Failed to list trips: ' . ($response['error'] ?? 'Unknown error');
    }
    
    if (!isset($response['data']) || !is_array($response['data'])) {
        return 'Invalid trips list returned';
    }
    
    if (count($response['data']) < 2) {
        return 'Expected at least 2 trips, got ' . count($response['data']);
    }
    
    return true;
});

// Test 6: Update trip with other backpacking fields
if ($trip2_id) {
    run_test('Update trip with backpacking-specific fields', function() use ($trip2_id) {
        $data = [
            'permit_required' => 1,
            'permit_cost' => 35.00,
            'permit_info' => 'Wilderness permit required, apply 2 weeks in advance',
            'water_sources' => 'Stream at mile 3, lake at mile 7',
            'trail_conditions' => 'Rocky, some snow patches above 10,000 ft',
            'cell_coverage' => 'spotty',
            'crowd_level' => 'moderate',
            'trailhead_parking' => 'Large lot, fills by 8am on weekends',
            'parking_cost' => 10.00,
            'pre_trip_notes' => 'Check weather, pack extra layers',
            'post_trip_notes' => 'Beautiful views, trail was well maintained',
            'lessons_learned' => 'Start earlier to avoid crowds'
        ];
        
        $response = api_request('PUT', 'trips&id=' . $trip2_id, $data);
        
        if (!isset($response['success']) || !$response['success']) {
            return 'Failed to update trip: ' . ($response['error'] ?? 'Unknown error');
        }
        
        // Spot check a few fields
        if ($response['data']['permit_required'] != 1) {
            return 'permit_required not updated correctly';
        }
        
        if ($response['data']['water_sources'] !== $data['water_sources']) {
            return 'water_sources not updated correctly';
        }
        
        return true;
    });
}

// Cleanup: Delete test trips
$cleanup_success = true;
if ($trip1_id) {
    echo "\nCleaning up test trip 1... ";
    $response = api_request('DELETE', 'trips&id=' . $trip1_id, null);
    if ($response['success']) {
        echo "{$green}OK{$reset}\n";
    } else {
        echo "{$yellow}Failed (non-critical){$reset}\n";
        $cleanup_success = false;
    }
}

if ($trip2_id) {
    echo "Cleaning up test trip 2... ";
    $response = api_request('DELETE', 'trips&id=' . $trip2_id, null);
    if ($response['success']) {
        echo "{$green}OK{$reset}\n";
    } else {
        echo "{$yellow}Failed (non-critical){$reset}\n";
        $cleanup_success = false;
    }
}

// Summary
echo "\n" . str_repeat('=', 40) . "\n";
echo "Test Results: ";
if ($tests_passed === $tests_run) {
    echo "{$green}ALL PASSED{$reset}";
} else {
    echo "{$red}SOME FAILED{$reset}";
}
echo " ($tests_passed/$tests_run)\n";

if (!$cleanup_success) {
    echo "{$yellow}Note: Some test data may not have been cleaned up{$reset}\n";
}

echo str_repeat('=', 40) . "\n";

// Exit with appropriate code
exit($tests_passed === $tests_run ? 0 : 1);
