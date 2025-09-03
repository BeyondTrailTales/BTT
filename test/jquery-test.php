<?php
/**
 * jQuery and JavaScript Libraries Test
 * Tests that all required libraries are loading correctly
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';
$pageTitle = 'jQuery Test';
$pageId = 'jquery-test';
require_once dirname(__DIR__) . '/public/includes/template-header.php';
?>

<div class="card">
    <h1>JavaScript Libraries Test</h1>
    
    <div class="test-results" style="padding: 20px;">
        <h2>Library Status:</h2>
        <ul id="library-status" style="list-style: none; padding: 0;">
            <li id="jquery-status">⏳ Checking jQuery...</li>
            <li id="sortable-status">⏳ Checking Sortable.js...</li>
            <li id="app-status">⏳ Checking app.js...</li>
        </ul>
        
        <h2 style="margin-top: 30px;">Console Errors:</h2>
        <div id="console-errors" style="background: #1a1a1a; padding: 10px; border-radius: 5px; min-height: 100px; color: #ff6b6b;">
            Checking for errors...
        </div>
        
        <h2 style="margin-top: 30px;">Test Results:</h2>
        <div id="test-results" style="background: #1a1a1a; padding: 10px; border-radius: 5px; min-height: 100px;">
            Running tests...
        </div>
    </div>
</div>

<script>
// Test script that runs after page load
document.addEventListener('DOMContentLoaded', function() {
    const results = [];
    const errors = [];
    
    // Store original console.error
    const originalError = console.error;
    console.error = function() {
        errors.push(Array.from(arguments).join(' '));
        originalError.apply(console, arguments);
    };
    
    // Test jQuery
    const jqueryStatus = document.getElementById('jquery-status');
    if (typeof jQuery !== 'undefined') {
        jqueryStatus.innerHTML = '✅ jQuery ' + jQuery.fn.jquery + ' loaded successfully';
        results.push('jQuery version ' + jQuery.fn.jquery + ' is available');
        
        // Test jQuery functionality
        jQuery(function() {
            results.push('jQuery document ready works');
        });
    } else {
        jqueryStatus.innerHTML = '❌ jQuery not loaded';
        results.push('ERROR: jQuery is not defined');
    }
    
    // Test Sortable.js
    const sortableStatus = document.getElementById('sortable-status');
    if (typeof Sortable !== 'undefined') {
        sortableStatus.innerHTML = '✅ Sortable.js loaded successfully';
        results.push('Sortable.js is available');
    } else {
        sortableStatus.innerHTML = '❌ Sortable.js not loaded';
        results.push('ERROR: Sortable is not defined');
    }
    
    // Test app.js
    const appStatus = document.getElementById('app-status');
    if (typeof window.BTT !== 'undefined') {
        appStatus.innerHTML = '✅ app.js loaded and BTT object initialized';
        results.push('BTT configuration object is available');
        results.push('BTT.baseUrl: ' + window.BTT.baseUrl);
    } else {
        appStatus.innerHTML = '⚠️ BTT object not found (might be normal if app.js loads later)';
        results.push('WARNING: BTT object not yet initialized');
    }
    
    // Display results
    setTimeout(function() {
        document.getElementById('test-results').innerHTML = 
            '<pre style="color: #4ade80;">' + results.join('\n') + '</pre>';
            
        if (errors.length > 0) {
            document.getElementById('console-errors').innerHTML = 
                '<pre>' + errors.join('\n') + '</pre>';
        } else {
            document.getElementById('console-errors').innerHTML = 
                '<span style="color: #4ade80;">No console errors detected!</span>';
        }
    }, 500);
});
</script>

<?php
require_once dirname(__DIR__) . '/public/includes/template-footer.php';
?>
