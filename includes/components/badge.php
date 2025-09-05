<?php
/**
 * Achievement Badge Component
 * Displays an achievement badge with icon and label
 * 
 * @param string $title    Badge title/name
 * @param string $icon     Icon (emoji or image path)
 * @param string $state    Badge state: 'earned', 'available', 'locked'
 * @param string $hint     Tooltip hint text for locked badges
 * @param bool   $isImage  Whether icon is an image path vs emoji
 */

// Set defaults
$title = isset($title) ? htmlspecialchars($title) : 'Achievement';
$icon = isset($icon) ? $icon : '🏆';
$state = isset($state) ? $state : 'available';
$hint = isset($hint) ? htmlspecialchars($hint) : 'Complete challenges to unlock';
$isImage = isset($isImage) ? (bool)$isImage : false;

// State-based classes
$stateClass = 'achievement-badge';
switch($state) {
    case 'earned':
        $stateClass .= ' earned';
        $ariaLabel = "Achievement earned: {$title}";
        break;
    case 'locked':
        $stateClass .= ' locked';
        $ariaLabel = "Achievement locked: {$title}. {$hint}";
        $icon = '🔒'; // Override icon for locked state
        $title = '???'; // Hide title for locked badges
        break;
    default:
        $ariaLabel = "Achievement available: {$title}";
}
?>

<div class="<?= $stateClass ?>" 
     role="button"
     tabindex="0"
     aria-label="<?= $ariaLabel ?>"
     <?php if ($state === 'locked'): ?>
     title="<?= $hint ?>"
     <?php endif; ?>>
    
    <?php if ($isImage && $state !== 'locked'): ?>
        <img src="<?= htmlspecialchars($icon) ?>" 
             alt="" 
             aria-hidden="true" 
             class="badge-icon badge-icon-img"
             width="32" 
             height="32"
             loading="lazy">
    <?php else: ?>
        <span class="badge-icon" aria-hidden="true">
            <?= htmlspecialchars($icon) ?>
        </span>
    <?php endif; ?>
    
    <span class="badge-name">
        <?= $title ?>
    </span>
</div>
