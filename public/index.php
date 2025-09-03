<?php
// Load bootstrap first
require_once dirname(__DIR__) . '/app/bootstrap.php';

// Page metadata
$pageId = 'home';
$pageTitle = 'Adventure Awaits';
$pageDescription = 'Plan your outdoor adventures with smart backpack management and trip planning';

// Include the unified template header
require_once __DIR__ . '/includes/template-header.php';
?>

<!-- Hero Section -->
<div class="trip-card trip-card--featured text-center py-10 px-5 mb-8">
    <h1 class="text-3xl mb-4">🌲 Welcome back, Adventurer! 🏔️</h1>
    <p class="text-lg mb-6 text-measure mx-auto">Ready for your next adventure?</p>
    <div class="d-flex gap-4 justify-center flex-wrap">
        <a href="<?php echo BTT_PUBLIC_URL; ?>/trips.php" class="btn btn--primary btn--lg">
            <span>🗺️</span> Plan New Trip
        </a>
        <a href="<?php echo BTT_PUBLIC_URL; ?>/backpacks.php" class="btn btn--secondary btn--lg">
            <span>🎒</span> Create Backpack
        </a>
    </div>
</div>

<!-- Main Features Grid -->
<div class="trip-card-grid trip-card-grid--3 mb-8">
    <!-- Plan Trip Card -->
    <div class="trip-card trip-card--clickable">
        <div class="trip-card__header text-center">
            <div class="text-3xl mb-3">🗺️</div>
            <h2 class="trip-card__title">Plan New Trip</h2>
        </div>
        <div class="trip-card__body text-center">
            <p class="mb-5">Start planning your next adventure</p>
        </div>
        <div class="trip-card__actions">
            <a href="<?php echo BTT_PUBLIC_URL; ?>/trips.php" class="btn btn--primary btn--block">
                Start Planning
            </a>
        </div>
    </div>
    
    <!-- Create Backpack Card -->
    <div class="trip-card trip-card--clickable">
        <div class="trip-card__header text-center">
            <div class="text-3xl mb-3">🎒</div>
            <h2 class="trip-card__title">Create Backpack</h2>
        </div>
        <div class="trip-card__body text-center">
            <p class="mb-5">Configure gear for your journey</p>
        </div>
        <div class="trip-card__actions">
            <a href="<?php echo BTT_PUBLIC_URL; ?>/backpacks.php" class="btn btn--primary btn--block">
                Create Backpack
            </a>
        </div>
    </div>
    
    <!-- Test Features Card -->
    <div class="trip-card trip-card--clickable">
        <div class="trip-card__header text-center">
            <div class="text-3xl mb-3">🧪</div>
            <h2 class="trip-card__title">Test Features</h2>
        </div>
        <div class="trip-card__body text-center">
            <p class="mb-5">Try out gamification systems</p>
        </div>
        <div class="trip-card__actions">
            <a href="<?php echo BTT_PUBLIC_URL; ?>/test/" class="btn btn--secondary btn--block">
                Explore Tests
            </a>
        </div>
    </div>
</div>

<!-- Adventure Stats -->
<div class="trip-card mb-8">
    <h2 class="text-xl text-center mb-6">🏆 Your Adventure Stats</h2>
    <div class="trip-card__stats">
        <div class="trip-card__stat">
            <div class="text-2xl mb-2">🗺️</div>
            <div class="trip-card__stat-value">2</div>
            <div class="trip-card__stat-label">Trips Planned</div>
        </div>
        <div class="trip-card__stat">
            <div class="text-2xl mb-2">🎒</div>
            <div class="trip-card__stat-value">4</div>
            <div class="trip-card__stat-label">Backpacks Created</div>
        </div>
        <div class="trip-card__stat">
            <div class="text-2xl mb-2">⭐</div>
            <div class="trip-card__stat-value">3</div>
            <div class="trip-card__stat-label">Current Level</div>
        </div>
        <div class="trip-card__stat">
            <div class="text-2xl mb-2">🔥</div>
            <div class="trip-card__stat-value">2</div>
            <div class="trip-card__stat-label">Day Streak</div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="panel panel--glass mb-8">
    <div class="panel__header">
        <h2 class="panel__title">📅 Recent Activity</h2>
    </div>
    <div class="panel__body">
        <div class="stack stack--md">
            <div class="trip-card trip-card--compact">
                <div class="d-flex justify-between align-center">
                    <div>
                        <h3 class="mb-1">Jacks Trip</h3>
                        <span class="text-sm">🚫 Not set</span>
                    </div>
                    <a href="<?php echo BTT_PUBLIC_URL; ?>/trips.php" class="btn btn--ghost btn--sm">View</a>
                </div>
            </div>
            <div class="trip-card trip-card--compact">
                <div class="d-flex justify-between align-center">
                    <div>
                        <h3 class="mb-1">Emily Trip</h3>
                        <span class="text-sm">📅 10/14/2025</span>
                    </div>
                    <a href="<?php echo BTT_PUBLIC_URL; ?>/trips.php" class="btn btn--ghost btn--sm">View</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Links Footer -->
<div class="text-center py-8 mt-10" style="border-top: 1px solid var(--glass-border);">
    <h3 class="mb-5">Quick Links</h3>
    <div class="inline inline--lg justify-center">
        <a href="<?php echo BTT_PUBLIC_URL; ?>/dashboard.php" class="text-secondary">Dashboard</a>
        <a href="<?php echo BTT_PUBLIC_URL; ?>/trips.php" class="text-secondary">My Trips</a>
        <a href="<?php echo BTT_PUBLIC_URL; ?>/backpacks.php" class="text-secondary">My Backpacks</a>
        <a href="<?php echo BTT_PUBLIC_URL; ?>/test/ui-demo.html" class="text-secondary">UI Demo</a>
        <a href="<?php echo BTT_PUBLIC_URL; ?>/test/demo-spacing.html?ui=v2" class="text-secondary">New Design System</a>
    </div>
</div>

<!-- Floating Action Button -->
<button class="btn--fab btn--primary btn--pulse" onclick="window.location.href='<?php echo BTT_PUBLIC_URL; ?>/trips.php'" aria-label="Create new trip">
    ➕
</button>

<?php require_once __DIR__ . '/includes/template-footer.php'; ?>
