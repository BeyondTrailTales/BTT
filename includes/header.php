<?php
// Shared header include for BeyondTrailTales MVP
// Expects app/config.php to be loaded by the parent page

// Load authentication helpers
require_once dirname(dirname(__DIR__)) . '/app/bootstrap.php';
use App\Services\AuthService;

// Get current user if logged in
$currentUser = null;
if (AuthService::isAuthenticated()) {
    $currentUser = AuthService::getCurrentUser();
}
?>
<!doctype html>
<html lang="en" data-theme="forest-dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($pageTitle ?? 'BeyondTrailTales'); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($pageDescription ?? 'Trip planner with backpacks'); ?>">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Forest Theme CSS -->
  <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-tokens.css">
  <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-base.css">
  <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-components.css">
  <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/forest-animations.css">
  <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/auth-nav.css">
  
</head>
<body data-page="<?php echo htmlspecialchars($pageId ?? 'home'); ?>">
  <a class="skip-link" href="#main-content">Skip to content</a>
  
  <header class="header" role="banner">
    <div class="container header-content">
      <a class="logo" href="<?php echo BTT_PUBLIC_URL; ?>/index.php" aria-label="BeyondTrailTales Home">BeyondTrailTales</a>
      <nav class="nav-main" role="navigation" aria-label="Main Navigation">
        <a class="nav-link<?php echo ($pageId ?? '') === 'trips' ? ' active' : ''; ?>" href="<?php echo BTT_PUBLIC_URL; ?>/trips.php">Trips</a>
        <a class="nav-link<?php echo ($pageId ?? '') === 'backpacks' ? ' active' : ''; ?>" href="<?php echo BTT_PUBLIC_URL; ?>/backpacks.php">Backpacks</a>
        <a class="nav-link<?php echo ($pageId ?? '') === 'gear' ? ' active' : ''; ?>" href="<?php echo BTT_PUBLIC_URL; ?>/gear.php">My Gear</a>
        
        <?php if ($currentUser): ?>
          <!-- User is logged in -->
          <div class="nav-user-menu">
            <button class="nav-user-button" aria-expanded="false" aria-controls="user-menu">
              <span class="nav-user-name"><?php echo htmlspecialchars($currentUser['username'] ?? $currentUser['email']); ?></span>
              <svg class="icon-chevron" width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </button>
            <div class="nav-dropdown" id="user-menu" hidden>
              <a href="<?php echo BTT_PUBLIC_URL; ?>/profile.php" class="dropdown-link">Profile</a>
              <a href="<?php echo BTT_PUBLIC_URL; ?>/settings.php" class="dropdown-link">Settings</a>
              <div class="dropdown-divider"></div>
              <form method="post" action="<?php echo BTT_API_URL; ?>/?route=auth&id=logout" class="logout-form">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <button type="submit" class="dropdown-link logout-btn">Logout</button>
              </form>
            </div>
          </div>
        <?php else: ?>
          <!-- User is not logged in -->
          <div class="nav-auth-links">
            <a class="nav-link<?php echo ($pageId ?? '') === 'login' ? ' active' : ''; ?>" href="<?php echo BTT_PUBLIC_URL; ?>/auth/login.php">Login</a>
            <a class="nav-link nav-link-primary<?php echo ($pageId ?? '') === 'register' ? ' active' : ''; ?>" href="<?php echo BTT_PUBLIC_URL; ?>/auth/register.php">Sign Up</a>
          </div>
        <?php endif; ?>
      </nav>
    </div>
  </header>
  <main id="main-content" class="main-content container" role="main">
  <script>
    // Initialize BTT global configuration
    window.BTT = {
      baseUrl: "<?php echo BTT_BASE_URL; ?>",
      publicUrl: "<?php echo BTT_PUBLIC_URL; ?>",
      assetsUrl: "<?php echo BTT_ASSETS_URL; ?>",
      apiUrl: "<?php echo BTT_API_URL; ?>"
    };
  </script>
  <script src="<?php echo BTT_ASSETS_URL; ?>/js/navigation.js"></script>

