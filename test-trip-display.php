<?php
session_start();
require_once __DIR__ . '/app/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    die("Please log in first.");
}

$db = new PDO('sqlite:' . __DIR__ . '/storage/sqlite/btt.db');
$stmt = $db->prepare("SELECT * FROM trips WHERE user_id = ? AND photo_path IS NOT NULL ORDER BY id");
$stmt->execute([$_SESSION['user_id']]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Trip Display Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .trip-card { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .trip-image { max-width: 300px; height: auto; margin: 10px 0; }
        .code { background: #f0f0f0; padding: 5px; font-family: monospace; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Trip Display Test</h1>
    
    <h2>Test 1: Direct Database Display</h2>
    <?php foreach ($trips as $trip): ?>
        <div class="trip-card">
            <h3>Trip #<?php echo $trip['id']; ?>: <?php echo htmlspecialchars($trip['title']); ?></h3>
            <p>Photo Path in DB: <span class="code"><?php echo htmlspecialchars($trip['photo_path']); ?></span></p>
            
            <h4>Direct img src test:</h4>
            <img src="<?php echo htmlspecialchars($trip['photo_path']); ?>" class="trip-image" 
                 onerror="this.style.border='3px solid red'; this.nextElementSibling.style.display='block';" 
                 alt="Direct path">
            <p class="error" style="display:none;">Direct path failed to load</p>
            
            <?php
            // Check file existence
            $fullPath = __DIR__ . '/' . $trip['photo_path'];
            if (file_exists($fullPath)) {
                echo '<p class="success">✓ File exists at: ' . htmlspecialchars($fullPath) . '</p>';
            } else {
                echo '<p class="error">✗ File NOT found at: ' . htmlspecialchars($fullPath) . '</p>';
            }
            ?>
        </div>
    <?php endforeach; ?>
    
    <h2>Test 2: JavaScript Rendering (like trips.js)</h2>
    <div id="js-trips"></div>
    
    <h2>Test 3: Load via AJAX Handler</h2>
    <button onclick="loadViaAjax()">Load Trips via AJAX</button>
    <div id="ajax-trips"></div>
    
    <script>
    // Test 2: JavaScript rendering
    const trips = <?php echo json_encode($trips); ?>;
    const container = document.getElementById('js-trips');
    
    trips.forEach(trip => {
        let photoUrl;
        if (trip.photo_path) {
            if (trip.photo_path.startsWith('http')) {
                photoUrl = trip.photo_path;
            } else if (trip.photo_path.startsWith('assets/img/')) {
                photoUrl = trip.photo_path;
            } else {
                photoUrl = `assets/img/trips/${trip.photo_path}`;
            }
        } else {
            photoUrl = 'https://images.unsplash.com/photo-1533873984035-25970ab07461?w=400&h=300&fit=crop';
        }
        
        const card = document.createElement('div');
        card.className = 'trip-card';
        card.innerHTML = `
            <h3>Trip #${trip.id}: ${trip.title}</h3>
            <p>Photo Path: <span class="code">${trip.photo_path}</span></p>
            <p>Computed URL: <span class="code">${photoUrl}</span></p>
            <img src="${photoUrl}" class="trip-image" 
                 onerror="this.style.border='3px solid red';" 
                 alt="JS rendered">
        `;
        container.appendChild(card);
    });
    
    // Test 3: AJAX loading
    async function loadViaAjax() {
        try {
            const response = await fetch('ajax-handler.php?route=trips', {
                credentials: 'include'
            });
            const ajaxTrips = await response.json();
            
            const ajaxContainer = document.getElementById('ajax-trips');
            ajaxContainer.innerHTML = '';
            
            ajaxTrips.forEach(trip => {
                if (!trip.photo_path) return;
                
                let photoUrl;
                if (trip.photo_path.startsWith('http')) {
                    photoUrl = trip.photo_path;
                } else if (trip.photo_path.startsWith('assets/img/')) {
                    photoUrl = trip.photo_path;
                } else {
                    photoUrl = `assets/img/trips/${trip.photo_path}`;
                }
                
                const card = document.createElement('div');
                card.className = 'trip-card';
                card.innerHTML = `
                    <h3>Trip #${trip.id}: ${trip.title}</h3>
                    <p>Photo Path from AJAX: <span class="code">${trip.photo_path}</span></p>
                    <p>Computed URL: <span class="code">${photoUrl}</span></p>
                    <img src="${photoUrl}" class="trip-image" 
                         onerror="this.style.border='3px solid red';" 
                         alt="AJAX loaded">
                `;
                ajaxContainer.appendChild(card);
            });
        } catch (error) {
            document.getElementById('ajax-trips').innerHTML = 
                '<p class="error">Error loading trips: ' + error.message + '</p>';
        }
    }
    </script>
</body>
</html>