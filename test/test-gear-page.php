<?php
/**
 * Test file for Gear Page Functionality
 * Tests CRUD operations and basic functionality
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

// Ensure we have a test user session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1; // Test user

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gear Page Test</title>
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
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #10b981;
            padding-bottom: 10px;
        }
        .test-result {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .test-result.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .test-result.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .test-result.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        button {
            background: #10b981;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #059669;
        }
        .gear-item {
            padding: 10px;
            background: #f9f9f9;
            margin: 5px 0;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .gear-actions button {
            padding: 5px 10px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h1>🧪 Gear Page Test Suite</h1>
    
    <div class="test-section">
        <h2>API Connectivity Test</h2>
        <button onclick="testAPIConnection()">Test API Connection</button>
        <div id="api-test-result"></div>
    </div>

    <div class="test-section">
        <h2>CRUD Operations Test</h2>
        <button onclick="testCreateGear()">Test Create</button>
        <button onclick="testReadGear()">Test Read</button>
        <button onclick="testUpdateGear()">Test Update</button>
        <button onclick="testDeleteGear()">Test Delete</button>
        <div id="crud-test-result"></div>
    </div>

    <div class="test-section">
        <h2>Weight Conversion Test</h2>
        <button onclick="testWeightConversion()">Run Conversion Tests</button>
        <div id="conversion-test-result"></div>
    </div>

    <div class="test-section">
        <h2>Current Gear Items</h2>
        <button onclick="loadGearItems()">Load Items</button>
        <div id="gear-list"></div>
    </div>

    <div class="test-section">
        <h2>Quick Add Test Item</h2>
        <form id="quick-add-form">
            <input type="text" id="test-name" placeholder="Item name" required>
            <select id="test-category" required>
                <option value="">Select category</option>
                <option value="shelter">Shelter</option>
                <option value="sleep">Sleep</option>
                <option value="cooking">Cooking</option>
                <option value="clothing">Clothing</option>
                <option value="navigation">Navigation</option>
                <option value="other">Other</option>
            </select>
            <input type="number" id="test-weight" placeholder="Weight (g)" required min="0">
            <button type="submit">Add Item</button>
        </form>
        <div id="quick-add-result"></div>
    </div>

    <script src="../vendor/jquery-3.7.1.min.js"></script>
    <script>
        const API_URL = '<?php echo BTT_API_URL; ?>';
        const CSRF_TOKEN = '<?php echo csrf_token(); ?>';
        
        let testItemId = null;

        // Test API Connection
        function testAPIConnection() {
            const resultDiv = $('#api-test-result');
            resultDiv.html('<div class="test-result info">Testing API connection...</div>');
            
            $.ajax({
                url: API_URL + '/?route=gear',
                method: 'GET',
                headers: {
                    'X-CSRF-Token': CSRF_TOKEN
                },
                success: function(response) {
                    if (response.success) {
                        resultDiv.html('<div class="test-result success">✅ API Connected Successfully! Found ' + (response.data.items?.length || 0) + ' items.</div>');
                    } else {
                        resultDiv.html('<div class="test-result error">❌ API returned error: ' + (response.message || 'Unknown error') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    resultDiv.html('<div class="test-result error">❌ Connection failed: ' + error + '</div>');
                }
            });
        }

        // Test Create
        function testCreateGear() {
            const resultDiv = $('#crud-test-result');
            resultDiv.html('<div class="test-result info">Creating test item...</div>');
            
            const testData = {
                name: 'Test Tent ' + Date.now(),
                category: 'shelter',
                weight_g: 1500,
                tags: ['test', 'ultralight'],
                notes: 'This is a test item created at ' + new Date().toLocaleString()
            };
            
            $.ajax({
                url: API_URL + '/?route=gear',
                method: 'POST',
                data: JSON.stringify(testData),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-Token': CSRF_TOKEN
                },
                success: function(response) {
                    if (response.success && response.data) {
                        testItemId = response.data.id;
                        resultDiv.html('<div class="test-result success">✅ Created item with ID: ' + testItemId + '</div>');
                        loadGearItems();
                    } else {
                        resultDiv.html('<div class="test-result error">❌ Create failed: ' + (response.message || 'Unknown error') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    resultDiv.html('<div class="test-result error">❌ Create request failed: ' + error + '</div>');
                }
            });
        }

        // Test Read
        function testReadGear() {
            const resultDiv = $('#crud-test-result');
            
            if (!testItemId) {
                resultDiv.html('<div class="test-result error">❌ No test item to read. Create one first.</div>');
                return;
            }
            
            resultDiv.html('<div class="test-result info">Reading test item...</div>');
            
            $.ajax({
                url: API_URL + '/?route=gear/' + testItemId,
                method: 'GET',
                headers: {
                    'X-CSRF-Token': CSRF_TOKEN
                },
                success: function(response) {
                    if (response.success && response.data) {
                        resultDiv.html('<div class="test-result success">✅ Read item: ' + response.data.name + '</div>');
                    } else {
                        resultDiv.html('<div class="test-result error">❌ Read failed: ' + (response.message || 'Unknown error') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    resultDiv.html('<div class="test-result error">❌ Read request failed: ' + error + '</div>');
                }
            });
        }

        // Test Update
        function testUpdateGear() {
            const resultDiv = $('#crud-test-result');
            
            if (!testItemId) {
                resultDiv.html('<div class="test-result error">❌ No test item to update. Create one first.</div>');
                return;
            }
            
            resultDiv.html('<div class="test-result info">Updating test item...</div>');
            
            const updateData = {
                name: 'Updated Test Tent ' + Date.now(),
                weight_g: 1200
            };
            
            $.ajax({
                url: API_URL + '/?route=gear/' + testItemId,
                method: 'PUT',
                data: JSON.stringify(updateData),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-Token': CSRF_TOKEN
                },
                success: function(response) {
                    if (response.success) {
                        resultDiv.html('<div class="test-result success">✅ Updated item successfully</div>');
                        loadGearItems();
                    } else {
                        resultDiv.html('<div class="test-result error">❌ Update failed: ' + (response.message || 'Unknown error') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    resultDiv.html('<div class="test-result error">❌ Update request failed: ' + error + '</div>');
                }
            });
        }

        // Test Delete
        function testDeleteGear() {
            const resultDiv = $('#crud-test-result');
            
            if (!testItemId) {
                resultDiv.html('<div class="test-result error">❌ No test item to delete. Create one first.</div>');
                return;
            }
            
            resultDiv.html('<div class="test-result info">Deleting test item...</div>');
            
            $.ajax({
                url: API_URL + '/?route=gear/' + testItemId,
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': CSRF_TOKEN
                },
                success: function(response) {
                    if (response.success) {
                        resultDiv.html('<div class="test-result success">✅ Deleted item successfully</div>');
                        testItemId = null;
                        loadGearItems();
                    } else {
                        resultDiv.html('<div class="test-result error">❌ Delete failed: ' + (response.message || 'Unknown error') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    resultDiv.html('<div class="test-result error">❌ Delete request failed: ' + error + '</div>');
                }
            });
        }

        // Test Weight Conversion
        function testWeightConversion() {
            const resultDiv = $('#conversion-test-result');
            resultDiv.empty();
            
            // Test conversions
            const tests = [
                { value: 1000, from: 'grams', to: 'ounces', expected: 35.27 },
                { value: 1, from: 'pounds', to: 'grams', expected: 453.59 },
                { value: 10, from: 'ounces', to: 'grams', expected: 283.50 }
            ];
            
            const CONVERSIONS = {
                grams: 1,
                ounces: 28.3495,
                pounds: 453.592
            };
            
            tests.forEach(test => {
                const grams = test.value * CONVERSIONS[test.from];
                const result = grams / CONVERSIONS[test.to];
                const rounded = Math.round(result * 100) / 100;
                
                const passed = Math.abs(rounded - test.expected) < 0.1;
                const status = passed ? 'success' : 'error';
                const icon = passed ? '✅' : '❌';
                
                resultDiv.append(`
                    <div class="test-result ${status}">
                        ${icon} ${test.value} ${test.from} → ${rounded} ${test.to} 
                        (expected: ${test.expected})
                    </div>
                `);
            });
        }

        // Load Gear Items
        function loadGearItems() {
            const listDiv = $('#gear-list');
            listDiv.html('<div class="test-result info">Loading items...</div>');
            
            $.ajax({
                url: API_URL + '/?route=gear',
                method: 'GET',
                headers: {
                    'X-CSRF-Token': CSRF_TOKEN
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const items = response.data.items || [];
                        
                        if (items.length === 0) {
                            listDiv.html('<div class="test-result info">No items found</div>');
                            return;
                        }
                        
                        let html = '';
                        items.forEach(item => {
                            html += `
                                <div class="gear-item">
                                    <div>
                                        <strong>${item.name}</strong> - 
                                        ${item.category} - 
                                        ${item.weight_g}g
                                        ${item.is_default ? '<span style="color: #666;">(Default)</span>' : ''}
                                    </div>
                                    ${!item.is_default ? `
                                        <div class="gear-actions">
                                            <button onclick="deleteItem('${item.id}')">Delete</button>
                                        </div>
                                    ` : ''}
                                </div>
                            `;
                        });
                        
                        listDiv.html(html);
                    } else {
                        listDiv.html('<div class="test-result error">❌ Failed to load items</div>');
                    }
                },
                error: function(xhr, status, error) {
                    listDiv.html('<div class="test-result error">❌ Request failed: ' + error + '</div>');
                }
            });
        }

        // Delete specific item
        function deleteItem(itemId) {
            if (confirm('Delete this item?')) {
                $.ajax({
                    url: API_URL + '/?route=gear/' + itemId,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    success: function(response) {
                        if (response.success) {
                            loadGearItems();
                        } else {
                            alert('Delete failed: ' + (response.message || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Delete request failed: ' + error);
                    }
                });
            }
        }

        // Quick Add Form
        $('#quick-add-form').on('submit', function(e) {
            e.preventDefault();
            
            const resultDiv = $('#quick-add-result');
            
            const data = {
                name: $('#test-name').val(),
                category: $('#test-category').val(),
                weight_g: parseInt($('#test-weight').val()),
                tags: [],
                notes: 'Added from test page'
            };
            
            $.ajax({
                url: API_URL + '/?route=gear',
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-Token': CSRF_TOKEN
                },
                success: function(response) {
                    if (response.success) {
                        resultDiv.html('<div class="test-result success">✅ Item added successfully!</div>');
                        $('#quick-add-form')[0].reset();
                        loadGearItems();
                    } else {
                        resultDiv.html('<div class="test-result error">❌ Failed: ' + (response.message || 'Unknown error') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    resultDiv.html('<div class="test-result error">❌ Request failed: ' + error + '</div>');
                }
            });
        });

        // Load items on page load
        $(document).ready(function() {
            testAPIConnection();
            loadGearItems();
        });
    </script>
</body>
</html>
