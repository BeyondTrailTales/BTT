<?php
session_name('BTT_SESSION');
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Test - Logged In</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .test { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; }
        .error { background: #f8d7da; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        pre { background: #f4f5f7; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>API Test - Session: <?= session_id() ?></h1>
    
    <div class="test">
        <h3>Session Data:</h3>
        <pre><?= json_encode($_SESSION, JSON_PRETTY_PRINT) ?></pre>
    </div>
    
    <div class="test">
        <h3>Test API Endpoints:</h3>
        <button onclick="testAPI('backpacks')">Test Backpacks</button>
        <button onclick="testAPI('trips')">Test Trips</button>
        <div id="results"></div>
    </div>
    
    <div class="test">
        <h3>Quick Actions:</h3>
        <button onclick="createSampleData()">Create Sample Data</button>
    </div>
    
    <script>
    function testAPI(route) {
        const resultsDiv = document.getElementById('results');
        resultsDiv.innerHTML = 'Loading...';
        
        fetch('/BTT/api/index.php?route=' + route, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            resultsDiv.innerHTML = '<h4>' + route + ' Response:</h4><pre>' + JSON.stringify(data, null, 2) + '</pre>';
        })
        .catch(error => {
            resultsDiv.innerHTML = '<p style="color: red;">Error: ' + error.message + '</p>';
        });
    }
    
    function createSampleData() {
        // Create a sample backpack
        fetch('/BTT/api/index.php?route=backpacks', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                name: 'Test Backpack ' + Date.now(),
                description: 'A test backpack',
                base_weight: 2.5
            })
        })
        .then(response => response.json())
        .then(data => {
            alert('Created backpack: ' + JSON.stringify(data));
            testAPI('backpacks');
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    }
    </script>
</body>
</html>
