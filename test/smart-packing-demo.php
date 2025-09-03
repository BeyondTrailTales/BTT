<?php
require_once dirname(__DIR__) . '/app/config.php';

$pageId = 'smart-packing-demo';
$pageTitle = 'Smart Packing Assistant Demo - BeyondTrailTales';
$pageDescription = 'Demo of AI-powered packing suggestions and optimization';

// Include header
require_once dirname(__DIR__) . '/public/includes/header.php';
?>

<div class="demo-container" style="max-width: 1400px; margin: 0 auto; padding: 2rem;">
    <div class="page-header">
        <h1 class="page-title">🤖 Smart Packing Assistant Demo</h1>
        <p class="page-description">Experience AI-powered packing suggestions, automatic categorization, and weight optimization</p>
    </div>
    
    <!-- Demo Controls -->
    <div class="demo-controls" style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
        <h3>Demo Settings</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
            <div>
                <label for="demo-trip-type">Trip Type:</label>
                <select id="demo-trip-type" class="form-control" onchange="updateDemoSettings()">
                    <option value="day-hike">Day Hike</option>
                    <option value="weekend-backpacking" selected>Weekend Backpacking</option>
                    <option value="thru-hike">Thru-Hike</option>
                </select>
            </div>
            <div>
                <label for="demo-weather">Weather:</label>
                <select id="demo-weather" class="form-control" onchange="updateDemoSettings()">
                    <option value="">Normal</option>
                    <option value="cold">Cold</option>
                    <option value="rain">Rain</option>
                    <option value="hot">Hot</option>
                </select>
            </div>
            <div>
                <label>&nbsp;</label>
                <button class="btn btn-primary" style="width: 100%;" onclick="loadSampleData()">
                    Load Sample Packing List
                </button>
            </div>
        </div>
    </div>
    
    <!-- Main Content Grid -->
    <div style="display: grid; grid-template-columns: 1fr 380px; gap: 2rem;">
        <!-- Packing List Section -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h2>Current Packing List</h2>
                    <div>
                        <button class="btn btn-secondary" onclick="clearPackingList()">Clear All</button>
                        <button class="btn btn-primary" onclick="addCustomItem()">Add Item</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="packing-list-container">
                        <p style="text-align: center; color: #6b7280;">No items in packing list. Load sample data to get started.</p>
                    </div>
                </div>
            </div>
            
            <!-- Visual Backpack Display -->
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2>Visual Backpack</h2>
                </div>
                <div class="card-body">
                    <div id="visual-backpack" style="display: flex; justify-content: space-around; padding: 2rem;">
                        <div class="backpack-section" data-section="lid" style="background: #dbeafe; padding: 1rem; border-radius: 8px; min-width: 120px;">
                            <h4 style="color: #3b82f6;">Top Lid</h4>
                            <div class="section-weight">0 kg</div>
                            <div class="section-items" style="font-size: 0.85rem; color: #6b7280;"></div>
                        </div>
                        <div class="backpack-section" data-section="main" style="background: #d1fae5; padding: 1rem; border-radius: 8px; min-width: 120px;">
                            <h4 style="color: #10b981;">Main Body</h4>
                            <div class="section-weight">0 kg</div>
                            <div class="section-items" style="font-size: 0.85rem; color: #6b7280;"></div>
                        </div>
                        <div class="backpack-section" data-section="front" style="background: #fed7aa; padding: 1rem; border-radius: 8px; min-width: 120px;">
                            <h4 style="color: #f59e0b;">Front Pocket</h4>
                            <div class="section-weight">0 kg</div>
                            <div class="section-items" style="font-size: 0.85rem; color: #6b7280;"></div>
                        </div>
                        <div class="backpack-section" data-section="side" style="background: #e9d5ff; padding: 1rem; border-radius: 8px; min-width: 120px;">
                            <h4 style="color: #8b5cf6;">Side Pockets</h4>
                            <div class="section-weight">0 kg</div>
                            <div class="section-items" style="font-size: 0.85rem; color: #6b7280;"></div>
                        </div>
                        <div class="backpack-section" data-section="bottom" style="background: #fecaca; padding: 1rem; border-radius: 8px; min-width: 120px;">
                            <h4 style="color: #ef4444;">Bottom</h4>
                            <div class="section-weight">0 kg</div>
                            <div class="section-items" style="font-size: 0.85rem; color: #6b7280;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Smart Assistant Panel (will be loaded here) -->
        <div id="assistant-container">
            <!-- Smart Packing Assistant will be inserted here -->
        </div>
    </div>
</div>

<!-- Load Smart Packing Module -->
<script src="/BTT/public/js/smart-packing.js"></script>

<!-- Include Smart Packing Assistant Component -->
<?php include dirname(__DIR__) . '/public/includes/smart-packing-assistant.php'; ?>

<style>
/* Demo-specific styles */
.demo-container {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}

.card-header h2 {
    margin: 0;
    font-size: 1.25rem;
    color: #1f2937;
}

.card-body {
    padding: 1.5rem;
}

.packing-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    margin: 0.5rem 0;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    transition: all 0.2s;
}

.packing-item:hover {
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.item-info {
    flex: 1;
}

.item-name {
    font-weight: 600;
    color: #1f2937;
}

.item-details {
    font-size: 0.85rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.item-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    padding: 0.25rem 0.5rem;
    background: transparent;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-icon:hover {
    background: #f3f4f6;
}

.btn-remove {
    color: #ef4444;
    border-color: #fecaca;
}

.btn-remove:hover {
    background: #fef2f2;
}

/* Override assistant panel position for demo */
#smart-packing-assistant {
    position: relative !important;
    top: 0 !important;
    right: 0 !important;
    width: 100% !important;
    margin: 0;
}
</style>

<script>
// Demo data
let demoPackingList = [];

// Sample packing lists for different trip types
const samplePackingLists = {
    'day-hike': [
        { name: 'Day Pack', weight: 0.5, category: 'backpack' },
        { name: 'Water Bottle', weight: 0.5, category: 'water' },
        { name: 'Trail Mix', weight: 0.2, category: 'snacks' },
        { name: 'Sandwich', weight: 0.3, category: 'food' },
        { name: 'First Aid Kit', weight: 0.2, category: 'first-aid' },
        { name: 'Map', weight: 0.1, category: 'navigation' },
        { name: 'Sunscreen', weight: 0.1, category: 'hygiene' },
        { name: 'Headlamp', weight: 0.1, category: 'electronics' }
    ],
    'weekend-backpacking': [
        { name: 'Backpack 65L', weight: 2.0, category: 'backpack' },
        { name: 'Tent', weight: 2.5, category: 'shelter' },
        { name: 'Sleeping Bag', weight: 1.2, category: 'sleep-system' },
        { name: 'Sleeping Pad', weight: 0.5, category: 'sleep-system' },
        { name: 'Cooking Stove', weight: 0.3, category: 'cooking' },
        { name: 'Pot Set', weight: 0.2, category: 'cooking' },
        { name: 'Water Filter', weight: 0.2, category: 'water' },
        { name: 'Water Bottles (2)', weight: 1.0, category: 'water' },
        { name: 'Food (2 days)', weight: 1.5, category: 'food' },
        { name: 'Extra Clothing', weight: 1.0, category: 'clothing' },
        { name: 'Rain Jacket', weight: 0.3, category: 'clothing' },
        { name: 'First Aid Kit', weight: 0.3, category: 'first-aid' },
        { name: 'Map and Compass', weight: 0.2, category: 'navigation' },
        { name: 'Headlamp', weight: 0.1, category: 'electronics' },
        { name: 'Multi-tool', weight: 0.2, category: 'tools' },
        { name: 'Toiletries', weight: 0.2, category: 'hygiene' }
    ],
    'thru-hike': [
        { name: 'Ultralight Pack', weight: 1.0, category: 'backpack' },
        { name: 'UL Tent', weight: 1.0, category: 'shelter' },
        { name: 'Down Sleeping Bag', weight: 0.8, category: 'sleep-system' },
        { name: 'UL Sleeping Pad', weight: 0.3, category: 'sleep-system' },
        { name: 'Canister Stove', weight: 0.15, category: 'cooking' },
        { name: 'Titanium Pot', weight: 0.1, category: 'cooking' },
        { name: 'Water Filter', weight: 0.15, category: 'water' },
        { name: 'Smart Water Bottles', weight: 0.5, category: 'water' },
        { name: 'Food (5 days)', weight: 2.5, category: 'food' },
        { name: 'Base Layers', weight: 0.3, category: 'clothing' },
        { name: 'Insulation Layer', weight: 0.4, category: 'clothing' },
        { name: 'Rain Gear', weight: 0.3, category: 'clothing' },
        { name: 'First Aid Kit', weight: 0.3, category: 'first-aid' },
        { name: 'GPS Device', weight: 0.2, category: 'electronics' },
        { name: 'Power Bank', weight: 0.3, category: 'electronics' },
        { name: 'Repair Kit', weight: 0.2, category: 'tools' }
    ]
};

// Initialize demo
function initDemo() {
    // Set global variables for the assistant
    window.currentTripType = document.getElementById('demo-trip-type').value;
    window.currentWeather = document.getElementById('demo-weather').value || null;
    window.packingItems = demoPackingList;
    window.updatePackingList = updatePackingListDisplay;
    
    // Initialize assistant if loaded
    if (typeof analyzePackingEfficiency === 'function') {
        analyzePackingEfficiency(demoPackingList);
    }
}

// Update demo settings
function updateDemoSettings() {
    window.currentTripType = document.getElementById('demo-trip-type').value;
    window.currentWeather = document.getElementById('demo-weather').value || null;
    
    // Re-analyze with new settings
    if (typeof analyzePackingEfficiency === 'function') {
        analyzePackingEfficiency(demoPackingList);
    }
}

// Load sample data
function loadSampleData() {
    const tripType = document.getElementById('demo-trip-type').value;
    demoPackingList = [...samplePackingLists[tripType]];
    
    // Add section assignments
    demoPackingList = demoPackingList.map(item => ({
        ...item,
        section: SmartPacking.getOptimalSection(item),
        quantity: 1,
        packed: Math.random() > 0.5
    }));
    
    updatePackingListDisplay(demoPackingList);
    BTTUtils.showToast(`Loaded ${demoPackingList.length} sample items for ${tripType.replace('-', ' ')}`, 'success');
}

// Update packing list display
function updatePackingListDisplay(items) {
    demoPackingList = items;
    window.packingItems = items;
    
    const container = document.getElementById('packing-list-container');
    
    if (items.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #6b7280;">No items in packing list. Load sample data to get started.</p>';
        updateVisualBackpack([]);
        return;
    }
    
    // Group items by section
    const sections = {
        lid: [],
        main: [],
        front: [],
        side: [],
        bottom: [],
        other: []
    };
    
    items.forEach(item => {
        const section = item.section || 'other';
        if (sections[section]) {
            sections[section].push(item);
        } else {
            sections.other.push(item);
        }
    });
    
    // Generate HTML
    let html = '';
    const sectionNames = {
        lid: 'Top Lid',
        main: 'Main Compartment',
        front: 'Front Pocket',
        side: 'Side Pockets',
        bottom: 'Bottom Section',
        other: 'Uncategorized'
    };
    
    Object.entries(sections).forEach(([section, sectionItems]) => {
        if (sectionItems.length === 0) return;
        
        html += `<div style="margin-bottom: 1.5rem;">`;
        html += `<h4 style="color: #6b7280; font-size: 0.9rem; margin-bottom: 0.5rem;">${sectionNames[section]}</h4>`;
        
        sectionItems.forEach((item, index) => {
            html += `
                <div class="packing-item" data-index="${items.indexOf(item)}">
                    <div class="item-info">
                        <div class="item-name">${BTTUtils.escapeHtml(item.name)}</div>
                        <div class="item-details">
                            ${item.weight} kg • ${item.category} • 
                            <span style="color: ${item.packed ? '#10b981' : '#6b7280'}">
                                ${item.packed ? '✓ Packed' : '○ Not packed'}
                            </span>
                        </div>
                    </div>
                    <div class="item-actions">
                        <button class="btn-icon" onclick="togglePacked(${items.indexOf(item)})" title="Toggle packed">
                            ${item.packed ? '✓' : '○'}
                        </button>
                        <button class="btn-icon btn-remove" onclick="removeItem(${items.indexOf(item)})" title="Remove">
                            ×
                        </button>
                    </div>
                </div>
            `;
        });
        
        html += `</div>`;
    });
    
    container.innerHTML = html;
    
    // Update visual backpack
    updateVisualBackpack(items);
    
    // Re-analyze efficiency
    if (typeof analyzePackingEfficiency === 'function') {
        analyzePackingEfficiency(items);
    }
}

// Update visual backpack display
function updateVisualBackpack(items) {
    const sections = {
        lid: { weight: 0, items: [] },
        main: { weight: 0, items: [] },
        front: { weight: 0, items: [] },
        side: { weight: 0, items: [] },
        bottom: { weight: 0, items: [] }
    };
    
    items.forEach(item => {
        const section = item.section || 'main';
        if (sections[section]) {
            sections[section].weight += item.weight || 0;
            sections[section].items.push(item.name);
        }
    });
    
    Object.entries(sections).forEach(([section, data]) => {
        const element = document.querySelector(`.backpack-section[data-section="${section}"]`);
        if (element) {
            element.querySelector('.section-weight').textContent = `${data.weight.toFixed(1)} kg`;
            element.querySelector('.section-items').textContent = 
                data.items.length > 0 ? 
                    (data.items.slice(0, 2).join(', ') + (data.items.length > 2 ? ` +${data.items.length - 2}` : '')) :
                    'Empty';
        }
    });
}

// Toggle packed status
function togglePacked(index) {
    demoPackingList[index].packed = !demoPackingList[index].packed;
    updatePackingListDisplay(demoPackingList);
}

// Remove item
function removeItem(index) {
    demoPackingList.splice(index, 1);
    updatePackingListDisplay(demoPackingList);
    BTTUtils.showToast('Item removed', 'info');
}

// Clear packing list
function clearPackingList() {
    if (confirm('Clear all items from the packing list?')) {
        demoPackingList = [];
        updatePackingListDisplay(demoPackingList);
        BTTUtils.showToast('Packing list cleared', 'info');
    }
}

// Add custom item
function addCustomItem() {
    const name = prompt('Item name:');
    if (!name) return;
    
    const weight = parseFloat(prompt('Weight (kg):') || '0.1');
    
    const newItem = {
        name: name,
        weight: weight,
        category: SmartPacking.categorizeItem(name),
        quantity: 1,
        packed: false
    };
    
    newItem.section = SmartPacking.getOptimalSection(newItem);
    
    demoPackingList.push(newItem);
    updatePackingListDisplay(demoPackingList);
    BTTUtils.showToast(`Added ${name}`, 'success');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initDemo();
    
    // Load sample data automatically
    setTimeout(() => {
        loadSampleData();
    }, 500);
});
</script>

<?php require_once dirname(__DIR__) . '/public/includes/footer.php'; ?>
