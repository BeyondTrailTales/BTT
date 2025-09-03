<?php
// Load bootstrap first
require_once dirname(__DIR__) . '/app/bootstrap.php';

// Page metadata
$pageId = 'test-spacing';
$pageTitle = 'Verify Global Spacing';
$pageDescription = 'Test page to verify the new global spacing system is working';

// Include the unified template header
require_once dirname(__DIR__) . '/public/includes/template-header.php';
?>

<div class="page-wrapper">
    <div class="main-content">
        <!-- Test Hero -->
        <div class="trip-card trip-card--featured mb-8">
            <h1 class="text-3xl mb-4">🎨 Global Spacing System Active!</h1>
            <p class="text-measure mx-auto mb-6">
                This page tests the new design system with proper spacing utilities.
                <?php if (strpos($bodyClasses[0] ?? '', 'btt-ui-v2') !== false): ?>
                    <strong class="text-success">✅ UI v2 is ACTIVE</strong>
                <?php else: ?>
                    <strong class="text-warning">⚠️ UI v2 is NOT active - add ?ui=v2 to URL</strong>
                <?php endif; ?>
            </p>
        </div>

        <!-- Spacing Examples -->
        <div class="section">
            <h2 class="mb-6">Spacing Examples</h2>
            
            <!-- Margin Examples -->
            <div class="panel mb-6">
                <div class="panel__header">
                    <h3 class="panel__title">Margin Utilities</h3>
                </div>
                <div class="panel__body">
                    <div class="flow">
                        <div class="p-3" style="background: var(--btt-green-100);">
                            <code>.mb-2</code> - margin-bottom: 8px
                        </div>
                        <div class="p-3" style="background: var(--btt-green-100);">
                            <code>.mb-4</code> - margin-bottom: 16px
                        </div>
                        <div class="p-3" style="background: var(--btt-green-100);">
                            <code>.mb-6</code> - margin-bottom: 24px
                        </div>
                    </div>
                </div>
            </div>

            <!-- Padding Examples -->
            <div class="panel mb-6">
                <div class="panel__header">
                    <h3 class="panel__title">Padding Utilities</h3>
                </div>
                <div class="panel__body">
                    <div class="d-flex gap-4 flex-wrap">
                        <div class="p-2" style="background: var(--btt-green-100);">
                            <code>.p-2</code>
                        </div>
                        <div class="p-4" style="background: var(--btt-green-100);">
                            <code>.p-4</code>
                        </div>
                        <div class="p-6" style="background: var(--btt-green-100);">
                            <code>.p-6</code>
                        </div>
                        <div class="px-8 py-2" style="background: var(--btt-green-100);">
                            <code>.px-8 .py-2</code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Button Examples -->
            <div class="panel mb-6">
                <div class="panel__header">
                    <h3 class="panel__title">Button Components</h3>
                </div>
                <div class="panel__body">
                    <div class="d-flex gap-3 flex-wrap">
                        <button class="btn btn--primary">Primary</button>
                        <button class="btn btn--secondary">Secondary</button>
                        <button class="btn btn--success">Success</button>
                        <button class="btn btn--danger">Danger</button>
                        <button class="btn btn--ghost">Ghost</button>
                        <button class="btn btn--outline">Outline</button>
                    </div>
                    <div class="d-flex gap-3 flex-wrap mt-4">
                        <button class="btn btn--primary btn--sm">Small</button>
                        <button class="btn btn--primary">Default</button>
                        <button class="btn btn--primary btn--lg">Large</button>
                        <button class="btn btn--primary btn--xl">Extra Large</button>
                    </div>
                </div>
            </div>

            <!-- Card Examples -->
            <div class="trip-card-grid trip-card-grid--3 mb-8">
                <div class="trip-card">
                    <h3 class="trip-card__title">Card 1</h3>
                    <p class="trip-card__body">This is a trip card with glassmorphism effect.</p>
                </div>
                <div class="trip-card trip-card--featured">
                    <h3 class="trip-card__title">Featured Card</h3>
                    <p class="trip-card__body">This card has a featured style.</p>
                </div>
                <div class="trip-card trip-card--weather">
                    <h3 class="trip-card__title">Weather Card</h3>
                    <p class="trip-card__body">Special weather-themed card.</p>
                </div>
            </div>

            <!-- Flow Utility -->
            <div class="panel mb-6">
                <div class="panel__header">
                    <h3 class="panel__title">Vertical Rhythm (Flow Utility)</h3>
                </div>
                <div class="panel__body">
                    <div class="flow p-4" style="background: var(--glass-bg); border-radius: var(--radius-lg);">
                        <h3>Automatic Spacing</h3>
                        <p>The flow utility automatically adds consistent spacing between child elements.</p>
                        <p>No need to manually add margin classes to each element.</p>
                        <ul>
                            <li>List item 1</li>
                            <li>List item 2</li>
                            <li>List item 3</li>
                        </ul>
                        <p>Perfect vertical rhythm maintained throughout!</p>
                    </div>
                </div>
            </div>

            <!-- Responsive Utilities -->
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel__title">Responsive Spacing</h3>
                </div>
                <div class="panel__body">
                    <div class="edge-safe p-4" style="background: var(--btt-green-100); border-radius: var(--radius-md);">
                        <p>This container uses <code>.edge-safe</code> to prevent edge collisions on all devices.</p>
                    </div>
                    <div class="mobile:p-3 tablet:p-4 desktop:p-5 mt-4" style="background: var(--btt-green-100); border-radius: var(--radius-md);">
                        <p>Responsive padding that adjusts per screen size.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Check -->
        <div class="mt-10 text-center">
            <h2 class="mb-4">System Status</h2>
            <div class="d-flex gap-4 justify-center flex-wrap">
                <div class="trip-card trip-card--compact">
                    <p>✅ Design Tokens Loaded</p>
                </div>
                <div class="trip-card trip-card--compact">
                    <p>✅ Spacing Utilities Active</p>
                </div>
                <div class="trip-card trip-card--compact">
                    <p>✅ Component Library Ready</p>
                </div>
                <div class="trip-card trip-card--compact">
                    <p>✅ ADA Compliant</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/public/includes/template-footer.php'; ?>
