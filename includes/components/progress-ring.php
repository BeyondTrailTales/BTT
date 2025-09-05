<?php
/**
 * Progress Ring Component
 * Accessible SVG progress indicator with animation
 * 
 * @param int    $value     Current progress value (0-100)
 * @param string $label     Descriptive label for the progress
 * @param string $valueText Custom value text (optional, defaults to percentage)
 * @param int    $size      Ring size in pixels (default: 120)
 * @param string $id        Unique ID for gradient (required for multiple rings)
 */

// Set defaults
$value = isset($value) ? max(0, min(100, (int)$value)) : 0;
$label = isset($label) ? htmlspecialchars($label) : 'Progress';
$valueText = isset($valueText) ? htmlspecialchars($valueText) : $value . '%';
$size = isset($size) ? (int)$size : 120;
$id = isset($id) ? htmlspecialchars($id) : 'ring-' . uniqid();

// Calculate viewBox and radius based on size
$viewBox = "0 0 {$size} {$size}";
$center = $size / 2;
$strokeWidth = $size / 10; // Proportional stroke width
$radius = ($size - $strokeWidth) / 2 - 2; // Slight padding
?>

<div class="progress-ring" 
     role="progressbar" 
     aria-label="<?= $label ?>" 
     aria-valuenow="<?= $value ?>" 
     aria-valuemin="0" 
     aria-valuemax="100"
     style="--ring-size: <?= $size ?>px;">
    
    <svg width="<?= $size ?>" 
         height="<?= $size ?>" 
         viewBox="<?= $viewBox ?>" 
         aria-hidden="true">
        
        <!-- Gradient Definition -->
        <defs>
            <linearGradient id="<?= $id ?>-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#4ade80;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#fbbf24;stop-opacity:1" />
            </linearGradient>
        </defs>
        
        <!-- Background Ring -->
        <circle class="ring-bg" 
                cx="<?= $center ?>" 
                cy="<?= $center ?>" 
                r="<?= $radius ?>"
                style="stroke-width: <?= $strokeWidth ?>px;">
        </circle>
        
        <!-- Progress Ring -->
        <circle class="ring-progress" 
                cx="<?= $center ?>" 
                cy="<?= $center ?>" 
                r="<?= $radius ?>"
                data-progress="<?= $value ?>"
                style="stroke: url(#<?= $id ?>-gradient); stroke-width: <?= $strokeWidth ?>px;">
        </circle>
    </svg>
    
    <!-- Center Content -->
    <div class="ring-center">
        <span class="ring-value"><?= $valueText ?></span>
        <span class="ring-label"><?= $label ?></span>
    </div>
</div>
