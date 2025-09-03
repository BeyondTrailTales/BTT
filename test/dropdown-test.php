<?php
// Test page for dropdown arrow fix
require_once dirname(__DIR__) . '/app/bootstrap.php';

$pageId = 'dropdown-test';
$pageTitle = 'Dropdown Arrow Fix Test';
$pageDescription = 'Testing the dropdown arrow display issue fix';

// Include the template header
require_once dirname(__DIR__) . '/public/includes/template-header.php';
?>

<div class="container">
    <h1>Dropdown Arrow Fix Test</h1>
    <p>This page tests the dropdown arrow display issue to ensure it's fixed.</p>
    
    <div style="max-width: 600px; margin: 2rem auto;">
        <h2>Test Dropdowns</h2>
        
        <!-- Normal Dropdown -->
        <div class="form-group" style="margin-bottom: 2rem;">
            <label for="test-dropdown-1">Normal Dropdown</label>
            <select id="test-dropdown-1" class="form-control">
                <option value="recent">Recently Created</option>
                <option value="name">Name</option>
                <option value="date">Start Date</option>
                <option value="modified">Last Modified</option>
            </select>
        </div>
        
        <!-- Dropdown with validation class -->
        <div class="form-group" style="margin-bottom: 2rem;">
            <label for="test-dropdown-2">Valid Dropdown</label>
            <select id="test-dropdown-2" class="form-control is-valid">
                <option value="">Select an option...</option>
                <option value="option1">Option 1</option>
                <option value="option2">Option 2</option>
                <option value="option3">Option 3</option>
            </select>
        </div>
        
        <!-- Invalid dropdown -->
        <div class="form-group" style="margin-bottom: 2rem;">
            <label for="test-dropdown-3">Invalid Dropdown</label>
            <select id="test-dropdown-3" class="form-control is-invalid">
                <option value="">Please select...</option>
                <option value="option1">Option 1</option>
                <option value="option2">Option 2</option>
            </select>
        </div>
        
        <!-- Disabled dropdown -->
        <div class="form-group" style="margin-bottom: 2rem;">
            <label for="test-dropdown-4">Disabled Dropdown</label>
            <select id="test-dropdown-4" class="form-control" disabled>
                <option value="disabled">This is disabled</option>
                <option value="option1">Option 1</option>
                <option value="option2">Option 2</option>
            </select>
        </div>
        
        <!-- Dropdown with optgroup -->
        <div class="form-group" style="margin-bottom: 2rem;">
            <label for="test-dropdown-5">Dropdown with Groups</label>
            <select id="test-dropdown-5" class="form-control">
                <option value="">Choose category...</option>
                <optgroup label="Recent">
                    <option value="recent-1">Recently Created</option>
                    <option value="recent-2">Recently Modified</option>
                    <option value="recent-3">Recently Viewed</option>
                </optgroup>
                <optgroup label="Alphabetical">
                    <option value="a-z">A to Z</option>
                    <option value="z-a">Z to A</option>
                </optgroup>
                <optgroup label="Date">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                </optgroup>
            </select>
        </div>
        
        <!-- Multiple select (should not have arrow) -->
        <div class="form-group" style="margin-bottom: 2rem;">
            <label for="test-dropdown-6">Multiple Select (no arrow expected)</label>
            <select id="test-dropdown-6" class="form-control" multiple size="4">
                <option value="option1">Option 1</option>
                <option value="option2">Option 2</option>
                <option value="option3">Option 3</option>
                <option value="option4">Option 4</option>
            </select>
        </div>
        
        <div class="alert" style="background: var(--bg-elevated); padding: 1.5rem; border-radius: 0.5rem; margin-top: 2rem;">
            <h3 style="color: var(--forest-mint); margin-bottom: 1rem;">✅ Expected Results:</h3>
            <ul style="color: var(--text-primary); line-height: 1.8;">
                <li>Each dropdown should show only ONE arrow on the right side</li>
                <li>The arrow should be properly positioned and not repeated</li>
                <li>Valid dropdown should have a green-tinted arrow</li>
                <li>Invalid dropdown should have a red-tinted arrow</li>
                <li>Disabled dropdown should have a grayed arrow</li>
                <li>Hovering should change the arrow color to mint green</li>
                <li>Focus should also change the arrow to mint green</li>
                <li>Multiple select should NOT have any arrow</li>
            </ul>
        </div>
        
        <div style="margin-top: 2rem; padding: 1rem; background: var(--bg-elevated); border-radius: 0.5rem;">
            <p style="color: var(--forest-leaf);"><strong>Fix Applied:</strong> The dropdown-fix.css file has been added to resolve the repeating arrow issue.</p>
            <p style="color: var(--text-secondary); margin-top: 0.5rem;">If arrows are still repeating, clear your browser cache and reload the page.</p>
        </div>
    </div>
</div>

<script>
// Test interaction states
document.querySelectorAll('select').forEach(select => {
    select.addEventListener('focus', function() {
        console.log('Focus on:', this.id);
    });
    
    select.addEventListener('change', function() {
        console.log('Changed:', this.id, 'to', this.value);
    });
});
</script>

<?php
// Include the template footer
require_once dirname(__DIR__) . '/public/includes/template-footer.php';
?>
