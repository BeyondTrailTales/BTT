<?php
/**
 * Backpack Card Component
 */

require_once __DIR__ . '/card.php';

function render_backpack_card($backpack) {
    // Prepare badges
    $badges = [];
    if ($backpack['is_public'] == 1) {
        $badges[] = ['text' => '🌐 Public', 'type' => 'primary'];
    }
    if ($backpack['is_wishlist'] == 1) {
        $badges[] = ['text' => '✨ Wishlist', 'type' => 'warning'];
    }
    
    // Calculate item count
    $item_count = 0;
    if (!empty($backpack['items'])) {
        $item_count = is_array($backpack['items']) ? count($backpack['items']) : 
                     count(json_decode($backpack['items'], true) ?? []);
    }
    
    // Weight formatting
    $base_weight = formatWeight($backpack['base_weight'] ?? 0);
    $total_weight = formatWeight($backpack['total_weight'] ?? 0);
    
    // Prepare meta information
    $meta = [];
    
    // Item count
    $meta[] = ['icon' => '📦', 'text' => "{$item_count} items"];
    
    // Base weight
    $meta[] = ['icon' => '⚖️', 'text' => "Base: {$base_weight}"];
    
    // Total weight
    if ($total_weight !== $base_weight) {
        $meta[] = ['icon' => '🎒', 'text' => "Total: {$total_weight}"];
    }
    
    // Capacity
    if (!empty($backpack['capacity_liters'])) {
        $meta[] = ['icon' => '📏', 'text' => "{$backpack['capacity_liters']}L"];
    }
    
    // Last updated
    if (!empty($backpack['updated_at'])) {
        $updated = date('M j', strtotime($backpack['updated_at']));
        $meta[] = ['icon' => '📅', 'text' => "Updated {$updated}"];
    }
    
    // Prepare actions
    $actions = [
        [
            'label' => 'Pack Builder',
            'icon' => '🔧',
            'onclick' => "openPackBuilder({$backpack['id']})",
            'primary' => true
        ],
        [
            'label' => 'View',
            'icon' => '👁️',
            'onclick' => "viewBackpack({$backpack['id']})"
        ]
    ];
    
    // Prepare image - could be pack photo or default
    $image = $backpack['photo'] ?? asset_url('images/default-backpack.jpg');
    $image_alt = $backpack['photo_alt_text'] ?? $backpack['name'] ?? 'Backpack photo';
    
    // Get weight class for visual indicator
    $weight_class = getWeightClass($backpack['base_weight'] ?? 0);
    
    return render_card([
        'title' => $backpack['name'] ?? 'Untitled Pack',
        'subtitle' => $backpack['brand'] ?? '',
        'description' => $backpack['description'] ?? '',
        'image' => $image,
        'image_alt' => $image_alt,
        'badges' => $badges,
        'meta' => $meta,
        'actions' => $actions,
        'class' => 'backpack-card ' . $weight_class,
        'id' => 'backpack-' . $backpack['id'],
        'data_attributes' => [
            'backpack-id' => $backpack['id'],
            'base-weight' => $backpack['base_weight'] ?? 0,
            'total-weight' => $backpack['total_weight'] ?? 0,
            'is-public' => $backpack['is_public'] ?? 0
        ]
    ]);
}

function render_backpack_empty_state() {
    return render_empty_card([
        'icon' => '🎒',
        'title' => 'No backpacks yet',
        'subtitle' => 'Create your first pack to start building',
        'action_label' => 'Create First Pack',
        'action_onclick' => 'createNewBackpack()',
        'class' => 'backpack-empty-card'
    ]);
}

function render_backpacks_grid($backpacks) {
    if (empty($backpacks)) {
        return '<div class="cards-grid">' . render_backpack_empty_state() . '</div>';
    }
    
    $html = '<div class="cards-grid">';
    foreach ($backpacks as $backpack) {
        $html .= render_backpack_card($backpack);
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Format weight with appropriate unit
 */
function formatWeight($grams) {
    if ($grams === 0 || $grams === null) {
        return '0g';
    }
    
    if ($grams >= 1000) {
        $kg = round($grams / 1000, 2);
        return $kg . 'kg';
    }
    
    // For ounces conversion (optional)
    $oz = round($grams * 0.035274, 1);
    
    return "{$grams}g ({$oz}oz)";
}

/**
 * Get weight class for styling
 */
function getWeightClass($base_weight_grams) {
    if ($base_weight_grams < 4536) { // < 10 lbs
        return 'weight-ultralight';
    } elseif ($base_weight_grams < 6804) { // < 15 lbs
        return 'weight-light';
    } elseif ($base_weight_grams < 9072) { // < 20 lbs
        return 'weight-moderate';
    } else {
        return 'weight-heavy';
    }
}
?>
