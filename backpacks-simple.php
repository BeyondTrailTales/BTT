<?php
// Load bootstrap
require_once __DIR__ . '/app/bootstrap.php';

// Require authentication
require_auth();

// Include Database class
require_once __DIR__ . '/api/classes/Database.php';

// Set page metadata
$pageId = 'backpacks';
$pageTitle = 'My Backpacks';
$pageDescription = 'Manage your backpacking gear lists';

// CRITICAL: Only load essential scripts to prevent timeout
$pageScripts = [];
$pageScripts[] = 'js/pack-builder.js';
$pageScripts[] = 'js/pack-builder-crud.js';

$pageStyles = [];
$pageStyles[] = 'css/backpacks-clean.css';

// Get database connection
$db = Database::getInstance();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect_to_login();
}
$user_id = $_SESSION['user_id'];

// Fetch pack statistics
try {
    $total_packs = $db->fetchOne("SELECT COUNT(*) as count FROM backpacks WHERE user_id = ?", [$user_id]);
    $avg_weight_query = $db->fetchOne("
        SELECT AVG(base_weight) as avg_weight 
        FROM backpacks 
        WHERE user_id = ? AND base_weight > 0
    ", [$user_id]);
    
    $packStats = [
        'total_count' => $total_packs ? $total_packs['count'] : 0,
        'avg_weight' => ($avg_weight_query && $avg_weight_query['avg_weight'] !== null) ? round($avg_weight_query['avg_weight']) : 0
    ];
} catch (Exception $e) {
    error_log("Pack stats fetch error: " . $e->getMessage());
    $packStats = ['total_count' => 0, 'avg_weight' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - BeyondTrailTales</title>
    
    <!-- Minimal CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/backpacks-clean.css'); ?>">
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .nav-simple {
            margin-bottom: 20px;
        }
        .nav-simple a {
            color: #10b981;
            text-decoration: none;
            margin-right: 20px;
        }
        .loading-message {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Simple Navigation -->
        <div class="nav-simple">
            <a href="/BTT/dashboard">← Back to Dashboard</a>
            <a href="/BTT/trips">Trips</a>
            <a href="/BTT/gear">Gear</a>
        </div>

        <!-- Header -->
        <div class="header">
            <h1>🎒 My Backpacks</h1>
            <p>Organize your gear and optimize your loadout</p>
            <div style="margin-top: 20px;">
                <strong>Total Packs:</strong> <?php echo $packStats['total_count']; ?> |
                <strong>Average Weight:</strong> <?php echo $packStats['avg_weight'] > 0 ? number_format($packStats['avg_weight'] / 1000, 1) . 'kg' : '0kg'; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div id="main-content">
            <!-- Tabs -->
            <div style="margin-bottom: 20px;">
                <button class="btn btn-primary pack-tab active" data-view="my-packs">My Packs</button>
                <button class="btn btn-secondary pack-tab" data-view="templates">Templates</button>
                <button class="btn btn-secondary pack-tab" data-view="builder">Builder</button>
                <button id="btn-new-pack" class="btn btn-primary" style="float: right;">+ Create Pack</button>
            </div>

            <!-- Views -->
            <div id="pack-views-container">
                <!-- My Packs View -->
                <div class="pack-view active" id="view-my-packs" data-view="my-packs">
                    <div id="packs-loading" class="loading-message">
                        Loading your packs...
                    </div>
                    <div id="packs-empty" class="empty-state" style="display: none;">
                        <h3>No packs created yet</h3>
                        <p>Start building your first pack to organize your gear</p>
                        <button class="btn btn-primary" onclick="createFirstPack()">Create Your First Pack</button>
                    </div>
                    <div id="packs-grid-items" style="display: none;"></div>
                </div>

                <!-- Templates View -->
                <div class="pack-view" id="view-templates" data-view="templates" style="display: none;">
                    <h3>Templates coming soon...</h3>
                </div>

                <!-- Builder View -->
                <div class="pack-view" id="view-builder" data-view="builder" style="display: none;">
                    <div class="pack-builder-content">
                        <h2>Pack Builder</h2>
                        <form class="pack-form" id="pack-form">
                            <input type="hidden" id="pack-id" name="id">
                            
                            <div class="form-group">
                                <label for="pack-name">Pack Name</label>
                                <input type="text" id="pack-name" name="name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="pack-description">Description</label>
                                <textarea id="pack-description" name="description"></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="pack-type">Type</label>
                                    <select id="pack-type" name="type">
                                        <option value="day-hike">Day Hike</option>
                                        <option value="overnight">Overnight</option>
                                        <option value="weekend">Weekend</option>
                                        <option value="extended">Extended</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="pack-capacity">Capacity (L)</label>
                                    <input type="number" id="pack-capacity" name="capacity_l" value="65">
                                </div>
                            </div>
                            
                            <div style="margin-top: 20px;">
                                <span id="pack-base-weight">Base Weight: 0g</span>
                            </div>
                            
                            <div id="sections-list" class="pack-sections">
                                <!-- Sections will be added here -->
                            </div>
                            
                            <div style="margin-top: 20px;">
                                <button type="button" id="btn-save-pack" class="btn btn-primary">Save Pack</button>
                                <button type="button" id="btn-cancel-edit" class="btn btn-secondary">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Minimal Scripts -->
    <script>
        // Set global config
        window.BTT = {
            baseUrl: "<?php echo BASE_URL; ?>",
            apiUrl: "/BTT/ajax-handler.php",
            userId: "<?php echo $user_id; ?>"
        };
        
        // Simple tab switching
        document.querySelectorAll('.pack-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const view = this.dataset.view;
                
                // Update tabs
                document.querySelectorAll('.pack-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Update views
                document.querySelectorAll('.pack-view').forEach(v => v.style.display = 'none');
                const targetView = document.querySelector(`#view-${view}`);
                if (targetView) targetView.style.display = 'block';
            });
        });
        
        // Load packs on page load
        async function loadPacks() {
            try {
                const response = await fetch('/BTT/ajax-handler.php?route=backpacks', {
                    credentials: 'include'
                });
                const packs = await response.json();
                
                const loading = document.getElementById('packs-loading');
                const empty = document.getElementById('packs-empty');
                const grid = document.getElementById('packs-grid-items');
                
                loading.style.display = 'none';
                
                if (!packs || packs.length === 0) {
                    empty.style.display = 'block';
                    grid.style.display = 'none';
                } else {
                    empty.style.display = 'none';
                    grid.style.display = 'grid';
                    grid.innerHTML = packs.map(pack => `
                        <div class="pack-card">
                            <div class="pack-card-header">
                                <h3 class="pack-card-title">${pack.name || 'Unnamed Pack'}</h3>
                                <p class="pack-card-description">${pack.description || ''}</p>
                                <div class="pack-card-stats">
                                    <div class="pack-stat">
                                        <span class="pack-stat-icon">📦</span>
                                        <span class="pack-stat-value">${pack.total_items || 0} items</span>
                                    </div>
                                    <div class="pack-stat">
                                        <span class="pack-stat-icon">⚖️</span>
                                        <span class="pack-stat-value">${(pack.total_weight_g || 0) / 1000}kg</span>
                                    </div>
                                </div>
                            </div>
                            <div class="pack-card-actions">
                                <button class="btn btn-sm btn-secondary" onclick="editPack(${pack.id})">Edit</button>
                                <button class="btn btn-sm btn-danger" onclick="deletePack(${pack.id})">Delete</button>
                            </div>
                        </div>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading packs:', error);
                document.getElementById('packs-loading').innerHTML = 'Error loading packs. Please refresh the page.';
            }
        }
        
        function createFirstPack() {
            document.querySelector('.pack-tab[data-view="builder"]').click();
        }
        
        async function editPack(id) {
            try {
                // Switch to builder view
                document.querySelector('.pack-tab[data-view="builder"]').click();
                
                // Load pack data
                const response = await fetch(`/BTT/ajax-handler.php?route=backpacks&id=${id}`, {
                    credentials: 'include'
                });
                const pack = await response.json();
                
                if (pack && pack.id) {
                    // Fill the form
                    document.getElementById('pack-id').value = pack.id;
                    document.getElementById('pack-name').value = pack.name || '';
                    document.getElementById('pack-description').value = pack.description || '';
                    document.getElementById('pack-type').value = pack.type || 'custom';
                    document.getElementById('pack-capacity').value = pack.capacity_l || 65;
                    
                    // Update base weight display
                    const baseWeightEl = document.getElementById('pack-base-weight');
                    if (baseWeightEl) {
                        baseWeightEl.textContent = `Base Weight: ${pack.total_weight_g || 0}g`;
                    }
                    
                    // Load sections if pack-builder is initialized
                    if (window.PackBuilder && typeof window.PackBuilder.loadPackForEdit === 'function') {
                        window.PackBuilder.loadPackForEdit(id);
                    } else if (window.PackBuilderCRUD && typeof window.PackBuilderCRUD.loadPackForEdit === 'function') {
                        window.PackBuilderCRUD.loadPackForEdit(id);
                    }
                } else {
                    alert('Pack not found');
                }
            } catch (error) {
                console.error('Error loading pack:', error);
                alert('Error loading pack for editing');
            }
        }
        
        async function deletePack(id) {
            if (!confirm('Are you sure you want to delete this pack?')) return;
            
            try {
                const response = await fetch(`/BTT/ajax-handler.php?route=backpacks&id=${id}`, {
                    method: 'DELETE',
                    credentials: 'include'
                });
                const result = await response.json();
                if (result.success) {
                    loadPacks(); // Reload
                } else {
                    alert('Error deleting pack');
                }
            } catch (error) {
                console.error('Error deleting pack:', error);
                alert('Error deleting pack');
            }
        }
        
        // Load packs when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadPacks();
        });
        
        // New pack button
        document.getElementById('btn-new-pack').addEventListener('click', function() {
            document.querySelector('.pack-tab[data-view="builder"]').click();
        });
    </script>
    
    <!-- Load jQuery first (required by pack-builder scripts) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Load pack builder scripts after jQuery -->
    <script src="<?php echo asset_url('js/pack-builder.js'); ?>?v=<?php echo time(); ?>"></script>
    <script src="<?php echo asset_url('js/pack-builder-crud.js'); ?>?v=<?php echo time(); ?>"></script>
</body>
</html>