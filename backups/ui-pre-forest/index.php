<?php
require_once dirname(__DIR__) . '/app/config.php';

$pageId = 'home';
$pageTitle = 'BeyondTrailTales - Trip Planning Made Simple';
$pageDescription = 'Plan your adventures with smart backpack management';

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Welcome to BeyondTrailTales</h1>
    <p class="page-description">Your simple trip planning companion with backpack management</p>
</div>

<div class="grid">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🎒 Manage Backpacks</h2>
        </div>
        <div class="card-body">
            <p>Create and organize your backpack configurations for different types of adventures.</p>
            <a href="<?php echo BTT_PUBLIC_URL; ?>/backpacks.php" class="btn btn-primary">View Backpacks</a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🏔️ Plan Trips</h2>
        </div>
        <div class="card-body">
            <p>Plan your trips with dates, locations, and attach the perfect backpack for your journey.</p>
            <a href="<?php echo BTT_PUBLIC_URL; ?>/trips.php" class="btn btn-primary">View Trips</a>
        </div>
    </div>
</div>

<div class="card">
    <h3>Quick Start Guide</h3>
    <ol>
        <li>Create a backpack configuration with your preferred gear weight</li>
        <li>Plan a trip with dates and location</li>
        <li>Attach your backpack to the trip</li>
        <li>Upload a photo with descriptive alt text for accessibility</li>
    </ol>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
