<?php
/**
 * Trips API Endpoint Tester
 * Tests all trips endpoints to diagnose why photo operations aren't working
 */
session_start();

// Just load the config and basic classes we need
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/classes/Database.php';
require_once __DIR__ . '/app/services/AuthService.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    // Try to redirect to login, but check if path exists
    $login_path = '/BTT/public/auth/login.php';
    if (file_exists(__DIR__ . '/public/auth/login.php')) {
        header('Location: ' . $login_path . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    } else {
        // Fallback to main login page
        header('Location: /BTT/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
    exit;
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trips API Endpoint Tester</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-result {
            margin: 10px 0;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        button {
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
        }
        button:hover { background: #0056b3; }
        .endpoint-info {
            background: #f0f0f0;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        input[type="file"] {
            margin: 10px 0;
        }
        .warning { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <h1>🔍 Trips API Endpoint Tester</h1>
    
    <div class="test-section">
        <h2>Environment Information</h2>
        <div class="endpoint-info">
            <p><strong>Base URL:</strong> <?= BASE_URL ?></p>
            <p><strong>API URL (BTT_API_URL):</strong> <?= BTT_API_URL ?></p>
            <p><strong>Ajax Handler URL:</strong> <?= BASE_URL ?>/ajax-handler.php</p>
            <p><strong>User ID:</strong> <?= $user_id ?></p>
            <p><strong>Session ID:</strong> <?= session_id() ?></p>
        </div>
    </div>

    <div class="test-section">
        <h2>1. Test Main API Endpoints (Photo Support)</h2>
        <button onclick="testMainApiGet()">Test GET /api?route=trips</button>
        <button onclick="testMainApiGetSingle()">Test GET /api?route=trips&id=6</button>
        <button onclick="testMainApiPost()">Test POST /api?route=trips</button>
        <button onclick="testMainApiPutNoPhoto()">Test PUT /api?route=trips&id=6 (No Photo)</button>
        <button onclick="testMainApiPutWithPhoto()">Test PUT /api?route=trips&id=6 (With Photo)</button>
        <button onclick="testMainApiDelete()">Test DELETE /api?route=trips&id=6</button>
        <div>
            <label>Test Photo: <input type="file" id="test-photo" accept="image/*"></label>
        </div>
        <div id="main-api-results"></div>
    </div>

    <div class="test-section">
        <h2>2. Test Ajax Handler Endpoints (No Photo Support)</h2>
        <button onclick="testAjaxGet()">Test GET ajax-handler.php?route=trips</button>
        <button onclick="testAjaxGetSingle()">Test GET ajax-handler.php?route=trips&id=6</button>
        <button onclick="testAjaxPost()">Test POST ajax-handler.php?route=trips</button>
        <button onclick="testAjaxPut()">Test PUT ajax-handler.php?route=trips&id=6</button>
        <button onclick="testAjaxDelete()">Test DELETE ajax-handler.php?route=trips&id=6</button>
        <div id="ajax-results"></div>
    </div>

    <div class="test-section">
        <h2>3. Direct API File Test</h2>
        <button onclick="testDirectApi()">Test Direct API Access</button>
        <div id="direct-api-results"></div>
    </div>

    <div class="test-section">
        <h2>4. BTTApi JavaScript Test</h2>
        <button onclick="testBTTApi()">Test BTTApi Methods</button>
        <div id="bttapi-results"></div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
    // Helper to display results
    function showResult(containerId, message, type = 'info') {
        const container = document.getElementById(containerId);
        const result = document.createElement('div');
        result.className = `test-result ${type}`;
        result.textContent = message;
        container.appendChild(result);
    }

    // 1. Main API Tests
    async function testMainApiGet() {
        try {
            const response = await fetch('/BTT/api?route=trips', {
                credentials: 'include'
            });
            const data = await response.json();
            showResult('main-api-results', `GET /api?route=trips
Status: ${response.status}
Response: ${JSON.stringify(data, null, 2)}`, response.ok ? 'success' : 'error');
        } catch (error) {
            showResult('main-api-results', `GET /api?route=trips - Error: ${error.message}`, 'error');
        }
    }

    async function testMainApiGetSingle() {
        try {
            const response = await fetch('/BTT/api?route=trips&id=6', {
                credentials: 'include'
            });
            const data = await response.json();
            showResult('main-api-results', `GET /api?route=trips&id=6
Status: ${response.status}
Response: ${JSON.stringify(data, null, 2)}`, response.ok ? 'success' : 'error');
        } catch (error) {
            showResult('main-api-results', `GET /api?route=trips&id=6 - Error: ${error.message}`, 'error');
        }
    }

    async function testMainApiPost() {
        try {
            const formData = new FormData();
            formData.append('title', 'Test Trip from API Tester');
            formData.append('location', 'Test Location');
            formData.append('start_date', '2025-01-01');
            formData.append('description', 'Created by API endpoint tester');

            const response = await fetch('/BTT/api?route=trips', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            const data = await response.json();
            showResult('main-api-results', `POST /api?route=trips
Status: ${response.status}
Response: ${JSON.stringify(data, null, 2)}`, response.ok ? 'success' : 'error');
        } catch (error) {
            showResult('main-api-results', `POST /api?route=trips - Error: ${error.message}`, 'error');
        }
    }

    async function testMainApiPutNoPhoto() {
        try {
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('title', 'Updated Trip Title (No Photo)');
            formData.append('location', 'Updated Location');

            const response = await fetch('/BTT/api?route=trips&id=6', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            const data = await response.json();
            showResult('main-api-results', `PUT /api?route=trips&id=6 (No Photo)
Status: ${response.status}
Response: ${JSON.stringify(data, null, 2)}`, response.ok ? 'success' : 'error');
        } catch (error) {
            showResult('main-api-results', `PUT /api?route=trips&id=6 - Error: ${error.message}`, 'error');
        }
    }

    async function testMainApiPutWithPhoto() {
        const fileInput = document.getElementById('test-photo');
        if (!fileInput.files.length) {
            showResult('main-api-results', 'Please select a photo file first', 'warning');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('title', 'Updated Trip with Photo');
            formData.append('location', 'Photo Test Location');
            formData.append('photo', fileInput.files[0]);
            formData.append('photo_alt_text', 'Test photo from API tester');
            formData.append('remove_photo', '0');

            showResult('main-api-results', `Uploading photo: ${fileInput.files[0].name} (${fileInput.files[0].size} bytes)`, 'info');

            const response = await fetch('/BTT/api?route=trips&id=6', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            const data = await response.json();
            showResult('main-api-results', `PUT /api?route=trips&id=6 (With Photo)
Status: ${response.status}
Response: ${JSON.stringify(data, null, 2)}`, response.ok ? 'success' : 'error');
        } catch (error) {
            showResult('main-api-results', `PUT /api?route=trips&id=6 (Photo) - Error: ${error.message}`, 'error');
        }
    }

    async function testMainApiDelete() {
        try {
            const formData = new FormData();
            formData.append('_method', 'DELETE');

            const response = await fetch('/BTT/api?route=trips&id=999', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            const data = await response.json();
            showResult('main-api-results', `DELETE /api?route=trips&id=999
Status: ${response.status}
Response: ${JSON.stringify(data, null, 2)}`, response.ok ? 'success' : 'error');
        } catch (error) {
            showResult('main-api-results', `DELETE /api?route=trips&id=999 - Error: ${error.message}`, 'error');
        }
    }

    // 2. Ajax Handler Tests
    async function testAjaxGet() {
        try {
            const response = await fetch('/BTT/ajax-handler.php?route=trips', {
                credentials: 'include'
            });
            const data = await response.json();
            showResult('ajax-results', `GET ajax-handler.php?route=trips
Status: ${response.status}
Response: ${JSON.stringify(data, null, 2)}`, response.ok ? 'success' : 'error');
        } catch (error) {
            showResult('ajax-results', `GET ajax-handler.php - Error: ${error.message}`, 'error');
        }
    }

    async function testAjaxGetSingle() {
        try {
            const response = await fetch('/BTT/ajax-handler.php?route=trips&id=6', {
                credentials: 'include'
            });
            const data = await response.json();
            showResult('ajax-results', `GET ajax-handler.php?route=trips&id=6
Status: ${response.status}
Response: ${JSON.stringify(data, null, 2)}`, response.ok ? 'success' : 'error');
        } catch (error) {
            showResult('ajax-results', `GET ajax-handler.php&id=6 - Error: ${error.message}`, 'error');
        }
    }

    async function testAjaxPost() {
        try {
            const formData = new FormData();
            formData.append('title', 'Test Trip from Ajax Handler');
            formData.append('location', 'Ajax Test Location');
            formData.append('start_date', '2025-01-02');

            const response = await fetch('/BTT/ajax-handler.php?route=trips', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            const data = await response.json();
            showResult('ajax-results', `POST ajax-handler.php?route=trips
Status: ${response.status}
Response: ${JSON.stringify(data, null, 2)}`, response.ok ? 'success' : 'error');
        } catch (error) {
            showResult('ajax-results', `POST ajax-handler.php - Error: ${error.message}`, 'error');
        }
    }

    async function testAjaxPut() {
        try {
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('title', 'Updated via Ajax Handler');
            formData.append('location', 'Ajax Updated Location');

            const response = await fetch('/BTT/ajax-handler.php?route=trips&id=6', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            const data = await response.json();
            showResult('ajax-results', `PUT ajax-handler.php?route=trips&id=6
Status: ${response.status}
Response: ${JSON.stringify(data, null, 2)}`, response.ok ? 'success' : 'error');
        } catch (error) {
            showResult('ajax-results', `PUT ajax-handler.php - Error: ${error.message}`, 'error');
        }
    }

    async function testAjaxDelete() {
        try {
            const formData = new FormData();
            formData.append('_method', 'DELETE');

            const response = await fetch('/BTT/ajax-handler.php?route=trips&id=998', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            const data = await response.json();
            showResult('ajax-results', `DELETE ajax-handler.php?route=trips&id=998
Status: ${response.status}
Response: ${JSON.stringify(data, null, 2)}`, response.ok ? 'success' : 'error');
        } catch (error) {
            showResult('ajax-results', `DELETE ajax-handler.php - Error: ${error.message}`, 'error');
        }
    }

    // 3. Direct API Test
    async function testDirectApi() {
        try {
            // Test if API directory is accessible
            const response = await fetch('/BTT/api/index.php?route=health', {
                credentials: 'include'
            });
            const data = await response.json();
            showResult('direct-api-results', `Direct API Health Check
Status: ${response.status}
Response: ${JSON.stringify(data, null, 2)}`, response.ok ? 'success' : 'error');

            // Test trips route directly
            const tripsResponse = await fetch('/BTT/api/index.php?route=trips', {
                credentials: 'include'
            });
            const tripsData = await tripsResponse.json();
            showResult('direct-api-results', `Direct API Trips Route
Status: ${tripsResponse.status}
Response: ${JSON.stringify(tripsData, null, 2)}`, tripsResponse.ok ? 'success' : 'error');
        } catch (error) {
            showResult('direct-api-results', `Direct API Test - Error: ${error.message}`, 'error');
        }
    }

    // 4. BTTApi JavaScript Test
    async function testBTTApi() {
        showResult('bttapi-results', `BTT Configuration:
apiUrl: ${window.BTT?.apiUrl || 'NOT SET'}
baseUrl: ${window.BTT?.baseUrl || 'NOT SET'}`, 'info');

        if (!window.BTTApi) {
            showResult('bttapi-results', 'BTTApi is not defined!', 'error');
            return;
        }

        try {
            // Test GET
            const trips = await BTTApi.get('trips');
            showResult('bttapi-results', `BTTApi.get('trips'):
${JSON.stringify(trips, null, 2)}`, 'success');

            // Test POST
            const newTrip = await BTTApi.post('trips', {
                title: 'BTTApi Test Trip',
                location: 'BTTApi Test',
                start_date: '2025-01-03'
            });
            showResult('bttapi-results', `BTTApi.post('trips'):
${JSON.stringify(newTrip, null, 2)}`, 'success');
        } catch (error) {
            showResult('bttapi-results', `BTTApi Test - Error: ${error.message}`, 'error');
        }
    }
    </script>
</body>
</html>