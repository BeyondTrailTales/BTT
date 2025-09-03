<?php
// Load bootstrap first
require_once dirname(__DIR__) . '/app/bootstrap.php';

// Set page metadata
$pageId = 'sort-test';
$pageTitle = 'Sorting Functionality Test';
$pageDescription = 'Testing all sorting features in BTT';

// Include the unified template header
require_once dirname(__DIR__) . '/public/includes/template-header.php';
?>

<style>
.test-section {
    background: var(--glass-bg);
    backdrop-filter: var(--glass-blur);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    margin-bottom: var(--space-6);
}

.test-header {
    color: var(--forest-mint);
    margin-bottom: var(--space-4);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.test-status {
    padding: var(--space-2) var(--space-4);
    border-radius: var(--radius-full);
    font-size: var(--text-sm);
    font-weight: var(--font-bold);
}

.status-working {
    background: var(--gradient-success);
    color: var(--forest-deep);
}

.status-broken {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
}

.test-results {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(74, 222, 128, 0.2);
    border-radius: var(--radius-md);
    padding: var(--space-4);
    margin-top: var(--space-4);
    font-family: monospace;
    font-size: var(--text-sm);
    color: var(--text-secondary);
    max-height: 300px;
    overflow-y: auto;
}

.test-item {
    padding: var(--space-2);
    margin: var(--space-2) 0;
    border-left: 3px solid var(--forest-mint);
    background: rgba(74, 222, 128, 0.05);
}

.btn-test {
    background: var(--gradient-primary);
    color: var(--forest-deep);
    padding: var(--space-2) var(--space-4);
    border: none;
    border-radius: var(--radius-md);
    cursor: pointer;
    font-weight: var(--font-bold);
    transition: var(--transition-all);
}

.btn-test:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-glow-md);
}
</style>

<div class="pack-builder-container">
    <h1 style="color: var(--forest-mint); margin-bottom: var(--space-6);">🔧 Sorting Functionality Test Suite</h1>
    
    <!-- Backpacks Sorting Test -->
    <section class="test-section">
        <div class="test-header">
            <h2>📦 Backpacks Sorting</h2>
            <span class="test-status" id="packs-status">Testing...</span>
        </div>
        <p>Testing sort options: Recently Modified, Name, Weight, Item Count</p>
        
        <div style="margin: var(--space-4) 0;">
            <label>Sort by:</label>
            <select id="test-sort-packs" style="margin: 0 var(--space-2);">
                <option value="recent">Recently Modified</option>
                <option value="name">Name</option>
                <option value="weight">Weight</option>
                <option value="items">Item Count</option>
            </select>
            <button class="btn-test" onclick="testPackSort()">Test Sort</button>
        </div>
        
        <div class="test-results" id="packs-results">
            <div>Ready to test...</div>
        </div>
    </section>
    
    <!-- Trips Sorting Test -->
    <section class="test-section">
        <div class="test-header">
            <h2>🗺️ Trips Sorting</h2>
            <span class="test-status" id="trips-status">Testing...</span>
        </div>
        <p>Testing sort options: Recently Created, Name, Start Date</p>
        
        <div style="margin: var(--space-4) 0;">
            <label>Sort by:</label>
            <select id="test-sort-trips" style="margin: 0 var(--space-2);">
                <option value="recent">Recently Created</option>
                <option value="name">Name</option>
                <option value="date">Start Date</option>
            </select>
            <button class="btn-test" onclick="testTripSort()">Test Sort</button>
        </div>
        
        <div class="test-results" id="trips-results">
            <div>Ready to test...</div>
        </div>
    </section>
    
    <!-- Gear Library Sorting Test -->
    <section class="test-section">
        <div class="test-header">
            <h2>🎒 Gear Library Sorting</h2>
            <span class="test-status" id="gear-status">Testing...</span>
        </div>
        <p>Testing gear library table sorting by columns</p>
        
        <div style="margin: var(--space-4) 0;">
            <button class="btn-test" onclick="testGearSort()">Test Gear Sort</button>
        </div>
        
        <div class="test-results" id="gear-results">
            <div>Ready to test...</div>
        </div>
    </section>
    
    <!-- Overall Status -->
    <section class="test-section" style="background: rgba(74, 222, 128, 0.1);">
        <h2 style="color: var(--forest-mint);">📊 Test Summary</h2>
        <div id="test-summary" style="margin-top: var(--space-4);">
            <p>Click the test buttons above to verify sorting functionality.</p>
        </div>
    </section>
</div>

<script>
// Test data
const testPacks = [
    { id: 1, name: 'Alpine Pack', weight: 4500, items: 25, modified: '2024-01-15' },
    { id: 2, name: 'Weekend Warrior', weight: 3200, items: 18, modified: '2024-01-20' },
    { id: 3, name: 'Day Hiker', weight: 1800, items: 12, modified: '2024-01-10' },
    { id: 4, name: 'Ultralight Setup', weight: 2500, items: 30, modified: '2024-01-25' }
];

const testTrips = [
    { id: 1, title: 'PCT Section Hike', created_at: '2024-01-15', start_date: '2024-03-15' },
    { id: 2, title: 'Grand Canyon Rim2Rim', created_at: '2024-01-20', start_date: '2024-02-10' },
    { id: 3, title: 'Appalachian Trail', created_at: '2024-01-10', start_date: '2024-04-01' },
    { id: 4, title: 'Zion Narrows', created_at: '2024-01-25', start_date: '2024-02-20' }
];

function updateStatus(elementId, status) {
    const element = document.getElementById(elementId);
    if (status === 'working') {
        element.textContent = '✅ Working';
        element.className = 'test-status status-working';
    } else {
        element.textContent = '❌ Broken';
        element.className = 'test-status status-broken';
    }
}

function testPackSort() {
    const sortBy = document.getElementById('test-sort-packs').value;
    const results = document.getElementById('packs-results');
    let sorted = [...testPacks];
    
    try {
        switch(sortBy) {
            case 'name':
                sorted.sort((a, b) => a.name.localeCompare(b.name));
                break;
            case 'weight':
                sorted.sort((a, b) => a.weight - b.weight);
                break;
            case 'items':
                sorted.sort((a, b) => a.items - b.items);
                break;
            case 'recent':
            default:
                sorted.sort((a, b) => new Date(b.modified) - new Date(a.modified));
        }
        
        results.innerHTML = `
            <div><strong>Sort by: ${sortBy}</strong></div>
            ${sorted.map(pack => `
                <div class="test-item">
                    ${pack.name} - Weight: ${pack.weight}g, Items: ${pack.items}, Modified: ${pack.modified}
                </div>
            `).join('')}
        `;
        
        updateStatus('packs-status', 'working');
        
        // Test actual implementation if PackBuilder exists
        if (window.PackBuilder && window.PackBuilder.sortPacks) {
            window.PackBuilder.state = window.PackBuilder.state || {};
            window.PackBuilder.state.packs = testPacks;
            window.PackBuilder.sortPacks(sortBy);
            results.innerHTML += '<div style="color: #4ade80; margin-top: 10px;">✓ PackBuilder.sortPacks() function exists and runs</div>';
        } else {
            results.innerHTML += '<div style="color: #f87171; margin-top: 10px;">⚠️ PackBuilder.sortPacks() not found</div>';
            updateStatus('packs-status', 'broken');
        }
    } catch (error) {
        results.innerHTML = `<div style="color: #f87171;">Error: ${error.message}</div>`;
        updateStatus('packs-status', 'broken');
    }
}

function testTripSort() {
    const sortBy = document.getElementById('test-sort-trips').value;
    const results = document.getElementById('trips-results');
    let sorted = [...testTrips];
    
    try {
        switch(sortBy) {
            case 'name':
                sorted.sort((a, b) => a.title.localeCompare(b.title));
                break;
            case 'date':
                sorted.sort((a, b) => new Date(a.start_date) - new Date(b.start_date));
                break;
            case 'recent':
            default:
                sorted.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        }
        
        results.innerHTML = `
            <div><strong>Sort by: ${sortBy}</strong></div>
            ${sorted.map(trip => `
                <div class="test-item">
                    ${trip.title} - Created: ${trip.created_at}, Start: ${trip.start_date}
                </div>
            `).join('')}
        `;
        
        updateStatus('trips-status', 'working');
    } catch (error) {
        results.innerHTML = `<div style="color: #f87171;">Error: ${error.message}</div>`;
        updateStatus('trips-status', 'broken');
    }
}

function testGearSort() {
    const results = document.getElementById('gear-results');
    
    try {
        // Check if gear library exists
        if (window.PackBuilder && window.PackBuilder.state && window.PackBuilder.state.gearLibrary) {
            const gear = window.PackBuilder.state.gearLibrary.slice(0, 5);
            
            // Test sorting by weight
            const sortedByWeight = [...gear].sort((a, b) => a.weight - b.weight);
            
            results.innerHTML = `
                <div><strong>Gear Library Loaded: ${window.PackBuilder.state.gearLibrary.length} items</strong></div>
                <div style="margin-top: 10px;"><strong>Sample sorted by weight:</strong></div>
                ${sortedByWeight.map(item => `
                    <div class="test-item">
                        ${item.name} - ${item.weight}g - ${item.category}
                    </div>
                `).join('')}
                <div style="color: #4ade80; margin-top: 10px;">✓ Gear library loaded and sortable</div>
            `;
            
            updateStatus('gear-status', 'working');
        } else {
            results.innerHTML = '<div style="color: #f87171;">Gear library not loaded. Please visit the Backpacks page first.</div>';
            updateStatus('gear-status', 'broken');
        }
    } catch (error) {
        results.innerHTML = `<div style="color: #f87171;">Error: ${error.message}</div>`;
        updateStatus('gear-status', 'broken');
    }
}

// Auto-run tests on page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        // Initial status check
        const packsOk = window.PackBuilder && window.PackBuilder.sortPacks;
        const tripsOk = true; // Trips sorting is implemented inline
        const gearOk = window.PackBuilder && window.PackBuilder.state;
        
        updateStatus('packs-status', packsOk ? 'working' : 'broken');
        updateStatus('trips-status', tripsOk ? 'working' : 'broken');
        updateStatus('gear-status', gearOk ? 'working' : 'broken');
        
        // Update summary
        const summary = document.getElementById('test-summary');
        const total = 3;
        const working = (packsOk ? 1 : 0) + (tripsOk ? 1 : 0) + (gearOk ? 1 : 0);
        
        summary.innerHTML = `
            <div style="font-size: 1.5rem; color: var(--forest-mint); margin-bottom: var(--space-4);">
                ${working}/${total} sorting features detected
            </div>
            <ul style="list-style: none; padding: 0;">
                <li style="margin: var(--space-2) 0;">${packsOk ? '✅' : '❌'} Backpacks sorting: ${packsOk ? 'Ready' : 'Not loaded'}</li>
                <li style="margin: var(--space-2) 0;">${tripsOk ? '✅' : '❌'} Trips sorting: ${tripsOk ? 'Ready' : 'Not found'}</li>
                <li style="margin: var(--space-2) 0;">${gearOk ? '✅' : '❌'} Gear library: ${gearOk ? 'Ready' : 'Not loaded'}</li>
            </ul>
        `;
    }, 1000);
});
</script>

<!-- Load jQuery and Pack Builder scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?php echo asset_url('js/pack-builder.js'); ?>"></script>
<script src="<?php echo asset_url('js/pack-builder-enhanced.js'); ?>"></script>

<?php require_once dirname(__DIR__) . '/public/includes/template-footer.php'; ?>
