<?php
// Test Backpack API Web Interface
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_auth();

$pageTitle = 'Backpack API Test';
require_once dirname(__DIR__) . '/includes/template-header.php';
?>

<div class="container mt-4">
    <h1>Backpack API Test</h1>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3>Test API Endpoints</h3>
        </div>
        <div class="card-body">
            <button class="btn btn-primary" onclick="testListBackpacks()">Test List Backpacks</button>
            <button class="btn btn-info" onclick="testCreateBackpack()">Test Create Backpack</button>
            <button class="btn btn-warning" onclick="testGetBackpack()">Test Get Backpack</button>
            <button class="btn btn-secondary" onclick="clearResults()">Clear Results</button>
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

<script src="/BTT/vendor/jquery-3.7.1.min.js"></script>
<script src="/BTT/assets/js/api.js"></script>

<script>
function addResult(message, data = null) {
    const results = document.getElementById('results');
    const timestamp = new Date().toLocaleTimeString();
    let output = `[${timestamp}] ${message}\n`;
    
    if (data !== null) {
        output += JSON.stringify(data, null, 2) + '\n';
    }
    
    output += '\n' + '='.repeat(80) + '\n\n';
    results.textContent += output;
    results.scrollTop = results.scrollHeight;
}

function clearResults() {
    document.getElementById('results').textContent = '';
}

async function testListBackpacks() {
    try {
        addResult('Testing BttApi.backpacks.list()...');
        
        const result = await BttApi.backpacks.list();
        
        addResult('SUCCESS: List backpacks returned:', result);
        
        // Check if result is properly unwrapped
        if (Array.isArray(result)) {
            addResult('✓ Result is an array (properly unwrapped)');
        } else if (result && result.data) {
            addResult('⚠ Result still has data wrapper - not properly unwrapped', result);
        } else {
            addResult('✓ Result structure:', typeof result);
        }
        
    } catch (error) {
        addResult('ERROR: List backpacks failed', {
            message: error.message,
            stack: error.stack
        });
    }
}

async function testCreateBackpack() {
    try {
        addResult('Testing BttApi.backpacks.create()...');
        
        const testPack = {
            name: 'Test Pack ' + Date.now(),
            description: 'Created by API test',
            capacity_l: 65,
            weight_empty_g: 2000,
            sections: [
                {
                    id: 'main',
                    name: 'Main Compartment',
                    order: 0,
                    color: '#10b981',
                    items: [
                        {
                            id: 'test-item-1',
                            name: 'Test Tent',
                            weight_g: 1500,
                            quantity: 1,
                            category: 'shelter'
                        },
                        {
                            id: 'test-item-2', 
                            name: 'Test Sleeping Bag',
                            weight_g: 800,
                            quantity: 1,
                            category: 'sleep'
                        }
                    ]
                }
            ]
        };
        
        const result = await BttApi.backpacks.create(testPack);
        
        addResult('SUCCESS: Create backpack returned:', result);
        
        // Store ID for get test
        if (result && result.id) {
            window.testPackId = result.id;
            addResult('✓ Created pack with ID: ' + result.id);
        } else if (result && result.data && result.data.id) {
            window.testPackId = result.data.id;
            addResult('⚠ Result has data wrapper, pack ID: ' + result.data.id);
        }
        
    } catch (error) {
        addResult('ERROR: Create backpack failed', {
            message: error.message,
            stack: error.stack
        });
    }
}

async function testGetBackpack() {
    try {
        if (!window.testPackId) {
            addResult('ERROR: No test pack ID available. Create a pack first.');
            return;
        }
        
        addResult('Testing BttApi.backpacks.get(' + window.testPackId + ')...');
        
        const result = await BttApi.backpacks.get(window.testPackId);
        
        addResult('SUCCESS: Get backpack returned:', result);
        
        // Check structure
        if (result && result.id) {
            addResult('✓ Result has pack data directly (properly unwrapped)');
            if (result.sections) {
                addResult('✓ Pack has sections:', result.sections);
            }
        } else if (result && result.data) {
            addResult('⚠ Result still has data wrapper', result);
        }
        
    } catch (error) {
        addResult('ERROR: Get backpack failed', {
            message: error.message,
            stack: error.stack
        });
    }
}

// Test on load
window.addEventListener('load', function() {
    setTimeout(function() {
        if (typeof BttApi !== 'undefined') {
            addResult('BttApi is defined');
            // Automatically test list on load
            testListBackpacks();
        } else {
            addResult('ERROR: BttApi is not defined');
        }
    }, 1000);
});
</script>

<?php require_once dirname(__DIR__) . '/includes/template-footer.php'; ?>
