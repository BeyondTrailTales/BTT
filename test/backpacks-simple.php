<?php
session_start();
$_SESSION['user_id'] = 1; // Hardcode user for testing
$_SESSION['logged_in'] = true;

// Direct database connection
$db = new PDO('sqlite:' . dirname(__DIR__) . '/storage/sqlite/btt.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'save') {
        $data = json_decode($_POST['data'], true);
        
        $sql = "INSERT INTO backpacks (user_id, name, description, created_at, updated_at) 
                VALUES (1, :name, :desc, datetime('now'), datetime('now'))";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'name' => $data['name'],
            'desc' => $data['description'] ?? ''
        ]);
        
        echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
        exit;
    }
    
    if ($_POST['action'] === 'load') {
        $result = $db->query("SELECT * FROM backpacks WHERE user_id = 1 ORDER BY id DESC");
        $packs = $result->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $packs]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Backpacks</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .pack { border: 1px solid #ccc; padding: 10px; margin: 10px 0; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        input, textarea { width: 100%; padding: 5px; margin: 5px 0; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Backpack Manager (Simple Version)</h1>
    
    <div>
        <h2>Create New Pack</h2>
        <input type="text" id="pack-name" placeholder="Pack Name">
        <textarea id="pack-desc" placeholder="Description"></textarea>
        <button onclick="savePack()">Save Pack</button>
        <div id="status"></div>
    </div>
    
    <div>
        <h2>Your Packs</h2>
        <button onclick="loadPacks()">Refresh List</button>
        <div id="packs-list"></div>
    </div>
    
    <script>
        function savePack() {
            const name = document.getElementById('pack-name').value;
            const desc = document.getElementById('pack-desc').value;
            
            if (!name) {
                alert('Name is required');
                return;
            }
            
            const data = {
                name: name,
                description: desc
            };
            
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=save&data=' + encodeURIComponent(JSON.stringify(data))
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    document.getElementById('status').innerHTML = '<div class="success">✓ Saved! ID: ' + result.id + '</div>';
                    document.getElementById('pack-name').value = '';
                    document.getElementById('pack-desc').value = '';
                    loadPacks();
                }
            });
        }
        
        function loadPacks() {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=load'
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    let html = '';
                    result.data.forEach(pack => {
                        html += '<div class="pack">';
                        html += '<strong>' + pack.name + '</strong><br>';
                        html += 'ID: ' + pack.id + '<br>';
                        html += pack.description || 'No description';
                        html += '</div>';
                    });
                    document.getElementById('packs-list').innerHTML = html || 'No packs found';
                }
            });
        }
        
        // Load packs on page load
        loadPacks();
    </script>
</body>
</html>
