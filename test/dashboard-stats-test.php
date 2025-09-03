<?php
/**
 * Dashboard Statistics Test Page
 * Tests that all dashboard statistics are pulling correctly from the database
 */

// Load bootstrap
require_once dirname(__DIR__) . '/app/bootstrap.php';

// Set page metadata
$pageTitle = 'Dashboard Stats Test';
$pageDescription = 'Testing dashboard statistics database integration';
$pageId = 'dashboard-test';
?>

<!DOCTYPE html>
<html lang="en" data-theme="forest-dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | BeyondTrailTales</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-tokens.css">
    <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-base.css">
    <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-components.css">
    <style>
        body {
            padding: 2rem;
            background: var(--gradient-forest);
        }
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-2xl);
            padding: 2rem;
        }
        .test-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: rgba(255,255,255,0.05);
            border-radius: var(--radius-lg);
        }
        .test-title {
            color: var(--forest-mint);
            margin-bottom: 1rem;
            font-size: 1.5rem;
            font-weight: 700;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .stat-item {
            background: var(--glass-bg);
            padding: 1rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--glass-border);
        }
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        .stat-value {
            color: var(--forest-emerald);
            font-size: 2rem;
            font-weight: 700;
        }
        .api-response {
            background: #1a1a1a;
            color: #10b981;
            padding: 1rem;
            border-radius: var(--radius-md);
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            overflow-x: auto;
            max-height: 400px;
            overflow-y: auto;
        }
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        .status-success {
            background: var(--forest-emerald);
            box-shadow: 0 0 10px var(--forest-emerald);
        }
        .status-error {
            background: var(--danger);
            box-shadow: 0 0 10px var(--danger);
        }
        .test-button {
            background: var(--gradient-success);
            color: var(--forest-deep);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-full);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-all);
            margin-top: 1rem;
        }
        .test-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-glow-md);
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1 style="color: var(--forest-mint); margin-bottom: 2rem;">🧪 Dashboard Statistics Test</h1>
        
        <!-- Database Connection Test -->
        <div class="test-section">
            <h2 class="test-title">Database Connection</h2>
            <div id="db-status"></div>
        </div>

        <!-- Live Statistics -->
        <div class="test-section">
            <h2 class="test-title">Live Statistics from Database</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-label">Total Trips</div>
                    <div class="stat-value" id="trips-count">-</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Total Backpacks</div>
                    <div class="stat-value" id="backpacks-count">-</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Current Level</div>
                    <div class="stat-value" id="level-count">-</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Day Streak</div>
                    <div class="stat-value" id="streak-count">-</div>
                </div>
            </div>
            <button class="test-button" onclick="refreshStats()">🔄 Refresh Statistics</button>
        </div>

        <!-- API Response Details -->
        <div class="test-section">
            <h2 class="test-title">API Response Details</h2>
            
            <h3 style="color: var(--forest-sage); margin-top: 1.5rem;">Trips API Response:</h3>
            <div class="api-response" id="trips-response">Loading...</div>
            
            <h3 style="color: var(--forest-sage); margin-top: 1.5rem;">Backpacks API Response:</h3>
            <div class="api-response" id="backpacks-response">Loading...</div>
            
            <h3 style="color: var(--forest-sage); margin-top: 1.5rem;">Gamification API Response:</h3>
            <div class="api-response" id="gamification-response">Loading...</div>
        </div>

        <!-- Test Actions -->
        <div class="test-section">
            <h2 class="test-title">Test Actions</h2>
            <button class="test-button" onclick="createTestTrip()">➕ Create Test Trip</button>
            <button class="test-button" onclick="createTestBackpack()" style="margin-left: 1rem;">➕ Create Test Backpack</button>
            <button class="test-button" onclick="clearTestData()" style="margin-left: 1rem; background: var(--gradient-danger);">🗑️ Clear Test Data</button>
        </div>
    </div>

    <script>
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkDatabaseConnection();
            refreshStats();
        });

        async function checkDatabaseConnection() {
            const statusDiv = document.getElementById('db-status');
            try {
                const response = await fetch('<?php echo BTT_API_URL; ?>?route=health');
                const data = await response.json();
                
                if (data.data && data.data.status === 'healthy') {
                    statusDiv.innerHTML = `
                        <div>
                            <span class="status-indicator status-success"></span>
                            <strong>Status:</strong> Connected
                        </div>
                        <div style="margin-top: 0.5rem;">
                            <strong>Storage Engine:</strong> ${data.data.storage_engine}
                        </div>
                        <div style="margin-top: 0.5rem;">
                            <strong>Database:</strong> ${data.data.database}
                        </div>
                        <div style="margin-top: 0.5rem;">
                            <strong>Tables:</strong> ${data.data.tables ? data.data.tables.join(', ') : 'N/A'}
                        </div>
                    `;
                } else {
                    statusDiv.innerHTML = `
                        <span class="status-indicator status-error"></span>
                        <strong>Status:</strong> Error
                    `;
                }
            } catch (error) {
                statusDiv.innerHTML = `
                    <span class="status-indicator status-error"></span>
                    <strong>Status:</strong> Connection Failed - ${error.message}
                `;
            }
        }

        async function refreshStats() {
            // Fetch trips
            try {
                const tripsResponse = await fetch('<?php echo BTT_API_URL; ?>?route=trips');
                const tripsData = await tripsResponse.json();
                
                document.getElementById('trips-response').textContent = JSON.stringify(tripsData, null, 2);
                
                if (tripsData.success && tripsData.data) {
                    document.getElementById('trips-count').textContent = tripsData.data.length;
                } else {
                    document.getElementById('trips-count').textContent = '0';
                }
            } catch (error) {
                document.getElementById('trips-response').textContent = 'Error: ' + error.message;
                document.getElementById('trips-count').textContent = 'Error';
            }

            // Fetch backpacks
            try {
                const backpacksResponse = await fetch('<?php echo BTT_API_URL; ?>?route=backpacks');
                const backpacksData = await backpacksResponse.json();
                
                document.getElementById('backpacks-response').textContent = JSON.stringify(backpacksData, null, 2);
                
                if (backpacksData.success && backpacksData.data) {
                    document.getElementById('backpacks-count').textContent = backpacksData.data.length;
                } else {
                    document.getElementById('backpacks-count').textContent = '0';
                }
            } catch (error) {
                document.getElementById('backpacks-response').textContent = 'Error: ' + error.message;
                document.getElementById('backpacks-count').textContent = 'Error';
            }

            // Fetch gamification stats
            try {
                const gamificationResponse = await fetch('/BTT/api/routes/gamification.php?action=status');
                const gamificationData = await gamificationResponse.json();
                
                document.getElementById('gamification-response').textContent = JSON.stringify(gamificationData, null, 2);
                
                document.getElementById('level-count').textContent = gamificationData.level || '1';
                document.getElementById('streak-count').textContent = gamificationData.streak_days || '0';
            } catch (error) {
                document.getElementById('gamification-response').textContent = 'Error: ' + error.message;
                document.getElementById('level-count').textContent = 'Error';
                document.getElementById('streak-count').textContent = 'Error';
            }
        }

        async function createTestTrip() {
            const tripData = {
                title: 'Test Trip ' + new Date().toISOString(),
                location: 'Test Mountain Trail',
                description: 'This is a test trip created for dashboard verification',
                start_date: new Date().toISOString().split('T')[0],
                distance: 10,
                difficulty: 'moderate',
                trip_type: 'day_hike'
            };

            try {
                const response = await fetch('<?php echo BTT_API_URL; ?>?route=trips', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(tripData)
                });

                const result = await response.json();
                if (result.success) {
                    alert('Test trip created successfully!');
                    refreshStats();
                } else {
                    alert('Failed to create test trip: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                alert('Error creating test trip: ' + error.message);
            }
        }

        async function createTestBackpack() {
            const backpackData = {
                name: 'Test Backpack ' + new Date().toISOString(),
                description: 'This is a test backpack created for dashboard verification',
                base_weight: 3.5,
                type: 'day-hike'
            };

            try {
                const response = await fetch('<?php echo BTT_API_URL; ?>?route=backpacks', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(backpackData)
                });

                const result = await response.json();
                if (result.success) {
                    alert('Test backpack created successfully!');
                    refreshStats();
                } else {
                    alert('Failed to create test backpack: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                alert('Error creating test backpack: ' + error.message);
            }
        }

        async function clearTestData() {
            if (!confirm('This will delete all test trips and backpacks. Are you sure?')) {
                return;
            }

            alert('Clear test data functionality needs to be implemented based on your requirements.');
            // You would implement the actual deletion logic here
        }
    </script>
</body>
</html>
