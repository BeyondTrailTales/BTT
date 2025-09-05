<?php
session_start();

// Test the trips API endpoint to see what's being returned
?>
<!DOCTYPE html>
<html>
<head>
    <title>Trip Photos API Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin-bottom: 30px; padding: 10px; background: #f5f5f5; border-radius: 5px; }
        .trip-photo { max-width: 200px; height: auto; border: 2px solid #ccc; margin: 5px; }
        .error { color: red; }
        .success { color: green; }
        pre { background: #fff; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Trip Photos API Test</h1>
    
    <div class="test-section">
        <h2>1. Session Check</h2>
        <?php if (isset($_SESSION['user_id'])): ?>
            <p class="success">✓ Logged in as user ID: <?php echo $_SESSION['user_id']; ?></p>
        <?php else: ?>
            <p class="error">✗ Not logged in - Please log in first!</p>
        <?php endif; ?>
    </div>
    
    <div class="test-section">
        <h2>2. Fetch Trips via AJAX Handler</h2>
        <button onclick="fetchTrips()">Fetch Trips</button>
        <div id="trips-result"></div>
    </div>
    
    <div class="test-section">
        <h2>3. Direct Database Query</h2>
        <?php
        if (isset($_SESSION['user_id'])) {
            try {
                $db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
                $stmt = $db->prepare("SELECT id, title, photo_path FROM trips WHERE user_id = ? ORDER BY id");
                $stmt->execute([$_SESSION['user_id']]);
                $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Title</th><th>Photo Path</th><th>Image</th></tr>";
                foreach ($trips as $trip) {
                    echo "<tr>";
                    echo "<td>{$trip['id']}</td>";
                    echo "<td>" . htmlspecialchars($trip['title']) . "</td>";
                    echo "<td>" . htmlspecialchars($trip['photo_path'] ?: 'NULL') . "</td>";
                    echo "<td>";
                    if ($trip['photo_path']) {
                        $fullPath = __DIR__ . '/' . $trip['photo_path'];
                        if (file_exists($fullPath)) {
                            echo "<img src='{$trip['photo_path']}' class='trip-photo' alt='Trip photo'>";
                            echo "<br><small class='success'>✓ File exists</small>";
                        } else {
                            echo "<small class='error'>✗ File not found at: {$trip['photo_path']}</small>";
                        }
                    } else {
                        echo "<small>No photo</small>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } catch (Exception $e) {
                echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        ?>
    </div>
    
    <script>
    function fetchTrips() {
        const resultDiv = document.getElementById('trips-result');
        resultDiv.innerHTML = '<p>Fetching...</p>';
        
        fetch('ajax-handler.php?route=trips', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            try {
                const data = JSON.parse(text);
                console.log('Parsed data:', data);
                
                let html = '<h3>API Response:</h3>';
                html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                
                if (Array.isArray(data)) {
                    html += '<h3>Trips with Photos:</h3>';
                    html += '<table border="1" cellpadding="5">';
                    html += '<tr><th>ID</th><th>Title</th><th>Photo Path (from API)</th><th>Display Test</th></tr>';
                    
                    data.forEach(trip => {
                        html += '<tr>';
                        html += `<td>${trip.id}</td>`;
                        html += `<td>${trip.title}</td>`;
                        html += `<td>${trip.photo_path || 'NULL'}</td>`;
                        html += '<td>';
                        if (trip.photo_path) {
                            html += `<img src="${trip.photo_path}" class="trip-photo" onerror="this.nextElementSibling.style.display='block'" alt="Trip photo">`;
                            html += `<small class="error" style="display:none">Failed to load image</small>`;
                        } else {
                            html += 'No photo';
                        }
                        html += '</td>';
                        html += '</tr>';
                    });
                    html += '</table>';
                }
                
                resultDiv.innerHTML = html;
            } catch (e) {
                resultDiv.innerHTML = '<p class="error">Failed to parse JSON: ' + e.message + '</p><pre>' + text + '</pre>';
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            resultDiv.innerHTML = '<p class="error">Fetch error: ' + error.message + '</p>';
        });
    }
    </script>
</body>
</html>