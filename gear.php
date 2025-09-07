<?php
/**
 * My Gear Page
 * Manage personal gear inventory with full CRUD operations
 * 
 * @package BeyondTrailTales
 * @version 1.0.0
 */

// Load bootstrap first
require_once __DIR__ . '/app/bootstrap.php';

// Require authentication
require_auth();

// Page metadata for SEO and accessibility
$pageId = 'gear';
$pageTitle = 'Trail Gear Library';
$pageDescription = 'Manage your ultralight gear inventory with smart categorization and weight tracking';

// Add page-specific scripts and CSS
$pageScripts = $pageScripts ?? [];
$pageScripts[] = 'js/save-animation.js'; // Save animation module
$pageScripts[] = 'js/gear-page.js';
$pageScripts[] = 'js/gear-library-enhanced.js';
$pageScripts[] = 'js/gear-forest-enhancements.js';

$pageStyles = $pageStyles ?? [];
$pageStyles[] = 'css/unified-page-headers.css'; // Unified header styles
$pageStyles[] = 'css/forest-duo-master.css';
$pageStyles[] = 'css/duolingo-forest-master.css';
$pageStyles[] = 'css/gear-ux-refined.css';
$pageStyles[] = 'css/gear-spacing-fixes.css'; // Fix spacing issues
$pageStyles[] = 'css/gear-color-system.css'; // Color coordination system
$pageStyles[] = 'css/save-animation.css'; // Save animation styles
$pageStyles[] = 'css/gear-title-fix.css'; // Title readability fixes
$pageStyles[] = 'css/gear-complete-cleanup.css'; // Complete UI cleanup
$pageStyles[] = 'css/gear-final-refinements.css'; // Final UI refinements
$pageStyles[] = 'css/gear-grid-fix.css'; // Grid display fixes
$pageStyles[] = 'css/gear-mobile-nav-fix.css'; // Fix mobile nav on desktop
$pageStyles[] = 'css/gear-page-input-fixes.css'; // Fix search inputs and text readability

// Include Database class
require_once __DIR__ . '/api/classes/Database.php';

// Get database connection and check user
$db = Database::getInstance();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect_to_login();
}
$user_id = $_SESSION['user_id'];

// Fetch gear statistics from database
try {
    // fetchOne returns array, get first value
    $total_count = $db->fetchOne("SELECT COUNT(*) as count FROM user_gear WHERE user_id = ? AND deleted_at IS NULL", [$user_id]);
    $total_weight = $db->fetchOne("SELECT COALESCE(SUM(weight_g), 0) as weight FROM user_gear WHERE user_id = ? AND deleted_at IS NULL", [$user_id]);
    $categories_count = $db->fetchOne("SELECT COUNT(DISTINCT category) as count FROM user_gear WHERE user_id = ? AND deleted_at IS NULL", [$user_id]);
    
    $gearStats = [
        'total_count' => $total_count ? $total_count['count'] : 0,
        'total_weight' => $total_weight ? $total_weight['weight'] : 0,
        'categories_count' => $categories_count ? $categories_count['count'] : 0
    ];
} catch (Exception $e) {
    error_log("Gear stats fetch error: " . $e->getMessage());
    $gearStats = ['total_count' => 0, 'total_weight' => 0, 'categories_count' => 0];
}

// Include the unified template header
require_once __DIR__ . '/includes/template-header.php';
?>

<!-- Forest Duo Gear Library Page -->
<div class="forest-duo-theme gear-forest-page gear-page">
  
  <!-- Unified Page Header -->
  <div class="page-hero">
    <div class="hero-content">
      <div class="hero-main-row">
        <div class="hero-header">
          <div class="hero-title-section">
            <h1 class="hero-title">
              <span class="hero-title-icon">🎒</span>
              Trail Gear Library
            </h1>
            <p class="hero-subtitle">Manage your ultralight gear • Track every gram</p>
          </div>
          
          <div class="hero-stats">
            <div class="hero-stat">
              <span class="hero-stat-value"><?= number_format($gearStats['total_count']) ?></span>
              <span class="hero-stat-label">Items</span>
            </div>
            <div class="hero-stat">
              <span class="hero-stat-value"><?= number_format($gearStats['total_weight']) ?>g</span>
              <span class="hero-stat-label">Weight</span>
            </div>
            <div class="hero-stat">
              <span class="hero-stat-value"><?= number_format($gearStats['categories_count']) ?></span>
              <span class="hero-stat-label">Categories</span>
            </div>
          </div>
        </div>
        
        <div class="hero-controls">
          <div class="hero-search">
            <input type="search" class="search-input" placeholder="Search gear..." id="gear-search-hero" aria-label="Search gear">
          </div>
          
          <select class="sort-select" id="sort-hero" aria-label="Sort gear">
            <option value="name">Name</option>
            <option value="weight-asc">Light→Heavy</option>
            <option value="weight-desc">Heavy→Light</option>
            <option value="recent">Recent</option>
          </select>
          
          <div class="view-toggle">
            <button class="view-toggle-btn active" data-view="grid" aria-label="Grid view">⊞</button>
            <button class="view-toggle-btn" data-view="list" aria-label="List view">☰</button>
          </div>
          
          <button id="btn-add-gear-hero" class="btn btn-add-gear" onclick="GearManager.showAddModal()" aria-label="Add new gear item">
            <span class="btn-icon">➕</span>
            <span class="btn-text">Add Gear</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Two-Column Layout Container -->
  <div class="gear-layout-two-column forest-gear-container" id="main-content">
    
    <!-- Left Sidebar - Filters and Controls -->
    <div class="gear-sidebar forest-sidebar">
      <!-- Enhanced Action Bar with Trail Focus -->
      <div class="gear-action-bar forest-action-bar">
        <div class="gear-header-main">
          <div class="gear-title-section">
            <h2 class="gear-view-title">
              <span class="title-icon">🏔️</span>
              Trail Arsenal
            </h2>
            <div class="gear-stats-summary">
              <div class="stat-chip">
                <span class="stat-icon">📊</span>
                <span class="stat-value" id="total-gear-count"><?= number_format($gearStats['total_count']) ?></span>
                <span class="stat-label">Items</span>
              </div>
              <div class="stat-chip">
                <span class="stat-icon">⚖️</span>
                <span class="stat-value" id="total-gear-weight"><?= number_format($gearStats['total_weight']) ?>g</span>
                <span class="stat-label">Total</span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="gear-actions forest-actions">
          <!-- Trail-focused Search -->
        <div class="search-bar forest-search">
          <span class="search-icon">🔍</span>
          <input type="search" placeholder="Find gear, brands, or categories..." id="gear-search-main" aria-label="Search gear">
          <div class="search-suggestions" id="gear-search-suggestions"></div>
        </div>
        
        <!-- Gear Library Tabs -->
        <div class="gear-tabs forest-tabs">
          <button class="gear-tab active" data-tab="all" role="tab" aria-selected="true">
            <span class="tab-icon">🌟</span>
            <span class="tab-text">All Gear</span>
            <span class="tab-count" id="all-count">0</span>
          </button>
          <button class="gear-tab" data-tab="custom" role="tab" aria-selected="false">
            <span class="tab-icon">🎒</span>
            <span class="tab-text">My Custom Gear</span>
            <span class="tab-count" id="custom-count">0</span>
          </button>
          <button class="gear-tab" data-tab="default" role="tab" aria-selected="false">
            <span class="tab-icon">📚</span>
            <span class="tab-text">Default Library</span>
            <span class="tab-count" id="default-count">0</span>
          </button>
        </div>

        <!-- Quick Category Filters -->
        <div class="quick-filters">
          <button class="filter-chip active" data-category="all">
            <span class="chip-icon">🌟</span>
            <span class="chip-text">All Categories</span>
          </button>
          <button class="filter-chip" data-category="shelter">
            <span class="chip-icon">⛺</span>
            <span class="chip-text">Shelter</span>
          </button>
          <button class="filter-chip" data-category="sleep">
            <span class="chip-icon">🛌</span>
            <span class="chip-text">Sleep</span>
          </button>
          <button class="filter-chip" data-category="cooking">
            <span class="chip-icon">🔥</span>
            <span class="chip-text">Cooking</span>
          </button>
          <button class="filter-chip" data-category="water">
            <span class="chip-icon">💧</span>
            <span class="chip-text">Water</span>
          </button>
          <button class="filter-chip" data-category="clothing">
            <span class="chip-icon">👕</span>
            <span class="chip-text">Clothing</span>
          </button>
          <button class="filter-chip" data-category="footwear">
            <span class="chip-icon">🥾</span>
            <span class="chip-text">Footwear</span>
          </button>
          <button class="filter-chip" data-category="navigation">
            <span class="chip-icon">🧭</span>
            <span class="chip-text">Navigation</span>
          </button>
          <button class="filter-chip" data-category="first-aid">
            <span class="chip-icon">🏥</span>
            <span class="chip-text">First Aid</span>
          </button>
          <button class="filter-chip" data-category="electronics">
            <span class="chip-icon">📱</span>
            <span class="chip-text">Electronics</span>
          </button>
          <button class="filter-chip" data-category="tools">
            <span class="chip-icon">🔧</span>
            <span class="chip-text">Tools</span>
          </button>
          <button class="filter-chip" data-category="ultralight">
            <span class="chip-icon">🪶</span>
            <span class="chip-text">Ultralight</span>
          </button>
        </div>
        
        <button id="btn-add-gear" class="btn-action forest-btn-primary" aria-label="Add new gear item">
          <span class="btn-icon">⚡</span>
          <span class="btn-text">Add Gear</span>
          <div class="btn-shine"></div>
        </button>
        </div>
      </div>

      <!-- Advanced Controls Section -->
      <div class="gear-advanced-controls forest-controls">
        <!-- Display Options -->
        <div class="control-section">
          <h3 class="control-section-title">
            <span class="section-icon">👁️</span>
            Display Options
          </h3>
          
          <!-- View Mode -->
          <div class="control-group">
            <label class="control-label">View Mode:</label>
            <div class="view-mode-buttons">
              <button class="view-mode-btn active" data-view="grid" aria-label="Grid view">
                <span class="view-icon">⊞</span>
              </button>
              <button class="view-mode-btn" data-view="list" aria-label="List view">
                <span class="view-icon">☰</span>
              </button>
              <button class="view-mode-btn" data-view="compact" aria-label="Compact view">
                <span class="view-icon">▦</span>
              </button>
            </div>
          </div>

          <!-- Density -->
          <div class="control-group">
            <label for="density-select" class="control-label">Density:</label>
            <select id="density-select" class="forest-select" aria-label="Display density">
              <option value="comfortable">Comfortable</option>
              <option value="compact">Compact</option>
              <option value="ultra-compact">Ultra Compact</option>
            </select>
          </div>
        </div>

        <!-- Sort & Filter Section -->
        <div class="control-section">
          <h3 class="control-section-title">
            <span class="section-icon">🔧</span>
            Sort & Filter
          </h3>
          
          <!-- Sort Options -->
          <div class="control-group">
            <label for="sort-gear" class="control-label">Sort by:</label>
            <select id="sort-gear" class="forest-select" aria-label="Sort gear items">
              <option value="name">📝 Name (A-Z)</option>
              <option value="category">🏷️ Category</option>
              <option value="weight-asc">⚖️ Weight (Light to Heavy)</option>
              <option value="weight-desc">⚖️ Weight (Heavy to Light)</option>
              <option value="recent">🆕 Recently Added</option>
              <option value="essential">⭐ Essential First</option>
            </select>
          </div>

          <!-- Weight Unit Toggle -->
          <div class="control-group">
            <label for="weight-unit" class="control-label">Units:</label>
            <select id="weight-unit" class="forest-select" aria-label="Weight display units">
              <option value="grams">Grams</option>
              <option value="ounces">Ounces</option>
              <option value="pounds">Pounds</option>
            </select>
          </div>
        </div>

        <!-- Results Status -->
        <div class="results-status" role="status" aria-live="polite" aria-atomic="true">
          <span id="result-count">Loading gear...</span>
        </div>
      </div>
    </div>

    <!-- Right Main Content Area -->
    <div class="gear-main-content forest-main-content">
      
      <!-- Gear Items Container -->
      <div class="gear-items-container" id="gear-items" role="region" aria-label="Gear items list">
      
      <!-- Loading State -->
      <div class="loading-spinner" id="gear-loading">
        <div class="spinner"></div>
        <p>Loading your gear library...</p>
      </div>

      <!-- Empty State (hidden by default) -->
      <div class="packs-empty-state" id="gear-empty" style="display: none;">
        <div class="packs-empty-icon">🎒</div>
        <div class="packs-empty-text">No gear items yet</div>
        <div class="packs-empty-subtext">Start building your gear library to track weights and organize your equipment</div>
        <button class="btn-action" onclick="GearManager.showAddModal()">
          <i>➕</i> Add Your First Item
        </button>
      </div>

      <!-- No Results State (hidden by default) -->
      <div class="packs-empty-state" id="gear-no-results" style="display: none;">
        <div class="packs-empty-icon">🔍</div>
        <div class="packs-empty-text">No matching gear found</div>
        <div class="packs-empty-subtext">Try adjusting your filters or search terms</div>
        <button class="btn-secondary" onclick="GearManager.clearFilters()">
          Clear Filters
        </button>
      </div>

        <!-- Gear Grid/List (populated by JavaScript) -->
        <div class="gear-grid" id="gear-grid" style="display: none;">
          <!-- Gear cards will be inserted here by JavaScript -->
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add/Edit Gear Modal -->
<div class="modal" id="gear-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title" style="display: none;">
  <div class="modal-backdrop" onclick="GearManager.closeModal()"></div>
  <div class="modal-content">
    <div class="modal-header">
      <h2 id="modal-title">Add Gear Item</h2>
      <button class="modal-close" onclick="GearManager.closeModal()" aria-label="Close dialog">
        <span aria-hidden="true">×</span>
      </button>
    </div>
    
    <form id="gear-form" class="modal-body" novalidate>
      <input type="hidden" id="gear-id" name="id">
      
      <!-- Name Field -->
      <div class="form-group">
        <label for="gear-name" class="form-label">
          Name <span class="required" aria-label="required">*</span>
        </label>
        <input 
          type="text" 
          id="gear-name" 
          name="name" 
          class="form-control" 
          required
          maxlength="100"
          aria-describedby="name-error"
        >
        <div class="form-error" id="name-error" role="alert"></div>
      </div>

      <!-- Category Field -->
      <div class="form-group">
        <label for="gear-category" class="form-label">
          Category <span class="required" aria-label="required">*</span>
        </label>
        <select 
          id="gear-category" 
          name="category" 
          class="form-control" 
          required
          aria-describedby="category-error"
        >
          <option value="">Select category...</option>
          <optgroup label="⛺ Core Systems">
            <option value="shelter">Shelter</option>
            <option value="sleep">Sleep System</option>
            <option value="cooking">Cooking</option>
            <option value="water">Water & Hydration</option>
          </optgroup>
          <optgroup label="👕 Clothing & Protection">
            <option value="clothing">Clothing</option>
            <option value="footwear">Footwear</option>
            <option value="rain-gear">Rain Gear</option>
          </optgroup>
          <optgroup label="🧭 Navigation & Safety">
            <option value="navigation">Navigation</option>
            <option value="first-aid">First Aid</option>
            <option value="emergency">Emergency</option>
          </optgroup>
          <optgroup label="📱 Electronics & Tools">
            <option value="electronics">Electronics</option>
            <option value="tools">Tools</option>
            <option value="repair">Repair</option>
          </optgroup>
          <optgroup label="🧼 Personal Care">
            <option value="hygiene">Hygiene</option>
            <option value="food-storage">Food Storage</option>
            <option value="other">Other</option>
          </optgroup>
        </select>
        <div class="form-error" id="category-error" role="alert"></div>
      </div>

      <!-- Weight Field -->
      <div class="form-row">
        <div class="form-group">
          <label for="gear-weight" class="form-label">
            Weight <span class="required" aria-label="required">*</span>
          </label>
          <input 
            type="number" 
            id="gear-weight" 
            name="weight" 
            class="form-control" 
            required
            min="0"
            step="0.01"
            aria-describedby="weight-error"
          >
          <div class="form-error" id="weight-error" role="alert"></div>
        </div>
        
        <div class="form-group">
          <label for="gear-weight-unit" class="form-label">Unit</label>
          <select id="gear-weight-unit" name="weight_unit" class="form-control">
            <option value="grams">Grams</option>
            <option value="ounces">Ounces</option>
            <option value="pounds">Pounds</option>
          </select>
        </div>
      </div>

      <!-- Tags Field -->
      <div class="form-group">
        <label for="gear-tags" class="form-label">
          Tags <small class="form-hint">(comma-separated)</small>
        </label>
        <input 
          type="text" 
          id="gear-tags" 
          name="tags" 
          class="form-control" 
          placeholder="e.g., ultralight, waterproof, essential"
          aria-describedby="tags-help"
        >
        <small id="tags-help" class="form-text">Separate multiple tags with commas</small>
      </div>

      <!-- Notes Field -->
      <div class="form-group">
        <label for="gear-notes" class="form-label">Notes</label>
        <textarea 
          id="gear-notes" 
          name="notes" 
          class="form-control" 
          rows="3"
          maxlength="500"
          placeholder="Additional details, brand, model, etc."
        ></textarea>
      </div>
    </form>
    
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="GearManager.closeModal()">
        Cancel
      </button>
      <button type="submit" form="gear-form" class="btn btn-primary">
        <span id="submit-text">Add Item</span>
      </button>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="delete-modal" role="dialog" aria-modal="true" aria-labelledby="delete-title" style="display: none;">
  <div class="modal-backdrop" onclick="GearManager.closeDeleteModal()"></div>
  <div class="modal-content modal-small">
    <div class="modal-header">
      <h2 id="delete-title">Confirm Delete</h2>
    </div>
    
    <div class="modal-body">
      <p id="delete-message">Are you sure you want to delete this gear item?</p>
      <p class="text-warning"><strong id="delete-item-name"></strong></p>
    </div>
    
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="GearManager.closeDeleteModal()" autofocus>
        Cancel
      </button>
      <button type="button" class="btn btn-danger" onclick="GearManager.confirmDelete()">
        Delete Item
      </button>
    </div>
  </div>
</div>

<!-- Success/Error Toast Notifications -->
<div id="toast-container" class="toast-container" aria-live="polite" aria-atomic="true">
  <!-- Toasts will be dynamically inserted here -->
</div>

<!-- Initialize Gear Manager -->
<script>
  // Initialize configuration
  window.GearConfig = {
    apiUrl: '<?php echo route_url("api"); ?>',
    csrfToken: '<?php echo csrf_token(); ?>'
  };

  // Initialize GearManager when DOM is loaded
  document.addEventListener('DOMContentLoaded', function() {
    if (window.GearManager) {
      console.log('Initializing GearManager...');
      // Update API configuration for AJAX handler
      GearManager.config.apiUrl = '<?php echo route_url(""); ?>/ajax-handler.php';
      GearManager.init();
      
      // Connect hero controls to existing functionality
      const heroSearch = document.getElementById('gear-search-hero');
      const mainSearch = document.getElementById('gear-search-main');
      if (heroSearch && mainSearch) {
        heroSearch.addEventListener('input', function() {
          mainSearch.value = this.value;
          mainSearch.dispatchEvent(new Event('input'));
        });
      }
      
      const heroSort = document.getElementById('sort-hero');
      const mainSort = document.getElementById('sort-gear');
      if (heroSort && mainSort) {
        heroSort.addEventListener('change', function() {
          mainSort.value = this.value;
          mainSort.dispatchEvent(new Event('change'));
        });
      }
      
      // Connect view toggle buttons
      document.querySelectorAll('.view-toggle-btn').forEach(btn => {
        btn.addEventListener('click', function() {
          const view = this.dataset.view;
          document.querySelectorAll('.view-mode-btn').forEach(modeBtn => {
            if (modeBtn.dataset.view === view) {
              modeBtn.click();
            }
          });
          document.querySelectorAll('.view-toggle-btn').forEach(toggleBtn => {
            toggleBtn.classList.remove('active');
          });
          this.classList.add('active');
        });
      });
    } else {
      console.error('GearManager not found - check that gear-page.js is loaded');
    }
  });
</script>

<?php
// Include the unified template footer
require_once __DIR__ . '/includes/template-footer.php';
?>
