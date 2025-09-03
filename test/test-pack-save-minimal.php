<?php
// Minimal Pack Save Test
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_auth();

$pageTitle = 'Minimal Pack Save Test';
require_once dirname(__DIR__) . '/includes/template-header.php';
?>

<div class="container mt-4">
    <h1>Minimal Pack Save Test</h1>
    
    <div class="card mb-4">
        <div class="card-body">
            <button class="btn btn-primary" onclick="testDirectSave()">Test Direct API Save</button>
            <button class="btn btn-info" onclick="testCollectData()">Test Collect Data</button>
            <button class="btn btn-success" onclick="testPackBuilderSave()">Test PackBuilder Save</button>
            <button class="btn btn-warning" onclick="checkScripts()">Check Scripts</button>
            <button class="btn btn-secondary" onclick="clearResults()">Clear</button>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <pre id="results" style="background: #1a1a1a; color: #00ff00; padding: 15px; max-height: 600px; overflow-y: auto; font-family: monospace;"></pre>
        </div>
    </div>
    
    <!-- Minimal Form -->
    <div style="display:none;">
        <input type="text" id="pack-name" value="Test Pack">
        <textarea id="pack-description">Test Description</textarea>
        <input type="number" id="pack-capacity" value="65">
        <input type="number" id="pack-base-weight" value="2000">
        <div id="sections-list">
            <div class="pack-section" data-section-id="main">
                <input type="text" class="section-name" value="Main">
                <div class="dropzone" data-section="main"></div>
            </div>
        </div>
    </div>
</div>

<script src="/BTT/vendor/jquery-3.7.1.min.js"></script>
<script src="/BTT/assets/js/api.js"></script>

<script>
function log(msg, data = null) {
    const results = document.getElementById('results');
    const timestamp = new Date().toLocaleTimeString();
    let output = `[${timestamp}] ${msg}\n`;
    if (data) output += JSON.stringify(data, null, 2) + '\n';
    results.textContent += output + '\n';
    results.scrollTop = results.scrollHeight;
}

function clearResults() {
    document.getElementById('results').textContent = '';
}

async function testDirectSave() {
    try {
        log('Testing direct API save...');
        
        const testData = {
            name: 'Test Pack ' + Date.now(),
            description: 'Test Description',
            capacity_l: 65,
            weight_empty_g: 2000,
            type: 'custom',
            sections: [
                {
                    id: 'main',
                    name: 'Main Compartment',
                    items: [],
                    order: 0
                }
            ]
        };
        
        log('Sending data:', testData);
        
        // Add timeout wrapper
        const timeoutPromise = new Promise((_, reject) => {
            setTimeout(() => reject(new Error('API call timed out after 5 seconds')), 5000);
        });
        
        const apiPromise = BttApi.backpacks.create(testData);
        
        const result = await Promise.race([apiPromise, timeoutPromise]);
        
        log('SUCCESS! Created pack:', result);
        
    } catch (error) {
        log('ERROR: ' + error.message, { stack: error.stack });
    }
}

function testCollectData() {
    try {
        log('Collecting form data...');
        
        const sections = [];
        $('.pack-section').each(function() {
            const sectionId = $(this).data('section-id');
            const sectionName = $(this).find('.section-name').val();
            sections.push({
                id: sectionId,
                name: sectionName,
                items: [],
                order: sections.length
            });
        });
        
        const packData = {
            name: $('#pack-name').val(),
            description: $('#pack-description').val(),
            capacity_l: parseFloat($('#pack-capacity').val()) || 65,
            weight_empty_g: parseFloat($('#pack-base-weight').val()) || 0,
            type: 'custom',
            sections: sections
        };
        
        log('Collected data:', packData);
        
    } catch (error) {
        log('ERROR collecting data: ' + error.message);
    }
}

async function testPackBuilderSave() {
    try {
        log('Testing PackBuilderCRUD.savePack()...');
        
        if (typeof PackBuilderCRUD === 'undefined') {
            log('ERROR: PackBuilderCRUD not loaded!');
            return;
        }
        
        // Set pack name with timestamp to make it unique
        $('#pack-name').val('Test Pack ' + Date.now());
        
        const result = await PackBuilderCRUD.savePack();
        log('SUCCESS! Pack saved:', result);
        
    } catch (error) {
        log('ERROR: ' + error.message, { 
            stack: error.stack,
            fullError: error 
        });
    }
}

function checkScripts() {
    log('Checking loaded scripts...');
    log('jQuery: ' + (typeof $ !== 'undefined' ? 'LOADED' : 'NOT LOADED'));
    log('BttApi: ' + (typeof BttApi !== 'undefined' ? 'LOADED' : 'NOT LOADED'));
    log('PackBuilderCRUD: ' + (typeof PackBuilderCRUD !== 'undefined' ? 'LOADED' : 'NOT LOADED'));
    
    // Check for any console errors
    if (window.console && window.console.error) {
        log('Check browser console for errors');
    }
}

// Auto check on load
window.addEventListener('load', function() {
    setTimeout(checkScripts, 500);
});
</script>

<script>
// Now try to load PackBuilderCRUD manually
(function($) {
    'use strict';
    
    // Simplified version of PackBuilderCRUD for testing
    window.PackBuilderCRUD = {
        state: {
            currentPackId: null,
            isDirty: false,
            sections: []
        },
        
        collectPackData: function() {
            console.log('Collecting pack data...');
            const sections = [];
            
            $('.pack-section').each(function() {
                const sectionId = $(this).data('section-id') || $(this).attr('data-section-id');
                const sectionName = $(this).find('.section-name').val();
                sections.push({
                    id: sectionId,
                    name: sectionName,
                    items: [],
                    order: sections.length
                });
            });
            
            return {
                name: $('#pack-name').val(),
                description: $('#pack-description').val(),
                capacity_l: parseFloat($('#pack-capacity').val()) || 65,
                weight_empty_g: parseFloat($('#pack-base-weight').val()) || 0,
                type: 'custom',
                sections: sections
            };
        },
        
        savePack: async function() {
            try {
                log('SavePack function started');
                console.log('SavePack called');
                
                const packData = this.collectPackData();
                log('Data collected from form');
                console.log('Data collected:', packData);
                
                if (!packData.name || packData.name.trim() === '') {
                    log('ERROR: Pack name is empty');
                    throw new Error('Please enter a pack name');
                }
                
                log('Calling BttApi.backpacks.create...');
                const response = await BttApi.backpacks.create(packData);
                
                log('API Response received:', response);
                console.log('Pack saved:', response);
                
                return response;
                
            } catch (error) {
                log('CATCH: Error in savePack: ' + error.message);
                console.error('Save error:', error);
                throw error;
            }
        }
    };
    
    log('✓ Minimal PackBuilderCRUD loaded');
    
})(jQuery);
</script>

<?php require_once dirname(__DIR__) . '/includes/template-footer.php'; ?>
