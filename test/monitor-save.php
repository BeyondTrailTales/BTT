<?php
/**
 * Monitor what's being saved when updating a backpack
 */

// Set error reporting to display all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/api/classes/Database.php';
require_once dirname(__DIR__) . '/app/Services/AuthService.php';

use App\Services\AuthService;

// Start session for auth
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monitor Backpack Save</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body { font-family: Arial; padding: 20px; }
        .log { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 4px; font-family: monospace; white-space: pre-wrap; }
        button { background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #059669; }
        .error { background: #fee; color: #800; }
        .success { background: #efe; color: #080; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Monitor Backpack Save Process</h1>
    
    <div class="section">
        <h2>1. Login First</h2>
        <input type="email" id="email" placeholder="Email" value="demo@example.com">
        <input type="password" id="password" placeholder="Password" value="Demo123!">
        <button onclick="login()">Login</button>
        <div id="login-result" class="log"></div>
    </div>
    
    <div class="section">
        <h2>2. Test Save with Items</h2>
        <p>This will update an existing backpack with test items and show exactly what's being sent and received.</p>
        <input type="text" id="pack-id" placeholder="Pack ID (leave empty to use most recent)">
        <button onclick="testSave()">Test Save</button>
        <div id="save-log" class="log"></div>
    </div>
    
    <div class="section">
        <h2>3. Check Database</h2>
        <button onclick="checkDatabase()">Check Items in Database</button>
        <div id="db-log" class="log"></div>
    </div>

    <script>
        const apiBase = '/BTT/api/index.php';
        
        async function login() {
            const email = $('#email').val();
            const password = $('#password').val();
            
            try {
                const response = await $.ajax({
                    url: apiBase + '?route=auth&id=login',
                    method: 'POST',
                    data: JSON.stringify({ login: email, password: password }),
                    contentType: 'application/json',
                    xhrFields: { withCredentials: true }
                });
                
                if (response.success) {
                    $('#login-result').removeClass('error').addClass('success').text('✅ Logged in as ' + response.data.user.username);
                } else {
                    $('#login-result').addClass('error').text('Login failed: ' + (response.message || 'Unknown error'));
                }
            } catch (error) {
                $('#login-result').addClass('error').text('Login error: ' + error.responseText);
            }
        }
        
        async function testSave() {
            const log = $('#save-log');
            log.text('Starting save test...\n');
            
            // Get pack ID or use most recent
            let packId = $('#pack-id').val();
            
            if (!packId) {
                // Get most recent pack
                try {
                    const listResponse = await $.ajax({
                        url: apiBase + '?route=backpacks',
                        method: 'GET',
                        xhrFields: { withCredentials: true }
                    });
                    
                    if (listResponse.success && listResponse.data.length > 0) {
                        packId = listResponse.data[0].id;
                        log.append(`Using most recent pack ID: ${packId}\n`);
                    } else {
                        log.append('No packs found. Create one first.\n');
                        return;
                    }
                } catch (error) {
                    log.append('Error getting packs: ' + error.responseText + '\n');
                    return;
                }
            }
            
            // Create test data with items
            const updateData = {
                name: 'Test Pack (Monitor Update)',
                sections: [
                    {
                        id: 'main',
                        name: 'Main Compartment',
                        order: 0,
                        items: [
                            {
                                name: 'Monitor Test Item 1',
                                weight_g: 100,
                                quantity: 1,
                                category: 'test',
                                brand: 'Test',
                                notes: 'Added via monitor'
                            },
                            {
                                name: 'Monitor Test Item 2',
                                weight_g: 200,
                                quantity: 2,
                                category: 'test'
                            }
                        ]
                    },
                    {
                        id: 'lid',
                        name: 'Top Lid',
                        order: 1,
                        items: [
                            {
                                name: 'Monitor Lid Item',
                                weight_g: 50,
                                quantity: 1,
                                category: 'test'
                            }
                        ]
                    }
                ]
            };
            
            log.append('\n📤 SENDING DATA:\n' + JSON.stringify(updateData, null, 2) + '\n\n');
            
            try {
                const response = await $.ajax({
                    url: apiBase + '?route=backpacks&id=' + packId,
                    method: 'PUT',
                    data: JSON.stringify(updateData),
                    contentType: 'application/json',
                    xhrFields: { withCredentials: true }
                });
                
                log.append('📥 RESPONSE:\n' + JSON.stringify(response, null, 2) + '\n\n');
                
                if (response.success) {
                    log.append('✅ Save successful!\n\n');
                    
                    // Now load the pack to see what was actually saved
                    log.append('Loading pack to verify...\n');
                    
                    const loadResponse = await $.ajax({
                        url: apiBase + '?route=backpacks&id=' + packId,
                        method: 'GET',
                        xhrFields: { withCredentials: true }
                    });
                    
                    if (loadResponse.success) {
                        const pack = loadResponse.data;
                        log.append(`\n📦 LOADED PACK:\n`);
                        log.append(`Name: ${pack.name}\n`);
                        log.append(`Total Items: ${pack.total_items || 0}\n`);
                        
                        if (pack.sections) {
                            log.append(`\nSections (${pack.sections.length}):\n`);
                            pack.sections.forEach(section => {
                                log.append(`  ${section.name}: ${section.items ? section.items.length : 0} items\n`);
                                if (section.items) {
                                    section.items.forEach(item => {
                                        log.append(`    - ${item.name} (${item.weight_g}g x ${item.quantity})\n`);
                                    });
                                }
                            });
                        } else {
                            log.append('No sections in response\n');
                        }
                    }
                } else {
                    log.addClass('error').append('❌ Save failed: ' + (response.message || 'Unknown error') + '\n');
                }
            } catch (error) {
                log.addClass('error').append('❌ Error: ' + error.responseText + '\n');
            }
        }
        
        async function checkDatabase() {
            const log = $('#db-log');
            log.text('Checking database...\n');
            
            // Make a request to check the database directly
            $.ajax({
                url: 'check-db-items.php',
                method: 'GET',
                success: function(data) {
                    log.append(data);
                },
                error: function(error) {
                    log.addClass('error').append('Error: ' + error.responseText);
                }
            });
        }
    </script>
</body>
</html>
