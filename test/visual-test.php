<?php
/**
 * Visual Test Page - Verify Forest Theme Improvements
 * Tests: Form readability, z-index layering, responsive design, ADA compliance
 */

// Load bootstrap
require_once dirname(__DIR__) . '/app/bootstrap.php';

// Set page metadata
$pageTitle = 'Visual Test';
$pageDescription = 'Testing improved design system components';
$pageId = 'visual-test';

// Include the unified template header
require_once dirname(__DIR__) . '/public/includes/template-header.php';
?>

<div class="container">
    <h1 class="text-gradient hero-title">Visual Test Suite</h1>
    <p class="hero-subtitle">Verifying all design improvements are working properly</p>

    <!-- Form Controls Test Section -->
    <section class="section">
        <h2 class="section-title">📝 Form Controls (Enhanced Readability)</h2>
        
        <div class="grid-responsive md:cols-2 gap-6">
            <!-- Text Inputs -->
            <div class="card glass-panel">
                <h3>Text Inputs</h3>
                <div class="stack-md">
                    <div class="form-group">
                        <label for="test-text" class="form-label">Text Input</label>
                        <input type="text" id="test-text" class="form-control" placeholder="Enter some text here...">
                        <div class="form-help">Helper text with good contrast</div>
                    </div>

                    <div class="form-group">
                        <label for="test-email" class="form-label">Email Input</label>
                        <input type="email" id="test-email" class="form-control" placeholder="your@email.com">
                    </div>

                    <div class="form-group">
                        <label for="test-password" class="form-label">Password Input</label>
                        <input type="password" id="test-password" class="form-control" placeholder="••••••••">
                    </div>

                    <div class="form-group">
                        <label for="test-disabled" class="form-label">Disabled Input</label>
                        <input type="text" id="test-disabled" class="form-control" placeholder="This is disabled" disabled>
                    </div>
                </div>
            </div>

            <!-- Select Dropdown -->
            <div class="card glass-panel">
                <h3>Select Dropdowns</h3>
                <div class="stack-md">
                    <div class="form-group">
                        <label for="test-select" class="form-label">Select with Options</label>
                        <select id="test-select" class="form-control">
                            <option value="">Choose an option...</option>
                            <optgroup label="Difficulty Levels">
                                <option value="easy">Easy Trail</option>
                                <option value="moderate">Moderate Trail</option>
                                <option value="hard">Hard Trail</option>
                                <option value="expert">Expert Trail</option>
                            </optgroup>
                            <optgroup label="Trail Types">
                                <option value="loop">Loop Trail</option>
                                <option value="out-back">Out and Back</option>
                                <option value="point">Point to Point</option>
                            </optgroup>
                        </select>
                        <div class="form-help">All text should be clearly readable</div>
                    </div>

                    <div class="form-group">
                        <label for="test-textarea" class="form-label">Textarea</label>
                        <textarea id="test-textarea" class="form-control" rows="4" placeholder="Enter a longer description here..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="test-date" class="form-label">Date Input</label>
                        <input type="date" id="test-date" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <!-- Checkboxes and Radios -->
        <div class="card glass-panel mt-4">
            <h3>Checkboxes & Radio Buttons</h3>
            <div class="grid-responsive md:cols-2 gap-6">
                <div>
                    <h4 class="text-sm text-forest mb-3">Checkboxes</h4>
                    <div class="stack-sm">
                        <div class="form-check">
                            <input type="checkbox" id="check1" class="form-check-input" checked>
                            <label for="check1" class="form-check-label">Tent</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" id="check2" class="form-check-input">
                            <label for="check2" class="form-check-label">Sleeping Bag</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" id="check3" class="form-check-input">
                            <label for="check3" class="form-check-label">Cooking Gear</label>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="text-sm text-forest mb-3">Radio Buttons</h4>
                    <div class="stack-sm">
                        <div class="form-check">
                            <input type="radio" id="radio1" name="trip-type" class="form-check-input" checked>
                            <label for="radio1" class="form-check-label">Day Hike</label>
                        </div>
                        <div class="form-check">
                            <input type="radio" id="radio2" name="trip-type" class="form-check-input">
                            <label for="radio2" class="form-check-label">Overnight</label>
                        </div>
                        <div class="form-check">
                            <input type="radio" id="radio3" name="trip-type" class="form-check-input">
                            <label for="radio3" class="form-check-label">Multi-day</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Z-Index Layering Test -->
    <section class="section">
        <h2 class="section-title">📊 Z-Index Layering Test</h2>
        
        <div class="card glass-panel" style="position: relative; min-height: 200px;">
            <p>Test the layering of different elements:</p>
            
            <!-- Base layer card -->
            <div style="position: absolute; top: 20px; left: 20px; width: 150px; height: 100px; background: var(--glass-bg); border: 1px solid var(--glass-border); padding: 1rem; border-radius: 8px;" class="z-0">
                Base Layer (z-0)
            </div>
            
            <!-- Dropdown layer -->
            <div style="position: absolute; top: 40px; left: 100px; width: 150px; height: 100px; background: var(--bg-elevated); border: 1px solid var(--forest-mint); padding: 1rem; border-radius: 8px;" class="z-dropdown">
                Dropdown (z-dropdown)
            </div>
            
            <!-- Modal layer -->
            <div style="position: absolute; top: 60px; left: 180px; width: 150px; height: 100px; background: var(--bg-elevated-contrast); border: 1px solid var(--forest-leaf); padding: 1rem; border-radius: 8px;" class="z-modal">
                Modal (z-modal)
            </div>
        </div>
    </section>

    <!-- Buttons and Actions -->
    <section class="section">
        <h2 class="section-title">🎯 Buttons & Actions</h2>
        
        <div class="card glass-panel">
            <h3 class="mb-4">Button Variants</h3>
            <div class="btn-group mb-4">
                <button class="btn btn-primary">Primary Action</button>
                <button class="btn btn-secondary">Secondary</button>
                <button class="btn btn-danger">Danger</button>
                <button class="btn btn-primary" disabled>Disabled</button>
            </div>
            
            <h3 class="mb-4">Button Sizes</h3>
            <div class="btn-group">
                <button class="btn btn-sm btn-primary">Small</button>
                <button class="btn btn-primary">Default</button>
                <button class="btn btn-lg btn-primary">Large</button>
            </div>
        </div>
    </section>

    <!-- Dropdown Test -->
    <section class="section">
        <h2 class="section-title">📋 Dropdown Menu</h2>
        
        <div class="card glass-panel">
            <div class="dropdown" style="position: relative;">
                <button class="btn btn-secondary" onclick="toggleDropdown(this)">
                    Open Dropdown ▼
                </button>
                <div class="dropdown-menu" id="test-dropdown">
                    <a href="#" class="dropdown-item">View Profile</a>
                    <a href="#" class="dropdown-item active">Settings</a>
                    <a href="#" class="dropdown-item">Help & Support</a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">Sign Out</a>
                    <a href="#" class="dropdown-item disabled">Disabled Item</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Cards and Spacing -->
    <section class="section">
        <h2 class="section-title">📦 Cards & Spacing</h2>
        
        <div class="grid-responsive md:cols-3 gap-4">
            <div class="card glass-panel card-padding-sm">
                <h3 class="text-forest">Small Padding</h3>
                <p class="text-secondary">This card uses card-padding-sm for tighter spacing.</p>
            </div>
            
            <div class="card glass-panel card-padding">
                <h3 class="text-forest">Default Padding</h3>
                <p class="text-secondary">This card uses the default card-padding class.</p>
            </div>
            
            <div class="card glass-panel card-padding-lg">
                <h3 class="text-forest">Large Padding</h3>
                <p class="text-secondary">This card uses card-padding-lg for more breathing room.</p>
            </div>
        </div>
    </section>

    <!-- Mobile Responsiveness Test -->
    <section class="section">
        <h2 class="section-title">📱 Mobile Responsiveness</h2>
        
        <div class="card glass-panel">
            <p class="mb-4">Resize your browser to test responsive behavior:</p>
            
            <div class="grid-responsive sm:cols-2 md:cols-3 lg:cols-4 gap-3">
                <div class="p-4 bg-elevated rounded-lg text-center">
                    <div class="text-3xl mb-2">🏕️</div>
                    <div class="text-sm">Camping</div>
                </div>
                <div class="p-4 bg-elevated rounded-lg text-center">
                    <div class="text-3xl mb-2">🥾</div>
                    <div class="text-sm">Hiking</div>
                </div>
                <div class="p-4 bg-elevated rounded-lg text-center">
                    <div class="text-3xl mb-2">🎒</div>
                    <div class="text-sm">Backpacking</div>
                </div>
                <div class="p-4 bg-elevated rounded-lg text-center">
                    <div class="text-3xl mb-2">🏔️</div>
                    <div class="text-sm">Mountaineering</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Accessibility Test -->
    <section class="section">
        <h2 class="section-title">♿ Accessibility Features</h2>
        
        <div class="card glass-panel">
            <div class="stack-md">
                <div>
                    <h3>Focus States</h3>
                    <p>Tab through these elements to see focus indicators:</p>
                    <div class="btn-group mt-3">
                        <button class="btn btn-primary">Tab to me</button>
                        <input type="text" class="form-control" placeholder="Then to me" style="max-width: 200px;">
                        <a href="#" class="btn btn-secondary">Then here</a>
                    </div>
                </div>
                
                <div>
                    <h3>Screen Reader Labels</h3>
                    <button class="btn btn-primary" aria-label="Save your trip details">
                        <span aria-hidden="true">💾</span> Save
                    </button>
                    <button class="btn btn-secondary" aria-label="Delete this item">
                        <span aria-hidden="true">🗑️</span> Delete
                    </button>
                </div>
                
                <div>
                    <h3>High Contrast</h3>
                    <p>All text should meet WCAG AA standards for contrast ratios.</p>
                    <div class="p-4 mt-3" style="background: var(--bg-elevated-contrast); border-radius: 8px;">
                        <p style="color: var(--form-text);">Primary text on elevated background</p>
                        <p style="color: var(--form-placeholder);">Placeholder text color</p>
                        <p style="color: var(--text-muted);">Muted text color</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Performance Notes -->
    <section class="section">
        <h2 class="section-title">⚡ Performance Optimizations</h2>
        
        <div class="card glass-panel">
            <ul class="stack-sm">
                <li>✅ Reduced blur effects on mobile devices</li>
                <li>✅ GPU acceleration for animations</li>
                <li>✅ Optimized touch targets (44px minimum)</li>
                <li>✅ Content visibility for off-screen elements</li>
                <li>✅ Efficient CSS variable usage</li>
                <li>✅ Responsive images with lazy loading</li>
            </ul>
        </div>
    </section>
</div>

<style>
/* Test-specific styles */
.bg-elevated {
    background: var(--bg-elevated);
}

.rounded-lg {
    border-radius: var(--radius-lg);
}

.text-3xl {
    font-size: 1.875rem;
}

.mb-2 {
    margin-bottom: 0.5rem;
}

.mb-3 {
    margin-bottom: 0.75rem;
}

.mb-4 {
    margin-bottom: 1rem;
}

.mt-3 {
    margin-top: 0.75rem;
}

.mt-4 {
    margin-top: 1rem;
}

.p-4 {
    padding: 1rem;
}

.text-forest {
    color: var(--forest-mint);
}

.text-center {
    text-align: center;
}

.max-width-200 {
    max-width: 200px;
}
</style>

<script>
// Toggle dropdown for testing
function toggleDropdown(button) {
    const dropdown = button.nextElementSibling;
    const isOpen = dropdown.classList.contains('show');
    
    // Close all dropdowns
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.classList.remove('show');
    });
    
    // Toggle this dropdown
    if (!isOpen) {
        dropdown.classList.add('show');
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});

// Mobile menu toggle
function toggleMobileMenu(button) {
    const menu = document.getElementById('mobile-nav-menu');
    const isOpen = menu.classList.contains('active');
    
    menu.classList.toggle('active');
    button.setAttribute('aria-expanded', !isOpen);
    menu.setAttribute('aria-hidden', isOpen);
}

// Test focus trap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});
</script>

<?php
// Include the unified template footer
require_once dirname(__DIR__) . '/public/includes/template-footer.php';
?>
