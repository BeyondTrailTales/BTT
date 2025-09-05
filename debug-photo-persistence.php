<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();

$user_id = $_SESSION['user_id'] ?? null;
$trip_id = $_GET['trip_id'] ?? 6; // Default to trip 6 which user is testing

?>
<!DOCTYPE html>
<html>
<head>
    <title>Photo Persistence Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .section { margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 8px; }
        .code { background: #fff; padding: 10px; border: 1px solid #ddd; font-family: monospace; overflow-x: auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        img { max-width: 300px; height: auto; border: 2px solid #333; }
        table { border-collapse: collapse; margin: 10px 0; }
        td, th { border: 1px solid #ddd; padding: 8px; }
        th { background: #e0e0e0; }
    </style>
</head>
<body>
    <h1>Photo Persistence Debug for Trip ID: <?php echo htmlspecialchars($trip_id); ?></h1>
    
    <?php if (!$user_id): ?>
        <div class="section error">
            <p>Not logged in! Please log in first.</p>
        </div>
    <?php exit; endif; ?>
    
    <div class="section">
        <h2>1. Direct Database Check</h2>
        <?php
        try {
            $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
            $stmt = $db->prepare("SELECT * FROM trips WHERE id = ? AND user_id = ?");
            $stmt->execute([$trip_id, $user_id]);
            $trip = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($trip) {
                echo "<p class='success'>✓ Trip found in database</p>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Value</th></tr>";
                echo "<tr><td>ID</td><td>{$trip['id']}</td></tr>";
                echo "<tr><td>Title</td><td>" . htmlspecialchars($trip['title']) . "</td></tr>";
                echo "<tr><td>Photo Path</td><td class='" . ($trip['photo_path'] ? 'success' : 'warning') . "'>" . 
                     htmlspecialchars($trip['photo_path'] ?: 'NULL') . "</td></tr>";
                echo "<tr><td>Photo Alt Text</td><td>" . htmlspecialchars($trip['photo_alt_text'] ?: 'NULL') . "</td></tr>";
                echo "<tr><td>Updated At</td><td>{$trip['updated_at']}</td></tr>";
                echo "</table>";
                
                if ($trip['photo_path']) {
                    $fullPath = __DIR__ . '/' . $trip['photo_path'];
                    echo "<h3>Photo File Check:</h3>";
                    if (file_exists($fullPath)) {
                        echo "<p class='success'>✓ File exists at: " . htmlspecialchars($trip['photo_path']) . "</p>";
                        echo "<p>File size: " . filesize($fullPath) . " bytes</p>";
                        echo "<img src='{$trip['photo_path']}' alt='Trip photo'>";
                    } else {
                        echo "<p class='error'>✗ File NOT found at: " . htmlspecialchars($fullPath) . "</p>";
                    }
                }
            } else {
                echo "<p class='error'>✗ Trip not found or doesn't belong to user</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>2. AJAX Handler Test</h2>
        <button onclick="testAjaxHandler()">Test AJAX Handler</button>
        <div id="ajax-result" class="code" style="margin-top: 10px; display: none;"></div>
    </div>
    
    <div class="section">
        <h2>3. BTTApi Test</h2>
        <button onclick="testBTTApi()">Test BTTApi.get()</button>
        <div id="bttapi-result" class="code" style="margin-top: 10px; display: none;"></div>
    </div>
    
    <div class="section">
        <h2>4. Full Trips.js Simulation</h2>
        <button onclick="simulateTripsJs()">Simulate trips.js Load</button>
        <div id="trips-simulation" style="margin-top: 10px;"></div>
    </div>
    
    <script src="assets/js/btt-api.js?v=<?php echo time(); ?>"></script>
    <script>
    const tripId = <?php echo $trip_id; ?>;
    
    async function testAjaxHandler() {
        const resultDiv = document.getElementById('ajax-result');
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = 'Loading...';
        
        try {
            const response = await fetch(`ajax-handler.php?route=trips&id=${tripId}`, {
                credentials: 'include',
                headers: { 'Accept': 'application/json' }
            });
            
            const text = await response.text();
            console.log('Raw AJAX response:', text);
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                resultDiv.innerHTML = `<span class="error">JSON Parse Error:</span><br>${text}`;
                return;
            }
            
            resultDiv.innerHTML = `
                <p><strong>Status:</strong> ${response.status}</p>
                <p><strong>Response Type:</strong> ${typeof data}</p>
                <p><strong>photo_path:</strong> <span class="${data.photo_path ? 'success' : 'warning'}">${data.photo_path || 'NULL'}</span></p>
                <pre>${JSON.stringify(data, null, 2)}</pre>
            `;
        } catch (error) {
            resultDiv.innerHTML = `<span class="error">Error:</span> ${error.message}`;
        }
    }
    
    async function testBTTApi() {
        const resultDiv = document.getElementById('bttapi-result');
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = 'Loading...';
        
        try {
            if (typeof BTTApi === 'undefined') {
                throw new Error('BTTApi not loaded');
            }
            
            const data = await BTTApi.get('trips', tripId);
            console.log('BTTApi response:', data);
            
            resultDiv.innerHTML = `
                <p><strong>Response Type:</strong> ${typeof data}</p>
                <p><strong>photo_path:</strong> <span class="${data.photo_path ? 'success' : 'warning'}">${data.photo_path || 'NULL'}</span></p>
                <pre>${JSON.stringify(data, null, 2)}</pre>
            `;
        } catch (error) {
            resultDiv.innerHTML = `<span class="error">Error:</span> ${error.message}`;
        }
    }
    
    async function simulateTripsJs() {
        const resultDiv = document.getElementById('trips-simulation');
        resultDiv.innerHTML = '<p>Loading trips like trips.js does...</p>';
        
        try {
            // Simulate the loadTrips function
            console.log('Loading trips via ajax-handler...');
            const response = await fetch('ajax-handler.php?route=trips', {
                credentials: 'include'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const trips = await response.json();
            console.log('Loaded trips:', trips);
            
            // Find our test trip
            const testTrip = trips.find(t => t.id == tripId);
            
            let html = '<h3>All Trips Loaded:</h3>';
            html += '<table><tr><th>ID</th><th>Title</th><th>Photo Path</th><th>Photo Display</th></tr>';
            
            trips.forEach(trip => {
                const isTestTrip = trip.id == tripId;
                html += `<tr${isTestTrip ? ' style="background: #ffffcc"' : ''}>`;
                html += `<td>${trip.id}</td>`;
                html += `<td>${trip.title}</td>`;
                html += `<td class="${trip.photo_path ? 'success' : 'warning'}">${trip.photo_path || 'NULL'}</td>`;
                html += '<td>';
                
                // Simulate photo URL building from trips.js
                let photoUrl;
                if (trip.photo_path) {
                    if (trip.photo_path.startsWith('http')) {
                        photoUrl = trip.photo_path;
                    } else if (trip.photo_path.startsWith('assets/img/')) {
                        photoUrl = trip.photo_path;
                    } else {
                        photoUrl = `assets/img/trips/${trip.photo_path}`;
                    }
                    html += `<img src="${photoUrl}" style="max-width: 100px;" onerror="this.style.border='2px solid red'">`;
                } else {
                    html += 'No photo';
                }
                
                html += '</td></tr>';
            });
            
            html += '</table>';
            
            if (testTrip) {
                html += `<h3>Test Trip (ID ${tripId}) Details:</h3>`;
                html += `<pre>${JSON.stringify(testTrip, null, 2)}</pre>`;
            } else {
                html += `<p class="error">Trip ID ${tripId} not found in results!</p>`;
            }
            
            resultDiv.innerHTML = html;
        } catch (error) {
            resultDiv.innerHTML = `<p class="error">Error: ${error.message}</p>`;
            console.error('Simulation error:', error);
        }
    }
    </script>
</body>
</html>