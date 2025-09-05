<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();

// Simple test to ensure pack builder works
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Pack Builder</title>
    <script src="/BTT/vendor/jquery-3.7.1.min.js"></script>
    <style>
        body { 
            font-family: monospace; 
            background: #1a1a1a; 
            color: #fff; 
            padding: 20px;
        }
        .status { 
            padding: 10px; 
            margin: 10px 0; 
            border-radius: 5px; 
        }
        .ok { background: #10b98120; border: 1px solid #10b981; }
        .error { background: #ef444420; border: 1px solid #ef4444; }
        pre { background: #2a2a2a; padding: 10px; border-radius: 5px; }
        a { color: #10b981; }
    </style>
</head>
<body>
    <h1>Pack Builder Test</h1>
    
    <div id="jquery-test" class="status">Checking jQuery...</div>
    <div id="ajax-test" class="status">Checking AJAX...</div>
    <div id="gear-test" class="status">Checking Gear Load...</div>
    <div id="save-test" class="status">Checking Save...</div>
    
    <h2>Test Results:</h2>
    <pre id="results"></pre>
    
    <p>
        <a href="/BTT/pack-builder.php">Go to Pack Builder</a> |
        <a href="/BTT/pack-builder.php?id=1">Edit Pack ID 1</a>
    </p>
    
    <script>
        const log = (msg) => {
            $('#results').append(msg + '\n');
        };
        
        // Test 1: jQuery
        if (typeof $ !== 'undefined') {
            $('#jquery-test').addClass('ok').text('✅ jQuery loaded');
            log('jQuery version: ' + $.fn.jquery);
        } else {
            $('#jquery-test').addClass('error').text('❌ jQuery not loaded');
        }
        
        // Test 2: AJAX
        $.ajax({
            url: '/BTT/ajax-handler.php?route=gear',
            method: 'GET',
            success: (response) => {
                $('#ajax-test').addClass('ok').text('✅ AJAX working');
                log('AJAX test successful');
                
                // Test 3: Gear loading
                if (Array.isArray(response) && response.length > 0) {
                    $('#gear-test').addClass('ok').text(`✅ Loaded ${response.length} gear items`);
                    log(`First gear item: ${response[0].name}`);
                } else {
                    $('#gear-test').addClass('error').text('❌ No gear items found');
                }
            },
            error: (xhr) => {
                $('#ajax-test').addClass('error').text('❌ AJAX failed');
                log('AJAX error: ' + xhr.responseText);
            }
        });
        
        // Test 4: Save test
        const testPack = {
            name: 'Test Pack ' + new Date().toLocaleTimeString(),
            description: 'Auto test',
            sections: [
                {
                    id: 'main',
                    name: 'Main Pack',
                    icon: '🎒',
                    items: [
                        {
                            id: 'test_item_1',
                            gear_id: 'def-tent-1',
                            name: 'Test Tent',
                            weight_g: 1500,
                            category: 'shelter',
                            quantity: 1
                        }
                    ]
                }
            ]
        };
        
        setTimeout(() => {
            $.ajax({
                url: '/BTT/ajax-handler.php?route=backpacks',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(testPack),
                success: (response) => {
                    if (response.success) {
                        $('#save-test').addClass('ok').text('✅ Save working');
                        log('Created pack ID: ' + response.data.id);
                        log('Stats: ' + JSON.stringify(response.stats, null, 2));
                    } else {
                        $('#save-test').addClass('error').text('❌ Save failed');
                        log('Save error: ' + response.message);
                    }
                },
                error: (xhr) => {
                    $('#save-test').addClass('error').text('❌ Save request failed');
                    log('Save error: ' + xhr.responseText);
                }
            });
        }, 1000);
    </script>
</body>
</html>