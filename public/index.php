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
<div style="text-align: center; padding: 4rem 1rem; margin-bottom: 3rem; background: linear-gradient(135deg, rgba(10, 40, 24, 0.98) 0%, rgba(22, 78, 99, 0.95) 100%); border-radius: 1.5rem; backdrop-filter: blur(10px); border: 1px solid rgba(74, 222, 128, 0.2);">
    <h1 style="font-size: clamp(2.5rem, 5vw, 4rem); color: var(--forest-mint); margin-bottom: 1rem; text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);">🌲 Welcome back, Adventurer! 🏔️</h1>
    <p style="font-size: clamp(1.125rem, 2vw, 1.5rem); color: rgba(255, 255, 255, 0.9); margin-bottom: 2rem; max-width: 800px; margin-left: auto; margin-right: auto;">Ready for your next adventure?</p>
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
        <a href="<?php echo BTT_PUBLIC_URL; ?>/trips.php" class="btn btn-primary btn-lg">
            <span>🗺️</span> Plan New Trip
        </a>
        <a href="<?php echo BTT_PUBLIC_URL; ?>/backpacks.php" class="btn btn-secondary btn-lg">
            <span>🎒</span> Create Backpack
        </a>
    </div>
</div>

<!-- Main Features Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
    <!-- Plan Trip Card -->
    <div class="card card--interactive">
        <div class="card__content" style="text-align: center; padding: 2rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🗺️</div>
            <h2 class="card__title">Plan New Trip</h2>
            <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Start planning your next adventure</p>
            <a href="<?php echo BTT_PUBLIC_URL; ?>/trips.php" class="btn btn-primary" style="width: 100%;">
                Start Planning
            </a>
        </div>
    </div>
    
    <!-- Create Backpack Card -->
    <div class="card card--interactive">
        <div class="card__content" style="text-align: center; padding: 2rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🎒</div>
            <h2 class="card__title">Create Backpack</h2>
            <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Configure gear for your journey</p>
            <a href="<?php echo BTT_PUBLIC_URL; ?>/backpacks.php" class="btn btn-primary" style="width: 100%;">
                Create Backpack
            </a>
        </div>
    </div>
    
    <!-- Test Features Card -->
    <div class="card card--interactive">
        <div class="card__content" style="text-align: center; padding: 2rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🧪</div>
            <h2 class="card__title">Test Features</h2>
            <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Try out gamification systems</p>
            <a href="<?php echo BTT_PUBLIC_URL; ?>/test/" class="btn btn-secondary" style="width: 100%;">
                Explore Tests
            </a>
        </div>
    </div>
</div>

<!-- Adventure Stats -->
<div style="background: var(--surface-primary); border: 1px solid var(--glass-border); border-radius: 1rem; padding: 2rem; margin-bottom: 3rem; backdrop-filter: blur(10px);">
    <h2 style="color: var(--forest-mint); margin-bottom: 2rem; text-align: center;">🏆 Your Adventure Stats</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1.5rem; text-align: center;">
        <div>
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🗺️</div>
            <div style="font-size: 2rem; color: var(--forest-mint); font-weight: bold;">2</div>
            <div style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.875rem;">TRIPS PLANNED</div>
        </div>
        <div>
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🎒</div>
            <div style="font-size: 2rem; color: var(--forest-mint); font-weight: bold;">4</div>
            <div style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.875rem;">BACKPACKS CREATED</div>
        </div>
        <div>
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">⭐</div>
            <div style="font-size: 2rem; color: var(--forest-mint); font-weight: bold;">3</div>
            <div style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.875rem;">CURRENT LEVEL</div>
        </div>
        <div>
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔥</div>
            <div style="font-size: 2rem; color: var(--forest-mint); font-weight: bold;">2</div>
            <div style="color: var(--text-secondary); text-transform: uppercase; font-size: 0.875rem;">DAY STREAK</div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div style="background: var(--surface-primary); border: 1px solid var(--glass-border); border-radius: 1rem; padding: 2rem; margin-bottom: 3rem; backdrop-filter: blur(10px);">
    <h2 style="color: var(--forest-mint); margin-bottom: 2rem;">📅 Recent Activity</h2>
    <div style="display: grid; gap: 1rem;">
        <div style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="color: var(--text-primary); margin: 0 0 0.25rem;">Jacks Trip</h3>
                <span style="color: var(--text-secondary); font-size: 0.875rem;">🚫 Not set</span>
            </div>
            <a href="<?php echo BTT_PUBLIC_URL; ?>/trips.php" class="btn btn-ghost btn-sm">View</a>
        </div>
        <div style="background: rgba(0, 0, 0, 0.2); padding: 1rem; border-radius: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="color: var(--text-primary); margin: 0 0 0.25rem;">Emily Trip</h3>
                <span style="color: var(--text-secondary); font-size: 0.875rem;">📅 10/14/2025</span>
            </div>
            <a href="<?php echo BTT_PUBLIC_URL; ?>/trips.php" class="btn btn-ghost btn-sm">View</a>
        </div>
    </div>
</div>

<!-- Quick Links Footer -->
<div style="text-align: center; padding: 2rem; margin-top: 4rem; border-top: 1px solid rgba(74, 222, 128, 0.2);">
    <h3 style="color: var(--forest-mint); margin-bottom: 1.5rem;">Quick Links</h3>
    <div style="display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap;">
        <a href="<?php echo BTT_PUBLIC_URL; ?>/dashboard.php" style="color: var(--text-secondary); text-decoration: none;">Dashboard</a>
        <a href="<?php echo BTT_PUBLIC_URL; ?>/trips.php" style="color: var(--text-secondary); text-decoration: none;">My Trips</a>
        <a href="<?php echo BTT_PUBLIC_URL; ?>/backpacks.php" style="color: var(--text-secondary); text-decoration: none;">My Backpacks</a>
        <a href="<?php echo BTT_PUBLIC_URL; ?>/test/ui-demo.html" style="color: var(--text-secondary); text-decoration: none;">UI Demo</a>
    </div>
</div>

<!-- Floating Action Button -->
<button class="fab pulse" onclick="window.location.href='<?php echo BTT_PUBLIC_URL; ?>/trips.php'">
    ➕
</button>

<?php require_once __DIR__ . '/includes/template-footer.php'; ?>
