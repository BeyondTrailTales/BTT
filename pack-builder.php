<?php
// Load bootstrap
require_once __DIR__ . '/app/bootstrap.php';

// Require authentication
require_auth();

// Include Database class
require_once __DIR__ . '/api/classes/Database.php';

// Set page metadata
$pageId = 'pack-builder';
$pageTitle = 'Pack Builder';
$pageDescription = 'Build and organize your backpack gear';

// Get pack ID from URL if editing
$packId = $_GET['id'] ?? null;
$isEdit = !empty($packId);

// Add essential page scripts and CSS - Full builder functionality
$pageScripts = $pageScripts ?? [];
// $pageScripts[] = 'vendor/jquery-ui.min.js';  // Not needed for basic drag/drop
$pageScripts[] = 'js/pack-builder-final.js?v=' . time();

$pageStyles = $pageStyles ?? [];
$cacheTime = time();
$pageStyles[] = 'css/jquery-ui.min.css?v=' . $cacheTime;
// Load only essential styles for horizontal UX redesign
$pageStyles[] = 'css/pack-toast.css?v=' . $cacheTime;
$pageStyles[] = 'css/pack-builder-simple.css?v=' . $cacheTime;
$pageStyles[] = 'css/gear-color-system.css?v=' . $cacheTime; // Color coordination system
$pageStyles[] = 'css/pack-builder-soft-forest.css?v=' . $cacheTime; // Soft forest theme - easy on the eyes
$pageStyles[] = 'css/packed-items-panel.css?v=' . $cacheTime; // Packed items panel for current pack

$bodyClasses = $bodyClasses ?? [];
$bodyClasses[] = 'pack-builder-page';
$bodyClasses[] = 'backpacks-forest-page';

// Get database connection and check user
$db = Database::getInstance()->getConnection();

if (!isset($_SESSION['user_id'])) {
    redirect_to_login();
}
$user_id = $_SESSION['user_id'];

// Load existing pack if editing
$packData = null;
if ($isEdit) {
    try {
        $stmt = $db->prepare("SELECT * FROM backpacks WHERE id = ? AND user_id = ?");
        $stmt->execute([$packId, $user_id]);
        $packData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$packData) {
            header('Location: /BTT/backpacks.php?error=pack_not_found');
            exit;
        }
    } catch (Exception $e) {
        error_log("Error loading pack: " . $e->getMessage());
        header('Location: /BTT/backpacks.php?error=load_failed');
        exit;
    }
}

require_once __DIR__ . '/includes/template-header.php';
?>

<div class="pack-builder-container">
    <!-- Header -->
    <div class="builder-header">
        <a href="/BTT/backpacks.php" class="btn btn-secondary">← Back to Packs</a>
        <h1><?= $isEdit ? 'Edit Pack: ' . htmlspecialchars($packData['name']) : '🎒 Simple Pack Builder' ?></h1>
        <div class="header-actions">
            <button id="view-all-items-btn" class="btn btn-secondary" onclick="PackBuilder.toggleItemsPanel()">📦 View All Items</button>
            <button id="save-pack-btn" class="btn btn-primary">💾 Save Pack</button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Left Side: Gear Browser -->
        <div class="gear-browser">
            <h2>🛍️ Add Gear to Pack</h2>
            
            <!-- Simple Search -->
            <div class="gear-search">
                <input type="text" id="gear-search" placeholder="Search for gear...">
                <span class="search-icon">🔍</span>
            </div>
            
            <!-- Category Filters -->
            <div class="gear-categories">
                <button class="category-btn active" data-category="all">All</button>
                <button class="category-btn" data-category="shelter">🏕️ Shelter</button>
                <button class="category-btn" data-category="sleep">🛌 Sleep</button>
                <button class="category-btn" data-category="cooking">🍳 Cook</button>
                <button class="category-btn" data-category="water">💧 Water</button>
                <button class="category-btn" data-category="clothing">👕 Clothes</button>
                <button class="category-btn" data-category="safety">🚨 Safety</button>
                <button class="category-btn" data-category="other">📦 Other</button>
            </div>
            
            <!-- Gear List -->
            <div class="gear-list" id="gear-list">
                <div class="loading">Loading your gear...</div>
            </div>
        </div>
        
        <!-- Right Side: Pack View -->
        <div class="pack-view">
            <!-- Pack Header with Weight Summary -->
            <div class="pack-header">
                <h2 class="pack-name"><?= $isEdit ? htmlspecialchars($packData['name']) : 'New Pack' ?></h2>
                
                <!-- Weight Breakdown Summary -->
                <div class="weight-summary">
                    <div class="weight-stats">
                        <div class="weight-stat">
                            <div class="weight-stat-icon">🎒</div>
                            <div class="weight-stat-label">Base Weight</div>
                            <div class="weight-stat-value base-weight">0g</div>
                        </div>
                        <div class="weight-stat">
                            <div class="weight-stat-icon">👕</div>
                            <div class="weight-stat-label">Worn Weight</div>
                            <div class="weight-stat-value worn-weight">0g</div>
                        </div>
                        <div class="weight-stat">
                            <div class="weight-stat-icon">🍎</div>
                            <div class="weight-stat-label">Consumables</div>
                            <div class="weight-stat-value consumable-weight">0g</div>
                        </div>
                        <div class="weight-stat">
                            <div class="weight-stat-icon">📦</div>
                            <div class="weight-stat-label">Items Count</div>
                            <div class="weight-stat-value total-items">0</div>
                        </div>
                    </div>
                    <div class="weight-stat total">
                        <div class="weight-stat-label">Total Pack Weight</div>
                        <div class="weight-stat-value total-weight">0g</div>
                    </div>
                </div>
            </div>
            
            <!-- Pack Sections -->
            <div class="pack-sections" id="pack-sections">
                <!-- Sections will be rendered here by JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Packed Items Overview Panel -->
<div id="packed-items-panel" class="packed-items-panel">
  <div class="panel-header">
    <h3>Pack Items Overview</h3>
    <button class="btn-icon" onclick="PackBuilder.toggleItemsPanel()">
      <span>×</span>
    </button>
  </div>
  <div class="panel-controls">
    <input type="search" id="items-search" placeholder="Search items..." class="items-search">
    <div class="panel-controls-row">
      <select id="items-filter" class="items-filter">
        <option value="all">All Sections</option>
        <option value="main">Main Compartment</option>
        <option value="lid">Top Lid</option>
        <option value="pockets">Side Pockets</option>
        <option value="external">External</option>
      </select>
      <button class="btn-refresh" onclick="PackBuilder.refreshItemsPanel()" title="Refresh items list">
        <span class="refresh-icon">↻</span>
        <span>Refresh</span>
      </button>
    </div>
  </div>
  <div class="panel-content">
    <div id="items-loading" class="items-loading">
      <div class="spinner"></div>
      <p>Loading items...</p>
    </div>
    <div id="items-list" class="items-list" style="display: none;">
      <!-- Items will be loaded here -->
    </div>
    <div id="items-empty" class="items-empty" style="display: none;">
      <p>No items in this pack yet</p>
    </div>
  </div>
  <div class="panel-footer">
    <div class="items-summary">
      <span class="summary-stat">
        <strong id="total-items">0</strong> items
      </span>
      <span class="summary-stat">
        <strong id="total-weight">0g</strong> total
      </span>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/template-footer.php'; ?>

<script>
// Set global BTT configuration
window.BTT = {
    baseUrl: "<?php echo BASE_URL; ?>",
    apiUrl: "<?php echo BASE_URL; ?>/ajax-handler.php",
    assetsUrl: "<?php echo BTT_ASSETS_URL; ?>",
    publicUrl: "<?php echo BTT_PUBLIC_URL; ?>",
    userId: "<?php echo $user_id; ?>",
    csrfToken: "<?php echo csrf_token(); ?>",
    debug: <?php echo BTT_DEBUG ? 'true' : 'false'; ?>
};

// Set user ID and pack data for Simple Pack Builder
window.BTT_USER_ID = <?php echo json_encode($user_id); ?>;
<?php if ($isEdit): ?>
window.BTT_PACK_DATA = <?= json_encode($packData) ?>;
<?php endif; ?>
</script>