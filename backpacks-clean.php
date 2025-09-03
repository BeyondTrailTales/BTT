<?php
// Clean Backpacks Page - Minimal Scripts
require_once __DIR__ . '/app/bootstrap.php';
require_auth();

$pageId = 'backpacks-clean';
$pageTitle = 'Pack Builder (Clean)';
$pageDescription = 'Clean pack builder with minimal scripts';

// Only essential styles
$pageStyles = [
    'css/pack-builder.css'
];

// Only essential scripts - no conflicts
$pageScripts = [
    'js/api.js',
    'js/pack-builder-crud.js'
];

require_once __DIR__ . '/includes/template-header.php';
?>

<div class="pack-builder-container">
    
    <!-- Simple Tabs -->
    <div class="pack-action-bar">
        <div class="pack-tabs">
            <button class="pack-tab active" data-view="my-packs">My Packs</button>
            <button class="pack-tab" data-view="builder">Pack Builder</button>
        </div>
        <button class="btn-primary" id="btn-new-pack">➕ New Pack</button>
    </div>
    
    <!-- My Packs View -->
    <div class="pack-view active" id="view-my-packs">
        <h2>My Backpacks</h2>
        <div id="packs-grid" class="packs-grid">
            <div class="loading-spinner">Loading...</div>
        </div>
    </div>
    
    <!-- Pack Builder View -->
    <div class="pack-view" id="view-builder" style="display:none;">
        <h2>Pack Builder</h2>
        
        <div class="builder-controls">
            <button class="btn-primary" id="btn-save-pack">💾 Save Pack</button>
            <button class="btn-secondary" id="btn-cancel-edit">Cancel</button>
        </div>
        
        <div class="pack-form">
            <div class="form-group">
                <label>Pack Name:</label>
                <input type="text" id="pack-name" placeholder="Enter pack name" required>
            </div>
            
            <div class="form-group">
                <label>Description:</label>
                <textarea id="pack-description" placeholder="Pack description"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Capacity (L):</label>
                    <input type="number" id="pack-capacity" value="65" min="0" max="150">
                </div>
                
                <div class="form-group">
                    <label>Base Weight (g):</label>
                    <input type="number" id="pack-base-weight" value="0" min="0">
                </div>
            </div>
        </div>
        
        <!-- Sections -->
        <div id="sections-list">
            <h3>Pack Sections</h3>
            
            <div class="pack-section" data-section-id="main">
                <div class="section-header">
                    <input type="text" class="section-name" value="Main Compartment">
                    <span class="section-weight">0g</span>
                </div>
                <div class="section-items dropzone" data-section="main">
                    <div class="dropzone-placeholder">No items yet</div>
                </div>
            </div>
            
            <div class="pack-section" data-section-id="lid">
                <div class="section-header">
                    <input type="text" class="section-name" value="Top Lid">
                    <span class="section-weight">0g</span>
                </div>
                <div class="section-items dropzone" data-section="lid">
                    <div class="dropzone-placeholder">No items yet</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Minimal required styles */
.pack-builder-container { padding: 20px; max-width: 1400px; margin: 0 auto; }
.pack-action-bar { display: flex; justify-content: space-between; margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 8px; }
.pack-tabs { display: flex; gap: 10px; }
.pack-tab { padding: 8px 16px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 4px; }
.pack-tab.active { background: #10b981; color: white; }
.pack-view { display: none; }
.pack-view.active { display: block; }
.packs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
.pack-card { border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: white; }
.pack-form { margin: 20px 0; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
.form-group input, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.btn-primary { background: #10b981; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; }
.btn-secondary { background: #6b7280; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; }
.pack-section { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 8px; }
.section-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
.section-items { min-height: 60px; background: #f9f9f9; padding: 10px; border-radius: 4px; }
.dropzone-placeholder { color: #999; text-align: center; }
.builder-controls { margin-bottom: 20px; display: flex; gap: 10px; }
.loading-spinner { text-align: center; padding: 40px; color: #666; }
</style>

<script>
// Simple tab switching
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.pack-tab');
    const views = document.querySelectorAll('.pack-view');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const viewName = this.getAttribute('data-view');
            
            // Update active tab
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Show corresponding view
            views.forEach(v => {
                if (v.id === 'view-' + viewName) {
                    v.classList.add('active');
                    v.style.display = 'block';
                } else {
                    v.classList.remove('active');
                    v.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/template-footer.php'; ?>
