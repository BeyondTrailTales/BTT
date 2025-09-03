<?php
// Test Pack Save Isolated
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_auth();

$pageTitle = 'Test Pack Save Isolated';
require_once dirname(__DIR__) . '/includes/template-header.php';
?>

<div class="container mt-4">
    <h1>Isolated Pack Save Test</h1>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3>Pack Form</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label>Pack Name:</label>
                <input type="text" id="pack-name" class="form-control" value="Test Pack">
            </div>
            <div class="form-group">
                <label>Description:</label>
                <textarea id="pack-description" class="form-control">Test Description</textarea>
            </div>
            <div class="form-group">
                <label>Capacity (L):</label>
                <input type="number" id="pack-capacity" class="form-control" value="65">
            </div>
            <div class="form-group">
                <label>Base Weight (g):</label>
                <input type="number" id="pack-base-weight" class="form-control" value="2000">
            </div>
            
            <!-- Hidden sections for testing -->
            <div id="sections-list" style="display:none;">
                <div class="pack-section" data-section-id="main">
                    <input type="text" class="section-name" value="Main Compartment">
                    <div class="section-items dropzone" data-section="main"></div>
                </div>
            </div>
            
            <button class="btn btn-primary" onclick="testSavePack()">Test Save Pack</button>
            <button class="btn btn-warning" onclick="testCollectData()">Test Collect Data</button>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Results</h3>
        </div>
        <div class="card-body">
            <pre id="results" style="background: #f5f5f5; padding: 15px; border-radius: 5px; max-height: 600px; overflow-y: auto;"></pre>
        </div>
    </div>
</div>

<!-- Load minimal scripts -->
<script src="/BTT/vendor/jquery-3.7.1.min.js"></script>
<script src="/BTT/assets/js/api.js"></script>
<script src="/BTT/assets/js/pack-builder-crud.js"></script>

<script>
// Make functions globally available
window.addResult = function(message, data) {
    const results = document.getElementById('results');
    const timestamp = new Date().toLocaleTimeString();
    let output = `[${timestamp}] ${message}\n`;
    
    if (data !== null && data !== undefined) {
        output += JSON.stringify(data, null, 2) + '\n';
    }
    
    output += '\n' + '='.repeat(80) + '\n\n';
    results.textContent += output;
    results.scrollTop = results.scrollHeight;
};

window.testCollectData = function() {
    try {
        addResult('Testing collectPackData...');
        
        if (window.PackBuilderCRUD) {
            const data = window.PackBuilderCRUD.collectPackData();
            addResult('SUCCESS: Data collected', data);
        } else {
            addResult('ERROR: PackBuilderCRUD not loaded');
        }
        
    } catch (error) {
        addResult('ERROR: collectPackData failed', {
            message: error.message,
            stack: error.stack
        });
    }
};

window.testSavePack = async function() {
    try {
        addResult('Testing savePack...');
        
        if (window.PackBuilderCRUD) {
            // Call savePack directly
            await window.PackBuilderCRUD.savePack();
            addResult('SUCCESS: savePack completed');
        } else {
            addResult('ERROR: PackBuilderCRUD not loaded');
        }
        
    } catch (error) {
        addResult('ERROR: savePack failed', {
            message: error.message,
            stack: error.stack
        });
    }
};

// Check initialization
window.addEventListener('load', function() {
    setTimeout(function() {
        if (typeof BttApi !== 'undefined') {
            addResult('✓ BttApi loaded');
        } else {
            addResult('✗ BttApi NOT loaded');
        }
        
        if (typeof PackBuilderCRUD !== 'undefined') {
            addResult('✓ PackBuilderCRUD loaded');
        } else {
            addResult('✗ PackBuilderCRUD NOT loaded');
        }
    }, 1000);
});
</script>

<?php require_once dirname(__DIR__) . '/includes/template-footer.php'; ?>
