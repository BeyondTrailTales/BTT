<?php
// Shared header include for BeyondTrailTales MVP
// Expects app/config.php to be loaded by the parent page
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($pageTitle ?? 'BeyondTrailTales'); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($pageDescription ?? 'Trip planner with backpacks'); ?>">
  <link rel="stylesheet" href="<?php echo BTT_ASSETS_URL; ?>/css/main.css">
</head>
<body data-page="<?php echo htmlspecialchars($pageId ?? 'home'); ?>">
  <a class="skip-link" href="#main-content">Skip to content</a>
  <header class="header" role="banner">
    <div class="container header-content">
      <a class="logo" href="<?php echo BTT_PUBLIC_URL; ?>/index.php" aria-label="BeyondTrailTales Home">BeyondTrailTales</a>
      <nav class="nav-main" role="navigation" aria-label="Main Navigation">
        <a class="nav-link<?php echo ($pageId ?? '') === 'trips' ? ' active' : ''; ?>" href="<?php echo BTT_PUBLIC_URL; ?>/trips.php">Trips</a>
        <a class="nav-link<?php echo ($pageId ?? '') === 'backpacks' ? ' active' : ''; ?>" href="<?php echo BTT_PUBLIC_URL; ?>/backpacks.php">Backpacks</a>
      </nav>
    </div>
  </header>
  <main id="main-content" class="main-content container" role="main">
  <script>
    window.BTT = {
      baseUrl: "<?php echo BTT_BASE_URL; ?>",
      publicUrl: "<?php echo BTT_PUBLIC_URL; ?>",
      assetsUrl: "<?php echo BTT_ASSETS_URL; ?>",
      apiUrl: "<?php echo BTT_API_URL; ?>"
    };
  </script>

