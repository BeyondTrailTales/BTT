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

// Add essential page scripts - Clean modular approach
$pageScripts = $pageScripts ?? [];
// Core API - unified and clean
$pageScripts[] = 'js/btt-api-unified.js'; // Unified API client
// Pack management module
$pageScripts[] = 'js/modules/pack-manager.js'; // Clean pack management
// Optional: Save animation
$pageScripts[] = 'js/save-animation.js'; // Save animation module

$pageStyles = $pageStyles ?? [];
// Force cache refresh with timestamp
$cacheTime = time();
$pageStyles[] = 'css/unified-page-headers.css?v=' . $cacheTime; // Unified header styles
$pageStyles[] = 'css/backpacks-clean.css?v=' . $cacheTime;
$pageStyles[] = 'css/pack-toast.css?v=' . $cacheTime;
// Add minimal builder styles for isolated builder tab
$pageStyles[] = 'css/forest-drag-zones.css?v=' . $cacheTime;
$pageStyles[] = 'css/gear-color-system.css?v=' . $cacheTime; // Color coordination system
$pageStyles[] = 'css/button-resize-fix.css?v=' . $cacheTime; // Fix button resize on hover
$pageStyles[] = 'css/save-animation.css?v=' . $cacheTime; // Save animation styles
$pageStyles[] = 'css/packed-items-panel.css?v=' . $cacheTime; // Packed items overview panel

// Add backpacks page body class
$bodyClasses = $bodyClasses ?? [];
$bodyClasses[] = 'backpacks-page';
$bodyClasses[] = 'backpacks-forest-page';

// Get database connection and check user
$db = Database::getInstance();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect_to_login();
}
$user_id = $_SESSION['user_id'];

// Fetch pack statistics from database
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

// Include the unified template header
require_once __DIR__ . '/includes/template-header.php';
?>

<!-- Backpacks Page Header -->
<div class="page-hero">
  <div class="hero-content">
    <div class="hero-main-row">
      <div class="hero-header">
        <div class="hero-title-section">
          <h1 class="hero-title">
            <span class="hero-title-icon">🎒</span>
            My Backpacks
          </h1>
          <p class="hero-subtitle">Organize gear and optimize your loadout</p>
        </div>
        
        <div class="hero-stats">
          <div class="hero-stat">
            <span class="hero-stat-value"><?php echo $packStats['total_count']; ?></span>
            <span class="hero-stat-label">Packs</span>
          </div>
          <div class="hero-stat">
            <span class="hero-stat-value"><?php echo $packStats['avg_weight'] ? round($packStats['avg_weight']) . 'g' : '—'; ?></span>
            <span class="hero-stat-label">Avg Weight</span>
          </div>
        </div>
      </div>
      
      <div class="hero-controls">
        <div class="hero-search">
          <input type="search" class="search-input" placeholder="Search packs..." id="pack-search-hero" aria-label="Search packs">
        </div>
        
        <select class="sort-select" id="sort-hero" aria-label="Sort packs">
          <option value="recent">Recent</option>
          <option value="name">Name</option>
          <option value="weight">Weight</option>
        </select>
        
        <div class="view-toggle">
          <button class="view-toggle-btn active" data-view="grid" aria-label="Grid view">⊞</button>
          <button class="view-toggle-btn" data-view="list" aria-label="List view">☰</button>
        </div>
        
        <button class="btn btn-create" id="createNewPackBtn">
          <span class="btn-icon">✨</span>
          <span>Create Pack</span>
        </button>
      </div>
    </div>
  </div>
</div>

  <!-- Clean Main Container -->
  <div class="dashboard" id="main-content">
    
    <!-- Clean Header Section -->
    <div class="card" style="grid-column: 1 / -1; margin-bottom: var(--space-lg); display: none;">
      <div class="card-header">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: var(--space-lg);">
          <div>
            <h1 class="card-title">🎒 My Backpacks</h1>
            <p class="card-subtitle">Organize your gear and optimize your loadout</p>
          </div>
          <div class="stats-grid" style="min-width: 200px;">
            <div class="stat" style="padding: var(--space-md);">
              <span class="stat-number"><?= $packStats['total_count'] ?></span>
              <span class="stat-label">Packs</span>
            </div>
            <div class="stat" style="padding: var(--space-md);">
              <span class="stat-number"><?= $packStats['avg_weight'] > 0 ? number_format($packStats['avg_weight'] / 1000, 1) . 'kg' : '0kg' ?></span>
              <span class="stat-label">Avg Weight</span>
            </div>
          </div>
        </div>
      </div>
      <div class="card-body">
        <!-- Clean Navigation Tabs -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-md);">
          <div class="view-switcher" style="display: flex; gap: var(--space-sm); background: var(--glass-subtle); padding: var(--space-xs); border-radius: var(--radius-lg);">
            <button class="btn btn-sm pack-tab active" data-view="my-packs">
              🎒 My Packs
            </button>
            <button class="btn btn-sm btn-ghost pack-tab" data-view="templates">
              📚 Templates
            </button>
            <button class="btn btn-sm btn-ghost pack-tab" data-view="builder">
              ⚡ Builder
            </button>
          </div>
          
          <div style="display: flex; gap: var(--space-md); align-items: center;">
            <!-- Clean Search -->
            <div style="position: relative; min-width: 200px;">
              <input type="search" placeholder="Search packs..." id="pack-search-main" 
                     style="width: 100%; padding: var(--space-sm) var(--space-md) var(--space-sm) var(--space-xl); 
                            border: 1px solid var(--glass-border); border-radius: var(--radius-lg); 
                            background: var(--glass-subtle); color: var(--text-primary); font-size: var(--text-sm);">
              <span style="position: absolute; left: var(--space-md); top: 50%; transform: translateY(-50%); opacity: 0.5;">🔍</span>
              <div class="search-suggestions" id="pack-search-suggestions"></div>
            </div>
            
            <a href="/BTT/pack-builder.php" class="btn btn-primary">
              ➕ Create Pack
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Clean Views Container -->
    <div id="pack-views-container" style="grid-column: 1 / -1;">
      
      <!-- My Packs View -->
      <div class="pack-view active" id="view-my-packs" data-view="my-packs">
        <!-- Clean Packs Grid -->
        <div id="packs-grid">
          <!-- Loading State -->
          <div class="card" id="packs-loading">
            <div class="card-body" style="text-align: center; padding: var(--space-3xl);">
              <div style="width: 40px; height: 40px; margin: 0 auto var(--space-lg); border: 3px solid var(--glass-border); border-top: 3px solid var(--pack-primary); border-radius: 50%; animation: spin 1s linear infinite;"></div>
              <p>Loading your packs...</p>
            </div>
          </div>
          
          <!-- Empty State -->
          <div class="empty-state card" id="packs-empty" style="display: none;">
            <div class="card-body">
              <div class="empty-state-icon">🎒</div>
              <h3 class="empty-state-title">No packs created yet</h3>
              <p class="empty-state-description">Start building your first pack to organize your gear and optimize your loadout</p>
              <div class="empty-state-actions">
                <a href="/BTT/pack-builder.php" class="btn btn-primary">
                  ➕ Create Your First Pack
                </a>
              </div>
            </div>
          </div>
          
          <!-- Packs Grid -->
          <div class="dashboard" id="packs-grid-items" style="margin-top: 0;"></div>
        </div>
      </div>
      
      <!-- Templates View -->
      <div class="pack-view" id="view-templates" data-view="templates" style="display: none;">
        <div class="templates-section">
          <h3 class="section-title">🏔️ Trail-Ready Templates</h3>
          <div class="templates-grid">
            <div class="template-card forest-card">
              <div class="template-header">
                <span class="template-icon">🥾</span>
                <h4>Day Hike Essentials</h4>
              </div>
              <div class="template-stats">
                <span class="template-weight">~2.5kg base weight</span>
                <span class="template-items">12 essential items</span>
              </div>
              <div class="template-description">Perfect for day adventures with safety essentials</div>
              <button class="btn-secondary template-use-btn" data-template="day-hike">Use Template</button>
            </div>
            
            <div class="template-card forest-card">
              <div class="template-header">
                <span class="template-icon">🏕️</span>
                <h4>Weekend Backpacking</h4>
              </div>
              <div class="template-stats">
                <span class="template-weight">~4.5kg base weight</span>
                <span class="template-items">25 items</span>
              </div>
              <div class="template-description">2-3 day trips with shelter and cooking gear</div>
              <button class="btn-secondary template-use-btn" data-template="weekend">Use Template</button>
            </div>
            
            <div class="template-card forest-card">
              <div class="template-header">
                <span class="template-icon">🪶</span>
                <h4>Ultralight Thru-hiking</h4>
              </div>
              <div class="template-stats">
                <span class="template-weight">~3.2kg base weight</span>
                <span class="template-items">18 optimized items</span>
              </div>
              <div class="template-description">Long distance minimalist setup for maximum efficiency</div>
              <button class="btn-secondary template-use-btn" data-template="ultralight">Use Template</button>
            </div>
            
            <div class="template-card forest-card">
              <div class="template-header">
                <span class="template-icon">❄️</span>
                <h4>Winter/Alpine</h4>
              </div>
              <div class="template-stats">
                <span class="template-weight">~6.8kg base weight</span>
                <span class="template-items">35 items</span>
              </div>
              <div class="template-description">Cold weather and technical terrain gear</div>
              <button class="btn-secondary template-use-btn" data-template="winter">Use Template</button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Pack Builder View - Isolated -->
      <div class="pack-view" id="view-builder" data-view="builder" style="display: none;">
        <div class="builder-notice" style="background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
          <p><strong>💡 Pro Tip:</strong> For a full-screen builder experience, <a href="/BTT/pack-builder.php" class="text-blue-600">try our dedicated Pack Builder</a></p>
        </div>
        
        <div class="simple-builder">
          <div class="builder-header">
            <h3>Quick Pack Builder</h3>
            <button class="btn btn-primary" id="btn-create-new-pack">Start New Pack</button>
          </div>
          
          <div class="builder-content" id="builder-workspace" style="display: none;">
            <div class="pack-form">
              <div class="form-group">
                <label for="quick-pack-name">Pack Name *</label>
                <input type="text" id="quick-pack-name" placeholder="Weekend Hike" required>
              </div>
              <div class="form-group">
                <label for="quick-pack-type">Pack Type</label>
                <select id="quick-pack-type">
                  <option value="day-hike">Day Hike</option>
                  <option value="weekend">Weekend</option>
                  <option value="extended">Extended</option>
                </select>
              </div>
              <button class="btn btn-primary" id="btn-save-quick-pack">Create & Edit Pack</button>
            </div>
            
            <!-- Optional: Gear preview for quick builder -->
            <div class="gear-preview" style="margin-top: 2rem;">
              <h4>Preview: Your Gear Library</h4>
              <div id="gear-library" style="max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem;">
                <div class="gear-loading">Loading gear library...</div>
              </div>
              <p style="font-size: 0.875rem; color: #6b7280; margin-top: 0.5rem;">
                💡 Use the full builder to drag and drop gear items
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- New Pack Modal -->
<div class="modal hidden" id="pack-modal">
  <div class="modal-backdrop" onclick="closePackModal()"></div>
  <div class="modal-content">
    <div class="modal-header">
      <h2 id="modal-title">Create New Pack</h2>
      <button class="modal-close" onclick="closePackModal()">×</button>
    </div>
    <form class="modal-body" id="pack-form">
      <input type="hidden" id="pack-id" name="id">
      
      <div class="form-group">
        <label for="pack-name">Pack Name</label>
        <input type="text" id="pack-name" name="name" placeholder="e.g., Weekend in the Mountains" required>
      </div>
      
      <div class="form-group">
        <label for="pack-description">Description</label>
        <textarea id="pack-description" name="description" placeholder="Brief description of this pack's purpose"></textarea>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label for="pack-type">Type</label>
          <select id="pack-type" name="type">
            <option value="day-hike">Day Hike</option>
            <option value="overnight">Overnight</option>
            <option value="weekend">Weekend</option>
            <option value="extended">Extended</option>
            <option value="thru-hike">Thru-hike</option>
            <option value="custom">Custom</option>
          </select>
        </div>
        <div class="form-group">
          <label for="pack-capacity">Capacity (L)</label>
          <input type="number" id="pack-capacity" name="capacity_l" min="1" max="150" value="65">
        </div>
      </div>
    </form>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="closePackModal()">Cancel</button>
      <button class="btn-primary" onclick="savePack()">Create Pack</button>
    </div>
  </div>
</div>

<!-- Packed Items Overview Panel -->
<div id="packed-items-panel" class="packed-items-panel">
  <div class="panel-header">
    <h3>All Packed Items</h3>
    <button class="btn-icon" onclick="PackManager.togglePackedItemsPanel()">
      <span>×</span>
    </button>
  </div>
  <div class="panel-controls">
    <input type="search" id="items-search" placeholder="Search items..." class="items-search">
    <select id="items-filter" class="items-filter">
      <option value="all">All Categories</option>
      <option value="shelter">Shelter</option>
      <option value="sleep">Sleep System</option>
      <option value="cooking">Cooking</option>
      <option value="water">Water</option>
      <option value="clothing">Clothing</option>
      <option value="electronics">Electronics</option>
      <option value="first-aid">First Aid</option>
      <option value="hygiene">Hygiene</option>
      <option value="other">Other</option>
    </select>
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
      <p>No items found in your packs</p>
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

<!-- Pack Builder Modal -->
<div class="modal hidden" id="pack-builder-modal">
  <div class="modal-backdrop" onclick="PackManager.closePackBuilder()"></div>
  <div class="modal-content pack-builder-content">
    <div class="modal-header">
      <h2 id="builder-title">Pack Builder</h2>
      <button class="modal-close" onclick="PackManager.closePackBuilder()">×</button>
    </div>
    <div class="pack-builder-body">
      <!-- Pack Info -->
      <div class="pack-info-bar">
        <div class="pack-details">
          <h3 id="builder-pack-name">Pack Name</h3>
          <div class="pack-weight-summary">
            <span class="weight-label">Total Weight:</span>
            <span class="weight-value" id="builder-total-weight">0g</span>
          </div>
        </div>
        <div class="pack-actions">
          <button class="btn-secondary" onclick="PackManager.addGearToPackModal()">+ Add Gear</button>
          <button class="btn-primary" onclick="PackManager.savePackContents()">Save Pack</button>
        </div>
      </div>
      
      <!-- Pack Sections -->
      <div class="pack-sections" id="pack-sections">
        <div class="section" data-section="main">
          <h4 class="section-header">
            <span class="section-name">Main Pack</span>
            <span class="section-stats">
              <span class="section-weight">0g</span>
              <span class="section-items">0 items</span>
            </span>
          </h4>
          <div class="section-items" id="section-main">
            <div class="empty-section">
              <p>No items added yet</p>
              <button class="btn-text" onclick="PackManager.addGearToPackModal()">Add first item</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Gear Selection Modal -->
<div class="modal hidden" id="gear-selection-modal">
  <div class="modal-backdrop" onclick="PackManager.closeGearSelection()"></div>
  <div class="modal-content">
    <div class="modal-header">
      <h2>Add Gear to Pack</h2>
      <button class="modal-close" onclick="PackManager.closeGearSelection()">×</button>
    </div>
    <div class="modal-body">
      <div class="gear-search-bar">
        <input type="search" placeholder="Search gear..." id="gear-search-modal">
      </div>
      <div class="gear-categories">
        <button class="category-filter active" data-category="all">All</button>
        <button class="category-filter" data-category="shelter">Shelter</button>
        <button class="category-filter" data-category="sleep">Sleep</button>
        <button class="category-filter" data-category="cooking">Cooking</button>
        <button class="category-filter" data-category="water">Water</button>
        <button class="category-filter" data-category="clothing">Clothing</button>
      </div>
      <div class="gear-list" id="modal-gear-list">
        <div class="gear-loading">Loading gear...</div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-secondary" onclick="PackManager.closeGearSelection()">Cancel</button>
    </div>
  </div>
</div>


<?php
// Include the unified template footer
require_once __DIR__ . '/includes/template-footer.php';
?>

<script>
// Clean, simple initialization
document.addEventListener('DOMContentLoaded', function() {
    // Set user ID for global use
    window.BTT_USER_ID = <?php echo json_encode($user_id); ?>;
    
    // Tab switching functionality
    document.querySelectorAll('.pack-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const view = this.dataset.view;
            
            // Update tabs
            document.querySelectorAll('.pack-tab').forEach(t => {
                t.classList.remove('active');
                t.classList.add('btn-ghost');
            });
            this.classList.remove('btn-ghost');
            this.classList.add('active');
            
            // Update views
            document.querySelectorAll('.pack-view').forEach(v => {
                v.style.display = 'none';
            });
            const targetView = document.getElementById(`view-${view}`);
            if (targetView) {
                targetView.style.display = 'block';
            }
        });
    });
});
</script>