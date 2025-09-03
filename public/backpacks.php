<?php
// Load bootstrap
require_once dirname(__DIR__) . '/app/bootstrap.php';

// Include card components
require_once __DIR__ . '/includes/components/backpack-card.php';

// Set page metadata
$pageId = 'backpacks-inline';
$pageTitle = 'Pack Builder';
$pageDescription = 'Build and manage your backpack configurations with our intuitive pack builder';

// Include jQuery and Bootstrap in page styles
$pageStyles = [
    'css/pack-builder.css', 
    'css/pack-builder-enhanced.css',
    'css/pack-builder-dnd.css',  // Enhanced drag-and-drop styles
    'css/gear-library.css',  // Base gear library styles
    'css/gear-library-enhanced.css'  // Enhanced gear library filters
];

// Include the unified template header
require_once __DIR__ . '/includes/template-header.php';
?>

<!-- Pack Builder Main Container -->
<div class="pack-builder-container">
    
    <!-- Top Action Bar -->
    <div class="pack-action-bar">
        <div class="pack-tabs">
            <button class="pack-tab active" data-view="my-packs">
                <i class="icon">🎒</i> My Packs
            </button>
            <button class="pack-tab" data-view="builder">
                <i class="icon">🔧</i> Pack Builder
            </button>
            <button class="pack-tab" data-view="templates">
                <i class="icon">📋</i> Templates
            </button>
            <button class="pack-tab" data-view="gear-library">
                <i class="icon">📦</i> Gear Library
            </button>
        </div>
        
        <div class="pack-actions">
            <div class="search-bar">
                <i class="search-icon">🔍</i>
                <input type="search" placeholder="Search packs or gear..." id="global-search">
            </div>
            <button class="btn-action btn-new-pack" id="btn-new-pack">
                <i class="icon">➕</i> New Pack
            </button>
        </div>
    </div>
    
    <!-- Main Content Area -->
    <div class="pack-content">
        
        <!-- My Packs View -->
        <div class="pack-view active" id="view-my-packs">
            <div class="packs-header">
                <h2 class="view-title">My Backpacks</h2>
                <div class="view-controls">
                    <div class="sort-control">
                        <label>Sort by:</label>
                        <select id="sort-packs">
                            <option value="recent">Recently Modified</option>
                            <option value="name">Name</option>
                            <option value="weight">Weight</option>
                            <option value="items">Item Count</option>
                        </select>
                    </div>
                    <div class="view-mode-toggle">
                        <button class="view-mode active" data-mode="grid" title="Grid View">
                            <i>⊞</i>
                        </button>
                        <button class="view-mode" data-mode="list" title="List View">
                            <i>☰</i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Pack Cards Grid -->
            <div class="packs-grid" id="packs-grid">
                <!-- Packs will be loaded here -->
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p>Loading your packs...</p>
                </div>
            </div>
        </div>
        
        <!-- Pack Builder View -->
        <div class="pack-view" id="view-builder">
            <!-- Sticky Action Bar at Top -->
            <div class="builder-action-bar" style="position: sticky; top: 0; z-index: 100; background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(240,248,255,0.95) 100%); backdrop-filter: blur(15px) saturate(1.5); border: 1px solid rgba(255,255,255,0.3); border-radius: 1rem; padding: 1rem 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 8px 24px rgba(0,0,0,0.1), 0 2px 8px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <h3 style="margin: 0; background: linear-gradient(135deg, #2dd4bf, #10b981); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 600;">🏎️ Pack Builder</h3>
                    <span id="save-status" style="color: #6b7280; font-size: 0.875rem; font-weight: 500;"></span>
                </div>
                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <button class="btn btn-secondary" id="btn-cancel-edit" style="padding: 0.625rem 1.25rem; font-weight: 500; border-radius: 0.625rem; background: rgba(255,255,255,0.8); border: 1px solid rgba(209,213,219,0.5); color: #6b7280; transition: all 0.2s ease;" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <span style="margin-right: 0.375rem;">❌</span> Cancel
                    </button>
                    <button class="btn btn-primary btn-lg" id="btn-save-pack" style="padding: 0.75rem 1.75rem; font-weight: 600; border-radius: 0.75rem; background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; box-shadow: 0 4px 12px rgba(16,185,129,0.3); transition: all 0.2s ease; font-size: 1rem;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(16,185,129,0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(16,185,129,0.3)';">
                        <span style="margin-right: 0.5rem; font-size: 1.125rem;">💾</span> Save Pack
                    </button>
                </div>
            </div>
            
            <div class="builder-layout">
                
                <!-- Left Panel: Pack Sections & Weight Summary -->
                <div class="builder-left">
                    <!-- Pack Sections (Moved to top) -->
                    <div class="pack-sections-card">
                        <div class="sections-header">
                            <h3>Pack Sections</h3>
                            <button class="btn-add-section" id="add-section">
                                <i>➕</i> Add
                            </button>
                        </div>
                        <div class="sections-list" id="sections-list">
                            <!-- Default sections -->
                            <div class="pack-section" data-section-id="main">
                                <div class="section-header">
                                    <span class="section-handle">≡</span>
                                    <input type="text" class="section-name" value="Main Compartment">
                                    <span class="section-weight">0g</span>
                                    <button class="btn-section-toggle">▼</button>
                                </div>
                                <div class="section-items dropzone" data-section="main">
                                    <div class="dropzone-placeholder">Drop gear here</div>
                                </div>
                            </div>
                            
                            <div class="pack-section" data-section-id="lid">
                                <div class="section-header">
                                    <span class="section-handle">≡</span>
                                    <input type="text" class="section-name" value="Top Lid">
                                    <span class="section-weight">0g</span>
                                    <button class="btn-section-toggle">▼</button>
                                </div>
                                <div class="section-items dropzone" data-section="lid">
                                    <div class="dropzone-placeholder">Drop gear here</div>
                                </div>
                            </div>
                            
                            <div class="pack-section" data-section-id="pockets">
                                <div class="section-header">
                                    <span class="section-handle">≡</span>
                                    <input type="text" class="section-name" value="Side Pockets">
                                    <span class="section-weight">0g</span>
                                    <button class="btn-section-toggle">▼</button>
                                </div>
                                <div class="section-items dropzone" data-section="pockets">
                                    <div class="dropzone-placeholder">Drop gear here</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Weight Summary -->
                    <div class="weight-summary-card">
                        <h3>Weight Summary</h3>
                        <div class="weight-stats">
                            <div class="weight-stat">
                                <span class="stat-label">Total Weight</span>
                                <span class="stat-value" id="total-weight">0g</span>
                            </div>
                            <div class="weight-stat">
                                <span class="stat-label">Base Weight</span>
                                <span class="stat-value" id="base-weight">0g</span>
                            </div>
                            <div class="weight-stat">
                                <span class="stat-label">Worn Weight</span>
                                <span class="stat-value" id="worn-weight">0g</span>
                            </div>
                            <div class="weight-stat">
                                <span class="stat-label">Consumables</span>
                                <span class="stat-value" id="consumable-weight">0g</span>
                            </div>
                        </div>
                        <div class="weight-chart" id="weight-chart">
                            <!-- Mini chart visualization -->
                        </div>
                    </div>
                </div>
                
                <!-- Right Panel: Gear Library & Pack Details -->
                <div class="builder-right">
                    <!-- Gear Library (At top for easy drag & drop) -->
                    <div class="gear-library-card" id="builder-gear-library">
                        <div class="library-header">
                            <h3>Gear Library</h3>
                            <div class="library-controls">
                                <input type="search" placeholder="Search gear..." id="gear-search" class="gear-search">
                                <button class="btn-add-custom" id="add-custom-gear">
                                    <i>➕</i> Custom
                                </button>
                            </div>
                        </div>
                        
                        <!-- Category Filters -->
                        <div class="category-filters">
                            <button class="cat-filter active" data-category="all">All</button>
                            <button class="cat-filter" data-category="shelter">Shelter</button>
                            <button class="cat-filter" data-category="sleep">Sleep</button>
                            <button class="cat-filter" data-category="cooking">Cooking</button>
                            <button class="cat-filter" data-category="clothing">Clothing</button>
                            <button class="cat-filter" data-category="navigation">Navigation</button>
                            <button class="cat-filter" data-category="hygiene">Hygiene</button>
                            <button class="cat-filter" data-category="first-aid">First Aid</button>
                            <button class="cat-filter" data-category="electronics">Electronics</button>
                            <button class="cat-filter" data-category="other">Other</button>
                        </div>
                        
                        <!-- Gear Items Grid -->
                        <div class="gear-items" id="gear-items">
                            <!-- Gear items will be loaded dynamically from API -->
                            <div class="loading-spinner">
                                <div class="spinner"></div>
                                <p>Loading gear...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pack Details (Below gear library for better workflow) -->
                    <div class="pack-info-card">
                        <h3>Pack Details</h3>
                        <div class="form-group">
                            <label>Pack Name</label>
                            <input type="text" id="pack-name" class="form-control" placeholder="Weekend Warrior">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea id="pack-description" class="form-control" rows="2" placeholder="Perfect for 2-3 day trips..."></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Capacity (L)</label>
                                <input type="number" id="pack-capacity" class="form-control" value="65">
                            </div>
                            <div class="form-group">
                                <label>Base Weight (g)</label>
                                <input type="number" id="pack-base-weight" class="form-control" value="0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Templates View -->
        <div class="pack-view" id="view-templates">
            <div class="templates-header">
                <h2 class="view-title">Pack Templates</h2>
                <p class="view-subtitle">Start with a pre-configured pack for your adventure type</p>
            </div>
            
            <div class="templates-grid">
                <!-- Template cards -->
                <div class="template-card">
                    <div class="template-icon">🏕️</div>
                    <h3>Weekend Warrior</h3>
                    <p>2-3 day trips, 3-season conditions</p>
                    <div class="template-stats">
                        <span>Base Weight: ~4.5kg</span>
                        <span>35 items</span>
                    </div>
                    <button class="btn-use-template">Use Template</button>
                </div>
                
                <div class="template-card">
                    <div class="template-icon">🏔️</div>
                    <h3>Thru-Hiker</h3>
                    <p>Long-distance trails, ultralight focus</p>
                    <div class="template-stats">
                        <span>Base Weight: ~3kg</span>
                        <span>28 items</span>
                    </div>
                    <button class="btn-use-template">Use Template</button>
                </div>
                
                <div class="template-card">
                    <div class="template-icon">❄️</div>
                    <h3>Winter Explorer</h3>
                    <p>Cold weather, snow camping</p>
                    <div class="template-stats">
                        <span>Base Weight: ~6kg</span>
                        <span>42 items</span>
                    </div>
                    <button class="btn-use-template">Use Template</button>
                </div>
                
                <div class="template-card">
                    <div class="template-icon">🌄</div>
                    <h3>Day Hiker</h3>
                    <p>Single day adventures</p>
                    <div class="template-stats">
                        <span>Base Weight: ~2kg</span>
                        <span>18 items</span>
                    </div>
                    <button class="btn-use-template">Use Template</button>
                </div>
            </div>
        </div>
        
        <!-- Enhanced Gear Library View -->
        <div class="pack-view" id="view-gear-library">
            <div class="gear-library-container">
                <!-- Header with View Controls -->
                <div class="gear-library-header">
                    <h2 class="view-title">Gear Library</h2>
                    
                    <!-- View Mode Toggle -->
                    <div class="view-mode-controls">
                        <label class="radio-group" title="Show all available gear items">
                            <input type="radio" name="gear-view-mode" value="both" checked>
                            <span>🌐 All Gear</span>
                        </label>
                        <label class="radio-group" title="Show only default system gear">
                            <input type="radio" name="gear-view-mode" value="default">
                            <span>📦 System Gear</span>
                        </label>
                        <label class="radio-group" title="Show only your custom gear">
                            <input type="radio" name="gear-view-mode" value="custom">
                            <span>✨ My Custom</span>
                        </label>
                        <label class="radio-group" title="Show items you've hidden">
                            <input type="radio" name="gear-view-mode" value="hidden">
                            <span>👁️‍🗨️ Hidden</span>
                        </label>
                    </div>
                </div>
                
                <!-- Search and Filter Bar -->
                <div class="gear-controls-bar">
                    <div class="gear-search-box">
                        <i class="search-icon">🔍</i>
                        <input type="text" id="gear-library-search" placeholder="Search gear..." class="gear-search-input">
                    </div>
                    
                    <div class="gear-filters">
                        <select id="gear-category-filter" class="gear-filter-select">
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
                        
                        <select id="gear-sort-select" class="gear-filter-select">
                            <option value="name">Sort by Name</option>
                            <option value="weight">Sort by Weight</option>
                            <option value="category">Sort by Category</option>
                        </select>
                    </div>
                    
                    <div class="gear-actions">
                        <button class="btn-primary" id="btn-add-custom-gear">
                            <i class="icon">➕</i> Add Custom Gear
                        </button>
                    </div>
                </div>
                
                <!-- Gear Stats Bar -->
                <div class="gear-stats-bar">
                    <div class="stat-item">
                        <span class="stat-label">Total Items:</span>
                        <span class="stat-value" id="gear-total-count">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Total Weight:</span>
                        <span class="stat-value" id="gear-total-weight">0g</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">View:</span>
                        <span class="stat-value" id="gear-view-indicator">All Gear</span>
                    </div>
                    <div class="stat-item">
                        <button class="btn-link" id="btn-toggle-view">
                            <i class="icon">⊞</i> <span id="view-toggle-text">Card View</span>
                        </button>
                    </div>
                </div>
                
                <!-- Gear Items Grid/List -->
                <div class="gear-library-content" id="gear-library-content">
                    <div class="gear-items-grid" id="gear-items-container">
                        <!-- Items will be loaded here dynamically -->
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                            <p>Loading gear library...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- Custom Gear Modal (simplified, non-intrusive) -->
<div class="custom-gear-panel" id="custom-gear-panel" style="display: none;">
    <div class="panel-header">
        <h3>Add Custom Gear</h3>
        <button class="btn-close-panel" id="close-custom-gear">×</button>
    </div>
    <div class="panel-body">
        <div class="form-group">
            <label>Item Name</label>
            <input type="text" id="custom-name" class="form-control">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Weight (g)</label>
                <input type="number" id="custom-weight" class="form-control">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select id="custom-category" class="form-control">
                    <option value="shelter">Shelter</option>
                    <option value="sleep">Sleep</option>
                    <option value="cooking">Cooking</option>
                    <option value="clothing">Clothing</option>
                    <option value="navigation">Navigation</option>
                    <option value="hygiene">Hygiene</option>
                    <option value="first-aid">First Aid</option>
                    <option value="electronics">Electronics</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Notes</label>
            <textarea id="custom-notes" class="form-control" rows="2"></textarea>
        </div>
        <div class="panel-actions">
            <button class="btn-secondary" id="cancel-custom">Cancel</button>
            <button class="btn-primary" id="save-custom">Add to Library</button>
        </div>
    </div>
</div>

<!-- jQuery (already included in template) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- jQuery UI for better drag and drop -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<!-- Our Pack Builder JS -->
<script src="<?php echo asset_url('js/pack-builder.js'); ?>"></script>
<!-- Enhanced Pack Builder (delete + improved UI) -->
<script src="<?php echo asset_url('js/pack-builder-enhanced.js'); ?>"></script>
<!-- Pack Builder Gear Integration -->
<script src="<?php echo asset_url('js/pack-builder-gear.js'); ?>"></script>
<!-- Pack Builder CRUD Operations -->
<script src="<?php echo asset_url('js/pack-builder-crud.js'); ?>"></script>
<!-- Enhanced Gear Library -->
<script src="<?php echo asset_url('js/gear-library.js'); ?>"></script>

<!-- Initialize Pack Builder after all scripts are loaded -->
<script>
$(document).ready(function() {
    console.log('🎒 Initializing Pack Builder UI...');
    
    // Ensure PackBuilder is available
    if (typeof window.PackBuilder !== 'undefined') {
        console.log('✅ PackBuilder loaded');
        
        // Load initial packs
        if (typeof window.PackBuilder.loadPacks === 'function') {
            window.PackBuilder.loadPacks();
            console.log('✅ Packs loaded');
        }
        
        // Show the my-packs view by default
        if (typeof window.PackBuilder.switchView === 'function') {
            window.PackBuilder.switchView('my-packs');
        }
        
        console.log('✅ Pack Builder UI Ready!');
    } else {
        console.error('❌ PackBuilder not loaded!');
    }
    
    // Add click handler for New Pack button if not already bound
    $('#btn-new-pack').off('click').on('click', function() {
        console.log('New Pack button clicked');
        if (window.PackBuilder && window.PackBuilder.createNewPack) {
            window.PackBuilder.createNewPack();
        } else if (window.PackBuilderCRUD && window.PackBuilderCRUD.createNewPack) {
            window.PackBuilderCRUD.createNewPack();
        }
    });
});
</script>
<!-- Save Pack Enhancement -->
<script src="<?php echo asset_url('js/gear-search-enhanced.js'); ?>"></script>
<!-- Weight Calculator with Animated Counters -->
<script src="<?php echo asset_url('js/weight-calculator.js'); ?>"></script>
<!-- Save Pack Enhancement -->
<script src="<?php echo asset_url('js/save-pack-enhancement.js'); ?>"></script>
<!-- Backpacks Loading Enhancement with New Card System -->
<script src="<?php echo asset_url('js/backpacks-loading.js'); ?>"></script>

<?php require_once __DIR__ . '/includes/template-footer.php'; ?>
