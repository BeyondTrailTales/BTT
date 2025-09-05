<?php
/**
 * Streak Counter Component
 * Displays a streak counter with fire animation
 * 
 * @param int    $days  Number of days in streak
 * @param string $type  Type of streak (default: 'day')
 * @param bool   $animate Whether to animate on load
 */

// Set defaults
$days = isset($days) ? max(0, (int)$days) : 0;
$type = isset($type) ? htmlspecialchars($type) : 'day';
$animate = isset($animate) ? (bool)$animate : true;

// Pluralization
$typeLabel = $days === 1 ? $type : $type . 's';

// Animation class
$animationClass = $animate && $days > 0 ? 'streak-celebrate' : '';
?>

<div class="streak <?= $animationClass ?>" 
     aria-live="polite" 
     aria-atomic="true"
     role="status">
    <span class="streak-icon" aria-hidden="true">🔥</span>
    <span class="streak-count"><?= $days ?></span>
    <span class="streak-label"><?= $typeLabel ?> streak</span>
</div>
