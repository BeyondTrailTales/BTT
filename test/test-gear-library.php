<?php
session_start();
$_SESSION['user_id'] = 1; // Simulate logged in user
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Gear Library</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .stats { background: #f0f0f0; padding: 10px; margin: 10px 0; }
        .gear-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
        .gear-item { border: 1px solid #ccc; padding: 10px; background: white; }
        .gear-item.default { background: #e3f2fd; }
        .gear-item.custom { background: #fff3e0; }
        .category { font-size: 0.8em; color: #666; }
        .weight { font-weight: bold; color: #2196F3; }
        .icon { font-size: 1.5em; }
    </style>
</head>
<body>
    <h1>Gear Library Test</h1>
    <div class="stats" id="stats">Loading...</div>
    <div class="gear-grid" id="gear-grid">Loading gear...</div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        console.log('Loading gear from AJAX handler...');
        
        $.ajax({
            url: '/BTT/ajax-handler.php?route=gear',
            method: 'GET',
            success: function(data) {
                console.log('Response received:', data);
                
                // Parse if string
                if (typeof data === 'string') {
                    try {
                        data = JSON.parse(data);
                    } catch(e) {
                        $('#gear-grid').html('<p style="color:red;">Error parsing response: ' + e.message + '</p>');
                        return;
                    }
                }
                
                // Display stats
                let categories = {};
                let totalWeight = 0;
                
                data.forEach(item => {
                    categories[item.category] = (categories[item.category] || 0) + 1;
                    totalWeight += parseInt(item.weight_g || item.weight || 0);
                });
                
                $('#stats').html(`
                    <h3>Statistics</h3>
                    <p>Total Items: <strong>${data.length}</strong></p>
                    <p>Total Weight: <strong>${(totalWeight/1000).toFixed(2)}kg</strong></p>
                    <p>Categories: ${Object.keys(categories).length}</p>
                    <details>
                        <summary>Items by Category</summary>
                        <ul>
                            ${Object.entries(categories).map(([cat, count]) => 
                                `<li>${cat}: ${count} items</li>`
                            ).join('')}
                        </ul>
                    </details>
                `);
                
                // Display items
                let html = '';
                data.forEach(item => {
                    const isDefault = item.is_default || item.id?.startsWith('def-');
                    html += `
                        <div class="gear-item ${isDefault ? 'default' : 'custom'}">
                            <div class="icon">${item.icon || '📦'}</div>
                            <div><strong>${item.name}</strong></div>
                            <div class="category">${item.category}</div>
                            <div class="weight">${item.weight_g || item.weight || 0}g</div>
                            ${item.notes ? `<div style="font-size:0.8em;">${item.notes}</div>` : ''}
                            <div style="font-size:0.7em; color:#999;">${isDefault ? 'System' : 'Custom'}</div>
                        </div>
                    `;
                });
                
                $('#gear-grid').html(html);
            },
            error: function(xhr, status, error) {
                console.error('Error loading gear:', error);
                $('#gear-grid').html('<p style="color:red;">Error loading gear: ' + error + '</p>');
            }
        });
    });
    </script>
</body>
</html>
