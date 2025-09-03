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
$pageTitle = 'My Gear';
$pageDescription = 'Manage your outdoor gear inventory with categories, tags, and weight tracking';

// Page-specific styles
$pageStyles = $pageStyles ?? [];
$pageStyles[] = 'css/pack-builder.css';         // Base layout and components shared with packs
$pageStyles[] = 'css/gear-page.css';            // Gear page specific styling

// Add page-specific scripts
$pageScripts = $pageScripts ?? [];
$pageScripts[] = 'js/gear-page.js';           // Main gear page functionality

// Include the unified template header
require_once __DIR__ . '/includes/template-header.php';
?>

<!-- Main Gear Container -->
<div class="gear-page">
<div class="pack-builder-container" id="main-content">
  
  <!-- Header Section -->
  <div class="gear-header-section">
    <div class="gear-header-content">
      <div class="gear-title-group">
        <h1 class="gear-page-title">
          <span class="page-icon" aria-hidden="true">📦</span>
          My Gear Library
        </h1>
        <p id="page-description" class="gear-page-subtitle">
          Track and organize all your outdoor equipment
        </p>
      </div>
      <button id="btn-add-gear" class="btn-action" aria-label="Add new gear item">
        <i class="icon">➕</i> Add Gear
      </button>
    </div>
  </div>

  <!-- Filters and Search Bar -->
  <div class="gear-toolbar" role="region" aria-label="Search and filter controls">
    <div class="toolbar-row">
      <!-- Search -->
      <div class="search-bar">
        <label for="gear-search" class="visually-hidden">Search gear</label>
        <span class="search-icon" aria-hidden="true">🔍</span>
        <input 
          id="gear-search" 
          type="search" 
          placeholder="Search by name, tags, or notes..." 
          aria-label="Search gear items"
          autocomplete="off"
        />
      </div>

      <!-- Category Filter -->
      <div class="filter-group">
        <label for="category-filter">Category:</label>
        <select id="category-filter" aria-label="Filter by category">
          <option value="">All Categories</option>
          <option value="shelter">Shelter</option>
          <option value="sleep">Sleep System</option>
          <option value="cooking">Cooking</option>
          <option value="clothing">Clothing</option>
          <option value="navigation">Navigation</option>
          <option value="hygiene">Hygiene</option>
          <option value="first-aid">First Aid</option>
          <option value="electronics">Electronics</option>
          <option value="water">Water</option>
          <option value="food-storage">Food Storage</option>
          <option value="repair">Repair</option>
          <option value="other">Other</option>
        </select>
      </div>

      <!-- Sort -->
      <div class="filter-group">
        <label for="sort-gear">Sort by:</label>
        <select id="sort-gear" aria-label="Sort gear items">
          <option value="name">Name (A-Z)</option>
          <option value="category">Category</option>
          <option value="weight-asc">Weight (Light to Heavy)</option>
          <option value="weight-desc">Weight (Heavy to Light)</option>
          <option value="recent">Recently Added</option>
        </select>
      </div>

      <!-- Weight Unit Toggle -->
      <div class="filter-group">
        <label for="weight-unit">Units:</label>
        <select id="weight-unit" aria-label="Weight display units">
          <option value="grams">Grams</option>
          <option value="ounces">Ounces</option>
          <option value="pounds">Pounds</option>
        </select>
      </div>
    </div>

    <!-- Results Count -->
    <div class="toolbar-status" role="status" aria-live="polite" aria-atomic="true">
      <span id="result-count">Loading gear...</span>
    </div>
  </div>

  <!-- Main Content Area -->
  <div class="pack-content">
    
    <!-- Gear Items List -->
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
          <option value="shelter">Shelter</option>
          <option value="sleep">Sleep System</option>
          <option value="cooking">Cooking</option>
          <option value="clothing">Clothing</option>
          <option value="navigation">Navigation</option>
          <option value="hygiene">Hygiene</option>
          <option value="first-aid">First Aid</option>
          <option value="electronics">Electronics</option>
          <option value="water">Water</option>
          <option value="food-storage">Food Storage</option>
          <option value="repair">Repair</option>
          <option value="other">Other</option>
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
    apiUrl: '<?php echo BTT_API_URL; ?>',
    csrfToken: '<?php echo csrf_token(); ?>'
  };
</script>

<?php
// Include the unified template footer
require_once __DIR__ . '/includes/template-footer.php';
?>
